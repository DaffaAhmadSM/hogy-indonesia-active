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

        $productTypeArray = explode(';', $productType);

        // 3. Match C# logic: First get list of products (like ProductDao.Instance.findListLike)
        $query = DB::table('product_v')
            ->select('productId', 'productName', 'unitId', 'productType')
            ->whereIn('productType', $productTypeArray);

        // Apply keyword search if provided
        if ($keyword) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('productId', 'like', $searchTerm)
                    ->orWhere('productName', 'like', $searchTerm)
                    ->orWhere('unitId', 'like', $searchTerm);
            });
        }

        $query->orderBy('productId', 'asc');

        // Paginate the products
        $products = $query->cursorPaginate(400);

        // 4. For each product, calculate transaction data (N+1 pattern like C#)
        $products->through(function ($product) use ($fromDate, $toDate, $warehouseId) {
            // Saldo Awal
            $saldoAwal = DB::table('prodtr_v')
                ->where('warehouseCode', $warehouseId)
                ->where('productId', $product->productId)
                ->where('transDate', '<', $fromDate)
                ->whereIn('type', ['InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked'])
                ->sum('originalQty');
            
            $product->saldoAwal = abs($saldoAwal ?? 0);

            // Masuk (In)
            $masuk = DB::table('prodtr_v')
                ->where('warehouseCode', $warehouseId)
                ->where('productId', $product->productId)
                ->whereBetween('transDate', [$fromDate, $toDate])
                ->whereIn('type', ['InvAdjust_In', 'Po_Picked'])
                ->sum('originalQty');
            
            $product->masuk = abs($masuk ?? 0);

            // Keluar (Out)
            $keluar = DB::table('prodtr_v')
                ->selectRaw('ABS(SUM(originalQty)) as qtyOut')
                ->where('warehouseCode', $warehouseId)
                ->where('productId', $product->productId)
                ->whereBetween('transDate', [$fromDate, $toDate])
                ->whereIn('type', ['InvAdjust_Out', 'So_Picked'])
                ->value('qtyOut');
            
            $product->keluar = abs($keluar ?? 0);

            // Stock Opname
            $stockOphname = DB::table('stockoph_v')
                ->where('productId', $product->productId)
                ->where('warehouseId', $warehouseId)
                ->where('posted', 1)
                ->whereBetween('transDate', [$fromDate, $toDate])
                ->sum('adjustedQty');
            
            $product->stockOphname = abs($stockOphname ?? 0);

            // Apply abs to ensure positive values (matching C# Math.Abs())
            $product->saldoAwal = abs($product->saldoAwal);
            $product->masuk = abs($product->masuk);
            $product->keluar = abs($product->keluar);
            $product->penyesuaian = 0; // C# sets this to 0
            $product->stockOphname = abs($product->stockOphname);

            // SaldoBuku = (SaldoAwal + Masuk) - Keluar
            $product->saldoBuku = round(($product->saldoAwal + round($product->masuk, 2)) - round($product->keluar, 2), 3);

            // Selisih = StockOphname - SaldoBuku
            $product->selisih = round($product->stockOphname - $product->saldoBuku, 3);

            return $product;
        });

        // 5. RENDER VIEW
        return view('Response.Report.ProductBbMain.search', compact('products'));
    }
}