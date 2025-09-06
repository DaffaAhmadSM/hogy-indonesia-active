<?php

namespace App\Exports;

use App\Models\SalespickV;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

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
        $prod_receipt = SalespickV::orderBy("registrationDate", "desc")
            ->orderBy("invoiceId")
            ->orderBy("ItemId")
            ->where('isCancel', 0);

        $keyword = $this->keywords;


        $fromDate = $this->fromDate;
        $toDate = $this->toDate;
        $prod_receipt = $prod_receipt->whereBetween('registrationDate', [$fromDate, $toDate]);

        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $searchTerm = '%' . $keyword . '%';
                    $q->where('requestNo', 'like', $searchTerm)
                        ->orWhere('docBc', 'like', $searchTerm)
                        ->orWhere('registrationNo', 'like', $searchTerm)
                        ->orWhere('InvoiceId', 'like', $searchTerm)
                        ->orWhere('CustName', 'like', $searchTerm)
                        ->orWhere('ItemId', 'like', $searchTerm)
                        ->orWhere('ItemName', 'like', $searchTerm);
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->get([
                'transDate',
                'requestNo',
                'docBc',
                'registrationNo',
                'registrationDate',
                'invoiceId',
                'invoiceDate',
                'custName',
                'ItemId',
                'ItemName',
                'unit',
                'qty',
                'currencyCode',
                'price',
                'amount',
                'notes',
                'PickCode'
            ]);

        return view('Export.excel.Invent-out-main', [
            'prod_receipt' => $prod_receipt
        ]);
    }
}
