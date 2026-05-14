<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\ProductService;
use App\Models\User;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\GeneralLedger;
use App\Models\Vender;
use App\Models\SubProduct;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ChartOfAccountsImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use \Carbon\Carbon;
class ChartOfAccountController extends Controller
{

    public function index(Request $request)
    {

        if(\Auth::user()->can('manage chart of account'))
        {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-01-01');
                $end = date('Y-m-d', strtotime('+1 day'));
            }
            $code = $request->get('code', null);
            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;
            $filter['code'] = $code;
            $types = ChartOfAccountType::where('created_by', '=', \Auth::user()->creatorId())->get();

            $chartAccounts = [];
            foreach($types as $type)
            {
                $query = ChartOfAccount::where('type', $type->id)
                ->where('created_by', '=', \Auth::user()->creatorId())
                ->with('subType');

            // Add filter by code if it's provided
            if ($code) {
                $query->where('code', 'like', "%{$code}%");  // Use a LIKE query to match part of the code
            }

            $accounts = $query->get();
            $chartAccounts[$type->name] = $accounts;



            }

            return view('chartOfAccount.index', compact('chartAccounts', 'types' , 'filter'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        $types = ChartOfAccountType::where('created_by',\Auth::user()->creatorId())->get()->pluck('name', 'id');
        $types->prepend('Select Account Type', 0);

        return view('chartOfAccount.create', compact('types'));
    }


    public function store(Request $request)
    {

        if(\Auth::user()->can('create chart of account'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required',
                                   'type' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $account              = new ChartOfAccount();
            $account->name        = $request->name;
            $account->code        = $request->code;
            $account->type        = $request->type;
            $account->sub_type    = $request->sub_type;
            $account->description = $request->description;
            $account->is_enabled  = isset($request->is_enabled) ? 1 : 0;
            $account->created_by  = \Auth::user()->creatorId();
            $account->save();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show(ChartOfAccount $chartOfAccount,Request $request)
    {
        if(\Auth::user()->can('ledger report'))
        {
            if(!empty($request->start_date) && !empty($request->end_date))
            {
                $start = $request->start_date;
                $end   = $request->end_date;
            }
            else
            {
                $start = date('Y-m-01');
                $end   = date('Y-m-t');
            }
            if(!empty($request->start_date) && !empty($request->end_date))
            {
                $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<=', $end)
                    ->get()->pluck('code_name', 'id');
                $accounts->prepend('Select Account', '');

            }
            else
            {
                $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $accounts->prepend('Select Account', '');
            }
            if(!empty($request->account))
            {
                $account = ChartOfAccount::find($request->account);
            }
            else
            {
                $account = ChartOfAccount::find($chartOfAccount->id);
            }

            // $journalItems = JournalItem::select('journal_entries.journal_id', 'journal_entries.date as transaction_date', 'journal_items.*')
            //     ->leftjoin('journal_entries', 'journal_entries.id', 'journal_items.journal')
            //     ->where('journal_entries.created_by', '=', \Auth::user()->creatorId())
            //     ->where('account', !empty($account) ? $account->id : 0);
            // $journalItems->where('date', '>=', $start);
            // $journalItems->where('date', '<=', $end);
            // $journalItems = $journalItems->get();

            $balance = 0;
            $debit   = 0;
            $credit  = 0;

            // foreach($journalItems as $item)
            // {
            //     if($item->debit > 0)
            //     {
            //         $debit += $item->debit;
            //     }

            //     else
            //     {
            //         $credit += $item->credit;
            //     }

            //     $balance = $credit - $debit;
            // }

            $filter['startDateRange'] = $start;
            $filter['endDateRange']   = $end;

            return view('chartOfAccount.show', compact('filter', 'account', 'accounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function edit(ChartOfAccount $chartOfAccount)
    {
        $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $types->prepend('Select Account Type', 0);

        // Get sub-types for the current type
        $subTypes = [];
        if ($chartOfAccount->type) {
            $subTypes = ChartOfAccountSubType::where('type', $chartOfAccount->type)
                ->get()
                ->pluck('name', 'id');
        }

        return view('chartOfAccount.edit', compact('chartOfAccount', 'types', 'subTypes'));
    }


    public function update(Request $request, $id)
    {

        if(\Auth::user()->can('edit chart of account'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $chartOfAccount = ChartOfAccount::find($id);
            $chartOfAccount->name        = $request->name;
            $chartOfAccount->code        = $request->code;
            $chartOfAccount->type        = $request->type;
            $chartOfAccount->sub_type    = $request->sub_type;
            $chartOfAccount->description = $request->description;
            $chartOfAccount->is_enabled  = isset($request->is_enabled) ? 1 : 0;
            $chartOfAccount->save();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully updated.'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function destroy(ChartOfAccount $chartOfAccount)
    {
        if(\Auth::user()->can('delete chart of account'))
        {
            $chartOfAccount->delete();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully deleted.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getSubType(Request $request)
    {
        $types = ChartOfAccountSubType::where('type', $request->type)->get()->pluck('name', 'id')->toArray();

        return response()->json($types);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls|max:2048', // Max 2MB
        ]);

        Excel::import(new ChartOfAccountsImport, $request->file('import_file'));

        return redirect()->back()->with('success', 'Chart of Accounts imported successfully!');
    }

    public function importFile()
    {
        return view('chartOfAccount.import');
    }
    public function showChartSetup()
    {
        $accounts = ChartOfAccount::with('types')->where('is_enabled', 1)
            ->where('created_by', \Auth::user()->creatorId())
            ->orderBy('type')
            ->get()
            ->groupBy('type');

        $customers = Customer::where('created_by', \Auth::user()->creatorId())->get(); // Assuming you have a Customer model
        $venders = Vender::where('created_by', \Auth::user()->creatorId())->get(); // Assuming you have a Customer model
        $existing = GeneralLedger::where('reference', 'opening balance')
        ->where('created_by',\Auth::user()->creatorId())
        ->first();
        $today = now()->toDateString();
        if ($existing) {
            $today = $existing->send_date;
        }
        // dd($existing);
        return view('chartOfAccount.chart_setup', compact('accounts', 'customers','venders','today'));
    }

    public function submitOpeningBalances(Request $request)
    {
        try {
            DB::beginTransaction();

            // Generate next VID
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;
            $creatorId = \Auth::user()->creatorId();
            $today = $request->input('send_date');
            
            // Update send_date for all existing opening balance entries first
            GeneralLedger::where('reference', 'opening balance')
                ->where('created_by', \Auth::user()->creatorId())
                ->update(['send_date' => $today]);

            $accounts = $request->input('accounts', []);
            $customers = $request->input('customers', []);
            $venders = $request->input('venders', []);

            // Handle chart of accounts (excluding customers)
            foreach ($accounts as $accountId => $data) {

                $debit = isset($data['debit']) ? floatval($data['debit']) : 0;
                $credit = isset($data['credit']) ? floatval($data['credit']) : 0;

                // Check if this account already has an opening balance entry
                $existing = GeneralLedger::where('account', $accountId)
                    ->where('reference', 'opening balance')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('user_id', 0) // Only main account entries, not customer/vendor specific
                    ->first();

                // Process if: 1) Has values > 0, OR 2) Already exists in ledger (even if setting to 0)
                if (($debit > 0 || $credit > 0) || $existing) {

                    $account = ChartOfAccount::find($accountId);
                    $existingVid = GeneralLedger::where('reference', 'opening balance')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();

                    if ($existing) {
                        // Update existing entry (even if setting to 0)
                        $existing->debit = $debit;
                        $existing->credit = $credit;
                        $existing->send_date = $today;
                        $existing->save();
                        
                        \Log::info('Updated existing account opening balance', [
                            'account_id' => $accountId,
                            'account_name' => $account->name,
                            'debit' => $debit,
                            'credit' => $credit
                        ]);
                    } else if ($debit > 0 || $credit > 0) {
                        // Only create new entry if there are actual values
                        $entry = new GeneralLedger();
                        $entry->vid = $existingVid ? $existingVid->vid : $newVoucherId;
                        $entry->account = $accountId;
                        $entry->type = 'opening balance';
                        $entry->debit = $debit;
                        $entry->credit = $credit;
                        $entry->ref_id = $accountId;
                        $entry->user_id = 0;
                        $entry->created_by = \Auth::user()->creatorId();
                        $entry->send_date = $today;
                        $entry->reference = 'opening balance';
                        $entry->ref_number = $account->name;
                        $entry->save();
                    }

                }
            }

            // Handle customer balances
            $receivableAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('name', 'Account Receivables')
                ->first();

            foreach ($customers as $customerId => $data) {

                $debit = isset($data['debit']) ? floatval($data['debit']) : 0;
                $credit = isset($data['credit']) ? floatval($data['credit']) : 0;

                if ($receivableAccount) {
                    // Check if this customer already has an opening balance entry
                    $existing = GeneralLedger::where('account', $receivableAccount->id)
                        ->where('reference', 'opening balance')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->where('user_id', $customerId)
                        ->first();

                    // Process if: 1) Has values > 0, OR 2) Already exists in ledger (even if setting to 0)
                    if (($debit > 0 || $credit > 0) || $existing) {
                        $existingVid = GeneralLedger::where('reference', 'opening balance')
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();

                        if ($existing) {
                            // Update existing entry (even if setting to 0)
                            $existing->debit = $debit;
                            $existing->credit = $credit;
                            $existing->send_date = $today;
                            $existing->save();
                            
                            \Log::info('Updated existing customer opening balance', [
                                'customer_id' => $customerId,
                                'account_id' => $receivableAccount->id,
                                'debit' => $debit,
                                'credit' => $credit
                            ]);
                        } else if ($debit > 0 || $credit > 0) {
                            // Only create new entry if there are actual values
                            $entry = new GeneralLedger();
                            $entry->vid = $existingVid ? $existingVid->vid : $newVoucherId;
                            $entry->account = $receivableAccount->id;
                            $entry->type = 'opening balance';
                            $entry->debit = $debit;
                            $entry->credit = $credit;
                            $entry->ref_id = $receivableAccount->id;
                            $entry->user_id = $customerId;
                            $entry->created_by = \Auth::user()->creatorId();
                            $entry->send_date = $today;
                            $entry->reference = 'opening balance';
                            $entry->ref_number = $receivableAccount->name;
                            $entry->save();
                            
                            \Log::info('Created new customer opening balance', [
                                'customer_id' => $customerId,
                                'account_id' => $receivableAccount->id,
                                'debit' => $debit,
                                'credit' => $credit
                            ]);
                        }
                    }
                }
            }

            \Log::info('Processing vendors', ['count' => count($venders)]);

            // Handle venders balances
            $payableAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('name', 'Account Payable')
                ->first();

            foreach ($venders as $venderId => $data) {
                $debit  = isset($data['debit']) ? floatval($data['debit']) : 0;
                $credit = isset($data['credit']) ? floatval($data['credit']) : 0;

                if ($payableAccount) {
                    // Check if this vendor already has an opening balance entry
                    $existing = GeneralLedger::where('account', $payableAccount->id)
                        ->where('reference', 'opening balance')
                        ->where('created_by',\Auth::user()->creatorId())
                        ->where('user_id', $venderId)
                        ->first();

                    // Process if: 1) Has values > 0, OR 2) Already exists in ledger (even if setting to 0)
                    if (($debit > 0 || $credit > 0) || $existing) {
                        $existingVid = GeneralLedger::where('reference', 'opening balance')
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();

                        if ($existing) {
                            // Update existing entry (even if setting to 0)
                            $existing->debit = $debit;
                            $existing->credit = $credit;
                            $existing->send_date = $today;
                            $existing->save();
                            
                            \Log::info('Updated existing vendor opening balance', [
                                'vendor_id' => $venderId,
                                'account_id' => $payableAccount->id,
                                'debit' => $debit,
                                'credit' => $credit
                            ]);
                        } else if ($debit > 0 || $credit > 0) {
                            // Only create new entry if there are actual values
                            $entry = new GeneralLedger();
                            $entry->vid = $existingVid ? $existingVid->vid : $newVoucherId;
                            $entry->account = $payableAccount->id;
                            $entry->type = 'opening balance';
                            $entry->debit = $debit;
                            $entry->credit = $credit;
                            $entry->ref_id = $payableAccount->id;
                            $entry->user_id = $venderId;
                            $entry->created_by = \Auth::user()->creatorId();
                            $entry->send_date = $today;
                            $entry->reference = 'opening balance';
                            $entry->ref_number = $payableAccount->name;
                            $entry->save();
                            
                            \Log::info('Created new vendor opening balance', [
                                'vendor_id' => $venderId,
                                'account_id' => $payableAccount->id,
                                'debit' => $debit,
                                'credit' => $credit
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            \Log::info('Opening balances saved successfully');
            return redirect()->route('chart-of-account.index')->with('success', __('Opening balances saved successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Opening balance submission failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->id
            ]);
            return redirect()->back()->with('error', 'Failed to save opening balances: ' . $e->getMessage());
        }
        
    }

}
