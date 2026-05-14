<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ProductService;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Tax;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;
class StockImport implements
    OnEachRow,
    WithHeadingRow,
    WithChunkReading,
    WithEvents
{
    
    protected $creatorId;
    
    // These properties should NOT be serialized - they'll be re-initialized when job runs
    protected $brandCache = [];
    protected $subBrandCache = [];
    protected $productCache = []; // Cached by SKU
    protected $productCacheById = []; // Cached by ID for quick lookup
    protected $categoryCache = [];
    protected $unitCache = [];
    protected $taxCache = [];
    protected $customFieldCache = [];
    protected $brandCategoryCache = []; // Cache brand-category relationships
    protected $errors = [];
    protected $rowNumber = 0;
    protected $successCount = 0;
    protected $failCount = 0;
    
    // Batch insert management - optimized for large imports (ERP level)
    protected $batchSize = 250; // Insert 250 rows per batch (reduced to prevent MySQL timeout)
    protected $subProductBatch = []; // Collect sub-products for batch insert
    protected $brandCategoryBatch = []; // Collect brand-category links for batch insert
    protected $customFieldBatch = []; // Collect custom fields for batch insert
    protected $newBrandsBatch = []; // Collect new brands for batch insert
    protected $newSubBrandsBatch = []; // Collect new sub-brands for batch insert
    protected $newProductsBatch = []; // Collect new products for batch insert
    protected $batchCount = 0;
    protected $transactionActive = false; // Track if transaction is active
    
    // Memory management - optimized for 20k+ rows
    protected $maxCacheSize = 5000; // Increased cache size for large imports
    protected $maxErrors = 1000; // Increased error limit for large imports
    protected $lastMemoryCheck = 0;
    protected $memoryCheckInterval = 1000; // Check memory every N rows
    
    // Database optimization flags
    protected $indexesDisabled = false;
    protected $foreignKeyChecksDisabled = false;
    
    // Pre-loaded data
    protected $preloadedCategories = [];
    protected $preloadedUnits = [];
    protected $preloadedTaxes = [];
    protected $preloadedCustomFields = [];
    protected $preloadedWarehouses = []; // Warehouse name => warehouse ID mapping
    
    // Track if initialized (for queue context)
    protected $initialized = false;

    public function __construct($creatorId)
    {
        $this->creatorId = $creatorId;
        $this->initialize();
    }
    
    /**
     * Initialize the import class (called in constructor and when job is executed)
     */
    protected function initialize()
    {
        if ($this->initialized) {
            return; // Already initialized
        }
        
        // Disable query logging for better performance
        DB::connection()->disableQueryLog();
        
        // Pre-load common data to reduce database queries
        $this->preloadCommonData();
        
        $this->initialized = true;
        
        \Log::info('StockImport initialized', [
            'creator_id' => $this->creatorId,
            'preloaded_categories' => count($this->preloadedCategories),
            'preloaded_units' => count($this->preloadedUnits),
            'preloaded_taxes' => count($this->preloadedTaxes),
            'preloaded_warehouses' => count($this->preloadedWarehouses),
            'warehouse_names' => array_keys($this->preloadedWarehouses),
            'memory_limit' => ini_get('memory_limit')
        ]);
    }
    
    /**
     * Register import events for index management
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                // Ensure initialization if not already done (for queued jobs)
                if (!$this->initialized) {
                    $this->initialize();
                }
                
                try {
                    $this->disableIndexes();
                } catch (\Exception $e) {
                    \Log::warning('Failed to disable indexes: ' . $e->getMessage());
                }
            },
            AfterImport::class => function(AfterImport $event) {
                try {
                    $this->enableIndexes();
                    $this->optimizeTables();
                } catch (\Exception $e) {
                    \Log::warning('Failed to enable indexes or optimize tables: ' . $e->getMessage());
                }
            },
            ImportFailed::class => function(ImportFailed $event) {
                try {
                    // Ensure indexes are re-enabled even if import fails
                    $this->enableIndexes();
                    \Log::error('Stock import failed', [
                        'exception' => $event->getException()->getMessage()
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error in ImportFailed handler: ' . $e->getMessage());
                }
            },
        ];
    }
    
    /**
     * Disable indexes and foreign key checks for faster inserts
     */
    private function disableIndexes()
    {
        try {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'mysql') {
                // Disable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                $this->foreignKeyChecksDisabled = true;
                
                // Disable indexes on main tables (MySQL doesn't support disabling indexes directly)
                // Instead, we'll use INSERT IGNORE and optimize queries
                \Log::info('Disabled foreign key checks for import');
            }
            
            $this->indexesDisabled = true;
        } catch (\Exception $e) {
            \Log::warning('Could not disable indexes: ' . $e->getMessage());
        }
    }
    
    /**
     * Re-enable indexes and foreign key checks
     */
    private function enableIndexes()
    {
        try {
            if ($this->foreignKeyChecksDisabled) {
                $driver = DB::connection()->getDriverName();
                
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    \Log::info('Re-enabled foreign key checks');
                }
                
                $this->foreignKeyChecksDisabled = false;
            }
            
            $this->indexesDisabled = false;
        } catch (\Exception $e) {
            \Log::warning('Could not enable indexes: ' . $e->getMessage());
        }
    }
    
    /**
     * Optimize tables after import
     */
    private function optimizeTables()
    {
        try {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'mysql') {
                // Optimize tables that were heavily modified
                $tables = ['sub_products', 'product_services', 'brands', 'sub_brands'];
                
                foreach ($tables as $table) {
                    try {
                        DB::statement("OPTIMIZE TABLE `{$table}`");
                        \Log::info("Optimized table: {$table}");
                    } catch (\Exception $e) {
                        \Log::warning("Could not optimize table {$table}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Could not optimize tables: ' . $e->getMessage());
        }
    }
    
    /**
     * Pre-load ALL reference data to eliminate DB queries during import (ERP level optimization)
     */
    private function preloadCommonData()
    {
        // Pre-load categories
        $categories = ProductServiceCategory::where('created_by', $this->creatorId)->get();
        foreach ($categories as $category) {
            $this->preloadedCategories[strtolower(trim($category->name))] = $category->id;
            $this->preloadedCategories[$category->id] = $category->id;
        }
        
        // Pre-load units
        $units = ProductServiceUnit::where('created_by', $this->creatorId)->get();
        foreach ($units as $unit) {
            $this->preloadedUnits[strtolower(trim($unit->name))] = $unit->id;
            $this->preloadedUnits[$unit->id] = $unit->id;
        }
        
        // Pre-load taxes
        $taxes = Tax::where('created_by', $this->creatorId)->get();
        foreach ($taxes as $tax) {
            $this->preloadedTaxes[$tax->id] = $tax;
        }
        
        // Pre-load custom fields
        $customFields = CustomField::where('created_by', $this->creatorId)
            ->whereIn('module', ['product', 'sub-product'])
            ->with('categories')
            ->get();
        foreach ($customFields as $field) {
            // Create keys for each category this field belongs to
            foreach ($field->categories as $category) {
                $key = $field->module . '_' . $category->id . '_' . strtolower($field->name);
                $this->preloadedCustomFields[$key] = $field;
            }
            // Also create a key without category for fallback (for product module)
            if ($field->module === 'product') {
                $key = $field->module . '_all_' . strtolower($field->name);
                $this->preloadedCustomFields[$key] = $field;
            }
        }
        
        // Pre-load ALL brands (zero queries during import)
        $brands = Brand::where('created_by', $this->creatorId)->get();
        foreach ($brands as $brand) {
            $cacheKey = strtolower(trim($brand->name));
            $this->brandCache[$cacheKey] = $brand;
        }
        
        // Pre-load ALL sub-brands (zero queries during import)
        $subBrands = VehicleModel::where('created_by', $this->creatorId)->get();
        foreach ($subBrands as $subBrand) {
            $cacheKey = strtolower(trim($subBrand->name)) . '_' . $subBrand->brand_id;
            $this->subBrandCache[$cacheKey] = $subBrand;
        }
        
        // Pre-load ALL products by SKU (zero queries during import)
        $products = ProductService::where('created_by', $this->creatorId)->get();
        foreach ($products as $product) {
            $cacheKey = strtolower(trim($product->sku));
            $this->productCache[$cacheKey] = $product;
            $this->productCacheById[$product->id] = $product;
        }
        
        // Pre-load brand-category relationships (zero queries during import)
        $brandCategories = DB::table('brand_category')->get();
        foreach ($brandCategories as $bc) {
            $key = $bc->brand_id . '_' . $bc->product_service_category_id;
            $this->brandCategoryCache[$key] = true;
        }
        
        // Pre-load warehouses by name (for warehouse column headers)
        $warehouses = \App\Models\warehouse::where('created_by', $this->creatorId)->get();
        foreach ($warehouses as $warehouse) {
            $warehouseName = trim($warehouse->name);
            $warehouseNameKey = strtolower($warehouseName);
            $warehouseNameNormalized = strtolower(str_replace([' ', '_', '-'], '', $warehouseName));
            
            // Validate warehouse ID
            if (empty($warehouse->id) || $warehouse->id <= 0) {
                \Log::warning('StockImport: Skipping warehouse with invalid ID', [
                    'warehouse_name' => $warehouseName,
                    'warehouse_id' => $warehouse->id
                ]);
                continue;
            }
            
            // Store multiple variations for flexible matching
            $warehouseIdInt = (int)$warehouse->id;
            $this->preloadedWarehouses[$warehouseName] = $warehouseIdInt; // Original name
            $this->preloadedWarehouses[$warehouseNameKey] = $warehouseIdInt; // Lowercase
            $this->preloadedWarehouses[$warehouseNameNormalized] = $warehouseIdInt; // Normalized (no spaces/underscores/dashes)
        }
        
        \Log::info('StockImport: Pre-loaded warehouses', [
            'count' => count($warehouses),
            'warehouse_names' => $warehouses->pluck('name')->toArray(),
            'unique_warehouse_ids' => array_unique(array_values($this->preloadedWarehouses))
        ]);
    }

    public function onRow(Row $row)
    {
        // Ensure initialization if not already done (for queued jobs)
        if (!$this->initialized) {
            $this->initialize();
        }
        
        $this->rowNumber = $row->getIndex();
        
        // Memory management: Clear caches periodically
        if ($this->rowNumber % $this->memoryCheckInterval === 0) {
            $this->manageMemory();
        }
        
        // Start batch transaction if needed
        if (!$this->transactionActive) {
            DB::beginTransaction();
            $this->transactionActive = true;
        }
        
        try {
            $items = $row->toArray();

            // Normalize keys to lowercase for case-insensitive matching
            // Also convert spaces to underscores for compatibility
            $itemsLower = [];
            foreach ($items as $k => $v) {
                $normalizedKey = strtolower(trim(str_replace(' ', '_', $k)));
                $itemsLower[$normalizedKey] = $v;
            }

            // Required fields: Brand, Product SKU, Sub Product Product No
            if (empty($itemsLower['brand_name']) || empty($itemsLower['product_sku']) || empty($itemsLower['sub_product_no'])) {
                $errorMsg = "Row {$this->rowNumber}: Missing required fields. Brand Name, Product SKU, and Sub Product No are required.";
                
                if ($this->failCount < 100) {
                    \Log::warning('Skipping row - missing required fields', [
                        'row_index' => $this->rowNumber,
                        'has_brand' => !empty($itemsLower['brand_name']),
                        'has_sku' => !empty($itemsLower['product_sku']),
                        'has_product_no' => !empty($itemsLower['sub_product_no'])
                    ]);
                }
                
                $this->errors[] = $errorMsg;
                $this->failCount++;
                throw new \Exception($errorMsg);
            }

            // Get category ID first (needed for brand linking)
            $categoryId = $this->getCategoryId($itemsLower);
            if (!$categoryId) {
                throw new \Exception('Category is required. Please provide category_id or category_name.');
            }

            // Step 1: Get or Create Brand and link to category
            $brand = $this->getOrCreateBrand($itemsLower['brand_name'], $categoryId);

            // Step 2: Get or Create Sub Brand
            $subBrand = null;
            if (!empty($itemsLower['sub_brand_name'])) {
                $subBrand = $this->getOrCreateSubBrand($itemsLower['sub_brand_name'], $brand->id);
            }

            // Step 3: Get or Create Product
            // Note: If duplicate SKU exists, getOrCreateProduct will return the existing product
            // This allows multiple sub-products to be added under the same product SKU
            $product = $this->getOrCreateProduct($itemsLower, $brand->id, $subBrand ? $subBrand->id : null, $categoryId);

            // Step 4: Collect Custom Fields for batch processing (zero queries) - must be before sub-product
            $this->collectCustomFieldsForBatch($product, $itemsLower);
            
            // Step 5: Add Sub Products to batch - handle both warehouse columns and single warehouse_id
            $this->addSubProductsToBatch($items, $itemsLower, $product->id);

            // Increment batch count
            $this->batchCount++;
            $this->successCount++;

            // Execute batch inserts every 250 rows (reduced to prevent MySQL timeout)
            if ($this->batchCount >= $this->batchSize) {
                $this->executeBatchInserts();
                
                // Reconnect to MySQL to prevent "server has gone away" errors
                $this->reconnectDatabase();
                
                // Force garbage collection periodically
                if ($this->successCount % 1000 === 0) {
                    gc_collect_cycles();
                }
            }

            // Progress logging - log every 1000 rows for large imports
            if ($this->successCount % 1000 === 0) {
                \Log::info('Stock import progress', [
                    'rows_processed' => $this->successCount,
                    'errors' => $this->failCount,
                    'current_row' => $this->rowNumber,
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                    'cache_size' => count($this->productCache) + count($this->brandCache) + count($this->subBrandCache)
                ]);
            }

        } catch (\Exception $e) {
            $this->failCount++;
            $errorMsg = "Row {$this->rowNumber}: " . $e->getMessage();
            
            // Limit error collection to prevent memory issues
            if (count($this->errors) < $this->maxErrors) {
                $this->errors[] = $errorMsg;
            } elseif (count($this->errors) === $this->maxErrors) {
                $this->errors[] = "... (error limit reached, more errors occurred but not logged)";
            }
            
            // Reduced logging - only log first 100 errors in detail
            if ($this->failCount <= 100) {
                \Log::error('Stock Import Error', [
                    'message' => $e->getMessage(),
                    'row_number' => $this->rowNumber,
                    'creator_id' => $this->creatorId
                ]);
            }
            
            // IMPORTANT: Don't rollback the entire batch on a single row error
            // Instead, commit successful rows in the batch and continue processing
            // This ensures we don't lose progress when individual rows fail
            if ($this->transactionActive && $this->batchCount > 0) {
                try {
                    // Commit the successful rows in the current batch before the error
                    DB::commit();
                    $this->batchCount = 0;
                    $this->transactionActive = false;
                } catch (\Exception $commitException) {
                    // If commit fails, rollback and reset
                    try {
                        DB::rollBack();
                    } catch (\Exception $rollbackException) {
                        // Ignore rollback errors
                    }
                    $this->batchCount = 0;
                    $this->transactionActive = false;
                }
            }
            
            // Don't throw exception - continue processing other rows
            // Errors will be collected and returned at the end
            return;
        }
    }
    
    /**
     * Reconnect to database to prevent "MySQL server has gone away" errors
     */
    private function reconnectDatabase()
    {
        try {
            // Check if connection is alive
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            // Connection lost - reconnect
            \Log::warning('Database connection lost, reconnecting...', [
                'error' => $e->getMessage()
            ]);
            DB::reconnect();
        }
    }
    
    /**
     * Execute batch inserts (ERP level optimization - 250 rows at a time)
     * This ensures data is saved frequently and reduces risk of data loss and MySQL timeouts
     */
    private function executeBatchInserts()
    {
        if (empty($this->subProductBatch) && empty($this->newBrandsBatch) && 
            empty($this->newSubBrandsBatch) && empty($this->newProductsBatch)) {
            return; // Nothing to insert
        }
        
        // Reconnect to prevent "MySQL server has gone away" errors
        $this->reconnectDatabase();
        
        // Initialize tracking variables
        $originalSubProductCount = count($this->subProductBatch);
        $validSubProducts = [];
        $skippedCount = 0;
        $brandsInserted = 0;
        $subBrandsInserted = 0;
        $productsInserted = 0;
        
        \Log::info('Starting batch inserts', [
            'sub_products' => $originalSubProductCount,
            'brands' => count($this->newBrandsBatch),
            'sub_brands' => count($this->newSubBrandsBatch),
            'products' => count($this->newProductsBatch),
            'batch_count' => $this->batchCount
        ]);
        
        // Use retry logic for MySQL connection issues
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                DB::beginTransaction();
                break; // Success - exit retry loop
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    \Log::error('Failed to start transaction after retries', [
                        'error' => $e->getMessage(),
                        'retries' => $retryCount
                    ]);
                    throw $e;
                }
                \Log::warning('Transaction start failed, reconnecting and retrying...', [
                    'error' => $e->getMessage(),
                    'retry' => $retryCount
                ]);
                $this->reconnectDatabase();
                sleep(1); // Wait 1 second before retry
            }
        }
        
        try {
            // Step 1: Insert new brands in batch
            if (!empty($this->newBrandsBatch)) {
                $brandData = [];
                foreach ($this->newBrandsBatch as $brand) {
                    $brandData[] = [
                        'name' => $brand['name'],
                        'created_by' => $brand['created_by'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                
                DB::table('brands')->insert($brandData);
                
                // Get inserted brands and update cache
                $insertedBrands = Brand::where('created_by', $this->creatorId)
                    ->whereIn('name', array_column($this->newBrandsBatch, 'name'))
                    ->get();
                
                $tempBrandToRealMap = [];
                foreach ($insertedBrands as $insertedBrand) {
                    $cacheKey = strtolower(trim($insertedBrand->name));
                    // Find matching temp_id
                    foreach ($this->newBrandsBatch as $batchBrand) {
                        if (strtolower(trim($batchBrand['name'])) === $cacheKey) {
                            $tempBrandToRealMap[$batchBrand['temp_id']] = $insertedBrand->id;
                            // Update brand-category batch with real ID
                            foreach ($this->brandCategoryBatch as &$bc) {
                                if (isset($bc['temp_brand_id']) && $bc['temp_brand_id'] == $batchBrand['temp_id']) {
                                    $bc['brand_id'] = $insertedBrand->id;
                                    unset($bc['temp_brand_id']);
                                }
                            }
                            // Update sub-brands batch with real brand IDs
                            foreach ($this->newSubBrandsBatch as &$sb) {
                                if ($sb['brand_id'] == $batchBrand['temp_id']) {
                                    $sb['brand_id'] = $insertedBrand->id;
                                }
                            }
                            // Update products batch with real brand IDs
                            foreach ($this->newProductsBatch as &$prod) {
                                if ($prod['brand_id'] == $batchBrand['temp_id']) {
                                    $prod['brand_id'] = $insertedBrand->id;
                                }
                            }
                            break;
                        }
                    }
                    $this->brandCache[$cacheKey] = $insertedBrand;
                }
                
                $this->newBrandsBatch = [];
            }
            
            // Step 2: Insert brand-category links in batch
            if (!empty($this->brandCategoryBatch)) {
                // Filter out duplicates
                $uniqueBC = [];
                $seen = [];
                foreach ($this->brandCategoryBatch as $bc) {
                    $key = $bc['brand_id'] . '_' . $bc['product_service_category_id'];
                    if (!isset($seen[$key]) && !isset($this->brandCategoryCache[$key])) {
                        $uniqueBC[] = $bc;
                        $seen[$key] = true;
                        $this->brandCategoryCache[$key] = true;
                    }
                }
                
                if (!empty($uniqueBC)) {
                    DB::table('brand_category')->insert($uniqueBC);
                }
                $this->brandCategoryBatch = [];
            }
            
            // Step 3: Insert new sub-brands in batch
            if (!empty($this->newSubBrandsBatch)) {
                $subBrandData = [];
                foreach ($this->newSubBrandsBatch as $subBrand) {
                    $subBrandData[] = [
                        'name' => $subBrand['name'],
                        'brand_id' => $subBrand['brand_id'], // May need to resolve temp ID
                        'created_by' => $subBrand['created_by'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                
                DB::table('sub_brands')->insert($subBrandData);
                
                // Get inserted sub-brands and update cache
                $insertedSubBrands = VehicleModel::where('created_by', $this->creatorId)
                    ->whereIn('name', array_column($this->newSubBrandsBatch, 'name'))
                    ->get();
                
                $tempSubBrandToRealMap = [];
                foreach ($insertedSubBrands as $insertedSubBrand) {
                    $cacheKey = strtolower(trim($insertedSubBrand->name)) . '_' . $insertedSubBrand->brand_id;
                    // Find matching temp_id
                    foreach ($this->newSubBrandsBatch as $batchSubBrand) {
                        if (strtolower(trim($batchSubBrand['name'])) === strtolower(trim($insertedSubBrand->name)) &&
                            $batchSubBrand['brand_id'] == $insertedSubBrand->brand_id) {
                            $tempSubBrandToRealMap[$batchSubBrand['temp_id']] = $insertedSubBrand->id;
                            // Update products batch with real sub-brand IDs
                            foreach ($this->newProductsBatch as &$prod) {
                                if (isset($prod['sub_brand_id']) && $prod['sub_brand_id'] == $batchSubBrand['temp_id']) {
                                    $prod['sub_brand_id'] = $insertedSubBrand->id;
                                }
                            }
                            break;
                        }
                    }
                    $this->subBrandCache[$cacheKey] = $insertedSubBrand;
                }
                
                // Clear sub-brands batch AFTER processing
                $this->newSubBrandsBatch = [];
            }
            
            // Step 4: Insert new products in batch
            if (!empty($this->newProductsBatch)) {
                $productData = [];
                foreach ($this->newProductsBatch as $product) {
                    $productData[] = [
                        'name' => $product['name'],
                        'sku' => $product['sku'],
                        'sale_price' => $product['sale_price'],
                        'sale_price_base' => $product['sale_price_base'], 
                        'purchase_price' => $product['purchase_price'],
                        'quantity' => $product['quantity'],
                        'tax_id' => $product['tax_id'],
                        'category_id' => $product['category_id'],
                        'unit_id' => $product['unit_id'],
                        'brand_id' => $product['brand_id'], // May need to resolve temp ID
                        'sub_brand_id' => $product['sub_brand_id'],
                        'type' => $product['type'],
                        'description' => $product['description'],
                        'created_by' => $product['created_by'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                
                DB::table('product_services')->insert($productData);
                
                // Get inserted products and update cache
                $insertedProducts = ProductService::where('created_by', $this->creatorId)
                    ->whereIn('sku', array_column($this->newProductsBatch, 'sku'))
                    ->orderBy('id', 'desc') // Get most recent first
                    ->get();
                
                $tempToRealMap = [];
                foreach ($insertedProducts as $insertedProduct) {
                    $cacheKey = strtolower(trim($insertedProduct->sku));
                    // Find matching temp_id - match by SKU
                    foreach ($this->newProductsBatch as $batchProduct) {
                        if (strtolower(trim($batchProduct['sku'])) === $cacheKey) {
                            $tempToRealMap[$batchProduct['temp_id']] = $insertedProduct->id;
                            // Also update cache by temp ID for immediate resolution
                            if (isset($this->productCacheById[$batchProduct['temp_id']])) {
                                // Update the cached temp object with real ID
                                $tempProduct = $this->productCacheById[$batchProduct['temp_id']];
                                $tempProduct->id = $insertedProduct->id;
                            }
                            // Update cache with real product
                            $this->productCache[$cacheKey] = $insertedProduct;
                            $this->productCacheById[$insertedProduct->id] = $insertedProduct;
                            // Keep temp ID mapping for sub-product resolution
                            $this->productCacheById[$batchProduct['temp_id']] = $insertedProduct;
                            break;
                        }
                    }
                }
                
                \Log::info('Products inserted and temp IDs resolved', [
                    'inserted_count' => count($insertedProducts),
                    'temp_to_real_map_size' => count($tempToRealMap),
                    'temp_ids' => array_keys($tempToRealMap)
                ]);
                
                // Update sub-product batch with real product IDs
                foreach ($this->subProductBatch as &$sp) {
                    // Check if product_id is a temp ID (negative) and resolve it
                    if ($sp['product_id'] < 0) {
                        if (isset($tempToRealMap[$sp['product_id']])) {
                            $sp['product_id'] = $tempToRealMap[$sp['product_id']];
                            unset($sp['temp_product_id']);
                        } else {
                            // Product ID is temp but not in map - try to find it in cache
                            // This can happen if product was created in a previous batch
                            $tempId = $sp['product_id'];
                            $found = false;
                            
                            // Check if product exists in cache by temp ID
                            if (isset($this->productCacheById[$tempId])) {
                                $cachedProduct = $this->productCacheById[$tempId];
                                // If it's still a temp object, check if we can find it by SKU
                                if ($cachedProduct->id < 0 && isset($cachedProduct->sku)) {
                                    $skuKey = strtolower(trim($cachedProduct->sku));
                                    if (isset($this->productCache[$skuKey]) && $this->productCache[$skuKey]->id > 0) {
                                        $sp['product_id'] = $this->productCache[$skuKey]->id;
                                        unset($sp['temp_product_id']);
                                        $found = true;
                                    }
                                }
                            }
                            
                            if (!$found) {
                                // Still unresolved - this is an error
                                \Log::error('Sub-product has unresolved temp product ID after batch insert', [
                                    'temp_product_id' => $sp['product_id'],
                                    'product_no' => $sp['product_no'],
                                    'available_temp_ids' => array_keys($tempToRealMap),
                                    'temp_product_id_field' => $sp['temp_product_id'] ?? null
                                ]);
                            }
                        }
                    } elseif (isset($sp['temp_product_id']) && isset($tempToRealMap[$sp['temp_product_id']])) {
                        // Fallback: check temp_product_id field
                        $sp['product_id'] = $tempToRealMap[$sp['temp_product_id']];
                        unset($sp['temp_product_id']);
                    }
                }
                
                // Update custom field batch with real product IDs
                $resolvedProductCFs = 0;
                $resolvedSubProductCFProductIds = 0;
                foreach ($this->customFieldBatch as &$cf) {
                    // Resolve product custom fields (record_id is product ID)
                    if ($cf['module'] === 'product') {
                        // Check if record_id is a temp ID (negative) and resolve it
                        if ($cf['record_id'] < 0 && isset($tempToRealMap[$cf['record_id']])) {
                            $cf['record_id'] = $tempToRealMap[$cf['record_id']];
                            unset($cf['temp_record_id']);
                            $resolvedProductCFs++;
                        } elseif (isset($cf['temp_record_id']) && isset($tempToRealMap[$cf['temp_record_id']])) {
                            // Fallback: check temp_record_id field
                            $cf['record_id'] = $tempToRealMap[$cf['temp_record_id']];
                            unset($cf['temp_record_id']);
                            $resolvedProductCFs++;
                        }
                    }
                    
                    // Resolve sub-product custom fields (product_id reference)
                    if ($cf['module'] === 'sub-product' && isset($cf['product_id'])) {
                        if ($cf['product_id'] < 0 && isset($tempToRealMap[$cf['product_id']])) {
                            $cf['product_id'] = $tempToRealMap[$cf['product_id']];
                            unset($cf['temp_product_id']);
                            $resolvedSubProductCFProductIds++;
                        } elseif (isset($cf['temp_product_id']) && isset($tempToRealMap[$cf['temp_product_id']])) {
                            // Fallback: check temp_product_id field
                            $cf['product_id'] = $tempToRealMap[$cf['temp_product_id']];
                            unset($cf['temp_product_id']);
                            $resolvedSubProductCFProductIds++;
                        }
                    }
                }
                unset($cf); // Break reference
                
                if ($resolvedProductCFs > 0 || $resolvedSubProductCFProductIds > 0) {
                    \Log::info('Resolved custom field temp IDs', [
                        'product_cfs_resolved' => $resolvedProductCFs,
                        'sub_product_cf_product_ids_resolved' => $resolvedSubProductCFProductIds
                    ]);
                }
                
                // Clear products batch AFTER processing
                $this->newProductsBatch = [];
            }
            
            // Step 5: Insert sub-products in batch (1000 at a time)
            if (!empty($this->subProductBatch)) {
                // CRITICAL: Try to resolve any remaining temp product IDs before filtering
                // This handles cases where products were created in previous batches
                foreach ($this->subProductBatch as &$sp) {
                    if ($sp['product_id'] < 0) {
                        // Try to find product in cache by temp ID or SKU
                        $tempId = $sp['product_id'];
                        
                        // Check cache by temp ID first
                        if (isset($this->productCacheById[$tempId])) {
                            $cachedProduct = $this->productCacheById[$tempId];
                            // If cached product now has real ID, use it
                            if ($cachedProduct->id > 0) {
                                $sp['product_id'] = $cachedProduct->id;
                                unset($sp['temp_product_id']);
                                continue;
                            }
                        }
                        
                        // If still temp, try to find by looking up sub-products that reference this product
                        // This is a fallback - shouldn't normally happen
                        \Log::warning('Sub-product still has temp product ID before insert', [
                            'temp_product_id' => $tempId,
                            'product_no' => $sp['product_no'],
                            'temp_product_id_field' => $sp['temp_product_id'] ?? null
                        ]);
                    }
                }
                unset($sp); // Break reference
                
                // Filter out any sub-products with invalid product IDs (should have been resolved above)
                $validSubProducts = [];
                $skippedCount = 0;
                foreach ($this->subProductBatch as $sp) {
                    if ($sp['product_id'] <= 0) {
                        \Log::error('Skipping sub-product with invalid product_id after resolution attempt', [
                            'product_id' => $sp['product_id'],
                            'product_no' => $sp['product_no'],
                            'temp_product_id' => $sp['temp_product_id'] ?? null,
                            'batch_size' => count($this->subProductBatch)
                        ]);
                        $skippedCount++;
                        // CRITICAL: Decrement success count since this item is being lost
                        $this->successCount--;
                        continue;
                    }
                    $validSubProducts[] = $sp;
                }
                
                if ($skippedCount > 0) {
                    \Log::error('CRITICAL: Skipped sub-products due to invalid product IDs - DATA LOSS', [
                        'skipped_count' => $skippedCount,
                        'total_batch' => count($this->subProductBatch),
                        'valid_count' => count($validSubProducts)
                    ]);
                }
                
                // Process in chunks of 1000 and track inserted sub-product IDs
                $insertedSubProductIds = []; // Track: batch_index => sub_product_id
                if (!empty($validSubProducts)) {
                    // Preserve batch_index from original batch (set in addSubProductToBatch)
                    // If batch_index is not set, use current position as fallback
                    foreach ($validSubProducts as $idx => &$sp) {
                        if (!isset($sp['batch_index'])) {
                            $sp['batch_index'] = $idx;
                        }
                    }
                    unset($sp);
                    
                    $chunks = array_chunk($validSubProducts, 1000);
                    foreach ($chunks as $chunk) {
                        $subProductData = [];
                        $productNos = [];
                        $batchIndices = []; // Track batch_index for each item in chunk
                        
                        foreach ($chunk as $sp) {
                            $subProductData[] = [
                                'product_no' => $sp['product_no'],
                                'product_id' => $sp['product_id'],
                                'sale_price' => $sp['sale_price'], 
                                'purchase_price' => $sp['purchase_price'],
                                'quantity' => $sp['quantity'],
                                'initial_stock' => $sp['initial_stock'],
                                'initial_rate' => $sp['initial_rate'],
                                'warehouse_id' => $sp['warehouse_id'],
                                'SP_sku' => $sp['SP_sku'],
                                'flag' => $sp['flag'],
                                'booked' => $sp['booked'],
                                'created_by' => $sp['created_by'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $productNos[] = $sp['product_no'];
                            $batchIndices[] = $sp['batch_index'];
                        }
                        
                        // Store created_at timestamp before insert to match exactly
                        $insertTimestamp = now();
                        
                        DB::table('sub_products')->insert($subProductData);
                        
                        // Fetch inserted sub-products by exact match criteria
                        // Match by product_no, product_id, sale_price, purchase_price, quantity, and created_at
                        // This ensures we get the exact sub-products we just inserted
                        $insertedSubProducts = SubProduct::whereIn('chassis_no', $productNos)
                            ->whereIn('product_id', array_unique(array_column($chunk, 'product_id')))
                            ->where('created_by', $this->creatorId)
                            ->where('created_at', '>=', $insertTimestamp->copy()->subSecond()) // Match within 1 second
                            ->orderBy('id', 'desc')
                            ->limit(count($chunk))
                            ->get();
                        
                        // Match inserted sub-products to chunk items by comparing all key fields
                        // This ensures accurate matching even if product_no + product_id are duplicated
                        foreach ($chunk as $sp) {
                            $batchIndex = $sp['batch_index'];
                            $matched = false;
                            
                            foreach ($insertedSubProducts as $inserted) {
                                // Check if this inserted sub-product matches the chunk item
                                if ($inserted->product_no === $sp['product_no'] &&
                                    $inserted->product_id == $sp['product_id'] &&
                                    abs($inserted->sale_price - $sp['sale_price']) < 0.01 &&
                                    abs($inserted->purchase_price - $sp['purchase_price']) < 0.01 &&
                                    abs($inserted->quantity - $sp['quantity']) < 0.01) {
                                    
                                    // Found match - link batch_index to sub_product_id
                                    $insertedSubProductIds[$batchIndex] = $inserted->id;
                                    $matched = true;
                                    
                                    // Remove from list to avoid duplicate matches
                                    $insertedSubProducts = $insertedSubProducts->reject(function($item) use ($inserted) {
                                        return $item->id === $inserted->id;
                                    });
                                    break;
                                }
                            }
                            
                            if (!$matched) {
                                \Log::warning('Could not find inserted sub-product for custom field linking', [
                                    'product_no' => $sp['product_no'],
                                    'product_id' => $sp['product_id'],
                                    'batch_index' => $batchIndex,
                                    'sale_price' => $sp['sale_price'],
                                    'purchase_price' => $sp['purchase_price']
                                ]);
                            }
                        }
                    }
                }
                
                // Link custom fields to sub-products using batch_index
                $linkedSubProductCFs = 0;
                if (!empty($insertedSubProductIds) && !empty($this->customFieldBatch)) {
                    foreach ($this->customFieldBatch as &$cf) {
                        if ($cf['module'] === 'sub-product' && isset($cf['batch_index']) && isset($insertedSubProductIds[$cf['batch_index']])) {
                            $cf['record_id'] = $insertedSubProductIds[$cf['batch_index']];
                            unset($cf['batch_index']);
                            $linkedSubProductCFs++;
                        }
                    }
                    unset($cf); // Break reference
                    
                    if ($linkedSubProductCFs > 0) {
                        \Log::info('Linked sub-product custom fields', [
                            'linked_count' => $linkedSubProductCFs,
                            'total_sub_product_cfs' => count(array_filter($this->customFieldBatch, function($cf) {
                                return $cf['module'] === 'sub-product';
                            }))
                        ]);
                    }
                }
                
                // Process opening balance entries for sub-products with quantity
                if (!empty($validSubProducts)) {
                    $this->processOpeningBalance($validSubProducts, $insertedSubProductIds);
                }
                
                // Clear sub-products batch AFTER processing
                $this->subProductBatch = [];
                
                \Log::info('Sub-products batch inserted', [
                    'inserted' => count($validSubProducts),
                    'skipped' => $skippedCount
                ]);
            }
            
            // Step 6: Insert custom fields in batch
            if (!empty($this->customFieldBatch)) {
                $cfData = [];
                $skippedCustomFields = 0;
                
                foreach ($this->customFieldBatch as $cf) {
                    // Only insert if record_id is valid (positive integer)
                    if (isset($cf['record_id']) && is_numeric($cf['record_id']) && $cf['record_id'] > 0) {
                        $cfData[] = [
                            'field_id' => $cf['field_id'],
                            'record_id' => $cf['record_id'],
                            'value' => $cf['value'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    } else {
                        $skippedCustomFields++;
                        // Log warning for unresolved custom fields
                        if ($skippedCustomFields <= 10) {
                            \Log::warning('Skipping custom field with invalid record_id', [
                                'module' => $cf['module'] ?? 'unknown',
                                'field_id' => $cf['field_id'] ?? null,
                                'record_id' => $cf['record_id'] ?? null,
                                'has_batch_index' => isset($cf['batch_index']),
                                'has_product_id' => isset($cf['product_id'])
                            ]);
                        }
                    }
                }
                
                if (!empty($cfData)) {
                    // Use insertOrIgnore to handle duplicates
                    foreach (array_chunk($cfData, 500) as $chunk) {
                        DB::table('custom_field_values')->insertOrIgnore($chunk);
                    }
                    
                    \Log::info('Custom fields batch inserted', [
                        'inserted' => count($cfData),
                        'skipped' => $skippedCustomFields,
                        'total_in_batch' => count($this->customFieldBatch)
                    ]);
                } else {
                    \Log::warning('No valid custom fields to insert', [
                        'total_in_batch' => count($this->customFieldBatch),
                        'skipped' => $skippedCustomFields
                    ]);
                }
                
                // Clear custom fields batch AFTER processing
                $this->customFieldBatch = [];
            }
            
            DB::commit();
            
            // Log successful batch execution with detailed counts
            $insertedCounts = [
                'sub_products_inserted' => isset($validSubProducts) ? count($validSubProducts) : 0,
                'sub_products_skipped' => isset($skippedCount) ? $skippedCount : 0,
                'brands_inserted' => isset($brandsInserted) ? $brandsInserted : 0,
                'sub_brands_inserted' => isset($subBrandsInserted) ? $subBrandsInserted : 0,
                'products_inserted' => isset($productsInserted) ? $productsInserted : 0
            ];
            
            \Log::info('Batch inserts executed successfully', [
                'batch_count' => $this->batchCount,
                'total_processed' => $this->successCount,
                'inserted_counts' => $insertedCounts,
                'batch_sizes_before' => [
                    'sub_products' => isset($originalSubProductCount) ? $originalSubProductCount : count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]
            ]);
            
            $this->batchCount = 0;
            $this->transactionActive = false;
            
        } catch (\PDOException $e) {
            // Handle MySQL connection errors specifically
            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false || 
                strpos($e->getMessage(), '2006') !== false) {
                \Log::error('MySQL connection lost during batch insert - attempting recovery', [
                    'error' => $e->getMessage(),
                    'batch_sizes' => [
                        'sub_products' => count($this->subProductBatch),
                        'brands' => count($this->newBrandsBatch),
                        'sub_brands' => count($this->newSubBrandsBatch),
                        'products' => count($this->newProductsBatch)
                    ]
                ]);
                
                // Try to rollback if transaction was started
                try {
                    DB::rollBack();
                } catch (\Exception $rollbackException) {
                    // Ignore rollback errors if connection is gone
                }
                
                // Reconnect and retry the batch insert
                $this->reconnectDatabase();
                
                // Retry the entire batch insert once
                try {
                    \Log::info('Retrying batch insert after MySQL reconnection');
                    return $this->executeBatchInserts(); // Recursive retry
                } catch (\Exception $retryException) {
                    \Log::error('Batch insert retry failed', [
                        'error' => $retryException->getMessage()
                    ]);
                    throw $retryException;
                }
            }
            
            // Other PDO exceptions - rollback and throw
            try {
                DB::rollBack();
            } catch (\Exception $rollbackException) {
                // Ignore rollback errors
            }
            \Log::error('Batch insert failed (PDOException)', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'batch_sizes' => [
                    'sub_products' => count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]
            ]);
            throw $e;
        } catch (\Exception $e) {
            try {
                DB::rollBack();
            } catch (\Exception $rollbackException) {
                // Ignore rollback errors
            }
            \Log::error('Batch insert failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'batch_sizes' => [
                    'sub_products' => count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]
            ]);
            throw $e;
        }
    }
    
    /**
     * Commit any pending batch transaction
     * Call this after import completes to ensure all data is saved
     * CRITICAL: This must be called to save remaining items (< batchSize)
     */
    public function commitPendingBatch()
    {
        try {
            // Execute any remaining batch inserts (CRITICAL - saves items that didn't reach batchSize)
            if ($this->batchCount > 0 || !empty($this->subProductBatch) || !empty($this->newBrandsBatch) || 
                !empty($this->newSubBrandsBatch) || !empty($this->newProductsBatch)) {
                \Log::info('Committing pending batches', [
                    'batch_count' => $this->batchCount,
                    'sub_products' => count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]);
                $this->executeBatchInserts();
            }
            
            if ($this->transactionActive) {
                try {
                    DB::commit();
                    $this->batchCount = 0;
                    $this->transactionActive = false;
                } catch (\Exception $e) {
                    try {
                        DB::rollBack();
                    } catch (\Exception $rollbackException) {
                        // Ignore rollback errors
                    }
                    $this->batchCount = 0;
                    $this->transactionActive = false;
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to commit pending batch', [
                'error' => $e->getMessage(),
                'batch_count' => $this->batchCount,
                'remaining_items' => [
                    'sub_products' => count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]
            ]);
            throw $e;
        }
    }
    
    /**
     * Called when import is complete - commit any pending batch
     * Safety net to ensure no data is lost
     */
    public function __destruct()
    {
        // CRITICAL: Save any remaining batches before object is destroyed
        // This is a safety net in case commitPendingBatch() wasn't called
        try {
            if ($this->batchCount > 0 || !empty($this->subProductBatch) || !empty($this->newBrandsBatch) || 
                !empty($this->newSubBrandsBatch) || !empty($this->newProductsBatch)) {
                \Log::warning('Saving batches in destructor - commitPendingBatch may not have been called', [
                    'batch_count' => $this->batchCount,
                    'remaining_items' => [
                        'sub_products' => count($this->subProductBatch),
                        'brands' => count($this->newBrandsBatch),
                        'sub_brands' => count($this->newSubBrandsBatch),
                        'products' => count($this->newProductsBatch)
                    ]
                ]);
                $this->executeBatchInserts();
            }
        } catch (\Exception $e) {
            // Log but don't throw in destructor
            \Log::error('Failed to save batches in destructor', [
                'error' => $e->getMessage(),
                'remaining_items' => [
                    'sub_products' => count($this->subProductBatch),
                    'brands' => count($this->newBrandsBatch),
                    'sub_brands' => count($this->newSubBrandsBatch),
                    'products' => count($this->newProductsBatch)
                ]
            ]);
        }
        
        // Commit any pending batch transaction
        if ($this->transactionActive) {
            try {
                DB::commit();
                $this->transactionActive = false;
            } catch (\Exception $e) {
                // Silently rollback in destructor to avoid issues
                try {
                    DB::rollBack();
                } catch (\Exception $rollbackException) {
                    // Ignore rollback errors in destructor
                }
                $this->transactionActive = false;
            }
        }
        
        // Ensure indexes are re-enabled even if import fails
        if ($this->foreignKeyChecksDisabled || $this->indexesDisabled) {
            $this->enableIndexes();
        }
        
        // Query logging remains disabled for better performance
        // It will be re-enabled automatically by Laravel if needed
    }

    /**
     * Manage memory by clearing caches when they get too large
     */
    private function manageMemory()
    {
        $totalCacheSize = count($this->brandCache) + count($this->subBrandCache) + 
                         count($this->productCache) + count($this->categoryCache) + 
                         count($this->unitCache);
        
        if ($totalCacheSize > $this->maxCacheSize) {
            // Clear half of each cache (keep most recently used)
            $this->clearCacheHalf('brandCache');
            $this->clearCacheHalf('subBrandCache');
            $this->clearCacheHalf('productCache');
            $this->clearCacheHalf('categoryCache');
            $this->clearCacheHalf('unitCache');
            
            // Force garbage collection
            gc_collect_cycles();
            
            \Log::info('Memory management: Cleared caches', [
                'row_number' => $this->rowNumber,
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
            ]);
        }
    }
    
    /**
     * Clear half of a cache array
     */
    private function clearCacheHalf($cacheName)
    {
        if (!property_exists($this, $cacheName) || empty($this->$cacheName)) {
            return;
        }
        
        $keys = array_keys($this->$cacheName);
        $halfCount = (int)ceil(count($keys) / 2);
        $keysToRemove = array_slice($keys, 0, $halfCount);
        
        foreach ($keysToRemove as $key) {
            unset($this->$cacheName[$key]);
        }
    }

    /**
     * Get brand from cache only (zero DB queries - ERP level optimization)
     * New brands are collected for batch insert
     */
    private function getOrCreateBrand($brandName, $categoryId)
    {
        $brandName = trim($brandName);
        $cacheKey = strtolower($brandName); // Normalize cache key
        
        // Check cache first (zero queries)
        if (isset($this->brandCache[$cacheKey])) {
            $brand = $this->brandCache[$cacheKey];
            
            // Collect brand-category link for batch insert if not already linked
            if ($categoryId) {
                $bcKey = $brand->id . '_' . $categoryId;
                if (!isset($this->brandCategoryCache[$bcKey])) {
                    $this->brandCategoryBatch[] = [
                        'brand_id' => $brand->id,
                        'product_service_category_id' => $categoryId
                    ];
                    $this->brandCategoryCache[$bcKey] = true; // Mark as queued
                }
            }
            
            return $brand;
        }

        // Brand not found - add to batch for creation (zero queries in row loop)
        $tempId = -(count($this->newBrandsBatch) + 1); // Temporary negative ID
        $this->newBrandsBatch[] = [
            'name' => $brandName,
            'created_by' => $this->creatorId,
            'temp_id' => $tempId,
            'cache_key' => $cacheKey
        ];
        
        // Create temporary brand object for immediate use
        $brand = new Brand();
        $brand->id = $tempId;
        $brand->name = $brandName;
        $brand->created_by = $this->creatorId;
        
        // Cache temporary brand
        $this->brandCache[$cacheKey] = $brand;
        
        // Collect brand-category link for batch insert
        if ($categoryId) {
            $this->brandCategoryBatch[] = [
                'brand_id' => $tempId, // Will be updated after batch insert
                'product_service_category_id' => $categoryId,
                'temp_brand_id' => $tempId
            ];
        }
        
        return $brand;
    }

    /**
     * Get sub-brand from cache only (zero DB queries - ERP level optimization)
     * New sub-brands are collected for batch insert
     */
    private function getOrCreateSubBrand($subBrandName, $brandId)
    {
        $subBrandName = trim($subBrandName);
        $cacheKey = strtolower($subBrandName) . '_' . $brandId;
        
        // Check cache first (zero queries)
        if (isset($this->subBrandCache[$cacheKey])) {
            return $this->subBrandCache[$cacheKey];
        }

        // Sub-brand not found - add to batch for creation (zero queries in row loop)
        $tempId = -(count($this->newSubBrandsBatch) + 10000); // Temporary negative ID (offset to avoid conflicts)
        $this->newSubBrandsBatch[] = [
            'name' => $subBrandName,
            'brand_id' => $brandId,
            'created_by' => $this->creatorId,
            'temp_id' => $tempId,
            'cache_key' => $cacheKey
        ];
        
        // Create temporary sub-brand object for immediate use
        $subBrand = new VehicleModel();
        $subBrand->id = $tempId;
        $subBrand->name = $subBrandName;
        $subBrand->brand_id = $brandId;
        $subBrand->created_by = $this->creatorId;
        
        // Cache temporary sub-brand
        $this->subBrandCache[$cacheKey] = $subBrand;
        
        return $subBrand;
    }

    /**
     * Get product from cache only (zero DB queries - ERP level optimization)
     * New products are collected for batch insert
     */
    private function getOrCreateProduct($items, $brandId, $subBrandId, $categoryId = null)
    {
        $sku = trim($items['product_sku']);
        $cacheKey = strtolower($sku); // Normalize cache key
        
        // Check cache first (zero queries)
        if (isset($this->productCache[$cacheKey])) {
            $product = $this->productCache[$cacheKey];
            // Ensure it's also cached by ID
            if (!isset($this->productCacheById[$product->id])) {
                $this->productCacheById[$product->id] = $product;
            }
            return $product;
        }

        // Get category if not provided (from cache only)
        if (!$categoryId) {
            $categoryId = $this->getCategoryId($items);
            if (!$categoryId) {
                throw new \Exception('Category is required. Please provide category_id or category_name.');
            }
        }

        // Get unit (from cache only)
        $unitId = $this->getUnitId($items);
        if (!$unitId) {
            throw new \Exception('Unit is required. Please provide unit_id or unit_name.');
        }

        // Handle taxes (from cache only)
        $taxIds = $this->getTaxIds($items);

        // Get sell price from Excel (base price)
        $baseSalePrice = floatval($items['product_sale_price'] ?? $items['sale_price'] ?? 0);
        $totalVatRate = 0;
        
        // Calculate VAT rate
        // Priority 1: Use VAT column if provided (direct VAT rate)
        // Priority 2: Calculate from tax IDs (existing behavior)
        $vatColumn = $items['vat'] ?? $items['vat_rate'] ?? $items['vat_percentage'] ?? null;
        if ($vatColumn !== null && $vatColumn !== '') {
            // Use VAT rate directly from column
            $totalVatRate = floatval($vatColumn);
        } else {
            // Fallback: Calculate VAT from tax IDs (existing behavior)
            foreach ($taxIds as $taxId) {
                if (isset($this->preloadedTaxes[$taxId])) {
                    $tax = $this->preloadedTaxes[$taxId];
                    $totalVatRate += floatval($tax->rate ?? 0);
                }
            }
        }
        
        // Calculate sell price with VAT (base price + VAT)
        $salePriceWithVat = $totalVatRate > 0 
            ? $baseSalePrice * (1 + ($totalVatRate / 100))
            : $baseSalePrice;

        // Product not found - add to batch for creation (zero queries in row loop)
        $tempId = -(count($this->newProductsBatch) + 20000); // Temporary negative ID (offset to avoid conflicts)
        $this->newProductsBatch[] = [
            'name' => trim($items['product_name'] ?? 'Product ' . $sku),
            'sku' => $sku,
            'sale_price' => $salePriceWithVat, // Store sell price from Excel WITH VAT (after calculation)
            'sale_price_base' => $baseSalePrice, // Store sell price from Excel (base price, no VAT)
            'purchase_price' => floatval($items['product_purchase_price'] ?? $items['purchase_price'] ?? 0),
            'quantity' => 0,
            'tax_id' => implode(',', $taxIds),
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'brand_id' => $brandId,
            'sub_brand_id' => $subBrandId ?? 0,
            'type' => trim($items['product_type'] ?? 'product'),
            'description' => $items['product_description'] ?? $items['description'] ?? null,
            'created_by' => $this->creatorId,
            'temp_id' => $tempId,
            'cache_key' => $cacheKey
        ];
        
        // Create temporary product object for immediate use
        $product = new ProductService();
        $product->id = $tempId;
        $product->sku = $sku;
        $product->name = trim($items['product_name'] ?? 'Product ' . $sku);
        $product->brand_id = $brandId;
        $product->sub_brand_id = $subBrandId ?? 0;
        $product->category_id = $categoryId;
        $product->created_by = $this->creatorId;
        
        // Cache temporary product
        $this->productCache[$cacheKey] = $product;
        $this->productCacheById[$tempId] = $product;
        
        return $product;
    }

    /**
     * Add sub-products to batch - handles both warehouse columns and single warehouse_id
     * If warehouse columns are detected, creates one sub-product per warehouse with quantity
     * Otherwise, creates a single sub-product with warehouse_id
     */
    private function addSubProductsToBatch($itemsOriginal, $itemsLower, $productId)
    {
        $productNo = trim($itemsLower['sub_product_no']);
        
        // Get sale price from Excel (save as-is, no VAT calculation)
        $subProductSalePrice = floatval($itemsLower['sub_product_sale_price'] ?? $itemsLower['sale_price'] ?? 0);
        
        
        // Detect warehouse columns (columns that match warehouse names)
        $warehouseColumns = [];
        foreach ($itemsOriginal as $columnName => $value) {
            // Normalize column name for comparison - handle spaces, underscores, and case
            $normalizedColumnName = trim($columnName);
            $normalizedColumnNameLower = strtolower(str_replace([' ', '_'], '', $normalizedColumnName));
            
            // Skip standard columns that are not warehouses
            $standardColumns = [
                'brand_name', 'sub_brand_name', 'product_sku', 'product_name', 
                'category_name', 'category_id', 'unit_name', 'unit_id',
                'product_sale_price', 'sale_price', 'product_purchase_price', 'purchase_price',
                'vat', 'vat_rate', 'vat_percentage', 'product_type',
                'sub_product_no', 'quantity', 'sub_product_quantity', 'warehouse_id',
                'sub_product_sale_price', 'sub_product_purchase_price', 'initial_stock',
                'initial_rate', 'sub_product_sku', 'product_description', 'description'
            ];
            
            // Normalize standard column names for comparison
            $normalizedStandardColumns = array_map(function($col) {
                return strtolower(str_replace([' ', '_'], '', $col));
            }, $standardColumns);
            
            // Skip if it's a standard column
            if (in_array($normalizedColumnNameLower, $normalizedStandardColumns)) {
                continue;
            }
            
            // Check if this column matches a warehouse name
            // Try multiple matching strategies for flexibility
            foreach ($this->preloadedWarehouses as $warehouseName => $warehouseId) {
                $warehouseNameTrimmed = trim($warehouseName);
                $warehouseNameLower = strtolower($warehouseNameTrimmed);
                $normalizedWarehouseName = strtolower(str_replace([' ', '_', '-'], '', $warehouseNameTrimmed));
                
                // Multiple matching strategies:
                // 1. Exact match (case-sensitive)
                // 2. Case-insensitive match
                // 3. Match ignoring spaces/underscores/dashes
                $matches = (
                    $normalizedColumnName === $warehouseNameTrimmed ||
                    $normalizedColumnNameLower === $warehouseNameLower ||
                    $normalizedColumnNameLower === $normalizedWarehouseName ||
                    str_replace([' ', '_', '-'], '', $normalizedColumnNameLower) === $normalizedWarehouseName
                );
                
                if ($matches) {
                    $quantity = floatval($value ?? 0);
                    if ($quantity > 0) {
                        // Validate warehouse ID
                        if (empty($warehouseId) || $warehouseId <= 0) {
                            \Log::warning('StockImport: Invalid warehouse ID in preloaded warehouses', [
                                'row' => $this->rowNumber,
                                'warehouse_name' => $warehouseNameTrimmed,
                                'warehouse_id' => $warehouseId
                            ]);
                            continue; // Skip this warehouse
                        }
                        
                        $warehouseColumns[] = [
                            'name' => $warehouseNameTrimmed,
                            'id' => (int)$warehouseId, // Ensure it's an integer
                            'quantity' => $quantity
                        ];
                        
                        // Log for debugging (only first few to avoid spam)
                        if (count($warehouseColumns) <= 3 && $this->rowNumber <= 5) {
                            \Log::info('StockImport: Warehouse column detected', [
                                'row' => $this->rowNumber,
                                'column_name' => $columnName,
                                'normalized_column' => $normalizedColumnNameLower,
                                'warehouse_name' => $warehouseNameTrimmed,
                                'warehouse_id' => $warehouseId,
                                'quantity' => $quantity
                            ]);
                        }
                    }
                    break; // Found match, move to next column
                }
            }
        }
        
        // Log if no warehouse columns found but warehouses exist (for debugging)
        if (empty($warehouseColumns) && !empty($this->preloadedWarehouses) && $this->rowNumber <= 5) {
            \Log::debug('StockImport: No warehouse columns detected', [
                'row' => $this->rowNumber,
                'available_warehouses' => array_keys($this->preloadedWarehouses),
                'column_names' => array_keys($itemsOriginal)
            ]);
        }
        
        // If warehouse columns are found, create sub-products for each warehouse
        if (!empty($warehouseColumns)) {
            foreach ($warehouseColumns as $warehouseData) {
                $quantity = $warehouseData['quantity'];
                $warehouseId = $warehouseData['id'];
                
                // Validate warehouse ID
                if (empty($warehouseId) || $warehouseId <= 0) {
                    \Log::warning('StockImport: Invalid warehouse ID detected', [
                        'row' => $this->rowNumber,
                        'warehouse_data' => $warehouseData,
                        'product_no' => $productNo
                    ]);
                    continue; // Skip this warehouse
                }
                
                // Ensure warehouse_id is a valid integer
                $warehouseIdInt = (int)$warehouseId;
                if ($warehouseIdInt <= 0) {
                    \Log::error('StockImport: Invalid warehouse ID after conversion', [
                        'row' => $this->rowNumber,
                        'warehouse_id' => $warehouseId,
                        'warehouse_id_int' => $warehouseIdInt,
                        'product_no' => $productNo
                    ]);
                    continue; // Skip this warehouse
                }
                
                // Get current batch index for this sub-product (used for custom field linking)
                $batchIndex = count($this->subProductBatch);
                
                // Use original product_no from Excel (do not append warehouse ID)
                // Each warehouse will have its own sub-product record with the same product_no
                
                // Prepare sub-product data for batch insert
                // Store sale price from Excel as-is (no VAT calculation)
                $this->subProductBatch[] = [
                    'product_no' => $productNo,
                    'product_id' => $productId, // May be temporary ID, will be resolved in batch insert
                    'sale_price' => $subProductSalePrice, // Store sale price from Excel as-is
                    'purchase_price' => floatval($itemsLower['sub_product_purchase_price'] ?? $itemsLower['purchase_price'] ?? 0),
                    'quantity' => $quantity,
                    'initial_stock' => $quantity,
                    'initial_rate' => floatval($itemsLower['initial_rate'] ?? $itemsLower['sub_product_purchase_price'] ?? $itemsLower['purchase_price'] ?? 0),
                    'warehouse_id' => $warehouseIdInt, // Ensure it's a valid integer
                    'SP_sku' => $itemsLower['sub_product_sku'] ?? null,
                    'flag' => 1, // Purchased
                    'booked' => 0, // Free
                    'created_by' => $this->creatorId,
                    'temp_product_id' => $productId < 0 ? $productId : null, // Track if product ID is temporary
                    'batch_index' => $batchIndex // Track position in batch for custom field linking
                ];
                
                // Log successful warehouse sub-product creation (first few only)
                if ($this->rowNumber <= 5 && count($warehouseColumns) <= 3) {
                    \Log::info('StockImport: Creating sub-product for warehouse', [
                        'row' => $this->rowNumber,
                        'product_no' => $productNo,
                        'warehouse_id' => $warehouseIdInt,
                        'warehouse_name' => $warehouseData['name'],
                        'quantity' => $quantity
                    ]);
                }
            }
        } else {
            // No warehouse columns found - use traditional single warehouse_id approach
            // Get current batch index for this sub-product (used for custom field linking)
            $batchIndex = count($this->subProductBatch);
            
            // Prepare sub-product data for batch insert
            // Store sale price from Excel as-is (no VAT calculation)
            $this->subProductBatch[] = [
                'product_no' => $productNo,
                'product_id' => $productId, // May be temporary ID, will be resolved in batch insert
                'sale_price' => $subProductSalePrice, // Store sale price from Excel as-is
                'purchase_price' => floatval($itemsLower['sub_product_purchase_price'] ?? $itemsLower['purchase_price'] ?? 0),
                'quantity' => floatval($itemsLower['quantity'] ?? $itemsLower['sub_product_quantity'] ?? 1),
                'initial_stock' => floatval($itemsLower['initial_stock'] ?? $itemsLower['quantity'] ?? 1),
                'initial_rate' => floatval($itemsLower['initial_rate'] ?? $itemsLower['sub_product_purchase_price'] ?? $itemsLower['purchase_price'] ?? 0),
                'warehouse_id' => !empty($itemsLower['warehouse_id']) ? intval($itemsLower['warehouse_id']) : null,
                'SP_sku' => $itemsLower['sub_product_sku'] ?? null,
                'flag' => 1, // Purchased
                'booked' => 0, // Free
                'created_by' => $this->creatorId,
                'temp_product_id' => $productId < 0 ? $productId : null, // Track if product ID is temporary
                'batch_index' => $batchIndex // Track position in batch for custom field linking
            ];
        }
    }
    
    /**
     * Collect custom fields for batch processing (zero queries)
     */
    private function collectCustomFieldsForBatch($product, $items)
    {
        // Collect product custom fields
        foreach ($this->preloadedCustomFields as $key => $field) {
            if ($field->module === 'product') {
                $fieldName = strtolower($field->name);
                $possibleKeys = [
                    'product_' . $fieldName,
                    'product_cf_' . $fieldName,
                    'cf_' . $fieldName,
                    $fieldName
                ];
                
                foreach ($possibleKeys as $checkKey) {
                    if (isset($items[$checkKey]) && ($items[$checkKey] === '0' || $items[$checkKey] === 0 || !empty($items[$checkKey]))) {
                        $fieldValue = trim((string)$items[$checkKey]);
                        if ($this->validateCustomFieldValue($field, $fieldValue)) {
                            $this->customFieldBatch[] = [
                                'field_id' => $field->id,
                                'record_id' => $product->id, // May be temporary ID
                                'value' => $fieldValue,
                                'module' => 'product',
                                'temp_record_id' => $product->id < 0 ? $product->id : null
                            ];
                            break;
                        }
                    }
                }
            }
        }
        
        // Collect sub-product custom fields (will be processed after sub-product is created)
        // Store with product reference for later processing
        foreach ($this->preloadedCustomFields as $key => $field) {
            if ($field->module === 'sub-product' && $field->categories->contains('id', $product->category_id)) {
                $fieldName = strtolower($field->name);
                $possibleKeys = [
                    'sub_product_' . $fieldName,
                    'sub_product_cf_' . $fieldName,
                    'cf_' . $fieldName,
                    $fieldName
                ];
                
                foreach ($possibleKeys as $checkKey) {
                    if (isset($items[$checkKey]) && ($items[$checkKey] === '0' || $items[$checkKey] === 0 || !empty($items[$checkKey]))) {
                        $fieldValue = trim((string)$items[$checkKey]);
                        if ($this->validateCustomFieldValue($field, $fieldValue)) {
                            // Store with product reference - will be linked to sub-product after batch insert
                            // Use the current batch index (before adding the sub-product)
                            $currentBatchIndex = count($this->subProductBatch);
                            $this->customFieldBatch[] = [
                                'field_id' => $field->id,
                                'record_id' => null, // Will be set after sub-product creation
                                'value' => $fieldValue,
                                'module' => 'sub-product',
                                'product_id' => $product->id,
                                'temp_product_id' => $product->id < 0 ? $product->id : null,
                                'batch_index' => $currentBatchIndex // Link to sub-product that will be added next
                            ];
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get category ID from name or ID (using pre-loaded data)
     */
    private function getCategoryId($items)
    {
        // Try category_id first
        if (!empty($items['category_id'])) {
            $categoryId = intval($items['category_id']);
            if (isset($this->preloadedCategories[$categoryId])) {
                return $categoryId;
            }
            if (isset($this->categoryCache[$categoryId])) {
                return $categoryId;
            }
            // Fallback to database query if not pre-loaded
            $category = ProductServiceCategory::where('id', $categoryId)
                ->where('created_by', $this->creatorId)
                ->first();
            if ($category) {
                $this->categoryCache[$categoryId] = $categoryId;
                $this->preloadedCategories[$categoryId] = $categoryId;
                return $categoryId;
            }
        }

        // Try category_name (using pre-loaded data)
        if (!empty($items['category_name'])) {
            $categoryName = strtolower(trim($items['category_name']));
            if (isset($this->preloadedCategories[$categoryName])) {
                return $this->preloadedCategories[$categoryName];
            }
            // Fallback to database query if not pre-loaded
            $category = ProductServiceCategory::where('name', $items['category_name'])
                ->where('created_by', $this->creatorId)
                ->first();
            if ($category) {
                $this->preloadedCategories[$categoryName] = $category->id;
                $this->preloadedCategories[$category->id] = $category->id;
                return $category->id;
            }
        }

        return null;
    }

    /**
     * Get unit ID from name or ID (using pre-loaded data)
     */
    private function getUnitId($items)
    {
        // Try unit_id first
        if (!empty($items['unit_id'])) {
            $unitId = intval($items['unit_id']);
            if (isset($this->preloadedUnits[$unitId])) {
                return $unitId;
            }
            if (isset($this->unitCache[$unitId])) {
                return $unitId;
            }
            // Fallback to database query if not pre-loaded
            $unit = ProductServiceUnit::where('id', $unitId)
                ->where('created_by', $this->creatorId)
                ->first();
            if ($unit) {
                $this->unitCache[$unitId] = $unitId;
                $this->preloadedUnits[$unitId] = $unitId;
                return $unitId;
            }
        }

        // Try unit_name (using pre-loaded data)
        if (!empty($items['unit_name'])) {
            $unitName = strtolower(trim($items['unit_name']));
            if (isset($this->preloadedUnits[$unitName])) {
                return $this->preloadedUnits[$unitName];
            }
            // Fallback to database query if not pre-loaded
            $unit = ProductServiceUnit::where('name', $items['unit_name'])
                ->where('created_by', $this->creatorId)
                ->first();
            if ($unit) {
                $this->preloadedUnits[$unitName] = $unit->id;
                $this->preloadedUnits[$unit->id] = $unit->id;
                return $unit->id;
            }
        }

        return null;
    }

    /**
     * Get tax IDs from tax string (using pre-loaded data)
     */
    private function getTaxIds($items)
    {
        $taxString = $items['tax'] ?? $items['tax_id'] ?? '';
        $taxIds = [];

        if (!empty($taxString)) {
            $taxes = preg_split('/[;,]/', $taxString);
            $taxes = array_map('trim', $taxes);
            $taxes = array_filter($taxes);

            foreach ($taxes as $tax) {
                $taxId = intval($tax);
                // Check pre-loaded taxes first
                if (isset($this->preloadedTaxes[$taxId])) {
                    $taxIds[] = $taxId;
                } elseif (isset($this->taxCache[$taxId])) {
                    $taxIds[] = $taxId;
                } else {
                    // Fallback to database query if not pre-loaded
                    $taxModel = Tax::where('id', $taxId)
                        ->where('created_by', $this->creatorId)
                        ->first();
                    if ($taxModel) {
                        $this->preloadedTaxes[$taxId] = $taxModel;
                        $this->taxCache[$taxId] = $taxId;
                        $taxIds[] = $taxModel->id;
                    }
                }
            }
        }

        return $taxIds;
    }

    /**
     * Process custom fields for product (using pre-loaded data)
     */
    private function processProductCustomFields($product, $items)
    {
        try {
            // Get custom fields from pre-loaded cache
            $customFields = [];
            foreach ($this->preloadedCustomFields as $key => $field) {
                if ($field->module === 'product') {
                    $customFields[] = $field;
                }
            }

            // If no pre-loaded fields, fallback to query (shouldn't happen often)
            if (empty($customFields)) {
                $customFields = CustomField::where('module', 'product')
                    ->where('created_by', $this->creatorId)
                    ->get();
            }

            $customFieldData = [];

            foreach ($customFields as $customField) {
                $fieldName = strtolower($customField->name);
                
                // Check various possible column names
                $possibleKeys = [
                    'product_' . $fieldName,
                    'product_cf_' . $fieldName,
                    'cf_' . $fieldName,
                    $fieldName
                ];

                foreach ($possibleKeys as $key) {
                    if (isset($items[$key]) && ($items[$key] === '0' || $items[$key] === 0 || !empty($items[$key]))) {
                        $fieldValue = trim((string)$items[$key]);
                        if ($this->validateCustomFieldValue($customField, $fieldValue)) {
                            $customFieldData[$customField->id] = $fieldValue;
                            break;
                        }
                    }
                }
            }

            if (!empty($customFieldData)) {
                CustomField::saveData($product, $customFieldData);
            }
        } catch (\Exception $e) {
            // Only log errors for first 100 failures to reduce overhead
            if ($this->failCount < 100) {
                \Log::error('Error processing product custom fields', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process custom fields for sub-product (using pre-loaded data)
     */
    private function processSubProductCustomFields($subProduct, $items)
    {
        try {
            // Get product to find category (use cache if available)
            $product = null;
            if (isset($this->productCacheById[$subProduct->product_id])) {
                $product = $this->productCacheById[$subProduct->product_id];
            } else {
                $product = ProductService::find($subProduct->product_id);
                if ($product) {
                    $this->productCacheById[$subProduct->product_id] = $product;
                    // Also cache by SKU if not already cached
                    if (!isset($this->productCache[$product->sku])) {
                        $this->productCache[$product->sku] = $product;
                    }
                }
            }
            
            if (!$product) {
                return;
            }

            // Get custom fields from pre-loaded cache
            $customFields = [];
            $cacheKey = 'sub-product_' . $product->category_id;
            foreach ($this->preloadedCustomFields as $key => $field) {
                if ($field->module === 'sub-product' && $field->categories->contains('id', $product->category_id)) {
                    $customFields[] = $field;
                }
            }

            // If no pre-loaded fields, fallback to query
            if (empty($customFields)) {
                $customFields = CustomField::where('module', 'sub-product')
                    ->forCategory($product->category_id)
                    ->where('created_by', $this->creatorId)
                    ->get();
            }

            foreach ($customFields as $customField) {
                $fieldName = strtolower($customField->name);
                
                // Check various possible column names
                $possibleKeys = [
                    'sub_product_' . $fieldName,
                    'sub_product_cf_' . $fieldName,
                    'cf_' . $fieldName,
                    $fieldName
                ];

                foreach ($possibleKeys as $key) {
                    if (isset($items[$key]) && ($items[$key] === '0' || $items[$key] === 0 || !empty($items[$key]))) {
                        $fieldValue = trim((string)$items[$key]);
                        if ($this->validateCustomFieldValue($customField, $fieldValue)) {
                            CustomFieldValue::updateOrCreate(
                                [
                                    'field_id' => $customField->id,
                                    'record_id' => $subProduct->id,
                                ],
                                [
                                    'value' => $fieldValue,
                                ]
                            );
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Only log errors for first 100 failures to reduce overhead
            if ($this->failCount < 100) {
                \Log::error('Error processing sub-product custom fields', [
                    'sub_product_id' => $subProduct->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Validate custom field value based on field type
     */
    private function validateCustomFieldValue($customField, $value)
    {
        switch ($customField->type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'number':
                return is_numeric($value);
            
            case 'date':
                $date = \DateTime::createFromFormat('Y-m-d', $value);
                return $date && $date->format('Y-m-d') === $value;
            
            case 'dropdown':
                $options = json_decode($customField->options, true);
                return in_array($value, $options ?? []);
            
            case 'text':
            case 'textarea':
            default:
                return true;
        }
    }

    /**
     * Process opening balance entries for sub-products with quantity
     * Similar to SubProductController logic
     */
    private function processOpeningBalance($validSubProducts, $insertedSubProductIds = [])
    {
        try {
            // Get unique product IDs
            $productIds = array_unique(array_column($validSubProducts, 'product_id'));
            if (empty($productIds)) {
                return;
            }

            // Load products with categories
            $products = ProductService::whereIn('id', $productIds)
                ->with('category')
                ->get()
                ->keyBy('id');

            // Group sub-products by purchase_account_id and calculate totals
            $accountTotals = [];
            $accountProducts = [];

            foreach ($validSubProducts as $sp) {
                // Get quantity for this warehouse (full qty for each warehouse)
                $quantity = floatval($sp['quantity'] ?? $sp['initial_stock'] ?? 0);
                
                // Get cost (purchase price) - use purchase_price as the cost
                $cost = floatval($sp['purchase_price'] ?? $sp['initial_rate'] ?? 0);

                // Only process if there's quantity and cost
                if ($quantity <= 0 || $cost <= 0) {
                    continue;
                }

                $productId = $sp['product_id'];
                if (!isset($products[$productId])) {
                    continue;
                }

                $product = $products[$productId];
                $category = $product->category;
                
                // Get warehouse info for logging
                $warehouseId = $sp['warehouse_id'] ?? null;
                $productNo = $sp['product_no'] ?? '';

                // Determine inventory account
                $purchaseAccountId = null;
                if ($category && $category->purchase_account_id) {
                    $purchaseAccountId = $category->purchase_account_id;
                }

                // If no purchase account from category, skip (or use default inventory account)
                if (!$purchaseAccountId) {
                    // Try to find default inventory account
                    $inventoryAccount = ChartOfAccount::where('created_by', $this->creatorId)
                        ->where('name', 'inventory')
                        ->first();
                    if ($inventoryAccount) {
                        $purchaseAccountId = $inventoryAccount->id;
                    } else {
                        continue; // Skip if no account found
                    }
                }

                // Calculate inventory amount: full qty for each warehouse * cost
                $inventoryAmount = $quantity * $cost;

                // Accumulate totals per account (sum all warehouses: qty * cost for each warehouse)
                if (!isset($accountTotals[$purchaseAccountId])) {
                    $accountTotals[$purchaseAccountId] = 0;
                    $accountProducts[$purchaseAccountId] = [];
                }
                $accountTotals[$purchaseAccountId] += $inventoryAmount;
                $accountProducts[$purchaseAccountId][] = $product;
                
                // Log calculation for first few items (for debugging)
                if (count($accountTotals) <= 3 && count($accountProducts[$purchaseAccountId]) <= 5) {
                    \Log::info('StockImport: Opening balance calculation', [
                        'product_id' => $productId,
                        'product_no' => $productNo,
                        'warehouse_id' => $warehouseId,
                        'quantity' => $quantity,
                        'cost' => $cost,
                        'inventory_amount' => $inventoryAmount,
                        'account_id' => $purchaseAccountId
                    ]);
                }
            }

            // Process each account
            foreach ($accountTotals as $accountId => $totalAmount) {
                if ($totalAmount <= 0) {
                    continue;
                }

                // Get inventory account
                $inventoryAccount = ChartOfAccount::where('created_by', $this->creatorId)
                    ->where('id', $accountId)
                    ->first();

                if (!$inventoryAccount) {
                    continue;
                }

                // Get voucher ID logic (same as SubProductController)
                $latestVoucher = GeneralLedger::where('created_by', $this->creatorId)
                    ->orderBy('vid', 'desc')
                    ->first();
                $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                $existingVid = GeneralLedger::where('reference', 'opening balance')
                    ->where('created_by', $this->creatorId)
                    ->first();

                // Check for existing inventory ledger entry for this account
                $existingInventory = GeneralLedger::where('account', $accountId)
                    ->where('reference', 'opening balance')
                    ->where('created_by', $this->creatorId)
                    ->first();

                $today = date('Y-m-d');

                if ($existingInventory) {
                    // Update existing entry
                    $existingInventory->update([
                        'debit' => $existingInventory->debit + $totalAmount,
                        'credit' => 0
                    ]);
                } else {
                    // Create new entry
                    GeneralLedger::create([
                        'vid' => $existingVid ? $existingVid->vid : $newVoucherId,
                        'account' => $accountId,
                        'type' => 'opening balance',
                        'debit' => $totalAmount,
                        'credit' => 0,
                        'ref_id' => $accountId,
                        'user_id' => 0,
                        'created_by' => $this->creatorId,
                        'send_date' => $today,
                        'reference' => 'opening balance',
                        'ref_number' => $inventoryAccount->name,
                    ]);
                }
            }

            // Create stock movement entries for each sub-product with opening balance
            $stockMovementsCreated = 0;
            foreach ($validSubProducts as $sp) {
                // Get quantity for this warehouse (full qty for each warehouse)
                $quantity = floatval($sp['quantity'] ?? $sp['initial_stock'] ?? 0);
                
                // Get cost (purchase price) - use purchase_price as the cost
                $cost = floatval($sp['purchase_price'] ?? $sp['initial_rate'] ?? 0);

                // Only process if there's quantity and cost
                if ($quantity <= 0 || $cost <= 0) {
                    continue;
                }

                $productId = $sp['product_id'];
                if (!isset($products[$productId])) {
                    continue;
                }

                // Get sub_product_id from insertedSubProductIds mapping using batch_index
                $subProductId = null;
                if (isset($sp['batch_index']) && isset($insertedSubProductIds[$sp['batch_index']])) {
                    $subProductId = $insertedSubProductIds[$sp['batch_index']];
                } else {
                    // Fallback: try to find sub-product by matching attributes
                    $subProduct = SubProduct::where('chassis_no', $sp['product_no'])
                        ->where('product_id', $productId)
                        ->where('created_by', $this->creatorId)
                        ->where('quantity', $quantity)
                        ->where('purchase_price', $cost)
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($subProduct) {
                        $subProductId = $subProduct->id;
                    }
                }

                // Only create stock movement if we have a valid sub_product_id
                if ($subProductId) {
                    try {
                        StockMovement::create([
                            'product_id' => $productId,
                            'sub_product_id' => $subProductId,
                            'bill_id' => null,
                            'invoice_id' => null,
                            'pos_id' => null,
                            'qty_in' => $quantity, // Opening balance is stock in
                            'qty_out' => 0,
                            'avg_cost' => $cost,
                            'cost_price' => $cost,
                            'activity' => 'Opening Balance',
                            'use_id' => null,
                            'item' => $subProductId,
                            'created_by' => $this->creatorId,
                        ]);
                        $stockMovementsCreated++;
                    } catch (\Exception $e) {
                        \Log::warning('Failed to create stock movement for opening balance', [
                            'sub_product_id' => $subProductId,
                            'product_id' => $productId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            \Log::info('Opening balance processed', [
                'accounts_processed' => count($accountTotals),
                'total_amount' => array_sum($accountTotals),
                'stock_movements_created' => $stockMovementsCreated,
                'account_breakdown' => array_map(function($amount, $accountId) {
                    return [
                        'account_id' => $accountId,
                        'total_amount' => $amount,
                        'calculation' => 'Sum of (quantity * cost) for all warehouses'
                    ];
                }, $accountTotals, array_keys($accountTotals))
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the import
            \Log::error('Error processing opening balance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 1000; // Optimized chunk size for large imports (20k+ rows)
    }

    public function startRow(): int
    {
        return 2; // Skip header row
    }

    /**
     * Get all errors collected during import
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get success count
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * Get failure count
     */
    public function getFailCount()
    {
        return $this->failCount;
    }

    /**
     * Get formatted error message with all errors
     */
    public function getErrorMessage()
    {
        if (empty($this->errors)) {
            return null;
        }

        $errorCount = $this->failCount;
        $displayedErrors = count($this->errors);
        
        $message = "Import completed with {$errorCount} error(s)";
        if ($displayedErrors < $errorCount) {
            $message .= " (showing first {$displayedErrors} errors due to memory optimization)";
        }
        $message .= ":\n\n";
        $message .= implode("\n", $this->errors);
        
        if ($this->successCount > 0) {
            $message .= "\n\nSuccessfully imported: {$this->successCount} row(s)";
        }
        
        return $message;
    }

    /**
     * Throw exception if there are errors
     * This should be called after import completes
     */
    public function throwIfHasErrors()
    {
        if ($this->hasErrors()) {
            throw new \Exception($this->getErrorMessage());
        }
    }
}

