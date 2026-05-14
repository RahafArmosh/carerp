<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimpleExpense extends Model
{
    use SoftDeletes;

    /** Ledger / UI: current naming */
    public const REF_EXPENSE = 'Service Bill';

    public const REF_EXPENSE_DELETE = 'Delete Service Bill';

    public const REF_PAYMENT = 'Service Bill Payment';

    public const REF_PAYMENT_DELETE = 'Delete Service Bill Payment';

    public const REF_TAX = 'Service Bill Tax';

    /** Historical rows created before rename */
    public const REF_EXPENSE_LEGACY = 'Simple Expense';

    public const REF_EXPENSE_DELETE_LEGACY = 'Delete Simple Expense';

    public const REF_PAYMENT_LEGACY = 'Simple Expense Payment';

    public const REF_PAYMENT_DELETE_LEGACY = 'Delete Simple Expense Payment';

    public const REF_TAX_LEGACY = 'Simple Expense Tax';

    /** expense_id number prefix (without #) */
    public const EXPENSE_NUMBER_PREFIX = 'SVB';

    protected $table = 'simple_expenses';
    
    protected $fillable = [
        'vender_id',
        'expense_date',
        'due_date',
        'expense_id',
        'category_id',
        'created_by',
        'tax_id',
        'currency_id',
        'exchange_rate',
        'attachment',
        'status',
        'send_date',
        'type',
        'user_type',
        'payment_status',
    ];

    public static $statues = [
        'Draft',
        'Send To Approve',
        'Approved',
        '',
        'Sent',
        '',
        'Received'
    ];

    public static $paymentstatues = [
        'Unpaid',
        '',
        'Partially Paid',
        '',
        'Paid',
    ];

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function accounts()
    {
        return $this->hasMany('App\Models\ExpenseAccount', 'ref_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\SimpleExpensePayment', 'expense_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency', 'currency_id');
    }

    public function getAccountTotal()
    {
        return $this->accounts()->sum('price');
    }

    public function getTotal()
    {
        $subTotal = $this->getAccountTotal();
        $taxRate = 0;
        
        if ($this->tax_id) {
            $taxIds = explode(',', $this->tax_id);
            foreach ($taxIds as $taxId) {
                $tax = \App\Models\Tax::find($taxId);
                if ($tax) {
                    $taxRate += $tax->rate;
                }
            }
        }
        
        $taxAmount = $subTotal * ($taxRate / 100);
        return $subTotal + $taxAmount;
    }

    public function getExpenseDue()
    {
        $paid = $this->payments()->sum('amount');
        return $this->getTotal() - $paid;
    }

    public static function ledgerReferencesExpense(): array
    {
        return [self::REF_EXPENSE, self::REF_EXPENSE_LEGACY, self::REF_EXPENSE_DELETE, self::REF_EXPENSE_DELETE_LEGACY];
    }

    public static function ledgerReferencesPayment(): array
    {
        return [self::REF_PAYMENT, self::REF_PAYMENT_LEGACY, self::REF_PAYMENT_DELETE, self::REF_PAYMENT_DELETE_LEGACY];
    }

    public static function ledgerReferencesAll(): array
    {
        return array_merge(self::ledgerReferencesExpense(), self::ledgerReferencesPayment());
    }

    public static function referenceIsExpenseLine(?string $ref): bool
    {
        return $ref && in_array($ref, [self::REF_EXPENSE, self::REF_EXPENSE_LEGACY, self::REF_EXPENSE_DELETE, self::REF_EXPENSE_DELETE_LEGACY], true);
    }

    public static function referenceIsPaymentLine(?string $ref): bool
    {
        return $ref && in_array($ref, self::ledgerReferencesPayment(), true);
    }

    public static function maxExpenseSequenceForCreator(int $createdBy): int
    {
        $max = 0;
        foreach (self::withTrashed()->where('created_by', $createdBy)->pluck('expense_id') as $id) {
            if (preg_match('/#(?:SEXP|SVB)(\d+)/', (string) $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }

    public static function nextExpenseSequenceNumber(int $createdBy): int
    {
        return self::maxExpenseSequenceForCreator($createdBy) + 1;
    }

    public static function formatExpenseIdFromSequence(int $seq): string
    {
        return '#' . self::EXPENSE_NUMBER_PREFIX . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * General ledger `type` column holds expense_id (#SVB… / legacy #SEXP…) on expense lines.
     */
    public static function glTypeLooksLikeServiceBillExpenseId(?string $type): bool
    {
        if (!$type) {
            return false;
        }

        return strstr($type, 'SEXP') !== false || strstr($type, self::EXPENSE_NUMBER_PREFIX) !== false;
    }

    public static function glTypeIsServiceBillPayment(?string $type): bool
    {
        if (!$type) {
            return false;
        }

        return strstr($type, self::REF_PAYMENT_LEGACY) !== false || strstr($type, self::REF_PAYMENT) !== false;
    }

    public static function glTypeIsExpensePaymentButNotServiceBill(?string $type): bool
    {
        if (!$type) {
            return false;
        }

        return strstr($type, 'Expense Payment') !== false && !self::glTypeIsServiceBillPayment($type);
    }

    public static function applyGeneralLedgerHeadExpenseTypes($query): void
    {
        $query->where(function ($q) {
            $q->where('type', 'LIKE', '%SEXP%')
                ->orWhere('type', 'LIKE', '%' . self::EXPENSE_NUMBER_PREFIX . '%')
                ->orWhere('type', 'LIKE', '%' . self::REF_EXPENSE_LEGACY . '%')
                ->orWhere('type', 'LIKE', '%' . self::REF_EXPENSE . '%');
        });
    }

    public static function applyGeneralLedgerPaymentTypes($query): void
    {
        $query->where(function ($q) {
            $q->where('type', 'LIKE', '%' . self::REF_PAYMENT_LEGACY . '%')
                ->orWhere('type', 'LIKE', '%' . self::REF_PAYMENT . '%');
        });
    }
}
