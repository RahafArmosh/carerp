<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpleExpensePayment extends Model
{
    protected $table = 'simple_expense_payments';
    
    protected $fillable = [
        'expense_id',
        'date',
        'account_id',
        'payment_method',
        'payment_id',
        'reference',
        'description',
        'currency_id',
        'currency_rate',
        'amount_in_currency',
        'amount',
        'add_receipt',
        'created_by',
        'status',
    ];

    public static $statues = [
        'Draft',
        '',
        'Paid',
    ];

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function expense()
    {
        return $this->belongsTo(SimpleExpense::class, 'expense_id');
    }
}
