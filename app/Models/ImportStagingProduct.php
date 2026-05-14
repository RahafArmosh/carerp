<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportStagingProduct extends Model
{
    protected $fillable = [
        'import_session_id',
        'created_by',
        'bill_data',
        'sku',
        'product_id',
        'product_name',
        'brand_name',
        'sub_brand_name',
        'category_name',
        'quantity',
        'sale_price',
        'purchase_price',
        'discount',
        'product_no',
        'custom_fields',
        'status',
        'status_message',
        'original_row_data',
        'row_number',
    ];

    protected $casts = [
        'bill_data' => 'array',
        'custom_fields' => 'array',
        'original_row_data' => 'array',
        'quantity' => 'integer',
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Get the product if matched
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    /**
     * Get the user who created this import
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
