<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ProductV;
use Illuminate\Http\Request;
use App\Exports\ExportProductBB;
use App\Jobs\ProcessProductBBExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductBbMainController extends Controller
{
    public function index(string $type)
    {

        $title = 'Laporan PertanggungJawaban Mutasi ';
        switch ($type) {
            case 'BB':
                $type = 'BAHAN_BAKU';
                $title .= 'Bahan Baku';
                break;
            case 'BP':
                $type = 'BAHAN_PENOLONG';
                $title .= 'Bahan Penolong';
                break;
            case 'BJ':
                $type = 'BARANG_JADI';
                $title .= 'Barang Jadi';
                break;
            case 'MP':
                $type = "MESIN;PERALATAN";
                $title .= 'Mesin & Peralatan';
                break;
            default:
                abort(404, 'Invalid product type');
        }

        return view('Report.product-bb-main', compact('type', 'title'));
    }

    public function export(string $type, Request $request)
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

        // 2. Ambil input
        // $fromDate = $validated['fromDate'] ?? Carbon::now()->toDateString();
        $fromDate = Carbon::parse($validated['fromDate'] ?? Carbon::now()->toDateString())->toDateString();
        $toDate = Carbon::parse($validated['toDate'] ?? Carbon::now()->toDateString())->toDateString();
        $warehouseId = $validated['warehouseId'] ?? 'WH';
        $productType = $type ?: 'BAHAN_BAKU';
        $keyword = $validated['keyword'] ?? null;

        $productTypeArray = explode(';', $productType);

        // 3. Masukkan ekspor ke dalam antrian
        $fromDateToDate = $fromDate . "-" . $toDate;

        $path = 'reports/';
        $fileName = 'products-' . $type . '-' . $fromDateToDate . '.xlsx';
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

        // `queue()` akan secara otomatis menjalankan ekspor di background.
        // File akan disimpan di storage/app/public/reports/...
        // Pastikan Anda sudah menjalankan `php artisan storage:link`
        $export = new ExportProductBB($fromDate, $toDate, $warehouseId, $productTypeArray, $keyword);

        // Kirim job ke antrian hanya dengan path file
        ProcessProductBBExport::dispatch($export, $fullPathName);
        // 4. Kirim respons dengan header HX-Trigger untuk menampilkan toast


        return response($pollingView)->header('HX-Trigger-toast', json_encode($toast));

        // 4. Beri respons langsung ke pengguna
        // return redirect()->back()->with('success', 'Ekspor sedang diproses. File akan tersedia untuk diunduh setelah selesai.');
    }

    public function checkExportStatus($filename, Request $request)
    {
        $filePath = 'reports/' . $filename;

        // Periksa apakah file sudah ada di storage publik
        if (Storage::disk('public')->exists($filePath)) {
            // Jika ada, kembalikan tombol download
            $fileUrl = $request->getSchemeAndHttpHost() . "/storage/" . $filePath;
            return view('components.hx.download-button', ['fileUrl' => $fileUrl, 'filename' => $filename]);
        } else {
            // Jika belum, kembalikan status "processing"
            // Ini akan membuat HTMX terus melakukan polling
            return view('components.hx.pool', ['filename' => $filename]);
        }
    }


    public function hxSearch(Request $request, string $type)
    {
        // 1. VALIDATION
        $validator = Validator::make($request->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'keyword' => 'nullable|string|max:255',
            'warehouseId' => 'nullable|string|max:50',
            // 'productType' is from the URL, so no need to validate it from request input
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // 2. SET DEFAULTS & GET INPUTS
        // Use the validated data, falling back to defaults if not present.
        // $fromDate = $validated['fromDate'] ?? Carbon::now()->toDateString();
        $fromDate = Carbon::parse($validated['fromDate'] ?? Carbon::now()->toDateString())->toDateString();
        $toDate = Carbon::parse($validated['toDate'] ?? Carbon::now()->toDateString())->toDateString();
        $warehouseId = $validated['warehouseId'] ?? 'WH';
        $productType = $type ?: 'BAHAN_BAKU'; // Use URL type, with a fallback
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';


        // 3. CACHE KEY (IMPROVED LOGIC)
        // The cache key should be consistent for the same query, but unique per page.
        // We include all filters and the current cursor to make it unique.
        $cursor = $request->input('cursor', 'first_page');
        $cacheKey = "product_bb_main_{$fromDate}_{$toDate}_{$warehouseId}_{$productType}_" . md5($keyword ?? '') . "_{$cursor}";


        $productType = explode(';', $productType);

        // 4. QUERY EXECUTION & PAGINATION (WRAPPED IN CACHE)
        $products = Cache::remember($cacheKey, 300, function () use ($request, $validated, $fromDate, $toDate, $warehouseId, $productType, $keyword, $searchTerm) {

            // Build the SINGLE, efficient query starting from the Product model
            $query = ProductV::query()
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
                ->selectRaw("ROUND(MAX(sto.adjustedQty), 4) as stockOphname")


                // LEFT JOIN to include products even if they have no transactions
                ->leftJoin('prodtr_v as trans', function ($join) use ($warehouseId) {
                    $join->on('product_v.productId', '=', 'trans.productId')
                        ->where('trans.warehouseCode', '=', $warehouseId);
                })
                ->leftJoinSub(
                    DB::table('stockoph_v as s')
                        ->select('s.productId', 's.adjustedQty', 's.transDate')
                        ->where('s.warehouseId', $warehouseId)
                        ->where('s.posted', 1)
                        ->where('s.transDate', '<=', $toDate)
                        // correlated subquery to pick the max transDate per product matching the filters
                        ->whereRaw(
                            's.transDate = (
                                select max(s2.transDate)
                                from stockoph_v s2
                                where s2.productId = s.productId
                                  and s2.warehouseId = ?
                                  and s2.posted = 1
                                  and s2.transDate <= ?
                            )',
                            [$warehouseId, $toDate]
                        ),
                    'sto',
                    function ($join) {
                        $join->on('product_v.productId', '=', 'sto.productId');
                    }
                )

                // Filter products and transactions
                ->whereIn('product_v.productType', $productType)

                // Apply keyword search if provided
                ->when($keyword, function ($query) use ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('product_v.productId', 'like', $searchTerm)
                            ->orWhere('product_v.productName', 'like', $searchTerm)
                            ->orWhere('product_v.unitId', 'like', $searchTerm);
                    });
                })

                ->orderBy('product_v.productId', 'asc')

                // Group by the product fields to make SUM() work correctly
                ->groupBy('product_v.productId', 'product_v.productName', 'product_v.unitId');

            // Paginate the final results
            $paginatedProducts = $query->cursorPaginate(400);

            // ** THE FIX **
            // Append the validated filter values to the pagination links.
            // This ensures that when you click "next", the filters are included in the URL.
            $paginatedProducts->withQueryString();

            return $paginatedProducts;
        });

        // return response()->json($products);

        // 5. RENDER VIEW
        return view('Response.Report.ProductBbMain.search', compact('products'));
    }
}
