<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectExpenseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'direct_expense_id',
        'sub_product_id',
        'qty',
        'amount',
        'currency_amount',
        'description',
        'chart_account_id',
    ];

    public function directExpense()
    {
        return $this->belongsTo(DirectExpense::class, 'direct_expense_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
    }
}
