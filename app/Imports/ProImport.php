<?php

namespace App\Imports;

use App\Models\Pro;
use App\Models\ProItem;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\SubProduct;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProImport implements ToArray
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Check if part number exists in company sub-products (robust match).
     * Prefer import_source=item_master (Item Master import), but allow fallback to any source.
     */
    private function hasPartNoInSubProducts(string $partNo): bool
    {
        $partNo = trim($partNo);
        if ($partNo === '') {
            return false;
        }

        $preferred = SubProduct::where('created_by', $this->userId)
            ->whereRaw('TRIM(product_no) = ?', [$partNo])
            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
            ->exists();

        if ($preferred) {
            return true;
        }

        return SubProduct::where('created_by', $this->userId)
            ->whereRaw('TRIM(product_no) = ?', [$partNo])
            ->exists();
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

        return null;
    }

    /**
     * Find supplier by name/code - throws exception if not found
     */
    private function findOrCreateSupplier($supplierName)
    {
        if (empty($supplierName)) {
            return null;
        }

        $trimmedName = trim($supplierName);

        // Try to find by name first (exact match preferred)
        $supplier = Vender::where('created_by', $this->userId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmedName)])
            ->first();

        // If no exact match, try case-insensitive exact match
        if (!$supplier) {
            $supplier = Vender::where('created_by', $this->userId)
                ->where('name', $trimmedName)
                ->first();
        }

        // If still not found, try partial match (case-insensitive)
        if (!$supplier) {
            $supplier = Vender::where('created_by', $this->userId)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($trimmedName) . '%'])
                ->first();
        }

        // If not found, throw exception instead of creating new vendor
        if (!$supplier) {
            throw new \Exception("Vendor '{$trimmedName}' does not exist in the system. Please create the vendor first before importing PRO.");
        }

        return $supplier;
    }

    /**
     * Generate next vendor number
     */
    private function venderNumber()
    {
        $latest = Vender::where('created_by', '=', $this->userId)->latest()->first();
        if (!$latest) {
            return 1;
        }
        return $latest->vender_id + 1;
    }

    /**
     * Generate next supplier code
     */
    private function supplierCode()
    {
        $latest = Vender::where('created_by', '=', $this->userId)
            ->whereNotNull('supplier_code')
            ->latest()
            ->first();
        
        if (!$latest || !$latest->supplier_code) {
            return 'SUP001';
        }

        // Extract number from code (e.g., SUP001 -> 1)
        preg_match('/SUP(\d+)/', $latest->supplier_code, $matches);
        $number = isset($matches[1]) ? (int)$matches[1] : 0;
        $nextNumber = $number + 1;
        
        return 'SUP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Find product by description/name or part_no (SKU)
     */
    private function findProduct($description, $partNo = null)
    {
        if (empty($description) && empty($partNo)) {
            return null;
        }

        $product = null;

        // First try to find by description (product name) - exact match preferred
        if (!empty($description)) {
            // Try exact match first
            $product = ProductService::where('created_by', $this->userId)
                ->where('name', $description)
                ->first();

            // If no exact match, try partial match
            if (!$product) {
                $product = ProductService::where('created_by', $this->userId)
                    ->where('name', 'like', '%' . $description . '%')
                    ->first();
            }
        }

        // If still not found and part_no is provided, try to match by SKU
        if (!$product && !empty($partNo)) {
            $product = ProductService::where('created_by', $this->userId)
                ->where('sku', $partNo)
                ->first();

            // If no exact SKU match, try partial match
            if (!$product) {
                $product = ProductService::where('created_by', $this->userId)
                    ->where('sku', 'like', '%' . $partNo . '%')
                    ->first();
            }
        }

        return $product;
    }

    public function array(array $data)
    {
        try {
            DB::beginTransaction();

            // Based on the Excel structure:
            // Row 1: Title "Purchase Order" (row 0)
            // Row 2: SUPPLIER NAME | PRO NO (row 1)
            // Row 3: SUPPLIER PROFORMA NO | PO DATE (row 2)
            // Row 4: SUPPLIER PROFORMA DATE (row 3)
            // Row 5: OUR ORDER REF (row 4)
            // Row 6: SUPPLIER REF (row 5)
            // Row 7: ETA DATE (row 6)
            // Row 8: CURRENCY ID (row 7)
            // Row 9: EXCHANGE RATE (row 8)
            // Row 10: Empty row (row 9)
            // Row 11: Column headers (row 10)
            // Row 12+: Data rows (rows 11+)

            // Skip empty rows at the beginning
            $startRow = 0;
            for ($i = 0; $i < count($data); $i++) {
                if (!empty(array_filter($data[$i]))) {
                    $startRow = $i;
                    break;
                }
            }

            // Find header row (should contain "PART NO" or similar)
            $headerRowIndex = null;
            for ($i = $startRow; $i < min($startRow + 15, count($data)); $i++) {
                $row = array_map('strtoupper', array_map('trim', $data[$i]));
                if (in_array('PART NO', $row) || in_array('PART_NO', $row) || in_array('PARTNO', $row)) {
                    $headerRowIndex = $i;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('Could not find header row with "PART NO" column.');
            }

            $headerRow = $data[$headerRowIndex];
            $itemRows = array_slice($data, $headerRowIndex + 1);

            // Extract PRO header information from rows before header
            $proHeader = $this->extractProHeader($data, $headerRowIndex);

            // Get next unique PRO number
            $proNumber = $this->getNextUniqueProNumber();

            // Find supplier - will throw exception if not found
            $supplier = null;
            $supplierId = null;
            $supplierName = null;

            if (!empty($proHeader['supplier_name'])) {
                $supplier = $this->findOrCreateSupplier($proHeader['supplier_name']);
                if ($supplier) {
                    $supplierId = $supplier->id;
                    $supplierName = $supplier->name;
                }
            } else {
                // Supplier name is required for import
                throw new \Exception('Supplier name is required in the import file. Please ensure "SUPPLIER NAME" is specified.');
            }

            // Parse PO date
            $poDate = !empty($proHeader['po_date']) ? $this->parseDate($proHeader['po_date']) : now()->format('Y-m-d');
            if (!$poDate) {
                $poDate = now()->format('Y-m-d');
            }

            // Create PRO
            $pro = new Pro();
            $pro->pro_no = (string)$proNumber; // Ensure pro_no is stored as string to match database schema
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
            $pro->status = 'open'; // Default status for imported PROs
            $pro->created_by = $this->userId;
            $pro->save();

            \Log::info('PRO created', ['pro_id' => $pro->id, 'pro_number' => $proNumber]);

            // Process items
            $columnMap = $this->mapColumns($headerRow);
            $itemsCreated = 0;
            
            // First pass: Validate all part numbers exist in sub_products before creating items
            $missingPartNumbers = [];
            $rowsWithoutPartNo = [];
            
            foreach ($itemRows as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $partNo = trim($this->getValue($row, $columnMap, 'part_no'));
                $description = trim($this->getValue($row, $columnMap, 'description'));
                
                // Part number is required for all items
                if (empty($partNo)) {
                    $rowsWithoutPartNo[] = 'Row ' . ($rowIndex + 1) . ($description ? " (Description: {$description})" : '');
                    continue;
                }
                
                // Check if part number exists in imported sub_products as product_no
                $subProductExists = $this->hasPartNoInSubProducts($partNo);
                
                if (!$subProductExists) {
                    $missingPartNumbers[] = $partNo;
                }
            }
            
            // If any rows are missing part numbers, rollback and throw exception
            if (!empty($rowsWithoutPartNo)) {
                DB::rollBack();
                $rowsList = implode(', ', $rowsWithoutPartNo);
                throw new \Exception('Import failed: Part number is required for all items. The following rows are missing part numbers: ' . $rowsList);
            }
            
            // If any part numbers are missing, rollback and throw exception
            if (!empty($missingPartNumbers)) {
                DB::rollBack();
                $missingList = implode(', ', array_unique($missingPartNumbers));
                throw new \Exception('Import failed: The following part numbers do not exist in company sub products (prefer import_source=item_master): ' . $missingList . '. Please ensure all part numbers exist as product_no in sub products before importing.');
            }

            foreach ($itemRows as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $partNo = $this->getValue($row, $columnMap, 'part_no');
                    $description = $this->getValue($row, $columnMap, 'description');
                    $orderQty = $this->getValue($row, $columnMap, 'order_qty');
                    $suppliedQty = $this->getValue($row, $columnMap, 'supplied_qty');
                    $unitPrice = $this->getValue($row, $columnMap, 'unit_price');

                    // Require part number for import
                    if (empty($partNo)) {
                        throw new \Exception("Part number is required for all items. Row with description '{$description}' is missing part number.");
                    }
                    
                    // Validate part number exists in imported sub_products (double check)
                    $partNoTrimmed = trim($partNo);
                    $subProductExists = $this->hasPartNoInSubProducts($partNoTrimmed);
                    
                    if (!$subProductExists) {
                        throw new \Exception("Part number '{$partNoTrimmed}' does not exist in company sub products.");
                    }

                    // Default values
                    $orderQty = $this->parseNumeric($orderQty, 0);
                    $suppliedQty = $this->parseNumeric($suppliedQty, 0);
                    $unitPrice = $this->parseNumeric($unitPrice, 0);

                    // Calculate remaining qty and total amount
                    $remainingQty = $orderQty - $suppliedQty;
                    $totalAmount = $orderQty * $unitPrice;

                    // Auto-fill description from imported stock if empty
                    if (empty(trim($description ?? '')) && !empty($partNo)) {
                        $subProductForDesc = SubProduct::where('created_by', $this->userId)
                            ->where('chassis_no', trim($partNo))
                            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
                            ->whereHas('productService', function ($q) use ($partNo) {
                                $q->where('created_by', $this->userId)->where('sku', trim($partNo));
                            })
                            ->with('productService')
                            ->latest()
                            ->first();

                        if (!$subProductForDesc) {
                            $subProductForDesc = SubProduct::where('created_by', $this->userId)
                                ->whereRaw('TRIM(product_no) = ?', [trim($partNo)])
                                ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
                                ->with('productService')
                                ->latest()
                                ->first();
                        }

                        if (!$subProductForDesc) {
                            $subProductForDesc = SubProduct::where('created_by', $this->userId)
                                ->whereRaw('TRIM(product_no) = ?', [trim($partNo)])
                                ->with('productService')
                                ->latest()
                                ->first();
                        }
                        
                        if ($subProductForDesc && $subProductForDesc->productService) {
                            $description = $subProductForDesc->productService->name;
                        }
                    }

                    // Find imported sub_product by part_no (product_no) and derive parent product from it
                    $subProduct = null;
                    $subProductId = null;
                    $productId = null;
                    if (!empty($partNo)) {
                        $partNoTrimmed = trim($partNo);

                        // Prefer the row where parent product SKU matches the same part number
                        $subProduct = SubProduct::where('created_by', $this->userId)
                            ->where('chassis_no', $partNoTrimmed)
                            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
                            ->whereHas('productService', function ($q) use ($partNoTrimmed) {
                                $q->where('created_by', $this->userId)->where('sku', $partNoTrimmed);
                            })
                            ->with('productService')
                            ->first();

                        if (!$subProduct) {
                            $subProduct = SubProduct::where('created_by', $this->userId)
                                ->whereRaw('TRIM(product_no) = ?', [$partNoTrimmed])
                                ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
                                ->with('productService')
                                ->latest()
                                ->first();
                        }

                        if (!$subProduct) {
                            $subProduct = SubProduct::where('created_by', $this->userId)
                                ->whereRaw('TRIM(product_no) = ?', [$partNoTrimmed])
                                ->with('productService')
                                ->latest()
                                ->first();
                        }
                        
                        if ($subProduct) {
                            $subProductId = $subProduct->id;
                            $productId = $subProduct->product_id ?: null;
                        }
                    }

                    // Create PRO Item
                    $item = new ProItem();
                    $item->pro_id = $pro->id;
                    $item->product_id = $productId;
                    $item->part_no = $partNo;
                    $item->description = $description;
                    $item->order_qty = $orderQty;
                    $item->supplied_qty = $suppliedQty;
                    $item->remaining_qty = $remainingQty;
                    $item->unit_price = $unitPrice;
                    $item->total_amount = $totalAmount;
                    $item->save();

                    // Log if product was found or not
                    if ($productId) {
                        \Log::info('PRO item linked to product', [
                            'item_id' => $item->id,
                            'product_id' => $productId,
                            'product_name' => $subProduct?->productService->name,
                            'description' => $description,
                            'part_no' => $partNo,
                            'sub_product_id' => $subProductId,
                            'matched_import_source' => $subProduct->import_source ?? null,
                        ]);
                    } else {
                        \Log::warning('PRO item product not found', [
                            'item_id' => $item->id,
                            'description' => $description,
                            'part_no' => $partNo,
                            'sub_product_id' => $subProductId,
                        ]);
                    }

                    $itemsCreated++;

                } catch (\Exception $e) {
                    \Log::error('Error processing PRO item row', [
                        'error' => $e->getMessage(),
                        'row_index' => $rowIndex,
                        'row_data' => $row,
                        'user_id' => $this->userId
                    ]);
                    // Continue with next row instead of failing entire import
                    continue;
                }
            }

            if ($itemsCreated == 0) {
                throw new \Exception('No valid items found in the file.');
            }

            DB::commit();
            
            \Log::info('PRO import completed successfully', [
                'pro_id' => $pro->id,
                'pro_number' => $proNumber,
                'items_created' => $itemsCreated,
                'user_id' => $this->userId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PRO import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId
            ]);
            throw $e;
        }
    }

    /**
     * Extract PRO header information from rows before item table
     */
    private function extractProHeader($data, $headerRowIndex)
    {
        $header = [];

        // Look through rows before the header row
        for ($i = 0; $i < $headerRowIndex; $i++) {
            $row = $data[$i];
            
            // Check each cell for header keywords
            for ($col = 0; $col < count($row); $col++) {
                $cell = strtoupper(trim($row[$col] ?? ''));
                $nextCell = isset($row[$col + 1]) ? trim($row[$col + 1]) : '';

                // Left column headers (row labels)
                if ($cell == 'SUPPLIER NAME' || $cell == 'SUPPLIER_NAME' || $cell == 'SUPPLIERNAME') {
                    $header['supplier_name'] = $nextCell;
                } elseif ($cell == 'SUPPLIER PROFORMA NO' || $cell == 'SUPPLIER_PROFORMA_NO' || $cell == 'SUPPLIERPROFORMANO') {
                    $header['supplier_proforma_no'] = $nextCell;
                } elseif ($cell == 'SUPPLIER PROFORMA DATE' || $cell == 'SUPPLIER_PROFORMA_DATE' || $cell == 'SUPPLIERPROFORMADATE') {
                    $header['supplier_proforma_date'] = $nextCell;
                } elseif ($cell == 'OUR ORDER REF' || $cell == 'OUR_ORDER_REF' || $cell == 'OUROrderREF') {
                    $header['our_order_ref'] = $nextCell;
                } elseif ($cell == 'SUPPLIER REF' || $cell == 'SUPPLIER_REF' || $cell == 'SUPPLIERREF') {
                    $header['supplier_ref'] = $nextCell;
                } elseif ($cell == 'ETA DATE' || $cell == 'ETA_DATE' || $cell == 'ETADATE' || $cell == 'ETA') {
                    $header['eta_date'] = $nextCell;
                } elseif ($cell == 'CURRENCY ID' || $cell == 'CURRENCY_ID' || $cell == 'CURRENCYID') {
                    $header['currency_id'] = $nextCell;
                } elseif ($cell == 'EXCHANGE RATE' || $cell == 'EXCHANGE_RATE' || $cell == 'EXCHANGERATE') {
                    $header['exchange_rate'] = $nextCell;
                }
                
                // Right column headers
                if ($cell == 'PRO NO' || $cell == 'PRO_NO' || $cell == 'PRONO') {
                    // Skip - we'll generate this
                } elseif ($cell == 'PO DATE' || $cell == 'PO_DATE' || $cell == 'PODATE') {
                    $header['po_date'] = $nextCell;
                }
            }
        }

        return $header;
    }

    /**
     * Map column headers to field names
     */
    private function mapColumns($headerRow)
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $header = strtoupper(trim($header));
            if (in_array($header, ['PART NO', 'PART_NO', 'PARTNO'])) {
                $map['part_no'] = $index;
            } elseif (in_array($header, ['DESCRIPTION', 'DESC'])) {
                $map['description'] = $index;
            } elseif (in_array($header, ['ORDER QTY', 'ORDER_QTY', 'ORDERQTY', 'ORDER QUANTITY'])) {
                $map['order_qty'] = $index;
            } elseif (in_array($header, ['SUPPLIED QTY', 'SUPPLIED_QTY', 'SUPPLIEDQTY', 'SUPPLIED QUANTITY'])) {
                $map['supplied_qty'] = $index;
            } elseif (in_array($header, ['REMAINING QTY', 'REMAINING_QTY', 'REMAININGQTY', 'REMAINING QUANTITY'])) {
                // Skip - will calculate
            } elseif (in_array($header, ['UNIT PRICE', 'UNIT_PRICE', 'UNITPRICE', 'PRICE'])) {
                $map['unit_price'] = $index;
            } elseif (in_array($header, ['TOTAL AMOUNT', 'TOTAL_AMOUNT', 'TOTALAMOUNT', 'TOTAL'])) {
                // Skip - will calculate
            }
        }
        return $map;
    }

    /**
     * Get value from row using column map
     */
    private function getValue($row, $columnMap, $field)
    {
        if (isset($columnMap[$field]) && isset($row[$columnMap[$field]])) {
            return trim($row[$columnMap[$field]]);
        }
        return '';
    }

    /**
     * Get next unique PRO number by checking existing numbers
     * Note: pro_no must be unique per creator/company (created_by + pro_no)
     */
    private function getNextUniqueProNumber()
    {
        // Get the highest PRO number for this creator/company
        $lastPro = Pro::withTrashed()
            ->where('created_by', $this->userId)
            ->orderByRaw('CAST(pro_no AS UNSIGNED) DESC')
            ->first();
        
        // Start from the last number + 1, or 1 if no PROs exist
        $startNumber = $lastPro && is_numeric($lastPro->pro_no) ? ((int)$lastPro->pro_no + 1) : 1;
        
        // Find the next available number within this creator/company scope
        $proNumber = $startNumber;
        $maxAttempts = 1000; // Safety limit to prevent infinite loop
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            // Check if this number already exists for this creator/company (including soft-deleted)
            // Since pro_no is stored as string, check both string and numeric formats
            $exists = Pro::withTrashed()
                ->where('created_by', $this->userId)
                ->where(function($query) use ($proNumber) {
                    $query->where('pro_no', (string)$proNumber)
                          ->orWhere('pro_no', $proNumber);
                })
                ->exists();
            
            if (!$exists) {
                // Double-check right before returning to avoid race conditions
                // Use a fresh query to ensure we have the latest data
                $doubleCheck = Pro::withTrashed()
                    ->where('created_by', $this->userId)
                    ->where(function($query) use ($proNumber) {
                        $query->where('pro_no', (string)$proNumber)
                              ->orWhere('pro_no', $proNumber);
                    })
                    ->exists();
                
                if (!$doubleCheck) {
                    return $proNumber;
                }
            }
            
            // If it exists, try the next number
            $proNumber++;
            $attempts++;
        }
        
        // Fallback: if we somehow can't find a unique number, use timestamp
        \Log::warning('Could not find unique PRO number after ' . $maxAttempts . ' attempts for creator ' . $this->userId . ', using timestamp');
        return (int)(time() % 1000000); // Use timestamp modulo as fallback
    }

    /**
     * Parse numeric value, handling Excel formatting
     */
    private function parseNumeric($value, $default = 0)
    {
        if (empty($value)) {
            return $default;
        }

        // Remove common formatting
        $value = str_replace([',', '$', ' '], '', $value);
        
        // Handle if it's already numeric
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $default;
    }
}
