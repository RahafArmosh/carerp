<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosRefundItem extends Model
{
    protected $fillable = [
        'refund_id',
        'pos_products_id',
        'product_no',
        'quantity',
        'return_price',
        'combo_id',
        'price_list_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'return_price' => 'decimal:2',
    ];

    // An item belongs to a refund
    public function refund()
    {
        return $this->belongsTo(PosRefund::class, 'refund_id');
    }

    // An item belongs to a POS Product
    public function posProduct()
    {
        return $this->belongsTo(PosProduct::class, 'pos_products_id');
    }

    // An item may belong to a Combo
    public function combo()
    {
        return $this->belongsTo(ComboOffer::class, 'combo_id');
    }

    // An item may belong to a Price List
    public function priceList()
    {
        return $this->belongsTo(PriceRule::class, 'price_list_id');
    }
}
