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
        if($type == "BARANG_JADI"){
            $warehouseId = 'FGS';
        }else {
            $warehouseId = $validated['warehouseId'] ?? 'WH';
        }
        $productType = $type ?: 'BAHAN_BAKU';
        $keyword = $validated['keyword'] ?? null;

        $productTypeArray = explode(';', $productType);

        // 3. Masukkan ekspor ke dalam antrian
        $fromDateToDate = $fromDate . "-" . $toDate;

        $path = 'reports/';
        $fileName ='products-' . $type . '-' . $fromDateToDate . '.xlsx';
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
        $warehouseId = '';
        if($type == "BARANG_JADI"){
            $warehouseId = 'FGS';
        }else {
            $warehouseId = $validated['warehouseId'] ?? 'WH';
        }
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

            // Match C# logic: Start from transactions table, not products table
            // This ensures we only get products that have actual transactions
            $query = DB::table('prodtr_v as trans')
                ->select([
                    'trans.productId',
                    'trans.productName',
                    'trans.unitId',
                ])
                // Saldo Awal: transactions before fromDate
                ->selectRaw("
                   ABS(COALESCE(SUM(CASE
                        WHEN trans.transDate < ? 
                        AND trans.type IN ('InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked')
                        THEN trans.originalQty
                        ELSE 0
                    END), 0)) as saldoAwal
                ", [$fromDate])
                // Masuk: incoming transactions in date range
                ->selectRaw("
                    ABS(COALESCE(SUM(CASE 
                        WHEN trans.transDate BETWEEN ? AND ? 
                        AND trans.type IN ('InvAdjust_In', 'Po_Picked') 
                        THEN trans.originalQty 
                        ELSE 0 
                    END), 0)) as masuk
                ", [$fromDate, $toDate])
                // Keluar: outgoing transactions in date range
                ->selectRaw("
                    ABS(COALESCE(SUM(CASE 
                        WHEN trans.transDate BETWEEN ? AND ? 
                        AND trans.type IN ('InvAdjust_Out', 'So_Picked') 
                        THEN trans.originalQty 
                        ELSE 0 
                    END), 0)) as keluar
                ", [$fromDate, $toDate])
                // Stock Opname: LEFT JOIN to get stock opname data
                ->selectRaw("COALESCE(SUM(sto.adjustedQty), 0) as stockOphname")
                
                // JOIN with product table to get product type for filtering
                ->join('product_v', 'trans.productId', '=', 'product_v.productId')
                
                // LEFT JOIN for stock opname (may not exist for all products)
                ->leftJoin('stockoph_v as sto', function ($join) use ($warehouseId, $fromDate, $toDate) {
                    $join->on('trans.productId', '=', 'sto.productId')
                        ->where('sto.warehouseId', '=', $warehouseId)
                        ->where('sto.posted', '=', 1)
                        ->whereBetween('sto.transDate', [$fromDate, $toDate]);
                })
                
                // Filter by warehouse (matching C# logic)
                ->where('trans.warehouseCode', '=', $warehouseId)
                
                // Filter by product type
                ->whereIn('product_v.productType', $productType)

                // Apply keyword search if provided
                ->when($keyword, function ($query) use ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('trans.productId', 'like', $searchTerm)
                            ->orWhere('trans.productName', 'like', $searchTerm)
                            ->orWhere('trans.unitId', 'like', $searchTerm);
                    });
                })

                // Group by product (matching C# logic)
                ->groupBy('trans.productId', 'trans.productName', 'trans.unitId')
                
                ->orderBy('trans.productId', 'asc');

            // Paginate the final results
            $paginatedProducts = $query->cursorPaginate(400);

            // Append the validated filter values to the pagination links
            $paginatedProducts->withQueryString();

            return $paginatedProducts;
        });

        // Calculate additional fields (saldoBuku and selisih) like in C# reference
        $products->getCollection()->transform(function ($product) {
            // Apply ABS to ensure positive values (matching C# Math.Abs())
            $product->saldoAwal = abs($product->saldoAwal);
            $product->masuk = abs($product->masuk);
            $product->keluar = abs($product->keluar);
            $product->stockOphname = abs($product->stockOphname);
            
            // SaldoBuku = (SaldoAwal + Masuk) - Keluar
            $product->saldoBuku = round(($product->saldoAwal + round($product->masuk, 2)) - round($product->keluar, 2), 3);
            
            // Selisih = StockOphname - SaldoBuku
            $product->selisih = round($product->stockOphname - $product->saldoBuku, 3);
            
            return $product;
        });

        // return response()->json($products);

        // 5. RENDER VIEW
        return view('Response.Report.ProductBbMain.search', compact('products'));
    }
}
