<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AlternativePartsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            
            'part_number',
            'alternative_part_number',
            'priority',
            'is_active',
        ];
    }

    public function array(): array
    {
        return [
            [
                '04111-0C098',
                '04465-0K360',
                1,
                1,
            ],
        ];
    }
}
