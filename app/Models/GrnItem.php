<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grn_id',
        'asn_item_id',
        'part_no',
        'description',
        'qty',
        'received_qty',
        'discrepancy',
        'unit_price',
        'total_price',
        'product_id',
        'sub_product_id',
    ];

    // Relationships
    public function grn()
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }

    public function asnItem()
    {
        return $this->belongsTo(AsnItem::class, 'asn_item_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function serialNumbers()
    {
        return $this->belongsToMany(SerialNumber::class, 'grn_serial_numbers', 'grn_item_id', 'serial_number_id')
            ->withTimestamps();
    }

    // Automatically calculate fields when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate discrepancy: received_qty - qty
            $item->discrepancy = $item->received_qty - $item->qty;
            
            // Calculate total_price: received_qty * unit_price
            $item->total_price = $item->received_qty * $item->unit_price;
        });
    }
}
