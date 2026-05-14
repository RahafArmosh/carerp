<?php

namespace App\Imports;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\ChartOfAccount;
use App\Models\MasterlistLeadger;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InvoiceImport implements ToArray
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Parse date from various formats (Excel numeric, string, etc.)
     */
    private function parseDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        // If it's a numeric value (Excel date serial number)
        if (is_numeric($dateValue)) {
            try {
                return Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse Excel date', [
                    'value' => $dateValue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If it's a string, try to parse it
        if (is_string($dateValue)) {
            // Try common date formats
            $formats = [
                'Y-m-d',
                'Y/m/d',
                'd-m-Y',
                'd/m/Y',
                'm-d-Y',
                'm/d/Y',
                'Y-m-d H:i:s',
                'Y/m/d H:i:s'
            ];

            foreach ($formats as $format) {
                try {
                    $date = \DateTime::createFromFormat($format, $dateValue);
                    if ($date && $date->format($format) === $dateValue) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon's flexible parsing
            try {
                return \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date string', [
                    'value' => $dateValue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If all else fails, return current date
        \Log::warning('Using current date as fallback', ['original_value' => $dateValue]);
        return date('Y-m-d');
    }

    public function array(array $data)
    {
        try {
            // Increase memory limit and execution time for large imports
            ini_set('memory_limit', '2048M');
            set_time_limit(3600); // 1 hour max
            
            // Disable query logging for better performance
            DB::connection()->disableQueryLog();

            // Validate required data structure
            if (count($data) < 4) {
                throw new \Exception('Invalid file format. File must have at least 4 rows.');
            }

            $invoiceHeader = $data[0];
            $invoiceData = $data[1];
            $productHeader = $data[2];
            $productRows = array_slice($data, 3);

            \Log::info('Invoice import started', [
                'user_id' => $this->userId,
                'product_rows_count' => count($productRows)
            ]);

            // Get next invoice number
            $lastInvoice = Invoice::where('created_by', $this->userId)
                ->withTrashed()
                ->latest()
                ->first();
            
            $invoiceNumber = $lastInvoice ? ($lastInvoice->invoice_id + 1) : 1;

            // Validate customer exists
            $customerIdIndex = array_search('customer_id', $invoiceHeader);
            if ($customerIdIndex === false) {
                throw new \Exception('customer_id column not found in invoice headers.');
            }
            
            $customerId = $invoiceData[$customerIdIndex];
            $customer = Customer::where('id', $customerId)
                ->where('created_by', $this->userId)
                ->first();
            
            if (!$customer) {
                throw new \Exception('Customer not found or not accessible.');
            }

            // Create invoice
            $invoice = new Invoice();
            $invoice->invoice_id = $invoiceNumber;
            $invoice->created_by = $this->userId;
            $invoice->status = 0;
            $invoice->type = 'regular';

            // Map invoice data
            foreach ($invoiceHeader as $index => $header) {
                $value = $invoiceData[$index] ?? null;
                
                if ($header == 'customer_id') {
                    $invoice->customer_id = $value;
                } elseif ($header == 'Issue Date' || $header == 'issue_date') {
                    $invoice->issue_date = $this->parseDate($value);
                } elseif ($header == 'Due Date' || $header == 'due_date') {
                    $invoice->due_date = $this->parseDate($value);
                } elseif ($header == 'category_id') {
                    $invoice->category_id = $value;
                } elseif ($header == 'salesman_id') {
                    $invoice->salesman_id = $value ?: $this->userId;
                } elseif ($header == 'tax_id') {
                    // Handle tax_id - can be single value, comma-separated string, or array
                    if (is_array($value)) {
                        $invoice->tax_id = implode(',', array_filter($value));
                    } elseif (is_string($value) && strpos($value, ',') !== false) {
                        $invoice->tax_id = $value;
                    } else {
                        $invoice->tax_id = $value ?: '';
                    }
                } elseif ($header == 'currency_id') {
                    if (!empty($value)) {
                        $currencyExists = DB::table('currencies')->where('id', $value)->exists();
                        $invoice->currency_id = $currencyExists ? $value : null;
                    } else {
                        $invoice->currency_id = null;
                    }
                } elseif ($header == 'exchange_rate') {
                    $invoice->exchange_rate = $value ?: 1;
                } elseif ($header == 'Bank_id' || $header == 'bank_account_id') {
                    $invoice->bank_account_id = $value;
                }
            }

            // Set default discount account if not provided
            $discountAccount = ChartOfAccount::where('created_by', $this->userId)
                ->where('name', 'Discounts Allowed')
                ->first();

            if (!$discountAccount) {
                // Create discount account if it doesn't exist (similar to store method)
                $expenseType = \App\Models\ChartOfAccountType::where('created_by', $this->userId)
                    ->where('name', 'Expenses')
                    ->first();
                
                if (!$expenseType) {
                    $expenseType = \App\Models\ChartOfAccountType::create([
                        'name' => 'Costs of Goods Sold',
                        'created_by' => $this->userId,
                    ]);
                }

                $gaSubType = \App\Models\ChartOfAccountSubType::where('type', $expenseType->id)
                    ->where('name', 'General and Administrative expenses')
                    ->first();
                
                if (!$gaSubType) {
                    $gaSubType = \App\Models\ChartOfAccountSubType::create([
                        'name' => 'Costs of Goods Sold',
                        'type' => $expenseType->id
                    ]);
                }

                $discountAccount = ChartOfAccount::create([
                    'code' => '5060',
                    'name' => 'Discounts Allowed',
                    'type' => $expenseType->id,
                    'sub_type' => $gaSubType->id,
                    'is_enabled' => 1,
                    'created_by' => $this->userId,
                ]);
            }

            $invoice->discount_account_id = $discountAccount->id;
            
            // Create invoice in a transaction
            DB::beginTransaction();
            try {
                $invoice->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            \Log::info('Invoice created', ['invoice_id' => $invoice->id, 'invoice_number' => $invoiceNumber]);

            // Normalize product headers by trimming whitespace (Excel often has trailing spaces)
            $normalizedHeaders = array_map('trim', $productHeader);
            
            // Get column indices for product data (search in normalized headers)
            $productNoIndex = array_search('product_no', $normalizedHeaders);
            $warehouseIdIndex = array_search('warehouse_id', $normalizedHeaders);
            $qtyIndex = array_search('qty', $normalizedHeaders);
            $priceIndex = array_search('price', $normalizedHeaders);
            $discountIndex = array_search('discount', $normalizedHeaders);
            
            // Log column positions for debugging
            \Log::info('Invoice import column indices', [
                'product_header' => $productHeader,
                'normalized_headers' => $normalizedHeaders,
                'product_no_index' => $productNoIndex,
                'warehouse_id_index' => $warehouseIdIndex,
                'qty_index' => $qtyIndex,
                'price_index' => $priceIndex,
                'discount_index' => $discountIndex
            ]);

            if ($productNoIndex === false) {
                throw new \Exception('product_no column not found in product headers.');
            }
            if ($qtyIndex === false) {
                throw new \Exception('qty column not found in product headers.');
            }
            if ($priceIndex === false) {
                throw new \Exception('price column not found in product headers.');
            }

            // Pre-load currency for conversion
            $currency = $invoice->currency_id ? Currency::find($invoice->currency_id) : null;
            $exchangeRate = $invoice->exchange_rate ?: ($currency ? $currency->exchange_rate : 1);

            // Pre-load all products and categories in bulk to avoid N+1 queries
            // Normalize product_nos: trim whitespace and convert to uppercase for consistent comparison
            $productNos = array_filter(array_map(function($row) use ($productNoIndex) {
                $value = !empty($row[$productNoIndex]) ? trim($row[$productNoIndex]) : null;
                return $value ? strtoupper($value) : null;
            }, $productRows));
            
            // Get unique product_nos and pre-load their sub-products info
            $productNos = array_unique($productNos);
            
            // Get user's creatorId for filtering (calculate once)
            $user = \App\Models\User::find($this->userId);
            $creatorId = $user ? $user->creatorId() : $this->userId;
            
            // Collect warehouse_ids from all rows to filter sub-products if all rows specify the same warehouse
            $warehouseIdsFromRows = [];
            if ($warehouseIdIndex !== false) {
                foreach ($productRows as $row) {
                    if (isset($row[$warehouseIdIndex]) && !empty(trim($row[$warehouseIdIndex] ?? ''))) {
                        $whId = trim($row[$warehouseIdIndex]);
                        if (is_numeric($whId)) {
                            $warehouseIdsFromRows[] = (int)$whId;
                        }
                    }
                }
                $warehouseIdsFromRows = array_unique($warehouseIdsFromRows);
            }
            
            // Query sub-products with case-insensitive product_no comparison
            // Normalize product_nos for the query (already normalized to uppercase above)
            $normalizedProductNos = array_map('strtoupper', array_map('trim', $productNos));
            
            $subProductsQuery = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) IN (' . implode(',', array_fill(0, count($normalizedProductNos), '?')) . ')', $normalizedProductNos)
                ->where('flag', '!=', 2)
                ->where('booked', 0)
                ->where('quantity', '>', 0)
                ->where('created_by', $creatorId);
            
            // If all rows specify warehouse_id(s), optionally filter by them for better performance
            // But we still load all warehouses to allow per-row warehouse filtering
            // (We'll filter per-row in the processing loop)
            
            $subProducts = $subProductsQuery
                ->select('id', 'chassis_no', 'product_id', 'quantity', 'warehouse_id', 'created_at')
                ->orderBy('chassis_no')
                ->orderBy('warehouse_id') // Group by warehouse for better organization
                ->orderBy('created_at', 'asc') // FIFO: oldest first within each warehouse
                ->get();
            
            // Group by normalized (uppercase) product_no and warehouse_id for consistent lookup
            $subProductsCache = $subProducts->groupBy(function($item) {
                $productNo = strtoupper(trim($item->product_no));
                // Handle NULL warehouse_id by converting to string 'null' for consistent cache key
                // Normalize warehouse_id to string (convert int to string, handle null)
                $warehouseId = $item->warehouse_id !== null ? (string)(int)$item->warehouse_id : 'null';
                return $productNo . '|' . $warehouseId;
            });
            
            // Pre-load products (filter out null product_ids and filter by created_by)
            $productIds = $subProductsCache->pluck('product_id')
                ->filter(function($id) {
                    return !empty($id);
                })
                ->unique()
                ->toArray();
            
            $productsCache = collect();
            if (!empty($productIds)) {
                $productsCache = ProductService::whereIn('id', $productIds)
                    ->where('created_by', $creatorId)
                    ->with('category:id,name,type')
                    ->get()
                    ->keyBy('id');
            }
            
            \Log::info('Pre-loaded data for import', [
                'unique_product_nos' => count($productNos),
                'sub_products_found' => $subProductsCache->sum(function($group) {
                    return $group->count();
                }),
                'products_loaded' => $productsCache->count(),
                'product_ids' => count($productIds),
                'user_id' => $this->userId,
                'creator_id' => $creatorId
            ]);

            // Process products in chunks to avoid memory issues
            $chunkSize = 500; // Process 500 rows at a time
            $chunks = array_chunk($productRows, $chunkSize, true);
            $errorArray = [];
            $totalProcessed = 0;
            
            \Log::info('Starting chunked processing', [
                'total_rows' => count($productRows),
                'chunk_size' => $chunkSize,
                'total_chunks' => count($chunks)
            ]);

            foreach ($chunks as $chunkIndex => $chunk) {
                \Log::info("Processing chunk " . ($chunkIndex + 1) . " of " . count($chunks), [
                    'chunk_size' => count($chunk),
                    'total_processed' => $totalProcessed,
                    'total_rows' => count($productRows)
                ]);

                // Process each chunk in a separate transaction
                DB::beginTransaction();
                
                try {
                    $chunkErrors = [];
                    $invoiceProductsToInsert = [];
                    $subProductUpdates = [];
                    $subProductIdsToUpdate = [];
                    
                    foreach ($chunk as $rowIndex => $productRow) {
                        try {
                            // Skip empty rows
                            if (empty($productRow[$productNoIndex])) {
                                continue;
                            }

                    // Normalize product_no: trim and convert to uppercase for consistent comparison
                    $productNo = strtoupper(trim($productRow[$productNoIndex]));
                    // Only read warehouse_id if the column exists in the header
                    $warehouseId = null;
                    if ($warehouseIdIndex !== false && isset($productRow[$warehouseIdIndex])) {
                        $warehouseIdValue = trim($productRow[$warehouseIdIndex] ?? '');
                        // Validate that warehouse_id is numeric and not the same as product_no (common mistake)
                        if ($warehouseIdValue !== '' && $warehouseIdValue !== $productNo && is_numeric($warehouseIdValue)) {
                            // Normalize warehouse_id to integer for consistent comparison (remove leading zeros, etc.)
                            $warehouseId = (string)(int)$warehouseIdValue; // Convert to int then back to string for cache key
                            
                            \Log::info('Reading warehouse_id from Excel', [
                                'row' => $rowIndex + 4,
                                'product_no' => $productNo,
                                'warehouse_id_raw' => $warehouseIdValue,
                                'warehouse_id_normalized' => $warehouseId
                            ]);
                        } elseif ($warehouseIdValue === $productNo) {
                            // If warehouse_id equals product_no, treat it as empty (user probably copied wrong column)
                            \Log::warning('warehouse_id equals product_no, treating as empty', [
                                'row' => $rowIndex + 4,
                                'product_no' => $productNo,
                                'warehouse_id_value' => $warehouseIdValue
                            ]);
                            $warehouseId = null;
                        } elseif ($warehouseIdValue === '') {
                            // Empty warehouse_id - will search all warehouses
                            $warehouseId = null;
                        }
                    } else {
                        // warehouse_id column doesn't exist in Excel - will search all warehouses
                        $warehouseId = null;
                    }
                    $qty = (float)($productRow[$qtyIndex] ?? 0);
                    $price = (float)($productRow[$priceIndex] ?? 0);
                    $discount = (float)($productRow[$discountIndex] ?? 0);

                            if ($qty <= 0) {
                                $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Quantity must be greater than 0 for product_no: {$productNo}";
                                continue;
                            }

                    // Get sub-products from cache (filtered by warehouse_id if provided)
                    $availableSubProducts = collect();
                    
                    if ($warehouseId) {
                        // STRICT: Only use products from the specified warehouse - NO FALLBACK to other warehouses
                        $cacheKey = $productNo . '|' . $warehouseId;
                        $warehouseSubProducts = $subProductsCache->get($cacheKey, collect());
                        
                        if ($warehouseSubProducts->isEmpty()) {
                            // Check if product exists in other warehouses for better error message
                            $allWarehousesForProduct = $subProductsCache->filter(function($group, $key) use ($productNo) {
                                return strpos($key, $productNo . '|') === 0;
                            });
                            
                            if ($allWarehousesForProduct->isEmpty()) {
                                // Double-check by querying database directly for debugging
                                $dbCheck = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [$productNo])
                                    ->where('created_by', $creatorId)
                                    ->where('flag', '!=', 2)
                                    ->where('booked', 0)
                                    ->where('quantity', '>', 0)
                                    ->select('id', 'chassis_no', 'warehouse_id', 'quantity', 'booked')
                                    ->get();
                                
                                \Log::warning('Product not found in specified warehouse', [
                                    'row' => $rowIndex + 4,
                                    'product_no' => $productNo,
                                    'warehouse_id_requested' => $warehouseId,
                                    'db_results' => $dbCheck->toArray(),
                                    'cache_keys_for_product' => $allWarehousesForProduct->keys()->toArray()
                                ]);
                                
                                $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Sub-product with product_no '{$productNo}' not found or not available in warehouse '{$warehouseId}'.";
                                continue;
                            } else {
                                // Product exists but NOT in the specified warehouse - STRICT: Do not use it
                                $availableWarehouses = $allWarehousesForProduct->keys()->map(function($key) {
                                    $parts = explode('|', $key);
                                    return $parts[1] === 'null' ? '(no warehouse)' : $parts[1];
                                })->implode(', ');
                                
                                \Log::warning('Product found in different warehouse - NOT using it (strict warehouse filter)', [
                                    'row' => $rowIndex + 4,
                                    'product_no' => $productNo,
                                    'warehouse_id_requested' => $warehouseId,
                                    'available_warehouses' => $availableWarehouses
                                ]);
                                
                                $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Sub-product with product_no '{$productNo}' not found in warehouse '{$warehouseId}'. Product is available in warehouses: {$availableWarehouses}. Please use the correct warehouse_id.";
                                continue;
                            }
                        } else {
                            // Found products in the specified warehouse - use ONLY these
                            $availableSubProducts = $warehouseSubProducts;
                            
                            \Log::info('Using products from specified warehouse', [
                                'row' => $rowIndex + 4,
                                'product_no' => $productNo,
                                'warehouse_id' => $warehouseId,
                                'count' => $availableSubProducts->count()
                            ]);
                        }
                    } else {
                        // No warehouse_id specified - get all sub-products for this product_no (FIFO across all warehouses)
                        $availableSubProducts = $subProductsCache->filter(function($group, $key) use ($productNo) {
                            return strpos($key, $productNo . '|') === 0;
                        })->flatten(1)->sortBy('created_at')->values();
                        
                        if ($availableSubProducts->isEmpty()) {
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Sub-product with product_no '{$productNo}' not found or not available.";
                            continue;
                        }
                        
                        \Log::info('No warehouse_id specified - using products from all warehouses', [
                            'row' => $rowIndex + 4,
                            'product_no' => $productNo,
                            'warehouses_used' => $availableSubProducts->pluck('warehouse_id')->unique()->values()->toArray()
                        ]);
                    }
                    
                    // Final verification: If warehouse_id was specified, verify all selected products are from that warehouse
                    if ($warehouseId && $availableSubProducts->isNotEmpty()) {
                        $wrongWarehouseProducts = $availableSubProducts->filter(function($sp) use ($warehouseId) {
                            $spWarehouseId = $sp->warehouse_id !== null ? (string)(int)$sp->warehouse_id : 'null';
                            return $spWarehouseId !== $warehouseId;
                        });
                        
                        if ($wrongWarehouseProducts->isNotEmpty()) {
                            \Log::error('CRITICAL: Products from wrong warehouse detected!', [
                                'row' => $rowIndex + 4,
                                'product_no' => $productNo,
                                'requested_warehouse_id' => $warehouseId,
                                'wrong_warehouse_products' => $wrongWarehouseProducts->pluck('warehouse_id')->unique()->values()->toArray()
                            ]);
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": CRITICAL ERROR - Attempted to use products from wrong warehouse. Requested warehouse: '{$warehouseId}'.";
                            continue;
                        }
                    }

                    // Get product from cache
                    $firstSubProduct = $availableSubProducts->first();
                    
                    // Check if product_id exists
                    if (empty($firstSubProduct->product_id)) {
                        \Log::warning('Sub-product has no product_id', [
                            'product_no' => $productNo,
                            'sub_product_id' => $firstSubProduct->id
                        ]);
                        $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Sub-product with product_no '{$productNo}' has no associated product_id.";
                        continue;
                    }
                    
                    $product = $productsCache->get($firstSubProduct->product_id);
                    
                    // If not in cache, try to load it directly
                    if (!$product) {
                        \Log::info('Product not in cache, loading directly', [
                            'product_id' => $firstSubProduct->product_id,
                            'product_no' => $productNo,
                            'creator_id' => $creatorId
                        ]);
                        
                        // First check if product exists at all (without creator filter)
                        $productExists = ProductService::where('id', $firstSubProduct->product_id)->exists();
                        
                        if (!$productExists) {
                            \Log::error('Product does not exist in database', [
                                'product_id' => $firstSubProduct->product_id,
                                'product_no' => $productNo,
                                'sub_product_id' => $firstSubProduct->id,
                                'user_id' => $this->userId
                            ]);
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Product with ID {$firstSubProduct->product_id} does not exist for product_no '{$productNo}'. The product may have been deleted.";
                            continue;
                        }
                        
                        // Try to load with creator filter
                        $product = ProductService::where('id', $firstSubProduct->product_id)
                            ->where('created_by', $creatorId)
                            ->with('category:id,name,type')
                            ->first();
                        
                        if ($product) {
                            // Add to cache for future use
                            $productsCache[$product->id] = $product;
                            \Log::info('Product loaded and added to cache', [
                                'product_id' => $product->id,
                                'product_name' => $product->name
                            ]);
                        } else {
                            // Product exists but belongs to different creator
                            $actualProduct = ProductService::where('id', $firstSubProduct->product_id)->first(['id', 'name', 'created_by']);
                            \Log::error('Product belongs to different creator', [
                                'product_id' => $firstSubProduct->product_id,
                                'product_no' => $productNo,
                                'sub_product_id' => $firstSubProduct->id,
                                'user_id' => $this->userId,
                                'user_creator_id' => $creatorId,
                                'product_creator_id' => $actualProduct ? $actualProduct->created_by : 'unknown',
                                'product_name' => $actualProduct ? $actualProduct->name : 'unknown'
                            ]);
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Product with ID {$firstSubProduct->product_id} exists but belongs to a different creator for product_no '{$productNo}'. Sub-product and product must belong to the same company.";
                            continue;
                        }
                    }

                    $category = $product->category;
                    $categoryType = $category ? $category->type : null;

                    // Check quantity availability using cached data
                    if ($categoryType === "Qty product") {
                        // For Qty product, check if we have enough quantity
                        $totalAvailable = $availableSubProducts->sum('quantity');
                        
                        if ($totalAvailable < $qty) {
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Not enough quantity for product_no '{$productNo}'. Required: {$qty}, Available: {$totalAvailable}";
                            continue;
                        }
                    } else {
                        // For non-Qty product, we need distinct items
                        $availableCount = $availableSubProducts->count();
                        
                        if ($availableCount < $qty) {
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": Not enough items for product_no '{$productNo}'. Required: {$qty}, Available: {$availableCount}";
                            continue;
                        }
                    }

                            // Allocate items using FIFO from cached data
                            $remainingQty = $qty;

                    if ($categoryType === "Qty product") {
                        // For Qty product, allocate from multiple sub-products if needed
                        foreach ($availableSubProducts as $sp) {
                            if ($remainingQty <= 0) break;

                            $availableQty = $sp->quantity;
                            $quantityToDeduct = min($availableQty, $remainingQty);

                            // Prepare sub-product update
                            $subProductUpdates[$sp->id] = [
                                'invoice_id' => $invoice->id,
                                'booked' => ($availableQty - $quantityToDeduct) <= 0 ? 1 : 0,
                                'quantity' => $availableQty - $quantityToDeduct,
                            ];
                            $subProductIdsToUpdate[] = $sp->id;

                            // Prepare invoice product for bulk insert
                            $finalPrice = ($invoice->currency_id && $exchangeRate > 0) ? ($price * $exchangeRate) : $price;
                            $finalDiscount = ($invoice->currency_id && $exchangeRate > 0) ? ($discount * $exchangeRate) : $discount;

                            $invoiceProductsToInsert[] = [
                                'invoice_id' => $invoice->id,
                                'product_id' => $product->id,
                                'sub_product_id' => $sp->id,
                                'quantity' => $quantityToDeduct,
                                'tax' => $invoice->tax_id,
                                'exchange_price' => $price,
                                'exchange_discount' => $discount,
                                'price' => $finalPrice,
                                'discount' => $finalDiscount,
                                'description' => '',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            
                            $target_document_type = "";
                            $target_document = 0;
                            if($sp->asn_id){
                                $target_document_type = "ASN";
                                $target_document = $sp->asn_id;
                            }else{
                                $target_document_type = "BILL";
                                $target_document = $sp->bill_id;
                            }
                            MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,$quantityToDeduct,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);

                            $remainingQty -= $quantityToDeduct;
                        }
                    } else {
                        // For non-Qty product, allocate distinct items
                        $itemsToAllocate = $availableSubProducts->take($qty);
                        
                        foreach ($itemsToAllocate as $sp) {
                            // Prepare sub-product update
                            $subProductUpdates[$sp->id] = [
                                'invoice_id' => $invoice->id,
                                'booked' => 1,
                                'quantity' => max(0, (int)$sp->quantity - 1),
                            ];
                            $subProductIdsToUpdate[] = $sp->id;

                            // Prepare invoice product for bulk insert
                            $finalPrice = ($invoice->currency_id && $exchangeRate > 0) ? ($price * $exchangeRate) : $price;
                            $finalDiscount = ($invoice->currency_id && $exchangeRate > 0) ? ($discount * $exchangeRate) : $discount;

                            $invoiceProductsToInsert[] = [
                                'invoice_id' => $invoice->id,
                                'product_id' => $product->id,
                                'sub_product_id' => $sp->id,
                                'quantity' => 1,
                                'tax' => $invoice->tax_id,
                                'exchange_price' => $price,
                                'exchange_discount' => $discount,
                                'price' => $finalPrice,
                                'discount' => $finalDiscount,
                                'description' => '',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                                                        $target_document_type = "";
                            $target_document = 0;
                            if($sp->asn_id){
                                $target_document_type = "ASN";
                                $target_document = $sp->asn_id;
                            }else{
                                $target_document_type = "BILL";
                                $target_document = $sp->bill_id;
                            }
                            MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,1,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);
       
                        }
                    }

                        } catch (\Exception $e) {
                            $chunkErrors[] = "Row " . ($rowIndex + 4) . ": " . $e->getMessage();
                            \Log::error('Error processing product row', [
                                'error' => $e->getMessage(),
                                'row_index' => $rowIndex,
                                'user_id' => $this->userId
                            ]);
                        }
                    }

                    // Bulk update sub-products for this chunk
                    if (!empty($subProductUpdates)) {
                        foreach ($subProductUpdates as $subProductId => $updateData) {
                            SubProduct::where('id', $subProductId)->update($updateData);
                        }
                    }

                // Bulk insert invoice products for this chunk
                if (!empty($invoiceProductsToInsert)) {
                    // Insert in smaller batches to avoid query size limits
                    $insertBatchSize = 100;
                    $insertBatches = array_chunk($invoiceProductsToInsert, $insertBatchSize);
                    
                    foreach ($insertBatches as $insertBatch) {
                        InvoiceProduct::insert($insertBatch);
                    }
                }

                    // If there are errors in this chunk, collect them
                    if (!empty($chunkErrors)) {
                        $errorArray = array_merge($errorArray, $chunkErrors);
                        DB::rollBack();
                    } else {
                        DB::commit();
                        $totalProcessed += count($chunk);
                        
                        // Refresh cache for updated sub-products to reflect changes
                        if (!empty($subProductIdsToUpdate)) {
                            $updatedSubProducts = SubProduct::whereIn('id', $subProductIdsToUpdate)
                                ->select('id', 'chassis_no', 'product_id', 'quantity', 'booked', 'warehouse_id', 'created_at')
                                ->get();
                            
                            foreach ($updatedSubProducts as $updatedSp) {
                                // Normalize product_no for cache lookup (uppercase)
                                $productNo = strtoupper(trim($updatedSp->product_no));
                                // Normalize warehouse_id same way as in grouping
                                $warehouseId = $updatedSp->warehouse_id !== null ? (string)(int)$updatedSp->warehouse_id : 'null';
                                $cacheKey = $productNo . '|' . $warehouseId;
                                
                                if ($subProductsCache->has($cacheKey)) {
                                    $subProductsCache[$cacheKey] = $subProductsCache[$cacheKey]->map(function($sp) use ($updatedSp) {
                                        if ($sp->id == $updatedSp->id) {
                                            $sp->quantity = $updatedSp->quantity;
                                            $sp->booked = $updatedSp->booked;
                                        }
                                        return $sp;
                                    })->filter(function($sp) {
                                        // Remove fully booked or zero quantity items
                                        return $sp->booked == 0 && $sp->quantity > 0;
                                    })->values();
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorArray[] = "Chunk " . ($chunkIndex + 1) . " failed: " . $e->getMessage();
                    \Log::error('Error processing chunk', [
                        'error' => $e->getMessage(),
                        'chunk_index' => $chunkIndex,
                        'user_id' => $this->userId
                    ]);
                }

                // Clear memory after each chunk
                if (isset($chunkErrors)) unset($chunkErrors);
                if (isset($invoiceProductsToInsert)) unset($invoiceProductsToInsert);
                if (isset($subProductUpdates)) unset($subProductUpdates);
                if (isset($subProductIdsToUpdate)) unset($subProductIdsToUpdate);
                
                // Force garbage collection every 5 chunks to free memory
                if (($chunkIndex + 1) % 5 == 0) {
                    gc_collect_cycles();
                    \Log::info('Garbage collection performed', [
                        'chunk' => $chunkIndex + 1,
                        'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
                    ]);
                }
            }

            // If there are any errors, delete invoice and throw exception
            if (!empty($errorArray)) {
                // Delete invoice if it was created
                if (isset($invoice) && $invoice->id) {
                    try {
                        Invoice::where('id', $invoice->id)->delete();
                    } catch (\Exception $e) {
                        \Log::error('Failed to delete invoice after import error', ['invoice_id' => $invoice->id]);
                    }
                }
                throw new \Exception("Import failed with errors:\n" . implode("\n", array_slice($errorArray, 0, 100)) . (count($errorArray) > 100 ? "\n... and " . (count($errorArray) - 100) . " more errors" : ""));
            }
            
            \Log::info('Invoice import completed successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'user_id' => $this->userId,
                'total_rows_processed' => $totalProcessed,
                'total_rows' => count($productRows)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId
            ]);
            throw $e;
        }
    }
}

