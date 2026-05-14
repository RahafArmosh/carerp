<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingListHistory extends Model
{
    protected $fillable = [
        'pricing_list_id',
        'price',
        'created_by'
    ];

    public function pricingList()
    {
        return $this->belongsTo(PricingList::class);
    }
}
