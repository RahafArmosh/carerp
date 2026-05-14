<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill',
        'vendor',
        'amount',
        'date',
        'currency_id',
        'currency_rate',
        'amount_in_currency',
    ];

    public function vendor()
    {
        return $this->hasOne('App\Models\Vender', 'vender_id', 'vendor');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
