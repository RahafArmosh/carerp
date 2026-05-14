<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogoutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'logged_out_at',
        'user_agent',
    ];
}
