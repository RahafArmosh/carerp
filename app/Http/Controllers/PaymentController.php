<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\GeneralLedger;
use App\Models\Bill;
use App\Models\BillStatusChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use \Carbon\Carbon;
use App\Models\Currency;
use PDF;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage payment')) {
            $creatorId = \Auth::user()->creatorId();
            
            // Optimize dropdown queries - use select to limit columns
            $vender = Vender::where('created_by', '=', $creatorId)
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $account = BankAccount::where('created_by', '=', $creatorId)
                ->select('id', 'holder_name')
                ->get()
                ->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', $creatorId)
                ->select('id', 'name')
                ->get()
                ->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $status = array_filter(Payment::$statues);
            
            // Build query with select to limit columns loaded
            $query = Payment::where('created_by', '=', $creatorId)
                ->select([
                    'id', 'date', 'amount', 'account_id', 'vender_id', 'category_id',
                    'reference', 'description', 'status', 'currency_id', 'currency_rate',
                    'amount_in_currency', 'bill_id'
                ]);
            
            // Optimize date filter - use whereBetween for range queries
            if (!empty($request->date)) {
                if (strpos($request->date, ' to ') !== false) {
                    $date_range = explode(' to ', $request->date);
                    if (count($date_range) == 2) {
                        $query->whereBetween('date', [trim($date_range[0]), trim($date_range[1])]);
                    }
                } else {
                    $query->whereDate('date', $request->date);
                }
            }

            if (!empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }
            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            // Optimize eager loading with select statements to limit columns
            $payments = $query->with([
                'bankAccount:id,bank_name,holder_name', // account_id is foreign key, not needed in select
                'vender:id,name', // vender_id is foreign key
                'category:id,name', // category_id is foreign key
                'currency:id,name,symbol', // currency_id is foreign key
                'billPayments:id,payment_id,amount', // Only load needed columns
                'bill' => function($query) {
                    $query->select('id', 'bill_id', 'type', 'currency_id')
                          ->with('currency:id,symbol');
                },
                'bills' => function($query) {
                    // Only select needed columns from bills
                    $query->select('bills.id', 'bills.bill_id', 'bills.type', 'bills.currency_id')
                          ->with('currency:id,symbol');
                }
            ])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc') // Secondary sort for consistent pagination
            ->paginate(50);

            return view('payment.index', compact('payments', 'account', 'category', 'vender', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create payment')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);

            //            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 2)->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('payment.create', compact('venders', 'categories', 'accounts', 'chartAccounts', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create payment')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    'category_id' => 'required',
                    'vender_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            try {
                DB::beginTransaction();
                $vender = Vender::where('id', $request->vendor_id)->first();

                $payment = new Payment();
                $payment->date = $request->date;
                $payment->amount = !empty($request->currency_id) ? $request->amount * $request->currency_rate : $request->amount;
                $payment->account_id = $request->account_id;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                // $payment->amount_in_currency = $request->amount_in_currency;
                $payment->vender_id = $request->vender_id;
                $payment->category_id = $request->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;
                $payment->status = 0;

                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $payment->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/payment'), $fileName);
                }
                $payment->description = $request->description;
                $payment->created_by = \Auth::user()->creatorId();
                $payment->payment_number = Payment::nextPaymentNumberFor($payment->created_by);
                $payment->save();
                $chartAccountId = ChartOfAccount::find($request->account_id);
                // if (!empty($request->bill_id)) {
                //     $payment->bill_id = $request->bill_id;
                //     $payment->save();
                //     $bill  = Bill::where('id', $request->bill_id)->first();
                //     $due   = $bill->getDue() - !empty($request->currency_id) ? $request->amount * $request->currency_rate : $request->amount;
                //     if ($due <= 0) {
                //         $bill->payment_status = 4;
                //         $bill->save();
                //         $statusChange = new BillStatusChange();
                //         $statusChange->bill_id = $bill->id;
                //         $statusChange->status = -1;
                //         $statusChange->payment_status = 4;
                //         $statusChange->changed_at = now();
                //         $statusChange->save();
                //     } else {
                //         $bill->payment_status = 2;
                //         $bill->save();
                //         $statusChange = new BillStatusChange();
                //         $statusChange->bill_id = $bill->id;
                //         $statusChange->status = -1;
                //         $statusChange->payment_status = 2;
                //         $statusChange->changed_at = now();
                //         $statusChange->save();
                //     }
                //     $account = new BillAccount();
                //     $account->chart_account_id = $chartAccountId;
                //     $account->price = $request->amount;
                //     $account->description = $request->description;
                //     $account->type = 'Payment';
                //     $account->ref_id = $payment->id;
                //     $account->save();
                //     $billPayment                 = new BillPayment();
                //     $billPayment->bill_id        = $request->bill_id;
                //     $billPayment->date           = $request->date;
                //     $billPayment->amount         = !empty($request->currency_id) ? $request->amount * $request->currency_rate : $request->amount;
                //     $billPayment->account_id     = $request->account_id;
                //     $billPayment->currency_id     = $request->currency_id;
                //     $billPayment->currency_rate     = $request->currency_rate;
                //     $billPayment->amount_in_currency     = $request->amount_in_currency;
                //     $billPayment->payment_method = 0;
                //     $billPayment->reference      = $request->reference;
                //     $billPayment->description    = $request->description;
                //     if (!empty($request->add_receipt)) {
                //         $billPayment->add_receipt = $fileName;
                //     }
                //     $billPayment->save();
                //     $payment->payment_id = $billPayment->id;
                //     $payment->save();
                // }
                //For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());

                //Twilio Notification
                if (isset($setting['twilio_payment_notification']) && $setting['twilio_payment_notification'] == 1) {

                    $vender = Vender::find($request->vender_id);
                    $paymentNotificationArr = [
                        'payment_amount' => \Auth::user()->priceFormat($request->amount),
                        'vendor_name' => $vender->name,
                        'payment_type' => 'Payment',
                    ];
                    // Utility::send_twilio_msg($request->contact, 'bill_payment', $paymentNotificationArr);
                }



                DB::commit();
                return redirect()->route('payment.index')->with('success', __('Payment successfully created') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Payment $payment)
    {

        if (\Auth::user()->can('edit payment')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);

            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            // $bills =  Bill::where('vender_id', $payment->vender_id)->where("payment_status", "!=", "4")->get();
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('payment.edit', compact('venders', 'categories', 'accounts', 'payment', 'chartAccounts', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Payment $payment)
    {
        $old_amount = $payment->amount;
        if (\Auth::user()->can('edit payment')) {
            try {
                DB::beginTransaction();
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'amount' => 'required',
                        'account_id' => 'required',
                        'category_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $vender = Vender::where('id', $request->old_vender_id)->first();
                $payment->date = $request->date;
                $payment->amount = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                // $payment->amount_in_currency     = $request->amount_in_currency;
                $payment->account_id = $request->account_id;
                $payment->vender_id = $request->old_vender_id;
                $payment->category_id = $request->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;

                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $payment->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/payment'), $fileName);
                }

                $payment->description = $request->description;
                $payment->save();
                if ($payment->status === 2) {
                    if (!empty($vender)) {
                        // Utility::userBalance('vendor', $vender->id, $payment->amount, 'credit');
                        Utility::updateUserBalance('vendor', $vender->id, $payment->amount, 'credit');
                        $vender->total_paid = $vender->total_paid - $payment->amount;
                    }
                    // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');
                    TransactionLines::where('reference_id', $payment->id)->where('reference', 'Payment')->delete();
                    $accountId = BankAccount::find($payment->account_id);
                    $newVoucherId = GeneralLedger::where('payment_id', $payment->id)
                        ->where('reference', 'LIKE', '%Payment%')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first()->vid;
                    GeneralLedger::where('payment_id', $payment->id)
                        ->where('reference', 'LIKE', '%Payment%')
                        ->where('created_by', \Auth::user()->creatorId())->delete();
                    // Create a new entry for debit to vendor account
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $newVoucherId;
                    $debitEntry->account = $vender->chart_account_id;
                    $debitEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                    $debitEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                    $debitEntry->debit = $payment->amount;
                    $debitEntry->credit = 0;
                    $debitEntry->ref_id = -1;
                    $debitEntry->user_id = $vender->id;
                    $debitEntry->payment_id = $payment->id;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->balance = $vender->balance;
                    $debitEntry->send_date = $payment->date;
                    $debitEntry->reference = 'Payment';
                    $debitEntry->save();


                    // Create a new entry for credit to payment account
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $newVoucherId;
                    $creditEntry->account = $accountId->chart_account_id;
                    $creditEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->debit = 0;
                    $creditEntry->credit = $payment->amount;
                    $creditEntry->ref_id = -1;
                    $creditEntry->user_id = 0;
                    $creditEntry->payment_id = $payment->id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = $payment->date;
                    $creditEntry->reference = 'Payment';
                    $creditEntry->save();
                    if (!empty($vender)) {
                        Utility::updateUserBalance('vendor', $vender->id, $old_amount, 'credit');
                        Utility::updateUserBalance('vendor', $vender->id, $request->amount, 'debit');
                        $vender->total_paid = $vender->total_paid + $request->amount;
                    }

                    // Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');
                }
                DB::commit();
                return redirect()->route('payment.index')->with('success', __('Payment Updated Successfully') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Request $request, Payment $payment)
    {
        if (\Auth::user()->can('delete payment')) {
            if ($payment->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    if (!empty($payment->add_receipt)) {
                        //storage limit
                        $file_path = '/uploads/payment/' . $payment->add_receipt;
                        $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    }
                    $deleteDate = Carbon::parse($request->input('delete_date'));
                    $voucherEntry = GeneralLedger::where('payment_id', $payment->id)
                        ->where('type', 'LIKE', '%Payment%')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();
                    $VoucherDate = $voucherEntry ? Carbon::parse($voucherEntry->send_date) : null;
                    if ($VoucherDate && $deleteDate->lt($VoucherDate)) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Delete date must be greater than or equal to the payment date.');
                    }
                    $payment->delete();
                    $type = 'Payment';
                    $user = 'Vender';
                    if ($payment->status === 2) {
                        TransactionLines::where('reference_id', $payment->id)->where('reference', 'Payment')->delete();
                        Transaction::destroyTransaction($payment->id, $type, $user);

                        if ($payment->vender_id != 0) {
                            // Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                            Utility::updateUserBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                        }
                        // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');
                        // Add to General Ledger
                        // Get the latest 'vid' entry, if any exist
                        $vender = Vender::where('id', $payment->vender_id)->first();
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
                            DB::rollBack();
                            return redirect()->back()->with('error', __("something went wrong , please try again."));
                        }
                        $accountId = BankAccount::find($payment->account_id);
                        // Create a new entry for debit to payment account
                        $debitEntry = new GeneralLedger();
                        $debitEntry->vid = $newVoucherId;
                        $debitEntry->account = $accountId->chart_account_id;
                        $debitEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->debit = $payment->amount; // Example value
                        $debitEntry->credit = 0; // Example value
                        $debitEntry->ref_id = -1;
                        $debitEntry->user_id = 0;
                        $debitEntry->created_by = \Auth::user()->creatorId();
                        $debitEntry->send_date = $deleteDate;
                        $debitEntry->payment_id = $payment->id;
                        $debitEntry->reference = 'Delete Payment';
                        $debitEntry->save();


                        // Create a new entry for credit to vendor account
                        $creditEntry = new GeneralLedger();
                        $creditEntry->vid = $newVoucherId;
                        $creditEntry->account = $vender->chart_account_id;
                        $creditEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                        $creditEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
                        $creditEntry->debit = 0; // Example value
                        $creditEntry->credit = $payment->amount; // Example value
                        $creditEntry->ref_id = -1;
                        $creditEntry->user_id = $payment->vender_id;
                        $creditEntry->created_by = \Auth::user()->creatorId();
                        $creditEntry->balance = $vender->balance;
                        $creditEntry->send_date = $deleteDate;
                        $creditEntry->payment_id = $payment->id;
                        $creditEntry->reference = 'Delete Payment';
                        $creditEntry->save();
                    }
                    DB::commit();
                    return redirect()->route('payment.index')->with('success', __('Payment successfully deleted.'));
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

    public function printvendorpayment($paymentId)
    {
        $payment = Payment::with(['vender', 'bankAccount', 'category', 'currency', 'billPayments.bill.currency'])
            ->findOrFail($paymentId);
        $settings = Utility::settings();
        $settings_data = Utility::settingsById($payment->created_by);
        $bill_logo = $settings_data['bill_logo'] ?? null;
        $logo_path = $bill_logo
            ? Utility::get_file('bill_logo/').$bill_logo
            : asset(Storage::url('uploads/logo/'.($settings['company_logo_dark'] ?? 'logo-dark.png')));

        $companyLogoFile = null;
        if (($settings['cust_darklayout'] ?? '') === 'on') {
            $companyLogoFile = ! empty($settings['company_logo_light'])
                ? $settings['company_logo_light']
                : ($settings['company_logo_dark'] ?? null);
        } else {
            $companyLogoFile = ! empty($settings['company_logo_dark'])
                ? $settings['company_logo_dark']
                : ($settings['company_logo_light'] ?? null);
        }

        $base = rtrim(URL::to('/'), '/');
        $company_logo_url = null;
        if (! empty($companyLogoFile)) {
            $company_logo_url = $base.'/documents/'.$companyLogoFile;
        }
        if (empty($company_logo_url) && ! empty($settings['company_logo_dark'])) {
            $company_logo_url = asset(Storage::url('uploads/logo/'.$settings['company_logo_dark']));
        }
        if (empty($company_logo_url)) {
            $company_logo_url = asset(Storage::url('uploads/logo/'.($settings['company_logo_dark'] ?? 'logo-dark.png')));
        }

        $company_stamp_url = ! empty($settings_data['company_stamp'])
            ? $base.'/documents/'.$settings_data['company_stamp']
            : asset('storage/uploads/logo/stamp-preview.png');

        $color = '#'.($settings['bill_color'] ?? 'ffffff');
        $font_color = Utility::getFontColor($color);

        return view('payment.print', compact(
            'payment',
            'font_color',
            'color',
            'settings',
            'settings_data',
            'logo_path',
            'company_logo_url',
            'company_stamp_url'
        ));
    }
    public function sendpayment(Request $request, $paymentId)
    {
        $payment = Payment::find($paymentId);
        $vender = Vender::where('id', $payment->vender_id)->first();
        $accountId = BankAccount::find($payment->account_id);
        $sendDate = $request->query('send_date');
        try {
            DB::beginTransaction();
            $payment->status = 2;
            $payment->save();
            $vender->total_paid = $vender->total_paid + $payment->amount;
            $vender->save();
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }
            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

            if ($existingRecord) {
                DB::rollBack();
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            if($accountId->chart_account_id === 0){
                DB::rollBack();
                return redirect()->back()->with('error', __("something went wrong , Bank chart of account is null"));
            }
            // Create a new entry for debit to vendor account
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $newVoucherId;
            $debitEntry->account = $vender->chart_account_id;
            $debitEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
            $debitEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
            $debitEntry->debit = $payment->amount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = -1;
            $debitEntry->user_id = $vender->id;
            $debitEntry->payment_id = $payment->id;
            $debitEntry->created_by = \Auth::user()->creatorId();
            $debitEntry->balance = $vender->balance;
            $debitEntry->send_date = $sendDate;
            $debitEntry->reference = 'Payment';
            $debitEntry->save();
            
            if($accountId->chart_account_id == 0){
                DB::rollBack();
                return redirect()->back()->with('error', __('Account not found'));
            }
            // Create a new entry for credit to payment account
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $accountId->chart_account_id;
            $creditEntry->type = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->ref_number = \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->debit = 0;
            $creditEntry->credit = $payment->amount;
            $creditEntry->ref_id = -1;
            $creditEntry->user_id = 0;
            $creditEntry->payment_id = $payment->id;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference = 'Payment';
            $creditEntry->save();
            if (!empty($vender)) {
                // Utility::userBalance('vendor', $vender->id, $request->amount, 'debit');
                Utility::updateUserBalance('vendor', $vender->id, $payment->amount, 'debit');
            }

            // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

            DB::commit();
            return  redirect()->back()->with('success', __('Payment successfully Received'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getAverageRate($currency_id)
    {
        $rate = DB::table('bill_payments')
            ->where('currency_id', $currency_id)
            ->whereNotNull('amount')
            ->whereNotNull('amount_in_currency')
            ->selectRaw('SUM(amount) as total_amount, SUM(amount_in_currency) as total_currency_amount')
            ->first();

        if ($rate && $rate->total_currency_amount > 0) {
            $avgRate = $rate->total_amount / $rate->total_currency_amount;
            return response()->json(['rate' => round($avgRate, 6)]);
        }

        return response()->json(['rate' => null], 404);
    }
    public function allocateForm($id)
    {
        $payment = Payment::findOrFail($id);
        $bills = Bill::where('vender_id', $payment->vender_id)
                    ->where('payment_status','!=',4)
                    ->get();

        return view('payment.allocate', compact('payment', 'bills'));
    }

    public function storeAllocation(Request $request, $paymentId)
{
    try {
        DB::beginTransaction();
    $payment = Payment::findOrFail($paymentId);
    $allocations = $request->input('allocations', []);

    // Sum of this form's allocations
    $newAllocationTotal = collect($request->input('allocations', []))
    ->map(fn($data) => isset($data['amount']) ? floatval($data['amount']) : 0)
    ->sum();

    // Already allocated before this form
    $alreadyAllocated = $payment->billPayments()->sum('amount');
    $totalToAllocate = $alreadyAllocated + $newAllocationTotal;

    if ($totalToAllocate > $payment->amount) {
        return redirect()->back()->with('error', __('Allocation exceeds total payment amount.'));
    }

    foreach ($request->input('allocations', []) as $billId => $data) {
        $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
        $amount_in_currency = isset($data['amount_in_currency']) ? floatval($data['amount_in_currency']) : $data['amount'];
        $bill = Bill::find($billId);
        if (!$bill) {
            continue;
        }

        $billDue = $bill->getDue();

        if ($amount > $billDue) {
            return redirect()->back()->with('error', __('Allocation amount exceeds due for bill :number', ['number' => $bill->bill_id]));
        }

        if ($amount > 0) {
                $billPayment = new BillPayment();
                $billPayment->bill_id = $bill->id;
                $billPayment->date = $payment->date;
                $billPayment->amount =  $amount ;
                $billPayment->account_id = $payment->account_id;
                $billPayment->currency_id = Currency::where('code', 'AED')->first()->id;
                $billPayment->currency_rate = Currency::where('code', 'AED')->first()->exchange_rate;
                $billPayment->amount_in_currency = $amount_in_currency ;
                $billPayment->payment_method = 0;
                $billPayment->reference = $payment->reference;
                $billPayment->description = $payment->description;
                $billPayment->add_receipt = $payment->add_receipt;
                $billPayment->payment_id = $payment->id;
                $billPayment->save();
        }

        // Refresh the bill to get updated due
        $bill->refresh();
        $due = $bill->getDue();
        $total = $bill->getTotal();

        // Update bill payment status
        if ($due <= 0) {
            $bill->payment_status = 4; // Paid
            $bill->save();

            $ispaid = BillStatusChange::where('bill_id', $bill->id)->where('payment_status', 4)->first();
            if ($ispaid) {
                $ispaid->changed_at = now();
                $ispaid->save();
            } else {
                BillStatusChange::create([
                    'bill_id' => $bill->id,
                    'status' => -1,
                    'payment_status' => 4,
                    'changed_at' => now(),
                ]);
            }
        } else {
            $bill->payment_status = 2; // Partially paid
            $bill->save();

            $ishalfpaid = BillStatusChange::where('bill_id', $bill->id)->where('payment_status', 2)->first();
            if ($ishalfpaid) {
                $ishalfpaid->changed_at = now();
                $ishalfpaid->save();
            } else {
                BillStatusChange::create([
                    'bill_id' => $bill->id,
                    'status' => -1,
                    'payment_status' => 2,
                    'changed_at' => now(),
                ]);
            }
        }
    }
    DB::commit();
    return redirect()->back()->with('success', __('Payment allocated successfully.'));
    } catch (\Exception $e) {
        DB::rollBack();
    return redirect()->back()->with('error', $e->getMessage());
                }
            }
    public function convert(Request $request)
    {
        $from = Currency::findOrFail($request->query('from_id'))->code; // e.g. USD
        $to = Currency::findOrFail($request->query('to_id'))->code;   // e.g. EUR
        $amount = (float) $request->query('amount', 1);

        // Fetch rates for the source currency
        $response = Http::get("https://open.er-api.com/v6/latest/{$from}");

        if ($response->successful()) {
            $json = $response->json();

            if (isset($json['rates'][$to])) {
                $rate = (float) $json['rates'][$to];
                $convertedAmount = $rate * $amount;

                return response()->json([
                    'rate' => $rate,
                    'result' => round($convertedAmount, 2)
                ]);
            }

            return response()->json([
                'error' => "Currency rate for {$to} not found."
            ], 404);
        }

        return response()->json(['error' => $response->body()], 500);
    }


    public function convertAED(Request $request)
    {
        $from = Currency::findOrFail($request->query('from_id'))->code; // e.g. USD
        $to = $request->query('to_code'); // e.g. AED
        $amount = (float) $request->query('amount', 1);

        // API endpoint: https://open.er-api.com/v6/latest/{FROM}
        $response = Http::get("https://open.er-api.com/v6/latest/{$from}");

        if ($response->successful()) {
            $json = $response->json();

            if (isset($json['rates'][$to])) {
                $rate = (float) $json['rates'][$to];
                $convertedAmount = $rate * $amount;

                return response()->json([
                    'rate' => $rate,
                    'result' => round($convertedAmount, 2)
                ]);
            }

            return response()->json([
                'error' => "Currency rate for {$to} not found."
            ], 404);
        }

        return response()->json(['error' => $response->body()], 500);
    }

        
}