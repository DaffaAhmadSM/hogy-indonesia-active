<?php

namespace App\Exports;

use App\Models\ReportPengeluaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class ExportInvtOutMain implements FromView, ShouldQueue
{
    use Exportable, Queueable;

    protected $fromDate;
    protected $toDate;
    protected $keywords;


    public function __construct($fromDate, $toDate, $keywords)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->keywords = $keywords;
    }
    
    public function view(): \Illuminate\Contracts\View\View
    {
        $keyword = $this->keywords;
        $fromDate = $this->fromDate;
        $toDate = $this->toDate;

        $columns = [
            "RECID",
            "BCTYPE",
            "NOMORDAFTAR",
            "TANGGALDAFTAR",
            "NOMORPENGIRIMAN",
            "PENERIMA",
            "TANGGALPENGIRIMAN",
            "KODEBARANG",
            "NAMABARANG",
            "JUMLAH",
            "SATUAN",
            "NILAI",
        ];

        $tableName = (new ReportPengeluaran())->getTable();
        $prod_receipt = ReportPengeluaran::select($columns)
            ->selectRaw("CASE 
                WHEN BCTYPE = 9 THEN 'BC40'
                WHEN BCTYPE = 10 THEN 'BC27'
                WHEN BCTYPE = 11 THEN 'BC23'
                WHEN BCTYPE = 12 THEN 'BC262'
                WHEN BCTYPE = 13 THEN 'BC30'
                WHEN BCTYPE = 14 THEN 'BC25'
                WHEN BCTYPE = 15 THEN 'BC261'
                WHEN BCTYPE = 16 THEN 'BC27'
                WHEN BCTYPE = 17 THEN 'BC41'
                ELSE 'UNKNOWN'
            END as BC_CODE_NAME")
            ->orderBy("$tableName.NOMORDAFTAR", "desc")
            ->orderBy("$tableName.KODEBARANG");

        $prod_receipt = $prod_receipt->whereBetween("$tableName.TANGGALDAFTAR", [$fromDate, $toDate]);

        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) use ($tableName) {
                $query->where(function ($q) use ($keyword, $tableName) {
                    $q->where("$tableName.NOMORDAFTAR", 'like', "%$keyword%")
                        ->orWhere("$tableName.KODEBARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NAMABARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NOMORPENGIRIMAN", 'like', "%$keyword%")
                        ->orWhere("$tableName.PENERIMA", 'like', "%$keyword%");
                });
            });
        }

        $prod_receipt = $prod_receipt->get();

        return view('Export.excel.Invent-out-main', [
            'prod_receipt' => $prod_receipt
        ]);
    }
}
