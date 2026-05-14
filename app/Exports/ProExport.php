<?php

namespace App\Exports;

use App\Models\Pro;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $filters;
    protected $user;

    public function __construct($userId, $filters = [])
    {
        $this->userId = $userId;
        $this->filters = $filters;
        $this->user = \App\Models\User::find($userId);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $query = Pro::where('created_by', $this->userId)
            ->with(['supplier', 'items.product', 'items.subProduct']);

        // Apply filters if provided
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        if (!empty($this->filters['po_date'])) {
            $query->where('po_date', $this->filters['po_date']);
        }

        if (!empty($this->filters['pro_no'])) {
            $query->where('pro_no', 'like', '%' . $this->filters['pro_no'] . '%');
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'PRO No',
            'Supplier Name',
            'Supplier Code',
            'PO Date',
            'Status',
            'Supplier Proforma No',
            'Supplier Proforma Date',
            'Our Order Ref',
            'Supplier Ref',
            'ETA Date',
            'Items Count',
            'Total Order Qty',
            'Total Supplied Qty',
            'Total Remaining Qty',
            'Total Amount',
            'Created At',
        ];
    }

    public function map($pro): array
    {
        $proNumberFormatted = $this->user ? $this->user->proNumberFormat($pro->pro_no) : $pro->pro_no;

        return [
            $proNumberFormatted,
            $pro->supplier_name ?? ($pro->supplier->name ?? '-'),
            $pro->supplier_code ?? '-',
            $pro->po_date ? (\Carbon\Carbon::parse($pro->po_date)->format('Y-m-d')) : '-',
            ucfirst($pro->status ?? 'open'),
            $pro->supplier_proforma_no ?? '-',
            $pro->supplier_proforma_date ? (\Carbon\Carbon::parse($pro->supplier_proforma_date)->format('Y-m-d')) : '-',
            $pro->our_order_ref ?? '-',
            $pro->supplier_ref ?? '-',
            $pro->eta_date ? (\Carbon\Carbon::parse($pro->eta_date)->format('Y-m-d')) : '-',
            $pro->items->count(),
            number_format($pro->getTotalOrderQty(), 2),
            number_format($pro->getTotalSuppliedQty(), 2),
            number_format($pro->getTotalRemainingQty(), 2),
            number_format($pro->getTotalAmount(), 2),
            $pro->created_at ? (\Carbon\Carbon::parse($pro->created_at)->format('Y-m-d H:i:s')) : '-',
        ];
    }
}
