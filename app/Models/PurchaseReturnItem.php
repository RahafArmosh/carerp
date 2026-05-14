<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_return_id',
        'bill_product_id',
        'sub_product_id',
        'product_id',
        'quantity',
        'unit_price',
        'created_by',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function billProduct()
    {
        return $this->belongsTo(BillProduct::class, 'bill_product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
