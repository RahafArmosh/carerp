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
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class SparePartsStockImport implements
    OnEachRow,
    WithHeadingRow,
    WithChunkReading,
    WithEvents
{
    protected $creatorId;
    
    protected $brandCache = [];
    protected $subBrandCache = [];
    protected $productCache = [];
    protected $categoryCache = [];
    protected $customFieldCache = []; // Key: lowercase field name, Value: CustomField object
    protected $customFieldCacheById = []; // Key: field ID, Value: CustomField object
    protected $taxCache = []; // Key: tax rate (as string), Value: Tax object
    protected $errors = [];
    protected $rowNumber = 0;
    protected $successCount = 0;
    protected $failCount = 0;
    
    protected $batchSize = 250;
    protected $subProductBatch = [];
    protected $customFieldBatch = [];
    protected $batchCount = 0;
    protected $transactionActive = false;
    
    protected $initialized = false;
    protected $defaultUnitId = null;
    protected $defaultWarehouseId = null;

    public function __construct($creatorId)
    {
        $this->creatorId = $creatorId;
        $this->initialize();
    }
    
    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }
        
        DB::connection()->disableQueryLog();
        $this->preloadCommonData();
        $this->initialized = true;
        
        \Log::info('SparePartsStockImport initialized', [
            'creator_id' => $this->creatorId,
            'memory_limit' => ini_get('memory_limit')
        ]);
    }
    
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                if (!$this->initialized) {
                    $this->initialize();
                }
            },
            AfterImport::class => function(AfterImport $event) {
                $this->executeBatchInserts();
            },
            ImportFailed::class => function(ImportFailed $event) {
                \Log::error('Item master import failed', [
                    'exception' => $event->getException()->getMessage()
                ]);
            },
        ];
    }
    
    public function chunkSize(): int
    {
        return 1000;
    }
    
    private function preloadCommonData()
    {
        // Pre-load categories
        $categories = ProductServiceCategory::where('created_by', $this->creatorId)->get();
        foreach ($categories as $category) {
            $this->categoryCache[strtolower(trim($category->name))] = $category->id;
        }
        
        // Pre-load brands
        $brands = Brand::where('created_by', $this->creatorId)->get();
        foreach ($brands as $brand) {
            $cacheKey = strtolower(trim($brand->name));
            $this->brandCache[$cacheKey] = $brand;
        }
        
        // Pre-load sub-brands
        $subBrands = VehicleModel::where('created_by', $this->creatorId)->get();
        foreach ($subBrands as $subBrand) {
            $cacheKey = strtolower(trim($subBrand->name));
            $this->subBrandCache[$cacheKey] = $subBrand;
        }
        
        // Pre-load products by name (description) and by SKU for uniqueness check
        $products = ProductService::where('created_by', $this->creatorId)->get();
        foreach ($products as $product) {
            $cacheKey = strtolower(trim($product->name));
            $this->productCache[$cacheKey] = $product;
            // Also cache by SKU for uniqueness check
            $this->productCache['sku_' . strtolower(trim($product->sku))] = $product;
        }
        
        // Pre-load custom fields for both product and sub-product modules
        // Spare parts can have custom fields on both product and sub-product
        $productCustomFields = CustomField::where('created_by', $this->creatorId)
            ->where('module', 'product')
            ->get();
        foreach ($productCustomFields as $field) {
            // Normalize field name same way as Excel columns (remove spaces, underscores, dashes)
            $cacheKey = strtolower(trim(str_replace([' ', '_', '-'], '', $field->name)));
            // Store product fields with prefix
            $this->customFieldCache['prod_' . $cacheKey] = $field;
            // Also store without prefix for backward compatibility
            if (!isset($this->customFieldCache[$cacheKey])) {
                $this->customFieldCache[$cacheKey] = $field;
            }
            $this->customFieldCacheById[$field->id] = $field;
        }
        
        $subProductCustomFields = CustomField::where('created_by', $this->creatorId)
            ->where('module', 'sub-product')
            ->get();
        foreach ($subProductCustomFields as $field) {
            // Normalize field name same way as Excel columns (remove spaces, underscores, dashes)
            $cacheKey = strtolower(trim(str_replace([' ', '_', '-'], '', $field->name)));
            // Store sub-product fields separately to avoid conflicts with product fields
            // Use a prefix to distinguish sub-product fields
            $this->customFieldCache['sub_' . $cacheKey] = $field;
            // Always store without prefix for sub-product fields (they take priority over product fields with same name)
            // This ensures sub-product fields are matched when Excel column name matches
            $this->customFieldCache[$cacheKey] = $field;
            $this->customFieldCacheById[$field->id] = $field;
        }
        
        \Log::info('Custom fields loaded for import', [
            'creator_id' => $this->creatorId,
            'product_fields_count' => $productCustomFields->count(),
            'sub_product_fields_count' => $subProductCustomFields->count(),
            'product_field_names' => $productCustomFields->pluck('name')->toArray(),
            'sub_product_field_names' => $subProductCustomFields->pluck('name')->toArray(),
        ]);
        
        // Get first unit created by the company
        $defaultUnit = ProductServiceUnit::where('created_by', $this->creatorId)
            ->orderBy('id', 'asc')
            ->first();
        if ($defaultUnit) {
            $this->defaultUnitId = $defaultUnit->id;
        } else {
            // Create a default unit if none exists
            $defaultUnit = ProductServiceUnit::create([
                'name' => 'Piece',
                'created_by' => $this->creatorId
            ]);
            $this->defaultUnitId = $defaultUnit->id;
        }
        
        // Get first warehouse created by the company
        $defaultWarehouse = \App\Models\warehouse::where('created_by', $this->creatorId)
            ->orderBy('id', 'asc')
            ->first();
        if ($defaultWarehouse) {
            $this->defaultWarehouseId = $defaultWarehouse->id;
        }
        
        // Pre-load taxes by rate for VAT lookup
        $taxes = Tax::where('created_by', $this->creatorId)->get();
        foreach ($taxes as $tax) {
            // Cache by rate (as string to handle decimal matching)
            $rateKey = (string)$tax->rate;
            $this->taxCache[$rateKey] = $tax;
            // Also cache by rounded rate for flexibility
            $roundedRateKey = (string)round($tax->rate, 2);
            if (!isset($this->taxCache[$roundedRateKey])) {
                $this->taxCache[$roundedRateKey] = $tax;
            }
        }
    }
    
    public function onRow(Row $row)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        
        $this->rowNumber = $row->getIndex();
        
        if (!$this->transactionActive) {
            DB::beginTransaction();
            $this->transactionActive = true;
        }
        
        try {
            $items = $row->toArray();
            
            // Check for Excel formulas (VLOOKUP, HLOOKUP, etc.) and reject them
            foreach ($items as $key => $value) {
                if (is_string($value) && (
                    stripos($value, '=VLOOKUP') !== false ||
                    stripos($value, '=HLOOKUP') !== false ||
                    stripos($value, '=INDEX') !== false ||
                    stripos($value, '=MATCH') !== false ||
                    stripos($value, '=IF(') !== false ||
                    (stripos($value, '=') === 0 && preg_match('/^=[A-Z]+\(/', $value))
                )) {
                    throw new \Exception("Row {$this->rowNumber}: Excel formulas are not allowed. Column '{$key}' contains a formula. Please convert formulas to values before importing.");
                }
            }
            
            // Normalize keys to lowercase for case-insensitive matching
            $itemsLower = [];
            foreach ($items as $k => $v) {
                // Additional check: Skip if value is a formula (starts with = and contains function call)
                if (is_string($v) && stripos($v, '=') === 0 && preg_match('/^=[A-Z]+\(/', $v)) {
                    throw new \Exception("Row {$this->rowNumber}: Excel formulas are not allowed in column '{$k}'. Please convert formulas to values before importing.");
                }
                $normalizedKey = strtolower(trim(str_replace([' ', '_', '-'], '', $k)));
                $itemsLower[$normalizedKey] = $v;
            }
            
            // Map headers: part no, description, STOCK QTY, GROUPE, PARTS TYPE, BRAND
            $partNoRaw = $this->getRawCellValue($itemsLower, ['partno', 'part_no', 'partnumber', 'part_number']);
            $partNo = $this->normalizeItemMasterPartNo($partNoRaw);
            $description = $this->getValue($itemsLower, ['description', 'desc', 'productname', 'product_name']);
            $stockQty = $this->getValue($itemsLower, ['stockqty', 'stock_qty', 'quantity', 'qty']);
            $groupe = $this->getValue($itemsLower, ['groupe', 'group', 'category', 'categoryname']);
            $partsType = $this->getValue($itemsLower, ['partstype', 'parts_type', 'parttype', 'part_type', 'subbrand', 'sub_brand']);
            $brand = $this->getValue($itemsLower, ['brand', 'brandname', 'brand_name']);
            
            // Validate required fields (part no normalized; avoid empty() so "0" is valid)
            if ($partNo === '') {
                throw new \Exception("Row {$this->rowNumber}: Part No is required.");
            }
            
            if (empty($description)) {
                throw new \Exception("Row {$this->rowNumber}: Description is required.");
            }
            
            if (empty($groupe)) {
                throw new \Exception("Row {$this->rowNumber}: GROUPE (Category) is required.");
            }
            
            // Get or create category
            $categoryId = $this->getOrCreateCategory($groupe);
            
            // Get or create brand
            $brandId = null;
            if (!empty($brand)) {
                $brandModel = $this->getOrCreateBrand($brand, $categoryId);
                $brandId = $brandModel->id;
            }
            
            // Get or create sub brand
            $subBrandId = null;
            if (!empty($partsType)) {
                $subBrandModel = $this->getOrCreateSubBrand($partsType, $brandId);
                $subBrandId = $subBrandModel->id;
            }
            
            // Get or create product (by description/name)
            // Use partNo as SKU for the product
            $product = $this->getOrCreateProduct($description, $brandId, $subBrandId, $categoryId, $partNo);
            
            // Process custom fields (all other columns that match custom field names)
            $customFieldData = [];
            $matchedFields = [];
            $unmatchedColumns = [];
            
            foreach ($items as $key => $value) {
                // Skip standard columns
                $normalizedKey = strtolower(trim(str_replace([' ', '_', '-'], '', $key)));
                if (in_array($normalizedKey, ['partno', 'part_no', 'partnumber', 'part_number', 
                    'description', 'desc', 'productname', 'product_name',
                    'stockqty', 'stock_qty', 'quantity', 'qty',
                    'groupe', 'group', 'category', 'categoryname',
                    'partstype', 'parts_type', 'parttype', 'part_type', 'subbrand', 'sub_brand',
                    'brand', 'brandname', 'brand_name'])) {
                    continue;
                }
                
                // Normalize the Excel column name the same way as cache keys
                // Remove spaces, underscores, dashes and convert to lowercase
                $fieldCacheKey = strtolower(trim(str_replace([' ', '_', '-'], '', $key)));
                
                // Try to find matching custom field
                // Priority: sub-product fields first (since we're importing sub-products)
                $customField = null;
                if (isset($this->customFieldCache['sub_' . $fieldCacheKey])) {
                    $customField = $this->customFieldCache['sub_' . $fieldCacheKey];
                } elseif (isset($this->customFieldCache[$fieldCacheKey])) {
                    // Check if non-prefixed cache entry is a sub-product field
                    $field = $this->customFieldCache[$fieldCacheKey];
                    if ($field->module === 'sub-product') {
                        $customField = $field;
                    }
                }
                
                // If no sub-product field found, try product field
                if (!$customField) {
                    if (isset($this->customFieldCache['prod_' . $fieldCacheKey])) {
                        $customField = $this->customFieldCache['prod_' . $fieldCacheKey];
                    } elseif (isset($this->customFieldCache[$fieldCacheKey])) {
                        $field = $this->customFieldCache[$fieldCacheKey];
                        if ($field->module === 'product') {
                            $customField = $field;
                        }
                    }
                }
                
                if ($customField) {
                    // Allow empty values - convert to empty string if null
                    $customFieldData[$customField->id] = $value !== null ? trim((string)$value) : '';
                    $matchedFields[] = [
                        'column' => $key,
                        'field_name' => $customField->name,
                        'field_id' => $customField->id,
                        'module' => $customField->module,
                        'value' => $value !== null ? trim((string)$value) : ''
                    ];
                } else {
                    // Track unmatched columns for debugging
                    $unmatchedColumns[] = $key;
                }
            }
            
            // Log custom field matching for debugging
            if (!empty($matchedFields) || !empty($unmatchedColumns)) {
                \Log::debug('Custom field matching for row', [
                    'row_number' => $this->rowNumber,
                    'matched_fields' => $matchedFields,
                    'unmatched_columns' => $unmatchedColumns,
                    'total_custom_fields_found' => count($customFieldData)
                ]);
            }
            
            // Separate custom fields for product and sub-product modules
            $productCustomFieldData = [];
            $subProductCustomFieldData = [];
            
            foreach ($customFieldData as $fieldId => $value) {
                // Get field by ID from cache
                if (isset($this->customFieldCacheById[$fieldId])) {
                    $field = $this->customFieldCacheById[$fieldId];
                    if ($field->module === 'product') {
                        $productCustomFieldData[$fieldId] = $value;
                    } elseif ($field->module === 'sub-product') {
                        $subProductCustomFieldData[$fieldId] = $value;
                    }
                }
            }
            
            // Save custom fields for product (if any)
            if (!empty($productCustomFieldData)) {
                CustomField::saveData($product, $productCustomFieldData);
            }
            
            // Create or update sub product
            // One-to-one relationship: one part_no = one product = one sub-product
            // Item Master import should not carry stock quantity into sub-products.
            // Force quantity/status fields per business rule.
            $quantity = 0;
            
            // Resolve Item Master sub-product: same part number must upsert, not insert again.
            // Excel can change representation (string vs float, spaces, case); normalize lookup.
            // Merges duplicate rows from earlier runs (soft-deletes safe extras).
            $existingSubProduct = $this->resolveItemMasterSubProductForUpsert($partNo);

            if ($existingSubProduct) {
                // Update existing sub product
                // Ensure it's linked to the correct product (in case product was recreated)
                $existingSubProduct->product_no = $partNo;
                $existingSubProduct->product_id = $product->id;
                $existingSubProduct->quantity = $quantity;
                $existingSubProduct->initial_stock = 0;
                $existingSubProduct->warehouse_id = $this->defaultWarehouseId ?? 1;
                $existingSubProduct->flag = 2;
                $existingSubProduct->booked = 3;
                $existingSubProduct->import_source = 'item_master';
                $existingSubProduct->save();
                
                // Save custom fields for sub-product
                if (!empty($subProductCustomFieldData)) {
                    CustomField::saveData($existingSubProduct, $subProductCustomFieldData);
                }
            } else {
                // Create new sub product
                // Each part_no gets its own sub-product linked to its own product
                $subProduct = new SubProduct();
                $subProduct->chassis_no = $partNo;
                $subProduct->product_id = $product->id; // Each part_no has its own unique product
                $subProduct->quantity = $quantity;
                $subProduct->initial_stock = 0;
                $subProduct->warehouse_id = $this->defaultWarehouseId ?? 1;
                $subProduct->sale_price = $product->sale_price ?? 0;
                $subProduct->purchase_price = $product->purchase_price ?? 0;
                $subProduct->created_by = $this->creatorId;
                $subProduct->flag = 2;
                $subProduct->booked = 3;
                $subProduct->import_source = 'item_master';
                $subProduct->save();
                
                // Save custom fields for sub-product
                if (!empty($subProductCustomFieldData)) {
                    CustomField::saveData($subProduct, $subProductCustomFieldData);
                }
            }
            
            $this->successCount++;
            
        } catch (\Exception $e) {
            $this->failCount++;
            $errorMsg = "Row {$this->rowNumber}: " . $e->getMessage();
            
            if (count($this->errors) < 1000) {
                $this->errors[] = $errorMsg;
            }
            
            \Log::error('Item Master Import Error', [
                'message' => $e->getMessage(),
                'row_number' => $this->rowNumber,
                'creator_id' => $this->creatorId
            ]);
        }
    }
    
    /**
     * Raw cell value (allows "0" as part number; does not treat 0 as empty).
     *
     * @param  array<string, mixed>  $itemsLower
     */
    private function getRawCellValue($itemsLower, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            $normalizedKey = strtolower(trim(str_replace([' ', '_', '-'], '', $key)));
            if (! array_key_exists($normalizedKey, $itemsLower)) {
                continue;
            }
            $v = $itemsLower[$normalizedKey];
            if ($v === null || $v === '') {
                continue;
            }

            return $v;
        }

        return null;
    }

    /**
     * Stable part number string for DB + re-import (Excel often returns floats).
     */
    private function normalizeItemMasterPartNo($raw): string
    {
        if ($raw === null) {
            return '';
        }
        if (is_numeric($raw) && ! is_string($raw)) {
            $f = (float) $raw;
            if (is_finite($f) && floor($f) == $f && abs($f) < 1e15) {
                return (string) (int) $f;
            }
            $s = rtrim(rtrim(sprintf('%.12F', $f), '0'), '.');

            return $s === '-0' ? '0' : $s;
        }

        return preg_replace('/\s+/u', ' ', trim((string) $raw));
    }

    /**
     * Find the canonical Item Master sub-product row for this part number, or null.
     * If multiple rows exist (duplicate imports), keeps the oldest id and soft-deletes safe duplicates.
     */
    private function resolveItemMasterSubProductForUpsert(string $normalizedPartNo): ?SubProduct
    {
        $wId = $this->defaultWarehouseId ?? 1;
        $rows = SubProduct::query()
            ->where('created_by', $this->creatorId)
            ->whereRaw('LOWER(TRIM(COALESCE(product_no, ""))) = ?', [strtolower($normalizedPartNo)])
            ->where(function ($query) {
                $query->where('import_source', 'item_master')
                    ->orWhereNull('import_source');
            })
            ->where(function ($query) use ($wId) {
                $query->where('warehouse_id', $wId)->orWhereNull('warehouse_id');
            })
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $keep = $rows->first();
        foreach ($rows->skip(1) as $dup) {
            if ($this->safeToRemoveDuplicateItemMasterRow($dup)) {
                $dup->delete();
            }
        }

        return $keep;
    }

    /**
     * Only remove duplicate rows that are clearly Item Master inventory lines (no sales/purchase links).
     */
    private function safeToRemoveDuplicateItemMasterRow(SubProduct $sp): bool
    {
        $src = strtolower((string) ($sp->import_source ?? ''));
        if ($src !== '' && $src !== 'item_master') {
            return false;
        }
        if (! empty($sp->bill_id)) {
            return false;
        }
        if (! empty($sp->invoice_id)) {
            return false;
        }
        if (! empty($sp->pos_id)) {
            return false;
        }
        if (! empty($sp->asn_id)) {
            return false;
        }
        if (! empty($sp->sale_order_id)) {
            return false;
        }

        return true;
    }

    private function getValue($itemsLower, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            $normalizedKey = strtolower(trim(str_replace([' ', '_', '-'], '', $key)));
            if (isset($itemsLower[$normalizedKey]) && !empty($itemsLower[$normalizedKey])) {
                return trim((string)$itemsLower[$normalizedKey]);
            }
        }
        return null;
    }
    
    private function getOrCreateCategory($categoryName)
    {
        $cacheKey = strtolower(trim($categoryName));
        
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }
        
        // Create new category
        $category = ProductServiceCategory::create([
            'name' => trim($categoryName),
            'created_by' => $this->creatorId
        ]);
        
        $this->categoryCache[$cacheKey] = $category->id;
        
        return $category->id;
    }
    
    private function getOrCreateBrand($brandName, $categoryId)
    {
        $cacheKey = strtolower(trim($brandName));
        
        if (isset($this->brandCache[$cacheKey])) {
            $brand = $this->brandCache[$cacheKey];
            
            // Link brand to category if not already linked
            $this->linkBrandToCategory($brand->id, $categoryId);
            
            return $brand;
        }
        
        // Create new brand
        $brand = Brand::create([
            'name' => trim($brandName),
            'created_by' => $this->creatorId
        ]);
        
        $this->brandCache[$cacheKey] = $brand;
        
        // Link brand to category
        $this->linkBrandToCategory($brand->id, $categoryId);
        
        return $brand;
    }
    
    private function linkBrandToCategory($brandId, $categoryId)
    {
        $exists = DB::table('brand_category')
            ->where('brand_id', $brandId)
            ->where('product_service_category_id', $categoryId)
            ->exists();
        
        if (!$exists) {
            DB::table('brand_category')->insert([
                'brand_id' => $brandId,
                'product_service_category_id' => $categoryId
            ]);
        }
    }
    
    private function getOrCreateSubBrand($subBrandName, $brandId)
    {
        $cacheKey = strtolower(trim($subBrandName));
        
        if (isset($this->subBrandCache[$cacheKey])) {
            return $this->subBrandCache[$cacheKey];
        }
        
        // Create new sub brand
        $subBrand = VehicleModel::create([
            'name' => trim($subBrandName),
            'brand_id' => $brandId ?? 0,
            'created_by' => $this->creatorId
        ]);
        
        $this->subBrandCache[$cacheKey] = $subBrand;
        
        return $subBrand;
    }
    
    private function getOrCreateProduct($productName, $brandId, $subBrandId, $categoryId, $partNo = null)
    {
        $cacheKey = strtolower(trim($productName));
        
        // CRITICAL: If partNo is provided, ONLY check by partNo (SKU)
        // This ensures one-to-one relationship: one partNo = one product
        // Even if descriptions are the same, different partNos create different products
        if (!empty($partNo)) {
            $partNo = trim($partNo);
            $skuCacheKey = 'sku_' . strtolower($partNo);
            
            // Check cache first
            if (isset($this->productCache[$skuCacheKey])) {
                $product = $this->productCache[$skuCacheKey];
                // Update product name if different (keeps product name updated)
                if (strtolower(trim($product->name)) !== $cacheKey) {
                    $product->name = trim($productName);
                    $product->save();
                    // Update name cache
                    $this->productCache[$cacheKey] = $product;
                }
                return $product;
            }
            
            // Check database for existing product with this SKU (partNo), case-insensitive + trim
            $existingProductWithSku = ProductService::where('created_by', $this->creatorId)
                ->whereRaw('LOWER(TRIM(COALESCE(sku, ""))) = ?', [strtolower($partNo)])
                ->first();
            
            if ($existingProductWithSku) {
                // Found existing product with this partNo - use it (one-to-one relationship)
                $this->productCache[$cacheKey] = $existingProductWithSku;
                $this->productCache[$skuCacheKey] = $existingProductWithSku;
                
                // Update product name if different (keeps product name updated)
                if (strtolower(trim($existingProductWithSku->name)) !== $cacheKey) {
                    $existingProductWithSku->name = trim($productName);
                    $existingProductWithSku->save();
                }
                // Keep SKU in canonical form from this import
                if (trim((string) $existingProductWithSku->sku) !== $partNo) {
                    $existingProductWithSku->sku = $partNo;
                    $existingProductWithSku->save();
                }
                
                return $existingProductWithSku;
            }
            
            // PartNo doesn't exist - CREATE NEW PRODUCT (even if description matches another product)
            // This ensures each partNo gets its own unique product
            $product = ProductService::create([
                'name' => trim($productName),
                'sku' => $partNo, // Always use partNo as SKU
                'category_id' => $categoryId,
                'brand_id' => $brandId ?? 0,
                'sub_brand_id' => $subBrandId ?? 0,
                'unit_id' => $this->defaultUnitId,
                'type' => 'product',
                'sale_price' => 0,
                'purchase_price' => 0,
                'tax_id' => null,
                'created_by' => $this->creatorId
            ]);
            
            // Cache by both name and SKU
            $this->productCache[$cacheKey] = $product;
            $this->productCache[$skuCacheKey] = $product;
            
            return $product;
        }
        
        // Fallback: If partNo is not provided, check by product name (for backward compatibility)
        if (isset($this->productCache[$cacheKey])) {
            return $this->productCache[$cacheKey];
        }
        
        // Generate unique SKU from product name if partNo not provided
        $baseSku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 15));
        $sku = $baseSku;
        $counter = 1;
        
        // Ensure SKU is unique
        while (isset($this->productCache['sku_' . strtolower($sku)]) || 
               ProductService::where('sku', $sku)->where('created_by', $this->creatorId)->exists()) {
            $sku = $baseSku . '_' . $counter;
            $counter++;
        }
        
        // Create new product
        $product = ProductService::create([
            'name' => trim($productName),
            'sku' => $sku,
            'category_id' => $categoryId,
            'brand_id' => $brandId ?? 0,
            'sub_brand_id' => $subBrandId ?? 0,
            'unit_id' => $this->defaultUnitId,
            'type' => 'product',
            'sale_price' => 0,
            'purchase_price' => 0,
            'tax_id' => null,
            'created_by' => $this->creatorId
        ]);
        
        $this->productCache[$cacheKey] = $product;
        $this->productCache['sku_' . strtolower($sku)] = $product;
        
        return $product;
    }
    
    private function getNumericValue($itemsLower, $possibleKeys)
    {
        $value = $this->getValue($itemsLower, $possibleKeys);
        if ($value === null || $value === '') {
            return null;
        }
        // Remove any non-numeric characters except decimal point and minus sign
        $numericValue = preg_replace('/[^0-9.-]/', '', (string)$value);
        if ($numericValue === '' || $numericValue === '-') {
            return null;
        }
        return (float)$numericValue;
    }
    
    private function findTaxByRate($vatRate)
    {
        // Try exact match first
        $rateKey = (string)$vatRate;
        if (isset($this->taxCache[$rateKey])) {
            return $this->taxCache[$rateKey]->id;
        }
        
        // Try rounded match
        $roundedRateKey = (string)round($vatRate, 2);
        if (isset($this->taxCache[$roundedRateKey])) {
            return $this->taxCache[$roundedRateKey]->id;
        }
        
        // Try to find closest match (within 0.01 tolerance)
        foreach ($this->taxCache as $tax) {
            if (abs($tax->rate - $vatRate) < 0.01) {
                return $tax->id;
            }
        }
        
        // No match found
        return null;
    }
    
    private function executeBatchInserts()
    {
        // Commit any pending transaction
        if ($this->transactionActive) {
            try {
                DB::commit();
                $this->transactionActive = false;
            } catch (\Exception $e) {
                \Log::error('Error committing transaction', ['error' => $e->getMessage()]);
                DB::rollBack();
                $this->transactionActive = false;
            }
        }
    }
    
    public function getSuccessCount()
    {
        return $this->successCount;
    }
    
    public function getFailCount()
    {
        return $this->failCount;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    public function getErrorMessage()
    {
        return implode("\n", array_slice($this->errors, 0, 50));
    }
    
}


