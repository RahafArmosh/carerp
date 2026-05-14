<?php

namespace App\Imports;

use App\Models\AltPartNumber;
use App\Models\ProductService;
use App\Models\SubProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AltPartNumbersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $errors = [];
        $raw = []; // temporary map for normalization

        foreach ($rows as $index => $row) {

            $rowNumber = $index + 2; // Excel rows start at 2 due to headings

            $partNo = strtoupper(trim($row['part_number'] ?? ''));
            $altNo  = strtoupper(trim($row['alternative_part_number'] ?? ''));

            if (!$partNo || !$altNo) {
                $errors[] = "Row {$rowNumber}: Part number or alternative is missing.";
                continue;
            }

            if ($partNo === $altNo) {
                $errors[] = "Row {$rowNumber}: Part number and alternative cannot be the same.";
                continue;
            }

            // Validate existence in SubProduct
            $part = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $partNo)->first();
            $alt  = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $altNo)->first();

            if (!$part) {
                $errors[] = "Row {$rowNumber}: Part number {$partNo} does not exist in the system.";
                continue;
            }

            if (!$alt) {
                $errors[] = "Row {$rowNumber}: Alternative part number {$altNo} does not exist in the system.";
                continue;
            }

            $priority = isset($row['priority']) && is_numeric($row['priority'])
                ? (int) $row['priority']
                : 1;

            $bothway = !empty($row['bothway']);

            // Build raw map for normalization
            $raw[$partNo][] = $altNo;
            if ($bothway) {
                $raw[$altNo][] = $partNo;
            }

            // Optional: store priority in another map if needed
            $priorityMap[$partNo][$altNo] = $priority;
            if ($bothway) {
                $priorityMap[$altNo][$partNo] = $priority;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }

        // Normalize all alternatives transitively
        $normalized = $this->normalizeAlternatives($raw);

        // Insert into DB
        DB::transaction(function () use ($normalized, $priorityMap) {
            foreach ($normalized as $part => $alts) {
                foreach ($alts as $alt) {

                    AltPartNumber::firstOrCreate(
                        [
                            'part_number' => $part,
                            'alternative_part_number' => $alt,
                        ],
                        [
                            'priority'   => $priorityMap[$part][$alt] ?? 1,
                            'is_active'  => 1,
                            'created_by' => \Auth::user()->creatorId(),
                        ]
                    );
                }
            }
        });
    }
    protected function normalizeAlternatives(array $alternatives)
    {
        $graph = [];

        // Symmetric edges
        foreach ($alternatives as $part => $alts) {
            if (!isset($graph[$part])) $graph[$part] = [];
            foreach ($alts as $alt) {
                $graph[$part][$alt] = true;
                if (!isset($graph[$alt])) $graph[$alt] = [];
                $graph[$alt][$part] = true;
            }
        }

        // Transitive closure
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($graph as $part => $alts) {
                foreach ($alts as $alt => $_) {
                    foreach ($graph[$alt] as $altOfAlt => $_) {
                        if ($altOfAlt !== $part && !isset($graph[$part][$altOfAlt])) {
                            $graph[$part][$altOfAlt] = true;
                            $graph[$altOfAlt][$part] = true;
                            $changed = true;
                        }
                    }
                }
            }
        }

        // Convert to simple array
        $normalized = [];
        foreach ($graph as $part => $alts) {
            $normalized[$part] = array_keys($alts);
        }

        return $normalized;
    }
}
