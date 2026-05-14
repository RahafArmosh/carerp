<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\ProductService;
use Illuminate\Http\Request;
use App\Models\GeneralLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
class JournalEntryController extends Controller
{
    /**
     * Normalize amount input from request rows for stable arithmetic.
     */
    private function normalizeJournalAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Support values like "1,234.56" and string payloads.
        $normalized = str_replace(',', '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }


    public function index(\Illuminate\Http\Request $request)
    {
        if (\Auth::user()->can('manage journal entry')) {
            $perPage = (int) $request->get('per_page', 25);
            if (!in_array($perPage, [25, 50, 100, 200], true)) {
                $perPage = 25;
            }

            $journalEntries = JournalEntry::query()
                ->where('created_by', '=', \Auth::user()->creatorId())
                ->with(['accounts', 'currency'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();

            return view('journalEntry.index', compact('journalEntries', 'perPage'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create journal entry')) {
            $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            //            $accounts->prepend('Select Account', '');

            $journalId = $this->journalNumber();
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->whereHas('subProducts')
                ->with(['brand', 'subBrand', 'category', 'subProducts']) // Load sub-products
                ->get()
                ->flatMap(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;

                    // Fetch sub-products and format them
                    return $productService->subProducts->map(function ($subProduct) use ($category, $brand, $subBrand, $productName) {
                        return [
                            'id' => $subProduct->id, // Sub-product ID
                            'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $subProduct->chassis_no,
                        ];
                    });
                })
                ->pluck('name', 'id'); // Convert to key-value array

            $product_services->prepend('--', '');

            $currencies = Currency::orderBy('name')->pluck('name', 'id');
            $currencyRates = Currency::orderBy('name')->pluck('exchange_rate', 'id');

            return view('journalEntry.create', compact('accounts', 'journalId', 'product_services', 'currencies', 'currencyRates'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {


        if (\Auth::user()->can('create journal entry')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'accounts' => 'required',
                    'currency_id' => 'nullable|exists:currencies,id',
                    'currency_rate' => 'nullable|numeric|min:0',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            try {
                DB::beginTransaction();
                $accounts = $request->accounts;

                $totalDebit = 0;
                $totalCredit = 0;
                for ($i = 0; $i < count($accounts); $i++) {
                    $debit = isset($accounts[$i]['debit']) ? $this->normalizeJournalAmount($accounts[$i]['debit']) : 0;
                    $credit = isset($accounts[$i]['credit']) ? $this->normalizeJournalAmount($accounts[$i]['credit']) : 0;
                    $totalDebit += $debit;
                    $totalCredit += $credit;
                }
                // $totalDebit  += $debit;


                if (bccomp((string) round($totalCredit, 2), (string) round($totalDebit, 2), 2) !== 0) {
                    return redirect()->back()->with('error', __('Debit and Credit must be Equal.'));
                }

                $journal = new JournalEntry();
                $journal->journal_id = $this->journalNumber();
                $journal->date = $request->date;
                $journal->reference = $request->reference;
                $journal->description = $request->description;
                $journal->currency_id = $request->filled('currency_id') ? $request->currency_id : null;
                $journal->currency_rate = ($journal->currency_id && $request->filled('currency_rate')) ? (float) $request->currency_rate : null;
                $journal->created_by = \Auth::user()->creatorId();
                
                // Handle attachment upload
                if (!empty($request->attachment)) {
                    $file_path = '/uploads/journal_entries/' . ($journal->attachment ?? '');
                    $image_size = $request->file('attachment')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    
                    if ($result == 1) {
                        $fileName = time() . "_" . $request->attachment->getClientOriginalName();
                        $dir = 'uploads/journal_entries';
                        $path = Utility::upload_file($request, 'attachment', $fileName, $dir, []);
                        if ($path['flag'] == 0) {
                            return redirect()->back()->with('error', __($path['msg']));
                        }
                        $journal->attachment = $fileName;
                    } else {
                        return redirect()->back()->with('error', __('Storage limit exceeded.'));
                    }
                }
                
                $journal->save();

                $currencyIdForAed = $journal->currency_id;
                $journalRateForAed = $journal->currency_rate;

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

                for ($i = 0; $i < count($accounts); $i++) {
                    $journalItem = new JournalItem();
                    $journalItem->journal = $journal->id;
                    $journalItem->account = $accounts[$i]['account'];
                    $journalItem->description = $accounts[$i]['description'];
                    $journalItem->sub_product_id = $accounts[$i]['sub_product_id'] != null ? $accounts[$i]['sub_product_id'] : null;
                    $journalItem->debit = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                    $journalItem->credit = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                    $journalItem->save();

                    $bankAccounts = BankAccount::where('chart_account_id', '=', $accounts[$i]['account'])->get();
                    // if (!empty($bankAccounts)) {
                    //     foreach ($bankAccounts as $bankAccount) {
                    //         $old_balance = $bankAccount->opening_balance;
                    //         if ($journalItem->debit > 0) {
                    //             $new_balance = $old_balance - $this->journalAmountToAed($currencyIdForAed, $journalItem->debit, $journalRateForAed);
                    //         }
                    //         if ($journalItem->credit > 0) {
                    //             $new_balance = $old_balance + $this->journalAmountToAed($currencyIdForAed, $journalItem->credit, $journalRateForAed);
                    //         }
                    //         if (isset($new_balance)) {
                    //             $bankAccount->opening_balance = $new_balance;
                    //             $bankAccount->save();
                    //         }
                    //     }
                    // }
                    if (isset($accounts[$i]['debit'])) {
                        $data = [
                            'account_id' => $accounts[$i]['account'],
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $this->journalAmountToAed($currencyIdForAed, $accounts[$i]['debit'], $journalRateForAed),
                            'reference' => 'Journal',
                            'reference_id' => $journal->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $journal->date,
                        ];
                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $newVoucherId;
                        $purchaseEntry->account = $accounts[$i]['account'];
                        $purchaseEntry->type = \Auth::user()->journalNumberFormat($journal->id);
                        $purchaseEntry->debit = $this->journalAmountToAed($currencyIdForAed, $accounts[$i]['debit'], $journalRateForAed);
                        $purchaseEntry->credit = 0; // Example value
                        $purchaseEntry->ref_id = $journal->id;
                        $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($journal->journal_id);
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->created_by = \Auth::user()->creatorId();
                        $purchaseEntry->balance = 0;
                        // $purchaseEntry->created_at = $journal->date;
                        // $purchaseEntry->updated_at = $journal->date;
                        $purchaseEntry->send_date = $journal->date;
                        $purchaseEntry->reference = 'Journal Entries';
                        $purchaseEntry->save();
                    } else {
                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $newVoucherId;
                        $purchaseEntry->account = $accounts[$i]['account'];
                        $purchaseEntry->type = \Auth::user()->journalNumberFormat($journal->id);
                        $purchaseEntry->debit = 0;
                        $purchaseEntry->credit = $this->journalAmountToAed($currencyIdForAed, $accounts[$i]['credit'], $journalRateForAed); // Example value
                        $purchaseEntry->ref_id = $journal->id;
                        $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($journal->journal_id);
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->created_by = \Auth::user()->creatorId();
                        $purchaseEntry->balance = 0;
                        $purchaseEntry->send_date = $journal->date;
                        $purchaseEntry->reference = 'Journal Entries';
                        $purchaseEntry->save();
                    }


                }

                DB::commit();
                return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully created.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($id)
    {
        if (\Auth::user()->can('show journal entry')) {
            $journalEntry = JournalEntry::withTrashed()->with('currency')->findOrFail($id);
    
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $accounts = $journalEntry->accounts;
                $settings = Utility::settings();
    
                return view('journalEntry.view', compact('journalEntry', 'accounts', 'settings'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function showdelete($id)
    {
        if (\Auth::user()->can('show journal entry')) {
            $journalEntry = JournalEntry::withTrashed()->with('currency')->findOrFail($id);
    
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $accounts = $journalEntry->accountsDelete;
                $settings = Utility::settings();
    
                return view('journalEntry.viewdelete', compact('journalEntry', 'accounts', 'settings'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('edit journal entry')) {
            $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');

            $currencies = Currency::orderBy('name')->pluck('name', 'id');
            $currencyRates = Currency::orderBy('name')->pluck('exchange_rate', 'id');

            return view('journalEntry.edit', compact('accounts', 'journalEntry', 'currencies', 'currencyRates'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('edit journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'accounts' => 'required',
                        'currency_id' => 'nullable|exists:currencies,id',
                        'currency_rate' => 'nullable|numeric|min:0',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $deletedItems = json_decode($request->input('deleted_items'), true);

                if (!empty($deletedItems)) {
                    foreach ($deletedItems as $id) {
                        JournalItem::where('id', $id)->delete(); // or ->forceDelete()
                    }
                }
                $accounts = $request->accounts;

                $totalDebit = 0;
                $totalCredit = 0;
                for ($i = 0; $i < count($accounts); $i++) {
                    $debit = isset($accounts[$i]['debit']) ? $this->normalizeJournalAmount($accounts[$i]['debit']) : 0;
                    $credit = isset($accounts[$i]['credit']) ? $this->normalizeJournalAmount($accounts[$i]['credit']) : 0;
                    $totalDebit += $debit;
                    $totalCredit += $credit;
                }

                if (bccomp((string) round($totalCredit, 2), (string) round($totalDebit, 2), 2) !== 0) {
                    return redirect()->back()->with('error', __('Debit and Credit must be Equal.'));
                }

                $journalEntry->date = $request->date;
                $journalEntry->reference = $request->reference;
                $journalEntry->description = $request->description;
                $journalEntry->currency_id = $request->filled('currency_id') ? $request->currency_id : null;
                $journalEntry->currency_rate = ($journalEntry->currency_id && $request->filled('currency_rate')) ? (float) $request->currency_rate : null;
                $journalEntry->created_by = \Auth::user()->creatorId();
                
                // Handle attachment upload
                if (!empty($request->attachment)) {
                    $file_path = '/uploads/journal_entries/' . ($journalEntry->attachment ?? '');
                    $image_size = $request->file('attachment')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    
                    if ($result == 1) {
                        // Delete old attachment if exists
                        if ($journalEntry->attachment) {
                            Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                            $oldFilePath = 'uploads/journal_entries/' . $journalEntry->attachment;
                            if (Storage::disk('public')->exists($oldFilePath)) {
                                Storage::disk('public')->delete($oldFilePath);
                            }
                        }
                        
                        $fileName = time() . "_" . $request->attachment->getClientOriginalName();
                        $dir = 'uploads/journal_entries';
                        $path = Utility::upload_file($request, 'attachment', $fileName, $dir, []);
                        if ($path['flag'] == 0) {
                            return redirect()->back()->with('error', __($path['msg']));
                        }
                        $journalEntry->attachment = $fileName;
                    } else {
                        return redirect()->back()->with('error', __('Storage limit exceeded.'));
                    }
                }
                
                $journalEntry->save();
                $currencyIdForAed = $journalEntry->currency_id;
                $journalRateForAed = $journalEntry->currency_rate;
                // Delete existing records
                $journal = GeneralLedger::where('ref_id', $journalEntry->id)
                    ->where('type', 'LIKE', '%JUR%')
                    ->where('reference', 'Journal Entries')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();

                if ($journal) {
                    $vid = $journal->id; // or $journal->vid if 'vid' is the column
                    GeneralLedger::where('ref_id', $journalEntry->id)
                    ->where('type', 'LIKE', '%JUR%')
                    ->where('reference', 'Journal Entries')
                    ->where('created_by', \Auth::user()->creatorId())->delete();
                }

                for ($i = 0; $i < count($accounts); $i++) {
                    $journalItem = JournalItem::find($accounts[$i]['id']);
                    // $oldAccount = $journalItem->account;
                    if ($journalItem == null) {
                        $journalItem = new JournalItem();
                        $journalItem->journal = $journalEntry->id;
                    }

                    if (isset($accounts[$i]['account'])) {
                        $journalItem->account = $accounts[$i]['account'];
                    }

                    $journalItem->description = $accounts[$i]['description'];
                    $journalItem->debit = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                    $journalItem->credit = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                    $journalItem->save();


                    $bankAccounts = BankAccount::where('chart_account_id', '=', $accounts[$i]['account'])->get();
                    if (!empty($bankAccounts)) {
                        foreach ($bankAccounts as $bankAccount) {
                            $old_balance = $bankAccount->opening_balance;
                            if ($journalItem->debit > 0) {
                                $new_balance = $old_balance - $this->journalAmountToAed($currencyIdForAed, $journalItem->debit, $journalRateForAed);
                            }
                            if ($journalItem->credit > 0) {
                                $new_balance = $old_balance + $this->journalAmountToAed($currencyIdForAed, $journalItem->credit, $journalRateForAed);
                            }
                            if (isset($new_balance)) {
                                $bankAccount->opening_balance = $new_balance;
                                $bankAccount->save();
                            }
                        }
                    }

                    if (isset($accounts[$i]['debit'])) {

                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $vid;
                        $purchaseEntry->account = $accounts[$i]['account'];
                        $purchaseEntry->type = \Auth::user()->journalNumberFormat($journalEntry->id);
                        $purchaseEntry->debit = $this->journalAmountToAed($currencyIdForAed, $accounts[$i]['debit'], $journalRateForAed);
                        $purchaseEntry->credit = 0; // Example value
                        $purchaseEntry->ref_id = $journalEntry->id;
                        $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($journalEntry->journal_id);
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->created_by = \Auth::user()->creatorId();
                        $purchaseEntry->balance = 0;
                        // $purchaseEntry->created_at = $journal->date;
                        // $purchaseEntry->updated_at = $journal->date;
                        $purchaseEntry->send_date = $journalEntry->date;
                        $purchaseEntry->reference = 'Journal Entries';
                        $purchaseEntry->save();
                    } else {
                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $vid;
                        $purchaseEntry->account = $accounts[$i]['account'];
                        $purchaseEntry->type = \Auth::user()->journalNumberFormat($journalEntry->id);
                        $purchaseEntry->debit = 0;
                        $purchaseEntry->credit = $this->journalAmountToAed($currencyIdForAed, $accounts[$i]['credit'], $journalRateForAed); // Example value
                        $purchaseEntry->ref_id = $journalEntry->id;
                        $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($journalEntry->journal_id);
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->created_by = \Auth::user()->creatorId();
                        $purchaseEntry->balance = 0;
                        $purchaseEntry->send_date = $journalEntry->date;
                        $purchaseEntry->reference = 'Journal Entries';
                        $purchaseEntry->save();
                    }
                }

                return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('delete journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    $deleteDate = Carbon::parse($request->input('delete_date')); // Convert string to Carbon
                    $journal = GeneralLedger::where('ref_id', $journalEntry->id)
                        ->where('type', 'LIKE', '%JUR%')
                        ->where('reference', 'Journal Entries')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();
                    
                    if ($journal && $deleteDate->lt(Carbon::parse($journal->send_date))) {
                        return redirect()->back()->with('error', 'Reverse date must be greater than or equal to the journal date.');
                    }

                    // Get original journal items before deletion
                    $originalJournalItems = JournalItem::where('journal', '=', $journalEntry->id)->get();

                    // Create new reverse journal entry
                    $reverseJournal = new JournalEntry();
                    $reverseJournal->journal_id = $this->journalNumber();
                    $reverseJournal->date = $deleteDate->format('Y-m-d');
                    $reverseJournal->reference = 'Reverse of ' . $journalEntry->reference;
                    $reverseJournal->description = 'Reverse of Journal: ' . \Auth::user()->journalNumberFormat($journalEntry->journal_id) . ' - ' . ($journalEntry->description ?? '');
                    $reverseJournal->currency_id = $journalEntry->currency_id;
                    $reverseJournal->currency_rate = $journalEntry->currency_rate;
                    $reverseJournal->created_by = \Auth::user()->creatorId();
                    $reverseJournal->save();
                    $currencyIdForAed = $reverseJournal->currency_id;
                    $journalRateForAed = $reverseJournal->currency_rate;

                    // Get new voucher ID for general ledger
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

                    // Create reverse journal items and general ledger entries
                    foreach ($originalJournalItems as $originalItem) {
                        // Create reverse journal item (swap debit and credit)
                        $reverseJournalItem = new JournalItem();
                        $reverseJournalItem->journal = $reverseJournal->id;
                        $reverseJournalItem->account = $originalItem->account;
                        $reverseJournalItem->description = $originalItem->description;
                        $reverseJournalItem->sub_product_id = $originalItem->sub_product_id;
                        $reverseJournalItem->debit = $originalItem->credit; // Swap: original credit becomes reverse debit
                        $reverseJournalItem->credit = $originalItem->debit; // Swap: original debit becomes reverse credit
                        $reverseJournalItem->save();

                        // Update bank account balances
                        $bankAccounts = BankAccount::where('chart_account_id', '=', $originalItem->account)->get();
                        if (!empty($bankAccounts)) {
                            foreach ($bankAccounts as $bankAccount) {
                                $old_balance = $bankAccount->opening_balance;
                                if ($reverseJournalItem->debit > 0) {
                                    $new_balance = $old_balance - $this->journalAmountToAed($currencyIdForAed, $reverseJournalItem->debit, $journalRateForAed);
                                }
                                if ($reverseJournalItem->credit > 0) {
                                    $new_balance = $old_balance + $this->journalAmountToAed($currencyIdForAed, $reverseJournalItem->credit, $journalRateForAed);
                                }
                                if (isset($new_balance)) {
                                    $bankAccount->opening_balance = $new_balance;
                                    $bankAccount->save();
                                }
                            }
                        }

                        // Create reverse general ledger entries
                        if ($reverseJournalItem->debit > 0) {
                            $purchaseEntry = new GeneralLedger();
                            $purchaseEntry->vid = $newVoucherId;
                            $purchaseEntry->account = $reverseJournalItem->account;
                            $purchaseEntry->type = \Auth::user()->journalNumberFormat($reverseJournal->id);
                            $purchaseEntry->debit = $this->journalAmountToAed($currencyIdForAed, $reverseJournalItem->debit, $journalRateForAed);
                            $purchaseEntry->credit = 0;
                            $purchaseEntry->ref_id = $reverseJournal->id;
                            $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($reverseJournal->journal_id);
                            $purchaseEntry->user_id = 0;
                            $purchaseEntry->created_by = \Auth::user()->creatorId();
                            $purchaseEntry->balance = 0;
                            $purchaseEntry->reference = 'Reverse Journal Entries';
                            $purchaseEntry->send_date = $deleteDate;
                            $purchaseEntry->save();
                        }

                        if ($reverseJournalItem->credit > 0) {
                            $purchaseEntry = new GeneralLedger();
                            $purchaseEntry->vid = $newVoucherId;
                            $purchaseEntry->account = $reverseJournalItem->account;
                            $purchaseEntry->type = \Auth::user()->journalNumberFormat($reverseJournal->id);
                            $purchaseEntry->debit = 0;
                            $purchaseEntry->credit = $this->journalAmountToAed($currencyIdForAed, $reverseJournalItem->credit, $journalRateForAed);
                            $purchaseEntry->ref_id = $reverseJournal->id;
                            $purchaseEntry->ref_number = \Auth::user()->journalNumberFormat($reverseJournal->journal_id);
                            $purchaseEntry->user_id = 0;
                            $purchaseEntry->created_by = \Auth::user()->creatorId();
                            $purchaseEntry->balance = 0;
                            $purchaseEntry->reference = 'Reverse Journal Entries';
                            $purchaseEntry->send_date = $deleteDate;
                            $purchaseEntry->save();
                        }
                    }

                    // Now delete the original journal entry
                    // $journalEntry->delete();
                    // JournalItem::where('journal', '=', $journalEntry->id)->delete();
                    // TransactionLines::where('reference_id', $journalEntry->id)->where('reference', 'Journal')->delete();

                    DB::commit();
                    return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully reversed.'));
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

    public function destroy(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('delete journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    
                    // Delete journal items
                    JournalItem::where('journal', '=', $journalEntry->id)->forceDelete();

                    // Delete transaction lines
                    TransactionLines::where('reference_id', $journalEntry->id)->where('reference', 'Journal')->delete();
                    
                    // Delete general ledger entries (without creating reverse entries)
                    GeneralLedger::where('ref_id', $journalEntry->id)
                        ->where('type', 'LIKE', '%JUR%')
                        ->where('reference', 'Journal Entries')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->delete();

                    // Delete the journal entry itself
                    $journalEntry->forceDelete();

                    DB::commit();
                    return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully deleted.'));
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

    /**
     * Convert journal line amounts to AED for ledger and bank balances (amount × rate).
     * Uses journal currency_rate when set; otherwise currencies.exchange_rate.
     *
     * @param  int|string|null  $currencyId
     * @param  float|string  $amount
     * @param  float|string|null  $journalCurrencyRate  Rate stored on the journal row (optional)
     */
    private function journalAmountToAed($currencyId, $amount, $journalCurrencyRate = null): float
    {
        $amount = (float) $amount;
        if ($currencyId === null || $currencyId === '') {
            return round($amount, 2);
        }
        $rate = null;
        if ($journalCurrencyRate !== null && $journalCurrencyRate !== '' && (float) $journalCurrencyRate > 0) {
            $rate = (float) $journalCurrencyRate;
        } else {
            $currency = Currency::find($currencyId);
            if (!$currency) {
                return round($amount, 2);
            }
            $rate = (float) ($currency->exchange_rate ?? 0);
        }
        if ($rate <= 0) {
            return round($amount, 2);
        }

        return round($amount * $rate, 2);
    }

    function journalNumber()
    {
        $latest = JournalEntry::where('created_by', '=', \Auth::user()->creatorId())->withTrashed()->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->journal_id + 1;
    }

    public function accountDestroy(Request $request)
    {

        if (\Auth::user()->can('delete journal entry')) {
            JournalItem::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('Journal entry account successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function journalDestroy($item_id)
    {
        if (\Auth::user()->can('delete journal entry')) {
            $journal = JournalItem::find($item_id);
            $journal->delete();

            return redirect()->back()->with('success', __('Journal account successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function duplicate(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('create journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    
                    // Create new journal entry
                    $newJournal = new JournalEntry();
                    $newJournal->journal_id = $this->journalNumber();
                    $newJournal->date = date('Y-m-d'); // Set to current date
                    $newJournal->reference = $journalEntry->reference . ' (Copy)';
                    $newJournal->description = $journalEntry->description;
                    $newJournal->currency_id = $journalEntry->currency_id;
                    $newJournal->currency_rate = $journalEntry->currency_rate;
                    $newJournal->created_by = \Auth::user()->creatorId();
                    $newJournal->save();
                    $currencyIdForAed = $newJournal->currency_id;
                    $journalRateForAed = $newJournal->currency_rate;

                    // Get new voucher ID for general ledger
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    if ($latestVoucher) {
                        $newVoucherId = $latestVoucher->vid + 1;
                    } else {
                        $newVoucherId = 1;
                    }

                    $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();
                    if ($existingRecord) {
                        return redirect()->back()->with('error', __("Something went wrong, please try again."));
                    }

                    // Duplicate journal items
                    foreach ($journalEntry->accounts as $account) {
                        $newJournalItem = new JournalItem();
                        $newJournalItem->journal = $newJournal->id;
                        $newJournalItem->account = $account->account;
                        $newJournalItem->description = $account->description;
                        $newJournalItem->sub_product_id = $account->sub_product_id;
                        $newJournalItem->debit = $account->debit;
                        $newJournalItem->credit = $account->credit;
                        $newJournalItem->save();

                        // Update bank account balances
                        $bankAccounts = BankAccount::where('chart_account_id', '=', $account->account)->get();
                        if (!empty($bankAccounts)) {
                            foreach ($bankAccounts as $bankAccount) {
                                $old_balance = $bankAccount->opening_balance;
                                if ($newJournalItem->debit > 0) {
                                    $new_balance = $old_balance - $this->journalAmountToAed($currencyIdForAed, $newJournalItem->debit, $journalRateForAed);
                                }
                                if ($newJournalItem->credit > 0) {
                                    $new_balance = $old_balance + $this->journalAmountToAed($currencyIdForAed, $newJournalItem->credit, $journalRateForAed);
                                }
                                if (isset($new_balance)) {
                                    $bankAccount->opening_balance = $new_balance;
                                    $bankAccount->save();
                                }
                            }
                        }

                        // Create general ledger entries
                        if ($newJournalItem->debit > 0) {
                            $generalLedger = new GeneralLedger();
                            $generalLedger->vid = $newVoucherId;
                            $generalLedger->account = $account->account;
                            $generalLedger->type = \Auth::user()->journalNumberFormat($newJournal->id);
                            $generalLedger->debit = $this->journalAmountToAed($currencyIdForAed, $newJournalItem->debit, $journalRateForAed);
                            $generalLedger->credit = 0;
                            $generalLedger->ref_id = $newJournal->id;
                            $generalLedger->ref_number = \Auth::user()->journalNumberFormat($newJournal->journal_id);
                            $generalLedger->user_id = 0;
                            $generalLedger->created_by = \Auth::user()->creatorId();
                            $generalLedger->balance = 0;
                            $generalLedger->send_date = $newJournal->date;
                            $generalLedger->reference = 'Journal Entries';
                            $generalLedger->save();
                        } else {
                            $generalLedger = new GeneralLedger();
                            $generalLedger->vid = $newVoucherId;
                            $generalLedger->account = $account->account;
                            $generalLedger->type = \Auth::user()->journalNumberFormat($newJournal->id);
                            $generalLedger->debit = 0;
                            $generalLedger->credit = $this->journalAmountToAed($currencyIdForAed, $newJournalItem->credit, $journalRateForAed);
                            $generalLedger->ref_id = $newJournal->id;
                            $generalLedger->ref_number = \Auth::user()->journalNumberFormat($newJournal->journal_id);
                            $generalLedger->user_id = 0;
                            $generalLedger->created_by = \Auth::user()->creatorId();
                            $generalLedger->balance = 0;
                            $generalLedger->send_date = $newJournal->date;
                            $generalLedger->reference = 'Journal Entries';
                            $generalLedger->save();
                        }
                    }

                    DB::commit();
                    return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully duplicated.'));
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

    public function journal_ledger($journalEntry_id)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $journalEntry_id)
                    ->where('reference', 'Journal Entries')
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();

                $balance = 0;
                $debit = 0;
                $credit = 0;
                $filter['balance'] = $balance;
                $filter['credit'] = $credit;
                $filter['debit'] = $debit;
                $filter['startDateRange'] = $start;
                $filter['endDateRange'] = $end;
                
                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }

    public function downloadAttachment($id)
    {
        if (\Auth::user()->can('show journal entry')) {
            $journalEntry = JournalEntry::findOrFail($id);
            
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                if ($journalEntry->attachment) {
                    $settings = Utility::getStorageSetting();
                    $filePath = 'uploads/journal_entries/' . $journalEntry->attachment;
                    
                    if ($settings['storage_setting'] == 'local') {
                        $file_path = storage_path('app/public/' . $filePath);
                        if (file_exists($file_path)) {
                            return \Response::download(
                                $file_path,
                                $journalEntry->attachment,
                                [
                                    'Content-Length: ' . filesize($file_path),
                                ]
                            );
                        } else {
                            return redirect()->back()->with('error', __('File does not exist.'));
                        }
                    } else {
                        // For cloud storage (wasabi/s3), use Utility::get_file() to get the URL
                        $fileUrl = Utility::get_file($filePath);
                        if (!empty($fileUrl)) {
                            return redirect($fileUrl);
                        } else {
                            return redirect()->back()->with('error', __('File does not exist.'));
                        }
                    }
                } else {
                    return redirect()->back()->with('error', __('No attachment found.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function viewAttachment($id)
    {
        if (!\Auth::user()->can('show journal entry')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $journalEntry = JournalEntry::findOrFail($id);

        if ($journalEntry->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (!$journalEntry->attachment) {
            return redirect()->back()->with('error', __('No attachment found.'));
        }

        $settings = Utility::getStorageSetting();
        $filePath = 'uploads/journal_entries/' . $journalEntry->attachment;

        if ($settings['storage_setting'] == 'local') {
            $file_path = storage_path('app/public/' . $filePath);
            if (!file_exists($file_path)) {
                return redirect()->back()->with('error', __('File does not exist.'));
            }

            // Let the browser try to render it inline (PDF/image/etc.)
            return response()->file($file_path);
        }

        // For cloud storage (wasabi/s3), use Utility::get_file() to get the URL
        $fileUrl = Utility::get_file($filePath);
        if (!empty($fileUrl)) {
            // Browser will handle inline rendering based on content type
            return redirect($fileUrl);
        }

        return redirect()->back()->with('error', __('File does not exist.'));
    }
}
