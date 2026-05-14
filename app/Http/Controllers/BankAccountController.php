<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('create bank account')) {
            $accounts = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->with(['chartAccount'])->get();

            return view('bankAccount.index', compact('accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create bank account')) {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            // $chart_accounts->prepend('Select Account', '');
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();
            return view('bankAccount.create', compact('customFields', 'chart_accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create bank account')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'holder_name' => 'required',
                    'bank_name' => 'required',
                    'account_number' => 'required',
                    'opening_balance' => 'required|numeric',
                    'contact_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }

            $account                  = new BankAccount();
            $account->chart_account_id = $request->chart_account_id;
            $account->holder_name     = $request->holder_name;
            $account->bank_name       = $request->bank_name;
            $account->account_number  = $request->account_number;
            $account->opening_balance = $request->opening_balance;
            $account->contact_number  = $request->contact_number;
            $account->bank_address    = $request->bank_address;
            $account->bank_details    = $request->bank_details;
            $account->created_by      = \Auth::user()->creatorId();
            $account->save();
            CustomField::saveData($account, $request->customField);
            $today = now()->toDateString();
            $account = ChartOfAccount::find($request->chart_account_id);

            // Determine voucher id to use
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'desc')
                ->first();
            $existingAccountVoucher = GeneralLedger::where('account', $request->chart_account_id)
                ->where('reference', 'opening balance')
                ->where('created_by', \Auth::user()->creatorId())
                ->first();
            $anyOpeningVoucher = GeneralLedger::where('reference', 'opening balance')
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'asc')
                ->first();

            $voucherIdToUse = $existingAccountVoucher?->vid
                ?? $anyOpeningVoucher?->vid
                ?? (($latestVoucher?->vid ?? 0) + 1);

            $existing = $existingAccountVoucher; // reuse if exists for this account

            // Only create/update ledger when opening balance is non-zero
            if ((float)$request->opening_balance != 0) {
                if ($request->opening_balance > 0) {
                    if ($existing) {
                        $existing->debit = $request->opening_balance;
                        $existing->credit = 0;
                        // $existing->send_date = $today;
                        $existing->save();
                    } else {
                        $entry = new GeneralLedger();
                        $entry->vid = $voucherIdToUse;
                        $entry->account = $request->chart_account_id;
                        $entry->type = 'opening balance';
                        $entry->debit = $request->opening_balance;
                        $entry->credit = 0;
                        $entry->ref_id = $request->chart_account_id;
                        $entry->user_id = 0;
                        $entry->created_by = \Auth::user()->creatorId();
                        $entry->send_date = $existingAccountVoucher->send_date ?? $anyOpeningVoucher->send_date ?? $today;
                        $entry->reference = 'opening balance';
                        $entry->ref_number = $account->name;
                        $entry->save();
                    }
                } else {
                    if ($existing) {
                        $existing->debit = 0;
                        $existing->credit = $request->opening_balance * -1;
                        // $existing->send_date = $today;
                        $existing->save();
                    } else {
                        $entry = new GeneralLedger();
                        $entry->vid = $voucherIdToUse;
                        $entry->account = $request->chart_account_id;
                        $entry->type = 'opening balance';
                        $entry->debit = 0;
                        $entry->credit = $request->opening_balance * -1;
                        $entry->ref_id = $request->chart_account_id;
                        $entry->user_id = 0;
                        $entry->created_by = \Auth::user()->creatorId();
                        $entry->send_date = $existingAccountVoucher->send_date ?? $anyOpeningVoucher->send_date ?? $today;
                        $entry->reference = 'opening balance';
                        $entry->ref_number = $account->name;
                        $entry->save();
                    }
                }
            }
            

            return redirect()->route('bank-account.index')->with('success', __('Account successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        if (\Auth::user()->can('manage bank account') || \Auth::user()->can('create bank account') || \Auth::user()->can('edit bank account') || \Auth::user()->type == 'super admin') {
            try {
                $filename = 'bank_accounts_' . date('Y-m-d_H-i-s') . '.xlsx';
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BankAccountExport(), $filename);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('bank-account.index');
    }


    public function edit(BankAccount $bankAccount)
    {
        if (\Auth::user()->can('edit bank account')) {
            if ($bankAccount->created_by == \Auth::user()->creatorId()) {
                $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                // $chart_accounts->prepend('Select Account', '');

                $bankAccount->customField = CustomField::getData($bankAccount, 'account');
                $customFields             = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();

                return view('bankAccount.edit', compact('bankAccount', 'customFields', 'chart_accounts'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, BankAccount $bankAccount)
    {
        if (\Auth::user()->can('create bank account')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'holder_name' => 'required',
                    'bank_name' => 'required',
                    'account_number' => 'required',
                    'opening_balance' => 'required|numeric',
                    'contact_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }
            $bankAccount->chart_account_id = $request->chart_account_id;
            $bankAccount->holder_name     = $request->holder_name;
            $bankAccount->bank_name       = $request->bank_name;
            $bankAccount->account_number  = $request->account_number;
            $bankAccount->opening_balance = $request->opening_balance;
            $bankAccount->contact_number  = $request->contact_number;
            $bankAccount->bank_address    = $request->bank_address;
            $bankAccount->bank_details    = $request->bank_details;
            $bankAccount->created_by      = \Auth::user()->creatorId();
            $bankAccount->save();
            CustomField::saveData($bankAccount, $request->customField);
            $today = now()->toDateString();
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'desc')
                ->first();
            $existingAccountVoucher = GeneralLedger::where('account', $request->chart_account_id)
                ->where('reference', 'opening balance')
                ->where('created_by', \Auth::user()->creatorId())
                ->first();
            $anyOpeningVoucher = GeneralLedger::where('reference', 'opening balance')
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'asc')
                ->first();

            $voucherIdToUse = $existingAccountVoucher?->vid
                ?? $anyOpeningVoucher?->vid
                ?? (($latestVoucher?->vid ?? 0) + 1);

            $existing = $existingAccountVoucher; // reuse if exists for this account
            $account = ChartOfAccount::find($request->chart_account_id);

            // Handle opening balance: update existing entry or create new one
            // If opening balance is 0 and entry exists, update it to 0 (or delete it)
            if ((float)$request->opening_balance != 0) {
                if ($request->opening_balance > 0) {
                    if ($existing) {
                        $existing->debit = $request->opening_balance;
                        $existing->credit = 0;
                        // $existing->send_date = $today;
                        $existing->save();
                    } else {
                        $entry = new GeneralLedger();
                        $entry->vid = $voucherIdToUse;
                        $entry->account = $request->chart_account_id;
                        $entry->type = 'opening balance';
                        $entry->debit = $request->opening_balance;
                        $entry->credit = 0;
                        $entry->ref_id = $request->chart_account_id;
                        $entry->user_id = 0;
                        $entry->created_by = \Auth::user()->creatorId();
                        $entry->send_date = $existingAccountVoucher->send_date ?? $anyOpeningVoucher->send_date ?? $today;
                        $entry->reference = 'opening balance';
                        $entry->ref_number = $account->name;
                        $entry->save();
                    }
                } else {
                    if ($existing) {
                        $existing->debit = 0;
                        $existing->credit = $request->opening_balance * -1;
                        // $existing->send_date = $today;
                        $existing->save();
                    } else {
                        $entry = new GeneralLedger();
                        $entry->vid = $voucherIdToUse;
                        $entry->account = $request->chart_account_id;
                        $entry->type = 'opening balance';
                        $entry->debit = 0;
                        $entry->credit = $request->opening_balance * -1;
                        $entry->ref_id = $request->chart_account_id;
                        $entry->user_id = 0;
                        $entry->created_by = \Auth::user()->creatorId();
                        $entry->send_date = $existingAccountVoucher->send_date ?? $anyOpeningVoucher->send_date ?? $today;
                        $entry->reference = 'opening balance';
                        $entry->ref_number = $account->name;
                        $entry->save();
                    }
                }
            } else {
                // Opening balance is 0 - update existing entry to 0 or delete it
                if ($existing) {
                    // Update existing entry to have 0 debit and 0 credit
                    $existing->debit = 0;
                    $existing->credit = 0;
                    $existing->save();
                    
                    // Optionally, you can delete the entry instead if preferred:
                    // $existing->delete();
                }
                // If no existing entry and opening balance is 0, don't create one
            }

            return redirect()->route('bank-account.index')->with('success', __('Account successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(BankAccount $bankAccount)
    {
        if (\Auth::user()->can('delete bank account')) {
            if ($bankAccount->created_by == \Auth::user()->creatorId()) {
                $transfer = BankTransfer::where('from_account', $bankAccount->id)->orwhere('to_account', $bankAccount->id)->get();
                if ($transfer->isNotEmpty()) {
                    return redirect()->route('bank-account.index')->with('error', __('Please delete related record of this account.'));
                }
                $revenue        = Revenue::where('account_id', $bankAccount->id)->first();
                $invoicePayment = InvoicePayment::where('account_id', $bankAccount->id)->first();
                $transaction    = Transaction::where('account', $bankAccount->id)->first();
                $payment        = Payment::where('account_id', $bankAccount->id)->first();
                $billPayment    = BillPayment::first();

                // Prevent deletion if any related record exists in revenue, invoice payments, transactions, payments, or bill payments
                if (!empty($revenue) || !empty($invoicePayment) || !empty($transaction) || !empty($payment) || !empty($billPayment)) {
                    return redirect()->route('bank-account.index')->with('error', __('Please delete related record of this account.'));
                } else {
                    $bankAccount->delete();
                    return redirect()->route('bank-account.index')->with('success', __('Account successfully deleted.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
