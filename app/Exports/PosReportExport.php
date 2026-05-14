<?php

namespace App\Exports;

use App\Models\Pos;
use App\Models\PosPayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosReportExport implements FromCollection, WithHeadings
{
    protected $posPayments;
    protected $user;
    protected $warehouseTotals;

    public function __construct($posPayments, $user, $warehouseTotals = [])
    {
        $this->posPayments = $posPayments;
        $this->user = $user;
        $this->warehouseTotals = $warehouseTotals;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $collection = $this->posPayments->map(function($pos) {
            $customerName = 'Walk-in Customer';
            if ($pos->customer_id != 0 && !empty($pos->customer)) {
                $customerName = $pos->customer->name;
            }
            
            $warehouseName = !empty($pos->warehouse) ? $pos->warehouse->name : '';
            $cashierName = !empty($pos->cashier) ? $pos->cashier->name : 'N/A';
            
            // Calculate total quantity
            $totalQuantity = 0;
            if ($pos->items && $pos->items->isNotEmpty()) {
                foreach ($pos->items as $item) {
                    $totalQuantity += (float)($item->quantity ?? 0);
                }
            }
            
            // Get values
            $rawSubtotal = $pos->getRawSubTotal();
            $totalDiscount = $pos->getTotalDiscountAmount();
            $tax = $pos->getTotalTax();
            $voucher = $pos->getVoucherTotal();

            // Total = Sub Total - Discount + Tax - Vouchers (show 0 if negative)
            $total = $rawSubtotal - $totalDiscount + $tax - $voucher;
            $total = $total < 0 ? 0 : $total;

            // Actual amount paid (sum of all payment amounts for this POS, excluding zero/negative)
            $actualPaid = PosPayment::where('pos_id', $pos->id)
                ->where('amount', '>', 0)
                ->sum('amount');

            // Calculate combo savings (difference between regular price total and combo price total)
            $comboSavings = 0;
            if ($pos->items && $pos->items->isNotEmpty()) {
                foreach ($pos->items as $item) {
                    if ($item->compo_id != 0 && $item->compo_id != '0' && $item->combo_price !== null) {
                        // Calculate what it would cost at regular price vs combo price
                        $regularPrice = (float)($item->price ?? 0);
                        $comboPrice = (float)($item->combo_price ?? 0);
                        $quantity = (float)($item->quantity ?? 0);
                        
                        // Savings = (regular price - combo price) * quantity
                        $comboSavings += ($regularPrice - $comboPrice) * $quantity;
                    }
                }
            }
            
            return [
                $this->user->posNumberFormat($pos->pos_id),
                $this->user->dateFormat($pos->created_at),
                $customerName,
                $warehouseName,
                $cashierName,
                $totalQuantity,
                $rawSubtotal,
                $totalDiscount,
                $tax,
                $total,       // Calculated total
                $actualPaid,  // Actual amount paid
                $comboSavings,
                $voucher,
            ];
        });
        
        // Add warehouse totals rows
        if (!empty($this->warehouseTotals)) {
            // Add empty row
            $collection->push(['', '', '', '', '', '', '', '', '', '', '', '', '']);
            // Add header row
            $collection->push(['', '', '', '', '', '', 'PAYMENT TOTALS BY WAREHOUSE', '', '', '', '', '', '']);
            $collection->push(['Warehouse', '', '', '', '', '', '', '', '', '', '', 'Card Total', 'Cash Total']);
            
            // Add totals rows
            foreach ($this->warehouseTotals as $warehouseId => $totals) {
                $collection->push([
                    $totals['warehouse_name'],
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $totals['card_total'],
                    $totals['cash_total'],
                ]);
            }
        }
        
        return $collection;
    }

    public function headings(): array
    {
        return [
            'POS ID',
            'Date',
            'Customer',
            'Warehouse',
            'Cashier',
            'Quantity',
            'Sub Total',
            'Discount',
            'Tax',
            'Total',
            'Actual Amount Paid',
            'Combo Savings',
            'Voucher',
        ];
    }

}

