<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BillProduct extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'sub_product_id',
        'bill_id',
        'chart_account_id',
        'quantity',
        'tax',
        'discount',
        'total',
        'price',
        'exchange_price',
        'exchange_discount'
    ];

    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function taxObject()
    {
        return $this->belongsTo(Tax::class, 'tax');
    }

    public function getTaxPriceAttribute()
    {
        $qty = $this->quantity ?? 0;
        $price = $this->price ?? 0;
        $discount = $this->discount * $qty ?? 0;

        // Ensure the relationship exists
        $taxRate = $this->taxObject->rate ?? 0;

        $subtotal = $qty * $price;

        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);

        return $taxAmount; // optional rounding
    }

    public function getTaxPriceExchangeAttribute()
    {
        $qty = $this->quantity ?? 0;
        $price = $this->exchange_price ?? 0;
        $discount = $this->exchange_discount * $qty ?? 0;

        // Ensure the relationship exists
        $taxRate = $this->taxObject->rate ?? 0;

        $subtotal = $qty * $price;

        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);

        return $taxAmount; // optional rounding
    }

    public function getTaxNameAttribute()
    {
        return $this->taxObject->name ?? '-';
    }

    public function getTaxRateAttribute()
    {
        return $this->taxObject->rate ?? '-';
    }

    public function getChartAccountNameAttribute()
    {
        return $this->chartAccount->name ?? '-';
    }
}
