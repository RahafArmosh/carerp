<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AltPartNumbersTemplateExport implements FromArray, WithHeadings, WithColumnFormatting
{
    /**
     * Define the headings for the XLSX template.
     */
    public function headings(): array
    {
        return [
            'part_number',
            'alternative_part_number',
        ];
    }

    /**
     * Example array to show users how to fill the template.
     */
    public function array(): array
    {
        return [
            ['ABC123', 'XYZ789'],
            ['ABC123', 'DEF456'],
        ];
    }

    /**
     * Force columns to text format so Excel doesn't auto-convert.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
