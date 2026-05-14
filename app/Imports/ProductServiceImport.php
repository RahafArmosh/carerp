<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Tax;
use App\Models\VehicleModel;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithQueuedChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductServiceImport implements
    OnEachRow,
    WithHeadingRow,
    WithChunkReading
    // WithQueuedChunkReading,
    // ShouldQueue
{

    protected $creatorId;

    public function __construct($creatorId)
    {
        $this->creatorId = $creatorId;
        \Log::info('ProductServiceImport constructor called', ['creator_id' => $creatorId]);
    }
    public function onRow(Row $row)
    {
        try {
            $items = $row->toArray();

            // Debug: Log the row data with more details
            \Log::info('=== PROCESSING ROW ===');
            \Log::info('Row number: ' . $row->getIndex());
            \Log::info('Raw row data:', $items);
            \Log::info('Creator ID: ' . $this->creatorId);

            if (empty($items['sku'])) {
                \Log::warning('Skipping row - empty SKU', ['row_index' => $row->getIndex()]);
                return;
            }

            // Debug: Check if required fields exist
            $requiredFields = ['name', 'sku', 'sale_price', 'purchase_price', 'type'];
            foreach ($requiredFields as $field) {
                if (!isset($items[$field]) || ($items[$field] !== '0' && empty($items[$field]))) {
                    \Log::error("Missing required field: $field", [
                        'row_index' => $row->getIndex(),
                        'available_fields' => array_keys($items),
                        'field_value' => $items[$field] ?? 'N/A',
                        'sku' => $items['sku'] ?? 'N/A'
                    ]);
                    return;
                }
            }

            // Handle taxes - can be semicolon or comma separated
            $itemsLower = [];
            foreach ($items as $k => $v) {
                $itemsLower[strtolower((string) $k)] = $v;
            }

            $taxString = $itemsLower['tax'] ?? '';
            \Log::info('Tax string: ' . $taxString);

            $taxes = [];
            if (! empty($taxString)) {
                $taxes = preg_split('/[;,]/', (string) $taxString);
                $taxes = array_map('trim', $taxes);
                $taxes = array_filter($taxes);
            }
            \Log::info('Parsed taxes:', $taxes);

            $taxesData = [];
            $totalVatRate = 0;
            foreach ($taxes as $taxToken) {
                $taxModel = null;
                if (is_numeric($taxToken) && ctype_digit((string) $taxToken)) {
                    $taxModel = Tax::where('id', (int) $taxToken)
                        ->where('created_by', $this->creatorId)
                        ->first();
                }
                if (! $taxModel) {
                    $taxModel = Tax::where('created_by', $this->creatorId)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim((string) $taxToken))])
                        ->first();
                }
                if ($taxModel) {
                    $taxesData[] = $taxModel->id;
                    $totalVatRate += floatval($taxModel->rate ?? 0);
                    \Log::info("Found tax: {$taxModel->id} - {$taxModel->name} - Rate: {$taxModel->rate}%");
                } else {
                    \Log::warning("Tax not found: {$taxToken} for creator: {$this->creatorId}");
                }
            }

            $categoryId = $this->resolveCategoryIdByName($itemsLower);
            $brandId = $this->resolveBrandIdByName($itemsLower);
            $subBrandId = $this->resolveModelIdByName($itemsLower, $brandId);
            $unitId = $this->resolveUnitIdByName($itemsLower);

            if (! $categoryId || ! $brandId || ! $subBrandId) {
                \Log::error('Product import: missing category, brand, or model (match by name)', [
                    'row_index' => $row->getIndex(),
                    'sku' => $items['sku'] ?? 'N/A',
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'sub_brand_id' => $subBrandId,
                ]);

                return;
            }

            // Check if product already exists
            $existingProduct = ProductService::where('sku', $items['sku'])
                ->where('created_by', $this->creatorId)
                ->first();

            if ($existingProduct) {
                // Do NOT overwrite existing product records on re-import; keep original values intact
                \Log::info('Product exists - skipping updates to core fields', ['id' => $existingProduct->id, 'sku' => $items['sku']]);
                $productService = $existingProduct;
            } else {
                \Log::info('Creating new product', ['sku' => $items['sku']]);
                $productService = new ProductService();
                // Validate and convert data for NEW product only
                $productService->name             = trim($items['name']);
                $productService->sku              = trim($items['sku']);
                
                // Calculate sale price with VAT
                $baseSalePrice = floatval($items['sale_price']);
                $productService->sale_price_base  = $baseSalePrice;
                
                // Apply VAT to sale price if taxes are present
                if ($totalVatRate > 0) {
                    $salePriceWithVat = $baseSalePrice * (1 + ($totalVatRate / 100));
                    $productService->sale_price = $salePriceWithVat;
                    \Log::info('Sale price calculated with VAT', [
                        'base_price' => $baseSalePrice,
                        'vat_rate' => $totalVatRate,
                        'price_with_vat' => $salePriceWithVat
                    ]);
                } else {
                    $productService->sale_price = $baseSalePrice;
                }
                $productService->purchase_price   = floatval($items['purchase_price']);
                $productService->quantity         = floatval($items['quantity'] ?? 0);
                $productService->tax_id           = implode(',', $taxesData);
                $productService->category_id      = $categoryId;
                $productService->brand_id         = $brandId;
                $productService->sub_brand_id     = $subBrandId;
                $productService->unit_id          = $unitId ?? 0;
                $productService->type             = trim($items['type']);
                $productService->description      = $items['description'] ?? null;
                $productService->created_by       = $this->creatorId;

                \Log::info('Product data before save:', [
                    'name' => $productService->name,
                    'sku' => $productService->sku,
                    'sale_price' => $productService->sale_price,
                    'purchase_price' => $productService->purchase_price,
                    'type' => $productService->type,
                    'created_by' => $productService->created_by
                ]);

                $productService->save();
                \Log::info('Product saved successfully:', ['sku' => $productService->sku, 'id' => $productService->id]);
            }


            // Handle custom fields
            $this->processCustomFields($productService, $items);
            
            \Log::info('=== ROW PROCESSED SUCCESSFULLY ===');
        } catch (\Exception $e) {
            \Log::error('ProductService Import Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'row_data' => $row->toArray(),
                'creator_id' => $this->creatorId
            ]);
            
            // Also print to console/log for immediate debugging
            echo "Import Error: " . $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile() . "\n";
            echo "Row data: " . json_encode($row->toArray()) . "\n";
        }
    }


    public function chunkSize(): int
    {
        return 500;
    }

    public function startRow(): int
    {
        \Log::info('ProductServiceImport startRow called');
        return 2; // Skip header row
    }

    /**
     * Process custom fields for the product
     */
    private function processCustomFields($productService, $items)
    {
        try {
            $itemsLower = [];
            foreach ($items as $k => $v) {
                $itemsLower[strtolower((string) $k)] = $v;
            }
            // Get all custom fields for the 'product' module
            $customFields = CustomField::where('module', 'product')
                ->where('created_by', $this->creatorId)
                ->get();

            \Log::info('Found custom fields for product module:', [
                'count' => $customFields->count(),
                'fields' => $customFields->pluck('name', 'id')->toArray()
            ]);

            $customFieldData = [];

            // Process each custom field
            foreach ($customFields as $customField) {
                $fieldName = $customField->name;
                $fieldId = $customField->id;
                $lookupKey = strtolower($fieldName);

                // Check if the field exists in the Excel data (allow "0" values), case-insensitive
                if (array_key_exists($lookupKey, $itemsLower) && ($itemsLower[$lookupKey] === '0' || $itemsLower[$lookupKey] === 0 || !empty($itemsLower[$lookupKey]))) {
                    $fieldValue = trim((string)$itemsLower[$lookupKey]);
                    
                    // Validate field value based on type
                    if ($this->validateCustomFieldValue($customField, $fieldValue)) {
                        $customFieldData[$fieldId] = $fieldValue;
                        
                        \Log::info('Custom field processed:', [
                            'field_name' => $fieldName,
                            'field_id' => $fieldId,
                            'field_type' => $customField->type,
                            'field_value' => $fieldValue
                        ]);
                    } else {
                        \Log::warning('Invalid custom field value:', [
                            'field_name' => $fieldName,
                            'field_type' => $customField->type,
                            'field_value' => $fieldValue
                        ]);
                    }
                }
            }

            // Save custom field data if any exists
            if (!empty($customFieldData)) {
                CustomField::saveData($productService, $customFieldData);
                
                \Log::info('Custom fields saved for product:', [
                    'product_id' => $productService->id,
                    'sku' => $productService->sku,
                    'custom_fields_count' => count($customFieldData)
                ]);
            } else {
                \Log::info('No custom fields found for product:', [
                    'product_id' => $productService->id,
                    'sku' => $productService->sku
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error processing custom fields:', [
                'product_id' => $productService->id,
                'sku' => $productService->sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
                // Check if value is in the options
                $options = json_decode($customField->options, true);
                return in_array($value, $options ?? []);
            
            case 'text':
            case 'textarea':
            default:
                return true; // Text fields are always valid
        }
    }

    private function firstNonEmptyString(array $itemsLower, array $keys): string
    {
        foreach ($keys as $key) {
            $k = strtolower($key);
            if (! array_key_exists($k, $itemsLower)) {
                continue;
            }
            $v = $itemsLower[$k];
            if ($v === null || $v === '') {
                continue;
            }
            if (is_string($v) && trim($v) === '') {
                continue;
            }
            $s = trim((string) $v);

            return $s;
        }

        return '';
    }

    private function resolveCategoryIdByName(array $itemsLower): ?int
    {
        $name = $this->firstNonEmptyString($itemsLower, ['category_name', 'category']);
        if ($name === '') {
            return null;
        }
        $row = ProductServiceCategory::where('created_by', $this->creatorId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        return $row?->id;
    }

    private function resolveBrandIdByName(array $itemsLower): ?int
    {
        $name = $this->firstNonEmptyString($itemsLower, ['brand_name', 'brand']);
        if ($name === '') {
            return null;
        }
        $row = Brand::where('created_by', $this->creatorId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        return $row?->id;
    }

    private function resolveModelIdByName(array $itemsLower, ?int $brandId): ?int
    {
        if (! $brandId) {
            return null;
        }
        $name = $this->firstNonEmptyString($itemsLower, ['model_name', 'model', 'sub_brand_name', 'sub_brand']);
        if ($name === '') {
            return null;
        }
        $row = VehicleModel::where('created_by', $this->creatorId)
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        return $row?->id;
    }

    private function resolveUnitIdByName(array $itemsLower): ?int
    {
        $name = $this->firstNonEmptyString($itemsLower, ['unit_name', 'unit']);
        if ($name === '') {
            return null;
        }
        $row = ProductServiceUnit::where('created_by', $this->creatorId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        return $row?->id;
    }
}
