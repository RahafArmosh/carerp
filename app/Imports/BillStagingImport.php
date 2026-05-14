<?php

namespace App\Imports;

use App\Models\ImportStagingProduct;
use App\Models\ProductService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BillStagingImport implements ToArray
{
    protected $userId;
    protected $importSessionId;

    public function __construct($userId, $importSessionId = null)
    {
        $this->userId = $userId;
        $this->importSessionId = $importSessionId ?? time() . '_' . $userId;
    }

    /**
     * Get the import session ID
     */
    public function getImportSessionId()
    {
        return $this->importSessionId;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

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
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            try {
                return \Carbon\Carbon::parse($dateValue);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date string', [
                    'value' => $dateValue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return now();
    }

    public function array(array $data)
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(0);
            
            // Reconnect to database to prevent "MySQL server has gone away" errors
            DB::reconnect();

            // Validate required data structure
            if (count($data) < 4) {
                throw new \Exception('Invalid file format. File must have at least 4 rows.');
            }

            $billHeader = $data[0];
            $billdata = $data[1];
            $subProductHeader = $data[2];
            $subProductRows = array_slice($data, 3);

            // Store bill data
            $billDataArray = [];
            foreach ($billHeader as $index => $header) {
                $billDataArray[$header] = $billdata[$index] ?? null;
            }

            // Get all SKUs from the import to check against existing products (in chunks to avoid large IN clause)
            // Normalize SKUs to lowercase for case-insensitive matching
            $skusToCheck = [];
            $skuIndex = array_search('sku', $subProductHeader);
            
            foreach ($subProductRows as $rowIndex => $subProductRow) {
                if ($skuIndex !== false && isset($subProductRow[$skuIndex])) {
                    $sku = trim($subProductRow[$skuIndex]);
                    if (!empty($sku)) {
                        $skusToCheck[] = strtolower($sku); // Normalize to lowercase
                    }
                }
            }

            // Pre-load all products by SKU for matching (case-insensitive)
            // Use case-insensitive comparison in database query
            $existingProducts = collect();
            $skuChunks = array_chunk(array_unique($skusToCheck), 1000); // Process 1000 SKUs at a time
            
            foreach ($skuChunks as $skuChunk) {
                // Use case-insensitive matching by comparing lowercase SKUs
                // Build placeholders for IN clause
                $placeholders = implode(',', array_fill(0, count($skuChunk), '?'));
                $products = ProductService::where('created_by', $this->userId)
                    ->whereRaw("LOWER(sku) IN ({$placeholders})", $skuChunk)
                    ->get();
                $existingProducts = $existingProducts->merge($products);
            }
            // Key by lowercase SKU for case-insensitive lookup
            $existingProducts = $existingProducts->keyBy(function ($product) {
                return strtolower($product->sku);
            });

            // Process each row and import to staging in chunks
            $chunkSize = 500; // Process 500 rows at a time
            $chunks = array_chunk($subProductRows, $chunkSize, true);
            $totalProcessed = 0;
            $foundCount = 0;
            $missingCount = 0;
            
            foreach ($chunks as $chunkIndex => $chunk) {
                \Log::info("Processing staging chunk " . ($chunkIndex + 1) . " of " . count($chunks), [
                    'chunk_size' => count($chunk),
                    'total_processed' => $totalProcessed,
                    'total_rows' => count($subProductRows)
                ]);
                
                $chunkStagingProducts = [];
                
                foreach ($chunk as $rowIndex => $subProductRow) {
                    // Extract data from row
                    $sku = null;
                    $productName = null;
                    $brandName = null;
                    $subBrandName = null;
                    $categoryName = null;
                    $quantity = 0;
                    $salePrice = 0;
                    $purchasePrice = 0;
                    $discount = 0;
                    $productNo = null;
                    $customFields = [];

                    foreach ($subProductHeader as $index => $header) {
                        $value = $subProductRow[$index] ?? null;
                        
                        if ($header == 'sku') {
                            $sku = trim($value ?? '');
                        } elseif ($header == 'product_name' || $header == 'name') {
                            $productName = $value;
                        } elseif ($header == 'brand_name' || $header == 'brand') {
                            $brandName = trim($value ?? '');
                        } elseif ($header == 'sub_brand_name' || $header == 'sub_brand') {
                            $subBrandName = trim($value ?? '');
                        } elseif ($header == 'category_name' || $header == 'category') {
                            $categoryName = trim($value ?? '');
                        } elseif ($header == 'quantity') {
                            $quantity = $value ?? 0;
                        } elseif ($header == 'sale_price') {
                            $salePrice = $value ?? 0;
                        } elseif ($header == 'purchase_price') {
                            $purchasePrice = $value ?? 0;
                        } elseif ($header == 'discount') {
                            $discount = $value ?? 0;
                        } elseif ($header == 'product_no') {
                            $productNo = $value;
                        } else {
                            // Store as custom field
                            $customFields[$header] = $value;
                        }
                    }

                    // Check if product exists by SKU
                    $status = 'MISSING';
                    $matchedProductId = null;
                    $statusMessage = null;

                    if (!empty($sku)) {
                        // Use lowercase SKU for case-insensitive matching
                        $skuLower = strtolower($sku);
                        if (isset($existingProducts[$skuLower])) {
                            $matchedProduct = $existingProducts[$skuLower];
                            $status = 'FOUND';
                            $matchedProductId = $matchedProduct->id;
                            $statusMessage = "Product found: {$matchedProduct->name} (ID: {$matchedProduct->id}, SKU: {$matchedProduct->sku})";
                        } else {
                            $statusMessage = "Product with SKU '{$sku}' not found in system";
                            if ($productName) {
                                $statusMessage .= ". Will be created with name: {$productName}";
                            }
                        }
                    } else {
                        $statusMessage = "No SKU provided";
                        if ($productName) {
                            $statusMessage .= ". Product will be created with name: {$productName}";
                        }
                    }

                    // Store in staging (include brand, sub-brand, category names for product creation)
                    $chunkStagingProducts[] = [
                        'import_session_id' => $this->importSessionId,
                        'created_by' => $this->userId,
                        'bill_data' => json_encode($billDataArray),
                        'sku' => $sku,
                        'product_id' => $matchedProductId,
                        'product_name' => $productName,
                        'brand_name' => $brandName,
                        'sub_brand_name' => $subBrandName,
                        'category_name' => $categoryName,
                        'quantity' => $quantity,
                        'sale_price' => $salePrice,
                        'purchase_price' => $purchasePrice,
                        'discount' => $discount,
                        'product_no' => $productNo,
                        'custom_fields' => json_encode($customFields),
                        'status' => $status,
                        'status_message' => $statusMessage,
                        'original_row_data' => json_encode($subProductRow),
                        'row_number' => $rowIndex + 4, // +4 because: header(1) + billdata(2) + subProductHeader(3) + rowIndex(0-based)
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                // Insert chunk to staging table
                if (!empty($chunkStagingProducts)) {
                    try {
                        ImportStagingProduct::insert($chunkStagingProducts);
                        $totalProcessed += count($chunkStagingProducts);
                        
                        // Count found/missing for logging
                        foreach ($chunkStagingProducts as $item) {
                            if ($item['status'] == 'FOUND') {
                                $foundCount++;
                            } else {
                                $missingCount++;
                            }
                        }
                        
                        // Reconnect to database periodically to prevent "MySQL server has gone away" errors
                        if (($chunkIndex + 1) % 10 == 0) {
                            DB::reconnect();
                        }
                        
                        // Clear memory
                        unset($chunkStagingProducts);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error inserting staging chunk', [
                            'chunk_index' => $chunkIndex,
                            'error' => $e->getMessage(),
                            'chunk_size' => count($chunkStagingProducts)
                        ]);
                        
                        // Try to reconnect and retry once
                        DB::reconnect();
                        try {
                            ImportStagingProduct::insert($chunkStagingProducts);
                            $totalProcessed += count($chunkStagingProducts);
                        } catch (\Exception $retryException) {
                            throw new \Exception("Failed to insert staging data after retry. Chunk " . ($chunkIndex + 1) . ": " . $retryException->getMessage());
                        }
                    }
                }
                
                // Force garbage collection periodically
                if (($chunkIndex + 1) % 20 == 0) {
                    gc_collect_cycles();
                }
            }

            \Log::info('Bill import staged successfully', [
                'import_session_id' => $this->importSessionId,
                'user_id' => $this->userId,
                'total_rows' => $totalProcessed,
                'found_count' => $foundCount,
                'missing_count' => $missingCount,
                'total_chunks' => count($chunks),
            ]);

        } catch (\Exception $e) {
            \Log::error('Bill staging import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $this->userId
            ]);
            throw $e;
        }
    }
}

