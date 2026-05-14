<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseProductPriceList extends Model
{
    use HasFactory;
    protected $table = 'warehouse_product_price_lists';
    protected $fillable=['warehouse_id','productservice_id','sale_price'];
    public function productService()
    {
        return $this->belongsTo(ProductService::class, 'productservice_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }
}
