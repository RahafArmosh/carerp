<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pro_id',
        'product_id',
        'part_no',
        'description',
        'order_qty',
        'supplied_qty',
        'remaining_qty',
        'unit_price',
        'total_amount',
    ];

    // Relationships
    public function pro()
    {
        return $this->belongsTo(Pro::class, 'pro_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    // Helper methods to calculate remaining_qty and total_amount
    public function calculateRemainingQty()
    {
        $this->remaining_qty = $this->order_qty - $this->supplied_qty;
        return $this->remaining_qty;
    }

    public function calculateTotalAmount()
    {
        $this->total_amount = $this->order_qty * $this->unit_price;
        return $this->total_amount;
    }

    // Automatically calculate fields when setting order_qty or unit_price
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate remaining_qty
            $item->remaining_qty = $item->order_qty - $item->supplied_qty;
            
            // Calculate total_amount
            $item->total_amount = $item->order_qty * $item->unit_price;
        });
    }
}
