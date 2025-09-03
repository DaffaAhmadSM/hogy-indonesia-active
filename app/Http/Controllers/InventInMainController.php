<?php

namespace App\Http\Controllers;

use App\Models\ProdreceiptV;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class InventInMainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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


        return view('Report.invent-in-main');
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

        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate')) : Carbon::now();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate')) : Carbon::now();
        $keywords = $request->input('keyword');

        $fileName = 'Laporan_Pemasukan_Barang_' . ($fromDate->toDateString() ?? '') . '_' . ($toDate->toDateString() ?? '') . '.xlsx';

        return (new \App\Exports\ExportEnvtInMain($fromDate, $toDate, $keywords))->download($fileName);
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

        $prod_receipt = ProdreceiptV::orderBy("invDateVend", "desc")
            ->orderBy("purchRecId")
            ->orderBy("ItemId")
            ->where('isCancel', 0);

        $keyword = $request->input('keyword');

        $fromDate = $request->filled('fromDate') ? Carbon::createFromFormat('Y-m-d', $request->input('fromDate')) : Carbon::now();
        $toDate = $request->filled('toDate') ? Carbon::createFromFormat('Y-m-d', $request->input('toDate')) : Carbon::now();
        $prod_receipt = $prod_receipt->whereBetween('invDateVend', [$fromDate, $toDate]);


        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('purchRecId', 'like', "%$keyword%")
                        ->orWhere('ItemId', 'like', "%$keyword%")
                        ->orWhere('ItemName', 'like', "%$keyword%")
                        ->orWhere('VendName', 'like', "%$keyword%");
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->cursorPaginate(50, [
                'purchRecId',
                'transDate',
                'requestNo',
                'docBc',
                'registrationNo',
                'registrationDate',
                'invNoVend',
                'invDateVend',
                'VendName',
                'ItemId',
                'ItemName',
                'unit',
                'qty',
                'currencyCode',
                'price',
                'amount',
                'notes',
                'PackCode'
            ])->withQueryString();

        return view('Response.Report.InventInMain.search', compact('prod_receipt'));
    }
}
