<?php

namespace App\Exports;

use App\Models\ReportMutasiBarang;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;


class ExportProductBB implements FromCollection, WithHeadings, ShouldQueue
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $warehouseId;
    protected $productType;
    protected $keyword;

    /**
     * Menerima semua filter dari controller
     */
    public function __construct(string $fromDate, string $toDate, string $warehouseId, array $productType, ?string $keyword)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->warehouseId = $warehouseId;
        $this->productType = $productType;
        $this->keyword = $keyword;
    }

    /**
     * Query using ReportMutasiBarang matching hxSearch logic
     */
    public function collection()
    {
        $keyword = $this->keyword;
        $searchTerm = '%' . $keyword . '%';
        
        // Map productType array to reportType
        $reportType = '';
        if (in_array('BAHAN_BAKU_PENOLONG', $this->productType)) {
            $reportType = '04 Mutasi Bahan Baku';
        } elseif (in_array('MESIN_PERALATAN', $this->productType)) {
            $reportType = '05 Mutasi Mesin dan Peralatan';
        } elseif (in_array('BARANG_JADI', $this->productType)) {
            $reportType = '06 Mutasi Barang Jadi';
        } elseif (in_array('BARANG_REJECT_SCRAP', $this->productType)) {
            $reportType = ['07 Mutasi Barang Reject', '07 Mutasi Barang Scrap'];
        } else {
            $reportType = '04 Mutasi Bahan Baku';
        }

        $tableName = (new ReportMutasiBarang())->getTable();

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

        $products = $products
            ->whereBetween('TRANSDATE', [$this->fromDate, $this->toDate])
            ->get([
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

        return $products->map(function ($product) {
            return [
                $product->KODEBARANG,
                $product->NAMABARANG,
                $product->SATUAN,
                $product->SALDOAWAL,
                $product->PEMASUKAN,
                $product->PENGELUARAN,
                $product->PENYESUAIAN,
                $product->SALDOBUKU,
                $product->STOCKOPNAME,
                $product->SELISIH,
            ];
        });
    }

    /**
     * Metode ini mendefinisikan baris header untuk file Excel Anda.
     */
    public function headings(): array
    {
        return [
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Saldo Awal',
            'Pemasukan',
            'Pengeluaran',
            'Penyesuaian',
            'Saldo Buku',
            'Stock Opname',
            'Selisih',
        ];
    }
}