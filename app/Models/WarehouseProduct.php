<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseProduct extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'created_by',
        'sale_price',
        'SP_sku',
        'product_num',
    ];
    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }
    public function warehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'from_warehouse');
    }

}
