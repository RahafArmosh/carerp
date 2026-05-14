<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProductImage extends Model
{
    protected $fillable = [
        'sub_product_id',
        'file_name',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (SubProductImage $image) {
            $creatorId = optional($image->subProduct)->created_by;
            if (! $creatorId || empty($image->file_name)) {
                return;
            }
            Utility::changeStorageLimit($creatorId, '/uploads/sub_product_image/'.$image->file_name);
        });
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class);
    }

    public function url(): string
    {
        return url('storage/uploads/sub_product_image/'.$this->file_name);
    }
}
