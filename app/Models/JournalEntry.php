<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class JournalEntry extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'date',
        'reference',
        'description',
        'journal_id',
        'created_by',
        'attachment',
        'currency_id',
        'currency_rate',
    ];

    protected $casts = [
        'currency_rate' => 'float',
    ];


    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function accounts()
    {
        return $this->hasmany('App\Models\JournalItem', 'journal', 'id');
    }

    /**
     * Format an amount using the journal currency when set, otherwise company default.
     */
    public function formatMoney($amount): string
    {
        $user = \Auth::user();
        if (!$user) {
            return number_format((float) $amount, 2);
        }
        $currency = $this->relationLoaded('currency') ? $this->currency : $this->currency()->first();
        if ($currency && $currency->symbol) {
            return $user->priceFormatCurr($amount, $currency->symbol);
        }

        return $user->priceFormat($amount);
    }
     public function accountsDelete()
    {
        return $this->hasmany('App\Models\JournalItem', 'journal', 'id')->withTrashed();
    }

    public function totalCredit()
    {
        $total = 0;
        foreach($this->accounts as $account)
        {
            $total += $account->credit;
        }

        return $total;
    }

    public function totalDebit()
    {
        $total = 0;
        foreach($this->accounts as $account)
        {
            $total += $account->debit;
        }

        return $total;
    }

      public function totalCreditdelete()
    {
        $total = 0;
        foreach($this->accountsDelete as $account)
        {
            $total += $account->credit;
        }

        return $total;
    }

    public function totalDebitdelete()
    {
        $total = 0;
        foreach($this->accountsDelete as $account)
        {
            $total += $account->debit;
        }

        return $total;
    }


}
