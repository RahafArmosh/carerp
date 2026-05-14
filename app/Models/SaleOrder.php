<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_order_no',
        'advance_sale_order_id',
        'customer_id',
        'customer_trn_no',
        'sales_order_date',
        'currency_id',
        'exchange_rate',
        'tax_id',
        'status',
        'invoice_id',
        'created_by',
        'converted_quotation_id'
    ];

    protected $casts = [
        'sales_order_date' => 'date',
        'exchange_rate' => 'decimal:6',
    ];

    /**
     * Get the customer that owns the sale order
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function advanceSaleOrder()
    {
        return $this->belongsTo(AdvanceSaleOrder::class, 'advance_sale_order_id');
    }

    /**
     * Get the currency for the sale order
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the invoice if converted
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the user who created the sale order
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all items for the sale order
     */
    public function items()
    {
        return $this->hasMany(SaleOrderItem::class, 'sale_order_id');
    }

    /**
     * Check if sale order is converted to invoice
     */
    public function isConverted()
    {
        return !empty($this->invoice_id);
    }

    /**
     * Get the pick list for this sale order
     */
    public function pickList()
    {
        return $this->hasOne(PickList::class, 'sales_order_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'converted_quotation_id');
    }

}
