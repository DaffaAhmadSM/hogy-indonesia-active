<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdtrV extends Model
{
    protected $table = 'prodtr_v';

    public function product()
    {
        return $this->belongsTo(ProductV::class, 'productId', 'productId');
    }
}
