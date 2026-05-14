<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'serial_number',
        'part_no',
        'product_id',
        'sub_product_id',
        'notes',
        'created_by',
    ];

    // Relationships
    public function grns()
    {
        return $this->belongsToMany(Grn::class, 'grn_serial_numbers', 'serial_number_id', 'grn_id')
            ->withPivot('grn_item_id')
            ->withTimestamps();
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
