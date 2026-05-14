<?php

namespace App\Imports;

use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class SubProductUpdateImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $creatorId;
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $customFields = null;

    public function __construct($creatorId)
    {
        $this->creatorId = $creatorId;
    }

    /**
     * Process data in chunks to avoid memory issues
     */
    public function chunkSize(): int
    {
        return 500; // Process 500 rows at a time
    }

    /**
     * Reconnect to database to prevent "MySQL server has gone away" errors
     */
    protected function reconnectDatabase()
    {
        DB::reconnect();
    }

    public function collection(Collection $rows)
    {
        // Reconnect to database before processing
        $this->reconnectDatabase();
        
        // Pre-load all custom fields for sub-products (cache it)
        if ($this->customFields === null) {
            $this->customFields = CustomField::where('module', 'sub-product')
                ->where('created_by', $this->creatorId)
                ->get()
                ->keyBy(function($field) {
                    // Normalize field name for matching (lowercase, replace spaces with underscores)
                    return strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', $field->name)));
                });
        }

        // Process this chunk in smaller batches for transactions
        $batchSize = 100; // Process 100 rows per transaction
        $rowsArray = $rows->toArray();
        $batches = array_chunk($rowsArray, $batchSize, true);
        
        foreach ($batches as $batchIndex => $batch) {
            DB::beginTransaction();
            
            try {
                foreach ($batch as $rowIndex => $row) {
                    try {
                    // Normalize row keys (Excel may have spaces, special chars)
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        $normalizedKey = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', $key)));
                        $normalizedRow[$normalizedKey] = $value;
                    }

                    // Export headers use "Chassis No"; legacy templates use "Product No"
                    if ((!isset($normalizedRow['product_no']) || $normalizedRow['product_no'] === null || $normalizedRow['product_no'] === '')
                        && isset($normalizedRow['chassis_no']) && $normalizedRow['chassis_no'] !== null && $normalizedRow['chassis_no'] !== '') {
                        $normalizedRow['product_no'] = $normalizedRow['chassis_no'];
                    }

                    // "Model" column alias for Sub Brand (name or ID handled below)
                    if ((!isset($normalizedRow['sub_brand']) || $normalizedRow['sub_brand'] === null || $normalizedRow['sub_brand'] === '')
                        && isset($normalizedRow['model']) && $normalizedRow['model'] !== null && $normalizedRow['model'] !== '') {
                        $normalizedRow['sub_brand'] = $normalizedRow['model'];
                    }
                    if ((!isset($normalizedRow['sub_brand_id']) || $normalizedRow['sub_brand_id'] === null || $normalizedRow['sub_brand_id'] === '')
                        && isset($normalizedRow['model_id']) && $normalizedRow['model_id'] !== null && $normalizedRow['model_id'] !== '') {
                        $normalizedRow['sub_brand_id'] = $normalizedRow['model_id'];
                    }

                    // Get ID from row (handle different possible column names)
                    $id = null;
                    if (isset($normalizedRow['id'])) {
                        $id = $normalizedRow['id'];
                    } elseif (isset($normalizedRow['sub_product_id'])) {
                        $id = $normalizedRow['sub_product_id'];
                    } elseif (isset($normalizedRow['subproduct_id'])) {
                        $id = $normalizedRow['subproduct_id'];
                    }

                    // Validate ID exists
                    if (empty($id)) {
                        $this->errors[] = "Row " . ($rowIndex + 2) . ": ID is required";
                        $this->errorCount++;
                        continue;
                    }

                    // Convert ID to integer if it's numeric
                    $id = is_numeric($id) ? (int)$id : $id;

                    // Find sub-product by ID
                    $subProduct = SubProduct::where('id', $id)
                        ->where('created_by', $this->creatorId)
                        ->first();

                    if (!$subProduct) {
                        $this->errors[] = "Row " . ($rowIndex + 2) . ": Sub-product with ID {$id} not found or you don't have permission to update it";
                        $this->errorCount++;
                        continue;
                    }

                    // Update fields if provided
                    $updateData = [];

                    // Update product_no if provided
                    if (isset($normalizedRow['product_no']) && $normalizedRow['product_no'] !== null && $normalizedRow['product_no'] !== '') {
                        $updateData['product_no'] = trim((string)$normalizedRow['product_no']);
                    }

                    // Helper function to clean and parse numeric values from Excel
                    $parseNumericValue = function($value) {
                        if ($value === null || $value === '') {
                            return null; // Not provided
                        }

                        // Convert to string and clean (remove currency symbols, spaces, etc.)
                        $cleaned = trim((string)$value);
                        $cleaned = preg_replace('/[^\d.,-]/', '', $cleaned); // Keep only digits, dots, commas, and minus

                        // Normalize decimal/thousand separators for common formats:
                        // 1,234.56 | 1.234,56 | 1234,56 | 1234.56
                        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
                            $lastComma = strrpos($cleaned, ',');
                            $lastDot = strrpos($cleaned, '.');
                            if ($lastComma > $lastDot) {
                                // Comma is decimal separator
                                $cleaned = str_replace('.', '', $cleaned);
                                $cleaned = str_replace(',', '.', $cleaned);
                            } else {
                                // Dot is decimal separator
                                $cleaned = str_replace(',', '', $cleaned);
                            }
                        } elseif (strpos($cleaned, ',') !== false) {
                            // If comma looks like decimal separator, convert to dot
                            if (preg_match('/,\d{1,2}$/', $cleaned)) {
                                $cleaned = str_replace('.', '', $cleaned);
                                $cleaned = str_replace(',', '.', $cleaned);
                            } else {
                                $cleaned = str_replace(',', '', $cleaned);
                            }
                        }

                        // Check if it's a valid number
                        if ($cleaned === '' || $cleaned === '-') {
                            return null; // Not a valid number
                        }

                        $numericValue = (float)$cleaned;
                        return is_finite($numericValue) ? $numericValue : null;
                    };

                    // Update sale_price if provided (support common column aliases)
                    $salePriceValue = null;
                    foreach (['sale_price', 'saleprice', 'selling_price', 'sellingprice'] as $salePriceColumn) {
                        if (array_key_exists($salePriceColumn, $normalizedRow) && $normalizedRow[$salePriceColumn] !== null && $normalizedRow[$salePriceColumn] !== '') {
                            $salePriceValue = $normalizedRow[$salePriceColumn];
                            break;
                        }
                    }
                    if ($salePriceValue !== null) {
                        $salePrice = $parseNumericValue($salePriceValue);
                        if ($salePrice !== null) {
                            $updateData['sale_price'] = $salePrice;
                        }
                    }

                    // Update purchase_price if provided (only if value is actually present and valid)
                    // Also check for combined "Purchase F Quantity" column format (e.g., "176.00Dhs 1")
                    $purchasePriceValue = null;
                    if (isset($normalizedRow['purchase_price']) && $normalizedRow['purchase_price'] !== null && $normalizedRow['purchase_price'] !== '') {
                        $purchasePriceValue = $normalizedRow['purchase_price'];
                    } elseif (isset($normalizedRow['purchase_f_quantity']) && $normalizedRow['purchase_f_quantity'] !== null && $normalizedRow['purchase_f_quantity'] !== '') {
                        // Handle combined "Purchase F Quantity" column - extract price part (before the quantity)
                        $combinedValue = trim((string)$normalizedRow['purchase_f_quantity']);
                        // Try to extract price (usually the first number before a space or text)
                        if (preg_match('/^([\d.,]+)/', $combinedValue, $matches)) {
                            $purchasePriceValue = $matches[1];
                        }
                    }
                    
                    if ($purchasePriceValue !== null) {
                        $purchasePrice = $parseNumericValue($purchasePriceValue);
                        if ($purchasePrice !== null) {
                            $updateData['purchase_price'] = $purchasePrice;
                        }
                    }

                    // Quantity updates are disabled - skip quantity column to prevent accidental updates
                    // Update quantity if provided (only if value is actually present and valid)
                    // Also check for combined "Purchase F Quantity" column format (e.g., "176.00Dhs 1")
                    // DISABLED: Quantity updates are not allowed via import
                    /*
                    $quantityValue = null;
                    if (isset($normalizedRow['quantity']) && $normalizedRow['quantity'] !== null && $normalizedRow['quantity'] !== '') {
                        $quantityValue = $normalizedRow['quantity'];
                    } elseif (isset($normalizedRow['purchase_f_quantity']) && $normalizedRow['purchase_f_quantity'] !== null && $normalizedRow['purchase_f_quantity'] !== '') {
                        // Handle combined "Purchase F Quantity" column - extract quantity part (after the price)
                        $combinedValue = trim((string)$normalizedRow['purchase_f_quantity']);
                        // Try to extract quantity (usually the last number)
                        if (preg_match('/\s+(\d+)\s*$/', $combinedValue, $matches)) {
                            $quantityValue = $matches[1];
                        }
                    }
                    
                    if ($quantityValue !== null) {
                        $quantity = $parseNumericValue($quantityValue);
                        if ($quantity !== null) {
                            $updateData['quantity'] = max(0, (int)$quantity); // Ensure quantity is not negative
                        }
                    }
                    */

                    // Update warehouse_id if provided (can be ID or warehouse name)
                    // Check multiple possible column names: warehouse_id, warehouse, location
                    $warehouseValue = null;
                    $warehouseColumnName = null;
                    
                    if (isset($normalizedRow['warehouse_id']) && $normalizedRow['warehouse_id'] !== null && $normalizedRow['warehouse_id'] !== '') {
                        $warehouseValue = trim((string)$normalizedRow['warehouse_id']);
                        $warehouseColumnName = 'warehouse_id';
                    } elseif (isset($normalizedRow['warehouse']) && $normalizedRow['warehouse'] !== null && $normalizedRow['warehouse'] !== '') {
                        $warehouseValue = trim((string)$normalizedRow['warehouse']);
                        $warehouseColumnName = 'warehouse';
                    } elseif (isset($normalizedRow['location']) && $normalizedRow['location'] !== null && $normalizedRow['location'] !== '') {
                        $warehouseValue = trim((string)$normalizedRow['location']);
                        $warehouseColumnName = 'location';
                    }
                    
                    if ($warehouseValue !== null) {
                        $warehouseId = null;
                        
                        // Check if it's a numeric ID
                        if (is_numeric($warehouseValue)) {
                            $warehouseId = (int)$warehouseValue;
                        } else {
                            // Try to find warehouse by name
                            $warehouse = warehouse::where('name', $warehouseValue)
                                ->where('created_by', $this->creatorId)
                                ->first();
                            
                            if ($warehouse) {
                                $warehouseId = $warehouse->id;
                            }
                        }
                        
                        // Validate warehouse exists and belongs to user's company
                        if ($warehouseId) {
                            $warehouse = warehouse::where('id', $warehouseId)
                                ->where('created_by', $this->creatorId)
                                ->first();
                            
                            if ($warehouse) {
                                $updateData['warehouse_id'] = $warehouseId;
                            } else {
                                // Log error but continue with other fields
                                $this->errors[] = "Row " . ($rowIndex + 2) . ": Warehouse ID {$warehouseId} not found or you don't have permission to use it";
                                // Don't increment errorCount here - we still process other fields successfully
                            }
                        } else {
                            // Log error but continue with other fields
                            $this->errors[] = "Row " . ($rowIndex + 2) . ": Warehouse '{$warehouseValue}' not found";
                            // Don't increment errorCount here - we still process other fields successfully
                        }
                    }

                    // Update parent product's Brand and Sub Brand if provided
                    try {
                        $product = $subProduct->productService;
                        if ($product) {
                            $productUpdate = [];

                            // Brand: allow updating by ID or by name (column headers: Brand or Brand ID)
                            if (isset($normalizedRow['brand_id']) && $normalizedRow['brand_id'] !== null && $normalizedRow['brand_id'] !== '') {
                                $brandIdValue = trim((string)$normalizedRow['brand_id']);
                                if (is_numeric($brandIdValue)) {
                                    $brand = Brand::find((int)$brandIdValue);
                                    if ($brand) {
                                        $productUpdate['brand_id'] = $brand->id;
                                    } else {
                                        $this->errors[] = "Row " . ($rowIndex + 2) . ": Brand ID {$brandIdValue} not found";
                                    }
                                }
                            } elseif (isset($normalizedRow['brand']) && $normalizedRow['brand'] !== null && $normalizedRow['brand'] !== '') {
                                $brandNameValue = trim((string)$normalizedRow['brand']);
                                $brand = Brand::where('name', $brandNameValue)->first();
                                if ($brand) {
                                    $productUpdate['brand_id'] = $brand->id;
                                } else {
                                    $this->errors[] = "Row " . ($rowIndex + 2) . ": Brand '{$brandNameValue}' not found";
                                }
                            }

                            // Sub Brand: allow updating by ID or by name (column headers: Sub Brand or Sub Brand ID)
                            if (isset($normalizedRow['sub_brand_id']) && $normalizedRow['sub_brand_id'] !== null && $normalizedRow['sub_brand_id'] !== '') {
                                $subBrandIdValue = trim((string)$normalizedRow['sub_brand_id']);
                                if (is_numeric($subBrandIdValue)) {
                                    $subBrand = VehicleModel::find((int)$subBrandIdValue);
                                    if ($subBrand) {
                                        $productUpdate['sub_brand_id'] = $subBrand->id;
                                    } else {
                                        $this->errors[] = "Row " . ($rowIndex + 2) . ": Sub Brand ID {$subBrandIdValue} not found";
                                    }
                                }
                            } elseif (isset($normalizedRow['sub_brand']) && $normalizedRow['sub_brand'] !== null && $normalizedRow['sub_brand'] !== '') {
                                $subBrandNameValue = trim((string)$normalizedRow['sub_brand']);
                                $subBrand = VehicleModel::where('name', $subBrandNameValue)->first();
                                if ($subBrand) {
                                    $productUpdate['sub_brand_id'] = $subBrand->id;
                                } else {
                                    $this->errors[] = "Row " . ($rowIndex + 2) . ": Sub Brand '{$subBrandNameValue}' not found";
                                }
                            }

                            if (!empty($productUpdate)) {
                                $product->update($productUpdate);
                            }
                        }
                    } catch (\Exception $e) {
                        // Log but don't stop the import
                        Log::warning('SubProductUpdateImport: Failed to update brand/sub-brand', [
                            'row' => $rowIndex + 2,
                            'sub_product_id' => $subProduct->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        $this->errors[] = "Row " . ($rowIndex + 2) . ": Failed to update Brand/Sub Brand - " . $e->getMessage();
                    }

                    // Update sub-product if there's data to update
                    if (!empty($updateData)) {
                        $subProduct->update($updateData);
                    }

                    // Update custom fields (each custom field is a separate column)
                    foreach ($normalizedRow as $columnName => $value) {
                        // Skip standard columns
                        if (in_array($columnName, ['id', 'sub_product_id', 'subproduct_id', 'product_no', 'chassis_no', 'sale_price', 'purchase_price', 'purchase_f_quantity', 'quantity', 'warehouse_id', 'warehouse', 'location', 'custom_fields', 'brand', 'brand_id', 'sub_brand', 'sub_brand_id', 'model', 'model_id'])) {
                            continue;
                        }

                        // Check if this column matches a custom field
                        if (isset($this->customFields[$columnName])) {
                            $customField = $this->customFields[$columnName];
                            
                            // Update or create custom field value
                            CustomFieldValue::updateOrCreate(
                                [
                                    'record_id' => $subProduct->id,
                                    'field_id' => $customField->id,
                                ],
                                [
                                    'value' => $value !== null ? (string)$value : '',
                                ]
                            );
                        }
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $this->errorCount++;
                    Log::error('SubProduct update import error', [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                }
                
                // Commit this batch
                DB::commit();
                
                // Reconnect after each batch to prevent connection timeout
                $this->reconnectDatabase();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('SubProduct update import batch failed', [
                    'batch' => $batchIndex + 1,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with next batch instead of failing completely
                $this->errorCount += count($batch);
            }
        }
        
        // Clear memory after processing chunk
        unset($rowsArray, $batches, $batch);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getErrorCount()
    {
        return $this->errorCount;
    }
}

