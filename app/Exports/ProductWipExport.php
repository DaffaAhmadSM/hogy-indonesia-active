<?php

namespace App\Exports;

use App\Models\ProdtrV;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;

class ProductWipExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue, WithCustomQuerySize
{

    use Exportable, Queueable;
    protected $asofDate;
    protected $keyword;

    /**
     * Menerima parameter filter dari controller.
     *
     * @param string|null $asofDate
     * @param string|null $keyword
     */
    public function __construct($asofDate, $keyword)
    {
        $this->asofDate = $asofDate;
        $this->keyword = $keyword;
    }

    public function querySize(): int
    {
        // We create a subquery to count the distinct groups, which is the correct way.
        $query = $this->query();
        
        // Using a subquery for counting is the most reliable method for grouped queries.
        return DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query->getQuery())
            ->count();
    }

    public function query(): Builder
    {
        $fromDate = "1990-01-01";
        $asofDate = $this->asofDate ? Carbon::createFromFormat('Y-m-d', $this->asofDate) : Carbon::now();

        $query = ProdtrV::query()
            ->where('warehouseCode', 'WIP')
            ->whereIn('type', ['InvAdjust_In', 'InvAdjust_Out', 'Po_Picked', 'So_Picked'])
            ->whereBetween('transDate', [$fromDate, $asofDate])
            ->groupBy('productId', 'productName', 'unitId')
            ->select(
                'productId',
                'productName',
                'unitId',
                DB::raw('ROUND(SUM(originalQty), 2) as jumlah')
            )
            ->orderBy('productId')
            ->orderBy('unitId');

        // Terapkan filter keyword jika ada
        if ($this->keyword) {
            $searchTerm = '%' . $this->keyword . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('productId', 'like', $searchTerm)
                    ->orWhere('productName', 'like', $searchTerm)
                    ->orWhere('unitId', 'like', $searchTerm);
            });
        }

        return $query;
    }

    /**
     * Mendefinisikan judul kolom untuk file ekspor.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Satuan',
            'Jumlah Total',
        ];
    }

    /**
     * Memetakan data dari setiap baris hasil query ke format yang diinginkan.
     *
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->productId,
            $row->productName,
            $row->unitId,
            $row->jumlah,
        ];
    }
}
