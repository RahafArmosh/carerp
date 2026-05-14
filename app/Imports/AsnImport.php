<?php

namespace App\Imports;

use App\Exceptions\AsnImportValidationException;
use App\Models\Asn;
use App\Models\AsnItem;
use App\Models\Vender;
use App\Models\Pro;
use App\Models\ProItem;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AsnImport implements ToArray
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

        return null;
    }

    /**
     * Find or create supplier by name/code
     */
    private function findOrCreateSupplier($supplierName, $supplierCode = null)
    {
        if (empty($supplierName)) {
            return null;
        }

        // Try to find by name first (exact match preferred)
        $supplier = Vender::where('created_by', $this->userId)
            ->where('name', trim($supplierName))
            ->first();

        // If no exact match, try partial match
        if (!$supplier) {
            $supplier = Vender::where('created_by', $this->userId)
                ->where('name', 'like', '%' . trim($supplierName) . '%')
                ->first();
        }

        // If not found and code is provided, try by code
        if (!$supplier && !empty($supplierCode)) {
            $supplier = Vender::where('created_by', $this->userId)
                ->where('contact', 'like', '%' . trim($supplierCode) . '%')
                ->first();
        }

        // If still not found, create new vendor
        if (!$supplier) {
            $supplier = new Vender();
            $supplier->vender_id = $this->venderNumber();
            $supplier->supplier_code = $this->supplierCode();
            $supplier->name = trim($supplierName);
            $supplier->contact = !empty($supplierCode) ? trim($supplierCode) : '';
            $supplier->email = '';
            
            // Find Account Payable chart account
            $accountPayable = ChartOfAccount::where('created_by', $this->userId)
                ->where('name', 'Account Payable')
                ->first();
            
            if ($accountPayable) {
                $supplier->chart_account_id = $accountPayable->id;
            }
            
            $supplier->created_by = $this->userId;
            $supplier->save();
            
            \Log::info('Created new vendor during ASN import', [
                'vendor_id' => $supplier->id,
                'vendor_name' => $supplier->name,
                'supplier_code' => $supplier->supplier_code
            ]);
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
     * Find PRO by PRO number string
     */
    private function findProByNumber($proNumberString)
    {
        if (empty($proNumberString)) {
            return null;
        }

        // Extract numeric part from PRO number (e.g., "PO12345" or "#PRO00001")
        $numericPart = preg_replace('/[^0-9]/', '', $proNumberString);
        
        if (empty($numericPart)) {
            return null;
        }

        // Try to find by pro_no (stored as numeric) and load items
        $pro = Pro::where('created_by', $this->userId)
            ->where('pro_no', $numericPart)
            ->with('items')
            ->first();

        return $pro;
    }

    /**
     * Validate ASN item against PRO
     * Only checks part number and price - ignores description and other fields
     * Returns array with 'valid' boolean and 'errors' array
     */
    private function validateAsnItemAgainstPro($supplierPoNo, $ourProNo, $orderRef, $partNo, $unitPrice, $receivedQty)
    {
        $errors = [];

        // 1. Find PRO by PRO number
        $pro = $this->findProByNumber($ourProNo);
        if (!$pro) {
            $errors[] = "Our PRO Number '{$ourProNo}' not found in system";
            return ['valid' => false, 'errors' => $errors, 'pro' => null, 'proItem' => null];
        }

        // 2. Find matching PRO item by part_no only (ignore description)
        $proItem = null;
        if (!empty($partNo)) {
            $partNoTrimmed = trim($partNo);
            
            // Try exact match first
            $proItem = $pro->items()->where('part_no', '=', $partNoTrimmed)->first();
            
            // If no exact match, try case-insensitive match
            if (!$proItem) {
                $proItem = $pro->items()->whereRaw('LOWER(part_no) = ?', [strtolower($partNoTrimmed)])->first();
            }
            
            // If still no match, try partial match as last resort
            if (!$proItem) {
                $proItem = $pro->items()->where('part_no', 'like', '%' . $partNoTrimmed . '%')->first();
            }

            if (!$proItem) {
                $errors[] = "Part Number '{$partNo}' not found in PRO items";
            }
        } else {
            $errors[] = "Part Number is required";
        }

        // 3. Validate Unit Price matches PRO item (only price check)
        if ($proItem) {
            $unitPriceMatch = abs((float)$unitPrice - (float)$proItem->unit_price) < 0.01; // Allow small floating point differences
            if (!$unitPriceMatch) {
                $errors[] = "Unit Price '{$unitPrice}' does not match PRO item's Unit Price '{$proItem->unit_price}'";
            }

            // 4. Validate quantities against PRO *remaining_qty* (not order_qty)
            // Import uses receivedQty only for validation (received_qty is not persisted during import).
            $receivedQty = (float) $receivedQty;
            if ($receivedQty > 0) {
                $remainingQty = (float) ($proItem->remaining_qty ?? 0);
                if ($remainingQty <= 0) {
                    // Fallback if remaining_qty wasn't calculated/persisted as expected.
                    $remainingQty = (float)($proItem->order_qty ?? 0) - (float)($proItem->supplied_qty ?? 0);
                }

                if ($receivedQty - $remainingQty > 0.0001) {
                    $errors[] = "Received Qty '{$receivedQty}' exceeds PRO remaining qty '{$remainingQty}' for part '{$partNo}'.";
                }
            }
        }

        $isValid = empty($errors);

        return [
            'valid' => $isValid,
            'errors' => $errors,
            'pro' => $pro,
            'proItem' => $proItem
        ];
    }

    public function array(array $data)
    {
        try {
            DB::beginTransaction();

            // Based on the image structure:
            // Row 1: Title "Advanced Shipping Notice" (row 0)
            // Row 2-7: Header fields (rows 1-6)
            // Row 9-10: Column headers (row 8-9)
            // Row 11+: Data rows (rows 10+)

            // Skip empty rows at the beginning
            $startRow = 0;
            for ($i = 0; $i < count($data); $i++) {
                if (!empty(array_filter($data[$i]))) {
                    $startRow = $i;
                    break;
                }
            }

            // Find header row (should contain "BOX NO" or "PART NO" or similar)
            $headerRowIndex = null;
            for ($i = $startRow; $i < min($startRow + 15, count($data)); $i++) {
                $row = array_map('strtoupper', array_map('trim', $data[$i]));
                if (in_array('BOX NO', $row) || in_array('BOX_NO', $row) || in_array('BOXNO', $row) ||
                    in_array('PART NO', $row) || in_array('PART_NO', $row) || in_array('PARTNO', $row)) {
                    $headerRowIndex = $i;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('Could not find header row with "BOX NO" or "PART NO" column.');
            }

            $headerRow = $data[$headerRowIndex];
            $itemRows = array_slice($data, $headerRowIndex + 1);

            // Extract ASN header information from rows before header
            $asnHeader = $this->extractAsnHeader($data, $headerRowIndex);

            // Get next unique ASN number (unique per creator)
            $asnNumber = $this->getNextUniqueAsnNumber();

            // Find or get supplier
            $supplier = null;
            $supplierId = null;
            $supplierName = null;
            $supplierCode = null;

            if (!empty($asnHeader['supplier_name'])) {
            $supplier = $this->findOrCreateSupplier($asnHeader['supplier_name'] ?? null, $asnHeader['supplier_code'] ?? null);
                if ($supplier) {
                    $supplierId = $supplier->id;
                    $supplierName = $supplier->name;
                $supplierCode = $supplier->contact ?? ($asnHeader['supplier_code'] ?? null);
                } else {
                    $supplierName = $asnHeader['supplier_name'];
                $supplierCode = $asnHeader['supplier_code'] ?? null;
                }
            }

            // Parse ASN date
            $asnDate = !empty($asnHeader['asn_date']) ? $this->parseDate($asnHeader['asn_date']) : now()->format('Y-m-d');
            if (!$asnDate) {
                $asnDate = now()->format('Y-m-d');
            }

            // Find warehouse by name or ID - REQUIRED
            $warehouseId = null;
            if (!empty($asnHeader['warehouse'])) {
                $warehouseValue = trim($asnHeader['warehouse']);
                
                // Try to find by ID first (if numeric)
                if (is_numeric($warehouseValue)) {
                    $warehouse = \App\Models\warehouse::where('id', $warehouseValue)
                        ->where('created_by', $this->userId)
                        ->first();
                    if ($warehouse) {
                        $warehouseId = $warehouse->id;
                    }
                }
                
                // If not found by ID, try by name
                if (!$warehouseId) {
                    $warehouse = \App\Models\warehouse::where('created_by', $this->userId)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($warehouseValue)])
                        ->first();
                    if ($warehouse) {
                        $warehouseId = $warehouse->id;
                    }
                }
            }

            // Warehouse is mandatory for ASN import
            if (!$warehouseId) {
                DB::rollBack();
                throw new \Exception('ASN Warehouse is required in the header and must match an existing warehouse (by ID or exact name).');
            }

            // STEP 1: Validate ALL items FIRST before creating ASN
            $columnMap = $this->mapColumns($headerRow);
            $validationErrors = [];
            $validatedItems = [];
            $actualHeaderRowIndex = $headerRowIndex;

            foreach ($itemRows as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $partNo = '';
                $description = '';
                try {
                    $boxNo = $this->getValue($row, $columnMap, 'box_no');
                    $supplierPoNo = $this->getValue($row, $columnMap, 'supplier_po_no');
                    $ourProNo = $this->getValue($row, $columnMap, 'our_pro_no');
                    $orderRef = $this->getValue($row, $columnMap, 'order_ref');
                    $partNo = $this->getValue($row, $columnMap, 'part_no');
                    $description = trim($this->getValue($row, $columnMap, 'description'));
                    
                    // Auto-fill description from stock if empty
                    if (empty($description) && !empty($partNo)) {
                        $subProduct = \App\Models\SubProduct::where('created_by', $this->userId)
                            ->where('chassis_no', trim($partNo))
                            ->with('productService')
                            ->latest()
                            ->first();
                        
                        if ($subProduct && $subProduct->productService) {
                            $description = $subProduct->productService->name;
                        }
                    }
                    
                    $qty = $this->getValue($row, $columnMap, 'qty');
                    $receivedQty = $this->getValue($row, $columnMap, 'received_qty');
                    $unitPrice = $this->getValue($row, $columnMap, 'unit_price');
                    $unitWeight = $this->getValue($row, $columnMap, 'unit_weight');
                    $hsCode = $this->getValue($row, $columnMap, 'hs_code');
                    $containerNo = $this->getValue($row, $columnMap, 'container_no');
                    $decNo = $this->getValue($row, $columnMap, 'dec_no');
                    $decDate = $this->getValue($row, $columnMap, 'dec_date');
                    $origin = $this->getValue($row, $columnMap, 'origin');

                    // Skip if part number is missing (description is ignored)
                    if (empty($partNo)) {
                        continue;
                    }

                    // Default values
                    $qty = $this->parseNumeric($qty, 0);
                    $receivedQty = $this->parseNumeric($receivedQty, $qty);
                    $unitPrice = $this->parseNumeric($unitPrice, 0);
                    $unitWeight = $this->parseNumeric($unitWeight, 0);

                    // Validate against PRO - REQUIRED for all items
                    $validationResult = $this->validateAsnItemAgainstPro(
                        $supplierPoNo,
                        $ourProNo,
                        $orderRef,
                        $partNo,
                        $unitPrice,
                        $receivedQty
                    );

                    if (!$validationResult['valid']) {
                        // Collect validation errors - FAIL FAST
                        $rowNumber = $actualHeaderRowIndex + 2 + $rowIndex;
                        $validationErrors[] = [
                            'row' => $rowNumber,
                            'part_no' => $partNo ?: 'N/A',
                            'description' => $description ?: 'N/A',
                            'errors' => $validationResult['errors'],
                            'data' => [
                                'supplier_po_no' => $supplierPoNo,
                                'our_pro_no' => $ourProNo,
                                'order_ref' => $orderRef,
                                'part_no' => $partNo,
                                'unit_price' => $unitPrice,
                                'received_qty' => $receivedQty
                            ]
                        ];
                    } else {
                        // Store validated item data for later processing
                        $validatedItems[] = [
                            'row_data' => $row,
                            'row_index' => $rowIndex,
                            'validation_result' => $validationResult,
                            'box_no' => $boxNo,
                            'supplier_po_no' => $supplierPoNo,
                            'our_pro_no' => $ourProNo,
                            'order_ref' => $orderRef,
                            'part_no' => $partNo,
                            'description' => $description,
                            'qty' => $qty,
                            'received_qty' => $receivedQty,
                            'unit_price' => $unitPrice,
                            'unit_weight' => $unitWeight,
                            'hs_code' => $hsCode,
                            'container_no' => $containerNo,
                            'dec_no' => $decNo,
                            'dec_date' => $decDate,
                            'origin' => $origin
                        ];
                    }

                } catch (\Exception $e) {
                    // Any exception during validation is a critical error
                    $rowNumber = $actualHeaderRowIndex + 2 + $rowIndex;
                    $validationErrors[] = [
                        'row' => $rowNumber,
                        'part_no' => $partNo ?: 'N/A',
                        'description' => $description ?: 'N/A',
                        'errors' => ['Error processing row: ' . $e->getMessage()],
                        'data' => []
                    ];
                }
            }

            // STEP 2: If ANY validation errors exist, rollback and throw with errors for Excel export
            if (!empty($validationErrors)) {
                DB::rollBack();
                $errorMessage = $this->formatValidationErrors($validationErrors);
                throw new AsnImportValidationException($errorMessage, $validationErrors);
            }

            // STEP 3: If no items found, fail
            if (empty($validatedItems)) {
                DB::rollBack();
                throw new \Exception('No valid items found in the file. Please ensure at least one row contains valid data.');
            }

            // STEP 4: All validations passed - Now create ASN
            $asn = new Asn();
            $asn->asn_no = (string)$asnNumber; // Ensure asn_no is stored as string to match database schema
            $asn->supplier_id = $supplierId;
            $asn->supplier_name = $supplierName;
            $asn->supplier_code = $supplierCode;
            $asn->supplier_inv_no = $asnHeader['supplier_inv_no'] ?? null;
            $asn->container_no = $asnHeader['container_no'] ?? null;
            $asn->dec_no = $asnHeader['dec_no'] ?? null;
            $asn->boe_number = $asnHeader['boe_number'] ?? null;
            $asn->dec_date = !empty($asnHeader['dec_date']) ? $this->parseDate($asnHeader['dec_date']) : null;
            $asn->asn_date = $asnDate;
            $asn->warehouse_id = $warehouseId;
            $currencyId = !empty($asnHeader['currency_id']) ? trim($asnHeader['currency_id']) : null;
            $asn->currency_id = !empty($currencyId) && is_numeric($currencyId) ? (int)$currencyId : null;
            $asn->exchange_rate = !empty($asnHeader['exchange_rate']) ? $this->parseNumeric($asnHeader['exchange_rate'], 1.0) : 1.0;
            $asn->status = 'created'; // Set status to 'open' when first importing
            $asn->created_by = $this->userId;
            $asn->save();

            // STEP 5: Create items
            \Log::info('ASN created', ['asn_id' => $asn->id, 'asn_number' => $asnNumber]);

            $itemsCreated = 0;
            $user = \App\Models\User::find($this->userId);

            foreach ($validatedItems as $itemData) {
                $validationResult = $itemData['validation_result'];
                $pro = $validationResult['pro'];
                $proItem = $validationResult['proItem'];
                $ourProId = $pro ? $pro->id : null;
                $ourProNoFormatted = $pro ? ($user ? $user->proNumberFormat($pro->pro_no) : $itemData['our_pro_no']) : $itemData['our_pro_no'];
                
                // Don't update PRO item's supplied_qty during import since we're not saving received_qty
                // if ($proItem) {
                //     $newSuppliedQty = $proItem->supplied_qty + $itemData['received_qty'];
                //     $proItem->supplied_qty = $newSuppliedQty;
                //     $proItem->save();
                // }

                // Calculate values
                // Don't save received_qty during import - set to 0
                $receivedQtyForItem = 0;
                $discrepancy = $receivedQtyForItem - $itemData['qty'];
                $totalPrice = $itemData['qty'] * $itemData['unit_price'];
                $totalWeight = $itemData['qty'] * $itemData['unit_weight'];
                $decDateParsed = !empty($itemData['dec_date']) ? $this->parseDate($itemData['dec_date']) : null;

                // Create ASN Item
                $item = new AsnItem();
                $item->asn_id = $asn->id;
                $item->box_no = $itemData['box_no'];
                $item->supplier_po_no = $itemData['supplier_po_no'];
                $item->our_pro_id = $ourProId;
                $item->our_pro_no = $ourProNoFormatted;
                $item->order_ref = $itemData['order_ref'];
                $item->part_no = $itemData['part_no'];
                $item->description = $itemData['description'];
                $item->qty = $itemData['qty'];
                $item->received_qty = $receivedQtyForItem; // Set to 0 - don't save received_qty during import
                $item->discrepancy = $discrepancy;
                $item->unit_price = $itemData['unit_price'];
                $item->total_price = $totalPrice;
                $item->unit_weight = $itemData['unit_weight'];
                $item->total_weight = $totalWeight;
                $item->hs_code = $itemData['hs_code'];
                $item->container_no = $itemData['container_no'];
                $item->dec_no = $itemData['dec_no'];
                $item->dec_date = $decDateParsed;
                $item->origin = $itemData['origin'];
                $item->save();

                $itemsCreated++;

                \Log::info('ASN item created', [
                    'item_id' => $item->id,
                    'part_no' => $itemData['part_no'],
                    'qty' => $itemData['qty'],
                    'received_qty' => $itemData['received_qty']
                ]);
            }

            // Recalculate and persist final status
            $asn->updateStatusBasedOnItems();

            DB::commit();
            
            \Log::info('ASN import completed successfully', [
                'asn_id' => $asn->id,
                'asn_number' => $asnNumber,
                'items_created' => $itemsCreated,
                'user_id' => $this->userId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Rethrow validation exception so controller can show short message + Excel download
            if ($e instanceof AsnImportValidationException) {
                throw $e;
            }

            // Log the error
            \Log::error('ASN import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $this->userId
            ]);

            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new \Exception($e->getMessage(), $code, $e);
        }
    }

    /**
     * Extract ASN header information from rows before item table
     */
    private function extractAsnHeader($data, $headerRowIndex)
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
                } elseif ($cell == 'SUPPLIER CODE' || $cell == 'SUPPLIER_CODE' || $cell == 'SUPPLIERCODE') {
                    $header['supplier_code'] = $nextCell;
                } elseif ($cell == 'SUPPLIER INV NO' || $cell == 'SUPPLIER_INV_NO' || $cell == 'SUPPLIER INV NO.' || $cell == 'SUPPLIERINVNO') {
                    $header['supplier_inv_no'] = $nextCell;
                } elseif ($cell == 'CONTAINER NO' || $cell == 'CONTAINER_NO' || $cell == 'CONTAINERNO') {
                    $header['container_no'] = $nextCell;
                } elseif ($cell == 'DEC NO' || $cell == 'DEC_NO' || $cell == 'DECNO') {
                    $header['dec_no'] = $nextCell;
                } elseif ($cell == 'BOE NUMBER' || $cell == 'BOE NO' || $cell == 'BOE_NO' || $cell == 'BOENUMBER') {
                    $header['boe_number'] = $nextCell;
                } elseif ($cell == 'DEC DATE' || $cell == 'DEC_DATE' || $cell == 'DECDATE') {
                    $header['dec_date'] = $nextCell;
                } elseif ($cell == 'ETA DATE' || $cell == 'ETA_DATE' || $cell == 'ETADATE' || $cell == 'ETA') {
                    $header['eta_date'] = $nextCell;
                } elseif ($cell == 'CURRENCY ID' || $cell == 'CURRENCY_ID' || $cell == 'CURRENCYID') {
                    $header['currency_id'] = $nextCell;
                } elseif ($cell == 'EXCHANGE RATE' || $cell == 'EXCHANGE_RATE' || $cell == 'EXCHANGERATE') {
                    $header['exchange_rate'] = $nextCell;
                } elseif ($cell == 'WAREHOUSE' || $cell == 'WAREHOUSE_ID' || $cell == 'WAREHOUSEID' || $cell == 'WAREHOUSE NAME' || $cell == 'WAREHOUSE_NAME') {
                    $header['warehouse'] = $nextCell;
                }
                
                // Right column headers
                if ($cell == 'ASN NO' || $cell == 'ASN_NO' || $cell == 'ASNNO') {
                    // Skip - we'll generate this
                } elseif ($cell == 'ASN DATE' || $cell == 'ASN_DATE' || $cell == 'ASNDATE') {
                    // We no longer require ASN date in the header sample; keep parsing if provided
                    $header['asn_date'] = $nextCell;
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
            if (in_array($header, ['BOX NO', 'BOX_NO', 'BOXNO', 'BOX NO.'])) {
                $map['box_no'] = $index;
            } elseif (in_array($header, ['SUPPLIER PO NO', 'SUPPLIER_PO_NO', 'SUPPLIER_PO_NO', 'SUPPLIERPONO'])) {
                $map['supplier_po_no'] = $index;
            } elseif (in_array($header, ['OUR PRO NO', 'OUR_PRO_NO', 'OURPRONO'])) {
                $map['our_pro_no'] = $index;
            } elseif (in_array($header, ['ORDER REF', 'ORDER_REF', 'ORDERREF'])) {
                $map['order_ref'] = $index;
            } elseif (in_array($header, ['PART NO', 'PART_NO', 'PARTNO'])) {
                $map['part_no'] = $index;
            } elseif (in_array($header, ['DESCRIPTION', 'DESC'])) {
                $map['description'] = $index;
            } elseif (in_array($header, ['QTY', 'QUANTITY'])) {
                $map['qty'] = $index;
            } elseif (in_array($header, ['RECEIVED QTY', 'RECEIVED_QTY', 'RECEIVEDQTY', 'RECEIVED QUANTITY'])) {
                $map['received_qty'] = $index;
            } elseif (in_array($header, ['DISCREPANCY'])) {
                // Skip - will calculate
            } elseif (in_array($header, ['UNIT PRICE', 'UNIT_PRICE', 'UNITPRICE', 'PRICE'])) {
                $map['unit_price'] = $index;
            } elseif (in_array($header, ['TOTAL PRICE', 'TOTAL_PRICE', 'TOTALPRICE', 'TOTAL'])) {
                // Skip - will calculate
            } elseif (in_array($header, ['UNIT WEIGHT', 'UNIT_WEIGHT', 'UNITWEIGHT'])) {
                $map['unit_weight'] = $index;
            } elseif (in_array($header, ['TOTAL WEIGHT', 'TOTAL_WEIGHT', 'TOTALWEIGHT'])) {
                // Skip - will calculate
            } elseif (in_array($header, ['HS CODE', 'HS_CODE', 'HSCODE'])) {
                $map['hs_code'] = $index;
            } elseif (in_array($header, ['CONTAINER NO', 'CONTAINER_NO', 'CONTAINERNO', 'CONTAINER NO'])) {
                $map['container_no'] = $index;
            } elseif (in_array($header, ['DEC NO', 'DEC_NO', 'DECNO'])) {
                $map['dec_no'] = $index;
            } elseif (in_array($header, ['DEC DATE', 'DEC_DATE', 'DECDATE', 'DED DATE', 'DED_DATE', 'DEDDATE'])) {
                $map['dec_date'] = $index;
            } elseif (in_array($header, ['ORIGIN'])) {
                $map['origin'] = $index;
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
     * Format validation errors in a clear, readable way
     */
    private function formatValidationErrors($validationErrors)
    {
        $errorCount = count($validationErrors);
        $message = "❌ ASN Import Failed: {$errorCount} error(s) found. Please fix the following issues:\n\n";
        
        $message .= "=" . str_repeat("=", 70) . "\n";
        $message .= "VALIDATION ERRORS SUMMARY\n";
        $message .= "=" . str_repeat("=", 70) . "\n\n";
        
        foreach ($validationErrors as $index => $error) {
            $rowNum = $error['row'];
            $partNo = $error['part_no'];
            $description = $error['description'];
            $errors = $error['errors'];
            
            $message .= "Error #" . ($index + 1) . " - Row {$rowNum}\n";
            $message .= str_repeat("-", 70) . "\n";
            $message .= "Part No: {$partNo}\n";
            $message .= "Description: {$description}\n";
            $message .= "\nIssues:\n";
            
            foreach ($errors as $err) {
                $message .= "  • {$err}\n";
            }
            
            // Include row data if available
            if (!empty($error['data'])) {
                $data = $error['data'];
                $message .= "\nRow Data:\n";
                if (!empty($data['supplier_po_no'])) {
                    $message .= "  - Supplier PO No: {$data['supplier_po_no']}\n";
                }
                if (!empty($data['our_pro_no'])) {
                    $message .= "  - Our PRO No: {$data['our_pro_no']}\n";
                }
                if (!empty($data['order_ref'])) {
                    $message .= "  - Order Ref: {$data['order_ref']}\n";
                }
                if (!empty($data['unit_price'])) {
                    $message .= "  - Unit Price: {$data['unit_price']}\n";
                }
                if (!empty($data['received_qty'])) {
                    $message .= "  - Received Qty: {$data['received_qty']}\n";
                }
            }
            
            $message .= "\n";
        }
        
        $message .= "=" . str_repeat("=", 70) . "\n";
        $message .= "IMPORT ROLLED BACK - No data was saved.\n";
        $message .= "Please fix all errors and try again.\n";
        $message .= "=" . str_repeat("=", 70) . "\n";
        
        return $message;
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

    /**
     * Get next unique ASN number by checking existing numbers per creator
     * Note: Checks uniqueness per created_by to allow same ASN numbers for different companies
     */
    private function getNextUniqueAsnNumber()
    {
        // Get the highest ASN number for this creator
        $lastAsn = Asn::where('created_by', $this->userId)
            ->withTrashed()
            ->orderByRaw('CAST(asn_no AS UNSIGNED) DESC')
            ->first();
        
        // Start from the last number + 1, or 1 if no ASNs exist for this creator
        $startNumber = $lastAsn && is_numeric($lastAsn->asn_no) ? ((int)$lastAsn->asn_no + 1) : 1;
        
        // Find the next available number (check per creator since we want uniqueness per creator)
        $asnNumber = $startNumber;
        $maxAttempts = 1000; // Safety limit to prevent infinite loop
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            // Check if this number already exists for this creator (including soft-deleted)
            // Since asn_no is stored as string, check both string and numeric formats
            $exists = Asn::where('created_by', $this->userId)
                ->withTrashed()
                ->where(function($query) use ($asnNumber) {
                    $query->where('asn_no', (string)$asnNumber)
                          ->orWhere('asn_no', $asnNumber);
                })
                ->exists();
            
            if (!$exists) {
                // Double-check right before returning to avoid race conditions
                // Use a fresh query to ensure we have the latest data
                $doubleCheck = Asn::where('created_by', $this->userId)
                    ->withTrashed()
                    ->where(function($query) use ($asnNumber) {
                        $query->where('asn_no', (string)$asnNumber)
                              ->orWhere('asn_no', $asnNumber);
                    })
                    ->exists();
                
                if (!$doubleCheck) {
                    return $asnNumber;
                }
            }
            
            // If it exists, try the next number
            $asnNumber++;
            $attempts++;
        }
        
        // Fallback: if we somehow can't find a unique number, use timestamp
        \Log::warning('Could not find unique ASN number after ' . $maxAttempts . ' attempts for creator ' . $this->userId . ', using timestamp');
        return (int)(time() % 1000000); // Use timestamp modulo as fallback
    }
}
