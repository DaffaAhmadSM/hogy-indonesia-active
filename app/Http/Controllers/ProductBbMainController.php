<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ProductV;
use Illuminate\Http\Request;
use App\Exports\ExportProductBB;
use App\Jobs\ProcessProductBBExport;
use App\Models\ReportMutasiBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductBbMainController extends Controller
{
    public function index(string $type, string $state = "active")
    {

        $title = 'Laporan PertanggungJawaban Mutasi ';
        switch ($type) {
            case 'BBP':
                $type = 'BAHAN_BAKU_PENOLONG';
                $title .= 'Bahan Baku dan Penolong';
                break;
            case 'BJ':
                $type = 'BARANG_JADI';
                $title .= 'Barang Jadi';
                break;
            case 'MP':
                $type = "MESIN_PERALATAN";
                $title .= 'Mesin & Peralatan';
                break;
            case 'BR':
                $type = 'BARANG_REJECT';
                $title .= 'Barang Reject';
                break;
            case 'BS':
                $type = 'BARANG_SCRAP';
                $title .= 'Barang Scrap';
                break;
            default:
                abort(404, 'Invalid product type');
        }

        return view('Report.product-bb-main', compact('type', 'title', 'state'));
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
        $reportType = '';
        switch ($type) {
            case 'BAHAN_BAKU_PENOLONG':
                $reportType = '04 Mutasi Bahan Baku';
                break;
            case 'MESIN_PERALATAN': 
                $reportType = '05 Mutasi Mesin dan Peralatan';
                break;
            case 'BARANG_JADI':
                $reportType = '06 Mutasi Barang Jadi';
                break;
            case 'BARANG_REJECT':
                $reportType = '07 Mutasi Barang Reject';
                break;
            case 'BARANG_SCRAP':
                $reportType = '07 Mutasi Barang Scrap';
                break;
            default:
                $reportType = '04 Mutasi Bahan Baku';
                break;
        }
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';

        $tableName = (new ReportMutasiBarang())->getTable();

        // 3. Match C# logic: First get list of products (like ProductDao.Instance.findListLike)
        $products = ReportMutasiBarang::where("$tableName.REPORTTYPE", $reportType)
        ->orderBy("$tableName.KODEBARANG");

        // Apply keyword search if provided
        if ($keyword) {
            $products->where(function ($q) use ($searchTerm) {
                $q->where('KODEBARANG', 'like', $searchTerm)
                    ->orWhere('NAMABARANG', 'like', $searchTerm)
                    ->orWhere('SATUAN', 'like', $searchTerm);
            });
        }

        // 4. PAGINATE & CACHE RESULTS
        $products = $products
                ->whereBetween('TRANSDATE', [$fromDate, $toDate])
                ->cursorPaginate(200, [
                    "RECID",
                    "KODEBARANG",
                    "NAMABARANG",
                    "SATUAN",
                    "SALDOAWAL",
                    "PEMASUKAN",
                    "PENGELUARAN",
                    "PENYESUAIAN",
                    "SALDOBUKU",
                    "STOCKOPNAME",
                    "SELISIH"
                ]);
        // return $products;

        // 5. RENDER VIEW
        return view('Response.Report.ProductBbMain.search', compact('products'));
    }
}