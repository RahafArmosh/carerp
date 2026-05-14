<?php

namespace App\Imports;

use App\Models\Bill;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\WarehouseProduct;
use App\Models\BillProduct;
use App\Models\CustomFieldValue;
use App\Models\Currency;
use App\Models\BillAccount;
use App\Models\Vender;
use App\Models\Tax;
use App\Models\ProductServiceCategory;
use App\Models\CustomField;
use App\Models\MasterlistLeadger;
use App\Models\warehouse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BillImport implements ToArray
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
                return Date::excelToDateTimeObject($dateValue);
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
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon's flexible parsing
            try {
                return \Carbon\Carbon::parse($dateValue);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date string', [
                    'value' => $dateValue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If all else fails, return current date
        \Log::warning('Using current date as fallback', ['original_value' => $dateValue]);
        return now();
    }

    public function array(array $data)
    {
        try {
            // Increase memory limit and execution time for large imports
            ini_set('memory_limit', '512M');
            set_time_limit(0);

            DB::beginTransaction();

            // Validate required data structure
            if (count($data) < 4) {
                throw new \Exception('Invalid file format. File must have at least 4 rows.');
            }

            $billHeader = $data[0];
            $billdata = $data[1];
            $subProductHeader = $data[2];
            $subProductRows = array_slice($data, 3);
            $totalRows = count($subProductRows);

            \Log::info('Bill import started', [
                'user_id' => $this->userId,
                'sub_product_rows_count' => $totalRows
            ]);

            // Get next bill number with proper locking to prevent race conditions
            // Lock the bills table to prevent concurrent access (we're already in a transaction)
            // Use a helper function to extract numeric value from bill_id
            $extractNumericBillId = function($billIdValue) {
                // If bill_id is formatted (contains #BILL), extract the numeric part
                if (preg_match('/#BILL(\d+)/i', $billIdValue, $matches)) {
                    return (int)$matches[1];
                } elseif (preg_match('/(\d+)/', $billIdValue, $matches)) {
                    // Extract first numeric sequence found
                    return (int)$matches[1];
                }
                // If no numeric found, try direct conversion
                return (int)$billIdValue;
            };
            
            // Get all bills and find the maximum numeric bill_id value
            // This ensures we get the highest number even if bills aren't sequential
            $allBills = Bill::where('created_by', $this->userId)
                ->where('bill_id', 'not like', '%#EXP%')
                ->withTrashed()
                ->lockForUpdate() // Lock for update to prevent race conditions
                ->get(['bill_id']);
            
            if ($allBills->isEmpty()) {
                $bill_number = 1;
            } else {
                // Extract numeric values and find the maximum
                $maxNumericBillId = $allBills->map(function ($bill) use ($extractNumericBillId) {
                    return $extractNumericBillId($bill->bill_id);
                })->max();
                
                $bill_number = $maxNumericBillId + 1;
            }
            
            \Log::info('Bill number generated', [
                'bill_number' => $bill_number,
                'user_id' => $this->userId
            ]);

            // Validate vendor exists
            $vendorId = $billdata[array_search('vender_id', $billHeader)];
            $vendor = Vender::where('id', $vendorId)->where('created_by', $this->userId)->first();
            if (!$vendor) {
                throw new \Exception('Vendor not found or not accessible.');
            }

            // Create bill
            $bill = new Bill();
            foreach ($billHeader as $index => $header) {
                if ($header == 'vender_id') {
                    $bill->vender_id = $billdata[$index];
                } elseif ($header == 'bill_date') {
                    $bill->bill_date = $this->parseDate($billdata[$index]);
                } elseif ($header == 'due_date') {
                    $bill->due_date = $this->parseDate($billdata[$index]);
                } elseif ($header == 'warehouse_id') {
                    if (!empty($billdata[$index])) {
                        // Validate warehouse exists and belongs to user
                        $warehouse = warehouse::where('id', $billdata[$index])
                            ->where('created_by', $this->userId)
                            ->first();
                        if ($warehouse) {
                            $bill->warehouse_id = $billdata[$index];
                        } else {
                            $bill->warehouse_id = null;
                            \Log::warning('Warehouse not found or not accessible', [
                                'warehouse_id' => $billdata[$index],
                                'user_id' => $this->userId
                            ]);
                        }
                    } else {
                        $bill->warehouse_id = null;
                    }
                } elseif ($header == 'category_id') {
                    $bill->category_id = $billdata[$index];
                } elseif ($header == 'order_number') {
                    $bill->order_number = $billdata[$index];
                } elseif ($header == 'salesman_id') {
                    // salesman_id cannot be null, use 0 as default or creatorId if provided
                    $salesmanId = $billdata[$index] ?? null;
                    $bill->salesman_id = !empty($salesmanId) ? $salesmanId : 0;
                } elseif ($header == 'tax_id') {
                    $bill->tax_id = $billdata[$index];
                } elseif ($header == 'currency_id') {
                    if (!empty($billdata[$index])) {
                        $currencyExists = DB::table('currencies')->where('id', $billdata[$index])->exists();
                        $bill->currency_id = $currencyExists ? $billdata[$index] : null;
                    } else {
                        $bill->currency_id = null;
                    }
                } elseif ($header == 'exchange_rate') {
                    $bill->exchange_rate = $billdata[$index] != null ? $billdata[$index] : 0;
                }
            }
            
            // Handle exchange_rate logic similar to BillController
            if ($bill->currency_id) {
                if (!($bill->exchange_rate && $bill->exchange_rate > 0)) {
                    // Get exchange_rate from currency if not provided or invalid
                    $currency = \App\Models\Currency::find($bill->currency_id);
                    $bill->exchange_rate = $currency ? ($currency->exchange_rate ?? 0) : 0;
                }
                // If exchange_rate was provided and > 0, keep it as is
            } else {
                $bill->exchange_rate = 0;
            }

            $bill->bill_id = $bill_number;
            $bill->created_by = $this->userId;
            $bill->type = 'Bill';
            $bill->user_type = 'vendor';
            $bill->save();

            \Log::info('Bill created', ['bill_id' => $bill->id, 'bill_number' => $bill_number]);

            // Pre-load all products, categories, custom fields, and tax data for better performance
            $productIdIndex = array_search('product_id', $subProductHeader);
            if ($productIdIndex === false) {
                throw new \Exception('product_id column not found in sub-product headers.');
            }

            // Extract all product IDs from the import data and validate they are not empty
            $productIds = [];
            $emptyProductIdRows = [];
            foreach ($subProductRows as $rowIndex => $subProductRow) {
                $productId = $subProductRow[$productIdIndex] ?? null;
                if (empty($productId)) {
                    $emptyProductIdRows[] = $rowIndex + 4; // +4 because: header(1) + billdata(2) + subProductHeader(3) + rowIndex(0-based)
                } else {
                    $productIds[] = $productId;
                }
            }
            
            // Check for empty product IDs first
            if (!empty($emptyProductIdRows)) {
                $rowsList = implode(', ', $emptyProductIdRows);
                throw new \Exception(__('Product ID is required but missing in the following rows: :rows. Please provide a valid Product ID for all rows.', ['rows' => $rowsList]));
            }
            
            $productIds = array_unique($productIds);

            // Pre-load all products with their categories in one query
            $products = ProductService::whereIn('id', $productIds)
                ->where('created_by', $this->userId)
                ->with('category')
                ->get()
                ->keyBy('id');

            // Validate all product IDs exist before starting import
            $foundProductIds = $products->keys()->toArray();
            $missingProductIds = array_diff($productIds, $foundProductIds);
            
            if (!empty($missingProductIds)) {
                $missingProductsList = implode(', ', $missingProductIds);
                throw new \Exception(__('The following Product IDs were not found or do not belong to your company: :products. Please verify the product IDs and try again.', ['products' => $missingProductsList]));
            }

            // Pre-load all categories with their purchase accounts
            $categoryIds = $products->pluck('category_id')->filter()->unique()->toArray();
            $categories = ProductServiceCategory::whereIn('id', $categoryIds)
                ->get()
                ->keyBy('id');

            // Pre-load custom fields for all categories
            $customFieldsCache = CustomField::where('module', 'sub-product')
                ->where('created_by', $this->userId)
                ->whereHas('categories', function($q) use ($categoryIds) {
                    $q->whereIn('product_service_categories.id', $categoryIds);
                })
                ->with('categories')
                ->get()
                ->keyBy(function ($field) {
                    return strtolower(trim($field->name));
                });

            // Pre-load tax data
            $tax = $bill->tax_id ? Tax::find($bill->tax_id) : null;
            $vendorChartAccount = $vendor->chartAccount;

            // Pre-load warehouse products for bulk updates
            $warehouseId = $bill->warehouse_id;
            $warehouseProducts = [];
            if ($warehouseId) {
                $warehouseProducts = WarehouseProduct::where('warehouse_id', $warehouseId)
                    ->whereIn('product_id', $productIds)
                    ->get()
                    ->keyBy('product_id');
            }

            // Process sub-products in chunks to avoid memory issues
            $chunkSize = 500; // Process 500 items at a time
            $chunks = array_chunk($subProductRows, $chunkSize, true);
            $processedCount = 0;

            foreach ($chunks as $chunkIndex => $chunk) {
                \Log::info("Processing chunk " . ($chunkIndex + 1) . " of " . count($chunks), [
                    'chunk_size' => count($chunk),
                    'total_processed' => $processedCount,
                    'total_rows' => $totalRows
                ]);

                // Prepare bulk insert arrays
                $subProductsToInsert = [];
                $billProductsToInsert = [];
                $customFieldValuesToInsert = [];
                $billAccountsToInsert = [];
                $productQuantityUpdates = [];
                $warehouseProductUpdates = [];
                $warehouseProductInserts = [];
                $customFieldsData = [];

                foreach ($chunk as $rowIndex => $subProductRow) {
                    try {
                        $productId = $subProductRow[$productIdIndex] ?? null;
                        
                        // Validate product ID is provided
                        if (empty($productId)) {
                            $rowNumber = $rowIndex + 4; // +4 because: header(1) + billdata(2) + subProductHeader(3) + rowIndex(0-based)
                            throw new \Exception(__('Product ID is required but missing. Row: :row', ['row' => $rowNumber]));
                        }
                        
                        // Validate product exists (safety check - should have been caught earlier)
                        if (!isset($products[$productId])) {
                            $rowNumber = $rowIndex + 4; // +4 because: header(1) + billdata(2) + subProductHeader(3) + rowIndex(0-based)
                            throw new \Exception(__('Product ID :product_id not found or does not belong to your company. Row: :row', ['product_id' => $productId, 'row' => $rowNumber]));
                        }
                        
                        $product = $products[$productId];
                    
                        // Extract data from row
                        $quantity = 0;
                        $salePrice = 0;
                        $purchasePrice = 0;
                        $discount = 0;
                        $productNo = null;
                        $finalPrice = 0;

                        foreach ($subProductHeader as $index => $header) {
                            if ($header == 'quantity') {
                                $quantity = $subProductRow[$index] ?? 0;
                            } elseif ($header == 'sale_price') {
                                $salePrice = $subProductRow[$index] ?? 0;
                            } elseif ($header == 'purchase_price') {
                                $purchasePrice = $subProductRow[$index] ?? 0;
                                $finalPrice = $purchasePrice;
                            } elseif ($header == 'discount') {
                                $discount = $subProductRow[$index] ?? 0;
                            } elseif ($header == 'product_no') {
                                $productNo = $subProductRow[$index] ?? null;
                            }
                        }

                        // Handle exchange rate calculations similar to BillController
                        $exchangePrice = $purchasePrice; // Original price in selected currency
                        $exchangeDiscount = $discount; // Original discount in selected currency
                        $convertedPrice = $purchasePrice; // Price converted to base currency
                        $convertedDiscount = $discount; // Discount converted to base currency
                        $subProductPurchasePrice = $purchasePrice; // Purchase price for SubProduct

                        if (!empty($bill->currency_id) && $bill->exchange_rate > 0) {
                            $exchangeRate = $bill->exchange_rate;
                            
                            // Calculate converted values (multiply by exchange rate)
                            $convertedPrice = $purchasePrice * $exchangeRate;
                            $convertedDiscount = $discount * $exchangeRate;
                            
                            // Calculate SubProduct purchase_price: (price - discount) * exchange_rate
                            $subProductPurchasePrice = ($purchasePrice - $discount) * $exchangeRate;
                        }

                        // Prepare sub-product for bulk insert
                        $subProductData = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'sale_price' => $salePrice,
                            'purchase_price' => $subProductPurchasePrice,
                            'product_no' => $productNo,
                            'bill_id' => $bill->id,
                            'warehouse_id' => $bill->warehouse_id,
                            'flag' => 0,
                            'created_by' => $this->userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        // Store original values for BillProduct creation (temporary fields)
                        $subProductData['_exchange_price'] = $exchangePrice;
                        $subProductData['_exchange_discount'] = $exchangeDiscount;
                        $subProductData['_converted_price'] = $convertedPrice;
                        $subProductData['_converted_discount'] = $convertedDiscount;
                        $subProductData['_original_purchase_price'] = $purchasePrice; // Original price from row
                        
                        $subProductsToInsert[] = $subProductData;

                        // Track product quantity updates (will be applied in bulk later)
                        if (!isset($productQuantityUpdates[$productId])) {
                            $productQuantityUpdates[$productId] = 0;
                        }
                        $productQuantityUpdates[$productId] += $quantity;

                        // Process custom fields and store them with row index for later mapping
                        // Get all custom fields for this product's category to match against headers
                        $productCategoryId = $product->category_id;
                        $categoryCustomFieldNames = [];
                        if ($productCategoryId) {
                            // Filter custom fields that belong to this category
                            // customFieldsCache is keyed by field name (lowercase), not category ID
                            $categoryCustomFields = $customFieldsCache->filter(function($field) use ($productCategoryId) {
                                return $field->categories->contains('id', $productCategoryId);
                            });
                            $categoryCustomFieldNames = $categoryCustomFields->keys()->toArray();
                        }
                        
                        $customFields = [];
                        foreach ($subProductHeader as $index => $header) {
                            $headerLower = strtolower(trim($header));
                            // Check if this header matches any custom field name for this category
                            if (in_array($headerLower, $categoryCustomFieldNames)) {
                                $value = $subProductRow[$index] ?? null;
                                // Convert to string and trim, but allow empty values
                                $customFields[$headerLower] = $value !== null ? trim((string)$value) : null;
                            }
                        }
                        
                        // Store custom fields data with row index for later processing
                        $customFieldsData[] = [
                            'row_index' => count($subProductsToInsert),
                            'fields' => $customFields,
                            'product' => $product
                        ];

                        // Prepare warehouse product updates
                        if ($warehouseId) {
                            if (isset($warehouseProducts[$productId])) {
                                if (!isset($warehouseProductUpdates[$productId])) {
                                    $warehouseProductUpdates[$productId] = 0;
                                }
                                $warehouseProductUpdates[$productId] += $quantity;
                            } else {
                                $warehouseProductInserts[] = [
                                    'warehouse_id' => $warehouseId,
                                    'product_id' => $productId,
                                    'quantity' => $quantity,
                                    'created_by' => $this->userId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error processing sub-product row', [
                            'error' => $e->getMessage(),
                            'row_index' => $rowIndex,
                            'user_id' => $this->userId
                        ]);
                        throw $e;
                    }
                }

                // Bulk insert sub-products (remove temporary fields before inserting)
                if (!empty($subProductsToInsert)) {
                    // Filter out temporary fields (starting with _) before inserting
                    $subProductsForInsert = array_map(function($item) {
                        return array_filter($item, function($key) {
                            return substr($key, 0, 1) !== '_';
                        }, ARRAY_FILTER_USE_KEY);
                    }, $subProductsToInsert);
                    
                    SubProduct::insert($subProductsForInsert);
                    
                    // Get the inserted sub-products to create bill products and custom field values
                    $insertedSubProducts = SubProduct::where('bill_id', $bill->id)
                        ->whereIn('product_id', array_column($subProductsToInsert, 'product_id'))
                        ->orderBy('id', 'desc')
                        ->limit(count($subProductsToInsert))
                        ->get()
                        ->reverse()
                        ->values();

                    // Create bill products and custom field values
                    foreach ($insertedSubProducts as $idx => $subProduct) {
                        $originalRow = $subProductsToInsert[$idx];
                        
                        // Create BillProduct with exchange_price and exchange_discount
                        // Use original values from row (stored in temporary fields)
                        $originalPurchasePrice = $originalRow['_original_purchase_price'] ?? $originalRow['_exchange_price'] ?? 0;
                        $exchangePrice = $originalRow['_exchange_price'] ?? $originalPurchasePrice;
                        $exchangeDiscount = $originalRow['_exchange_discount'] ?? 0;
                        $convertedPrice = $originalRow['_converted_price'] ?? $originalPurchasePrice;
                        $convertedDiscount = $originalRow['_converted_discount'] ?? 0;
                        
                        $billProductsToInsert[] = [
                            'bill_id' => $bill->id,
                            'product_id' => $subProduct->product_id,
                            'sub_product_id' => $subProduct->id,
                            'quantity' => $subProduct->quantity,
                            'tax' => $bill->tax_id,
                            'discount' => $convertedDiscount, // Converted discount (in base currency)
                            'price' => $convertedPrice, // Converted price (in base currency)
                            'exchange_price' => $exchangePrice, // Original price in selected currency
                            'exchange_discount' => $exchangeDiscount, // Original discount in selected currency
                            'description' => '',
                            // 'created_by' => $this->userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        MasterlistLeadger::addFree($subProduct->productService->id,$bill->warehouse_id,$subProduct->quantity,'BILL',$bill->id,\Auth::user()->creatorId());
                        

                        // Process custom fields for this sub-product
                        if (isset($customFieldsData[$idx])) {
                            $customFieldsInfo = $customFieldsData[$idx];
                            $product = $customFieldsInfo['product'];
                            
                            if ($product->category_id) {
                                // Filter custom fields that belong to this category
                                $categoryCustomFields = collect($customFieldsCache)->flatten()->filter(function($field) use ($product) {
                                    return $field->categories->contains('id', $product->category_id);
                                })->keyBy(function($field) {
                                    return strtolower(trim($field->name));
                                });
                                foreach ($customFieldsInfo['fields'] as $fieldName => $fieldValue) {
                                    // Check if field exists in cache (allow empty values but field must exist)
                                    if (isset($categoryCustomFields[$fieldName])) {
                                        $customField = $categoryCustomFields[$fieldName];
                                        $customFieldValuesToInsert[] = [
                                            'field_id' => $customField->id,
                                            'record_id' => $subProduct->id,
                                            'value' => $fieldValue !== null ? (string)$fieldValue : '',
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ];
                                    } else {
                                        \Log::warning('Custom field not found in cache', [
                                            'field_name' => $fieldName,
                                            'category_id' => $product->category_id,
                                            'available_fields' => $categoryCustomFields->keys()->toArray(),
                                            'sub_product_id' => $subProduct->id,
                                            'user_id' => $this->userId
                                        ]);
                                    }
                                }
                            } else {
                                \Log::warning('Category custom fields not found', [
                                    'category_id' => $product->category_id ?? 'null',
                                    'sub_product_id' => $subProduct->id,
                                    'user_id' => $this->userId,
                                    'available_categories' => $customFieldsCache->keys()->toArray()
                                ]);
                            }
                        }

                        // Create BillAccount entries
                        if ($tax) {
                            // Get product for this sub-product
                            $productForAccount = $products[$subProduct->product_id];
                            
                            // Vendor account
                            if ($vendorChartAccount) {
                                $taxAmount = ($tax->rate * ($originalRow['purchase_price'] * $subProduct->quantity) / 100);
                                $billAccountsToInsert[] = [
                                    'chart_account_id' => $vendorChartAccount->id,
                                    'price' => ($originalRow['purchase_price'] * $subProduct->quantity) + $taxAmount,
                                    'description' => '',
                                    'type' => 'Bill Vender',
                                    'ref_id' => $bill->id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }

                            // Category account
                            if ($productForAccount->category_id && isset($categories[$productForAccount->category_id])) {
                                $category = $categories[$productForAccount->category_id];
                                if ($category->purchase_account_id) {
                                    $billAccountsToInsert[] = [
                                        'chart_account_id' => $category->purchase_account_id,
                                        'price' => $originalRow['purchase_price'] * $subProduct->quantity,
                                        'description' => '',
                                        'type' => 'Bill Category',
                                        'ref_id' => $bill->id,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }
                        }
                    }

                    // Bulk insert bill products
                    if (!empty($billProductsToInsert)) {
                        BillProduct::insert($billProductsToInsert);
                    }

                    // Bulk insert custom field values
                    if (!empty($customFieldValuesToInsert)) {
                        CustomFieldValue::insert($customFieldValuesToInsert);
                    }

                    // Bulk insert bill accounts
                    if (!empty($billAccountsToInsert)) {
                        BillAccount::insert($billAccountsToInsert);
                    }

                    // Bulk update product quantities
                    foreach ($productQuantityUpdates as $prodId => $qtyIncrease) {
                        DB::table('product_services')
                            ->where('id', $prodId)
                            ->increment('quantity', $qtyIncrease);
                    }

                    // Bulk update warehouse products
                    foreach ($warehouseProductUpdates as $prodId => $qtyIncrease) {
                        DB::table('warehouse_products')
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_id', $prodId)
                            ->increment('quantity', $qtyIncrease);
                    }

                    // Bulk insert new warehouse products
                    if (!empty($warehouseProductInserts)) {
                        WarehouseProduct::insert($warehouseProductInserts);
                    }

                    $processedCount += count($subProductsToInsert);
                    
                    // Commit this chunk to avoid long-running transactions
                    DB::commit();
                    DB::beginTransaction();
                    
                    // Clear arrays for next chunk
                    $subProductsToInsert = [];
                    $billProductsToInsert = [];
                    $customFieldValuesToInsert = [];
                    $billAccountsToInsert = [];
                    $productQuantityUpdates = [];
                    $warehouseProductUpdates = [];
                    $warehouseProductInserts = [];
                    $customFieldsData = [];
                }
            }

            DB::commit();
            \Log::info('Bill import completed successfully', [
                'bill_id' => $bill->id,
                'bill_number' => $bill_number,
                'user_id' => $this->userId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bill import failed', [
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

