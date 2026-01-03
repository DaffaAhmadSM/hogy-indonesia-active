<?php

namespace App\Exports;

use App\Models\ReportWIP;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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
        $tableName = (new ReportWIP())->getTable();
        $keyword = $this->keyword;

        $prod_tr = ReportWIP::orderBy("$tableName.KODEBARANG");

        if ($keyword != null) {
            $prod_tr->where(function ($query) use ($keyword, $tableName) {
                $searchTerm = '%' . $keyword . '%';
                $query->where("$tableName.KODEBARANG", 'like', $searchTerm)
                    ->orWhere("$tableName.NAMABARANG", 'like', $searchTerm);
            });
        }

        return $prod_tr->select(
            "KODEBARANG",
            "NAMABARANG",
            "SATUAN",
            "SALDOAKHIR"
        );
    }

    /**
     * Mendefinisikan judul kolom untuk file ekspor.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Saldo Akhir',
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
            $row->KODEBARANG,
            $row->NAMABARANG,
            $row->SATUAN,
            $row->SALDOAKHIR,
        ];
    }
}
