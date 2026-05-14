<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsnImportErrorsExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    protected $validationErrors;

    public function __construct(array $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->validationErrors as $index => $error) {
            $errors = $error['errors'] ?? [];
            $errorList = is_array($errors) ? implode(' | ', $errors) : (string) $errors;
            $explanation = $this->explainErrors($errors);
            $data = $error['data'] ?? [];
            $rows[] = [
                $error['row'] ?? ($index + 1),
                $error['part_no'] ?? 'N/A',
                $error['description'] ?? 'N/A',
                $errorList,
                $explanation,
                $data['supplier_po_no'] ?? '',
                $data['our_pro_no'] ?? '',
                $data['order_ref'] ?? '',
                $data['unit_price'] ?? '',
                $data['received_qty'] ?? '',
            ];
        }
        return $rows;
    }

    /**
     * Turn error messages into clear "what to do" explanations for the user.
     */
    private function explainErrors(array $errors): string
    {
        $tips = [];
        foreach ($errors as $err) {
            $err = trim((string) $err);
            if (stripos($err, 'PRO Number') !== false && stripos($err, 'not found') !== false) {
                $tips[] = 'Create or use an existing PRO (Purchase Request Order) with this number in the system, or correct the "Our PRO No" in your file to match an existing PRO.';
            } elseif (stripos($err, 'Part Number') !== false && stripos($err, 'not found in PRO') !== false) {
                $tips[] = 'Add this Part No to the PRO items for the given PRO, or correct the Part No in your file to match an item already on the PRO.';
            } elseif (stripos($err, 'Part Number is required') !== false) {
                $tips[] = 'Fill in the Part No column for this row. Part No cannot be empty.';
            } elseif (stripos($err, 'Unit Price') !== false && stripos($err, 'does not match') !== false) {
                $tips[] = 'Change the Unit Price in your file to match the Unit Price on the PRO item, or update the PRO item price in the system.';
            } elseif (stripos($err, 'Error processing row') !== false) {
                $tips[] = 'Check that all required columns exist and data format is correct (e.g. numbers without text, valid dates).';
            } else {
                $tips[] = 'Please correct the data in your file as indicated above and re-import.';
            }
        }
        return implode(' ', array_unique($tips));
    }

    public function headings(): array
    {
        return [
            __('Row #'),
            __('Part No'),
            __('Description'),
            __('Error(s)'),
            __('What to do'),
            __('Supplier PO No'),
            __('Our PRO No'),
            __('Order Ref'),
            __('Unit Price'),
            __('Received Qty'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 18,
            'C' => 35,
            'D' => 50,
            'E' => 55,
            'F' => 18,
            'G' => 16,
            'H' => 14,
            'I' => 12,
            'J' => 14,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
