<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class InvoiceProduct extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'invoice_id',
        'quantity',
        'tax',
        'discount',
        'sub_product_id',
        'price',
        'exchange_price',
        'exchange_discount',
        'description'
    ];

    public function product(){
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }
    public function taxObject() {
        return $this->belongsTo(Tax::class, 'tax');
    }
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
