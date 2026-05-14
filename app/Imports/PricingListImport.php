<?php

namespace App\Imports;

use App\Models\ProductService;
use App\Models\PricingList;
use App\Models\PricingListType;
use App\Models\SubProduct;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use DB;

class PricingListImport implements ToCollection, WithHeadingRow
{
    protected int $warehouseId;
    protected int $creatorId;

    public function __construct(int $warehouseId, int $creatorId)
    {
        $this->warehouseId = $warehouseId;
        $this->creatorId = $creatorId;
    }

    public function collection(Collection $rows)
    {
        // [name => id]
        $pricingTypes = PricingListType::where('created_by', $this->creatorId)->pluck('id', 'name')->toArray();
        $errors = [];
        $processedCount = 0;
        $matchedPartCount = 0;
        $updatedPricesCount = 0;

        if (empty($pricingTypes)) {
            throw ValidationException::withMessages([
                'file' => ['No pricing list types found for this company. Please create pricing list types first.'],
            ]);
        }

        // Build fast lookup maps for large imports (e.g. 1700 rows).
        $identifiers = [];
        $rowMap = []; // part_number => [rows]

        $rowNumber = 1;
        $getCell = function ($row, string $key) {
            if ($row instanceof \Illuminate\Support\Collection) {
                return $row->get($key);
            }
            if (is_array($row)) {
                return $row[$key] ?? null;
            }
            return null;
        };
        foreach ($rows as $index => $row) {

            $rowNumber = $index + 2; // because heading row is 1

            $val =
                $getCell($row, 'part_number')
                ?? $getCell($row, 'part number')
                ?? $getCell($row, 'sku')
                ?? $getCell($row, 'part_no')
                ?? $getCell($row, 'part no');

            $val = trim((string) $val);

            if ($val !== '') {
                $identifiers[] = $val;
                $rowMap[$val][] = $rowNumber; // store row number for this part number
            }
        }
        $identifiers = array_values(array_unique($identifiers));

        $duplicateErrors = [];

        foreach ($rowMap as $partNumber => $rowsList) {
            if (count($rowsList) > 1) {
                $duplicateErrors[] = "Part number '{$partNumber}' duplicated in rows: " . implode(', ', $rowsList);
            }
        }

        if (!empty($duplicateErrors)) {
            throw ValidationException::withMessages([
                'file' => $duplicateErrors
            ]);
        }

        // Map by SKU (part_number) first.
        $productsBySku = ProductService::where('created_by', $this->creatorId)
            ->whereIn('sku', $identifiers)
            ->get()
            ->keyBy('sku');

        // Map barcode/product_no -> product_id (then we can load ProductService by id).
        $productIdByBarcode = SubProduct::where('created_by', $this->creatorId)
            ->whereIn('chassis_no', $identifiers)
            ->pluck('product_id', 'chassis_no')
            ->toArray();

        $productsById = ProductService::where('created_by', $this->creatorId)
            ->whereIn('id', array_values(array_unique(array_filter($productIdByBarcode))))
            ->get()
            ->keyBy('id');

        DB::transaction(function () use (
            $rows,
            $pricingTypes,
            $productsBySku,
            $productIdByBarcode,
            $productsById,
            &$errors,
            &$processedCount,
            &$matchedPartCount,
            &$updatedPricesCount
        ) {

            foreach ($rows as $index => $row) {

                $rowNumber = $index + 2;
                $processedCount++;

                // Support common part number headings (template uses "part_number")
                $partNumberKeys = ['part_number', 'part number', 'sku', 'partnumber', 'part_no', 'part no'];
                $partNumber = '';
                foreach ($partNumberKeys as $k) {
                    $key = Str::lower(Str::snake((string) $k));
                    $raw = null;
                    if ($row instanceof \Illuminate\Support\Collection) {
                        if ($row->has($key)) {
                            $raw = $row->get($key);
                        }
                    } elseif (is_array($row)) {
                        if (array_key_exists($key, $row)) {
                            $raw = $row[$key];
                        }
                    }
                    if ($raw !== null && $raw !== '') {
                        $partNumber = trim((string) $raw);
                        break;
                    }
                }

                if (!$partNumber) {
                    continue;
                }

                $product = null;
                // 1) Try SKU directly
                $product = $productsBySku->get($partNumber);
                // 2) Fallback: treat partNumber as barcode (sub_products.chassis_no)
                if (!$product && isset($productIdByBarcode[$partNumber])) {
                    $product = $productsById->get((int) $productIdByBarcode[$partNumber]);
                }

                if (!$product) {
                    $errors[] = "Row {$rowNumber}: Part number {$partNumber} not found.";
                    continue;
                }
                $matchedPartCount++;

                foreach ($pricingTypes  as $typeName => $typeId) {
                    // Normalize possible heading keys for this type name.
                    // WithHeadingRow typically converts headings to snake_case, but type names can contain spaces/symbols.
                    $keysToTry = array_values(array_unique([
                        strtolower($typeName),
                        Str::lower(Str::snake($typeName)),
                        Str::lower(Str::slug($typeName, '_')),
                    ]));

                    $value = null;
                    foreach ($keysToTry as $k) {
                        $raw = null;
                        if ($row instanceof \Illuminate\Support\Collection) {
                            if ($row->has($k)) {
                                $raw = $row->get($k);
                            }
                        } elseif (is_array($row)) {
                            if (array_key_exists($k, $row)) {
                                $raw = $row[$k];
                            }
                        }
                        if ($raw !== null && $raw !== '') {
                            $value = $raw;
                            break;
                        }
                    }

                    if ($value === null) {
                        continue;
                    }

                    if (!is_numeric($value)) {
                        $errors[] = "Row {$rowNumber}: Invalid price for {$typeName}.";
                        continue;
                    }

                    PricingList::updateOrCreate(
                        [
                            'pricing_list_type_id' => $typeId,
                            'product_service_id'   => $product->id,
                            'warehouse_id'         => $this->warehouseId,
                        ],
                        [
                            'current_price' => (float) $value,
                            'created_by'    => $this->creatorId,
                        ]
                    );
                    $updatedPricesCount++;
                }
            }
        });

        if ($processedCount > 0 && $matchedPartCount === 0) {
            $errors[] = "No valid part numbers were found. Make sure the sheet has a 'part_number' (or SKU) column and values match existing product SKUs.";
        }

        if ($processedCount > 0 && $updatedPricesCount === 0) {
            $errors[] = "No prices were imported. Make sure the pricing columns match your Pricing List Type names (as in the downloaded template) and contain numeric values.";
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors
            ]);
        }
    }
}
