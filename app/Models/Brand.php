<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Brand extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name','category_id','created_by'];

    public function categories()
    {
        return $this->belongsToMany(ProductServiceCategory::class, 'brand_category', 'brand_id', 'product_service_category_id')
            ->using(BrandCategory::class)
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Replace category links: soft-detach removed IDs, attach or restore added IDs (respects unique pivot rows).
     *
     * @param  array<int|string>  $categoryIds
     */
    public function syncCategories(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        $currentActive = $this->categories()->pluck('product_service_categories.id')->all();
        $toRemove = array_diff($currentActive, $categoryIds);
        $toAdd = array_diff($categoryIds, $currentActive);

        foreach ($toRemove as $categoryId) {
            $this->categories()->updateExistingPivot($categoryId, ['deleted_at' => now()]);
        }

        foreach ($toAdd as $categoryId) {
            $existing = DB::table('brand_category')
                ->where('brand_id', $this->id)
                ->where('product_service_category_id', $categoryId)
                ->first();

            if ($existing) {
                DB::table('brand_category')->where('id', $existing->id)->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
            } else {
                $this->categories()->attach($categoryId);
            }
        }
    }

    public function subBrands()
    {
        return $this->hasMany(VehicleModel::class);
    }

    public function products()
    {
        return $this->hasMany(ProductService::class);
    }
}
