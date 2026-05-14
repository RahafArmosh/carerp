<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Currency;

class DealProduct extends Model
{
    protected $fillable = [
        'deal_id', 'product_id', 'quantity', 'price', 'currency_id', 'exchange_rate', 'exchange_price', 'created_by'
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

