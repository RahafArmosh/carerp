<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    //
    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'target_country','source_id','url','assigned_to','created_by'];
    public function assignedUser()
{
    return $this->belongsTo(User::class, 'assigned_to');
}
public function source()
{
    return $this->belongsTo(Source::class, 'source_id');
}
}
