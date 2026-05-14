<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealReminder extends Model
{
    protected $fillable = [
        'deal_id',
        'user_id',
        'created_by',
        'message',
        'is_read',
        'is_done',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_done' => 'boolean',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}


