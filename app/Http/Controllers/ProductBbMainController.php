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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // 2. SET DEFAULTS & GET INPUTS
        $fromDate = Carbon::parse($validated['fromDate'] ?? Carbon::now()->toDateString())->toDateString();
        $toDate = Carbon::parse($validated['toDate'] ?? Carbon::now()->toDateString())->toDateString();
        $warehouseId = '';
        if($type == "BARANG_JADI"){
            $warehouseId = 'FGS';
        }else {
            $warehouseId = $validated['warehouseId'] ?? 'WH';
        }
        $productType = $type ?: 'BAHAN_BAKU';
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';

        $productType = explode(';', $productType);

        // REPLICATE C# N+1 PATTERN EXACTLY
        // Step 1: Get all products from product_v (like C# ProductDao.Instance.findListLike())
        $productsQuery = DB::table('product_v')
            ->select(['productId', 'productName', 'unitId'])
            ->whereIn('productType', $productType);

        // Apply keyword search if provided
        if ($keyword) {
            $productsQuery->where(function ($q) use ($searchTerm) {
                $q->where('productId', 'like', $searchTerm)
                  ->orWhere('productName', 'like', $searchTerm)
                  ->orWhere('unitId', 'like', $searchTerm);
            });
        }

        $products = $productsQuery
            ->orderBy('productId', 'asc')
            ->limit(400)
            ->get();

        // Step 2: For EACH product, run separate queries (N+1 pattern, matching C#)
        $results = collect();

        foreach ($products as $product) {
            $rec = (object)[
                'productId' => $product->productId,
                'productName' => $product->productName,
                'unitId' => $product->unitId,
            ];

            // Query 1: Saldo Awal (opening balance) - matches C# ProductBbDao.cs line 164-184
            $saldoAwal = DB::table('prodtr_v as v')
                ->where('v.warehouseCode', '=', $warehouseId)
                ->where('v.productId', '=', $rec->productId)
                ->where('v.transDate', '<', $fromDate)
                ->whereIn('v.type', ['InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked'])
                ->sum('v.originalQty');

            $rec->saldoAwal = abs($saldoAwal ?? 0);

            // Query 2: Masuk (incoming) - matches C# ProductBbDao.cs line 186-207
            $masuk = DB::table('prodtr_v as v')
                ->where('v.warehouseCode', '=', $warehouseId)
                ->where('v.productId', '=', $rec->productId)
                ->where('v.transDate', '>=', $fromDate)
                ->where('v.transDate', '<=', $toDate)
                ->whereIn('v.type', ['InvAdjust_In', 'Po_Picked'])
                ->sum('v.originalQty');

            $rec->masuk = abs($masuk ?? 0);

            // Query 3: Keluar (outgoing) - matches C# ProductBbDao.cs line 209-230
            $keluar = DB::table('prodtr_v as v')
                ->where('v.warehouseCode', '=', $warehouseId)
                ->where('v.productId', '=', $rec->productId)
                ->where('v.transDate', '>=', $fromDate)
                ->where('v.transDate', '<=', $toDate)
                ->whereIn('v.type', ['InvAdjust_Out', 'So_Picked'])
                ->sum(DB::raw('ABS(v.originalQty)'));

            $rec->keluar = abs($keluar ?? 0);

            // Query 4: Stock Opname - matches C# ProductBbDao.cs line 246
            $stockOphname = DB::table('stockoph_v')
                ->where('productId', '=', $rec->productId)
                ->where('warehouseId', '=', $warehouseId)
                ->where('posted', '=', 1)
                ->whereBetween('transDate', [$fromDate, $toDate])
                ->sum('adjustedQty');

            $rec->stockOphname = abs($stockOphname ?? 0);

            // Apply calculations (matching C# logic lines 232-250)
            $rec->penyesuaian = 0;

            // SaldoBuku = (SaldoAwal + Math.Round(Masuk, 2)) - Math.Round(Keluar, 2)
            $rec->saldoBuku = round(($rec->saldoAwal + round($rec->masuk, 2)) - round($rec->keluar, 2), 3);

            // Selisih = StockOphname - SaldoBuku
            $rec->selisih = round($rec->stockOphname - $rec->saldoBuku, 3);

            $results->push($rec);
        }

        // Convert to object with items() method for blade compatibility
        $products = new class($results) {
            private $items;

            public function __construct($items) {
                $this->items = $items;
            }

            public function items() {
                return $this->items;
            }

            public function hasMorePages() {
                return false;
            }
        };

        // 5. RENDER VIEW
        return view('Response.Report.ProductBbMain.search', compact('products'));
    }

}
