<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductServiceImage extends Model
{
    protected $fillable = [
        'product_service_id',
        'file_name',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ProductServiceImage $image) {
            $creatorId = optional($image->productService)->created_by;
            if (! $creatorId || empty($image->file_name)) {
                return;
            }
            Utility::changeStorageLimit($creatorId, '/uploads/pro_image/'.$image->file_name);
        });
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }

    public function url(): string
    {
        return url('storage/uploads/pro_image/'.$this->file_name);
    }
}
