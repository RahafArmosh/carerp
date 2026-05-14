<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\GeneralLedger;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerStatementExport implements FromCollection, WithHeadings
{
    protected $customer;
    protected $startMonth;
    protected $endMonth;
    protected $account;
    protected $creatorId;
    protected $user;

    public function __construct($customer, $startMonth, $endMonth, $account, $creatorId)
    {
        $this->customer = $customer;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->account = $account;
        $this->creatorId = $creatorId;
        $this->user = \Auth::user();
    }

    public function collection()
    {
        $receivablesAccount = ChartOfAccount::where('created_by', $this->creatorId)
            ->where('name', 'Account Receivables')
            ->first();
        if (!$receivablesAccount) {
            return collect([]);
        }

        $query = GeneralLedger::where('general_ledger.created_by', $this->creatorId)
            ->where('general_ledger.account', $receivablesAccount->id)
            ->selectRaw('general_ledger.vid, general_ledger.account, general_ledger.ref_id, general_ledger.type, general_ledger.user_id, SUM(general_ledger.credit) as total_credit, SUM(general_ledger.debit) as total_debit, general_ledger.created_at, general_ledger.updated_at, general_ledger.send_date, general_ledger.reference, general_ledger.payment_id, general_ledger.ref_number')
            ->groupBy('general_ledger.vid')
            ->orderBy('general_ledger.send_date', 'ASC')
            ->orderBy('general_ledger.vid', 'ASC');

        if (!empty($this->customer)) {
            $query->where('general_ledger.user_id', (int) $this->customer);
        }

        if (!empty($this->startMonth) && !empty($this->endMonth)) {
            $start = new \DateTime($this->startMonth);
            $end = new \DateTime($this->endMonth);
            $end->modify('last day of this month');
            $query->whereBetween('general_ledger.send_date', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        } else {
            $start = new \DateTime('first day of 6 months ago');
            $end = new \DateTime('last day of this month');
            $query->whereBetween('general_ledger.send_date', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        }

        if (!empty($this->account) && $this->account != '') {
            $query->where('general_ledger.account', $this->account);
        }

        $rows = $query->get();

        $balance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $exportRows = [];

        foreach ($rows as $gl) {
            $totalDebit += (float) $gl->total_debit;
            $totalCredit += (float) $gl->total_credit;
            $balance = $totalDebit - $totalCredit;

            $customerName = '';
            if ($gl->user_id) {
                $c = Customer::find($gl->user_id);
                $customerName = $c ? $c->name : '';
            }
            $accountName = '';
            if ($gl->account) {
                $acc = ChartOfAccount::find($gl->account);
                $accountName = $acc ? $acc->name : '';
            }
            $amount = (float) $gl->total_debit > 0 ? (float) $gl->total_debit : (float) $gl->total_credit;

            $typeDisplay = $gl->type ?? '';
            if (($gl->reference ?? '') === 'POS Deletion Reversal') {
                $typeDisplay = $typeDisplay !== '' ? $typeDisplay : __('POS Deletion Reversal');
            }

            $exportRows[] = [
                $this->user->dateFormat($gl->send_date),
                $this->user->dateFormat($gl->updated_at),
                $gl->vid,
                $this->user->priceFormat($amount),
                $typeDisplay,
                $customerName,
                $accountName,
                $this->user->priceFormat((float) $gl->total_debit),
                $this->user->priceFormat((float) $gl->total_credit),
                $this->user->priceFormat($balance),
            ];
        }

        // Add total row
        $exportRows[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            __('Total'),
            $this->user->priceFormat($totalDebit),
            $this->user->priceFormat($totalCredit),
            $this->user->priceFormat($totalDebit - $totalCredit),
        ];

        return collect($exportRows);
    }

    public function headings(): array
    {
        return [
            __('Date'),
            __('Update Date'),
            __('VID'),
            __('Amount'),
            __('Type'),
            __('Name'),
            __('Account'),
            __('Debit'),
            __('Credit'),
            __('Balance'),
        ];
    }
}
