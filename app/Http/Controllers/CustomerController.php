<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Transaction;
use App\Models\Utility;
use Auth;
use App\Models\User;
use App\Models\Plan;
use App\Models\Vender;
use App\Models\AccountingDocument;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerPayment;
use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;

class CustomerController extends Controller
{

    public function dashboard()
    {
        $data['invoiceChartData'] = \Auth::user()->invoiceChartData();

        return view('customer.dashboard', $data);
    }

    public function index()
    {
        if (\Auth::user()->can('manage customer')) {
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get();

            // Balance from ledger: debit - credit for Account Receivables per customer
            $receivablesAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('name', 'Account Receivables')
                ->first();
            $ledgerBalances = [];
            foreach ($customers as $customer) {
                $ledgerBalances[$customer->id] = 0;
            }
            if ($receivablesAccount) {
                $totals = GeneralLedger::where('general_ledger.created_by', \Auth::user()->creatorId())
                    ->where('general_ledger.account', $receivablesAccount->id)
                    ->whereIn('general_ledger.user_id', $customers->pluck('id'))
                    ->selectRaw('general_ledger.user_id, SUM(general_ledger.debit) as total_debit, SUM(general_ledger.credit) as total_credit')
                    ->groupBy('general_ledger.user_id')
                    ->get()
                    ->keyBy('user_id');
                foreach ($customers as $customer) {
                    $row = $totals->get($customer->id);
                    $debit = $row ? (float) $row->total_debit : 0;
                    $credit = $row ? (float) $row->total_credit : 0;
                    $ledgerBalances[$customer->id] = $debit - $credit;
                }
            }

            return view('customer.index', compact('customers', 'ledgerBalances'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create(Request $request)
    {
        if (\Auth::user()->can('create customer')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Receivables')->get()
                ->pluck('code_name', 'id');

            $chart_accounts_vendor = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->get()
                ->pluck('code_name', 'id');

            $fromPos = $request->has('from') && $request->from === 'pos';

            return view('customer.create', compact('customFields', 'chart_accounts', 'chart_accounts_vendor', 'fromPos'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create customer')) {

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                // 'email' => [
                //     Rule::unique('customers')->where(function ($query) {
                //         return $query->where('created_by', \Auth::user()->id);
                //     })
                // ],
            ];


            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                
                // If from POS, return JSON response
                if ($request->has('from_pos') && $request->from_pos == '1') {
                    return response()->json([
                        'error' => $messages->first(),
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            // Check for duplicate customer with same name and contact number for the same creator
            $creatorId = \Auth::user()->creatorId();
            $name = trim($request->name);
            $contact = trim($request->contact);
            
            $existingCustomer = Customer::where('created_by', $creatorId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
                ->where('contact', $contact)
                ->first();

            if ($existingCustomer) {
                $errorMessage = __('A customer with the same name and contact number already exists.');
                
                // If from POS, return JSON response
                if ($request->has('from_pos') && $request->from_pos == '1') {
                    return response()->json([
                        'error' => $errorMessage,
                        'errors' => ['name' => [$errorMessage], 'contact' => [$errorMessage]]
                    ], 422);
                }
                
                return redirect()->route('customer.index')->with('error', $errorMessage);
            }

            $objCustomer    = \Auth::user();
            $creator        = User::find($objCustomer->creatorId());
            $total_customer = $objCustomer->countCustomers();
            $plan           = Plan::find($creator->plan);

            $default_language          = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            if ($total_customer < $plan->max_customers || $plan->max_customers == -1) {
                $customer                  = new Customer();
                $customer->customer_id     = $this->customerNumber();
                $customer->customer_code   = $this->customerCode();
                $customer->name            = trim($request->name);
                $customer->contact         = trim($request->contact);
                $customer->email           = $request->email;
                $customer->tax_number      = $request->tax_number;
                $customer->customer_trn_no  = $request->customer_trn_no;
                // Resolve chart account: use provided one, otherwise find "Account Receivables"
                if (!empty($request->chart_account)) {
                    $customer->chart_account_id = $request->chart_account;
                } else {
                    $accountReceivables = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'Account Receivables')
                        ->first();

                    if (!$accountReceivables) {
                        // If from POS, return JSON response
                        if ($request->has('from_pos') && $request->from_pos == '1') {
                            return response()->json([
                                'error' => __('Account Receivables chart account not found. Please create it first.')
                            ], 422);
                        }
                        return redirect()->back()->with('error', __('Account Receivables chart account not found. Please create it first.'));
                    }

                    $customer->chart_account_id = $accountReceivables->id;
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

                // Retry logic for handling race conditions with unique customer_code
                $maxRetries = 5;
                $retryCount = 0;
                $saved = false;
                
                while (!$saved && $retryCount < $maxRetries) {
                    try {
                        $customer->save();
                        $saved = true;
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Check if it's a duplicate entry error for customer_code
                        if ($e->getCode() == 23000 && (strpos($e->getMessage(), 'customer_code_unique') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false)) {
                            $retryCount++;
                            // Regenerate customer code
                            $customer->customer_code = $this->customerCode();
                            \Log::warning('Duplicate customer_code detected, retrying with new code', [
                                'attempt' => $retryCount,
                                'new_code' => $customer->customer_code
                            ]);
                        } else {
                            // Re-throw if it's a different error
                            throw $e;
                        }
                    }
                }
                
                if (!$saved) {
                    throw new \Exception('Failed to save customer after ' . $maxRetries . ' attempts due to duplicate customer_code');
                }
                
                CustomField::saveData($customer, $request->customField);
                if ($request->has('vendor_radio')) {
                    $vender                   = new Vender();
                    $vender->vender_id        = $this->customerNumber();
                    $vender->supplier_code    = $this->supplierCode();
                    $vender->name             = $request->name;
                    $vender->contact          = $request->contact;
                    $vender->email            = $request->email;
                    $vender->tax_number      = $request->tax_number;
                    // Resolve vendor chart account: use provided one, otherwise find by code 2100
                    if (!empty($request->chart_account_vendor)) {
                        $vender->chart_account_id = $request->chart_account_vendor;
                    } else {
                        $vendorAccount = ChartOfAccount::where('code', 2100)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();

                        if (!$vendorAccount) {
                            return redirect()->back()->with('error', __('Vendor chart account (code: 2100) not found. Please create it first.'));
                        }

                        $vender->chart_account_id = $vendorAccount->id;
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
                }
                //save doc for customer
                if ($request->hasFile('documents')) {
                    $documents = $request->file('documents');
                    foreach ($documents as $document) {
                        $filenameWithExt = $document->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $document->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . (time() + 10) . '.' . $extension;
                        $document->move(public_path('documents'), $fileNameToStore);
                        $accountDocument = new AccountingDocument();
                        $accountDocument->document_name = $filenameWithExt;
                        $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                        $accountDocument->customer_id = $customer->id;
                        $accountDocument->save();
                        if ($request->has('vendor_radio')) {
                            $accountDocument = new AccountingDocument();
                            $accountDocument->document_name = $filenameWithExt;
                            $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                            $accountDocument->vender_id = $vender->id;
                            $accountDocument->save();
                        }
                    }
                }
            } else {
                // If from POS, return JSON response
                if ($request->has('from_pos') && $request->from_pos == '1') {
                    return response()->json([
                        'error' => __('Your user limit is over, Please upgrade plan.')
                    ], 422);
                }
                return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            }

            //For Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $customerNotificationArr = [
                'user_name' => \Auth::user()->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
            ];

            //Twilio Notification
            if (isset($setting['twilio_customer_notification']) && $setting['twilio_customer_notification'] == 1) {
                Utility::send_twilio_msg($request->contact, 'new_customer', $customerNotificationArr);
            }

            // If created from POS, return JSON response instead of redirecting
            if ($request->has('from_pos') && $request->from_pos == '1') {
                return response()->json([
                    'success' => true,
                    'message' => __('Customer successfully created.'),
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'contact' => $customer->contact,
                        'email' => $customer->email,
                    ]
                ]);
            }

            return redirect()->route('customer.index')->with('success', __('Customer successfully created.'));
        } else {
            // If from POS, return JSON response
            if ($request->has('from_pos') && $request->from_pos == '1') {
                return response()->json([
                    'error' => __('Permission denied.')
                ], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($ids)
    {
        try {
            $id       = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Customer Not Found.'));
        }
        $id       = \Crypt::decrypt($ids);
        $customer = Customer::find($id);
        $customerPyment = CustomerPayment::where('customer_id', '=', $id)->get();
        return view('customer.show', compact('customer', 'customerPyment'));
    }


    public function edit($id)
    {
        if (\Auth::user()->can('edit customer')) {
            $customer              = Customer::find($id);
            $customer->customField = CustomField::getData($customer, 'customer');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Receivables')->get()
                ->pluck('code_name', 'id');
            return view('customer.edit', compact('customer', 'customFields', 'chart_accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Customer $customer)
    {

        if (\Auth::user()->can('edit customer')) {

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'chart_account' => 'required|exists:chart_of_accounts,id',
            ];


            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            $customer->name             = $request->name;
            $customer->contact          = $request->contact;
            $customer->email           = $request->email;
            $customer->chart_account_id           = $request->chart_account;
            $customer->tax_number      = $request->tax_number;
            $customer->customer_trn_no  = $request->customer_trn_no;
            $customer->created_by       = \Auth::user()->creatorId();
            $customer->billing_name     = $request->billing_name;
            $customer->billing_country  = $request->billing_country;
            $customer->billing_state    = $request->billing_state;
            $customer->billing_city     = $request->billing_city;
            $customer->billing_phone    = $request->billing_phone;
            $customer->billing_zip      = $request->billing_zip;
            $customer->billing_address  = $request->billing_address;
            $customer->shipping_name    = $request->shipping_name;
            $customer->shipping_country = $request->shipping_country;
            $customer->shipping_state   = $request->shipping_state;
            $customer->shipping_city    = $request->shipping_city;
            $customer->shipping_phone   = $request->shipping_phone;
            $customer->shipping_zip     = $request->shipping_zip;
            $customer->shipping_address = $request->shipping_address;
            
            // Generate customer_code if it's null or empty
            if (empty($customer->customer_code)) {
                $customer->customer_code = $this->customerCode();
            }
            
            $customer->save();

            CustomField::saveData($customer, $request->customField);
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
                    $accountDocument->customer_id = $customer->id;
                    $accountDocument->save();
                }
            }
            return redirect()->route('customer.index')->with('success', __('Customer successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Customer $customer)
    {
        if (\Auth::user()->can('delete customer')) {
            if ($customer->created_by == \Auth::user()->creatorId()) {
                if (\App\Models\Invoice::where('customer_id', $customer->id)->exists()) {
                    return redirect()->back()->with('error', __('Cannot delete customer with existing invoices.'));
                }
                $customer->delete();

                return redirect()->route('customer.index')->with('success', __('Customer successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function customerNumber()
    {
        $max = Customer::where('created_by', '=', \Auth::user()->creatorId())->max('customer_id');

        return ($max === null || $max === '') ? 1 : ((int) $max + 1);
    }

    function customerCode()
    {
        // Find the latest customer_code for this creator to maintain sequential numbering
        $latest = Customer::where('created_by', '=', \Auth::user()->creatorId())
            ->whereNotNull('customer_code')
            ->latest()
            ->first();
        
        $startNumber = 1;
        
        if ($latest && $latest->customer_code) {
            // Extract number from code (e.g., CUS001 -> 1)
            preg_match('/CUS(\d+)/', $latest->customer_code, $matches);
            if (isset($matches[1])) {
                $startNumber = (int)$matches[1] + 1;
            }
        }
        
        // Find the next available code by checking for global uniqueness
        // (since customer_code has a unique constraint globally)
        $maxAttempts = 1000; // Prevent infinite loop
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $code = 'CUS' . str_pad($startNumber, 3, '0', STR_PAD_LEFT);
            
            // Check if this code already exists globally (unique constraint is global)
            $exists = Customer::where('customer_code', $code)->exists();
            
            if (!$exists) {
                return $code;
            }
            
            // Code exists, try next number
            $startNumber++;
            $attempt++;
        }
        
        // Fallback: use timestamp-based code if we can't find a unique sequential one
        // This should rarely happen, but prevents infinite loops
        return 'CUS' . str_pad(substr(time(), -6), 6, '0', STR_PAD_LEFT);
    }

    function supplierCode()
    {
        $latest = Vender::where('created_by', '=', \Auth::user()->creatorId())
            ->whereNotNull('supplier_code')
            ->latest()
            ->first();
        
        if (!$latest || !$latest->supplier_code) {
            return 'SUP001';
        }

        // Extract number from code (e.g., SUP001 -> 1)
        preg_match('/SUP(\d+)/', $latest->supplier_code, $matches);
        $number = isset($matches[1]) ? (int)$matches[1] : 0;
        $nextNumber = $number + 1;
        
        return 'SUP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function customerLogout(Request $request)
    {
        \Auth::guard('customer')->logout();

        $request->session()->invalidate();

        return redirect()->route('customer.login');
    }

    public function payment(Request $request)
    {

        if (\Auth::user()->can('manage customer payment')) {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer')->where('type', 'Payment');
            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('customer.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {
        if (\Auth::user()->can('manage customer payment')) {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer');

            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('customer.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'customer');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

        return view('customer.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Customer::findOrFail($userDetail['id']);

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
        $user       = Customer::findOrFail($userDetail['id']);
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
        $user       = Customer::findOrFail($userDetail['id']);
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

        return redirect()->back()->with('success', __('Language Change Successfully!'));
    }


    public function export()
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = 'customer_' . date('Y-m-d i:h:s');
        $data = Excel::download(new CustomerExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('customer.import');
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

        $customers = (new CustomerImport())->toArray(request()->file('file'))[0];

        $totalCustomer = count($customers) - 1;
        $errorArray    = [];
        for ($i = 1; $i <= count($customers) - 1; $i++) {
            $customer = $customers[$i];

            $customerByEmail = Customer::where('email', $customer[2])->first();
            if (!empty($customerByEmail)) {
                $customerData = $customerByEmail;
            } else {
                $customerData = new Customer();
                $customerData->customer_id = $this->customerNumber();
                $customerData->customer_code = $this->customerCode();
            }

            // Do not set customer_id from CSV column 0 ("Customer No" is display-only / formatted in exports).
            $customerData->name             = $customer[1];
            $customerData->email            = $customer[2];
            $customerData->contact          = $customer[3];
            $customerData->is_active        = 1;
            $customerData->billing_name     = $customer[4];
            $customerData->billing_country  = $customer[5];
            $customerData->billing_state    = $customer[6];
            $customerData->billing_city     = $customer[7];
            $customerData->billing_phone    = $customer[8];
            $customerData->billing_zip      = $customer[9];
            $customerData->billing_address  = $customer[10];
            $customerData->shipping_name    = $customer[11];
            $customerData->shipping_country = $customer[12];
            $customerData->shipping_state   = $customer[13];
            $customerData->shipping_city    = $customer[14];
            $customerData->shipping_phone   = $customer[15];
            $customerData->shipping_zip     = $customer[16];
            $customerData->shipping_address = $customer[17];
            $customerData->chart_account_id = $customer[18];
            $customerData->balance          = 0;
            $customerData->created_by       = \Auth::user()->creatorId();

            if (empty($customerData)) {
                $errorArray[] = $customerData;
            } else {
                $customerData->save();
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

    public function searchCustomers(Request $request)
    {
        try {
            if (!\Auth::check()) {
                return response()->json(['results' => []]);
            }
            
            $search = trim($request->q ?? $request->search ?? '');
            $creatorId = \Auth::user()->creatorId();
            
            // Build base query
            $query = Customer::where('is_active', '=', 1)
                ->where('created_by', '=', $creatorId);
            
            // If search term is provided, search across all fields (name, email, contact, phones, customer_code)
            // Using LOWER() for case-insensitive search
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchLower . '%'])
                      ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $searchLower . '%'])
                      ->orWhereRaw('LOWER(contact) LIKE ?', ['%' . $searchLower . '%'])
                      ->orWhereRaw('LOWER(billing_phone) LIKE ?', ['%' . $searchLower . '%'])
                      ->orWhereRaw('LOWER(shipping_phone) LIKE ?', ['%' . $searchLower . '%'])
                      ->orWhereRaw('LOWER(customer_code) LIKE ?', ['%' . $searchLower . '%']);
                });
            }
            
            $customers = $query->limit(50)
                ->get()
                ->map(function($customer) {
                    $displayText = $customer->name;
                    if ($customer->contact) {
                        $displayText .= ' (' . $customer->contact . ')';
                    }
                    if ($customer->email) {
                        $displayText .= ' - ' . $customer->email;
                    }
                    
                    return [
                        'id' => $customer->id,
                        'text' => $displayText,
                        'value' => $customer->id,
                        'label' => $customer->name,
                        'email' => $customer->email,
                        'contact' => $customer->contact,
                    ];
                });

            return response()->json(['results' => $customers]);
        } catch (\Exception $e) {
            \Log::error('Customer search error: ' . $e->getMessage());
            return response()->json(['results' => [], 'error' => $e->getMessage()], 500);
        }
    }



    public function uploadcustomer(Request $request)
    {
        // Validate the file
        $request->validate([
            'fileInput.*' => 'required|file|max:10240', // Example validation rules (max size: 10MB)
        ]);
        $customerId = $request->input('customerId');
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
            $accountDocument->customer_id = $customerId;
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
}
