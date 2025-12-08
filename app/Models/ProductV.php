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
            get: fn() => ($this->saldoAwal + $this->masuk) - $this->keluar
        );
    }

    protected function selisih(): Attribute
    {
        return Attribute::make(
            get: fn() => round($this->stockOphname != 0 && $this->stockOphname != null ? $this->stockOphname - $this->saldo_buku : 0, 2)
        );
    }
}
