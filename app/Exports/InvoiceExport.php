<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Tax;
use App\Models\Invoice;
use App\Models\ProductServiceCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Invoice::where('created_by', \Auth::user()->creatorId())
        ->get()
        ->map(function ($invoice) {
            return [
                "invoice_id"      => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                "customer"        => optional(Customer::find($invoice->customer_id))->name ?? $invoice->customer_id,
                "issue_date"      => $invoice->issue_date,
                "due_date"        => $invoice->due_date,
                "ref_number"      => $invoice->ref_number,
                "status"          => Invoice::$statues[$invoice->status] ?? 'Unknown',
                "payment_status"  => Invoice::$paymentstatues[$invoice->payment_status] ?? 'Unknown',
                "type"            => $invoice->type,
                "tax_id" => optional(Tax::find($invoice->tax_id))->name . ' ' . optional(Tax::find($invoice->tax_id))->rate . '%',
                "total"           => $invoice->getTotal(),
            ];
        });
    }

    public function headings(): array
    {
        return [
           "Invoice Id",
            "Customer",
            "Issue Date",
            "Due Date",
            "Ref Number",
            "Status",
            "Payment Status",
            "Type",
            "Tax ID",
            "Total",

        ];
    }
}
