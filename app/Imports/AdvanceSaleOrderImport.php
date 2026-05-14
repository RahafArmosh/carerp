<?php

namespace App\Imports;

use App\Models\AdvanceSaleOrder;
use App\Models\AdvanceSaleOrderItem;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AdvanceSaleOrderImport implements ToArray
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        if (is_numeric($dateValue)) {
            try {
                return Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        if (is_string($dateValue)) {
            $formats = [
                'Y-m-d',
                'Y/m/d',
                'd-m-Y',
                'd/m/Y',
                'm-d-Y',
                'm/d/Y',
                'Y-m-d H:i:s',
                'Y/m/d H:i:s',
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateValue);
                if ($date && $date->format($format) === $dateValue) {
                    return $date->format('Y-m-d');
                }
            }

            try {
                return \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        return date('Y-m-d');
    }

    private function findTaxIds($taxValue, $creatorId): array
    {
        if ($taxValue === null || $taxValue === '') {
            return [];
        }

        $taxIds = [];
        $taxValues = array_map('trim', explode(',', (string) $taxValue));

        foreach ($taxValues as $value) {
            if ($value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $tax = Tax::where('id', $value)->where('created_by', $creatorId)->first();
                if ($tax) {
                    $taxIds[] = (string) $tax->id;
                    continue;
                }

                $tax = Tax::where('created_by', $creatorId)->where('rate', (float) $value)->first();
                if ($tax) {
                    $taxIds[] = (string) $tax->id;
                    continue;
                }
            }

            $rateMatch = [];
            if (preg_match('/^(\d+(?:\.\d+)?)\s*%?/i', trim($value), $rateMatch)) {
                $rate = (float) $rateMatch[1];
                $tax = Tax::where('created_by', $creatorId)->where('rate', $rate)->first();
                if ($tax) {
                    $taxIds[] = (string) $tax->id;
                    continue;
                }
            }

            $tax = Tax::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($value))])
                ->where('created_by', $creatorId)
                ->first();

            if ($tax) {
                $taxIds[] = (string) $tax->id;
            }
        }

        return $taxIds;
    }

    public function array(array $data)
    {
        if (count($data) < 8) {
            throw new \Exception('Invalid file format. File must have at least 8 rows.');
        }

        $customerName = null;
        $salesOrderDate = null;
        $taxValue = null;

        for ($i = 0; $i < min(15, count($data)); $i++) {
            $row = $data[$i] ?? [];
            $rowUpper = array_map(function ($v) {
                return strtoupper(trim((string) $v));
            }, $row);

            foreach ($rowUpper as $colIndex => $cellValue) {
                if (stripos($cellValue, 'CUSTOMER NAME') !== false && isset($row[$colIndex + 1]) && trim((string) $row[$colIndex + 1]) !== '') {
                    $customerName = trim((string) $row[$colIndex + 1]);
                    break 2;
                }
            }

            foreach ($rowUpper as $colIndex => $cellValue) {
                if (stripos($cellValue, 'TAX') !== false && stripos($cellValue, 'CUSTOMER') === false && isset($row[$colIndex + 1])) {
                    $rawTax = $row[$colIndex + 1];
                    $taxValue = is_numeric($rawTax) ? (string) (float) $rawTax : trim((string) $rawTax);
                    break;
                }
            }
        }

        if (isset($data[5][8]) && stripos((string) $data[5][8], 'SALES ORDER DATE') !== false && isset($data[5][9])) {
            $salesOrderDate = $this->parseDate($data[5][9]);
        }
        if (isset($data[6][8]) && stripos((string) $data[6][8], 'SALES ORDER DATE') !== false && isset($data[6][9])) {
            $salesOrderDate = $this->parseDate($data[6][9]);
        }

        if (empty($customerName)) {
            throw new \Exception('Customer Name is required in the header section.');
        }

        $user = \App\Models\User::find($this->userId);
        $creatorId = $user ? $user->creatorId() : $this->userId;

        $customer = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($customerName))])
            ->where('created_by', $creatorId)
            ->first();
        if (!$customer) {
            throw new \Exception('Customer not found: "' . $customerName . '".');
        }

        $currency = Currency::where('code', 'AED')->first();
        if (!$currency) {
            $currency = Currency::first();
        }

        $taxId = '';
        if ($taxValue !== null && $taxValue !== '') {
            $taxIds = $this->findTaxIds($taxValue, $creatorId);
            $taxId = !empty($taxIds) ? implode(',', $taxIds) : '';
        } else {
            $defaultTax = Tax::where('created_by', $creatorId)->where('rate', 5)->first();
            if (!$defaultTax) {
                $defaultTax = Tax::where('created_by', $creatorId)->first();
            }
            $taxId = $defaultTax ? (string) $defaultTax->id : '';
        }

        $headerRow = null;
        $headerRowIndex = null;
        for ($i = 4; $i < min(15, count($data)); $i++) {
            $row = $data[$i] ?? [];
            foreach ($row as $cell) {
                $cellUpper = strtoupper(trim((string) $cell));
                if (stripos($cellUpper, 'PART NO') !== false || $cellUpper === 'PARTNO' || stripos($cellUpper, 'PART NUMBER') !== false) {
                    $headerRow = $row;
                    $headerRowIndex = $i;
                    break 2;
                }
            }
        }
        if (!$headerRow) {
            throw new \Exception('Table headers not found. Could not locate PART NO column.');
        }

        $partNoIndex = false;
        $descriptionIndex = false;
        $reqQtyIndex = false;
        $unitPriceIndex = false;
        foreach ($headerRow as $index => $header) {
            $headerUpper = strtoupper(preg_replace('/\s+/', ' ', trim((string) $header)));
            if (stripos($headerUpper, 'PART NO') !== false || $headerUpper === 'PARTNO' || stripos($headerUpper, 'PART NUMBER') !== false) {
                $partNoIndex = $index;
            } elseif (stripos($headerUpper, 'DESCRIPTION') !== false) {
                $descriptionIndex = $index;
            } elseif (stripos($headerUpper, 'REQ QTY') !== false || stripos($headerUpper, 'REQUIRED QTY') !== false || stripos($headerUpper, 'REQ. QTY') !== false) {
                $reqQtyIndex = $index;
            } elseif (stripos($headerUpper, 'UNIT PRICE') !== false || stripos($headerUpper, 'PRICE') !== false) {
                $unitPriceIndex = $index;
            }
        }

        if ($partNoIndex === false || $reqQtyIndex === false) {
            throw new \Exception('Required columns PART NO and REQ QTY were not found.');
        }

        $dataRows = array_slice($data, $headerRowIndex + 1);
        $dataRows = array_filter($dataRows, function ($row) use ($partNoIndex) {
            return isset($row[$partNoIndex]) && trim((string) $row[$partNoIndex]) !== '';
        });
        if (empty($dataRows)) {
            throw new \Exception('No item rows found in file.');
        }

        $lastOrder = AdvanceSaleOrder::where('created_by', $this->userId)->withTrashed()->latest()->first();
        $nextNo = $lastOrder ? ((int) $lastOrder->advance_sale_order_no + 1) : 1;
        $salesOrderDate = $salesOrderDate ?: date('Y-m-d');

        DB::beginTransaction();
        try {
            $order = new AdvanceSaleOrder();
            $order->advance_sale_order_no = $nextNo;
            $order->customer_id = $customer->id;
            $order->sales_order_date = $salesOrderDate;
            $order->currency_id = $currency ? $currency->id : null;
            $order->exchange_rate = $currency ? $currency->exchange_rate : 1.0;
            $order->tax_id = $taxId;
            $order->status = 'draft';
            $order->created_by = $this->userId;
            $order->save();

            foreach ($dataRows as $row) {
                $partNo = isset($row[$partNoIndex]) ? trim((string) $row[$partNoIndex]) : null;
                if (empty($partNo)) {
                    continue;
                }

                $description = ($descriptionIndex !== false && isset($row[$descriptionIndex]))
                    ? trim((string) $row[$descriptionIndex])
                    : null;
                $reqQty = isset($row[$reqQtyIndex]) ? (float) $row[$reqQtyIndex] : 0;
                $unitPrice = isset($row[$unitPriceIndex]) ? (float) $row[$unitPriceIndex] : 0;

                $item = new AdvanceSaleOrderItem();
                $item->advance_sale_order_id = $order->id;
                $item->part_no = $partNo;
                $item->description = $description;
                $item->req_qty = (float) $reqQty;
                $item->converted_qty = 0.0;
                $item->unit_price = (float) $unitPrice;
                $item->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
