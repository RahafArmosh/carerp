<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalikAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'chart_of_account_id',
        'name',
        'balance'
    ];

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_of_account_id');
    }
}
