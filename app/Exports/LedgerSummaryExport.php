<?php

namespace App\Exports;

use App\Models\GeneralLedger;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $accountId;

    public function __construct($startDate, $endDate, $accountId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->accountId = $accountId;
    }

    public function collection()
    {
        // Use the same logic as the ledger summary view
        $accountArrays = [];
        
        if (!empty($this->accountId)) {
            $chart_accounts = \App\Models\ChartOfAccount::where('id', $this->accountId)
                ->where('created_by', \Auth::user()->creatorId())
                ->get();
        } else {
            $chart_accounts = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->get();
        }

        foreach ($chart_accounts as $key => $account) {
            $chartDatas = Utility::getAccountData(
                $account['id'],
                $this->startDate,
                $this->endDate,
            );
            
            $chartDatas = $chartDatas->toArray();
            $accountArrays[] = $chartDatas;
        }

        // Flatten the array for export
        $exportData = collect();
        foreach ($accountArrays as $accounts) {
            foreach ($accounts as $account) {
                $exportData->push($account);
            }
        }

        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Account Name',
            'User Name',
            'Transaction Type',
            'Transaction Date',
            'Debit',
            'Credit',
            'Balance',
            'Reference',
            'Ref ID',
            'User ID',
            'Type',
            'Vid'
        ];
    }

    public function map($account): array
    {
        // Calculate running balance
        static $runningBalance = 0;
        $runningBalance += ($account->debit - $account->credit);

        return [
            $account->account_name ?? '',
            $account->user_name ?? '',
            $account->reference ?? '',
            $account->send_date ?? '',
            $account->debit ?? 0,
            $account->credit ?? 0,
            $runningBalance,
            $account->reference ?? '',
            $account->ref_id ?? '',
            $account->user_id ?? '',
            $account->type ?? '',
            $account->vid ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
