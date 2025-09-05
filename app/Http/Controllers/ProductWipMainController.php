<?php

namespace App\Http\Controllers;

use App\Models\ProdtrV;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Exports\ProductWipExport;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessProductWipExport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductWipMainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('Report.product-wip-main');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asofDate' => 'nullable|date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $toast = ['showToast' => ['message' => $validator->errors()->first(), 'type' => 'error']];
            return response('')->header('HX-Trigger', json_encode($toast));
        }

        $asofDate = $request->filled('asofDate') ? $request->input('asofDate') : Carbon::now()->toDateString();
        $keyword = $request->filled('keyword') ? $request->input('keyword') : null;

        $filename = 'wip-report-' . '-' . $asofDate . '-' . $keyword . '.xlsx';
        $filePath = 'reports/' . $filename;

        new ProductWipExport($asofDate, $keyword)->store($filePath, 'public');

        if (Storage::disk('public')->exists($filePath)) {
           Storage::disk('public')->delete($filePath);
        }
        // Kirim job ke antrian

        $toast = ['showToast' => ['message' => 'Ekspor dimulai...', 'type' => 'info']];
        $pollingView = view('components.hx.pool', ['filename' => $filename, 'checkRoute' => 'report.wip.export-status'])->render();

        return response($pollingView)->header('HX-Trigger-toast', json_encode($toast));
    }

    public function download($filename)
    {
        $filePath = 'reports/' . $filename;

        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->download($filePath);
        }

        $toast = ['showToast' => ['message' => 'File not found, please export the report again.', 'type' => 'error']];
        return response('')->header('HX-Trigger-toast', json_encode($toast));
    }

    /**
     * Memeriksa status ekspor dan mengembalikan view yang sesuai.
     */
    public function checkWipExportStatus($filename)
    {
        $filePath = 'reports/' . $filename;

        if (Storage::disk('public')->exists($filePath)) {
            $fileUrl = Storage::disk('public')->url($filePath);
            Cache::forget('export-status-wip-' . $filename);
            return view('components.hx.download-button', ['fileUrl' => $fileUrl, 'filename' => $filename]);
        }
    }

    public function hxSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asofDate' => 'date|date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        $prod_tr = ProdtrV::where('warehouseCode', 'WIP')
            ->whereIn('type', [
                'InvAdjust_In',
                'InvAdjust_Out',
                'Po_Picked',
                'So_Picked'
            ])
            ->orderBy('productId')
            ->orderBy('unitId');

        $fromDate = Carbon::createFromFormat('Y-m-d', "1990-01-01");

        $keyword = $request->input('keyword');

        if ($request->input('asofDate') != null) {

            $asofDate = Carbon::createFromFormat('Y-m-d', $request->input('asofDate'));

            $prod_tr = $prod_tr->whereBetween('transDate', [$fromDate, $asofDate]);

        } else {
            $asofDate = Carbon::now();
            $prod_tr = $prod_tr->whereBetween('transDate', [$fromDate, $asofDate]);
        }

        if ($keyword != null) {
            $keyword = $request->input('keyword');
            $prod_tr->where(function ($query) use ($keyword) {
                $searchTerm = '%' . $keyword . '%';
                $query->where('productId', 'like', $searchTerm)
                    ->orWhere('productName', 'like', $searchTerm)
                    ->orWhere('unitId', 'like', $searchTerm);
            });
        }

        $cursor = $request->filled('cursor') ? $request->input('cursor') : "first_page";
        $cacheKey = 'invent_out_main_' . $fromDate->toDateString() . '_' . $asofDate->toDateString() . '_' . $request->input('keyword') . '_' . $cursor;
        $cacheDuration = now()->addMinutes(10);


        // $prod_tr = $prod_tr
        //     ->groupBy('productId', 'productName', 'unitId')
        //     ->select(
        //         'productId',
        //         'productName',
        //         'unitId',
        //         DB::raw('SUM(originalQty) as jumlah')
        //     )
        //     ->cursorPaginate(200);

        $prod_tr = Cache::remember($cacheKey, $cacheDuration, function () use ($prod_tr) {
            return $prod_tr
                ->groupBy('productId', 'productName', 'unitId')
                ->select(
                    'productId',
                    'productName',
                    'unitId',
                    DB::raw('ROUND(SUM(originalQty), 2) as jumlah')
                )
                ->cursorPaginate(300)->withQueryString();
        });

        return view('Response.Report.ProductWipMain.search', compact('prod_tr'));
    }
}
