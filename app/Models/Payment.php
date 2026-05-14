<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $fillable = [
        'payment_number',
        'date',
        'amount',
        'account_id',
        'chart_account_id',
        'vender_id',
        'bill_id',
        'payment_id',
        'description',
        'category_id',
        'payment_method',
        'reference',
        'created_by',
        'status',
        'currency_id',
        'currency_rate',
        'amount_in_currency'
    ];

    public static $statues = [
        'Draft',
        '',
        'Received'
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    public function bills()
    {
        return $this->belongsToMany(Bill::class, 'bill_payments')
            ->withTrashed()
            ->withPivot('amount');
    }

    public function billPayments()
{
    return $this->hasMany(BillPayment::class);
}

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id')->withTrashed();
    }

    /**
     * Next sequential vendor payment number for this tenant (created_by).
     */
    public static function nextPaymentNumberFor(int $createdBy): int
    {
        return (int) DB::transaction(function () use ($createdBy) {
            $max = static::where('created_by', $createdBy)->lockForUpdate()->max('payment_number');

            return ($max !== null ? (int) $max : 0) + 1;
        });
    }

    /**
     * Display label (e.g. VP00001) for a payment row by primary key; caches per request.
     */
    public static function formatLabelForId(?int $id): string
    {
        if (! $id) {
            return '—';
        }
        static $cache = [];
        if (! array_key_exists($id, $cache)) {
            $row = static::query()->select('payment_number')->find($id);
            $num = $row?->payment_number ?? $id;
            $cache[$id] = auth()->check()
                ? auth()->user()->paymentNumberFormat($num)
                : 'VP'.sprintf('%05d', $num);
        }

        return $cache[$id];
    }
}
