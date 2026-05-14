<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CustomerPayment  extends Model
{
    protected $fillable = [
        'payment_number',
        'date',
        'amount',
        'account_id',
        'chart_account_id',
        'charge',
        'bank_charge_account_id',
        'customer_id',
        'invoice_id',
        'payment_id',
        'description',
        'category_id',
        'payment_method',
        'reference',
        'created_by',
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

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_payments', 'payment_id', 'invoice_id')
            ->withTrashed()
            ->withPivot('amount');
    }

    public function invoicePayments()
    {
        return $this->hasMany(InvoicePayment::class, 'payment_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Next sequential customer payment number for this tenant (created_by).
     */
    public static function nextPaymentNumberFor(int $createdBy): int
    {
        return (int) DB::transaction(function () use ($createdBy) {
            $max = static::where('created_by', $createdBy)->lockForUpdate()->max('payment_number');

            return ($max !== null ? (int) $max : 0) + 1;
        });
    }

    /**
     * Display label (e.g. CP00001) for a customer payment row by primary key.
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
                ? auth()->user()->CustomerpaymentNumberFormat($num)
                : 'CP'.sprintf('%05d', $num);
        }

        return $cache[$id];
    }
}
