<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\CustomFieldValue;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $userId;
    protected $filters;
    protected $user;
    protected $customFields;
    protected $customFieldValues;
    protected $canViewAllColumns;
    protected $customFieldsArray; // Cache as array for faster access
    protected $creatorId;
    protected $trackIds = []; // IDs to track for debugging

    public function __construct($userId, $filters = [], $customFields = [], $customFieldValues = [], $trackIds = [])
    {
        $this->userId = $userId;
        $this->filters = $filters;
        $this->user = \App\Models\User::find($userId);
        $this->creatorId = $this->user ? $this->user->creatorId() : $userId;
        $this->customFields = $customFields;
        $this->customFieldValues = $customFieldValues;
        $this->canViewAllColumns = $this->user && $this->user->can('view_all_stock_columns');
        // Convert custom fields to array for faster iteration
        $this->customFieldsArray = $customFields instanceof \Illuminate\Support\Collection 
            ? $customFields->toArray() 
            : (is_array($customFields) ? $customFields : []);
        
        // Track specific IDs for debugging
        $this->trackIds = is_array($trackIds) ? $trackIds : [];
        
        // If custom field values are empty, we'll load them in chunks during export
        // This is more memory efficient for large datasets
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        // Start with base query - include ALL sub-products for this creator
        $query = SubProduct::query()->where('created_by', $this->creatorId);

        // Match report: hide zero quantity unless show_zero_qty is explicitly set
        if (empty($this->filters['show_zero_qty'])) {
            $query->where('quantity', '>', 0);
        }
        // Exclude Item Master import source (same as stock report)
        $query->where(function ($q) {
            $q->whereNull('import_source')->orWhere('import_source', '!=', 'item_master');
        });

        // Apply the same filters as the stockReport method
        if (!empty($this->filters['q'])) {
            $q = trim($this->filters['q']);
            $query->where(function($subQ) use ($q) {
                // Search in sub-product fields (these will always work)
                $subQ->where('chassis_no', 'like', "%{$q}%")
                     ->orWhere('quantity', 'like', "%{$q}%")
                     // Also search in productService if it exists, but don't exclude records without productService
                     ->orWhere(function($psQ) use ($q) {
                         $psQ->whereHas('productService', function($psSubQ) use ($q) {
                             $psSubQ->where('name', 'like', "%{$q}%")
                                    ->orWhere('sku', 'like', "%{$q}%");
                         });
                     });
            });
        }
        
        // IMPORTANT: The query above ensures ALL sub-products are included
        // Records without productService relationship are still included if they match product_no or quantity search

        if (!empty($this->filters['category_id'])) {
            $productIds = \App\Models\ProductService::where('category_id', $this->filters['category_id'])->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if (!empty($this->filters['product_id'])) {
            $query->where('product_id', $this->filters['product_id']);
        }
        if (!empty($this->filters['brand_id'])) {
            $productIds = \App\Models\ProductService::where('brand_id', $this->filters['brand_id'])->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if (!empty($this->filters['sub_brand_id'])) {
            $productIds = \App\Models\ProductService::where('sub_brand_id', $this->filters['sub_brand_id'])->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }
        if (!empty($this->filters['bill_id'])) {
            $query->where('bill_id', $this->filters['bill_id']);
        }
        if (!empty($this->filters['invoice_id'])) {
            $query->where('invoice_id', $this->filters['invoice_id']);
        }
        if (!empty($this->filters['asn_id'])) {
            $query->where('asn_id', $this->filters['asn_id']);
        }
        // Chassis numbers: same paste-from-Excel parsing as controller (newlines, commas, tabs)
        if (!empty($this->filters['vins'])) {
            $vinsRaw = trim((string) $this->filters['vins']);
            $vins = array_values(array_filter(array_map('trim', preg_split('/[\r\n\t,]+/', $vinsRaw, -1, PREG_SPLIT_NO_EMPTY)), function ($v) {
                return $v !== '';
            }));
            if (!empty($vins)) {
                $query->whereIn('chassis_no', $vins);
            }
        }
        if (!empty($this->filters['customer_id'])) {
            $query->whereIn('id', function($sub) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                    ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                    ->where('inv2.customer_id', $this->filters['customer_id']);
            });
        }
        if (!empty($this->filters['vender_id'])) {
            $query->whereIn('id', function($sub) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('bill_products as bp2', 'bp2.sub_product_id', '=', 'sp.id')
                    ->join('bills as b2', 'b2.id', '=', 'bp2.bill_id')
                    ->where('b2.vender_id', $this->filters['vender_id']);
            });
        }
        
        // Filter by Purchase Status
        if (!empty($this->filters['purchase_status'])) {
            $query->where('flag', $this->filters['purchase_status']);
        }
        
        // Filter by Book Status
        if (!empty($this->filters['book_status'])) {
            $bookStatus = $this->filters['book_status'];
            $query->where(function($q) use ($bookStatus) {
                switch ($bookStatus) {
                    case 'free':
                        $q->where('booked', 0);
                        break;
                    case 'booked':
                        $q->where('booked', 1)
                          ->whereNotNull('invoice_id')
                          ->where(function($invCheck) {
                              $invCheck->whereHas('invoice', function($invQ) {
                                  $invQ->where('type', 'regular');
                              })->orWhereNull('invoice_id'); // Include records even if invoice relationship is missing
                          });
                        break;
                    case 'rented':
                        $q->where(function($rentQ) {
                            $rentQ->where(function($r1) {
                                $r1->where('booked', 1)
                                   ->whereNotNull('invoice_id')
                                   ->where(function($invCheck) {
                                       $invCheck->whereHas('invoice', function($invQ) {
                                           $invQ->where('type', 'rent');
                                       })->orWhereNull('invoice_id');
                                   });
                            })->orWhere(function($r2) {
                                $r2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->where(function($invCheck) {
                                       $invCheck->whereHas('invoice', function($invQ) {
                                           $invQ->where('type', 'rent');
                                       })->orWhereNull('invoice_id');
                                   });
                            });
                        });
                        break;
                    case 'sold':
                        $q->where(function($soldQ) {
                            $soldQ->where(function($s1) {
                                $s1->where('booked', 2)
                                   ->whereNull('invoice_id');
                            })->orWhere(function($s2) {
                                $s2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->where(function($invCheck) {
                                       $invCheck->whereHas('invoice', function($invQ) {
                                           $invQ->where('type', 'regular');
                                       })->orWhereNull('invoice_id');
                                   });
                            })->orWhere(function($s3) {
                                $s3->where('booked', 1)
                                   ->whereNotNull('pos_id');
                            });
                        });
                        break;
                    case 'delivered':
                        // Delivered is the else case - anything that doesn't match Free, Booked, Rented, or Sold
                        $q->where(function($delQ) {
                            // booked == 1 but not rented, not booked (regular invoice), and not sold (pos_id)
                            $delQ->where(function($d1) {
                                $d1->where('booked', 1)
                                   ->where(function($d1sub) {
                                       $d1sub->whereNull('invoice_id')
                                             ->orWhereDoesntHave('invoice', function($invQ) {
                                                 $invQ->whereIn('type', ['rent', 'regular']);
                                             });
                                   })
                                   ->whereNull('pos_id');
                            })
                            // booked == 2 but not sold and not rented
                            ->orWhere(function($d2) {
                                $d2->where('booked', 2)
                                   ->where(function($invCheck) {
                                       $invCheck->where(function($invSub) {
                                           $invSub->whereNotNull('invoice_id')
                                                  ->whereHas('invoice', function($invQ) {
                                                      $invQ->where('type', '!=', 'rent')
                                                           ->where('type', '!=', 'regular');
                                                  });
                                       })->orWhereNull('invoice_id');
                                   });
                            });
                        });
                        break;
                }
            });
        }

        $query->orderBy('created_at', 'desc');
        
        // Eager load relationships to prevent N+1 queries
        // IMPORTANT: Don't use selective column loading (id,name,sku) as it can cause issues during chunking
        // Load full relationships to ensure all data is available during map()
        // with() uses LEFT JOIN internally, so records without relationships are still included
        // This ensures ALL sub-products are exported, even if productService, invoice, bill, or warehouse relationships are missing
        $query->with([
            'productService.brand',
            'productService.subBrand',
            'bill',
            'invoice',
            'warehouse.country'
        ]);
        
        // Get all records - use get() instead of returning query builder
        // This ensures we have full control and can log everything
        $allRecords = $query->get();
        
        // For tracked IDs, verify quantities directly from database
        if (!empty($this->trackIds)) {
            foreach ($this->trackIds as $trackId) {
                $dbQuantity = DB::table('sub_products')
                    ->where('id', $trackId)
                    ->where('created_by', $this->creatorId)
                    ->value('quantity');
                
                $record = $allRecords->firstWhere('id', $trackId);
                $recordQuantity = $record ? ($record->attributes['quantity'] ?? $record->quantity ?? null) : null;
                
                \Log::info('StockReportExport: Quantity verification for TRACKED ID', [
                    'sub_product_id' => $trackId,
                    'quantity_from_db_direct' => $dbQuantity,
                    'quantity_in_collection' => $recordQuantity,
                    'record_exists_in_collection' => $record ? 'YES' : 'NO',
                ]);
            }
        }
        
        // Log the total count and specific IDs being processed
        $totalCount = $allRecords->count();
        $recordIds = $allRecords->pluck('id')->toArray();
        
        \Log::info('StockReportExport: Collection loaded', [
            'total_count' => $totalCount,
            'creator_id' => $this->creatorId,
            'filters' => $this->filters,
            'first_10_ids' => array_slice($recordIds, 0, 10),
            'last_10_ids' => array_slice($recordIds, -10),
        ]);
        
        // If tracking specific IDs, check if they're in the collection and log their quantities
        if (!empty($this->trackIds)) {
            $foundIds = [];
            $missingIds = [];
            $quantityInfo = [];
            
            foreach ($this->trackIds as $trackId) {
                $record = $allRecords->firstWhere('id', $trackId);
                if ($record) {
                    $foundIds[] = $trackId;
                    // Log detailed quantity information for tracked IDs
                    $quantityInfo[$trackId] = [
                        'quantity_from_attributes' => $record->attributes['quantity'] ?? 'NOT_SET',
                        'quantity_from_getAttribute' => $record->getAttribute('quantity') ?? 'NULL',
                        'quantity_from_property' => $record->quantity ?? 'NULL',
                        'quantity_type' => gettype($record->quantity ?? null),
                        'all_quantity_methods' => [
                            'attributes' => $record->attributes['quantity'] ?? null,
                            'getAttribute' => $record->getAttribute('quantity') ?? null,
                            'property' => $record->quantity ?? null,
                            'getRawOriginal' => method_exists($record, 'getRawOriginal') ? $record->getRawOriginal('quantity') : 'METHOD_NOT_EXISTS',
                        ],
                    ];
                } else {
                    $missingIds[] = $trackId;
                }
            }
            
            \Log::info('StockReportExport: Tracked IDs status with quantity details', [
                'tracked_ids' => $this->trackIds,
                'found_ids' => $foundIds,
                'missing_ids' => $missingIds,
                'quantity_info' => $quantityInfo,
            ]);
        }
        
        return $allRecords;
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Product',
            'Brand',
            'Model',
            'SKU',
            'Chassis No',
            'Sale Price',
        ];

        // Add Purchase Price if user has permission
        if ($this->user && $this->user->can('view_all_stock_columns')) {
            $headings[] = 'Purchase Price';
        }

        $headings = array_merge($headings, [
            'Quantity',
            'Purchase Status',
            'Book Status',
        ]);

        // Add Bill if user has permission
        if ($this->user && $this->user->can('view_all_stock_columns')) {
            $headings[] = 'Bill';
        }

        $headings[] = 'ASN';
        $headings[] = 'Invoice';
        $headings[] = 'Location';

        // Add custom field columns if user has permission
        if ($this->user && $this->user->can('view_all_stock_columns')) {
            foreach ($this->customFields as $customField) {
                $headings[] = $customField->name;
            }
        }

        $headings[] = 'Custom Fields';

        return $headings;
    }

    public function map($productService): array
    {
        // Log every record being processed to track which ones are included
        static $processedCount = 0;
        $processedCount++;
        
        $currentId = $productService->id ?? null;
        
        // Always log if this is a tracked ID
        if (!empty($this->trackIds) && in_array($currentId, $this->trackIds)) {
            \Log::info('StockReportExport: Processing TRACKED ID', [
                'record_number' => $processedCount,
                'sub_product_id' => $currentId,
                'product_no' => $productService->product_no ?? 'NULL',
                'quantity' => $productService->quantity ?? 'NULL',
                'created_by' => $productService->created_by ?? 'NULL',
            ]);
        }
        
        // Log every 1000th record for general tracking
        if ($processedCount % 1000 == 0) {
            \Log::info('StockReportExport: Processing record', [
                'record_number' => $processedCount,
                'sub_product_id' => $currentId,
            ]);
        }
        
        try {
            // Optimize product name building
            // Ensure we handle cases where relationships might not be loaded
            $ps = $productService->productService ?? null;
            $brandName = ($ps && isset($ps->brand) && $ps->brand) ? $ps->brand->name : '';
            $subBrandName = ($ps && isset($ps->subBrand) && $ps->subBrand) ? $ps->subBrand->name : '';
            $productName = $ps ? ($ps->name ?? '') : '';
        
        $productDisplay = '';
        if ($brandName) {
            $productDisplay = $brandName;
            if ($subBrandName) {
                $productDisplay .= '/' . $subBrandName;
            }
            if ($productName) {
                $productDisplay .= '/' . $productName;
            }
        } elseif ($subBrandName) {
            $productDisplay = $subBrandName;
            if ($productName) {
                $productDisplay .= '/' . $productName;
            }
        } else {
            $productDisplay = $productName ?: '-';
        }
        
        $row = [
            $productService->id,
            $productDisplay,
            $brandName ?: '-',
            $subBrandName ?: '-',
            ($ps && $ps->sku) ? $ps->sku : '-',
            $productService->product_no ?? '-',
            $this->user ? $this->user->priceFormat($productService->sale_price ?? 0) : number_format($productService->sale_price ?? 0, 2),
        ];

        // Add Purchase Price if user has permission (use cached permission)
        if ($this->canViewAllColumns) {
            $row[] = $this->user ? $this->user->priceFormat($productService->purchase_price ?? 0) : number_format($productService->purchase_price ?? 0, 2);
        }

        // Ensure quantity is always a number (not null or string) for proper Excel summing
        // IMPORTANT: For tracked IDs, ALWAYS query database directly to bypass any Eloquent issues
        $quantity = null;
        $isTrackedId = !empty($this->trackIds) && in_array($currentId, $this->trackIds);
        
        // For tracked IDs, ALWAYS query database directly to get the raw value
        if ($isTrackedId) {
            $rawQuantity = \DB::table('sub_products')
                ->where('id', $currentId)
                ->where('created_by', $this->creatorId)
                ->value('quantity');
            
            \Log::info('StockReportExport: Direct DB query for TRACKED ID quantity', [
                'sub_product_id' => $currentId,
                'quantity_from_db_direct' => $rawQuantity,
                'quantity_type_from_db' => gettype($rawQuantity),
                'quantity_from_attributes' => $productService->attributes['quantity'] ?? 'NOT_SET',
                'quantity_from_getAttribute' => $productService->getAttribute('quantity') ?? 'NULL',
                'quantity_from_property' => $productService->quantity ?? 'NULL',
            ]);
            
            // ALWAYS use the direct DB value for tracked IDs - don't trust Eloquent
            $quantity = $rawQuantity !== null ? $rawQuantity : 0;
            
            \Log::info('StockReportExport: Using direct DB quantity for TRACKED ID', [
                'sub_product_id' => $currentId,
                'quantity_used' => $quantity,
            ]);
        } else {
            // For non-tracked IDs, use normal methods
            // Try different methods to get quantity
            if (isset($productService->attributes['quantity'])) {
                $quantity = $productService->attributes['quantity'];
            } elseif (method_exists($productService, 'getAttribute')) {
                $quantity = $productService->getAttribute('quantity');
            } else {
                $quantity = $productService->quantity;
            }
        }
        
        // Log quantity for tracked IDs to debug
        if (!empty($this->trackIds) && in_array($currentId, $this->trackIds)) {
            \Log::info('StockReportExport: Quantity value for TRACKED ID', [
                'sub_product_id' => $currentId,
                'quantity_final_before_processing' => $quantity,
                'quantity_type' => gettype($quantity),
                'quantity_is_null' => is_null($quantity),
                'quantity_is_empty' => empty($quantity),
            ]);
        }
        
        // Handle different possible values - be very explicit
        // IMPORTANT: For tracked IDs, preserve the exact value from database
        if ($isTrackedId) {
            // For tracked IDs, we already have the direct DB value
            // Just ensure it's numeric format
            if ($quantity === null || $quantity === '') {
                $quantity = 0;
            } else {
                $quantity = is_numeric($quantity) ? (float)$quantity : 0;
            }
            
            \Log::info('StockReportExport: Final quantity for TRACKED ID before adding to row', [
                'sub_product_id' => $currentId,
                'final_quantity' => $quantity,
                'quantity_type' => gettype($quantity),
            ]);
        } else {
            // For non-tracked IDs, handle different possible values
            if ($quantity === null || $quantity === '' || $quantity === false) {
                $quantity = 0;
            } elseif (is_string($quantity)) {
                // If it's a string, try to convert it
                $quantity = is_numeric($quantity) ? (float)$quantity : 0;
            } elseif (is_bool($quantity)) {
                // Handle boolean (shouldn't happen but just in case)
                $quantity = $quantity ? 1 : 0;
            } else {
                // Ensure it's a numeric value - but preserve the actual value
                $quantity = is_numeric($quantity) ? (float)$quantity : 0;
            }
        }
        
        // Final validation - if still 0 but we know it should be 1, log it
        if ($quantity == 0 && $isTrackedId) {
            \Log::warning('StockReportExport: Quantity is 0 for TRACKED ID that should have value', [
                'sub_product_id' => $currentId,
                'final_quantity' => $quantity,
                'db_query_result' => \DB::table('sub_products')->where('id', $currentId)->value('quantity'),
            ]);
        }
        
        $row[] = $quantity;
        
        // Purchase Status (optimized)
        $flag = $productService->flag ?? 0;
        $flagLabels = [
            0 => 'Pending',
            1 => 'Purchased',
            2 => 'Cancelled',
            3 => 'Inventory'
        ];
        $purchaseStatus = $flagLabels[$flag] ?? 'Unknown';
        $row[] = $purchaseStatus;

        // Book Status (optimized)
        $bookStatus = 'Free';
        $invoice = $productService->invoice ?? null;
        $invoiceType = ($invoice && isset($invoice->type)) ? $invoice->type : null;
        
        if ($productService->booked == 1) {
            if ($invoiceType == 'rent') {
                $bookStatus = 'Rented';
            } elseif ($invoiceType == 'regular') {
                $bookStatus = 'Booked';
            } elseif ($productService->pos_id != null) {
                $bookStatus = 'Sold';
            }
        } elseif ($productService->booked == 2) {
            if ($invoiceType == 'rent') {
                $bookStatus = 'Rented';
            } elseif ($invoiceType == 'regular' || $productService->invoice_id == null) {
                $bookStatus = 'Sold';
            }
        } elseif ($productService->booked == 3) {
            $bookStatus = 'Delivered';
        }
        $row[] = $bookStatus;

        // Add Bill if user has permission (use cached permission)
        if ($this->canViewAllColumns) {
            $row[] = ($productService->bill_id && $productService->bill) 
                ? ($this->user ? $this->user->billNumberFormat($productService->bill->bill_id) : $productService->bill->bill_id)
                : '-';
        }

        // ASN
        $row[] = !empty($productService->asn_id)
            ? ($this->user && method_exists($this->user, 'asnNumberFormat')
                ? $this->user->asnNumberFormat($productService->asn_id)
                : $productService->asn_id)
            : '-';

        // Invoice (optimized)
        if ($productService->invoice_id && $invoice) {
            $row[] = $this->user ? $this->user->invoiceNumberFormat($invoice->invoice_id) : ($invoice->invoice_id ?? '-');
        } elseif ($productService->pos_id) {
            $row[] = 'POS#' . sprintf('%05d', $productService->pos_id);
        } else {
            $row[] = '-';
        }

        // Location (optimized)
        $warehouse = $productService->warehouse ?? null;
        $row[] = ($warehouse && isset($warehouse->country) && $warehouse->country) 
            ? ($warehouse->name ?? '') . '/' . ($warehouse->country->name ?? '') 
            : ($warehouse ? ($warehouse->name ?? '') : '');

        // Add custom field values if user has permission (use cached permission and array)
        if ($this->canViewAllColumns && !empty($this->customFieldsArray)) {
            $recordCustomValues = isset($this->customFieldValues[$productService->id]) 
                ? $this->customFieldValues[$productService->id] 
                : [];
            foreach ($this->customFieldsArray as $customField) {
                $fieldId = is_object($customField) ? $customField->id : $customField['id'];
                $row[] = isset($recordCustomValues[$fieldId]) ? $recordCustomValues[$fieldId] : '-';
            }
        }

        // Custom Fields (combined) - optimized
        $customFieldsText = '';
        if (!empty($this->customFieldsArray) && isset($this->customFieldValues[$productService->id])) {
            $values = [];
            $recordCustomValues = $this->customFieldValues[$productService->id];
            foreach ($this->customFieldsArray as $customField) {
                $fieldId = is_object($customField) ? $customField->id : $customField['id'];
                $fieldName = is_object($customField) ? $customField->name : $customField['name'];
                if (isset($recordCustomValues[$fieldId])) {
                    $val = $recordCustomValues[$fieldId];
                    if ($val !== null && $val !== '') {
                        $values[] = $fieldName . ': ' . $val;
                    }
                }
            }
            if (!empty($values)) {
                $customFieldsText = implode('; ', $values);
            }
        }
        $row[] = $customFieldsText ?: '-';

        // Log successful mapping for tracked IDs with quantity info
        if (!empty($this->trackIds) && in_array($currentId, $this->trackIds)) {
            // Find quantity column index (after Purchase Price if exists)
            $quantityIndex = $this->canViewAllColumns ? 7 : 6; // Adjust based on columns before quantity
            $quantityInRow = $row[$quantityIndex] ?? 'NOT_FOUND';
            
            \Log::info('StockReportExport: Successfully mapped TRACKED ID', [
                'sub_product_id' => $currentId,
                'row_count' => count($row),
                'first_column' => $row[0] ?? 'NULL',
                'quantity_in_row' => $quantityInRow,
                'quantity_index' => $quantityIndex,
                'row_data' => $row, // Full row for debugging
            ]);
        }

        return $row;
        } catch (\Exception $e) {
            // Log the error but still return a row to ensure the record is included
            \Log::error('StockReportExport: Error mapping sub-product', [
                'sub_product_id' => $currentId ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            // Log the error but still return a row to ensure the record is included
            \Log::error('Error mapping sub-product in export', [
                'sub_product_id' => $productService->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a minimal row with the ID so the record is not lost
            $minimalRow = [
                $productService->id ?? 'ERROR',
                'ERROR: ' . $e->getMessage(),
                '-',
                '-',
                $productService->product_no ?? '-',
                '0.00',
            ];
            
            if ($this->canViewAllColumns) {
                $minimalRow[] = '0.00';
            }
            
            $minimalRow = array_merge($minimalRow, [
                $productService->quantity ?? 0,
                'Unknown',
                'Unknown',
            ]);
            
            if ($this->canViewAllColumns) {
                $minimalRow[] = '-';
            }
            
            $minimalRow[] = '-'; // ASN
            $minimalRow[] = '-'; // Invoice
            $minimalRow[] = '-'; // Location
            
            if ($this->canViewAllColumns && !empty($this->customFieldsArray)) {
                foreach ($this->customFieldsArray as $customField) {
                    $minimalRow[] = '-';
                }
            }
            
            $minimalRow[] = '-'; // Custom Fields
            
            return $minimalRow;
        }
    }

    /**
     * Register events to format quantity column as numeric
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Find the Quantity column by searching the header row
                $headerRow = 1;
                $highestColumn = $event->sheet->getHighestColumn();
                $quantityColumnIndex = null;
                
                // Search for "Quantity" header
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $headerValue = $event->sheet->getCell($col . $headerRow)->getValue();
                    if (strtolower(trim($headerValue)) === 'quantity') {
                        $quantityColumnIndex = $col;
                        break;
                    }
                }
                
                // If found, format all quantity cells as numeric
                if ($quantityColumnIndex) {
                    $highestRow = $event->sheet->getHighestRow();
                    
                    // Format quantity column as numeric for all data rows (skip header row)
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $cell = $quantityColumnIndex . $row;
                        $cellObject = $event->sheet->getCell($cell);
                        
                        // IMPORTANT: Get the calculated value first, then fall back to value
                        // This preserves the actual value that was set in map()
                        $calculatedValue = $cellObject->getCalculatedValue();
                        $value = $calculatedValue !== null ? $calculatedValue : $cellObject->getValue();
                        
                        // Check if this is a tracked ID
                        $isTrackedRow = false;
                        $trackedId = null;
                        if (!empty($this->trackIds)) {
                            // Try to get the ID from the first column (column A)
                            $idCell = $event->sheet->getCell('A' . $row)->getValue();
                            if (in_array($idCell, $this->trackIds)) {
                                $isTrackedRow = true;
                                $trackedId = $idCell;
                                
                                // For tracked IDs, get the value directly from database to verify
                                $dbQuantity = DB::table('sub_products')
                                    ->where('id', $idCell)
                                    ->value('quantity');
                                
                                \Log::info('StockReportExport: AfterSheet formatting quantity for TRACKED ID', [
                                    'sub_product_id' => $idCell,
                                    'row' => $row,
                                    'cell' => $cell,
                                    'value_before_formatting' => $cellObject->getValue(),
                                    'calculated_value' => $calculatedValue,
                                    'value_used' => $value,
                                    'value_type' => gettype($value),
                                    'db_quantity' => $dbQuantity,
                                ]);
                                
                                // For tracked IDs, use the database value if cell value doesn't match
                                if ($dbQuantity !== null && ($value == 0 || $value == '' || $value == null) && $dbQuantity != 0) {
                                    \Log::warning('StockReportExport: Cell value is 0 but DB has value for TRACKED ID - correcting', [
                                        'sub_product_id' => $idCell,
                                        'cell_value' => $value,
                                        'db_value' => $dbQuantity,
                                    ]);
                                    $value = $dbQuantity;
                                }
                            }
                        }
                        
                        // IMPORTANT: Only format as numeric, don't change the value
                        // If value exists (even if 0), preserve it
                        if ($value !== null && $value !== '') {
                            // Convert to float/numeric but preserve the actual value
                            $numericValue = is_numeric($value) ? (float)$value : (is_string($value) && is_numeric(trim($value)) ? (float)trim($value) : 0);
                            
                            // Set the value explicitly as numeric type
                            $event->sheet->getCell($cell)->setValueExplicit(
                                $numericValue,
                                DataType::TYPE_NUMERIC
                            );
                            
                            // Log for tracked IDs after setting
                            if ($isTrackedRow) {
                                $finalValue = $event->sheet->getCell($cell)->getValue();
                                \Log::info('StockReportExport: AfterSheet set quantity for TRACKED ID', [
                                    'sub_product_id' => $trackedId,
                                    'value_set' => $numericValue,
                                    'value_after_set' => $finalValue,
                                ]);
                            }
                        } elseif ($value === 0 || $value === '0') {
                            // Explicitly handle 0 values
                            $event->sheet->getCell($cell)->setValueExplicit(0, DataType::TYPE_NUMERIC);
                        }
                        // Don't set empty values to 0 - they might be legitimately empty
                        // Only format the data type if value exists
                    }
                }
            },
        ];
    }

    /**
     * Test method to check why specific sub-product IDs are not included in export
     * Usage: StockReportExport::testSubProductIds([123, 456, 789], $userId, $filters)
     */
    public static function testSubProductIds(array $subProductIds, $userId, $filters = [])
    {
        $user = \App\Models\User::find($userId);
        $creatorId = $user ? $user->creatorId() : $userId;
        
        $results = [];
        
        foreach ($subProductIds as $subProductId) {
            $subProduct = SubProduct::find($subProductId);
            
            if (!$subProduct) {
                $results[$subProductId] = [
                    'exists' => false,
                    'reason' => 'Sub-product does not exist in database'
                ];
                continue;
            }
            
            // Get quantity using multiple methods to see which one works
            $quantityMethods = [
                'quantity_property' => $subProduct->quantity ?? null,
                'quantity_getAttribute' => $subProduct->getAttribute('quantity') ?? null,
                'quantity_attributes' => $subProduct->attributes['quantity'] ?? null,
                'quantity_getRawOriginal' => method_exists($subProduct, 'getRawOriginal') ? $subProduct->getRawOriginal('quantity') : null,
            ];
            
            $result = [
                'id' => $subProductId,
                'exists' => true,
                'created_by' => $subProduct->created_by,
                'creator_id' => $creatorId,
                'matches_creator' => $subProduct->created_by == $creatorId,
                'product_id' => $subProduct->product_id,
                'product_no' => $subProduct->chassis_no,
                'quantity' => $subProduct->quantity,
                'quantity_methods' => $quantityMethods,
                'quantity_type' => gettype($subProduct->quantity ?? null),
                'flag' => $subProduct->flag,
                'booked' => $subProduct->booked,
                'invoice_id' => $subProduct->invoice_id,
                'bill_id' => $subProduct->bill_id,
                'warehouse_id' => $subProduct->warehouse_id,
                'excluded_by_filters' => [],
                'query_result' => null
            ];
            
            // Check if it matches creator
            if ($subProduct->created_by != $creatorId) {
                $result['excluded_by_filters'][] = 'created_by mismatch';
            }
            
            // Build the same export and get collection
            $export = new self($userId, $filters, [], []);
            $collection = $export->collection();
            
            // Check if this specific ID is in the collection
            $queryResult = $collection->firstWhere('id', $subProductId);
            $result['query_result'] = $queryResult ? 'INCLUDED' : 'EXCLUDED';
            $result['collection_count'] = $collection->count();
            
            // Also test if the record can be mapped (this is the actual export process)
            if ($queryResult) {
                try {
                    // Test the map method to see if it processes correctly
                    $mappedRow = $export->map($queryResult);
                    $result['map_result'] = 'SUCCESS';
                    $result['mapped_row_count'] = count($mappedRow);
                    $result['mapped_id'] = $mappedRow[0] ?? null; // First column should be ID
                } catch (\Exception $e) {
                    $result['map_result'] = 'ERROR';
                    $result['map_error'] = $e->getMessage();
                    $result['map_trace'] = $e->getTraceAsString();
                }
            } else {
                // Record is not in collection - check why
                $result['not_in_collection_reason'] = 'Record not found in collection after query execution';
            }
            
            // Check each filter condition
            if (!empty($filters['q'])) {
                $q = trim($filters['q']);
                $matches = false;
                if (stripos($subProduct->chassis_no ?? '', $q) !== false) {
                    $matches = true;
                }
                if (stripos((string)($subProduct->quantity ?? ''), $q) !== false) {
                    $matches = true;
                }
                if ($subProduct->productService) {
                    if (stripos($subProduct->productService->name ?? '', $q) !== false) {
                        $matches = true;
                    }
                    if (stripos($subProduct->productService->sku ?? '', $q) !== false) {
                        $matches = true;
                    }
                }
                if (!$matches) {
                    $result['excluded_by_filters'][] = 'search query (q)';
                }
            }
            
            if (!empty($filters['category_id'])) {
                if ($subProduct->product_id) {
                    $product = \App\Models\ProductService::find($subProduct->product_id);
                    if (!$product || $product->category_id != $filters['category_id']) {
                        $result['excluded_by_filters'][] = 'category_id filter';
                    }
                } else {
                    $result['excluded_by_filters'][] = 'category_id filter (no product_id)';
                }
            }
            
            if (!empty($filters['product_id'])) {
                if ($subProduct->product_id != $filters['product_id']) {
                    $result['excluded_by_filters'][] = 'product_id filter';
                }
            }
            
            if (!empty($filters['brand_id'])) {
                if ($subProduct->product_id) {
                    $product = \App\Models\ProductService::find($subProduct->product_id);
                    if (!$product || $product->brand_id != $filters['brand_id']) {
                        $result['excluded_by_filters'][] = 'brand_id filter';
                    }
                } else {
                    $result['excluded_by_filters'][] = 'brand_id filter (no product_id)';
                }
            }
            
            if (!empty($filters['warehouse_id'])) {
                if ($subProduct->warehouse_id != $filters['warehouse_id']) {
                    $result['excluded_by_filters'][] = 'warehouse_id filter';
                }
            }
            
            if (!empty($filters['bill_id'])) {
                if ($subProduct->bill_id != $filters['bill_id']) {
                    $result['excluded_by_filters'][] = 'bill_id filter';
                }
            }
            
            if (!empty($filters['invoice_id'])) {
                if ($subProduct->invoice_id != $filters['invoice_id']) {
                    $result['excluded_by_filters'][] = 'invoice_id filter';
                }
            }
            
            if (!empty($filters['purchase_status'])) {
                if ($subProduct->flag != $filters['purchase_status']) {
                    $result['excluded_by_filters'][] = 'purchase_status filter';
                }
            }
            
            if (!empty($filters['book_status'])) {
                $bookStatus = $filters['book_status'];
                $matches = false;
                
                switch ($bookStatus) {
                    case 'free':
                        $matches = $subProduct->booked == 0;
                        break;
                    case 'booked':
                        $matches = $subProduct->booked == 1 
                            && $subProduct->invoice_id 
                            && $subProduct->invoice 
                            && $subProduct->invoice->type == 'regular';
                        break;
                    case 'rented':
                        $matches = ($subProduct->booked == 1 || $subProduct->booked == 2)
                            && $subProduct->invoice_id
                            && $subProduct->invoice
                            && $subProduct->invoice->type == 'rent';
                        break;
                    case 'sold':
                        $matches = ($subProduct->booked == 2 && !$subProduct->invoice_id)
                            || ($subProduct->booked == 2 && $subProduct->invoice_id && $subProduct->invoice && $subProduct->invoice->type == 'regular')
                            || ($subProduct->booked == 1 && $subProduct->pos_id);
                        break;
                }
                
                if (!$matches) {
                    $result['excluded_by_filters'][] = 'book_status filter (' . $bookStatus . ')';
                }
            }
            
            $results[$subProductId] = $result;
        }
        
        return $results;
    }
}

