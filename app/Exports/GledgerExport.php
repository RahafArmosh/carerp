<?php

namespace App\Exports;

use App\Models\GeneralLedger;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GledgerExport implements FromCollection, WithHeadings
{

    protected $startDate;
    protected $endDate;
    protected $account;

    public function __construct($startDate, $endDate, $account)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->account = $account;
    }
    public function collection()
    {

        $data = GeneralLedger::select(
                'general_ledger.id',
                'general_ledger.vid as item',
                'general_ledger.type',
                'general_ledger.reference',
                'general_ledger.ref_number',
                'debit',
                'credit',
                'ref_id',
                'chart_of_accounts.name as account',
                'user_id',
                'send_date'
            )
            ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
            ->where('general_ledger.created_by', \Auth::user()->creatorId());

        // ✅ Apply filters
        if (!empty($this->startDate)) {
            $data->whereDate('general_ledger.send_date', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $data->whereDate('general_ledger.send_date', '<=', $this->endDate);
        }

        if (!empty($this->account)) {
            if (is_numeric($this->account)) {
                $data->where('general_ledger.account', (int) $this->account);
            } else {
                $data->where('chart_of_accounts.name', 'like', '%' . $this->account . '%');
            }
        }

        return $data
            ->orderBy('general_ledger.send_date', 'asc')
            ->orderBy('general_ledger.vid', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            "ID",
            "Vid",
            "Type",
            "Reference",
            "Ref Number",
            "Debit",
            "Credit",
            "Ref_id",
            "Account",
            "User_id",
            "Date"
        ];
    }
}
