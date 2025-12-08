<?php

namespace App\Http\Controllers;

use App\Models\SalespickV;
use Illuminate\Http\Request;
use App\Models\SalesPickBomV;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class InventOutMainController extends Controller
{
    public function index()
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


        return view('Report.invent-out-main');
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
        $keywords = $request->input('keyword');

        $fileName = 'Laporan_Pengeluaran_Barang_' . ($fromDate ?? '') . '_' . ($toDate ?? '') . '.xlsx';
        $path = 'reports/';
        $fullPathName = $path . $fileName;

        (new \App\Exports\ExportInvtOutMain($fromDate, $toDate, $keywords))->store($fullPathName, 'public');

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

        $prod_receipt = SalespickV::orderBy("registrationDate", "desc")
            ->orderBy("invoiceId")
            ->orderBy("ItemId")
            ->orderBy("amount")
            ->orderBy("price")
            ->where('isCancel', 0);

        $keyword = $request->input('keyword');


        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate'))->toDateString() : Carbon::now()->toDateString();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate'))->toDateString() : Carbon::now()->toDateString();
        $prod_receipt = $prod_receipt->whereBetween('registrationDate', [$fromDate, $toDate]);

        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $searchTerm = '%' . $keyword . '%';
                    $q->where('requestNo', 'like', $searchTerm)
                        ->orWhere('docBc', 'like', $searchTerm)
                        ->orWhere('registrationNo', 'like', $searchTerm)
                        ->orWhere('InvoiceId', 'like', $searchTerm)
                        ->orWhere('CustName', 'like', $searchTerm)
                        ->orWhere('ItemId', 'like', $searchTerm)
                        ->orWhere('ItemName', 'like', $searchTerm);
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->cursorPaginate(500, [
                'salesPickLineRecId',
                'transDate',
                'requestNo',
                'docBc',
                'registrationNo',
                'registrationDate',
                'invoiceId',
                'invoiceDate',
                'custName',
                'ItemId',
                'ItemName',
                'unit',
                'qty',
                'currencyCode',
                'price',
                'amount',
                'Notes',
                'PickCode'
            ])->withQueryString();
        // return response()->json($prod_receipt);

        return view('Response.Report.InventOutMain.search', compact('prod_receipt'));
    }
}
