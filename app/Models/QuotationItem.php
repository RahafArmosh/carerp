<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'parent_id',
        'partnumber',
        'product_service_id',
        'description',
        're_quantity',
        'av_quantity',
        'unit_price',
        'total_price',
        'is_alternative',
        'is_selected',
        'form_state',
        'updated_by',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class, 'product_service_id');
    }

    public function parent()
    {
        return $this->belongsTo(QuotationItem::class, 'parent_id');
    }

    public function alternatives()
    {
        return $this->hasMany(QuotationItem::class, 'parent_id')
                    ->where('is_alternative', true);
    }
}
