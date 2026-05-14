<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectExpensePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'payment_date',
        'amount',
        'currency_id',
        'currency_rate',
        'currency_amount',
        'account_id',
        'direct_expense_id',
        'vendor_id',
        'description',
        'payment_method',
        'reference',
        'add_receipt',
        'status',
        'created_by',
    ];

    public static $statues = [
        'Draft',
        '',
        'Received'
    ];

    public function directExpense()
    {
        return $this->belongsTo(DirectExpense::class, 'direct_expense_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'vendor_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

