<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookToken extends Model
{
    protected $fillable = [
        'user_token',
        'page_token',
        'expires_at'
    ];
}
