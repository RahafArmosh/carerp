<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class QoutaionsCreatExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([
            [
                'part_number' => 'PN-001',
                'quantity'    => 10,
            ],
            [
                'part_number' => 'PN-002',
                'quantity'    => 5,
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'part_number',
            'quantity',
        ];
    }
}
