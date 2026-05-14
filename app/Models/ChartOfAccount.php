<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    /**
     * Ensure account id exists for this company (general_ledger.account → chart_of_accounts.id).
     *
     * @throws \RuntimeException
     */
    public static function ensureExistsForCompany(?int $accountId, int $companyCreatorId, string $contextLabel): int
    {
        if (empty($accountId)) {
            throw new \RuntimeException(__('Chart account is missing for :context. Check vendor, product category purchase account, or tax settings.', ['context' => $contextLabel]));
        }
        if (!static::where('id', $accountId)->where('created_by', $companyCreatorId)->exists()) {
            throw new \RuntimeException(__('Chart account #:id for :context is invalid or was deleted. Update accounting settings.', ['id' => $accountId, 'context' => $contextLabel]));
        }

        return $accountId;
    }

    protected $fillable = [
        'id',
        'name',
        'code',
        'type',
        'sub_type',
        'is_enabled',
        'description',
        'created_by',
    ];

    public function types()
    {
        return $this->hasOne('App\Models\ChartOfAccountType', 'id', 'type');
    }

    public function accounts()
    {
        return $this->hasOne('App\Models\JournalItem', 'account', 'id');
    }

    public function balance()
    {
        $journalItem = JournalItem::select(\DB::raw('sum(credit) as totalCredit'),
            \DB::raw('sum(debit) as totalDebit'),
            \DB::raw('sum(credit) - sum(debit) as netAmount'))->where('account', $this->id);
        $journalItem = $journalItem->first();
        $data['totalCredit'] = $journalItem->totalCredit;
        $data['totalDebit'] = $journalItem->totalDebit;
        $data['netAmount'] = $journalItem->netAmount;

        return $data;
    }

    public function subType()
    {
        return $this->hasOne('App\Models\ChartOfAccountSubType', 'id', 'sub_type');
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function expenses()
{
    return $this->hasMany(InvoiceExpense::class);
}
}
