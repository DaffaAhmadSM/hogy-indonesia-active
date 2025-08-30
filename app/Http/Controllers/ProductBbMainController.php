<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ProductV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProductBbMainController extends Controller
{
    public function index()
    {
        return view('Report.product-bb-main');
    }

    public function export()
    {
        // Logic for exporting data
    }


    public function hxSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'keyword' => 'nullable|string|max:255',
            'warehouseId' => 'nullable|string|max:50',
        ]);


        $fromDate = $request->input('fromDate', Carbon::now()->startOfMonth()->toDateString());
        $toDate = $request->input('toDate', Carbon::now()->endOfMonth()->toDateString());
        $warehouseId = $request->input('warehouseId', 'WH');

        $cacheKey = "product_bb_main_{$fromDate}_{$toDate}_{$warehouseId}_" . md5($request->input('keyword', ''));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';

        // 2. Build the SINGLE, efficient query starting from the Product model
        $products = ProductV::query()
            ->select([
                'product_v.productId',
                'product_v.productName',
                'product_v.unitId',
            ])
            // Use selectRaw to perform calculations securely with parameter binding
            ->selectRaw("
               ROUND(COALESCE(SUM(CASE 
                    WHEN trans.transDate < ? AND trans.type IN ('InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked') 
                    THEN trans.originalQty 
                    ELSE 0 
                END), 0), 4) as saldoAwal
            ", [$fromDate])
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN trans.transDate BETWEEN ? AND ? AND trans.type IN ('InvAdjust_In', 'Po_Picked') THEN trans.originalQty ELSE 0 END), 0), 4) as masuk", [$fromDate, $toDate])
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN trans.transDate BETWEEN ? AND ? AND trans.type IN ('InvAdjust_Out', 'So_Picked') THEN ABS(trans.originalQty) ELSE 0 END), 0), 4) as keluar", [$fromDate, $toDate])
            ->selectRaw("ROUND(COALESCE(SUM(sto.adjustedQty), 0), 4) as stockOphname")

            // LEFT JOIN to include products even if they have no transactions
            ->leftJoin('prodtr_v as trans', function ($join) use ($warehouseId) {
                $join->on('product_v.productId', '=', 'trans.productId')
                    ->where('trans.warehouseCode', '=', $warehouseId);
            })

            // LEFT JOIN for stock opname data, with multiple conditions
            ->leftJoin('stockoph_v as sto', function ($join) use ($warehouseId, $fromDate, $toDate) {
                $join->on('product_v.productId', '=', 'sto.productId')
                    ->where('sto.warehouseId', '=', $warehouseId)
                    ->where('sto.posted', '=', 1)
                    ->whereBetween('sto.transDate', [$fromDate, $toDate]);
            })

            // Filter products and transactions
            ->where('product_v.productType', 'BAHAN_BAKU')

            // Apply keyword search if provided
            ->when($keyword, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('product_v.productId', 'like', $searchTerm)
                        ->orWhere('product_v.productName', 'like', $searchTerm)
                        ->orWhere('product_v.unitId', 'like', $searchTerm);
                });
            })

            // Group by the product fields to make SUM() work correctly
            ->groupBy('product_v.productId', 'product_v.productName', 'product_v.unitId');

            // Paginate the final results
           
            $products = Cache::remember($cacheKey, 300, function () use ($products) {
                return $products->cursorPaginate(400);
            });

        // return response()->json($products);

        return view('Response.Report.ProductBbMain.search', compact('products'));
    }
}
