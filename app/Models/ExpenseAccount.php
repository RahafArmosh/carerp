<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseAccount extends Model
{
    use HasFactory;

    protected $table = 'expense_accounts';

    protected $fillable = [
        'chart_account_id',
        'price',
        'description',
        'type',
        'ref_id',
    ];

    public function chartAccount()
    {
        return $this->belongsTo('App\Models\ChartOfAccount', 'chart_account_id', 'id');
    }
}
