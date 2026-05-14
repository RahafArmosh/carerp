<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadRoleCondition extends Model
{
    protected $fillable = ['lead_role_id', 'lead_column', 'operation', 'value', 'connector'];

    public function role()
{
    return $this->belongsTo(LeadRole::class);
}

}
