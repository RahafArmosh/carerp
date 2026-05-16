<?php

use App\Models\GeneralLedger;
use Illuminate\Support\Facades\Auth;

if (!function_exists('createGeneralLedgerEntry')) {
    function createGeneralLedgerEntry($params)
    {
        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
        $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

        if (GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists()) {
            throw new \Exception("Voucher ID conflict. Please try again.");
        }

        $ledgerEntry = new GeneralLedger();
        $ledgerEntry->vid = $newVoucherId;
        $ledgerEntry->account = $params['account_id'];
        $ledgerEntry->type = $params['type'];
        $ledgerEntry->debit = $params['debit'] ?? 0;
        $ledgerEntry->credit = $params['credit'] ?? 0;
        $ledgerEntry->ref_id = $params['ref_id'] ?? null;
        $ledgerEntry->user_id = $params['user_id'] ?? 0;
        $ledgerEntry->created_by = Auth::user()->creatorId();
        $ledgerEntry->send_date = $params['date'] ?? now();
        $ledgerEntry->reference = $params['reference'] ?? null;

        $ledgerEntry->save();

        return $ledgerEntry;
    }
}
