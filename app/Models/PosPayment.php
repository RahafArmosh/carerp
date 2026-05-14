<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPayment extends Model
{
    protected $fillable = [
        'pos_id',
        'date',
        'amount',
        'total_user_payment',
        'discount',
        'created_by',
        'voucher_id',
        'payment_method_id',
    ];


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }
    
    public function voucher()
    {
        return $this->hasOne('App\Models\Voucher', 'id', 'voucher_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_method_id', 'id');
    }
}
