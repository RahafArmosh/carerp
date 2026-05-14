<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'invoice',
        'customer',
        'amount',
        'date',
        'currency_id',
        'currency_rate',
        'amount_in_currency',
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'customer_id', 'customer');
    }

    public function chartAccount()
{
    return $this->belongsTo(ChartOfAccount::class);
}

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice', 'id');
    }
}
