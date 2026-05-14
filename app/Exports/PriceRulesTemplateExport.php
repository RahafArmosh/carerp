<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class PriceRulesTemplateExport implements FromArray, WithHeadings, WithStrictNullComparison
{
    /**
     * Excel column headers
     */
    public function headings(): array
    {
        return [
            'part_number',
            'warehouse_id',
            'price_mode',        // fixed | discount | formula
            'value',
            'base_price_source', // sale | purchase
        ];
    }

    /**
     * Template rows (leave empty or add example)
     */
    public function array(): array
    {
        // empty template (recommended)
        // return [];

        // OR example row (optional)
        
        return [
            [
                'ABC123',
                1,
                'discount',
                10,
                'sale',
            ], [
                'BCD123',
                1,
                'fixed',
                100,
                'sale',
            ], [
                'CDE123',
                1,
                'formula',
                25,
                'purchase',
            ],
        ];
        
    }
}
