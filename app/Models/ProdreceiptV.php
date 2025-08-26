<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $purchRecId
 * @property string $code
 * @property string|null $description
 * @property string|null $transDate
 * @property string $PurchaseId
 * @property string $VendId
 * @property string|null $VendName
 * @property string|null $currencyCode
 * @property string|null $invDateVend
 * @property string|null $invNoVend
 * @property float|null $rate
 * @property string|null $registrationDate
 * @property string|null $registrationNo
 * @property string|null $requestNo
 * @property string|null $docBc
 * @property string|null $inventTransId
 * @property int $pRecId
 * @property string $ItemId
 * @property string|null $ItemName
 * @property float|null $qty
 * @property string|null $unit
 * @property float|null $price
 * @property float|null $amount
 * @property string|null $Notes
 * @property string|null $PackCode
 * @property int|null $isCancel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereDocBc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereInvDateVend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereInvNoVend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereInventTransId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereIsCancel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV wherePRecId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV wherePackCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV wherePurchRecId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV wherePurchaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereRegistrationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereRegistrationNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereRequestNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereTransDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereVendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProdreceiptV whereVendName($value)
 * @mixin \Eloquent
 */
class ProdreceiptV extends Model
{
    protected $table = 'prodreceipt_v';
}
