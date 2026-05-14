<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class macRestrict extends Model
{
    protected $fillable = [
        'mac',
        'user_id',
        'created_by',
    ];
}
