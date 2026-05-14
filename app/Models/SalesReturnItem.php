<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_return_id',
        'invoice_product_id',
        'sub_product_id',
        'product_id',
        'quantity',
        'unit_price',
        'created_by',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class, 'invoice_product_id');
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
