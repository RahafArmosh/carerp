<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadProduct extends Model
{
    protected $fillable = ['lead_id', 'product_id', 'quantity','price'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class);
    }
}
