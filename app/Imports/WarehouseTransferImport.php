<?php

namespace App\Imports;

use App\Models\warehouse;
use App\Models\SubProduct;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferRequest;
use App\Models\PosLog;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class WarehouseTransferImport implements ToArray, WithChunkReading, WithBatchInserts
{
    protected $userId;
    protected $actorUserId;
    protected $restrictedFromWarehouseId;
    protected $warehouses = [];
    protected $batchSize = 500; // Process 500 rows at a time
    protected $errors = [];
    protected $successCount = 0;
    protected $currentRequestId = null; // Track current request ID for grouping
    
    // Performance optimization: Cache product mappings (draft import does not validate stock; approve does)
    protected $productNoToIdCache = []; // Cache product_no => product_id mappings
    protected $maxErrors = 10000; // Maximum errors to track (prevent memory issues)
    
    // Static property to store last import errors (for controller access)
    public static $lastImportErrors = null;

    public function __construct($userId, int $actorUserId = 0, ?int $restrictedFromWarehouseId = null)
    {
        $this->userId = $userId;
        $this->actorUserId = $actorUserId;
        $this->restrictedFromWarehouseId = $restrictedFromWarehouseId;
    }

    public function chunkSize(): int
    {
        return 1000; // Read 1000 rows at a time from Excel
    }

    public function batchSize(): int
    {
        return 500; // Insert 500 records at a time
    }

    /**
     * Pre-load product data and stock for a chunk to avoid N+1 queries
     * This dramatically improves performance for large imports
     */
    private function preloadProductData(array $dataRows, array $columnIndices)
    {
        // Extract unique product_no and warehouse combinations from this chunk
        $productWarehousePairs = [];
        foreach ($dataRows as $row) {
            $fromWarehouseId = trim($row[$columnIndices['from_warehouse']] ?? '');
            $productNo = trim($row[$columnIndices['product_no']] ?? '');
            
            if (!empty($fromWarehouseId) && !empty($productNo)) {
                $key = "{$fromWarehouseId}_{$productNo}";
                if (!isset($productWarehousePairs[$key])) {
                    $productWarehousePairs[$key] = [
                        'warehouse_id' => $fromWarehouseId,
                        'product_no' => $productNo
                    ];
                }
            }
        }
        
        if (empty($productWarehousePairs)) {
            return;
        }
        
        // Extract unique product numbers and warehouse IDs
        $productNos = array_unique(array_column($productWarehousePairs, 'product_no'));
        $warehouseIds = array_unique(array_column($productWarehousePairs, 'warehouse_id'));
        
        // Batch load product_no to product_id mappings (only for missing ones)
        $missingProductNos = array_diff($productNos, array_keys($this->productNoToIdCache));
        if (!empty($missingProductNos)) {
            // Any sub-product line in this warehouse (including qty 0) resolves product_id for drafts
            $productMappings = SubProduct::whereIn('chassis_no', $missingProductNos)
                ->whereIn('warehouse_id', $warehouseIds)
                ->select('chassis_no', 'product_id', 'warehouse_id', 'quantity')
                ->orderByDesc('quantity')
                ->get();

            foreach ($productMappings as $mapping) {
                if (!isset($this->productNoToIdCache[$mapping->chassis_no])) {
                    $this->productNoToIdCache[$mapping->chassis_no] = $mapping->product_id;
                }
            }
        }
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

    public function array(array $data)
    {
        try {
            // Increase memory limit and execution time for large imports
            ini_set('memory_limit', '2048M'); // 2GB for 7000+ rows
            set_time_limit(0);
            
            // Disable query logging for performance
            DB::connection()->disableQueryLog();

            // Validate required data structure - must have at least header row + 1 data row
            if (count($data) < 2) {
                throw new \Exception('Invalid file format. File must have at least a header row and one data row.');
            }

            // Check if this is the first chunk (contains header)
            static $isFirstChunk = true;
            static $columnIndices = [];
            static $totalProcessed = 0;

            if ($isFirstChunk) {
                $headerRow = $data[0];
                $dataRows = array_slice($data, 1);
                
                // Find column indices
                $columnIndices['from_warehouse'] = array_search('from_warehouse', array_map('strtolower', $headerRow));
                $columnIndices['to_warehouse'] = array_search('to_warehouse', array_map('strtolower', $headerRow));
                $columnIndices['product_no'] = array_search('product_no', array_map('strtolower', $headerRow));
                $columnIndices['quantity'] = array_search('quantity', array_map('strtolower', $headerRow));
                $columnIndices['date'] = array_search('date', array_map('strtolower', $headerRow));

                // Validate required columns exist
                foreach (['from_warehouse', 'to_warehouse', 'product_no', 'quantity', 'date'] as $col) {
                    if ($columnIndices[$col] === false) {
                        throw new \Exception("Column \"{$col}\" not found in header row.");
                    }
                }

                // Pre-load all warehouses for validation (only once)
                $this->warehouses = warehouse::where('created_by', $this->userId)
                    ->get()
                    ->keyBy('id');

                \Log::info('Warehouse Transfer import started', [
                    'user_id' => $this->userId,
                    'total_warehouses' => count($this->warehouses)
                ]);

                $isFirstChunk = false;
            } else {
                $dataRows = $data; // Subsequent chunks don't have headers
            }
            
            // Pre-load product mappings and stock for this chunk (performance optimization)
            $this->preloadProductData($dataRows, $columnIndices);

            // Group rows by from_warehouse, to_warehouse, and date to create requests
            // Process rows in batches
            $chunkSize = 500;
            $chunks = array_chunk($dataRows, $chunkSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                // Group rows by warehouse combination and date for request creation
                $groupedRows = [];
                foreach ($chunk as $rowIndex => $row) {
                    $rowNumber = $totalProcessed + $rowIndex + 2; // +2 for header row
                    
                    try {
                        // Extract values
                        $fromWarehouseId = trim($row[$columnIndices['from_warehouse']] ?? '');
                        $toWarehouseId = trim($row[$columnIndices['to_warehouse']] ?? '');
                        $productNo = trim($row[$columnIndices['product_no']] ?? '');
                        $quantity = isset($row[$columnIndices['quantity']]) ? (int)$row[$columnIndices['quantity']] : 0;
                        $dateValue = $row[$columnIndices['date']] ?? null;

                        // Validate required fields
                        if (empty($fromWarehouseId) || empty($toWarehouseId) || empty($productNo) || $quantity <= 0 || empty($dateValue)) {
                            throw new \Exception("Missing required fields on row {$rowNumber}.");
                        }

                        // Validate warehouses
                        if (!isset($this->warehouses[$fromWarehouseId])) {
                            throw new \Exception("From warehouse ID {$fromWarehouseId} not found on row {$rowNumber}.");
                        }
                        if (!empty($this->restrictedFromWarehouseId) && (int) $fromWarehouseId !== (int) $this->restrictedFromWarehouseId) {
                            throw new \Exception("From warehouse must be your assigned warehouse (ID {$this->restrictedFromWarehouseId}) on row {$rowNumber}.");
                        }
                        if (!isset($this->warehouses[$toWarehouseId])) {
                            throw new \Exception("To warehouse ID {$toWarehouseId} not found on row {$rowNumber}.");
                        }
                        if ($fromWarehouseId == $toWarehouseId) {
                            throw new \Exception("From and To warehouse cannot be the same on row {$rowNumber}.");
                        }

                        // Parse date
                        $transferDate = $this->parseDate($dateValue);
                        if (!$transferDate) {
                            throw new \Exception("Invalid date format on row {$rowNumber}.");
                        }

                        // Draft import: do not validate stock (approval enforces availability)

                        // Get product ID from cache (performance optimization)
                        $productId = $this->productNoToIdCache[$productNo] ?? null;
                        
                        if (!$productId) {
                            throw new \Exception("Product {$productNo} not found in warehouse {$fromWarehouseId} on row {$rowNumber}.");
                        }

                        // Group by warehouse combination and date
                        $groupKey = "{$fromWarehouseId}_{$toWarehouseId}_{$transferDate}";
                        if (!isset($groupedRows[$groupKey])) {
                            $groupedRows[$groupKey] = [
                                'from_warehouse' => $fromWarehouseId,
                                'to_warehouse' => $toWarehouseId,
                                'date' => $transferDate,
                                'transfers' => []
                            ];
                        }

                        $groupedRows[$groupKey]['transfers'][] = [
                            'row_number' => $rowNumber,
                            'product_no' => $productNo,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                        ];

                    } catch (\Exception $e) {
                        // Store error but continue processing - always import rest of file
                        if (count($this->errors) < $this->maxErrors) {
                            $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                        }
                        // Only log every 100th error to reduce logging overhead
                        if (count($this->errors) % 100 == 0) {
                            \Log::warning('Warehouse Transfer import row errors accumulating', [
                                'error_count' => count($this->errors),
                                'user_id' => $this->userId
                            ]);
                        }
                        // Continue to next row - don't throw exception
                    }
                }

                // Process grouped rows and create requests/transfers
                // Use individual transactions per group to ensure commits persist independently
                foreach ($groupedRows as $groupKey => $groupData) {
                    // Check if we're already in a transaction (Laravel Excel might wrap the import)
                    $wasInTransaction = DB::transactionLevel() > 0;
                    
                    if ($wasInTransaction) {
                        // We're in a nested transaction - use savepoint approach
                        // Create savepoint for this group
                        $savepoint = 'sp_' . uniqid();
                        DB::statement("SAVEPOINT {$savepoint}");
                        
                        try {
                            // Create transfer request as DRAFT (no stock movement on import)
                            $transferRequest = WarehouseTransferRequest::create([
                                'request_number' => WarehouseTransferRequest::generateRequestNumber(),
                                'from_warehouse' => $groupData['from_warehouse'],
                                'to_warehouse'   => $groupData['to_warehouse'],
                                'request_date'   => $groupData['date'],
                                'status'         => 'draft',
                                'notes'          => 'Imported from Excel - Draft',
                                'created_by'     => ($this->actorUserId > 0 ? $this->actorUserId : $this->userId),
                            ]);

                            // Prepare draft transfer rows (stock is NOT moved here)
                            $transferInserts = [];
                            foreach ($groupData['transfers'] as $transferData) {
                                $transferInserts[] = [
                                    'request_id'     => $transferRequest->id,
                                    'from_warehouse' => $groupData['from_warehouse'],
                                    'to_warehouse'   => $groupData['to_warehouse'],
                                    'product_id'     => $transferData['product_id'],
                                    'product_no'     => $transferData['product_no'],
                                    'quantity'       => $transferData['quantity'],
                                    'date'           => $groupData['date'],
                                    'status'         => 'draft', // stock not moved yet
                                    'created_by'     => ($this->actorUserId > 0 ? $this->actorUserId : $this->userId),
                                    'created_at'     => now(),
                                    'updated_at'     => now(),
                                ];

                                $this->successCount++;
                            }

                            // Bulk insert transfers
                            if (!empty($transferInserts)) {
                                WarehouseTransfer::insert($transferInserts);
                            }
                            
                            // Release savepoint (commit this group's work)
                            DB::statement("RELEASE SAVEPOINT {$savepoint}");
                            
                        } catch (\Exception $e) {
                            // Rollback to savepoint (undo this group only)
                            DB::statement("ROLLBACK TO SAVEPOINT {$savepoint}");
                            // Store error for this group but continue processing other groups - always import rest
                            if (count($this->errors) < $this->maxErrors) {
                                $this->errors[] = "Error creating request for group {$groupKey}: " . $e->getMessage();
                            }
                            // Only log every 10th group error to reduce logging overhead
                            if (count($this->errors) % 10 == 0) {
                                \Log::error('Warehouse Transfer import group errors accumulating', [
                                    'error_count' => count($this->errors),
                                    'user_id' => $this->userId
                                ]);
                            }
                            // Continue to next group - don't throw exception
                        }
                    } else {
                        // Not in a transaction - use regular transaction
                        try {
                            DB::transaction(function () use ($groupData, $groupKey) {
                                // Create transfer request as DRAFT (no stock movement on import)
                                $transferRequest = WarehouseTransferRequest::create([
                                    'request_number' => WarehouseTransferRequest::generateRequestNumber(),
                                    'from_warehouse' => $groupData['from_warehouse'],
                                    'to_warehouse'   => $groupData['to_warehouse'],
                                    'request_date'   => $groupData['date'],
                                    'status'         => 'draft',
                                    'notes'          => 'Imported from Excel - Draft',
                                    'created_by'     => ($this->actorUserId > 0 ? $this->actorUserId : $this->userId),
                                ]);

                                // Prepare draft transfer rows (stock is NOT moved here)
                                $transferInserts = [];
                                foreach ($groupData['transfers'] as $transferData) {
                                    $transferInserts[] = [
                                        'request_id'     => $transferRequest->id,
                                        'from_warehouse' => $groupData['from_warehouse'],
                                        'to_warehouse'   => $groupData['to_warehouse'],
                                        'product_id'     => $transferData['product_id'],
                                        'product_no'     => $transferData['product_no'],
                                        'quantity'       => $transferData['quantity'],
                                        'date'           => $groupData['date'],
                                        'status'         => 'draft', // stock not moved yet
                                        'created_by'     => ($this->actorUserId > 0 ? $this->actorUserId : $this->userId),
                                        'created_at'     => now(),
                                        'updated_at'     => now(),
                                    ];

                                    $this->successCount++;
                                }

                                // Bulk insert transfers
                                if (!empty($transferInserts)) {
                                    WarehouseTransfer::insert($transferInserts);
                                }
                            }, 5); // Retry up to 5 times if deadlock occurs
                            
                        } catch (\Exception $e) {
                            // Store error for this group but continue processing other groups - always import rest
                            if (count($this->errors) < $this->maxErrors) {
                                $this->errors[] = "Error creating request for group {$groupKey}: " . $e->getMessage();
                            }
                            // Only log every 10th group error to reduce logging overhead
                            if (count($this->errors) % 10 == 0) {
                                \Log::error('Warehouse Transfer import group errors accumulating', [
                                    'error_count' => count($this->errors),
                                    'user_id' => $this->userId
                                ]);
                            }
                            // Continue to next group - don't throw exception
                        }
                    }
                }

                $totalProcessed += count($chunk);

                // Log progress every 1000 rows
                if ($totalProcessed % 1000 == 0) {
                    \Log::info('Warehouse Transfer import progress', [
                        'processed' => $totalProcessed,
                        'success' => $this->successCount,
                        'errors' => count($this->errors)
                    ]);
                }
            }

            // Re-enable query logging
            DB::connection()->enableQueryLog();
            
            // Log final results
            if (!empty($this->errors)) {
                \Log::warning('Warehouse Transfer import completed with errors', [
                    'success_count' => $this->successCount,
                    'error_count' => count($this->errors),
                    'user_id' => $this->userId,
                    'errors' => array_slice($this->errors, 0, 50) // Log first 50 errors
                ]);
                
                // Store errors in cache so controller can retrieve them
                // Use a unique key based on user ID and timestamp
                $errorKey = 'warehouse_transfer_import_errors_' . $this->userId . '_' . time();
                \Cache::put($errorKey, [
                    'success_count' => $this->successCount,
                    'errors' => $this->errors,
                    'timestamp' => now()
                ], 300); // Store for 5 minutes
                
                // If we have successful imports, DON'T throw exception - let import complete successfully
                // The controller will check for errors in cache
                // Store the error key in static property so controller can access it
                static::$lastImportErrors = [
                    'key' => $errorKey,
                    'success_count' => $this->successCount,
                    'error_count' => count($this->errors)
                ];

                if ($this->successCount > 0) {
                    // Partial success: some rows imported, some failed
                    \Log::info('Warehouse Transfer import completed with partial success', [
                        'success_count' => $this->successCount,
                        'error_count' => count($this->errors),
                        'error_key' => $errorKey,
                        'user_id' => $this->userId
                    ]);
                } else {
                    // No successful imports - but still don't throw exception, just return with errors
                    // This ensures the import process completes and errors are shown to user
                    \Log::warning('Warehouse Transfer import completed with no successful imports', [
                        'error_count' => count($this->errors),
                        'error_key' => $errorKey,
                        'user_id' => $this->userId
                    ]);
                    // Don't throw - controller will pick up errors via static::$lastImportErrors
                }
            } else {
                \Log::info('Warehouse Transfer import completed successfully', [
                    'success_count' => $this->successCount,
                    'user_id' => $this->userId
                ]);
            }

        } catch (\Exception $e) {
            DB::connection()->enableQueryLog();
            \Log::error('Warehouse Transfer import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $this->userId
            ]);
            throw $e;
        }
    }
}

