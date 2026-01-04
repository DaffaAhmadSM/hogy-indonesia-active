<?php

namespace App\Http\Controllers;

use App\Models\ProdreceiptV;
use App\Models\ReportPemasukan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class InventInMainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($state = "active")
    {
        // $validator = Validator::make(request()->all(), [
        //     'fromDate' => 'required|date',
        //     'toDate' => 'required|date|after_or_equal:fromDate',
        //     'keyword' => 'nullable|string|max:255',
        // ]);

        // if ($validator->fails()) {
        //     return redirect()->back()->withErrors($validator)->withInput();
        // }

        $fromDate = Carbon::now()->startOfMonth();
        $toDate = Carbon::now()->endOfMonth();


        return view('Report.invent-in-main', ['state' => $state]);
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
    public function show()
    {
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
            'fromDate' => 'nullable|date|date_format:Y-m-d',
            'toDate' => 'nullable|date|after_or_equal:fromDate|date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate'))->toDateString() : Carbon::now();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate'))->toDateString() : Carbon::now();
        $keywords = $request->input('keyword');

        $fileName = 'Laporan_Pemasukan_Barang_' . ($fromDate ?? '') . '_' . ($toDate ?? '') . '.xlsx';
        $path = 'reports/';
        $fullPathName = $path . $fileName;

        // Delete existing file if present
        if (\Storage::disk('public')->exists($fullPathName)) {
            \Storage::disk('public')->delete($fullPathName);
        }

        // Queue the export job
        (new \App\Exports\ExportEnvtInMain($fromDate, $toDate, $keywords))->queue($fullPathName, 'public');

        $toast = ['showToast' => ['message' => 'Ekspor akan siap dalam beberapa saat.', 'type' => 'success']];
        $pollingView = view('components.hx.pool', ['filename' => $fileName, 'checkRoute' => 'report.inventInMain.export-status'])->render();


        return response($pollingView)->header('HX-Trigger-toast', json_encode($toast));
    }

    public function hxSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'date|date_format:Y-m-d',
            'toDate' => 'nullable|date|after_or_equal:fromDate|date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        // return ReportPemasukan::cursorPaginate(10);

        $columns = [
                "BCTYPE",
                "NOMORDAFTAR",
                "TANGGALDAFTAR",
                "NOMORPENERIMAAN",
                "PENGIRIM",
                "NOMORDAFTAR",
                "TANGGALDAFTAR",
                "TANGGALPENERIMAAN",
                "PENGIRIM",
                "KODEBARANG",
                "NAMABARANG",
                "JUMLAH",
                "SATUAN",
                "NILAI",
        ];

        $tableName = (new ReportPemasukan)->getTable();
        $prod_receipt = ReportPemasukan::select($columns)
        ->selectRaw("CASE 
            WHEN BCTYPE = 9 THEN 'BC40'
            WHEN BCTYPE = 10 THEN 'BC27'
            WHEN BCTYPE = 11 THEN 'BC23'
            WHEN BCTYPE = 12 THEN 'BC262'
            WHEN BCTYPE = 13 THEN 'BC30'
            WHEN BCTYPE = 14 THEN 'BC25'
            WHEN BCTYPE = 15 THEN 'BC261'
            WHEN BCTYPE = 16 THEN 'BC27'
            WHEN BCTYPE = 17 THEN 'BC41'
            ELSE 'UNKNOWN'
        END as BC_CODE_NAME")
        ->orderBy("$tableName.NOMORDAFTAR", "desc")
        ->orderBy("$tableName.KODEBARANG");

        $keyword = $request->input('keyword');

        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate'))->toDateString() : Carbon::now();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate'))->toDateString() : Carbon::now();
        $prod_receipt = $prod_receipt->whereBetween("$tableName.TANGGALDAFTAR", [$fromDate, $toDate]);


        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) use ($tableName) {
                $query->where(function ($q) use ($keyword, $tableName) {
                    $q->where("$tableName.NOMORDAFTAR", 'like', "%$keyword%")
                        ->orWhere("$tableName.KODEBARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NAMABARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NOMORPENERIMAAN", 'like', "%$keyword%")
                        ->orWhere("$tableName.PENGIRIM", 'like', "%$keyword%");
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->cursorPaginate(500)->withQueryString();
        // return $prod_receipt;

        return view('Response.Report.InventInMain.search', compact('prod_receipt'));
    }
}
