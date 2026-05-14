<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_order_id',
        'part_no',
        'description',
        'req_qty',
        'stock_qty',
        'picking_qty',
        'packed_qty',
        'discrepancy',
        'unit_price',
        'product_id',
        'sub_product_id',
    ];

    protected $casts = [
        'req_qty' => 'decimal:2',
        'stock_qty' => 'decimal:2',
        'picking_qty' => 'decimal:2',
        'packed_qty' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Automatically calculate fields when saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Discrepancy = difference between STOCK QTY (reserved) and PACKED QTY: packed_qty - stock_qty
            $stockQty = $item->stock_qty ?? $item->req_qty ?? 0;
            $item->discrepancy = ($item->packed_qty ?? 0) - $stockQty;
        });
    }

    /**
     * Get the sale order that owns the item
     */
    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    /**
     * Get the product if linked
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    /**
     * Get the sub-product if linked
     */
    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }
}
