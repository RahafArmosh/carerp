<?php

namespace App\Http\Controllers;

use App\Exports\VenderExport;
use App\Imports\VenderImport;
use App\Models\CustomField;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\Vender;
use Auth;
use App\Models\User;
use App\Models\Plan;
use App\Models\Customer;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use App\Models\ChartOfAccount;
use App\Models\AccountingDocument;
use App\Models\Payment;
use App\Models\SimpleExpense;
use App\Models\DirectExpense;
use Illuminate\Support\Facades\Storage;
use App\Models\GeneralLedger;

class VenderController extends Controller
{

    public function dashboard()
    {
        $data['billChartData'] = \Auth::user()->billChartData();

        return view('vender.dashboard', $data);
    }

    public function index()
    {
        if (\Auth::user()->can('manage vender')) {
            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get();

            // Balance from ledger: debit - credit for Account Payable per vendor
            $payablesAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('name', 'Account Payable')
                ->first();

            $ledgerBalances = [];
            foreach ($venders as $vender) {
                $ledgerBalances[$vender->id] = 0;
            }

            if ($payablesAccount) {
                $totals = GeneralLedger::where('general_ledger.created_by', \Auth::user()->creatorId())
                    ->where('general_ledger.account', $payablesAccount->id)
                    ->whereIn('general_ledger.user_id', $venders->pluck('id'))
                    ->selectRaw('general_ledger.user_id, SUM(general_ledger.debit) as total_debit, SUM(general_ledger.credit) as total_credit')
                    ->groupBy('general_ledger.user_id')
                    ->get()
                    ->keyBy('user_id');

                foreach ($venders as $vender) {
                    $row = $totals->get($vender->id);
                    $debit = $row ? (float) $row->total_debit : 0;
                    $credit = $row ? (float) $row->total_credit : 0;
                    $ledgerBalances[$vender->id] = $debit - $credit;
                }
            }

            return view('vender.index', compact('venders', 'ledgerBalances'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create vender')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->get()
                ->pluck('code_name', 'id');
            // $chart_accounts->prepend('Select Account', '');

            $chart_accounts_customer = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Receivables')->get()
                ->pluck('code_name', 'id');

            return view('vender.create', compact('customFields', 'chart_accounts', 'chart_accounts_customer'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create vender')) {
            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                // 'email' => [
                //     Rule::unique('venders')->where(function ($query) {
                //         return $query->where('created_by', \Auth::user()->id);
                //     })
                // ],
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }
            $objVendor    = \Auth::user();
            $creator      = User::find($objVendor->creatorId());
            $total_vendor = $objVendor->countVenders();
            $plan         = Plan::find($creator->plan);
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            if ($total_vendor < $plan->max_venders || $plan->max_venders == -1) {
                $vender                   = new Vender();
                $vender->vender_id        = $this->venderNumber();
                $vender->supplier_code    = $this->supplierCode();
                $vender->name             = $request->name;
                $vender->contact          = $request->contact;
                $vender->email            = $request->email;
                $vender->tax_number      = $request->tax_number;
                // Resolve chart account: use provided one, otherwise find "Account Payable"
                if (!empty($request->chart_account)) {
                    $vender->chart_account_id = $request->chart_account;
                } else {
                    $accountPayable = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'Account Payable')
                        ->first();

                    if (!$accountPayable) {
                        return redirect()->back()->with('error', __('Account Payable chart account not found. Please create it first.'));
                    }

                    $vender->chart_account_id = $accountPayable->id;
                }
                $vender->created_by       = \Auth::user()->creatorId();
                $vender->billing_name     = $request->billing_name;
                $vender->billing_country  = $request->billing_country;
                $vender->billing_state    = $request->billing_state;
                $vender->billing_city     = $request->billing_city;
                $vender->billing_phone    = $request->billing_phone;
                $vender->billing_zip      = $request->billing_zip;
                $vender->billing_address  = $request->billing_address;
                $vender->shipping_name    = $request->shipping_name;
                $vender->shipping_country = $request->shipping_country;
                $vender->shipping_state   = $request->shipping_state;
                $vender->shipping_city    = $request->shipping_city;
                $vender->shipping_phone   = $request->shipping_phone;
                $vender->shipping_zip     = $request->shipping_zip;
                $vender->shipping_address = $request->shipping_address;
                $vender->lang             = !empty($default_language) ? $default_language->value : '';
                $vender->save();
                CustomField::saveData($vender, $request->customField);
                if ($request->has('customer_radio')) {
                    $customer                  = new Customer();
                    $customer->customer_id     = $this->venderNumber();
                    $customer->customer_code   = $this->customerCode();
                    $customer->name            = $request->name;
                    $customer->contact         = $request->contact;
                    $customer->email           = $request->email;
                    $customer->tax_number      = $request->tax_number;
                    // Resolve customer chart account: use provided one, otherwise find by code 1200
                    if (!empty($request->chart_account_customer)) {
                        $customer->chart_account_id = $request->chart_account_customer;
                    } else {
                        $customerAccount = ChartOfAccount::where('code', 1200)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();

                        if (!$customerAccount) {
                            return redirect()->back()->with('error', __('Customer chart account (code: 1200) not found. Please create it first.'));
                        }

                        $customer->chart_account_id = $customerAccount->id;
                    }
                    $customer->created_by      = \Auth::user()->creatorId();
                    $customer->billing_name    = $request->billing_name;
                    $customer->billing_country = $request->billing_country;
                    $customer->billing_state   = $request->billing_state;
                    $customer->billing_city    = $request->billing_city;
                    $customer->billing_phone   = $request->billing_phone;
                    $customer->billing_zip     = $request->billing_zip;
                    $customer->billing_address = $request->billing_address;

                    $customer->shipping_name    = $request->shipping_name;
                    $customer->shipping_country = $request->shipping_country;
                    $customer->shipping_state   = $request->shipping_state;
                    $customer->shipping_city    = $request->shipping_city;
                    $customer->shipping_phone   = $request->shipping_phone;
                    $customer->shipping_zip     = $request->shipping_zip;
                    $customer->shipping_address = $request->shipping_address;

                    $customer->lang = !empty($default_language) ? $default_language->value : '';

                    $customer->save();
                    CustomField::saveData($customer, $request->customField);
                }
                if ($request->hasFile('documents')) {
                    $documents = $request->file('documents');
                    foreach ($documents as $document) {
                        if ($document->isValid()) {
                            $filenameWithExt = $document->getClientOriginalName();
                            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                            $extension = $document->getClientOriginalExtension();
                            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                            // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
                            $document->move(public_path('documents'), $fileNameToStore);
                            // Save the file path to the database
                            $accountDocument = new AccountingDocument();
                            $accountDocument->document_name = $filenameWithExt;
                            $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                            $accountDocument->vender_id = $vender->id;
                            $accountDocument->save();
                            if ($request->has('customer_radio')) {
                                $accountDocument = new AccountingDocument();
                                $accountDocument->document_name = $filenameWithExt;
                                $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                                $accountDocument->customer_id = $customer->id;
                                $accountDocument->save();
                            }
                        } else {
                            // Handle file upload error
                            $error = $document->getErrorMessage(); // Get the specific error message
                            return redirect()->back()->with('error', $error);
                        }
                    }
                }
            } else {
                return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            }
            $role_r = Role::where('name', '=', 'vender')->firstOrFail();
            $vender->assignRole($role_r); //Assigning role to user
            $vender->type     = 'Vender';


            //For Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $vendorNotificationArr = [
                'user_name' => \Auth::user()->name,
                'vendor_name' => $vender->name,
                'vendor_email' => $vender->email,
            ];

            //Twilio Notification
            if (isset($setting['twilio_vender_notification']) && $setting['twilio_vender_notification'] == 1) {
                Utility::send_twilio_msg($request->contact, 'new_vendor', $vendorNotificationArr);
            }

            return redirect()->route('vender.index')->with('success', __('Vendor successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($ids)
    {
        try {
            $id       = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Vendor Not Found.'));
        }

        $id     = \Crypt::decrypt($ids);
        $vendor = Vender::find($id);
        $venderPyment = Payment::where('vender_id', '=', $vendor->id)->get();
        
        // Get service bills for this vendor
        $simpleExpenses = SimpleExpense::where('vender_id', $vendor->id)
            ->where('created_by', \Auth::user()->creatorId())
            ->with(['category', 'currency'])
            ->orderBy('expense_date', 'desc')
            ->get();
        
        // Get direct expenses for this vendor
        $directExpenses = DirectExpense::where('vendor_id', $vendor->id)
            ->where('created_by', \Auth::user()->creatorId())
            ->with(['vendor', 'currency', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('vender.show', compact('vendor', 'venderPyment', 'simpleExpenses', 'directExpenses'));
    }


    public function edit($id)
    {
        if (\Auth::user()->can('edit vender')) {
            $vender              = Vender::find($id);
            $vender->customField = CustomField::getData($vender, 'vendor');
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->get()
                ->pluck('code_name', 'id');
            return view('vender.edit', compact('vender', 'customFields', 'chart_accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Vender $vender)
    {
        if (\Auth::user()->can('edit vender')) {

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'chart_account' => 'required|exists:chart_of_accounts,id',
            ];


            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }
            $vender->name             = $request->name;
            $vender->contact          = $request->contact;
            $vender->tax_number      = $request->tax_number;
             // Resolve chart account: use provided one, otherwise find "Account Payable"
            if (!empty($request->chart_account)) {
                $vender->chart_account_id = $request->chart_account;
            } else {
                $accountPayable = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                    ->where('name', 'Account Payable')
                    ->first();

                if (!$accountPayable) {
                    return redirect()->back()->with('error', __('Account Payable chart account not found. Please create it first.'));
                }

                $vender->chart_account_id = $accountPayable->id;
            }
            $vender->created_by       = \Auth::user()->creatorId();
            $vender->billing_name     = $request->billing_name;
            $vender->billing_country  = $request->billing_country;
            $vender->billing_state    = $request->billing_state;
            $vender->billing_city     = $request->billing_city;
            $vender->billing_phone    = $request->billing_phone;
            $vender->billing_zip      = $request->billing_zip;
            $vender->billing_address  = $request->billing_address;
            $vender->shipping_name    = $request->shipping_name;
            $vender->shipping_country = $request->shipping_country;
            $vender->shipping_state   = $request->shipping_state;
            $vender->shipping_city    = $request->shipping_city;
            $vender->shipping_phone   = $request->shipping_phone;
            $vender->shipping_zip     = $request->shipping_zip;
            $vender->shipping_address = $request->shipping_address;
            
            // Generate supplier_code if it's null or empty
            if (empty($vender->supplier_code)) {
                $vender->supplier_code = $this->supplierCode();
            }
            
            $vender->save();
            CustomField::saveData($vender, $request->customField);

            if ($request->hasFile('documents')) {
                $documents = $request->file('documents');
                foreach ($documents as $document) {
                    $filenameWithExt = $document->getClientOriginalName();
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $document->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
                    $document->move(public_path('documents'), $fileNameToStore);
                    // Save the file path to the database
                    $accountDocument = new AccountingDocument();
                    $accountDocument->document_name = $filenameWithExt;
                    $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                    $accountDocument->vender_id = $vender->id;
                    $accountDocument->save();
                }
            }
            return redirect()->route('vender.index')->with('success', __('Vendor successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Vender $vender)
    {
        if (\Auth::user()->can('delete vender')) {
            if ($vender->created_by == \Auth::user()->creatorId()) {
                $relatedTypes = [];
                if (\App\Models\Bill::where('vender_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('bills');
                }
                if (\App\Models\SimpleExpense::where('vender_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('service bills');
                }
                if (\App\Models\DirectExpense::where('vendor_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('direct expenses');
                }
                if (\App\Models\DirectExpensePayment::where('vendor_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('direct expense payments');
                }
                if (\App\Models\Payment::where('vender_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('payments');
                }
                if (\App\Models\Refund::where('vendor_id', $vender->id)->exists()) {
                    $relatedTypes[] = __('refunds');
                }

                if (!empty($relatedTypes)) {
                    return redirect()->back()->with('error', __('Cannot delete vendor with related records: :types', ['types' => implode(', ', $relatedTypes)]));
                }

                $vender->delete();

                return redirect()->route('vender.index')->with('success', __('Vendor successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function venderNumber()
    {
        $max = Vender::where('created_by', '=', \Auth::user()->creatorId())->max('vender_id');

        return (int) $max + 1;
    }

    function supplierCode()
    {
        $creatorId = \Auth::user()->creatorId();
        $codes     = Vender::where('created_by', $creatorId)
            ->whereNotNull('supplier_code')
            ->pluck('supplier_code');

        $maxNum = 0;
        foreach ($codes as $code) {
            if (preg_match('/SUP(\d+)/', (string) $code, $matches)) {
                $maxNum = max($maxNum, (int) $matches[1]);
            }
        }

        $nextNumber = $maxNum + 1;

        return 'SUP' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    function customerCode()
    {
        $latest = Customer::where('created_by', '=', \Auth::user()->creatorId())
            ->whereNotNull('customer_code')
            ->latest()
            ->first();
        
        if (!$latest || !$latest->customer_code) {
            return 'CUS001';
        }

        // Extract number from code (e.g., CUS001 -> 1)
        preg_match('/CUS(\d+)/', $latest->customer_code, $matches);
        $number = isset($matches[1]) ? (int)$matches[1] : 0;
        $nextNumber = $number + 1;
        
        return 'CUS' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function venderLogout(Request $request)
    {
        \Auth::guard('vender')->logout();

        $request->session()->invalidate();

        return redirect()->route('vender.login');
    }

    public function payment(Request $request)
    {

        if (\Auth::user()->can('manage vender payment')) {
            $category = [
                'Bill' => 'Bill',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('created_by', \Auth::user()->creatorId())->where('user_type', 'Vender')->where('type', 'Payment');
            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('vender.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {

        if (\Auth::user()->can('manage vender transaction')) {

            $category = [
                'Bill' => 'Bill',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Vender');

            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('vender.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'vendor');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

        return view('vender.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {

        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'name' => 'required|max:120',
                'contact' => 'required',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );
        if ($request->hasFile('profile')) {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $dir        = storage_path('uploads/avatar/');
            $image_path = $dir . $userDetail['avatar'];

            if (File::exists($image_path)) {
                File::delete($image_path);
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
        }

        if (!empty($request->profile)) {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name']    = $request['name'];
        $user['email']   = $request['email'];
        $user['contact'] = $request['contact'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editBilling(Request $request)
    {

        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'billing_name' => 'required',
                'billing_country' => 'required',
                'billing_state' => 'required',
                'billing_city' => 'required',
                'billing_phone' => 'required',
                'billing_zip' => 'required',
                'billing_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editShipping(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'shipping_name' => 'required',
                'shipping_country' => 'required',
                'shipping_state' => 'required',
                'shipping_city' => 'required',
                'shipping_phone' => 'required',
                'shipping_zip' => 'required',
                'shipping_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function changeLanquage($lang)
    {


        $user       = Auth::user();
        $user->lang = $lang;
        $user->save();

        return redirect()->back()->with('success', __('Language successfully change.'));
    }

    public function export()
    {
        $name = 'vendor_' . date('Y-m-d i:h:s');
        $data = Excel::download(new VenderExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('vender.import');
    }

    public function import(Request $request)
    {

        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $vendors = (new VenderImport())->toArray(request()->file('file'))[0];

        $totalCustomer = count($vendors) - 1;
        $errorArray    = [];
        $creatorId = \Auth::user()->creatorId();

        for ($i = 1; $i <= count($vendors) - 1; $i++) {
            $vendor = $vendors[$i];

            $email = isset($vendor[2]) ? trim((string) $vendor[2]) : '';
            $vendorByEmail = $email !== ''
                ? Vender::where('created_by', $creatorId)->where('email', $email)->first()
                : null;

            if ($vendorByEmail) {
                $vendorData = $vendorByEmail;
            } else {
                $vendorData = new Vender();
            }

            $vendorData->name               = $vendor[1] ?? '';
            $vendorData->email            = $email;
            $vendorData->contact          = $vendor[3] ?? '';
            $vendorData->avatar           = $vendor[4] ?? '';
            $vendorData->billing_name     = $vendor[5] ?? '';
            $vendorData->billing_country  = $vendor[6] ?? '';
            $vendorData->billing_state    = $vendor[7] ?? '';
            $vendorData->billing_city     = $vendor[8] ?? '';
            $vendorData->billing_phone    = $vendor[9] ?? '';
            $vendorData->billing_zip      = $vendor[10] ?? '';
            $vendorData->billing_address  = $vendor[11] ?? '';
            $vendorData->shipping_name    = $vendor[12] ?? '';
            $vendorData->shipping_country = $vendor[13] ?? '';
            $vendorData->shipping_state   = $vendor[14] ?? '';
            $vendorData->shipping_city    = $vendor[15] ?? '';
            $vendorData->shipping_phone   = $vendor[16] ?? '';
            $vendorData->shipping_zip     = $vendor[17] ?? '';
            $vendorData->shipping_address  = $vendor[18] ?? '';
            if (isset($vendor[19]) && $vendor[19] !== '' && $vendor[19] !== null) {
                $vendorData->chart_account_id = $vendor[19];
            }
            $vendorData->created_by = $creatorId;

            if (!$vendorData->exists) {
                $vendorData->vender_id       = $this->venderNumber();
                $vendorData->supplier_code   = $this->supplierCode();
                if (empty($vendorData->chart_account_id)) {
                    $accountPayable = ChartOfAccount::where('created_by', $creatorId)
                        ->where('name', 'Account Payable')
                        ->first();
                    if ($accountPayable) {
                        $vendorData->chart_account_id = $accountPayable->id;
                    }
                }
            }

            if (empty($vendorData)) {
                $errorArray[] = $vendorData;
            } else {
                $vendorData->save();
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function uploadvendor(Request $request)
    {
        // Validate the file
        $request->validate([
            'fileInput.*' => 'required|file|max:10240', // Example validation rules (max size: 10MB)
        ]);
        $vendorId = $request->input('vendorId');
        foreach ($request->file('fileInput') as $document) {
            $filenameWithExt = $document->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $document->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
            $document->move(public_path('documents'), $fileNameToStore);
            // Save the file path to the database
            $accountDocument = new AccountingDocument();
            $accountDocument->document_name = $filenameWithExt;
            $accountDocument->document_path = 'documents/' . $fileNameToStore;
            $accountDocument->vender_id = $vendorId;
            $accountDocument->save();
        }
        return back()->with('success', 'File uploaded successfully.');
    }


    public function deleteFile(Request $request)
    {
        $fileId = $request->input('document_id');

        // Find the file by ID and delete it (adjust this logic based on your implementation)
        $file = AccountingDocument::find($fileId);
        if ($file) {
            Storage::delete('public/' . $file->document_path);
            $file->delete();
            return back()->with('success', 'File deleted successfully.');
        }
        return back()->with('error', 'File not found.');
    }

    /**
     * Get vendor details for AJAX request
     */
    public function getDetail(Request $request)
    {
        $vendorId = $request->input('id');
        $vendor = Vender::where('id', $vendorId)
                       ->where('created_by', \Auth::user()->creatorId())
                       ->first();

        if ($vendor) {
            return response()->json([
                'name' => $vendor->name,
                'email' => $vendor->email,
                'contact' => $vendor->contact,
                'address' => $vendor->billing_address
            ]);
        }

        return response()->json(['error' => 'Vendor not found'], 404);
    }
}
