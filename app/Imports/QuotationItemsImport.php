<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;
use App\Models\ProductService;
class QuotationItemsImport implements ToCollection, WithHeadingRow
{
    protected array $items = [];

    public function collection(Collection $rows)
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $sku = trim($row['part_number'] ?? '');
            $qty = (int) ($row['quantity'] ?? 0);

            if (!$sku || $qty <= 0) {
                $errors[] = "Row {$rowNo}: Invalid partnumber or quantity.";
                continue;
            }

            // if (!ProductService::where('sku', $sku)->exists()) {
            //     $errors[] = "Row {$rowNo}: SKU {$sku} not found.";
            //     continue;
            // }

            // merge duplicates
            $this->items[$sku] = ($this->items[$sku] ?? 0) + $qty;
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
    }

    public function getItems(): array
    {
        return $this->items; // [sku => qty]
    }
}
