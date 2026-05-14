<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarAccessoryRequestItem extends Model
{
    protected $fillable = [
        'request_id', 'car_id', 'accessory_id', 'product_id', 'quantity', 'sell_price'
    ];

    public function request()
    {
        return $this->belongsTo(CarAccessoryRequest::class, 'request_id');
    }

    public function car()
    {
        return $this->belongsTo(SubProduct::class, 'car_id');
    }

    public function accessory()
    {
        return $this->belongsTo(SubProduct::class, 'accessory_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
