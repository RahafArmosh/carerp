<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class QuotationItemsExport implements FromCollection, WithHeadings
{
    protected $quotation;
    protected $items;

    public function __construct($quotation)
    {
        $this->quotation = $quotation;
    }

    public function collection()
    {
        $this->items = $this->quotation->items()
            ->where('is_alternative', 0)
            ->with('productService')
            ->get();

        return $this->items->map(function ($item) {
            return [
                'part_number' => $item->partnumber,
                'quantity' => $item->re_quantity,
                'form_state' => $item->form_state, // keep for styling reference
            ];
        });
    }

    public function headings(): array
    {
        return ['Part number', 'Quantity', 'Form State'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $rowNumber = 1; // row 1 = headings

                foreach ($this->items as $item) {
                    if ($item->form_state === 'out of system') {
                        $event->sheet->getStyle("A{$rowNumber}:C{$rowNumber}")
                            ->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => 'FF0000'],
                                ],
                            ]);
                    }

                    $rowNumber++;
                }
            },
        ];
    }
}
