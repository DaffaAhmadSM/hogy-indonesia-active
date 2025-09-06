<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPickBomV extends Model
{
    protected $table = 'salespickbom_v';

    protected $casts = [
        'RegistrationDate' => 'date:Y-m-d',
        'InvoiceDate'      => 'date:Y-m-d',
        'Qty'              => 'double',
    ];
}
