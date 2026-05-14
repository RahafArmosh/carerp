<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'from_warehouse',
        'to_warehouse',
        'product_id',
        'product_no',
        'quantity',
        'date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }

    public function fromWarehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'from_warehouse');
    }
    public function toWarehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'to_warehouse');
    }

    /**
     * Get the transfer request this transfer belongs to
     */
    public function request()
    {
        return $this->belongsTo(WarehouseTransferRequest::class, 'request_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }

}
