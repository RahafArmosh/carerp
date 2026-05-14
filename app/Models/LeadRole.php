<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadRole extends Model
{
    protected $fillable = ['name', 'assigned_user_id', 'created_by','pipeline_id', 'active'];

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    public function conditions()
    {
        return $this->hasMany(LeadRoleCondition::class);
    }
    public function pipeline()
{
    return $this->belongsTo(Pipeline::class);
}
}
