<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockCountErrorsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $errorItems;

    public function __construct(array $errorItems)
    {
        $this->errorItems = collect($errorItems);
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->errorItems;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Row Number',
            'Product No',
            'Quantity',
            'Error Type',
            'Error Message',
        ];
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
        return [
            $item['row'] ?? '',
            $item['product_no'] ?? '',
            $item['quantity'] ?? '',
            $item['error_type'] ?? '',
            $item['error_message'] ?? '',
        ];
    }
}
