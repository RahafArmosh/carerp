<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Currency;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'date',
        'amount',
        'account_id',
        'payment_method',
        'order_id',
        'currency_id',
        'currency_rate',
        'amount_in_currency',
        'txn_id',
        'payment_type',
        'receipt',
        'reference',
        'description',
        'charge',
        'bank_charge_account_id',
        'payment_id',
    ];


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chargebankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'bank_charge_account_id');
    }
    public function customerPayment()
    {
        return $this->belongsTo(CustomerPayment::class, 'payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
