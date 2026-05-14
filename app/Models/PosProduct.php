<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosProduct extends Model
{
    protected $fillable = [
        'id',
        'product_id',
        'sub_product_id',
        'pos_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'combo_price',
        'compo_id',
        'pricelist_price',
        'price_list_id',
        'status',
    ];

    public function product(){
        return $this->belongsTo(ProductService::class, 'product_id');
    }
    
    public function sub_product(){
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }
    
    // Get the component (compo)
    public function compo()
    {
        return $this->hasOne('App\Models\Compo', 'id', 'compo_id');
    }

    // Get the price list
    public function priceList()
    {
        return $this->hasOne('App\Models\PriceList', 'id', 'price_list_id');
    }


}
