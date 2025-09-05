<?php

namespace App\Exports;

use App\Models\ProductV; // Sesuaikan dengan path model Anda
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;


class ExportProductBB implements FromQuery, WithHeadings, WithMapping, ShouldQueue, WithCustomQuerySize
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
     * Metode ini akan menjalankan query utama Anda.
     * Laravel Excel akan secara otomatis melakukan chunking pada hasil query ini,
     * sehingga sangat efisien dalam penggunaan memori.
     */
    public function query()
    {
        $searchTerm = '%' . $this->keyword . '%';

        // Logika query sama persis dengan yang Anda miliki, tanpa paginasi
        return ProductV::query()
            ->select([
                'product_v.productId',
                'product_v.productName',
                'product_v.unitId',
            ])
            ->selectRaw("
               ROUND(COALESCE(SUM(CASE 
                    WHEN trans.transDate < ? AND trans.type IN ('InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked') 
                    THEN trans.originalQty 
                    ELSE 0 
                END), 0), 4) as saldoAwal
            ", [$this->fromDate])
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN trans.transDate BETWEEN ? AND ? AND trans.type IN ('InvAdjust_In', 'Po_Picked') THEN trans.originalQty ELSE 0 END), 0), 4) as masuk", [$this->fromDate, $this->toDate])
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN trans.transDate BETWEEN ? AND ? AND trans.type IN ('InvAdjust_Out', 'So_Picked') THEN ABS(trans.originalQty) ELSE 0 END), 0), 4) as keluar", [$this->fromDate, $this->toDate])
            ->selectRaw("ROUND(COALESCE(SUM(sto.adjustedQty), 0), 4) as stockOphname")
            ->leftJoin('prodtr_v as trans', function ($join) {
                $join->on('product_v.productId', '=', 'trans.productId')
                    ->where('trans.warehouseCode', '=', $this->warehouseId);
            })
            ->leftJoin('stockoph_v as sto', function ($join) {
                $join->on('product_v.productId', '=', 'sto.productId')
                    ->where('sto.warehouseId', '=', $this->warehouseId)
                    ->where('sto.posted', '=', 1)
                    ->whereBetween('sto.transDate', [$this->fromDate, $this->toDate]);
            })
            ->whereIn('product_v.productType', $this->productType)
            ->when($this->keyword, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('product_v.productId', 'like', $searchTerm)
                        ->orWhere('product_v.productName', 'like', $searchTerm)
                        ->orWhere('product_v.unitId', 'like', $searchTerm);
                });
            })
            ->groupBy('product_v.productId', 'product_v.productName', 'product_v.unitId')
            ->orderBy('product_v.productId', 'asc');
    }

    /**
     * Metode ini mendefinisikan baris header untuk file CSV Anda.
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
            'Saldo Akhir',
        ];
    }

    public function querySize(): int
    {
        $searchTerm = '%' . $this->keyword . '%';
        
        // Hitung total baris yang akan diekspor berdasarkan filter
        $query = ProductV::query()
            ->leftJoin('prodtr_v as trans', function ($join) {
                $join->on('product_v.productId', '=', 'trans.productId')
                    ->where('trans.warehouseCode', '=', $this->warehouseId);
            })
            ->leftJoin('stockoph_v as sto', function ($join) {
                $join->on('product_v.productId', '=', 'sto.productId')
                    ->where('sto.warehouseId', '=', $this->warehouseId)
                    ->where('sto.posted', '=', 1)
                    ->whereBetween('sto.transDate', [$this->fromDate, $this->toDate]);
            })
            ->whereIn('product_v.productType', $this->productType)
            ->when($this->keyword, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('product_v.productId', 'like', $searchTerm)
                        ->orWhere('product_v.productName', 'like', $searchTerm)
                        ->orWhere('product_v.unitId', 'like', $searchTerm);
                });
            })
            ->groupBy('product_v.productId', 'product_v.productName', 'product_v.unitId')
            ->orderBy('product_v.productId', 'asc');
        return $query->count();
    }

    /**
     * Metode ini memetakan setiap baris data dari query.
     * Anda bisa melakukan transformasi data atau kalkulasi tambahan di sini.
     * @param mixed $product
     */
    public function map($product): array
    {
        // Hitung Saldo Akhir di sini
        $saldoAkhir = ($product->saldoAwal + $product->masuk) - $product->keluar + $product->stockOphname;

        return [
            $product->productId,
            $product->productName,
            $product->unitId,
            $product->saldoAwal,
            $product->masuk,
            $product->keluar,
            $product->stockOphname,
            round($saldoAkhir, 4), // Saldo akhir yang sudah dihitung
        ];
    }
}
