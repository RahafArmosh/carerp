<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceSaleOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'advance_sale_order_id',
        'part_no',
        'description',
        'req_qty',
        'converted_qty',
        'unit_price',
    ];

    protected $casts = [
        'req_qty' => 'decimal:2',
        'converted_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function advanceSaleOrder()
    {
        return $this->belongsTo(AdvanceSaleOrder::class, 'advance_sale_order_id');
    }
}
