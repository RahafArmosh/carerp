<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickListStatusLog extends Model
{
    protected $fillable = [
        'pick_list_id',
        'user_id',
        'old_status',
        'new_status',
        'changed_at',
        'created_by',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function pickList()
    {
        return $this->belongsTo(PickList::class, 'pick_list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
