<?php

namespace App\Imports;

use App\Models\PriceRule;
use App\Models\SubProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class PriceRulesImport implements ToCollection, WithHeadingRow
{
    public function __construct(protected int $creatorId)
    {
    }

    public function collection(Collection $rows)
    {
        $errors = [];
        $payload = [];
        $processedCount = 0;

        foreach ($rows as $i => $row) {

            $rowNo = $i + 2;
            $processedCount++;

            // Support flexible heading names
            $part = '';
            foreach (['part_number', 'part number', 'sku', 'part_no', 'part no'] as $k) {
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
                    $part = strtoupper(trim((string) $raw));
                    break;
                }
            }

            $get = function (string $key) use ($row) {
                if ($row instanceof \Illuminate\Support\Collection) {
                    return $row->get($key);
                }
                if (is_array($row)) {
                    return $row[$key] ?? null;
                }
                return null;
            };

            $warehouseId = $get('warehouse_id') ?? $get('warehouse');
            $priceMode = strtolower(trim((string) ($get('price_mode') ?? $get('mode') ?? '')));
            $value = $get('value') ?? $get('price_value');
            $baseSource = strtolower(trim((string) ($get('base_price_source') ?? $get('base_source') ?? 'sale')));

            // basic validation
            if (!$part || !$warehouseId || !$priceMode || $value === null || $value === '' || !is_numeric($value)) {
                $errors[] = "Row {$rowNo}: Missing or invalid data.";
                continue;
            }

            if (!in_array($priceMode, ['fixed', 'discount', 'formula'])) {
                $errors[] = "Row {$rowNo}: Invalid price_mode.";
                continue;
            }

            if (!in_array($baseSource, ['sale', 'purchase'])) {
                $errors[] = "Row {$rowNo}: Invalid base_price_source.";
                continue;
            }

            // get ALL subproducts for this part + warehouse
            $subProducts = SubProduct::where('chassis_no', $part)
                ->where('warehouse_id', $warehouseId)
                ->where('created_by', $this->creatorId)
                ->get();

            if ($subProducts->isEmpty()) {
                $errors[] = "Row {$rowNo}: Part not found in this warehouse.";
                continue;
            }

            $payload[] = [
                'subProducts' => $subProducts,
                'warehouseId' => $warehouseId,
                'priceMode' => $priceMode,
                'value' => (float) $value,
                'baseSource' => $baseSource,
            ];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors
            ]);
        }

        DB::transaction(function () use ($payload) {

            foreach ($payload as $row) {

                // remove existing price rules (if any)
                $oldRuleIds = $row['subProducts']
                    ->pluck('price_rule_id')
                    ->filter()
                    ->unique();

                if ($oldRuleIds->isNotEmpty()) {
                    PriceRule::whereIn('id', $oldRuleIds)->delete();
                }
                $prodcutservice = $row['subProducts']->first()->productService;

                // create ONE price rule
                $rule = PriceRule::create([
                    'warehouse_id' => $row['warehouseId'],
                    'apply_to' => 'product',
                    "target_id" =>$prodcutservice->id,
                    'price_mode' => $row['priceMode'],
                    'value' => $row['value'],
                    'apply_99' => 0,
                    'base_price_source' => $row['baseSource'],
                    'created_by' => $this->creatorId,
                ]);

                // attach rule to ALL subproducts
                SubProduct::whereIn(
                    'id',
                    $row['subProducts']->pluck('id')
                )->update([
                    'price_rule_id' => $rule->id
                ]);
            }
        });

        // If file had rows but none produced payload, fail loudly (avoid silent "success")
        if ($processedCount > 0 && empty($payload)) {
            throw ValidationException::withMessages([
                'file' => [
                    "No rules were imported. Make sure the sheet has columns: part_number, warehouse_id, price_mode, value, base_price_source (or download the template).",
                ],
            ]);
        }
    }
}
