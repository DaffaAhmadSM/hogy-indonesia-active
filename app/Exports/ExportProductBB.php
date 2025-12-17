<?php

namespace App\Exports;

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
     * Match C# logic: Get products first, then query transactions for each (N+1 pattern)
     */
    public function collection()
    {
        $searchTerm = '%' . $this->keyword . '%';

        // 1. Get list of products (like C# ProductDao.Instance.findListLike)
        $query = DB::table('product_v')
            ->select('productId', 'productName', 'unitId', 'productType')
            ->whereIn('productType', $this->productType);

        // Apply keyword search if provided
        if ($this->keyword) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('productId', 'like', $searchTerm)
                    ->orWhere('productName', 'like', $searchTerm)
                    ->orWhere('unitId', 'like', $searchTerm);
            });
        }

        $query->orderBy('productId', 'asc');

        $products = $query->get();

        // 2. For each product, calculate transaction data (N+1 pattern like C#)
        $results = $products->map(function ($product) {
            // Saldo Awal
            $saldoAwal = DB::table('prodtr_v')
                ->where('warehouseCode', $this->warehouseId)
                ->where('productId', $product->productId)
                ->where('transDate', '<', $this->fromDate)
                ->whereIn('type', ['InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked'])
                ->sum('originalQty');
            
            $saldoAwal = abs($saldoAwal ?? 0);

            // Masuk (In)
            $masuk = DB::table('prodtr_v')
                ->where('warehouseCode', $this->warehouseId)
                ->where('productId', $product->productId)
                ->whereBetween('transDate', [$this->fromDate, $this->toDate])
                ->whereIn('type', ['InvAdjust_In', 'Po_Picked'])
                ->sum('originalQty');
            
            $masuk = abs($masuk ?? 0);

            // Keluar (Out)
            $keluar = DB::table('prodtr_v')
                ->selectRaw('ABS(SUM(originalQty)) as qtyOut')
                ->where('warehouseCode', $this->warehouseId)
                ->where('productId', $product->productId)
                ->whereBetween('transDate', [$this->fromDate, $this->toDate])
                ->whereIn('type', ['InvAdjust_Out', 'So_Picked'])
                ->value('qtyOut');
            
            $keluar = abs($keluar ?? 0);

            // Stock Opname
            $stockOphname = DB::table('stockoph_v')
                ->where('productId', $product->productId)
                ->where('warehouseId', $this->warehouseId)
                ->where('posted', 1)
                ->whereBetween('transDate', [$this->fromDate, $this->toDate])
                ->sum('adjustedQty');
            
            $stockOphname = abs($stockOphname ?? 0);

            // Apply abs to ensure positive values (matching C# Math.Abs())
            $saldoAwal = abs($saldoAwal);
            $masuk = abs($masuk);
            $keluar = abs($keluar);
            $penyesuaian = 0; // C# sets this to 0
            $stockOphname = abs($stockOphname);

            // SaldoBuku = (SaldoAwal + Masuk) - Keluar
            $saldoBuku = round(($saldoAwal + round($masuk, 2)) - round($keluar, 2), 3);

            // Selisih = StockOphname - SaldoBuku
            $selisih = round($stockOphname - $saldoBuku, 3);

            return [
                $product->productId,
                $product->productName,
                $product->unitId,
                $saldoAwal,
                $masuk,
                $keluar,
                $stockOphname,
                $saldoBuku,
                $selisih,
            ];
        });

        return $results;
    }

    /**
     * Metode ini mendefinisikan baris header untuk file Excel Anda.
     */
    public function headings(): array
    {
        return [
            'ID Produk',
            'Nama Produk',
            'Unit',
            'Saldo Awal',
            'Masuk',
            'Keluar',
            'Stock Opname',
            'Saldo Buku',
            'Selisih',
        ];
    }
}