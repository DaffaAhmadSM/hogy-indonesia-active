<?php

namespace App\Exports;

use App\Models\ProdreceiptV;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use function Livewire\Volt\protect;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportEnvtInMain implements FromView
{

    use Exportable;

    protected $fromDate;
    protected $toDate;

    protected $keywords;


    public function __construct($fromDate, $toDate, $keywords)
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

        $prod_receipt = ProdreceiptV::orderBy("registrationDate")
            ->orderBy("purchRecId")
            ->orderBy("ItemId")
            ->where('isCancel', 0);

        $keyword = $this->keywords;

        if ($this->fromDate != null) {

            $fromDate = Carbon::createFromFormat('Y-m-d', $this->fromDate)->startOfDay();

            if ($this->toDate != null) {
                $toDate = Carbon::createFromFormat('Y-m-d', $this->toDate)->endOfDay();
            } else {
                $toDate = Carbon::createFromFormat('Y-m-d', $this->fromDate)->endOfDay();
            }

            $prod_receipt = $prod_receipt->whereBetween('transDate', [$fromDate, $toDate]);

        }

        if ($keyword != null) {
            $prod_receipt = $prod_receipt->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('purchRecId', 'like', "%$keyword%")
                        ->orWhere('ItemId', 'like', "%$keyword%")
                        ->orWhere('ItemName', 'like', "%$keyword%")
                        ->orWhere('VendName', 'like', "%$keyword%");
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
            'prod_receipt' => ProdreceiptV::all()
        ]);
    }
}
