<?php

namespace App\Http\Controllers;

use App\Models\ReportPengeluaran;
use App\Models\SalespickV;
use Illuminate\Http\Request;
use App\Models\SalesPickBomV;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class InventOutMainController extends Controller
{
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


        // return response()->json($prod_receipt->items());


        return view('Report.invent-out-main', ['state' => $state]);
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
    public function show(string $salesPickLineRecId)
    {

        try {
            // Use the Eloquent model to build the query
            $salesPicks = SalesPickBomV::query()
                ->select([
                    'SalesPickLineRecId',
                    'InventTransIdFg',
                    'ItemId',
                    'ItemName',
                    'Qty',
                    'Unit',
                    'DocBc',
                    'RequestNo',
                    'RegistrationNo',
                    'RegistrationDate',
                    'InvoiceId',
                    'InvoiceDate',
                    'InvJournalId',
                    'WorksheetId'
                ])
                ->where('SalesPickLineRecId', $salesPickLineRecId)
                ->orderBy('SalesPickLineRecId', 'asc')
                ->get();

            // return $salesPicks;

            return view('Response.Report.InventOutMain.detail', compact('salesPicks'));
        } catch (QueryException $e) {
            // Log the error for debugging.
            Log::error("Database error in findListSalesPickBom: " . $e->getMessage());

            $salesPicks = collect();
            // Return an empty collection on failure, similar to the original C# code.
            return view('Response.Report.InventOutMain.detail', compact('salesPicks'));
        }
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

        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate'))->toDateString() : Carbon::now()->toDateString();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate'))->toDateString() : Carbon::now()->toDateString();

        // Validate date range not exceeding 31 days
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);
        if ($from->diffInDays($to) > 31) {
            $toast = ['showToast' => ['message' => 'Rentang tanggal tidak boleh lebih dari 31 hari!', 'type' => 'error']];
            return response('')->header('HX-Trigger-toast', json_encode($toast));
        }
        $keywords = $request->input('keyword');

        $fileName = 'Laporan_Pengeluaran_Barang_' . ($fromDate ?? '') . '_' . ($toDate ?? '') . '.xlsx';
        $path = 'reports/';
        $fullPathName = $path . $fileName;

        // Delete existing file if exists
        if (\Storage::disk('public')->exists($fullPathName)) {
            \Storage::disk('public')->delete($fullPathName);
        }

        (new \App\Exports\ExportInvtOutMain($fromDate, $toDate, $keywords))->queue($fullPathName, 'public');

        $toast = [
            'showToast' => [
                'message' => 'Ekspor sedang diproses. File akan tersedia setelah selesai.',
                'type' => 'success' // Tipe bisa: 'success', 'error', 'info'
            ]
        ];
        $pollingView = view('components.hx.pool', ['filename' => $fileName, 'checkRoute' => 'report.inventOutMain.export-status'])->render();


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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fromDate = $request->filled('fromDate') ? $request->input('fromDate') : Carbon::now()->toDateString();
        $toDate = $request->filled('toDate') ? $request->input('toDate') : Carbon::now()->toDateString();

        // Validate date range not exceeding 31 days
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);
        if ($from->diffInDays($to) > 31) {
            $toast = ['showToast' => ['message' => 'Rentang tanggal tidak boleh lebih dari 31 hari!', 'type' => 'error']];
            return response('')->header('HX-Trigger-toast', json_encode($toast));
        }

    $columns = [
        "RECID",
        "BCTYPE",
        "NOMORDAFTAR",
        "TANGGALDAFTAR",
        "NOMORPENGIRIMAN",
        "PENERIMA",
        "NOMORDAFTAR",
        "TANGGALDAFTAR",
        "TANGGALPENGIRIMAN",
        "PENERIMA",
        "KODEBARANG",
        "NAMABARANG",
        "JUMLAH",
        "SATUAN",
        "CURRENCY",
        "NILAI",
        ];

        $tableName = (new ReportPengeluaran())->getTable();
        $prod_receipt = ReportPengeluaran::select($columns)
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
                        ->orWhere("$tableName.NOMORPENGIRIMAN", 'like', "%$keyword%")
                        ->orWhere("$tableName.PENERIMA", 'like', "%$keyword%");
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->cursorPaginate(500)->withQueryString();
        // return response()->json($prod_receipt);

        return view('Response.Report.InventOutMain.search', compact('prod_receipt'));
    }
}
