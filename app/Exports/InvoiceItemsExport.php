<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Tax;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceItemsExport implements FromCollection, WithHeadings
{
    protected Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function collection()
    {
        return InvoiceProduct::with(['product', 'subProduct'])
            ->where('invoice_id', $this->invoice->id)
            ->get()
            ->map(function ($item) {
                $taxRate = 0;
                if (!empty($item->tax)) {
                    $taxIds = array_filter(explode(',', (string) $item->tax));
                    if (!empty($taxIds)) {
                        $taxRate = (float) Tax::whereIn('id', $taxIds)->sum('rate');
                    }
                }

                $unitBase = max((float) ($item->price ?? 0) - (float) ($item->discount ?? 0), 0);
                $lineSubtotal = $unitBase * (float) ($item->quantity ?? 0);
                $lineTax = ($lineSubtotal * $taxRate) / 100;
                $lineTotal = $lineSubtotal + $lineTax;

                return [
                    'invoice_no' => \Auth::user()->invoiceNumberFormat($this->invoice->invoice_id),
                    'sub_product_id' => $item->sub_product_id ?? '',
                    'sub_product_no' => optional($item->subProduct)->product_no ?? '',
                    'product_name' => optional($item->product)->name ?? '',
                    'qty' => (float) ($item->quantity ?? 0),
                    'unit_price' => (float) ($item->price ?? 0),
                    'discount' => (float) ($item->discount ?? 0),
                    'tax_rate_percent' => $taxRate,
                    'line_total' => round($lineTotal, 2),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Sub Product ID',
            'Sub Product No',
            'Product Name',
            'Quantity',
            'Unit Price',
            'Discount',
            'Tax Rate (%)',
            'Line Total',
        ];
    }
}

