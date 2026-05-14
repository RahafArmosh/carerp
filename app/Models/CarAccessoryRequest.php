<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarAccessoryRequest  extends Model
{
    protected $fillable = [
        'request_no', 'request_date', 'status', 'created_by'
    ];

    public function items()
    {
        return $this->hasMany(CarAccessoryRequestItem::class, 'request_id');
    }
}
