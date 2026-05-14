<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboOffer extends Model
{
    protected $table = 'combo_offers';
    protected $fillable = [
        'warehouse_id',
        'brand_id',
        'sub_brand_id',
        'product_service_id',
        'type',
        'buy_quantity',
        'get_quantity',
        'tiered_price',
        'active',
        'valid_until',
        'created_by'
    ];

    protected $casts = [
        'active' => 'boolean',
        'valid_until' => 'date',
    ];


    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }

    public function products()
    {
        return $this->belongsToMany(ProductService::class, 'combo_offer_product_service');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subBrand()
    {
        return $this->belongsTo(VehicleModel::class, 'sub_brand_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
            ->orWhere('valid_until', '>=', now());
        });
    }

}
