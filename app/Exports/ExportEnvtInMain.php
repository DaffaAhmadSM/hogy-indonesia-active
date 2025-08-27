<?php

namespace App\Exports;

use App\Models\ProdreceiptV;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportEnvtInMain implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ProdreceiptV::all();
    }
}
