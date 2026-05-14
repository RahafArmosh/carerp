<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

class SaleOrderSampleExport
{
    public function __invoke()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1: Customer Name only (Customer Code and TRN come from customer master)
        $sheet->setCellValue('A1', 'CUSTOMER NAME :');
        $sheet->setCellValue('B1', 'Sample Customer Name');

        // Right side: Location and Sales Order Details
        $sheet->setCellValue('I1', 'LOCATION:');
        $sheet->setCellValue('J1', 'the stock location');
        $sheet->setCellValue('I2', 'SALES ORDER DATE');
        $sheet->setCellValue('J2', date('Y-m-d'));
        $sheet->setCellValue('I3', 'TAX');
        $sheet->setCellValue('J3', '5'); // Rate only (e.g. 5 for 5% VAT)

        // Table Headers (DESCRIPTION and TOTAL CONFIRMED removed)
        $headers = [
            'PART NO',
            'REQ QTY',
            'UNIT PRICE',
            'TOTAL',
        ];
        $sheet->fromArray([$headers], null, 'A5');

        // Sample Data (no DESCRIPTION or TOTAL CONFIRMED column)
        $sampleData = [
            ['04465-60280', 50, 215.00, 10750.00],
            ['04466-60160', 50, 185.00, 9250.00],
            ['90916-02759', 100, 85.00, 8500.00],
            ['23390-51070', 300, 70.00, 21000.00],
        ];
        $sheet->fromArray($sampleData, null, 'A6');

        // Style header rows
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);
        $sheet->getStyle('A5:D5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A5:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style customer info labels
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('I1:I3')->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Format number columns
        $sheet->getStyle('B6:D9')->getNumberFormat()->setFormatCode('#,##0.00');

        // Format date column
        $sheet->getStyle('J2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        // Create writer and save to temporary file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'sale_order_import_sample_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sample_sale_order');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
