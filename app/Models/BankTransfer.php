<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $fillable = [
        'from_account',
        'to_account',
        'amount',
        'date',
        'payment_method',
        'reference',
        'description',
        'created_by',
        'currency_id',
        'currency_rate'
    ];

    public function fromBankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'from_account');
    }

    public function toBankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'to_account');
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

}
