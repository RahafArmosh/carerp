<?php

namespace App\Imports;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\MasterlistLeadger;
use App\Models\Tax;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SaleOrderImport implements ToArray
{
    protected $userId;
    protected $errors = [];

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

            try {
                return \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date string', [
                    'value' => $dateValue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return date('Y-m-d');
    }

    /**
     * Extract value from cell by searching multiple possible keys
     */
    private function getValue($row, $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            $index = array_search($key, $row);
            if ($index !== false && isset($row[$index]) && !empty(trim($row[$index]))) {
                return trim($row[$index]);
            }
        }
        return $default;
    }

    /**
     * Find customer by name only
     */
    private function findCustomer($customerName)
    {
        $user = \App\Models\User::find($this->userId);
        $creatorId = $user ? $user->creatorId() : $this->userId;

        // Find by name only (case-insensitive, trimmed)
        if (!empty($customerName)) {
            $customer = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($customerName))])
                ->where('created_by', $creatorId)
                ->first();
            if ($customer) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Find tax IDs by tax value: rate only (e.g. 5 or 5%), or ID, or name (comma-separated)
     */
    private function findTaxIds($taxValue, $creatorId)
    {
        if ($taxValue === null || $taxValue === '') {
            return [];
        }

        $taxIds = [];
        // Split by comma to handle multiple taxes
        $taxValues = array_map('trim', explode(',', (string)$taxValue));

        foreach ($taxValues as $value) {
            if ($value === '') {
                continue;
            }

            // Try to find by ID first (if numeric and looks like ID, not rate)
            if (is_numeric($value)) {
                $tax = Tax::where('id', $value)
                    ->where('created_by', $creatorId)
                    ->first();
                if ($tax) {
                    $taxIds[] = (string)$tax->id;
                    continue;
                }
                // Treat as rate (e.g. 5 means 5% VAT)
                $tax = Tax::where('created_by', $creatorId)
                    ->where('rate', (float)$value)
                    ->first();
                if ($tax) {
                    $taxIds[] = (string)$tax->id;
                    continue;
                }
            }

            // Try to parse as rate: "5%", "5% VAT", "5 %" -> 5
            $rateMatch = [];
            if (preg_match('/^(\d+(?:\.\d+)?)\s*%?/i', trim($value), $rateMatch)) {
                $rate = (float)$rateMatch[1];
                $tax = Tax::where('created_by', $creatorId)
                    ->where('rate', $rate)
                    ->first();
                if ($tax) {
                    $taxIds[] = (string)$tax->id;
                    continue;
                }
            }

            // Try to find by name (case-insensitive, trimmed)
            $tax = Tax::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($value))])
                ->where('created_by', $creatorId)
                ->first();
            
            if ($tax) {
                $taxIds[] = (string)$tax->id;
            }
        }

        return $taxIds;
    }

    public function array(array $data)
    {
        try {
            ini_set('memory_limit', '2048M');
            set_time_limit(3600);
            DB::connection()->disableQueryLog();

            // Validate minimum rows (customer block + table header + at least 1 data row)
            if (count($data) < 8) {
                throw new \Exception('Invalid file format. File must have at least 8 rows (header + table header + at least 1 data row).');
            }

            // Extract header information (rows 1-10)
            // Customer info (CUSTOMER NAME only; Customer Code and TRN come from customer master)
            // Row 6-7: Sales Order info (I6: SALES ORDER NO, I7: SALES ORDER DATE)

            $customerName = null;
            $salesOrderNo = null;
            $salesOrderDate = null;
            $taxValue = null;

            // Extract customer info - search from row 1 onward (rows 1-15) to support sample layout and various formats
            for ($i = 0; $i < min(15, count($data)); $i++) {
                if (!isset($data[$i])) {
                    continue;
                }
                
                $row = $data[$i];
                $rowValues = array_values($row); // normalize keys for sparse rows
                $rowUpper = array_map(function ($v) {
                    return strtoupper(trim((string)$v));
                }, $row);
                
                // Look for customer name with various formats
                foreach ($rowUpper as $colIndex => $cellValue) {
                    // Check if this cell contains "CUSTOMER NAME" (with or without colon)
                    if (stripos($cellValue, 'CUSTOMER NAME') !== false) {
                        // Get the value from the next column
                        if (isset($row[$colIndex + 1]) && !empty(trim($row[$colIndex + 1]))) {
                            $customerName = trim($row[$colIndex + 1]);
                            break 2; // Break out of both loops
                        }
                    }
                }
                
                // Look for TAX (accept any value including 0 so that zero tax saves correctly)
                foreach ($rowUpper as $colIndex => $cellValue) {
                    if (stripos($cellValue, 'TAX') !== false && stripos($cellValue, 'CUSTOMER') === false) {
                        if (isset($row[$colIndex + 1])) {
                            $rawTax = $row[$colIndex + 1];
                            $taxValue = is_numeric($rawTax) ? (string)(float)$rawTax : trim((string)$rawTax);
                            break;
                        }
                    }
                }
            }

            // Explicitly read TAX from row 3 (I3/J3) when present - matches common layout
            if ($taxValue === null && isset($data[2]) && isset($data[2][8]) && stripos((string)$data[2][8], 'TAX') !== false && isset($data[2][9])) {
                $rawTax = $data[2][9];
                $taxValue = is_numeric($rawTax) ? (string)(float)$rawTax : trim((string)$rawTax);
            }

            // Extract sales order date and tax from row 6-7 (columns I-J)
            // SALES ORDER NO is auto-generated by system, not required in import
            if (isset($data[5])) { // Row 6
                $row6 = $data[5];
                // Column I (index 8) might have "SALES ORDER DATE" (moved up since SALES ORDER NO removed)
                if (isset($row6[8]) && stripos($row6[8], 'SALES ORDER DATE') !== false && isset($row6[9])) {
                    $salesOrderDate = $this->parseDate($row6[9]);
                }
                // Column I (index 8) might have "TAX" (accept 0)
                if (isset($row6[8]) && stripos($row6[8], 'TAX') !== false && isset($row6[9]) && $taxValue === null) {
                    $rawTax = $row6[9];
                    $taxValue = is_numeric($rawTax) ? (string)(float)$rawTax : trim((string)$rawTax);
                }
            }

            if (isset($data[6])) { // Row 7
                $row7 = $data[6];
                // Column I (index 8) should have "SALES ORDER DATE"
                if (isset($row7[8]) && stripos($row7[8], 'SALES ORDER DATE') !== false && isset($row7[9])) {
                    $salesOrderDate = $this->parseDate($row7[9]);
                }
            }
            
            // Also check row 5 (I5) for TAX
            if (isset($data[4])) { // Row 5
                $row5 = $data[4];
                if (isset($row5[8]) && stripos($row5[8], 'TAX') !== false && isset($row5[9]) && $taxValue === null) {
                    $rawTax = $row5[9];
                    $taxValue = is_numeric($rawTax) ? (string)(float)$rawTax : trim((string)$rawTax);
                }
            }

            // Find customer by name only
            if (empty($customerName)) {
                $debugInfo = [];
                for ($i = 0; $i < min(15, count($data)); $i++) {
                    if (isset($data[$i])) {
                        $rowStr = implode(' | ', array_filter(array_map('trim', $data[$i])));
                        if (!empty($rowStr)) {
                            $debugInfo[] = "Row " . ($i + 1) . ": " . substr($rowStr, 0, 100);
                        }
                    }
                }
                $debugMsg = !empty($debugInfo) ? "\n\nFound rows:\n" . implode("\n", $debugInfo) : "";
                throw new \Exception('Customer Name is required in the header section. Please ensure "CUSTOMER NAME" label exists in rows 1-15, followed by the customer name value in the next column.' . $debugMsg);
            }
            
            $customer = $this->findCustomer($customerName);
            if (!$customer) {
                throw new \Exception('Customer not found. Please ensure customer exists with name: "' . $customerName . '". The name must match exactly (case-insensitive).');
            }

            // Get or generate sales order number
            if (empty($salesOrderNo)) {
                $lastSaleOrder = SaleOrder::where('created_by', $this->userId)
                    ->withTrashed()
                    ->latest()
                    ->first();
                $salesOrderNo = $lastSaleOrder ? ((int)$lastSaleOrder->sale_order_no + 1) : 1;
            }

            // Default sales order date to today if not provided
            if (empty($salesOrderDate)) {
                $salesOrderDate = date('Y-m-d');
            }

            // Get user's creatorId for tax lookup
            $user = \App\Models\User::find($this->userId);
            $creatorId = $user ? $user->creatorId() : $this->userId;

            // Get default currency (currencies table doesn't have created_by column)
            // Try to get AED currency first (common default), otherwise get first available
            $currency = Currency::where('code', 'AED')->first();
            if (!$currency) {
                $currency = Currency::first();
            }
            $currencyId = $currency ? $currency->id : null;
            $exchangeRate = $currency ? $currency->exchange_rate : 1.0;

            // Get tax from import or use default (allow explicit 0 so zero tax saves)
            $taxId = '';
            $taxProvidedInFile = ($taxValue !== null && $taxValue !== '');
            if ($taxProvidedInFile) {
                // Try to find tax by the provided value (can be ID, rate e.g. 0 or 5, or name)
                $taxIds = $this->findTaxIds($taxValue, $creatorId);
                $taxId = !empty($taxIds) ? implode(',', $taxIds) : ($taxValue === '0' ? '' : '');
            }
            
            // If no tax found from import, use default tax only when TAX was not in the file
            if (empty($taxId) && !$taxProvidedInFile) {
                // Try to find 5% tax first, then fallback to first tax
                $defaultTax = Tax::where('created_by', $creatorId)
                    ->where('rate', 5)
                    ->first();
                
                if (!$defaultTax) {
                    $defaultTax = Tax::where('created_by', $creatorId)->first();
                }
                
                $taxId = $defaultTax ? (string)$defaultTax->id : '';
            }

            // Parse table data - find header row dynamically
            // Headers could be in row 11 or 12 (0-indexed: 10 or 11)
            // Look for headers: PART NO, DESCRIPTION, REQ QTY, UNIT PRICE, TOTAL (TOTAL CONFIRMED removed from SO)
            if (count($data) < 8) {
                throw new \Exception('Table headers not found. File must have at least 8 rows.');
            }

            $headerRow = null;
            $headerRowIndex = null;
            
            // Try to find header row by looking for "PART NO" in rows 5-14 (0-indexed) to support sample layout
            for ($i = 4; $i < min(15, count($data)); $i++) {
                if (!isset($data[$i])) {
                    continue;
                }
                
                $row = $data[$i];
                foreach ($row as $cell) {
                    $cellUpper = strtoupper(trim($cell));
                    // Check if this row contains "PART NO" header
                    if (stripos($cellUpper, 'PART NO') !== false || $cellUpper == 'PARTNO' || stripos($cellUpper, 'PART NUMBER') !== false) {
                        $headerRow = $row;
                        $headerRowIndex = $i;
                        break 2; // Break out of both loops
                    }
                }
            }
            
            if (!$headerRow) {
                // If header row not found, try row 11 (0-indexed: 10) as fallback
                if (isset($data[10])) {
                    $headerRow = $data[10];
                    $headerRowIndex = 10;
                } else {
                    throw new \Exception('Table headers not found. Could not locate "PART NO" column in rows 5-15.');
                }
            }
            
            // Find column indices (only required columns)
            $partNoIndex = false;
            $descriptionIndex = false;
            $reqQtyIndex = false;
            $unitPriceIndex = false;
            // Optional columns (for backward compatibility, but not required)
            $stockQtyIndex = false;
            $packedQtyIndex = false;

            foreach ($headerRow as $index => $header) {
                if (empty($header)) {
                    continue; // Skip empty cells
                }
                
                $headerUpper = strtoupper(trim($header));
                // Remove extra spaces and normalize
                $headerUpper = preg_replace('/\s+/', ' ', $headerUpper);
                
                // Match PART NO with various formats
                if (stripos($headerUpper, 'PART NO') !== false || $headerUpper == 'PARTNO' || stripos($headerUpper, 'PART NUMBER') !== false) {
                    $partNoIndex = $index;
                } elseif (stripos($headerUpper, 'DESCRIPTION') !== false) {
                    $descriptionIndex = $index;
                } elseif (stripos($headerUpper, 'REQ QTY') !== false || stripos($headerUpper, 'REQUIRED QTY') !== false || stripos($headerUpper, 'REQ. QTY') !== false) {
                    $reqQtyIndex = $index;
                } elseif (stripos($headerUpper, 'UNIT PRICE') !== false || stripos($headerUpper, 'PRICE') !== false) {
                    $unitPriceIndex = $index;
                } elseif (stripos($headerUpper, 'STOCK QTY') !== false || stripos($headerUpper, 'STOCK QUANTITY') !== false) {
                    // Optional - for backward compatibility
                    $stockQtyIndex = $index;
                } elseif (stripos($headerUpper, 'PACKED QTY') !== false || stripos($headerUpper, 'PACKED QUANTITY') !== false) {
                    // Optional - for backward compatibility
                    $packedQtyIndex = $index;
                }
            }

            if ($partNoIndex === false) {
                // Provide debug info about what headers were found
                $foundHeaders = array_filter(array_map('trim', $headerRow));
                $foundHeadersStr = implode(', ', $foundHeaders);
                throw new \Exception('PART NO column not found in headers. Found headers in row ' . ($headerRowIndex + 1) . ': ' . $foundHeadersStr);
            }
            if ($reqQtyIndex === false) {
                throw new \Exception('REQ QTY column not found in headers.');
            }

            // Get data rows (starting from the row after header row)
            $dataRows = array_slice($data, $headerRowIndex + 1);
            
            // Filter out empty rows
            $dataRows = array_filter($dataRows, function($row) use ($partNoIndex) {
                return isset($row[$partNoIndex]) && !empty(trim($row[$partNoIndex]));
            });

            if (empty($dataRows)) {
                throw new \Exception('No data rows found in the file.');
            }

            // Get user's creatorId for stock validation
            $user = \App\Models\User::find($this->userId);
            $creatorId = $user ? $user->creatorId() : $this->userId;

            // Stock is not validated to block import: we reserve only available stock (min of req_qty and available) per item.

            // Create sale order
            DB::beginTransaction();
            try {
                $saleOrder = new SaleOrder();
                $saleOrder->sale_order_no = $salesOrderNo;
                $saleOrder->customer_id = $customer->id;
                $saleOrder->sales_order_date = $salesOrderDate;
                $saleOrder->currency_id = $currencyId;
                $saleOrder->exchange_rate = $exchangeRate;
                $saleOrder->tax_id = $taxId;
                $saleOrder->status = 'draft';
                $saleOrder->created_by = $this->userId;
                $saleOrder->save();

                // Create sale order items (stock validation passed)
                $rowNumber = $headerRowIndex + 2; // Reset row number for item creation (headerRowIndex is 0-indexed, so +2 gives actual row number)

                foreach ($dataRows as $rowIndex => $row) {
                    $partNo = isset($row[$partNoIndex]) ? trim($row[$partNoIndex]) : null;
                    if (empty($partNo)) {
                        $rowNumber++;
                        continue;
                    }

                    $description = ($descriptionIndex !== false && isset($row[$descriptionIndex]))
                        ? trim($row[$descriptionIndex])
                        : null;
                    $reqQty = isset($row[$reqQtyIndex]) ? (float)$row[$reqQtyIndex] : 0;
                    $unitPrice = isset($row[$unitPriceIndex]) ? (float)$row[$unitPriceIndex] : 0;

                    if (empty($description)) {
                        $subProductForDesc = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($partNo))])
                            ->where('created_by', $creatorId)
                            ->with('productService')
                            ->latest()
                            ->first();
                        if ($subProductForDesc && $subProductForDesc->productService) {
                            $description = $subProductForDesc->productService->name;
                        }
                    }

                    // Allocate from stock (FIFO): one SO item per sub-product with allocated qty
                    $allocations = $this->allocatePartNoFromStock($creatorId, $partNo, $reqQty);
                    if (empty($allocations)) {
                        $item = new SaleOrderItem();
                        $item->sale_order_id = $saleOrder->id;
                        $item->part_no = $partNo;
                        $item->description = $description;
                        $item->req_qty = $reqQty;
                        $item->stock_qty = 0;
                        $item->packed_qty = 0;
                        $item->unit_price = $unitPrice;
                        $item->product_id = null;
                        $item->sub_product_id = null;
                        $item->save();
                    } else {
                        foreach ($allocations as $alloc) {
                            $sp = $alloc['sub_product'];
                            $qty = $alloc['qty'];
                            $item = new SaleOrderItem();
                            $item->sale_order_id = $saleOrder->id;
                            $item->part_no = $partNo;
                            $item->description = $description;
                            $item->req_qty = $qty;
                            $item->stock_qty = $qty;
                            $item->packed_qty = 0;
                            $item->unit_price = $unitPrice;
                            $item->product_id = $sp->product_id;
                            $item->sub_product_id = $sp->id;
                            $item->save();

                            # Master List Ledger
                        }
                    }

                    $rowNumber++;
                }

                DB::commit();
                
                // Book sub-products using FIFO after sale order is created
                $saleOrder->refresh(); // Reload with items
                $this->bookSubProductsForSaleOrder($saleOrder);
                
                \Log::info('Sale order imported successfully', [
                    'sale_order_id' => $saleOrder->id,
                    'sale_order_no' => $salesOrderNo,
                    'items_count' => count($dataRows)
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Sale order import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $this->userId
            ]);
            throw $e;
        }
    }

    /**
     * Allocate req_qty for a part_no from available stock (FIFO). Does NOT modify DB.
     * Returns array of [['sub_product' => SubProduct, 'qty' => float], ...].
     */
    private function allocatePartNoFromStock($creatorId, $partNo, $reqQty)
    {
        $partNo = strtoupper(trim($partNo));
        if ($partNo === '' || $reqQty <= 0) {
            return [];
        }

        $availableSubProducts = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [$partNo])
            ->where('created_by', $creatorId)
            ->where('flag', '!=', 2)
            ->where('booked', 0)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'ASC')
            ->get();

        if ($availableSubProducts->isEmpty()) {
            return [];
        }

        $product = ProductService::find($availableSubProducts->first()->product_id);
        $categoryType = ($product && $product->category) ? $product->category->type : null;
        $reqQty = (float)$reqQty;
        $allocations = [];

        if ($categoryType === 'Qty product') {
            $remaining = $reqQty;
            foreach ($availableSubProducts as $sp) {
                if ($remaining <= 0) {
                    break;
                }
                $availableQty = (float)$sp->quantity;
                $qty = min($availableQty, $remaining);
                if ($qty > 0) {
                    $allocations[] = ['sub_product' => $sp, 'qty' => $qty];
                    $remaining -= $qty;
                }
            }
        } else {
            $count = (int)$reqQty;
            $taken = 0;
            foreach ($availableSubProducts as $sp) {
                if ($taken >= $count) {
                    break;
                }
                $allocations[] = ['sub_product' => $sp, 'qty' => 1];
                $taken++;
            }
        }

        return $allocations;
    }

    /**
     * Book sub-products for sale order. Each SO item already has sub_product_id and stock_qty.
     */
    public function bookSubProductsForSaleOrder($saleOrder)
    {
        $user = \App\Models\User::find($this->userId);
        $creatorId = $user ? $user->creatorId() : $this->userId;

        foreach ($saleOrder->items as $item) {
            if (empty($item->sub_product_id) || (float)($item->stock_qty ?? 0) <= 0) {
                continue;
            }

            $sp = SubProduct::where('id', $item->sub_product_id)
                ->where('created_by', $creatorId)
                ->first();

            if (!$sp) {
                continue;
            }

            $product = ProductService::find($sp->product_id);
            $categoryType = ($product && $product->category) ? $product->category->type : null;
            $qtyToBook = (float)$item->stock_qty;

            if ($categoryType === 'Qty product') {
                $availableQty = (float)$sp->quantity;
                $take = min($availableQty, $qtyToBook);
                if ($take <= 0) {
                    continue;
                }
                $sp->quantity = $availableQty - $take;
                $sp->booked = ($sp->quantity <= 0) ? 1 : 0;
                $sp->sale_order_id = $saleOrder->id;
                $sp->so_qty_reserved = $take;
                $sp->save();
                
                $target_document_type = "";
                $target_document = 0;
                if($sp->asn_id){
                    $target_document_type = "ASN";
                    $target_document = $sp->asn_id;
                }else{
                    $target_document_type = "BILL";
                    $target_document = $sp->bill_id;
                }
                MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,$take,'SO',$saleOrder->id,$saleOrder->created_by,$target_document_type,$target_document);
            
            } else {
                $sp->booked = 1;
                $sp->sale_order_id = $saleOrder->id;
                $sp->save();

                $target_document_type = "";
                $target_document = 0;
                if($sp->asn_id){
                    $target_document_type = "ASN";
                    $target_document = $sp->asn_id;
                }else{
                    $target_document_type = "BILL";
                    $target_document = $sp->bill_id;
                }
                MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,1,'SO',$saleOrder->id,$saleOrder->created_by,$target_document_type,$target_document);
       
            }
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
