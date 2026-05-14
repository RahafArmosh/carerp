<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\InvoicePayment;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\TransactionLines;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RevenueController extends Controller
{

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage revenue')) {
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');


            $query = Revenue::where('created_by', '=', \Auth::user()->creatorId());


            if (count(explode('to', $request->date)) > 1) {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->date)) {
                $date_range = [$request->date, $request->date];
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }
            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }
            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            if (!empty($request->payment)) {
                $query->where('payment_method', '=', $request->payment);
            }

            $revenues = $query->with(['bankAccount', 'customer', 'category'])->get();

            return view('revenue.index', compact('revenues', 'customer', 'account', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {

        if (\Auth::user()->can('create revenue')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $categories = ProductServiceCategory::get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $projects = Project::get()->pluck('project_name', 'id');
            return view('revenue.create', compact('customers', 'categories', 'accounts', 'chartAccounts', 'projects'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create revenue')) {

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
            try {
                DB::beginTransaction();
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
                $revenue                 = new Revenue();
                $revenue->date           = $request->date;
                $revenue->amount         = $request->amount;
                $revenue->account_id     = $request->account_id;
                $revenue->revenue_account = $request->revenue_account;
                $revenue->project_id = $request->project_id != null ? $request->project_id: 0;
                $revenue->customer_id    = $request->customer_id;
                $revenue->category_id    = $request->category_id;
                $revenue->payment_method = 0;
                $revenue->reference      = $request->reference;
                $revenue->description    = $request->description;
                if (!empty($request->add_receipt)) {
                    //storage limit
                    $image_size = $request->file('add_receipt')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                    if ($result == 1) {
                        $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                        $revenue->add_receipt = $fileName;
                        $dir = 'uploads/revenue';
                        $url = '';
                        $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                        if ($path['flag'] == 0) {
                            return redirect()->back()->with('error', __($path['msg']));
                        }
                    }
                }


                $revenue->created_by     = \Auth::user()->creatorId();
                $revenue->save();

                $category            = ProductServiceCategory::where('id', $request->category_id)->first();
                $revenue->payment_id = $revenue->id;
                $revenue->type       = 'Revenue';
                $revenue->category   = $category->name;
                $revenue->user_id    = $revenue->customer_id;
                $revenue->user_type  = 'Customer';
                $revenue->account    = $request->account_id;
                // Transaction::addTransaction($revenue);

                // Add to General Ledger
                // Create a new entry for debit to cash account
                $AccountEntry = new GeneralLedger();
                $AccountEntry->vid = $newVoucherId;
                $AccountEntry->account = BankAccount::where('id', $request->account_id)->first()->chart_account_id;
                $AccountEntry->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $AccountEntry->debit = $request->amount;
                $AccountEntry->credit = 0;
                $AccountEntry->ref_id = $revenue->id;
                $AccountEntry->user_id = 0;
                $AccountEntry->created_by = \Auth::user()->creatorId();
                $AccountEntry->send_date = $request->date;
                $AccountEntry->reference = 'Revenue';
                $AccountEntry->save();

                $RevenueAccountEntry = new GeneralLedger();
                $RevenueAccountEntry->vid = $newVoucherId;
                $RevenueAccountEntry->account = $request->revenue_account;
                $RevenueAccountEntry->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $RevenueAccountEntry->debit = 0;
                $RevenueAccountEntry->credit = $request->amount;
                $RevenueAccountEntry->ref_id = $revenue->id;
                $RevenueAccountEntry->user_id = 0;
                $RevenueAccountEntry->created_by = \Auth::user()->creatorId();
                $RevenueAccountEntry->send_date = $request->date;
                $RevenueAccountEntry->reference = 'Revenue';
                $RevenueAccountEntry->save();

                $customer         = Customer::where('id', $request->customer_id)->first();
                $payment          = new InvoicePayment();
                $payment->name    = !empty($customer) ? $customer['name'] : '';
                $payment->date    = \Auth::user()->dateFormat($request->date);
                $payment->amount  = \Auth::user()->priceFormat($request->amount);
                $payment->invoice = '';

                if (!empty($customer)) {
                    Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
                    Utility::userBalance('customer', $customer->id, $revenue->amount, 'debit');
                }

                // Utility::bankAccountBalance($request->account_id, $revenue->amount, 'credit');

                $accountId = BankAccount::find($revenue->account_id);

                $CustomerEntryC = new GeneralLedger();
                $CustomerEntryC->vid = $newVoucherId;
                $CustomerEntryC->account = $customer->chartAccount->id;
                $CustomerEntryC->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $CustomerEntryC->debit = 0;
                $CustomerEntryC->credit = $request->amount;
                $CustomerEntryC->ref_id = $revenue->id;
                $CustomerEntryC->user_id = $customer->id;
                $CustomerEntryC->created_by = \Auth::user()->creatorId();
                $CustomerEntryC->send_date = $request->date;
                $CustomerEntryC->reference = 'Revenue';
                $CustomerEntryC->save();

                $CustomerEntryD = new GeneralLedger();
                $CustomerEntryD->vid = $newVoucherId;
                $CustomerEntryD->account = $customer->chartAccount->id;
                $CustomerEntryD->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $CustomerEntryD->debit = $request->amount;
                $CustomerEntryD->credit = 0;
                $CustomerEntryD->ref_id = $revenue->id;
                $CustomerEntryD->user_id = $customer->id;
                $CustomerEntryD->created_by = \Auth::user()->creatorId();
                $CustomerEntryD->send_date = $request->date;
                $CustomerEntryD->reference = 'Revenue';
                $CustomerEntryD->save();



                //For Notification
                $setting  = Utility::settings(\Auth::user()->creatorId());
                $revenueNotificationArr = [
                    'revenue_amount' => \Auth::user()->priceFormat($request->amount),
                    'customer_name' => !empty($customer) ? $customer->name : '-',
                    'user_name' => \Auth::user()->name,
                    'revenue_date' => $request->date,
                ];
                //Slack Notification
                if (isset($setting['revenue_notification']) && $setting['revenue_notification'] == 1) {
                    Utility::send_slack_msg('new_revenue', $revenueNotificationArr);
                }
                //Telegram Notification
                if (isset($setting['telegram_revenue_notification']) && $setting['telegram_revenue_notification'] == 1) {
                    Utility::send_telegram_msg('new_revenue', $revenueNotificationArr);
                }
                //Twilio Notification
                if (isset($setting['twilio_revenue_notification']) && $setting['twilio_revenue_notification'] == 1) {
                    Utility::send_twilio_msg(!empty($customer) ? $customer->contact : '-', 'new_revenue', $revenueNotificationArr);
                }


                //webhook
                $module = 'New Revenue';
                $webhook =  Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($revenue);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->route('revenue.index')->with('success', __('Revenue successfully created.'));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }
                DB::commit();
                return redirect()->route('revenue.index')->with('success', __('Revenue successfully created') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
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
        return redirect()->route('revenue.index');
    }


    public function edit(Revenue $revenue)
    {
        if (\Auth::user()->can('edit revenue')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            return view('revenue.edit', compact('customers', 'categories', 'accounts', 'revenue', 'chartAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, Revenue $revenue)
    {

        if (\Auth::user()->can('edit revenue')) {

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

            $customer = Customer::where('id', $request->customer_id)->first();
            if (!empty($customer)) {
                Utility::userBalance('customer', $customer->id, $revenue->amount, 'debit');
            }

            // Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');

            $revenue->date           = $request->date;
            $revenue->amount         = $request->amount;
            $revenue->account_id     = $request->account_id;
            $revenue->revenue_account     = $request->revenue_account;
            $revenue->project_id     = $request->project_id;
            $revenue->customer_id    = $request->customer_id;
            $revenue->category_id    = $request->category_id;
            $revenue->payment_method = 0;
            $revenue->reference      = $request->reference;
            $revenue->description    = $request->description;
            if (!empty($request->add_receipt)) {
                //storage limit
                $file_path = '/uploads/revenue/' . $revenue->add_receipt;
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if ($result == 1) {
                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    $path = storage_path('uploads/revenue/' . $revenue->add_receipt);

                    if (file_exists($path)) {
                        \File::delete($path);
                    }
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $revenue->add_receipt = $fileName;
                    $dir        = 'uploads/revenue';
                    $url = '';
                    $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                    if ($path['flag'] == 0) {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }

            $revenue->save();

            $category            = ProductServiceCategory::where('id', $request->category_id)->first();
            $revenue->category   = $category->name;
            $revenue->payment_id = $revenue->id;
            $revenue->type       = 'Revenue';
            // $revenue->account    = $request->account_id;
            // Transaction::editTransaction($revenue);

            $accountId = BankAccount::find($revenue->account_id);


            if (!empty($customer)) {
                Utility::userBalance('customer', $customer->id, $request->amount, 'credit');
            }

            // Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');
            GeneralLedger::where('ref_id', $revenue->id)
            ->where(function ($query) {
                $query->where('type', 'LIKE', '%RV%');
            })
            ->delete();

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
 // Add to General Ledger
                // Create a new entry for debit to cash account
                $AccountEntry = new GeneralLedger();
                $AccountEntry->vid = $newVoucherId;
                $AccountEntry->account = BankAccount::where('id', $request->account_id)->first()->chart_account_id;
                $AccountEntry->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $AccountEntry->debit = $request->amount;
                $AccountEntry->credit = 0;
                $AccountEntry->ref_id = $revenue->id;
                $AccountEntry->user_id = 0;
                $AccountEntry->created_by = \Auth::user()->creatorId();
                $AccountEntry->send_date = $request->date;
                $AccountEntry->reference = 'Revenue';
                $AccountEntry->save();

                $RevenueAccountEntry = new GeneralLedger();
                $RevenueAccountEntry->vid = $newVoucherId;
                $RevenueAccountEntry->account = $request->revenue_account;
                $RevenueAccountEntry->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $RevenueAccountEntry->debit = 0;
                $RevenueAccountEntry->credit = $request->amount;
                $RevenueAccountEntry->ref_id = $revenue->id;
                $RevenueAccountEntry->user_id = 0;
                $RevenueAccountEntry->created_by = \Auth::user()->creatorId();
                $RevenueAccountEntry->send_date = $request->date;
                $RevenueAccountEntry->reference = 'Revenue';
                $RevenueAccountEntry->save();

                $CustomerEntryC = new GeneralLedger();
                $CustomerEntryC->vid = $newVoucherId;
                $CustomerEntryC->account = $customer->chartAccount->id;
                $CustomerEntryC->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $CustomerEntryC->debit = 0;
                $CustomerEntryC->credit = $request->amount;
                $CustomerEntryC->ref_id = $revenue->id;
                $CustomerEntryC->user_id = $customer->id;
                $CustomerEntryC->created_by = \Auth::user()->creatorId();
                $CustomerEntryC->send_date = $request->date;
                $CustomerEntryC->reference = 'Revenue';
                $CustomerEntryC->save();

                $CustomerEntryD = new GeneralLedger();
                $CustomerEntryD->vid = $newVoucherId;
                $CustomerEntryD->account = $customer->chartAccount->id;
                $CustomerEntryD->type = \Auth::user()->revenueNumberFormat($revenue->id);
                $CustomerEntryD->debit = $request->amount;
                $CustomerEntryD->credit = 0;
                $CustomerEntryD->ref_id = $revenue->id;
                $CustomerEntryD->user_id = $customer->id;
                $CustomerEntryD->created_by = \Auth::user()->creatorId();
                $CustomerEntryD->send_date = $request->date;
                $CustomerEntryD->reference = 'Revenue';
                $CustomerEntryD->save();


            return redirect()->route('revenue.index')->with('success', __('Revenue Updated Successfully') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Revenue $revenue)
    {

        if (\Auth::user()->can('delete revenue')) {
            try {
                DB::beginTransaction();
                if ($revenue->created_by == \Auth::user()->creatorId()) {
                    $redirectUrl = route('revenue.index');
                    if (empty(request()->input('delete_date'))) {
                        $dateToDelete = now()->toDateString();
                    } else {
                        $dateToDelete = request()->input('delete_date');
                        $billCreatedAt = strtotime($revenue->created_at);

                        // Convert the input date to a timestamp
                        $inputDateTimestamp = strtotime($dateToDelete);
                        if ($inputDateTimestamp < $billCreatedAt) {
                            return redirect()->back()->with('error', __("Entered date is not greater than bill's created date"));
                        }
                    }
                    if (!empty($revenue->add_receipt)) {
                        //storage limit
                        $file_path = '/uploads/revenue/' . $revenue->add_receipt;
                        $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    }
                    TransactionLines::where('reference_id', $revenue->id)->where('reference', 'Revenue')->delete();
                    $revenue->delete();
                    $type = 'Revenue';
                    $user = 'Customer';
                    Transaction::destroyTransaction($revenue->id, $type, $user);

                    if ($revenue->customer_id != 0) {
                        Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
                    }


                    // Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');
                    // Ensure there are no conflicting records with the new voucher ID
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                    if (GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists()) {
                        return redirect()->back()->with('error', __("Something went wrong, please try again."));
                    }
                    // Create a new entry for debit to cash account
                    $AccountEntry = new GeneralLedger();
                    $AccountEntry->vid = $newVoucherId;
                    $AccountEntry->account = BankAccount::where('id', $revenue->account_id)->first()->chart_account_id;
                    $AccountEntry->type = "Delete revenue " . \Auth::user()->revenueNumberFormat($revenue->id);
                    $AccountEntry->debit = 0;
                    $AccountEntry->credit = $revenue->amount;
                    $AccountEntry->ref_id = $revenue->id;
                    $AccountEntry->user_id = 0;
                    $AccountEntry->created_by = \Auth::user()->creatorId();
                    $AccountEntry->send_date = $dateToDelete;
                    $AccountEntry->reference = 'Delete Revenue';
                    $AccountEntry->save();

                    $RevenueAccountEntry = new GeneralLedger();
                    $RevenueAccountEntry->vid = $newVoucherId;
                    $RevenueAccountEntry->account = $revenue->revenue_account;
                    $RevenueAccountEntry->type = "Delete revenue " . \Auth::user()->revenueNumberFormat($revenue->id);
                    $RevenueAccountEntry->debit = $revenue->amount;
                    $RevenueAccountEntry->credit = 0;
                    $RevenueAccountEntry->ref_id = $revenue->id;
                    $RevenueAccountEntry->user_id = 0;
                    $RevenueAccountEntry->created_by = \Auth::user()->creatorId();
                    $RevenueAccountEntry->send_date = $dateToDelete;
                    $RevenueAccountEntry->reference = 'Delete Revenue';
                    $RevenueAccountEntry->save();
                    $customer         = Customer::where('id', $revenue->customer_id)->first();
                    $CustomerEntryC = new GeneralLedger();
                    $CustomerEntryC->vid = $newVoucherId;
                    $CustomerEntryC->account = $customer->chartAccount->id;
                    $CustomerEntryC->type = "Delete revenue " . \Auth::user()->revenueNumberFormat($revenue->id);
                    $CustomerEntryC->debit = 0;
                    $CustomerEntryC->credit = $revenue->amount;
                    $CustomerEntryC->ref_id = $revenue->id;
                    $CustomerEntryC->user_id = $customer->id;
                    $CustomerEntryC->created_by = \Auth::user()->creatorId();
                    $CustomerEntryC->send_date = $dateToDelete;
                    $CustomerEntryC->reference = 'Delete Revenue';
                    $CustomerEntryC->save();

                    $CustomerEntryD = new GeneralLedger();
                    $CustomerEntryD->vid = $newVoucherId;
                    $CustomerEntryD->account = $customer->chartAccount->id;
                    $CustomerEntryD->type = "Delete revenue " . \Auth::user()->revenueNumberFormat($revenue->id);
                    $CustomerEntryD->debit =  $revenue->amount;
                    $CustomerEntryD->credit = 0;
                    $CustomerEntryD->ref_id = $revenue->id;
                    $CustomerEntryD->user_id = $customer->id;
                    $CustomerEntryD->created_by = \Auth::user()->creatorId();
                    $CustomerEntryD->send_date = $dateToDelete;
                    $CustomerEntryD->reference = 'Delete Revenue';
                    $CustomerEntryD->save();

                    DB::commit();
                    return redirect($redirectUrl)->with('success', __('Revenue successfully deleted.'));
                } else {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
