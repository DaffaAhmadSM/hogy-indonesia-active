<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProdtrV extends Model
{
    protected $table = 'prodtr_v';

    public function product()
    {
        return $this->belongsTo(ProductV::class, 'productId', 'productId');
    }


    protected $appends = ['saldo_buku', 'selisih'];

    /**
     * Calculates the final book balance.
     */
    protected function saldoBuku(): Attribute
    {
        return Attribute::make(
            get: fn () => round(($this->saldoAwal + $this->masuk) - $this->keluar, 3)
        );
    }

    /**
     * Calculates the difference between physical and book balance.
     */
    protected function selisih(): Attribute
    {
        return Attribute::make(
            get: fn () => round(($this->stockOphname ?? 0) - $this->saldo_buku, 3)
        );
    }
}
