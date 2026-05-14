<?php

namespace App\Imports;

use App\Models\Pro;
use App\Models\ProItem;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\SubProduct;
use App\Models\warehouse;
use App\Models\CustomField;
use App\Models\Brand;
use App\Models\MasterlistLeadger;
use App\Models\VehicleModel;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * PRO import that creates SubProduct (stock) when part_no does not exist yet.
 * Use this when you want to import PRO lines for items not yet in stock.
 */
class ProCreateSubProductImport implements ToArray
{
    protected $userId;
    protected $productCacheBySku = [];
    protected $subProductCacheByPartNo = [];
    protected $subProductCustomFieldCacheByCategory = [];

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    private function parseDate($dateValue)
    {
        if (empty($dateValue)) return null;
        if (is_numeric($dateValue)) {
            try {
                return Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse Excel date', ['value' => $dateValue, 'error' => $e->getMessage()]);
            }
        }
        if (is_string($dateValue)) {
            $formats = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'm-d-Y', 'm/d/Y', 'Y-m-d H:i:s', 'Y/m/d H:i:s'];
            foreach ($formats as $format) {
                try {
                    $date = \DateTime::createFromFormat($format, $dateValue);
                    if ($date && $date->format($format) === $dateValue) return $date->format('Y-m-d');
                } catch (\Exception $e) { continue; }
            }
            try {
                return \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date string', ['value' => $dateValue, 'error' => $e->getMessage()]);
            }
        }
        return null;
    }

    private function findOrCreateSupplier($supplierName)
    {
        if (empty($supplierName)) return null;
        $trimmedName = trim($supplierName);
        $supplier = Vender::where('created_by', $this->userId)->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmedName)])->first();
        if (!$supplier) $supplier = Vender::where('created_by', $this->userId)->where('name', $trimmedName)->first();
        if (!$supplier) $supplier = Vender::where('created_by', $this->userId)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($trimmedName) . '%'])->first();
        if (!$supplier) throw new \Exception("Vendor '{$trimmedName}' does not exist in the system. Please create the vendor first before importing PRO.");
        return $supplier;
    }

    /** Find product by SKU. Each SKU = one product. */
    private function findProductBySku($sku)
    {
        if (empty(trim($sku ?? ''))) return null;
        return ProductService::where('created_by', $this->userId)
            ->where('sku', trim($sku))
            ->first();
    }

    private function normalizeLookupKey($value): string
    {
        return strtolower(trim((string) $value));
    }

    private function getCachedProductBySku($sku): ?ProductService
    {
        $key = $this->normalizeLookupKey($sku);
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->productCacheBySku)) {
            return $this->productCacheBySku[$key];
        }

        $product = $this->findProductBySku($sku);
        $this->productCacheBySku[$key] = $product ?: null;
        return $product;
    }

    private function getCachedSubProductByPartNo($partNo): ?SubProduct
    {
        $key = $this->normalizeLookupKey($partNo);
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->subProductCacheByPartNo)) {
            return $this->subProductCacheByPartNo[$key];
        }

        $subProduct = SubProduct::where('created_by', $this->userId)
            ->where('chassis_no', trim((string) $partNo))
            ->with('productService')
            ->latest()
            ->first();

        $this->subProductCacheByPartNo[$key] = $subProduct ?: null;
        return $subProduct;
    }

    private function preloadLookupData(array $itemRows, array $columnMap, array $normalizedHeaderByIndex): void
    {
        $partNos = [];
        $skus = [];

        foreach ($itemRows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $partNo = trim($this->getValue($row, $columnMap, 'part_no'));
            if ($partNo !== '') {
                $partNos[] = $partNo;
            }

            $rowAssoc = $this->buildRowAssoc($row, $normalizedHeaderByIndex);
            $sku = trim($this->getValue($row, $columnMap, 'sku'));
            if ($sku === '') {
                $sku = trim((string) ($rowAssoc['sku'] ?? ''));
            }
            if ($sku === '' && $partNo !== '') {
                $sku = $partNo;
            }
            if ($sku !== '') {
                $skus[] = $sku;
            }
        }

        $partNos = array_values(array_unique($partNos));
        $skus = array_values(array_unique($skus));

        if (!empty($skus)) {
            foreach (array_chunk($skus, 500) as $chunk) {
                $products = ProductService::where('created_by', $this->userId)
                    ->whereIn('sku', $chunk)
                    ->get();

                foreach ($products as $product) {
                    $key = $this->normalizeLookupKey($product->sku);
                    if ($key !== '' && !array_key_exists($key, $this->productCacheBySku)) {
                        $this->productCacheBySku[$key] = $product;
                    }
                }
            }
        }

        if (!empty($partNos)) {
            foreach (array_chunk($partNos, 500) as $chunk) {
                $subProducts = SubProduct::where('created_by', $this->userId)
                    ->whereIn('chassis_no', $chunk)
                    ->with('productService')
                    ->orderByDesc('id')
                    ->get();

                foreach ($subProducts as $subProduct) {
                    $key = $this->normalizeLookupKey($subProduct->chassis_no);
                    if ($key !== '' && !array_key_exists($key, $this->subProductCacheByPartNo)) {
                        $this->subProductCacheByPartNo[$key] = $subProduct;
                    }
                }
            }
        }
    }

    private function findProduct($description, $partNo = null)
    {
        if (empty($description) && empty($partNo)) return null;
        $product = null;
        if (!empty($description)) {
            $product = ProductService::where('created_by', $this->userId)->where('name', $description)->first();
            if (!$product) $product = ProductService::where('created_by', $this->userId)->where('name', 'like', '%' . $description . '%')->first();
        }
        if (!$product && !empty($partNo)) {
            $product = ProductService::where('created_by', $this->userId)->where('sku', $partNo)->first();
            if (!$product) $product = ProductService::where('created_by', $this->userId)->where('sku', 'like', '%' . $partNo . '%')->first();
        }
        return $product;
    }

    private function extractProHeader($data, $headerRowIndex)
    {
        $header = [];
        for ($i = 0; $i < $headerRowIndex; $i++) {
            $row = $data[$i];
            for ($col = 0; $col < count($row); $col++) {
                $cell = strtoupper(trim($row[$col] ?? ''));
                $nextCell = isset($row[$col + 1]) ? trim($row[$col + 1]) : '';
                if ($cell == 'SUPPLIER NAME' || $cell == 'SUPPLIER_NAME' || $cell == 'SUPPLIERNAME') $header['supplier_name'] = $nextCell;
                elseif ($cell == 'SUPPLIER PROFORMA NO' || $cell == 'SUPPLIER_PROFORMA_NO' || $cell == 'SUPPLIERPROFORMANO') $header['supplier_proforma_no'] = $nextCell;
                elseif ($cell == 'SUPPLIER PROFORMA DATE' || $cell == 'SUPPLIER_PROFORMA_DATE' || $cell == 'SUPPLIERPROFORMADATE') $header['supplier_proforma_date'] = $nextCell;
                elseif ($cell == 'OUR ORDER REF' || $cell == 'OUR_ORDER_REF' || $cell == 'OUROrderREF') $header['our_order_ref'] = $nextCell;
                elseif ($cell == 'SUPPLIER REF' || $cell == 'SUPPLIER_REF' || $cell == 'SUPPLIERREF') $header['supplier_ref'] = $nextCell;
                elseif ($cell == 'ETA DATE' || $cell == 'ETA_DATE' || $cell == 'ETADATE' || $cell == 'ETA') $header['eta_date'] = $nextCell;
                elseif ($cell == 'CURRENCY ID' || $cell == 'CURRENCY_ID' || $cell == 'CURRENCYID') $header['currency_id'] = $nextCell;
                elseif ($cell == 'EXCHANGE RATE' || $cell == 'EXCHANGE_RATE' || $cell == 'EXCHANGERATE') $header['exchange_rate'] = $nextCell;
                elseif ($cell == 'PO DATE' || $cell == 'PO_DATE' || $cell == 'PODATE') $header['po_date'] = $nextCell;
            }
        }
        return $header;
    }

    private function mapColumns($headerRow)
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $header = strtoupper(trim($header));
            if (in_array($header, ['PART NO', 'PART_NO', 'PARTNO'])) $map['part_no'] = $index;
            elseif (in_array($header, ['DESCRIPTION', 'DESC'])) $map['description'] = $index;
            elseif (in_array($header, ['SKU', 'PRODUCT_SKU', 'PRODUCTSKU'])) $map['sku'] = $index;
            elseif (in_array($header, ['ORDER QTY', 'ORDER_QTY', 'ORDERQTY', 'ORDER QUANTITY'])) $map['order_qty'] = $index;
            elseif (in_array($header, ['SUPPLIED QTY', 'SUPPLIED_QTY', 'SUPPLIEDQTY', 'SUPPLIED QUANTITY'])) $map['supplied_qty'] = $index;
            elseif (in_array($header, ['SALE PRICE', 'SALE_PRICE', 'SALEPRICE', 'SELLING PRICE', 'SELLING_PRICE', 'SELLINGPRICE'])) $map['sale_price'] = $index;
            elseif (in_array($header, ['UNIT PRICE', 'UNIT_PRICE', 'UNITPRICE', 'PRICE'])) $map['unit_price'] = $index;
            elseif (in_array($header, ['CATEGORY ID', 'CATEGORY_ID', 'CATEGORYID'])) $map['category_id'] = $index;
            elseif (in_array($header, ['BRAND NAME', 'BRAND_NAME', 'BRANDNAME', 'BRAND'])) $map['brand_name'] = $index;
            elseif (in_array($header, ['SUB BRAND NAME', 'SUB_BRAND_NAME', 'SUB_BRAND', 'SUBBRAND'])) $map['sub_brand_name'] = $index;
        }
        return $map;
    }

    /**
     * Find an existing Brand for this company by name (case-insensitive),
     * or create it if it does not exist yet.
     */
    private function findBrandByName($name, $categoryId = null)
    {
        if (empty(trim($name ?? ''))) return null;
        $trimmed = trim($name);

        // Try exact (case-insensitive) match first
        $brand = Brand::where('created_by', $this->userId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmed)])
            ->first();

        if ($brand) {
            // If category from this line is provided, ensure the brand is linked to it
            if (!empty($categoryId)) {
                $changed = false;
                if (empty($brand->category_id)) {
                    $brand->category_id = $categoryId;
                    $changed = true;
                }

                if ($changed) {
                    $brand->save();
                }

                // Also link through pivot table if not already linked
                $category = ProductServiceCategory::find($categoryId);
                if ($category && !$brand->categories()->where('product_service_category_id', $categoryId)->exists()) {
                    $brand->categories()->attach($categoryId);
                }
            }

            return $brand;
        }

        // Create brand for this company if not found
        $brand = new Brand();
        $brand->name = $trimmed;
        $brand->category_id = $categoryId;
        $brand->created_by = $this->userId;
        $brand->save();

        // Also link through pivot table if category exists
        if (!empty($categoryId)) {
            $category = ProductServiceCategory::find($categoryId);
            if ($category) {
                $brand->categories()->attach($categoryId);
            }
        }

        \Log::info('PRO Import (create stock): Created new Brand from import', [
            'user_id' => $this->userId,
            'brand_id' => $brand->id,
            'brand_name' => $brand->name,
            'category_id' => $categoryId,
        ]);

        return $brand;
    }

    /**
     * Find an existing SubBrand for this company by name (and optional brand),
     * or create it if it does not exist yet and a brand is provided.
     */
    private function findSubBrandByName($name, $brandId = null)
    {
        if (empty(trim($name ?? ''))) return null;
        $trimmed = trim($name);

        $query = VehicleModel::where('created_by', $this->userId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmed)]);

        if ($brandId !== null) {
            $query->where('brand_id', $brandId);
        }

        $subBrand = $query->first();

        if (!$subBrand && $brandId !== null) {
            // Create sub-brand linked to this brand
            $subBrand = new VehicleModel();
            $subBrand->name = $trimmed;
            $subBrand->brand_id = $brandId;
            $subBrand->created_by = $this->userId;
            $subBrand->save();

            \Log::info('PRO Import (create stock): Created new SubBrand from import', [
                'user_id' => $this->userId,
                'sub_brand_id' => $subBrand->id,
                'sub_brand_name' => $subBrand->name,
                'brand_id' => $brandId,
            ]);
        }

        return $subBrand;
    }

    private function getValue($row, $columnMap, $field)
    {
        if (isset($columnMap[$field]) && isset($row[$columnMap[$field]])) return trim((string)$row[$columnMap[$field]]);
        return '';
    }

    private function getNextUniqueProNumber()
    {
        $lastPro = Pro::withTrashed()
            ->where('created_by', $this->userId)
            ->orderByRaw('CAST(pro_no AS UNSIGNED) DESC')
            ->first();

        $startNumber = $lastPro && is_numeric($lastPro->pro_no) ? ((int)$lastPro->pro_no + 1) : 1;
        $proNumber = $startNumber;
        $maxAttempts = 1000;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $exists = Pro::withTrashed()
                ->where('created_by', $this->userId)
                ->where(function ($q) use ($proNumber) {
                    $q->where('pro_no', (string)$proNumber)->orWhere('pro_no', $proNumber);
                })
                ->exists();

            if (!$exists) return $proNumber;
            $proNumber++;
            $attempts++;
        }
        return (int)(time() % 1000000);
    }

    private function parseNumeric($value, $default = 0)
    {
        if (empty($value)) return $default;
        $value = str_replace([',', '$', ' '], '', $value);
        return is_numeric($value) ? (float)$value : $default;
    }

    private function normalizeHeaderKey($value): string
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') return '';
        $value = str_replace([' ', '-', '.', '/', '\\', "\t", "\n", "\r"], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value);
        $value = preg_replace('/_+/', '_', $value);
        return trim($value, '_');
    }

    private function buildRowAssoc(array $row, array $normalizedHeaderByIndex): array
    {
        $assoc = [];
        foreach ($normalizedHeaderByIndex as $idx => $key) {
            if ($key === '') continue;
            if (!array_key_exists($idx, $row)) continue;
            $assoc[$key] = trim((string)$row[$idx]);
        }
        return $assoc;
    }

    private function buildSubProductCustomFieldData(array $rowAssoc, ?ProductService $product): array
    {
        if (!$product) return [];

        $categoryKey = !empty($product->category_id) ? (string) $product->category_id : 'global';
        if (!array_key_exists($categoryKey, $this->subProductCustomFieldCacheByCategory)) {
            $query = CustomField::where('module', 'sub-product')
                ->where('created_by', $this->userId);

            // Include fields for this product's category OR global fields (no category linked)
            if (!empty($product->category_id)) {
                $query->where(function ($q) use ($product) {
                    $q->forCategory($product->category_id)
                        ->orWhereDoesntHave('categories');
                });
            }

            $this->subProductCustomFieldCacheByCategory[$categoryKey] = $query->get();
        }

        $customFields = $this->subProductCustomFieldCacheByCategory[$categoryKey];
        if ($customFields->isEmpty()) return [];

        $data = [];
        foreach ($customFields as $field) {
            $fieldNameKey = $this->normalizeHeaderKey($field->name);
            if ($fieldNameKey === '') continue;

            $possibleKeys = [
                'sub_product_' . $fieldNameKey,
                'sub_product_cf_' . $fieldNameKey,
                'cf_' . $fieldNameKey,
                $fieldNameKey,
            ];

            foreach ($possibleKeys as $key) {
                if (array_key_exists($key, $rowAssoc) && ($rowAssoc[$key] === '0' || (string)$rowAssoc[$key] !== '')) {
                    $data[$field->id] = $rowAssoc[$key];
                    break;
                }
            }
        }

        return $data;
    }

    private function sameNullableFk($a, $b): bool
    {
        $na = ($a === null || $a === '') ? null : (int) $a;
        $nb = ($b === null || $b === '') ? null : (int) $b;

        return $na === $nb;
    }

    private function dimensionsMatchProduct(array $dims, ProductService $ps): bool
    {
        return $this->sameNullableFk($dims['category_id'] ?? null, $ps->category_id)
            && $this->sameNullableFk($dims['brand_id'] ?? null, $ps->brand_id)
            && $this->sameNullableFk($dims['sub_brand_id'] ?? null, $ps->sub_brand_id);
    }

    /**
     * Compare incoming custom field payload with values stored on the sub-product.
     */
    private function customFieldValuesMatch(SubProduct $sp, array $incomingByFieldId): bool
    {
        $existing = CustomField::getData($sp, 'sub-product');

        if (empty($incomingByFieldId)) {
            foreach ($existing as $v) {
                if (is_array($v)) {
                    if (! empty(array_filter($v, static fn ($x) => $x !== null && $x !== ''))) {
                        return false;
                    }
                } elseif ($v !== null && $v !== '') {
                    return false;
                }
            }

            return true;
        }

        foreach ($incomingByFieldId as $fieldId => $val) {
            $ev = $existing->get($fieldId);
            if (is_array($val) && is_array($ev)) {
                $a = array_values($val);
                $b = array_values($ev);
                sort($a);
                sort($b);

                if ($a !== $b) {
                    return false;
                }
            } elseif (is_array($val) || is_array($ev)) {
                return false;
            } else {
                if ((string) ($ev ?? '') !== (string) $val) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Resolve category / brand / sub-brand from row without creating brands (lookup-only).
     */
    private function resolveRowDimensionsReadOnly(array $row, array $columnMap, array $rowAssoc): array
    {
        $categoryIdValue = $this->getValue($row, $columnMap, 'category_id');
        $categoryId = null;
        if (!empty($categoryIdValue) && is_numeric(trim($categoryIdValue))) {
            $categoryId = (int) trim($categoryIdValue);
        }
        if ($categoryId === null) {
            $categoryId = isset($rowAssoc['category_id']) && is_numeric(trim((string) $rowAssoc['category_id']))
                ? (int) trim($rowAssoc['category_id'])
                : null;
        }

        $brandId = null;
        $subBrandId = null;
        $brandName = $this->getValue($row, $columnMap, 'brand_name');
        if ($brandName === '') {
            $brandName = isset($rowAssoc['brand_name']) ? trim((string) $rowAssoc['brand_name']) : '';
        }
        $subBrandName = $this->getValue($row, $columnMap, 'sub_brand_name');
        if ($subBrandName === '') {
            $subBrandName = isset($rowAssoc['sub_brand_name']) ? trim((string) $rowAssoc['sub_brand_name']) : '';
        }

        if ($brandName !== '') {
            $brand = Brand::where('created_by', $this->userId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($brandName))])
                ->first();
            if ($brand) {
                $brandId = $brand->id;
                if ($subBrandName !== '') {
                    $subBrand = VehicleModel::where('created_by', $this->userId)
                        ->where('brand_id', $brandId)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($subBrandName))])
                        ->first();
                    if ($subBrand) {
                        $subBrandId = $subBrand->id;
                    }
                }
            }
        }

        return [
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'sub_brand_id' => $subBrandId,
        ];
    }

    private function subProductMatchesSignature(SubProduct $sp, ?ProductService $product, array $dims, array $incomingCf): bool
    {
        $ps = $sp->productService;
        if (! $ps) {
            return false;
        }
        if ($product !== null) {
            if ((int) $sp->product_id !== (int) $product->id) {
                return false;
            }

            return $this->customFieldValuesMatch($sp, $incomingCf);
        }

        if (! $this->dimensionsMatchProduct($dims, $ps)) {
            return false;
        }

        return $this->customFieldValuesMatch($sp, $incomingCf);
    }

    /**
     * Reuse an existing sub-product when part no + product (or category/brand/sub-brand) + custom fields match — do not create again.
     */
    private function findReusableSubProductBySignature(
        string $partNoTrimmed,
        ?ProductService $product,
        array $dims,
        array $incomingCf
    ): ?SubProduct {
        $query = SubProduct::where('created_by', $this->userId)
            ->where('chassis_no', $partNoTrimmed);

        if ($product !== null) {
            $query->where('product_id', $product->id);
        }

        foreach ($query->with('productService')->get() as $sp) {
            if ($this->subProductMatchesSignature($sp, $product, $dims, $incomingCf)) {
                return $sp;
            }
        }

        return null;
    }

    public function array(array $data)
    {
        try {
            @ini_set('memory_limit', '2048M');
            @ini_set('max_execution_time', '7200');
            set_time_limit(7200);
            DB::connection()->disableQueryLog();

            DB::beginTransaction();

            $startRow = 0;
            for ($i = 0; $i < count($data); $i++) {
                if (!empty(array_filter($data[$i]))) { $startRow = $i; break; }
            }

            $headerRowIndex = null;
            for ($i = $startRow; $i < min($startRow + 15, count($data)); $i++) {
                $row = array_map('strtoupper', array_map('trim', $data[$i]));
                if (in_array('PART NO', $row) || in_array('PART_NO', $row) || in_array('PARTNO', $row)) {
                    $headerRowIndex = $i;
                    break;
                }
            }

            if ($headerRowIndex === null) throw new \Exception('Could not find header row with "PART NO" column.');

            $headerRow = $data[$headerRowIndex];
            $itemRows = array_slice($data, $headerRowIndex + 1);
            $proHeader = $this->extractProHeader($data, $headerRowIndex);
            $proNumber = $this->getNextUniqueProNumber();

            $supplier = null;
            $supplierId = null;
            $supplierName = null;
            if (!empty($proHeader['supplier_name'])) {
                $supplier = $this->findOrCreateSupplier($proHeader['supplier_name']);
                if ($supplier) { $supplierId = $supplier->id; $supplierName = $supplier->name; }
            } else {
                throw new \Exception('Supplier name is required in the import file. Please ensure "SUPPLIER NAME" is specified.');
            }

            $poDate = !empty($proHeader['po_date']) ? $this->parseDate($proHeader['po_date']) : now()->format('Y-m-d');
            if (!$poDate) $poDate = now()->format('Y-m-d');

            $pro = new Pro();
            $pro->pro_no = (string)$proNumber;
            $pro->supplier_id = $supplierId;
            $pro->supplier_name = $supplierName;
            $pro->po_date = $poDate;
            $pro->supplier_proforma_no = $proHeader['supplier_proforma_no'] ?? null;
            $pro->supplier_proforma_date = !empty($proHeader['supplier_proforma_date']) ? $this->parseDate($proHeader['supplier_proforma_date']) : null;
            $pro->our_order_ref = $proHeader['our_order_ref'] ?? null;
            $pro->supplier_ref = $proHeader['supplier_ref'] ?? null;
            $pro->eta_date = !empty($proHeader['eta_date']) ? $this->parseDate($proHeader['eta_date']) : null;
            $currencyId = !empty($proHeader['currency_id']) ? trim($proHeader['currency_id']) : null;
            $pro->currency_id = !empty($currencyId) && is_numeric($currencyId) ? (int)$currencyId : null;
            $pro->exchange_rate = !empty($proHeader['exchange_rate']) ? $this->parseNumeric($proHeader['exchange_rate'], 1.0) : 1.0;
            $pro->status = 'open';
            $pro->created_by = $this->userId;
            $pro->save();

            $columnMap = $this->mapColumns($headerRow);
            $normalizedHeaderByIndex = [];
            foreach ($headerRow as $idx => $headerCell) {
                $normalizedHeaderByIndex[$idx] = $this->normalizeHeaderKey($headerCell);
            }
            $this->preloadLookupData($itemRows, $columnMap, $normalizedHeaderByIndex);
            $itemsCreated = 0;
            $rowsWithoutPartNo = [];

            foreach ($itemRows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;
                $partNo = trim($this->getValue($row, $columnMap, 'part_no'));
                $description = trim($this->getValue($row, $columnMap, 'description'));
                if (empty($partNo)) {
                    $rowsWithoutPartNo[] = 'Row ' . ($rowIndex + 1) . ($description ? " (Description: {$description})" : '');
                }
            }

            if (!empty($rowsWithoutPartNo)) {
                DB::rollBack();
                throw new \Exception('Import failed: Part number is required for all items. The following rows are missing part numbers: ' . implode(', ', $rowsWithoutPartNo));
            }

            foreach ($itemRows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;

                try {
                    $rowAssoc = $this->buildRowAssoc($row, $normalizedHeaderByIndex);
                    $partNo = $this->getValue($row, $columnMap, 'part_no');
                    $description = $this->getValue($row, $columnMap, 'description');
                    $orderQty = $this->getValue($row, $columnMap, 'order_qty');
                    $suppliedQty = $this->getValue($row, $columnMap, 'supplied_qty');
                    $salePriceRaw = $this->getValue($row, $columnMap, 'sale_price');
                    $unitPrice = $this->getValue($row, $columnMap, 'unit_price');

                    if (empty($partNo)) throw new \Exception("Part number is required. Row with description '{$description}' is missing part number.");

                    $orderQty = $this->parseNumeric($orderQty, 0);
                    $suppliedQty = $this->parseNumeric($suppliedQty, 0);
                    if ($salePriceRaw === '') {
                        $salePriceRaw = $rowAssoc['sale_price'] ?? ($rowAssoc['saleprice'] ?? ($rowAssoc['selling_price'] ?? ($rowAssoc['sellingprice'] ?? '')));
                    }
                    $salePrice = $this->parseNumeric($salePriceRaw, null);
                    $unitPrice = $this->parseNumeric($unitPrice, 0);
                    $remainingQty = $orderQty - $suppliedQty;
                    $totalAmount = $orderQty * $unitPrice;

                    $productSku = $this->getValue($row, $columnMap, 'sku');
                    if (empty($productSku)) $productSku = isset($rowAssoc['sku']) ? trim((string)$rowAssoc['sku']) : '';
                    if (empty($productSku)) $productSku = $partNo;

                    if (empty(trim($description ?? '')) && !empty($partNo)) {
                        $subProductForDesc = SubProduct::where('created_by', $this->userId)->where('chassis_no', trim($partNo))->with('productService')->latest()->first();
                        if ($subProductForDesc && $subProductForDesc->productService) $description = $subProductForDesc->productService->name;
                    }

                    // Each SKU = one product: find product by SKU only (do not match by name so each SKU gets its own parent when creating)
                    $product = $this->getCachedProductBySku($productSku);
                    if ($product && $salePrice !== null) {
                        $product->sale_price = $salePrice;
                        $product->save();
                    }
                    $productId = $product ? $product->id : null;

                    $subProduct = null;
                    $subProductId = null;
                    $partNoTrimmed = trim($partNo);

                    // Signature: same part + same parent product (category/brand/sub-brand) + same custom fields => reuse stock line
                    if ($product) {
                        $dims = [
                            'category_id' => $product->category_id,
                            'brand_id' => $product->brand_id,
                            'sub_brand_id' => $product->sub_brand_id,
                        ];
                    } else {
                        $dims = $this->resolveRowDimensionsReadOnly($row, $columnMap, $rowAssoc);
                    }

                    $cfContext = $product;
                    if (! $cfContext) {
                        $cfContext = new ProductService();
                        $cfContext->category_id = $dims['category_id'];
                        $cfContext->brand_id = $dims['brand_id'];
                        $cfContext->sub_brand_id = $dims['sub_brand_id'];
                    }
                    $incomingCf = $this->buildSubProductCustomFieldData($rowAssoc, $cfContext);

                    $subProduct = $this->findReusableSubProductBySignature($partNoTrimmed, $product, $dims, $incomingCf);
                    if (! $subProduct) {
                        $cached = $this->getCachedSubProductByPartNo($partNoTrimmed);
                        if ($cached && $this->subProductMatchesSignature($cached, $product, $dims, $incomingCf)) {
                            $subProduct = $cached;
                        }
                    }

                    if ($subProduct) {
                        $subProductId = $subProduct->id;
                        if (! $productId && $subProduct->product_id) {
                            $productId = $subProduct->product_id;
                        }
                        if (! $product && ! empty($subProduct->product_id)) {
                            $product = ProductService::where('id', $subProduct->product_id)
                                ->where('created_by', $this->userId)
                                ->first();
                        }
                        if ($product) {
                            $this->productCacheBySku[$this->normalizeLookupKey($product->sku)] = $product;
                        }
                        if ($salePrice !== null) {
                            $subProduct->sale_price = $salePrice;
                            $subProduct->save();
                        }

                        $customFieldData = $this->buildSubProductCustomFieldData($rowAssoc, $product);
                        if (! empty($customFieldData)) {
                            CustomField::saveData($subProduct, $customFieldData);
                        }

                        $this->subProductCacheByPartNo[$this->normalizeLookupKey($partNoTrimmed)] = $subProduct;
                    } elseif ($productId) {
                        $defaultWarehouse = warehouse::where('created_by', $this->userId)->first();
                        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
                        $subProduct = new SubProduct();
                        $subProduct->chassis_no = $partNoTrimmed;
                        $subProduct->product_id = $productId;
                        $subProduct->quantity = 0;
                        $subProduct->warehouse_id = $warehouseId;
                        $subProduct->sale_price = $salePrice !== null ? $salePrice : ($product->sale_price ?? 0);
                        $subProduct->purchase_price = $product->purchase_price ?? 0;
                        $subProduct->created_by = $this->userId;
                        $subProduct->flag = SubProduct::FLAG_ORDERED;
                        $subProduct->SP_sku = $product->sku;
                        $subProduct->import_source = 'item_master';
                        $note = $rowAssoc['note'] ?? ($rowAssoc['sub_product_note'] ?? null);
                        if ($note !== null && $note !== '') {
                            $subProduct->note = $note;
                        }
                        $subProduct->save();
                        $subProductId = $subProduct->id;
                        $this->subProductCacheByPartNo[$this->normalizeLookupKey($partNoTrimmed)] = $subProduct;

                        $customFieldData = $this->buildSubProductCustomFieldData($rowAssoc, $product);
                        if (!empty($customFieldData)) {
                            CustomField::saveData($subProduct, $customFieldData);
                        }

                        \Log::info('PRO Import (create stock): Created new SubProduct', [
                            'user_id' => $this->userId, 'sub_product_id' => $subProductId, 'product_no' => $partNoTrimmed, 'product_id' => $productId,
                        ]);
                    } elseif (!empty($description) || !empty($partNoTrimmed)) {
                        // Part not in stock: first create Product (SKU, brand, sub-brand, category), then create SubProduct (part no + custom fields)

                        // --- Step 1: Create Product (ProductService) with SKU, brand, sub-brand, category ---
                        $productName = !empty(trim($description)) ? trim($description) : $partNoTrimmed;

                        $categoryIdValue = $this->getValue($row, $columnMap, 'category_id');
                        $categoryId = null;
                        if (!empty($categoryIdValue) && is_numeric(trim($categoryIdValue))) {
                            $categoryId = (int) trim($categoryIdValue);
                        }
                        if (empty($categoryId)) {
                            $categoryId = isset($rowAssoc['category_id']) && is_numeric(trim((string)$rowAssoc['category_id'])) ? (int) trim($rowAssoc['category_id']) : null;
                        }

                        $brandId = null;
                        $subBrandId = null;
                        $brandName = $this->getValue($row, $columnMap, 'brand_name');
                        if (empty($brandName)) $brandName = isset($rowAssoc['brand_name']) ? trim((string)$rowAssoc['brand_name']) : '';
                        $subBrandName = $this->getValue($row, $columnMap, 'sub_brand_name');
                        if (empty($subBrandName)) $subBrandName = isset($rowAssoc['sub_brand_name']) ? trim((string)$rowAssoc['sub_brand_name']) : '';
                        if (!empty($brandName)) {
                            // Pass categoryId so new brand (if created) can be linked to the same category
                            $brand = $this->findBrandByName($brandName, $categoryId);
                            if ($brand) {
                                $brandId = $brand->id;
                                if (!empty($subBrandName)) {
                                    $subBrand = $this->findSubBrandByName($subBrandName, $brandId);
                                    if ($subBrand) $subBrandId = $subBrand->id;
                                }
                            }
                        }

                        $newProductSku = $productSku;
                        if (empty(trim($newProductSku ?? ''))) $newProductSku = $partNoTrimmed;

                        $newProduct = new ProductService();
                        $newProduct->name = $productName;
                        $newProduct->sku = $newProductSku;
                        $newProduct->category_id = $categoryId;
                        $newProduct->brand_id = $brandId;
                        $newProduct->sub_brand_id = $subBrandId;
                        $newProduct->sale_price = $salePrice !== null ? $salePrice : $unitPrice;
                        $newProduct->purchase_price = $unitPrice;
                        $newProduct->type = 'product';
                        $newProduct->created_by = $this->userId;
                        $newProduct->save();
                        $productId = $newProduct->id;
                        $product = $newProduct;
                        $this->productCacheBySku[$this->normalizeLookupKey($newProduct->sku)] = $newProduct;

                        // --- Step 2: Create SubProduct with part no and custom fields ---
                        $defaultWarehouse = warehouse::where('created_by', $this->userId)->first();
                        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
                        $subProduct = new SubProduct();
                        $subProduct->chassis_no = $partNoTrimmed;  // part no
                        $subProduct->product_id = $productId;
                        $subProduct->quantity = 0;
                        $subProduct->warehouse_id = $warehouseId;
                        $subProduct->sale_price = $salePrice !== null ? $salePrice : $unitPrice;
                        $subProduct->purchase_price = $unitPrice;
                        $subProduct->created_by = $this->userId;
                        $subProduct->flag = SubProduct::FLAG_ORDERED;
                        $subProduct->SP_sku = $newProduct->sku;
                        $subProduct->import_source = 'item_master';
                        $note = $rowAssoc['note'] ?? ($rowAssoc['sub_product_note'] ?? null);
                        if ($note !== null && $note !== '') {
                            $subProduct->note = $note;
                        }
                        $subProduct->save();
                        $subProductId = $subProduct->id;
                        $this->subProductCacheByPartNo[$this->normalizeLookupKey($partNoTrimmed)] = $subProduct;

                        // Apply custom fields to sub-product
                        $customFieldData = $this->buildSubProductCustomFieldData($rowAssoc, $product);
                        if (!empty($customFieldData)) {
                            CustomField::saveData($subProduct, $customFieldData);
                        }

                        \Log::info('PRO Import (create stock): Created Product (sku, brand, sub-brand, category) then SubProduct (part_no, custom fields)', [
                            'user_id' => $this->userId, 'product_id' => $productId, 'sub_product_id' => $subProductId, 'product_no' => $partNoTrimmed,
                        ]);
                    }

                    $item = new ProItem();
                    $item->pro_id = $pro->id;
                    $item->product_id = $productId;
                    // pro_items.sub_product_id was removed by migration; keep only product-level linkage.
                    $item->part_no = $partNo;
                    $item->description = $description;
                    $item->order_qty = $orderQty;
                    $item->supplied_qty = $suppliedQty;
                    $item->remaining_qty = $remainingQty;
                    $item->unit_price = $unitPrice;
                    $item->total_amount = $totalAmount;
                    $item->save();

                    // No need 
                    // MasterlistLeadger::addBooked($productId,null,$orderQty,'PRO',)

                    $itemsCreated++;
                } catch (\Exception $e) {
                    \Log::error('ProCreateSubProductImport row error', ['error' => $e->getMessage(), 'row_index' => $rowIndex, 'user_id' => $this->userId]);
                    continue;
                }
            }

            if ($itemsCreated == 0) throw new \Exception('No valid items found in the file.');

            DB::commit();
            \Log::info('PRO import (create stock) completed', ['pro_id' => $pro->id, 'pro_number' => $proNumber, 'items_created' => $itemsCreated, 'user_id' => $this->userId]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PRO import (create stock) failed', ['error' => $e->getMessage(), 'user_id' => $this->userId]);
            throw $e;
        }
    }
}
