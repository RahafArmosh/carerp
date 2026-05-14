<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\DebitNote;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\Vender;
use App\Models\Currency;
use App\Models\ChartOfAccount;
use App\Models\TransactionLines;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (\Auth::user()->can('manage debit note')) {
            $bills = Bill::where('created_by', \Auth::user()->creatorId())->get();

            return view('debitNote.index', compact('bills'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($bill_id)
    {
        if (\Auth::user()->can('create debit note')) {

            $billDue = Bill::where('id', $bill_id)->first();
            $bill_no = $billDue->id;
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            $currency_symbol = $billDue->currency ? $billDue->currency->symbol : \Auth::user()->currencySymbol();
            return view('debitNote.create', compact('billDue', 'bill_id', 'chartAccounts','bill_no','currencies','currency_symbol'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request, $bill_id)
    {

        if (\Auth::user()->can('create debit note')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $billDue = Bill::where('id', $bill_id)->first();

            if ($request->amount > $billDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($billDue->getDue()) . ' credit limit of this bill.');
            }
            $bill               = Bill::where('id', $bill_id)->first();
            $debit              = new DebitNote();
            $debit->bill        = $bill_id;
            $debit->vendor      = $bill->vender_id;
            $debit->date        = $request->date;
            $debit->amount      = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $debit->currency_id      = $request->currency_id ;
            $debit->currency_rate      = $request->currency_rate ;
            $debit->amount_in_currency      = $request->amount_in_currency ;                      
            $debit->description = $request->description;
            $debit->account_id = $request->account_id;
            $debit->save();

            Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');

            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $vendor = Vender::find($bill->vender_id);

            // Create a new entry for credit to Vendor account
            $vendorAccountId = $vendor->chart_account_id;
            $vendorEntry = new GeneralLedger();
            $vendorEntry->vid = $newVoucherId;
            $vendorEntry->account = $vendorAccountId;
            $vendorEntry->type = "Debit Note For " . \Auth::user()->billNumberFormat($bill->bill_id);
            $vendorEntry->debit = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $vendorEntry->credit = 0;
            $vendorEntry->ref_id = $debit->id;
            $vendorEntry->user_id = $vendor->id;
            $vendorEntry->created_by = \Auth::user()->creatorId();
            $vendorEntry->balance = $vendor->balance;
            $vendorEntry->send_date = $request->date;
            $vendorEntry->reference = 'Debit Note';
            $vendorEntry->save();

            $AccountEntry = new GeneralLedger();
            $AccountEntry->vid = $newVoucherId;
            $AccountEntry->account = $request->account_id;
            $AccountEntry->type = "Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $AccountEntry->debit = 0;
            $AccountEntry->credit = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $AccountEntry->ref_id = $debit->id;
            $AccountEntry->user_id = 0;
            $AccountEntry->created_by = \Auth::user()->creatorId();
            $AccountEntry->balance = 0;
            $AccountEntry->send_date = $request->date;
            $AccountEntry->reference = 'Debit Note';
            $AccountEntry->save();
            return redirect()->back()->with('success', __('Debit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit($bill_id, $debitNote_id)
    {
        if (\Auth::user()->can('edit debit note')) {

            $debitNote = DebitNote::find($debitNote_id);
            $chartAccounts =  ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
            ->where('created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            $bill = Bill::find($debitNote->bill);
            $currency_symbol = $bill->currency ? $bill->currency->symbol : \Auth::user()->currencySymbol();
            return view('debitNote.edit', compact('debitNote','chartAccounts','currencies','currency_symbol'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, $bill_id, $debitNote_id)
    {

        if (\Auth::user()->can('edit debit note')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $billDue = Bill::where('id', $bill_id)->first();
            if ($request->amount > $billDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($billDue->getDue()) . ' credit limit of this bill.');
            }


            $debit = DebitNote::find($debitNote_id);
            //            Utility::userBalance('vendor', $billDue->vender_id, $debit->amount, 'credit');
            Utility::updateUserBalance('vendor', $billDue->vender_id, $debit->amount, 'debit');



            $debit->date        = $request->date;
            $debit->amount      = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $debit->currency_id      = $request->currency_id ;
            $debit->currency_rate      = $request->currency_rate ;
            $debit->amount_in_currency      = $request->amount_in_currency ;    
            $debit->description = $request->description;
            $debit->account_id = $request->account_id;
            $debit->save();
            //            Utility::userBalance('vendor', $billDue->vender_id, $request->amount, 'debit');
            Utility::updateUserBalance('vendor', $billDue->vender_id, $request->amount, 'credit');
            $latestVoucher = GeneralLedger::where('ref_id', $debit->id)
                ->where(function ($query) {
                    $query->where('type', 'LIKE', '%Debit Note%');
                })
                ->where('created_by',\Auth::user()->creatorId())
                ->first()->vid;
            GeneralLedger::where('ref_id', $debit->id)
                ->where(function ($query) {
                    $query->where('type', 'LIKE', '%Debit Note%');
                })
                ->where('created_by',\Auth::user()->creatorId())->delete();
             $vendor = Vender::find($billDue->vender_id);

            // Create a new entry for credit to Vendor account
            $vendorAccountId = $vendor->chart_account_id;
            $vendorEntry = new GeneralLedger();
            $vendorEntry->vid = $latestVoucher;
            $vendorEntry->account = $vendorAccountId;
            $vendorEntry->type = "Debit Note For " . \Auth::user()->billNumberFormat($billDue->bill_id);
            $vendorEntry->debit = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $vendorEntry->credit = 0;
            $vendorEntry->ref_id = $debit->id;
            $vendorEntry->user_id = $vendor->id;
            $vendorEntry->created_by = \Auth::user()->creatorId();
            $vendorEntry->balance = $vendor->balance;
            $vendorEntry->send_date = $request->date;
            $vendorEntry->reference = 'Debit Note';
            $vendorEntry->save();

            $AccountEntry = new GeneralLedger();
            $AccountEntry->vid = $latestVoucher;
            $AccountEntry->account = $request->account_id;
            $AccountEntry->type = "Debit Note For ".\Auth::user()->billNumberFormat($billDue->bill_id);
            $AccountEntry->debit = 0;
            $AccountEntry->credit = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
            $AccountEntry->ref_id = $debit->id;
            $AccountEntry->user_id = 0;
            $AccountEntry->created_by = \Auth::user()->creatorId();
            $AccountEntry->balance = 0;
            $AccountEntry->send_date = $request->date;
            $AccountEntry->reference = 'Debit Note';
            $AccountEntry->save();

            return redirect()->back()->with('success', __('Debit Note successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy($bill_id, $debitNote_id)
    {
        if (\Auth::user()->can('delete debit note')) {
            $debitNote = DebitNote::find($debitNote_id);
            $debitNote->delete();
            Utility::updateUserBalance('vendor', $debitNote->vendor, $debitNote->amount, 'debit');
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $bill = Bill::where('id', $bill_id)->first();
            $vendor = Vender::find($bill->vender_id);
            TransactionLines::where('reference_id', $debitNote->id)->where('reference', 'Debit Note')->delete();
            // Create a new entry for credit to Vendor account
            $vendorAccountId = $vendor->chart_account_id;
            $vendorEntry = new GeneralLedger();
            $vendorEntry->vid = $newVoucherId;
            $vendorEntry->account = $vendorAccountId;
            $vendorEntry->type = "Delete Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $vendorEntry->ref_number = "Delete Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $vendorEntry->debit = 0;
            $vendorEntry->credit = $debitNote->amount;
            $vendorEntry->ref_id = $debitNote->id;
            $vendorEntry->user_id = $vendor->id;
            $vendorEntry->created_by = \Auth::user()->creatorId();
            $vendorEntry->balance = $vendor->balance;
            $vendorEntry->send_date = $debitNote->date;
            $vendorEntry->reference = 'Delete Debit Note';
            $vendorEntry->save();


            $accountEntry = new GeneralLedger();
            $accountEntry->vid = $newVoucherId;
            $accountEntry->account = $debitNote->account_id;
            $accountEntry->type = "Delete Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $accountEntry->ref_number = "Delete Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $accountEntry->debit = $debitNote->amount;
            $accountEntry->credit = 0;
            $accountEntry->ref_id = $debitNote->id;
            $accountEntry->user_id = 0;
            $accountEntry->created_by = \Auth::user()->creatorId();
            $accountEntry->balance = 0;
            $accountEntry->send_date = $debitNote->date;
            $accountEntry->reference = 'Delete Debit Note';
            $accountEntry->save();
            return redirect()->back()->with('success', __('Debit Note successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if (\Auth::user()->can('create debit note')) {
            $bills = Bill::where('created_by', \Auth::user()->creatorId())->wherein('status',[4,6])->where('type', 'Bill')->get();
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('debitNote.custom_create', compact('bills','chartAccounts','currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        if (\Auth::user()->can('create debit note')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'bill' => 'required|numeric',
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $bill_id = $request->bill;
            $billDue = Bill::where('id', $bill_id)->first();

            if ($request->amount > $billDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($billDue->getDue()) . ' credit limit of this bill.');
            }
            $bill               = Bill::where('id', $bill_id)->first();
            $debit              = new DebitNote();
            $debit->bill        = $bill_id;
            $debit->vendor      = $bill->vender_id;
            $debit->date        = $request->date;
            $debit->amount      = !empty($request->currency_id) ? $request->amount * $request->currency_rate : $request->amount;
            $debit->currency_id      = $request->currency_id;
            $debit->currency_rate      = $request->currency_rate;
            $debit->amount_in_currency      = $request->amount_in_currency;
            $debit->description = $request->description;
            $debit->account_id = $request->account_id;
            $debit->save();
            //            Utility::userBalance('vendor', $bill->vender_id, $request->amount, 'debit');
            Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $vendor = Vender::find($bill->vender_id);
            // Create a new entry for credit to Vendor account
            $vendorAccountId = $vendor->chart_account_id;
            $vendorEntry = new GeneralLedger();
            $vendorEntry->vid = $newVoucherId;
            $vendorEntry->account = $vendorAccountId;
            $vendorEntry->type = "Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $vendorEntry->ref_number = "Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $vendorEntry->debit = $debit->amount;
            $vendorEntry->credit = 0;
            $vendorEntry->ref_id = $debit->id;
            $vendorEntry->user_id = $vendor->id;
            $vendorEntry->created_by = \Auth::user()->creatorId();
            $vendorEntry->balance = $vendor->balance;
            $vendorEntry->send_date = $request->date;
            $vendorEntry->reference = 'Debit Note';
            $vendorEntry->save();

            $AccountEntry = new GeneralLedger();
            $AccountEntry->vid = $newVoucherId;
            $AccountEntry->account = $request->account_id;
            $AccountEntry->type = "Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $AccountEntry->ref_number = "Debit Note For ".\Auth::user()->billNumberFormat($bill->bill_id);
            $AccountEntry->debit = 0;
            $AccountEntry->credit = $debit->amount;
            $AccountEntry->ref_id = $debit->id;
            $AccountEntry->user_id = 0;
            $AccountEntry->created_by = \Auth::user()->creatorId();
            $AccountEntry->balance = 0;
            $AccountEntry->send_date = $request->date;
            $AccountEntry->reference = 'Debit Note';
            $AccountEntry->save();

            return redirect()->back()->with('success', __('Debit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getbill(Request $request)
    {

        $bill = Bill::where('id', $request->bill_id)->first();
        echo json_encode($bill->getDue());
    }
}
