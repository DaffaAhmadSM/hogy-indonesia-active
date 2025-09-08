<?php

namespace App\Exports;

use App\Models\ProdtrV;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductRejectExport implements FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;
    protected $validated;

    /**
     * Menerima data yang divalidasi dari controller.
     *
     * @param array $validated
     */
    public function __construct(array $validated)
    {
        $this->validated = $validated;
    }

    /**
     * Mendefinisikan header untuk file Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Satuan',
            'Saldo Awal',
            'Masuk',
            'Keluar',
            'Stock Opname',
            'Saldo Akhir',
        ];
    }

    /**
     * Memetakan data dari setiap baris query ke format array untuk baris Excel.
     *
     * @param mixed $product
     * @return array
     */
    public function map($product): array
    {
        // Hitung Saldo Akhir
        $saldoAkhir = ($product->saldoAwal + $product->masuk) - $product->keluar;

        return [
            $product->productId,
            $product->productName,
            $product->unitId,
            $product->saldoAwal,
            $product->masuk,
            $product->keluar,
            $product->stockOphname,
            $saldoAkhir,
        ];
    }

    /**
     * Query ini mengambil data yang akan diekspor.
     * Ini adalah query yang sama dari controller Anda, tanpa paginasi.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        $validated = $this->validated;
        $keyword = $validated['keyword'] ?? null;
        $searchTerm = '%' . $keyword . '%';

        // Subquery untuk stock opname
        $stockOpnameSubquery = StockOpname::query()
            ->select('productId', DB::raw('ROUND(COALESCE(SUM(adjustedQty), 0), 3) as totalAdjustedQty'))
            ->where('warehouseId', $validated['warehouseId'])
            ->where('posted', 1)
            ->whereBetween('transDate', [$validated['fromDate'], $validated['toDate']])
            ->groupBy('productId');

        // Query utama untuk mengambil data produk
        return ProdtrV::query()
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
            ->orderBy('prodtr_v.productId'); // Hapus paginasi untuk ekspor
    }
}
