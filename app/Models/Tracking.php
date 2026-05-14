<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLocationNameAttribute()
    {
        return app('App\Services\GeocodingService')->getAddress($this->latitude, $this->longitude);
    }
}
