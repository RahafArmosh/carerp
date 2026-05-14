<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneralLedger;
use App\Models\Pos;
use App\Models\PosProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePosLedgerSubProductId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:update-ledger-sub-product-id 
                            {--dry-run : Run without making changes}
                            {--pos-id= : Update specific POS ID only}
                            {--creator-id= : Update for specific creator ID only (filters POS records)}
                            {--company-id= : Update for specific company/creator ID only (filters GeneralLedger entries)}
                            {--limit= : Limit number of POS records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update POS GeneralLedger entries to add sub_product_id from PosProduct records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $posId = $this->option('pos-id');
        $creatorId = $this->option('creator-id');
        $companyId = $this->option('company-id');
        $limit = $this->option('limit');

        $this->info('Starting POS Ledger sub_product_id update...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all POS records that have GeneralLedger entries without sub_product_id
        // Use a subquery since Pos model doesn't have generalLedgerEntries relationship
        // Note: Table name is 'general_ledger' (singular), not 'general_ledgers'
        $query = Pos::query()
            ->whereExists(function ($q) use ($companyId) {
                $q->select(DB::raw(1))
                  ->from('general_ledger')
                  ->whereColumn('general_ledger.ref_id', 'pos.id')
                  ->whereIn('general_ledger.reference', ['POS', 'POS Payment'])
                  ->whereNull('general_ledger.sub_product_id');
                
                // Filter by company/creator ID if provided (filters GeneralLedger entries)
                if ($companyId) {
                    $q->where('general_ledger.created_by', $companyId);
                }
            });

        if ($posId) {
            $query->where('id', $posId);
            $this->info("Processing POS ID: {$posId}");
        }

        if ($creatorId) {
            $query->where('created_by', $creatorId);
            $this->info("Filtering POS records by creator ID: {$creatorId}");
        }

        if ($companyId) {
            $this->info("Filtering GeneralLedger entries by company/creator ID: {$companyId}");
        }

        if ($limit) {
            $query->limit((int)$limit);
            $this->info("Limiting to {$limit} POS records");
        }

        $posRecords = $query->get();
        $totalPos = $posRecords->count();

        if ($totalPos === 0) {
            $this->info('No POS records found that need updating.');
            return 0;
        }

        $this->info("Found {$totalPos} POS record(s) to process.");

        $progressBar = $this->output->createProgressBar($totalPos);
        $progressBar->start();

        $stats = [
            'pos_processed' => 0,
            'ledger_entries_updated' => 0,
            'ledger_entries_skipped' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($posRecords as $pos) {
                try {
                    // Get all PosProducts for this POS
                    $posProducts = PosProduct::where('pos_id', $pos->id)
                        ->with('sub_product')
                        ->get();

                    if ($posProducts->isEmpty()) {
                        $this->newLine();
                        $this->warn("POS ID {$pos->id} has no PosProduct records. Skipping.");
                        $stats['pos_processed']++;
                        $progressBar->advance();
                        continue;
                    }

                    // Get all GeneralLedger entries for this POS that need updating
                    $ledgerQuery = GeneralLedger::where('ref_id', $pos->id)
                        ->whereIn('reference', ['POS', 'POS Payment'])
                        ->whereNull('sub_product_id');
                    
                    // Filter by company/creator ID if provided
                    if ($companyId) {
                        $ledgerQuery->where('created_by', $companyId);
                    }
                    
                    $ledgerEntries = $ledgerQuery->get();

                    if ($ledgerEntries->isEmpty()) {
                        $stats['pos_processed']++;
                        $progressBar->advance();
                        continue;
                    }

                    $updatedInThisPos = 0;
                    $skippedInThisPos = 0;
                    $processedEntryIds = [];

                    // Strategy: Match ledger entries with PosProducts by account type and amount
                    // For each PosProduct, match all its corresponding ledger entries:
                    // 1. Category sale account entry (credit = sale amount)
                    // 2. Tax entry (credit = tax amount for this item)
                    // 3. Customer entry (debit = sale amount + tax amount)
                    // 4. Purchase account entry (credit = purchase amount, if product type is 'product')
                    // 5. Expense account entry (debit = purchase amount, if product type is 'product')

                    // Get customer and tax account IDs once (shared across all products)
                    $customerAccountId = null;
                    if ($pos->customer_id && $pos->customer_id != 0) {
                        $customer = \App\Models\Customer::find($pos->customer_id);
                        if ($customer) {
                            $customerAccountId = $customer->chart_account_id ?? null;
                        }
                    }

                    $taxAccountId = null;
                    if ($pos->tax_id) {
                        $taxIds = explode(',', $pos->tax_id);
                        $firstTaxId = trim($taxIds[0]);
                        if ($firstTaxId) {
                            $tax = \App\Models\Tax::find($firstTaxId);
                            if ($tax) {
                                $taxAccountId = $tax->chart_account_id ?? null;
                            }
                        }
                    }

                    // First pass: Collect all PosProducts with their calculated amounts and signatures
                    $productSignatures = [];
                    foreach ($posProducts as $posProduct) {
                        if (!$posProduct->sub_product_id) {
                            continue;
                        }

                        // Get product and category to identify account IDs
                        $product = $posProduct->product;
                        if (!$product || !$product->category) {
                            continue;
                        }

                        $category = $product->category;
                        
                        // Get account IDs for this product's category
                        $categorySaleAccountId = $category->sale_account_id ?? null;
                        $categoryPurchaseAccountId = $category->purchase_account_id ?? null;
                        $categoryExpenseAccountId = $category->expense_account_id ?? null;

                        // Calculate sale amount: Use combo_price if combo exists, otherwise use regular price
                        // Then apply discount
                        $basePrice = (float)$posProduct->price;
                        
                        // Use combo_price if combo exists (compo_id != 0 and combo_price is not null)
                        if ($posProduct->compo_id != 0 && $posProduct->compo_id != '0' && $posProduct->combo_price !== null) {
                            $basePrice = (float)$posProduct->combo_price;
                        }
                        
                        // Calculate subtotal: basePrice * quantity
                        $subtotal = $basePrice * (float)$posProduct->quantity;
                        
                        // Apply discount (discount is percentage 0-100)
                        $discount = (float)($posProduct->discount ?? 0);
                        $discountAmount = $subtotal * ($discount / 100);
                        
                        // Sale amount = subtotal - discount
                        $saleAmount = $subtotal - $discountAmount;
                        
                        // Calculate tax amount for this item
                        $taxAmount = 0;
                        if ($pos->tax_id && !empty($pos->tax_id)) {
                            $taxes = \App\Models\Utility::tax($pos->tax_id);
                            foreach ($taxes as $tax) {
                                if ($tax && $tax->rate && $tax->name) {
                                    $taxPrice = \App\Models\Utility::taxRate($tax->rate, $subtotal, 1, $discountAmount);
                                    $taxAmount += $taxPrice;
                                }
                            }
                        }
                        
                        // Calculate purchase amount (only for product type 'product')
                        $purchaseAmount = 0;
                        if ($product->type == 'product') {
                            // Get product cost: use avg_cost if > 0, otherwise use subproduct purchase_price, otherwise product purchase_price
                            $productCost = 0;
                            if ($product->avg_cost > 0) {
                                $productCost = (float)$product->avg_cost;
                            } elseif ($posProduct->sub_product_id) {
                                $subProduct = \App\Models\SubProduct::find($posProduct->sub_product_id);
                                if ($subProduct && $subProduct->purchase_price > 0) {
                                    $productCost = (float)$subProduct->purchase_price;
                                } else {
                                    $productCost = (float)($product->purchase_price ?? 0);
                                }
                            } else {
                                $productCost = (float)($product->purchase_price ?? 0);
                            }
                            
                            // Base purchase amount = product_cost * quantity
                            $purchaseAmount = $productCost * (float)$posProduct->quantity;
                            
                            // Add direct expenses and car accessories if applicable
                            if ($posProduct->sub_product_id && $categoryPurchaseAccountId) {
                                $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $posProduct->sub_product_id)
                                    ->where('chart_account_id', $categoryPurchaseAccountId)
                                    ->whereHas('directExpense', function ($query) use ($pos) {
                                        $query->where('created_by', $pos->created_by);
                                    })
                                    ->sum('amount');
                                
                                $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($posProduct) {
                                        $query->where('car_id', $posProduct->sub_product_id)
                                            ->orWhere('accessory_id', $posProduct->sub_product_id);
                                    })
                                    ->whereHas('request', function ($query) use ($pos) {
                                        $query->where('created_by', $pos->created_by);
                                    })
                                    ->sum('sell_price');
                                
                                $purchaseAmount += $directExpenseAmount + $carAccessoryAmount;
                            }
                        }

                        // Create a unique signature for this product's entry set
                        // This helps identify which entries belong together
                        $signature = sprintf(
                            'sale:%.2f|tax:%.2f|customer:%.2f|purchase:%.2f|expense:%.2f|catSale:%s|catPurchase:%s|catExpense:%s|type:%s',
                            $saleAmount,
                            $taxAmount,
                            $saleAmount + $taxAmount,
                            $purchaseAmount,
                            $purchaseAmount,
                            $categorySaleAccountId ?? 'null',
                            $categoryPurchaseAccountId ?? 'null',
                            $categoryExpenseAccountId ?? 'null',
                            $product->type
                        );

                        $productSignatures[] = [
                            'posProduct' => $posProduct,
                            'product' => $product,
                            'category' => $category,
                            'sub_product_id' => $posProduct->sub_product_id,
                            'saleAmount' => $saleAmount,
                            'taxAmount' => $taxAmount,
                            'purchaseAmount' => $purchaseAmount,
                            'categorySaleAccountId' => $categorySaleAccountId,
                            'categoryPurchaseAccountId' => $categoryPurchaseAccountId,
                            'categoryExpenseAccountId' => $categoryExpenseAccountId,
                            'signature' => $signature,
                        ];
                    }

                    // Second pass: Match each product signature to a unique set of ledger entries
                    // Process products in order, ensuring each ledger entry is only assigned once
                    $tolerance = 0.01; // Allow 0.01 difference for floating point precision
                    
                    // Track which products already have purchase/expense entries matched
                    $productsWithPurchaseExpense = [];
                    
                    foreach ($productSignatures as $productIndex => $productData) {
                        $posProduct = $productData['posProduct'];
                        $product = $productData['product'];
                        $category = $productData['category'];
                        $saleAmount = $productData['saleAmount'];
                        $taxAmount = $productData['taxAmount'];
                        $purchaseAmount = $productData['purchaseAmount'];
                        $categorySaleAccountId = $productData['categorySaleAccountId'];
                        $categoryPurchaseAccountId = $productData['categoryPurchaseAccountId'];
                        $categoryExpenseAccountId = $productData['categoryExpenseAccountId'];
                        $subProductId = $productData['sub_product_id'];

                        // Required entries: category, tax, customer (always)
                        // Optional entries: purchase, expense (only if product type is 'product')
                        $requiredEntries = ['category', 'tax', 'customer'];
                        $optionalEntries = [];
                        if ($product->type == 'product') {
                            $optionalEntries = ['purchase', 'expense'];
                        }

                        // Find ALL matching entries for this product as a complete set
                        // We need to find entries that:
                        // 1. Match the expected account and amount
                        // 2. Are NOT already processed
                        // 3. Form a complete, unique set together
                        
                        $matchingEntries = [];
                        $tempProcessedIds = $processedEntryIds; // Track what we're about to use
                        
                        // 1. Find category sale account entry (credit = sale amount)
                        if ($categorySaleAccountId && $saleAmount > 0) {
                            $categoryEntry = $ledgerEntries->first(function ($entry) use ($categorySaleAccountId, $saleAmount, $tempProcessedIds, $tolerance) {
                                return !in_array($entry->id, $tempProcessedIds) &&
                                       $entry->account == $categorySaleAccountId &&
                                       $entry->credit > 0 &&
                                       abs($entry->credit - $saleAmount) <= $tolerance;
                            });
                            if ($categoryEntry) {
                                $matchingEntries['category'] = $categoryEntry;
                                $tempProcessedIds[] = $categoryEntry->id; // Mark as used for this set
                            }
                        }

                        // 2. Find tax entry (credit = tax amount for this item)
                        if ($taxAccountId && $taxAmount > 0) {
                            $taxEntry = $ledgerEntries->first(function ($entry) use ($taxAccountId, $taxAmount, $tempProcessedIds, $tolerance) {
                                return !in_array($entry->id, $tempProcessedIds) &&
                                       $entry->account == $taxAccountId &&
                                       $entry->credit > 0 &&
                                       abs($entry->credit - $taxAmount) <= $tolerance;
                            });
                            if ($taxEntry) {
                                $matchingEntries['tax'] = $taxEntry;
                                $tempProcessedIds[] = $taxEntry->id; // Mark as used for this set
                            }
                        }

                        // 3. Find customer entry (debit = sale amount + tax amount)
                        if ($customerAccountId && ($saleAmount + $taxAmount) > 0) {
                            $customerEntry = $ledgerEntries->first(function ($entry) use ($customerAccountId, $saleAmount, $taxAmount, $tempProcessedIds, $tolerance) {
                                $expectedDebit = $saleAmount + $taxAmount;
                                return !in_array($entry->id, $tempProcessedIds) &&
                                       $entry->account == $customerAccountId &&
                                       $entry->debit > 0 &&
                                       abs($entry->debit - $expectedDebit) <= $tolerance;
                            });
                            if ($customerEntry) {
                                $matchingEntries['customer'] = $customerEntry;
                                $tempProcessedIds[] = $customerEntry->id; // Mark as used for this set
                            }
                        }

                        // 4. Find purchase account entry (credit = purchase amount, if product type is 'product')
                        // IMPORTANT: Only look for purchase entries if purchase_amount > 0
                        // If purchase_amount is 0, these entries don't exist in the ledger
                        if ($product->type == 'product' && $categoryPurchaseAccountId && $purchaseAmount > 0) {
                            $purchaseEntry = $ledgerEntries->first(function ($entry) use ($categoryPurchaseAccountId, $purchaseAmount, $tempProcessedIds, $tolerance) {
                                return !in_array($entry->id, $tempProcessedIds) &&
                                       $entry->account == $categoryPurchaseAccountId &&
                                       $entry->credit > 0 &&
                                       abs($entry->credit - $purchaseAmount) <= $tolerance;
                            });
                            if ($purchaseEntry) {
                                $matchingEntries['purchase'] = $purchaseEntry;
                                $tempProcessedIds[] = $purchaseEntry->id; // Mark as used for this set
                            } else {
                                // Log why purchase entry wasn't found (only if purchase_amount > 0)
                                $availablePurchaseEntries = $ledgerEntries->filter(function ($entry) use ($categoryPurchaseAccountId, $tempProcessedIds) {
                                    return !in_array($entry->id, $tempProcessedIds) &&
                                           $entry->account == $categoryPurchaseAccountId &&
                                           $entry->credit > 0;
                                });
                                Log::debug("Purchase entry not found for product", [
                                    'pos_id' => $pos->id,
                                    'product_index' => $productIndex,
                                    'sub_product_id' => $subProductId,
                                    'expected_purchase_amount' => $purchaseAmount,
                                    'category_purchase_account_id' => $categoryPurchaseAccountId,
                                    'available_purchase_entries' => $availablePurchaseEntries->map(function($e) {
                                        return ['id' => $e->id, 'credit' => $e->credit, 'account' => $e->account];
                                    })->toArray(),
                                    'processed_entry_ids' => $tempProcessedIds,
                                ]);
                            }
                        }

                        // 5. Find expense account entry (debit = purchase amount, if product type is 'product')
                        // IMPORTANT: Only look for expense entries if purchase_amount > 0
                        // If purchase_amount is 0, these entries don't exist in the ledger
                        if ($product->type == 'product' && $categoryExpenseAccountId && $purchaseAmount > 0) {
                            $expenseEntry = $ledgerEntries->first(function ($entry) use ($categoryExpenseAccountId, $purchaseAmount, $tempProcessedIds, $tolerance) {
                                return !in_array($entry->id, $tempProcessedIds) &&
                                       $entry->account == $categoryExpenseAccountId &&
                                       $entry->debit > 0 &&
                                       abs($entry->debit - $purchaseAmount) <= $tolerance;
                            });
                            if ($expenseEntry) {
                                $matchingEntries['expense'] = $expenseEntry;
                                $tempProcessedIds[] = $expenseEntry->id; // Mark as used for this set
                            } else {
                                // Log why expense entry wasn't found
                                $availableExpenseEntries = $ledgerEntries->filter(function ($entry) use ($categoryExpenseAccountId, $purchaseAmount, $tempProcessedIds, $tolerance) {
                                    return !in_array($entry->id, $tempProcessedIds) &&
                                           $entry->account == $categoryExpenseAccountId &&
                                           $entry->debit > 0;
                                });
                                Log::debug("Expense entry not found for product", [
                                    'pos_id' => $pos->id,
                                    'product_index' => $productIndex,
                                    'sub_product_id' => $subProductId,
                                    'expected_purchase_amount' => $purchaseAmount,
                                    'category_expense_account_id' => $categoryExpenseAccountId,
                                    'available_expense_entries' => $availableExpenseEntries->map(function($e) {
                                        return ['id' => $e->id, 'debit' => $e->debit, 'account' => $e->account];
                                    })->toArray(),
                                    'processed_entry_ids' => $tempProcessedIds,
                                ]);
                            }
                        }
                        
                        // Check if all required entries are found
                        $allRequiredFound = true;
                        foreach ($requiredEntries as $entryType) {
                            if (!isset($matchingEntries[$entryType])) {
                                $allRequiredFound = false;
                                break;
                            }
                        }
                        
                        // Check if all optional entries are found (if product type is 'product')
                        // IMPORTANT: For products, purchase/expense entries are optional but preferred
                        // If purchase amount is 0, purchase/expense entries won't exist, so don't require them
                        // If we can't find them when purchase amount > 0, we should still assign the required entries (category, tax, customer)
                        // This handles cases where purchase/expense entries might have been matched to another product
                        // or where the amounts don't match exactly
                        $allOptionalFound = true;
                        $missingOptionalEntries = [];
                        if ($product->type == 'product') {
                            // Only require purchase/expense entries if purchase amount > 0
                            // If purchase amount is 0, these entries won't exist in the ledger
                            foreach ($optionalEntries as $entryType) {
                                if (!isset($matchingEntries[$entryType])) {
                                    // Only mark as missing if purchase amount > 0
                                    // If purchase amount is 0, these entries are not expected to exist
                                    if ($purchaseAmount > 0) {
                                        $allOptionalFound = false;
                                        $missingOptionalEntries[] = $entryType;
                                    }
                                    // If purchase amount is 0, don't mark as missing (they're not expected)
                                }
                            }
                        } else {
                            // If not product type, optional entries are not required
                            $allOptionalFound = true;
                        }
                        
                        // Assign if we found all required entries
                        // For products, we prefer to have optional entries too, but we'll assign required entries even if optional are missing
                        // This ensures each product gets matched to at least its required entries
                        if ($allRequiredFound) {
                            // If product type is 'product' and we're missing optional entries (and purchase amount > 0), log a warning but still proceed
                            // If purchase amount is 0, purchase/expense entries are not expected, so don't log a warning
                            if ($product->type == 'product' && !$allOptionalFound && $purchaseAmount > 0 && !empty($missingOptionalEntries)) {
                                Log::warning("Product has required entries but missing optional purchase/expense entries", [
                                    'pos_id' => $pos->id,
                                    'product_index' => $productIndex,
                                    'sub_product_id' => $subProductId,
                                    'missing_optional' => $missingOptionalEntries,
                                    'found_entries' => array_keys($matchingEntries),
                                    'purchase_amount' => $purchaseAmount,
                                    'category_purchase_account_id' => $categoryPurchaseAccountId,
                                    'category_expense_account_id' => $categoryExpenseAccountId,
                                ]);
                            }
                            // CRITICAL: Verify that ALL entry IDs in this set are unique
                            $entryIds = [];
                            $hasDuplicate = false;
                            foreach ($matchingEntries as $entry) {
                                if (in_array($entry->id, $entryIds)) {
                                    // Duplicate entry found within this set, skip
                                    $hasDuplicate = true;
                                    break;
                                }
                                $entryIds[] = $entry->id;
                            }
                            
                            // Only assign if no duplicates and all entries form a complete unique set
                            if (!$hasDuplicate) {
                                // Assign sub_product_id to all matching entries
                                $entryIdsAssigned = [];
                                $hasPurchaseExpenseEntries = false;
                                foreach ($matchingEntries as $entryType => $entry) {
                                    if (!$dryRun) {
                                        $entry->sub_product_id = $subProductId;
                                        $entry->save();
                                    }
                                    $updatedInThisPos++;
                                    $processedEntryIds[] = $entry->id; // Mark as permanently processed
                                    $entryIdsAssigned[] = $entry->id;
                                    
                                    // Track if this product has purchase/expense entries
                                    if ($entryType == 'purchase' || $entryType == 'expense') {
                                        $hasPurchaseExpenseEntries = true;
                                    }
                                }
                                
                                // Store whether this product has purchase/expense entries
                                $productsWithPurchaseExpense[$subProductId] = $hasPurchaseExpenseEntries;
                                
                                // Log successful assignment for debugging
                                if (!$dryRun) {
                                    Log::info("Assigned sub_product_id {$subProductId} to ledger entries", [
                                        'pos_id' => $pos->id,
                                        'product_index' => $productIndex,
                                        'sub_product_id' => $subProductId,
                                        'entry_ids' => $entryIdsAssigned,
                                        'entry_types' => array_keys($matchingEntries),
                                        'sale_amount' => $saleAmount,
                                        'tax_amount' => $taxAmount,
                                        'purchase_amount' => $purchaseAmount,
                                        'has_purchase_expense' => $hasPurchaseExpenseEntries,
                                    ]);
                                }
                            } else {
                                // Log when a set is skipped due to duplicates
                                Log::warning("Skipped assigning sub_product_id due to duplicate entries", [
                                    'pos_id' => $pos->id,
                                    'product_index' => $productIndex,
                                    'sub_product_id' => $subProductId,
                                    'matching_entry_ids' => array_map(function($e) { return $e->id; }, $matchingEntries),
                                ]);
                            }
                        } else {
                            // Log when required entries are missing (this is a problem)
                            $missingRequired = array_diff($requiredEntries, array_keys($matchingEntries));
                            if (!empty($missingRequired)) {
                                Log::warning("Could not find required entries for product", [
                                    'pos_id' => $pos->id,
                                    'product_index' => $productIndex,
                                    'sub_product_id' => $subProductId,
                                    'missing_required' => $missingRequired,
                                    'found_entries' => array_keys($matchingEntries),
                                    'sale_amount' => $saleAmount,
                                    'tax_amount' => $taxAmount,
                                    'purchase_amount' => $purchaseAmount,
                                ]);
                            }
                        }
                    }

                    // Handle remaining entries - match them to the correct product based on account IDs
                    // Purchase/expense entries with 0 amounts should be matched to their corresponding products
                    // IMPORTANT: Prefer products that don't already have purchase/expense entries matched
                    $remainingEntries = $ledgerEntries->filter(function ($entry) use ($processedEntryIds) {
                        // Skip already processed entries
                        if (in_array($entry->id, $processedEntryIds)) {
                            return false;
                        }
                        
                        // Skip payment entries
                        return strpos($entry->type, 'Payment') === false;
                    });

                    if ($remainingEntries->isNotEmpty()) {
                        // Try to match remaining entries to products based on account IDs
                        foreach ($remainingEntries as $entry) {
                            $matchedSubProductId = null;
                            $matchingProducts = []; // Store all products that match by account
                            
                            // Find all products that match by account ID
                            foreach ($productSignatures as $productData) {
                                $product = $productData['product'];
                                $categoryPurchaseAccountId = $productData['categoryPurchaseAccountId'];
                                $categoryExpenseAccountId = $productData['categoryExpenseAccountId'];
                                $subProductId = $productData['sub_product_id'];
                                
                                // Match purchase account entry (credit = purchase amount, but could be 0)
                                if ($product->type == 'product' && 
                                    $categoryPurchaseAccountId && 
                                    $entry->account == $categoryPurchaseAccountId &&
                                    $entry->credit >= 0) { // Allow 0 credit for purchase entries
                                    $matchingProducts[] = [
                                        'sub_product_id' => $subProductId,
                                        'has_purchase_expense' => $productsWithPurchaseExpense[$subProductId] ?? false,
                                        'purchase_amount' => $productData['purchaseAmount'],
                                        'product_index' => array_search($productData, array_values($productSignatures)),
                                    ];
                                }
                                
                                // Match expense account entry (debit = purchase amount, but could be 0)
                                if ($product->type == 'product' && 
                                    $categoryExpenseAccountId && 
                                    $entry->account == $categoryExpenseAccountId &&
                                    $entry->debit >= 0) { // Allow 0 debit for expense entries
                                    $matchingProducts[] = [
                                        'sub_product_id' => $subProductId,
                                        'has_purchase_expense' => $productsWithPurchaseExpense[$subProductId] ?? false,
                                        'purchase_amount' => $productData['purchaseAmount'],
                                        'product_index' => array_search($productData, array_values($productSignatures)),
                                    ];
                                }
                            }
                            
                            // Prefer products that don't already have purchase/expense entries
                            // If multiple products match, prefer the one without purchase/expense entries
                            if (!empty($matchingProducts)) {
                                // Sort: products without purchase/expense first, then by purchase_amount (0 first)
                                usort($matchingProducts, function($a, $b) {
                                    // First priority: products without purchase/expense entries
                                    if ($a['has_purchase_expense'] != $b['has_purchase_expense']) {
                                        return $a['has_purchase_expense'] ? 1 : -1;
                                    }
                                    // Second priority: products with purchase_amount = 0 (these are the ones needing the entries)
                                    if ($a['purchase_amount'] == 0 && $b['purchase_amount'] > 0) {
                                        return -1;
                                    }
                                    if ($a['purchase_amount'] > 0 && $b['purchase_amount'] == 0) {
                                        return 1;
                                    }
                                    return 0;
                                });
                                
                                $matchedSubProductId = $matchingProducts[0]['sub_product_id'];
                            }
                            
                            // If we couldn't match by account, use first product's sub_product_id as fallback
                            if (!$matchedSubProductId) {
                                $firstPosProduct = $posProducts->first();
                                if ($firstPosProduct && $firstPosProduct->sub_product_id) {
                                    $matchedSubProductId = $firstPosProduct->sub_product_id;
                                }
                            }
                            
                            if ($matchedSubProductId) {
                                if (!$dryRun) {
                                    $entry->sub_product_id = $matchedSubProductId;
                                    $entry->save();
                                }
                                $updatedInThisPos++;
                                $processedEntryIds[] = $entry->id;
                                
                                Log::debug("Matched remaining entry to product", [
                                    'pos_id' => $pos->id,
                                    'entry_id' => $entry->id,
                                    'entry_account' => $entry->account,
                                    'entry_debit' => $entry->debit,
                                    'entry_credit' => $entry->credit,
                                    'matched_sub_product_id' => $matchedSubProductId,
                                    'matching_products' => $matchingProducts,
                                ]);
                            } else {
                                $skippedInThisPos++;
                            }
                        }
                    }

                    // Count skipped payment entries (they don't need sub_product_id)
                    $paymentEntries = $ledgerEntries->filter(function ($entry) use ($processedEntryIds) {
                        return !in_array($entry->id, $processedEntryIds) && 
                               strpos($entry->type, 'Payment') !== false;
                    });
                    $skippedInThisPos += $paymentEntries->count();

                    $stats['ledger_entries_updated'] += $updatedInThisPos;
                    $stats['ledger_entries_skipped'] += $skippedInThisPos;
                    $stats['pos_processed']++;

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->newLine();
                    $this->error("Error processing POS ID {$pos->id}: " . $e->getMessage());
                    Log::error("Error updating POS ledger sub_product_id for POS {$pos->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $progressBar->advance();
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine(2);
                $this->info('DRY RUN COMPLETE - No changes were made');
            } else {
                DB::commit();
                $this->newLine(2);
                $this->info('Update completed successfully!');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine(2);
            $this->error('Fatal error: ' . $e->getMessage());
            Log::error('Fatal error updating POS ledger sub_product_id', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $progressBar->finish();

        // Display statistics
        $this->newLine(2);
        $this->info('=== Update Statistics ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['POS Records Processed', $stats['pos_processed']],
                ['Ledger Entries Updated', $stats['ledger_entries_updated']],
                ['Ledger Entries Skipped', $stats['ledger_entries_skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        return 0;
    }
}
