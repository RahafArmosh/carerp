<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    protected $fillable = [
        'bill_id',
        'date',
        'account_id',
        'payment_method',
        'payment_id',
        'reference',
        'description',
        'currency_id',
        'currency_rate',
        'amount_in_currency'
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
    return $this->belongsTo(Payment::class);
}
public function bill()
{
    return $this->belongsTo(Bill::class, 'bill_id');
}
}
