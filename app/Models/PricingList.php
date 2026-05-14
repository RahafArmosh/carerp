<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingList extends Model
{
    protected $fillable = [
        'pricing_list_type_id',
        'product_service_id',
        'warehouse_id',
        'current_price',
        'created_by',
    ];

    public function type()
    {
        return $this->belongsTo(PricingListType::class, 'pricing_list_type_id');
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
