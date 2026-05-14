<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QuotationShowExport implements FromArray, WithStyles
{
    protected Quotation $quotation;
    protected array $altRows = [];

    public function __construct(Quotation $quotation)
    {
        $this->quotation = $quotation->load([
            'customer',
            'warehouse',
            'priceGroup',
            'items.productService',
        ]);
    }

    public function array(): array
    {
        $rows = [];

        /* =========================
         |  QUOTATION INFO
         ========================= */
        $rows[] = ['Quotation No', $this->quotation->quotation_no];
        $rows[] = ['Customer', $this->quotation->customer->name ?? ''];
        $rows[] = ['Quotation Date', $this->quotation->quotation_date];
        $rows[] = ['Currency', "AED"];

        $rows[] = [];

        /* =========================
         |  TABLE HEADER
         ========================= */
        $headerRow = count($rows) + 1;

        $rows[] = [
            'SKU',
            'Product Name',
            'Quantity',
            'Available Qty',
            'Unit Price',
            'Total',
            'Type',
        ];

        $rowIndex = $headerRow + 1;

        /* =========================
         |  GROUP ITEMS
         ========================= */

        $mainItems = $this->quotation->items->where('is_alternative', 0);

        foreach ($mainItems as $mainItem) {

            // MAIN ITEM
            if ($mainItem->form_state == 'out of system'){
                
                $rows[] = [
                    $mainItem->partnumber,
                    "N/A",
                    $mainItem->re_quantity,
                    0,
                    0,
                    0,
                    'MAIN',
                ];
            }else{
                $rows[] = [
                    $mainItem->partnumber,
                    $mainItem->productService->name,
                    $mainItem->re_quantity,
                    $mainItem->av_quantity ?? '',
                    $mainItem->unit_price,
                    $mainItem->av_quantity * $mainItem->unit_price,
                    'MAIN',
                ];
            }

            $rowIndex++;

            // ALTERNATIVES UNDER IT
            $alternatives = $this->quotation->items
                ->where('is_alternative', 1)
                ->where('parent_id', $mainItem->id);

            foreach ($alternatives as $alt) {

                $rows[] = [
                    $alt->productService->sku,
                    '↳ ' . $alt->productService->name,
                    $alt->re_quantity,
                    $alt->av_quantity ?? '',
                    $alt->unit_price,
                    $alt->av_quantity * $alt->unit_price,
                    'ALT',
                ];

                $this->altRows[] = count($rows);
                $rowIndex++;
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        /* Quotation labels */
        foreach (range(1, 4) as $row) {
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        }

        /* Header */
        $sheet->getStyle("A5:G5")->getFont()->setBold(true);

        /* Alternative rows → light gray */
        foreach ($this->altRows as $row) {
            $Trow  = $row - 1;
            $sheet->getStyle("A{$Trow}:G{$Trow}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFD3D3D3');
        }

        /* Auto size */
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}