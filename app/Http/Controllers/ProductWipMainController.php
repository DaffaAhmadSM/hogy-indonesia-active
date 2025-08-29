<?php

namespace App\Http\Controllers;

use App\Models\ProdtrV;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

    public function export()
    {

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

        $cacheKey = 'invent_out_main_' . md5(serialize($request->all()));
        $cacheDuration = now()->addMinutes(10);

        $prod_tr = ProdtrV::where('warehouseCode', 'WIP')
            ->whereIn('type', [
                'InvAdjust_In',
                'InvAdjust_Out',
                'Po_Picked',
                'So_Picked'
            ])
            ->orderBy('productId')
            ->orderBy('productName')
            ->orderBy('unitId');

        $fromDate = Carbon::createFromFormat('Y-m-d', "1990-01-01")->startOfDay();

        $keyword = $request->input('keyword');

        if ($request->input('asofDate') != null) {

            $asofDate = Carbon::createFromFormat('Y-m-d', $request->input('asofDate'))->startOfDay();

            $prod_tr = $prod_tr->whereBetween('transDate', [$fromDate, $asofDate]);

        } else {
            $asofDate = Carbon::now()->startOfDay();
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
                    DB::raw('SUM(originalQty) as jumlah')
                )
                ->cursorPaginate(100);
        });

        return view('Response.Report.ProductWipMain.search', compact('prod_tr'));
    }
}
