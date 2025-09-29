<?php

namespace App\Exports;

use App\Models\ProdreceiptV;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use function Livewire\Volt\protect;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportEnvtInMain implements FromView, ShouldQueue
{

    use Exportable, Queueable;

    protected $fromDate;
    protected $toDate;

    protected $keywords;


    public function __construct(Carbon $fromDate, Carbon $toDate, $keywords)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->keywords = $keywords;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): \Illuminate\Contracts\View\View
    {

        $prod_receipt = ProdreceiptV::orderBy("registrationDate", "desc")
            ->orderBy("purchRecId")
            ->orderBy("ItemId")
            ->where('isCancel', 0);

        $keyword = $this->keywords;

        $fromDate = $this->fromDate;
        $toDate = $this->toDate;

        $prod_receipt = $prod_receipt->whereBetween('registrationDate', [$fromDate, $toDate]);


        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('requestNo', 'like', "%$keyword%")
                        ->orWhere('docBc', 'like', "%$keyword%")
                        ->orWhere('registrationNo', 'like', "%$keyword%")
                        ->orWhere('invNoVend', 'like', "%$keyword%")
                        ->orWhere('VendName', 'like', "%$keyword%")
                        ->orWhere('ItemId', 'like', "%$keyword%")
                        ->orWhere('ItemName', 'like', "%$keyword%");
                });
            });
        }


        $prod_receipt = $prod_receipt
            ->get([
                'purchRecId',
                'transDate',
                'requestNo',
                'docBc',
                'registrationNo',
                'registrationDate',
                'invNoVend',
                'invDateVend',
                'VendName',
                'ItemId',
                'ItemName',
                'unit',
                'qty',
                'currencyCode',
                'price',
                'amount',
                'notes',
                'PackCode'
            ]);

        return view('Export.excel.InventInMain', [
            'prod_receipt' => $prod_receipt
        ]);
    }
}
