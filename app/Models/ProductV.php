<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductV extends Model
{
    protected $table = 'product_v';

    protected $appends = ['saldo_buku', 'selisih'];

    // Accessor for the 'book balance' calculation
    protected function saldoBuku(): Attribute
    {
        return Attribute::make(
            get: fn() => round(($this->saldoAwal + $this->masuk) - $this->keluar, 2)
        );
    }

    protected function selisih(): Attribute
    {
        return Attribute::make(
            get: fn() => round($this->stockOphname == 0 ? abs($this->saldo_buku) : $this->stockOphname - $this->saldo_buku, 2)
        );
    }
}
