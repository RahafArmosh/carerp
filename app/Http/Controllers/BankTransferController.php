<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BankTransferController extends Controller
{

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage bank transfer')) {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $query = BankTransfer::where('created_by', '=', \Auth::user()->creatorId());

            if (count(explode('to', $request->date)) > 1) {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->date)) {
                $date_range = [$request->date, $request->date];
                $query->whereBetween('date', $date_range);
            }


            if (!empty($request->f_account)) {
                $query->where('from_account', '=', $request->f_account);
            }
            if (!empty($request->t_account)) {
                $query->where('to_account', '=', $request->t_account);
            }
            $transfers = $query->with(['fromBankAccount', 'toBankAccount'])->get();

            return view('bank-transfer.index', compact('transfers', 'account'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create bank transfer')) {
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currencies = Currency::get();
            return view('bank-transfer.create', compact('bankAccount','currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create bank transfer')) {
            try {
                DB::beginTransaction();
                // Get the latest 'vid' entry, if any exist
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                // Extract the vid value from the last record and increment it
                if ($latestVoucher) {
                    $lastVid = $latestVoucher->vid;
                    $newVoucherId = $lastVid + 1;
                } else {
                    // If no record exists, start with 1
                    $newVoucherId = 1;
                }
                $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

                if ($existingRecord) {
                    return redirect()->back()->with('error', __("something went wrong , please try again."));
                }

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'from_account' => 'required|numeric',
                        'to_account' => 'required|numeric',
                        'amount' => 'required|numeric',
                        'date' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $transfer                 = new BankTransfer();
                $transfer->from_account   = $request->from_account;
                $transfer->to_account     = $request->to_account;
                $transfer->amount         = $request->amount;
                $transfer->date           = $request->date;
                $transfer->payment_method = 0;
                $transfer->reference      = $request->reference;
                $transfer->description    = $request->description;
                $transfer->currency_id    = !empty($request->currency_id) ? $request->currency_id : null;
                $transfer->currency_rate    = !empty($request->currency_rate) ? $request->currency_rate : null;
                $transfer->created_by     = \Auth::user()->creatorId();
                $transfer->save();
                $fromAccount = BankAccount::findOrFail($request->from_account);
                $toAccount = BankAccount::findOrFail($request->to_account);
                $amount = $request->amount ;
                if (!empty($request->currency_id)) {
                    if(!empty($request->currency_rate)){
                        $amount= $request->amount * $request->currency_rate;
                    }
                    else{
                        $currancy = Currency::findOrFail($request->currency_id);
                        $amount = $amount * $currancy->currency_rate;
                    }
                } 
                //add to GL from account debit
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $newVoucherId;
                $debitEntry->account = $fromAccount->chart_account_id;
                $debitEntry->type = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $debitEntry->ref_number = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $debitEntry->debit = 0;
                $debitEntry->credit = $amount;
                $debitEntry->ref_id = $transfer->id;
                $debitEntry->user_id = 0;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->send_date = $request->date;
                $debitEntry->reference = 'Bank Transfer';
                $debitEntry->save();


                //add to GL to account credit
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $newVoucherId;
                $creditEntry->account = $toAccount->chart_account_id;
                $creditEntry->type = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $creditEntry->ref_number = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $creditEntry->debit =  $amount;
                $creditEntry->credit = 0;
                $creditEntry->ref_id = $transfer->id;
                $creditEntry->user_id = 0;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $request->date;
                $creditEntry->reference = 'Bank Transfer';
                $creditEntry->save();


                // Utility::bankAccountBalance($request->from_account, $amount, 'credit');

                // Utility::bankAccountBalance($request->to_account, $amount, 'debit');
                DB::commit();
                return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('bank-transfer.index');
    }

    public function print($id)
    {
        if (\Auth::user()->can('manage bank transfer')) {
            $transfer = BankTransfer::where('id', $id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['fromBankAccount', 'toBankAccount', 'currency'])
                ->firstOrFail();
            
            $settings = Utility::settings();
            
            return view('bank-transfer.print', compact('transfer', 'settings'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(BankTransfer $transfer, $id)
    {
        if (\Auth::user()->can('edit bank transfer')) {
            $transfer = BankTransfer::where('id', $id)->first();
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currencies = Currency::get();
            return view('bank-transfer.edit', compact('bankAccount', 'transfer','currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, BankTransfer $transfer, $id)
    {
        if (\Auth::user()->can('edit bank transfer')) {
            $transfer = BankTransfer::find($id);
            $validator = \Validator::make(
                $request->all(),
                [
                    'from_account' => 'required|numeric',
                    'to_account' => 'required|numeric',
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            // Utility::bankAccountBalance($transfer->from_account, $transfer->amount, 'debit');
            // Utility::bankAccountBalance($transfer->to_account, $transfer->amount, 'credit');

            $transfer->from_account   = $request->from_account;
            $transfer->to_account     = $request->to_account;
            $transfer->amount         = $request->amount;
            $transfer->date           = $request->date;
            $transfer->payment_method = 0;
            $transfer->reference      = $request->reference;
            $transfer->description    = $request->description;
            $transfer->currency_id    = !empty($request->currency_id) ? $request->currency_id : null;
            $transfer->currency_rate    = !empty($request->currency_rate) ? $request->currency_rate : null;
            $transfer->save();
            $amount = $request->amount ;
                if (!empty($request->currency_id)) {
                    if(!empty($request->currency_rate)){
                        $amount= $request->amount * $request->currency_rate;
                    }
                    else{
                        $currancy = Currency::findOrFail($request->currency_id);
                        $amount = $amount * $currancy->currency_rate;
                    }
                } 
            GeneralLedger::where('ref_id', $transfer->id)
            ->where('reference', 'like', 'Bank Transfer')
            ->where('created_by', \Auth::user()->creatorId())
            ->delete();

            $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
                // Extract the vid value from the last record and increment it
                if ($latestVoucher) {
                    $lastVid = $latestVoucher->vid;
                    $newVoucherId = $lastVid + 1;
                } else {
                    // If no record exists, start with 1
                    $newVoucherId = 1;
                }
                $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

                if ($existingRecord) {
                    return redirect()->back()->with('error', __("something went wrong , please try again."));
                }
                $fromAccount = BankAccount::findOrFail($request->from_account);
                $toAccount = BankAccount::findOrFail($request->to_account);
                //add to GL from account debit
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $newVoucherId;
                $debitEntry->account = $fromAccount->chart_account_id;
                $debitEntry->type = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $debitEntry->ref_number = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $debitEntry->debit = 0;
                $debitEntry->credit = $amount;
                $debitEntry->ref_id = $transfer->id;
                $debitEntry->user_id = 0;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->send_date = $request->date;
                $debitEntry->reference = 'Bank Transfer';
                $debitEntry->save();


                //add to GL to account credit
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $newVoucherId;
                $creditEntry->account = $toAccount->chart_account_id;
                $creditEntry->type = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $creditEntry->ref_number = 'Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                $creditEntry->debit = $amount;
                $creditEntry->credit = 0;
                $creditEntry->ref_id = $transfer->id;
                $creditEntry->user_id = 0;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $request->date;
                $creditEntry->reference = 'Bank Transfer';
                $creditEntry->save();

            // Utility::bankAccountBalance($request->from_account, $amount, 'credit');
            // Utility::bankAccountBalance($request->to_account, $amount, 'debit');

            return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Request $request)
    {

        if (\Auth::user()->can('delete bank transfer')) {
            $transfer = BankTransfer::findOrFail($request->transfer_id);
            $deleteDate = Carbon::parse($request->delete_date);
            $transferDate = Carbon::parse($transfer->date); 
            if ($transfer->created_by == \Auth::user()->creatorId()) {
                try {
                    if ($deleteDate->lt($transferDate)) {
                        return redirect()->back()->with('error', 'Delete date must be greater than or equal to the transfer date.');
                    }
                    DB::beginTransaction();
                    // Get the latest 'vid' entry, if any exist
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    // Extract the vid value from the last record and increment it
                    if ($latestVoucher) {
                        $lastVid = $latestVoucher->vid;
                        $newVoucherId = $lastVid + 1;
                    } else {
                        // If no record exists, start with 1
                        $newVoucherId = 1;
                    }
                    $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

                    if ($existingRecord) {
                        return redirect()->back()->with('error', __("something went wrong , please try again."));
                    }
                    $fromAccount = BankAccount::findOrFail($transfer->from_account);
                    $toAccount = BankAccount::findOrFail($transfer->to_account);
                    $amount = $transfer->amount ;
                    if (!empty($transfer->currency_id)) {
                        if(!empty($transfer->currency_rate)){
                            $amount= $transfer->amount * $transfer->currency_rate;
                        }
                        else{
                            $currancy = Currency::findOrFail($transfer->currency_id);
                            $amount = $amount * $currancy->currency_rate;
                        }
                    } 
                    //add to GL from account credit
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $newVoucherId;
                    $creditEntry->account = $fromAccount->chart_account_id;
                    $creditEntry->type = 'Delete Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                    $creditEntry->ref_number = 'Delete Bank Transfer from ' . $fromAccount->bank_name . ' to ' . $toAccount->bank_name;
                    $creditEntry->debit = $amount;
                    $creditEntry->credit = 0;
                    $creditEntry->ref_id = $transfer->id;
                    $creditEntry->user_id = 0;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = Carbon::now();
                    $creditEntry->reference = 'Delete Bank Transfer';
                    $creditEntry->save();


                    //add to GL to account debit
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $newVoucherId;
                    $debitEntry->account = $toAccount->chart_account_id;
                    $debitEntry->type = 'Delete Bank Transfer from ' . $fromAccount->bank_name . ' ' . $toAccount->bank_name;
                    $debitEntry->ref_number = 'Delete Bank Transfer from ' . $fromAccount->bank_name . ' ' . $toAccount->bank_name;
                    $debitEntry->debit = 0;
                    $debitEntry->credit = $amount;
                    $debitEntry->ref_id = $transfer->id;
                    $debitEntry->user_id = 0;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->send_date = Carbon::now();
                    $debitEntry->reference = 'Delete Bank Transfer';
                    $debitEntry->save();


                    $transfer->delete();

                    // Utility::bankAccountBalance($transfer->from_account, $amount, 'debit');
                    // Utility::bankAccountBalance($transfer->to_account, $amount, 'credit');
                    DB::commit();
                    return redirect()->route('bank-transfer.index')->with('success', __('Amount transfer successfully deleted.'));
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', $e->getMessage());
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
