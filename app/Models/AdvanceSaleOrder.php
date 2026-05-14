<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceSaleOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'advance_sale_order_no',
        'customer_id',
        'customer_trn_no',
        'sales_order_date',
        'currency_id',
        'exchange_rate',
        'tax_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'sales_order_date' => 'date',
        'exchange_rate' => 'decimal:6',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(AdvanceSaleOrderItem::class, 'advance_sale_order_id');
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class, 'advance_sale_order_id');
    }
}
