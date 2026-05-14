<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Revenue;
use App\Models\BillProduct;
use App\Models\Customer;
use App\Models\BillAccount;
use App\Models\InvoiceProduct;
use App\Models\JournalItem;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TrialBalancTotalExport implements FromArray , WithHeadings , WithStyles, WithCustomStartCell, WithColumnWidths, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function __construct($data , $startDate, $endDate, $companyName)
    {
        $formattedData = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $totalPrevious = 0;
        $totalNew = 0;

        foreach($data as $key => $type)
        {
            $formattedData[] = [
                'Account Name' => '',
                'Account No'   => '',
                'Previous Balance'   => '',
                'Debit'        => '',
                'Credit'       => '',
                'New Balance'       => '',
            ];

            $formattedData[] = [
                'Account Name' => $key,
                'Account No'   => '',
                'Previous Balance'   => '',
                'Debit'        => '',
                'Credit'       => '',
                'New Balance'       => '',
            ];

            foreach($type as $account)
            {
                $formattedData[] = [
                    'Account Name' => $account['name'],
                    'Account No'   => $account['code'],
                    'Previous Balance'   => number_format((float) $account['prevDebit'] - $account['prevCredit'], 2, '.', ''),
                    'Debit'        => number_format((float)$account['totalDebit'] == 0 ? 0 : $account['totalDebit'], 2, '.', ''),
                    'Credit'       => number_format((float)$account['totalCredit'] == 0 ? 0 : $account['totalCredit'], 2, '.', ''),
                    'New Balance'  => number_format((float)( $account['prevDebit'] - $account['prevCredit'] ) + ($account['totalDebit'] - $account['totalCredit']), 2, '.', ''),
                ];


                $totalDebit += $account['totalDebit'];
                $totalCredit += $account['totalCredit'];
                $totalPrevious += $account['prevDebit'] - $account['prevCredit'];
                $totalNew += ( $account['prevDebit'] - $account['prevCredit'] ) + ($account['totalDebit'] - $account['totalCredit']);
            }

        }
        if($formattedData != [])
        {
            if ($formattedData != []) {
                $formattedData[] = [
                    'Account Name' => 'Total',
                    'Account No'   => '',
                    'Previous Balance'   => number_format((float)$totalPrevious, 2, '.', ''),
                    'Debit'        => number_format((float)$totalDebit == 0 ? 0 : $totalDebit, 2, '.', ''),
                    'Credit'       => number_format((float)$totalCredit == 0 ? 0 : $totalCredit, 2, '.', ''),
                    'New Balance'  => number_format((float)$totalNew, 2, '.', ''),
                ];
            }

        }

        $this->data         = $formattedData;
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
        $this->companyName  = $companyName;
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('B6')->getFont()->setBold(true);
        $sheet->getStyle('C6')->getFont()->setBold(true);
        $sheet->getStyle('D6')->getFont()->setBold(true);

    }

    public function array(): array
    {
        return $this->data;
    }


    public function headings(): array
    {
        return [
            "Account Name",
            "Account No",
            "Previous Balance",
            "Debit",
            "Credit",
            "New Balance",
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->getDelegate()->mergeCells('A2:D2');
                $event->sheet->getDelegate()->mergeCells('A3:D3');
                $event->sheet->getDelegate()->mergeCells('A4:D4');

                $event->sheet->getDelegate()->setCellValue('A2', 'Trial Balance - ' . $this->companyName)->getStyle('A2')->getFont()->setBold(true);
                $event->sheet->getDelegate()->setCellValue('A3', 'Print Out Date : ' . date('Y-m-d H:i'));
                $event->sheet->getDelegate()->setCellValue('A4', 'Date : ' . $this->startDate . ' - ' . $this->endDate);

                $startRow = 2;
                $lastRow = $event->sheet->getHighestRow();

                $event->sheet->getStyle('A' . $lastRow . ':Z' . $lastRow)->getFont()->setBold(true);

                $event->sheet->getStyle('A' . $startRow . ':Z' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                $data = $this->data;
                foreach ($data as $index => $row) {
                    if (isset($row['Account Name']) && ($row['Account Name'] == 'Assets' || $row['Account Name'] == 'Income' || $row['Account Name'] == 'Costs of Goods Sold' || $row['Account Name'] == 'Expenses' ||
                     $row['Account Name'] ==  'Liabilities' || $row['Account Name'] ==  'Equity')) {
                        $rowIndex = $index + 7; // Adjust for 1-based indexing and header row
                        $event->sheet->getStyle('A' . $rowIndex . ':D' . $rowIndex)
                            ->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                ],
                            ]);
                    }
                }
            },
        ];
    }
}
