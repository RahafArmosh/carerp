<?php

namespace App\Exports;

use App\Models\BankAccount;
use App\Models\Utility;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BankAccountExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        $accounts = BankAccount::with('chartAccount')
            ->where('created_by', \Auth::user()->creatorId())
            ->get();

        return $accounts->map(function (BankAccount $account) {
            $chartAccountName = !empty($account->chartAccount) ? $account->chartAccount->name : '-';
            $chartAccountId = !empty($account->chartAccount) ? $account->chartAccount->id : null;
            $currentBalance = $chartAccountId ? Utility::getAccountBalanceNew($chartAccountId) : 0;

            return [
                'Chart Of Account' => $chartAccountName,
                'Name' => $account->holder_name,
                'Bank' => $account->bank_name,
                'Account Number' => $account->account_number,
                'Opening Balance' => $account->opening_balance,
                'Current Balance' => $currentBalance,
                'Contact Number' => $account->contact_number,
                'Bank Address' => $account->bank_address,
                'Created At' => optional($account->created_at)->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Chart Of Account',
            'Name',
            'Bank',
            'Account Number',
            'Opening Balance',
            'Current Balance',
            'Contact Number',
            'Bank Address',
            'Created At',
        ];
    }
}


