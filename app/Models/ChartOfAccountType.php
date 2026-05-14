<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountType extends Model
{
    protected $fillable = [
        'name',
        'created_by',
    ];


    public function subTypes()
    {
        return $this->hasMany(ChartOfAccountSubType::class, 'type');
    }
}
