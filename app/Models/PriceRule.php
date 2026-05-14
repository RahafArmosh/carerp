<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'apply_to',
        'target_id',
        'price_mode',
        'value',
        'apply_99',
        'created_by',
        'base_price_source'
    ];

    protected $casts = [
        'apply_99' => 'boolean',
    ];

    /**
     * Relationships
     */

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by warehouse
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Get a readable label for the target (e.g. category, brand)
     */
    public function getTargetLabelAttribute()
    {
        switch ($this->apply_to) {
            case 'brand':
                return optional(\App\Models\Brand::find($this->target_id))->name;
            case 'category':
                return optional(\App\Models\ProductServiceCategory::find($this->target_id))->name;
            case 'sub_brand':
                return optional(\App\Models\VehicleModel::find($this->target_id))->name;
            case 'product':
                $productsku = ProductService::find($this->target_id);
                return $productsku->name . ' - ' . $productsku->sku;

            default:
                return 'Unknown';
        }
    }
}
