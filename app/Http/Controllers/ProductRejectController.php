<?php

namespace App\Http\Controllers;

use App\Models\ProdtrV;
use App\Models\ProductV;
use App\Models\StockOpName;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exports\ProductRejectExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductRejectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $state = "active")
    {
        return view('Report.product-reject-main', ['state' => $state]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function export()
    {
        $validator = Validator::make(request()->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'keyword' => 'nullable|string|max:255',
            'warehouseId' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['warehouseId'] = request()->filled('warehouseId') ? $validated['warehouseId'] : 'REJ';

        $warehouseId = $validated['warehouseId'];
        if ($warehouseId == 'REJ') {
            $fileNameWarehouse = 'Reject';
        } else if ($warehouseId == 'SCR') {
            $fileNameWarehouse = 'Scrap';
        } else {
            $fileNameWarehouse = $warehouseId;
        }

        $validated['fromDate'] = request()->filled('fromDate') ? Carbon::parse($validated['fromDate'])->toDateString() : Carbon::now()->toDateString();
        $validated['toDate'] = request()->filled('toDate') ? Carbon::parse($validated['toDate'])->toDateString() : Carbon::now()->toDateString();

        $fromDateToDate = $validated['fromDate'] . "-" . $validated['toDate'];

        $path = 'reports/';
        $fileName = 'products-' . $fileNameWarehouse . '-' . $fromDateToDate . '.xlsx';
        $fullPathName = $path . $fileName;

        $toast = [
            'showToast' => [
                'message' => 'Ekspor sedang diproses. File akan tersedia setelah selesai.',
                'type' => 'success' // Tipe bisa: 'success', 'error', 'info'
            ]
        ];

        $pollingView = view('components.hx.pool', ['filename' => $fileName])->render();

        // check if the file exist, is yes delete
        if (Storage::disk('public')->exists($fullPathName)) {
            Storage::disk('public')->delete($fullPathName);
        }

        (new ProductRejectExport($validated))->store($fullPathName, 'public');

        return response($pollingView)->header('HX-Trigger-toast', json_encode($toast));
    }

    public function hxSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'keyword' => 'nullable|string|max:255',
            'warehouseId' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';


        $validated['warehouseId'] = $request->filled('warehouseId') ? $validated['warehouseId'] : 'REJ';
        $validated['toDate'] = $request->filled('toDate') ? Carbon::parse($validated['toDate'])->toDateString() : Carbon::now()->toDateString();
        $validated['fromDate'] = $request->filled('fromDate') ? Carbon::parse($validated['fromDate'])->toDateString() : Carbon::now()->toDateString();

        // 2. Subquery for stock opname (remains the same)
        $stockOpnameSubquery = StockOpname::query()
            ->select('productId', DB::raw('ROUND(COALESCE(SUM(adjustedQty), 0), 3) as totalAdjustedQty'))
            ->where('warehouseId', $validated['warehouseId'])
            ->where('posted', 1)
            ->whereBetween('transDate', [$validated['fromDate'], $validated['toDate']])
            ->groupBy('productId');

        // 3. The final, corrected query
        $products = ProdtrV::query()
            ->join('product_v', 'prodtr_v.productId', '=', 'product_v.productId')
            ->leftJoinSub($stockOpnameSubquery, 'sto', function ($join) {
                $join->on('prodtr_v.productId', '=', 'sto.productId');
            })
            ->select([
                'prodtr_v.productId',
                'product_v.productName',
                'product_v.unitId',
                'sto.totalAdjustedQty as stockOphname'
            ])

            // THE FIX: ABS() is now outside the SUM() for all calculations to perfectly match the C# logic.
            ->selectRaw("ROUND(ABS(COALESCE(SUM(CASE WHEN prodtr_v.transDate < ? AND prodtr_v.type IN ('InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked') THEN prodtr_v.originalQty ELSE 0 END), 0)), 3) as saldoAwal", [$validated['fromDate']])
            ->selectRaw("ROUND(ABS(COALESCE(SUM(CASE WHEN prodtr_v.transDate BETWEEN ? AND ? AND prodtr_v.type = 'InvAdjust_In' THEN prodtr_v.originalQty ELSE 0 END), 0)), 3) as masuk", [$validated['fromDate'], $validated['toDate']])
            ->selectRaw("ROUND(ABS(COALESCE(SUM(CASE WHEN prodtr_v.transDate BETWEEN ? AND ? AND prodtr_v.type = 'InvAdjust_Out' THEN prodtr_v.originalQty ELSE 0 END), 0)), 3) as keluar", [$validated['fromDate'], $validated['toDate']])

            ->where('prodtr_v.warehouseCode', $validated['warehouseId'])

            ->when($keyword, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('prodtr_v.productId', 'like', $searchTerm)
                        ->orWhere('product_v.productName', 'like', $searchTerm);
                });
            })

            ->groupBy('prodtr_v.productId', 'product_v.productName', 'product_v.unitId', 'sto.totalAdjustedQty')
            ->havingRaw('SUM(CASE WHEN prodtr_v.transDate BETWEEN ? AND ? THEN 1 ELSE 0 END) > 0', [$validated['fromDate'], $validated['toDate']])

            ->orderBy('prodtr_v.productId')
            ->cursorPaginate(50)->withQueryString();

        return view('Response.Report.ProductReject.search', compact('products'));
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
}
