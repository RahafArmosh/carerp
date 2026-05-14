<?php

namespace App\Exports;

use App\Models\Asn;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AsnExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $filters;
    protected $asnId;
    protected $user;
    protected $customFields;

    public function __construct($userId, $filters = [], $asnId = null)
    {
        $this->userId = $userId;
        $this->filters = $filters;
        $this->asnId = $asnId;
        $this->user = \App\Models\User::find($userId);
        
        // Get all custom fields for sub-products to include in export
        $this->customFields = CustomField::where('created_by', $userId)
            ->where('module', 'sub-product')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $query = Asn::where('created_by', $this->userId)
            ->with(['supplier', 'items']);

        // If asnId is provided, export only that ASN
        if ($this->asnId) {
            $query->where('id', $this->asnId);
        } else {
            // Apply filters if provided (for bulk export)
            if (!empty($this->filters['supplier_id'])) {
                $query->where('supplier_id', $this->filters['supplier_id']);
            }

            if (!empty($this->filters['asn_date'])) {
                $query->where('asn_date', $this->filters['asn_date']);
            }

            if (!empty($this->filters['asn_no'])) {
                $query->where('asn_no', 'like', '%' . $this->filters['asn_no'] . '%');
            }
        }

        $asns = $query->orderBy('id', 'desc')->get();
        
        // Flatten ASNs into items - create a collection where each item is a row
        $rows = collect();
        foreach ($asns as $asn) {
            if ($asn->items && $asn->items->count() > 0) {
                foreach ($asn->items as $item) {
                    $rows->push((object)[
                        'asn' => $asn,
                        'item' => $item,
                    ]);
                }
            } else {
                // If no items, create a single row with ASN header info only
                $rows->push((object)[
                    'asn' => $asn,
                    'item' => null,
                ]);
            }
        }
        
        return $rows;
    }

    public function headings(): array
    {
        $headings = [
            'ASN No',
            'ASN Date',
            'Supplier Name',
            'Supplier Code',
            'Supplier Inv No',
            'Container No',
            'DEC No',
            'DEC Date',
            'Status',
            'Box No',
            'Supplier PO No',
            'Our PRO No',
            'Order Ref',
            'Part No',
            'Description',
            'Qty',
            'Received Qty',
            'Discrepancy',
            'Unit Price',
            'Total Price',
            'Unit Weight',
            'Total Weight',
        ];

        // Add custom field headings
        foreach ($this->customFields as $customField) {
            $headings[] = $customField->name;
        }

        return $headings;
    }

    public function map($row): array
    {
        $asn = $row->asn;
        $item = $row->item;
        
        $asnNumberFormatted = $this->user ? $this->user->asnNumberFormat($asn->asn_no) : $asn->asn_no;
        
        // Base row data
        $rowData = [
            $asnNumberFormatted,
            $asn->asn_date ? (\Carbon\Carbon::parse($asn->asn_date)->format('Y-m-d')) : '-',
            $asn->supplier_name ?? ($asn->supplier->name ?? '-'),
            $asn->supplier_code ?? '-',
            $asn->supplier_inv_no ?? '-',
            $asn->container_no ?? '-',
            $asn->dec_no ?? '-',
            $asn->dec_date ? (\Carbon\Carbon::parse($asn->dec_date)->format('Y-m-d')) : '-',
            ucfirst($asn->status ?? 'created'),
        ];

        // Add item data if exists
        if ($item) {
            $rowData = array_merge($rowData, [
                $item->box_no ?? '-',
                $item->supplier_po_no ?? '-',
                $item->our_pro_no ?? '-',
                $item->order_ref ?? '-',
                $item->part_no ?? '-',
                $item->description ?? '-',
                number_format($item->qty ?? 0, 2),
                number_format($item->received_qty ?? 0, 2),
                number_format($item->discrepancy ?? 0, 2),
                number_format($item->unit_price ?? 0, 2),
                number_format($item->total_price ?? 0, 2),
                number_format($item->unit_weight ?? 0, 3),
                number_format($item->total_weight ?? 0, 3),
            ]);

            // Get custom field values from sub-product based on part_no = product_no
            $customFieldValues = [];
            if (!empty($item->part_no)) {
                $subProduct = SubProduct::where('chassis_no', $item->part_no)
                    ->where('created_by', $this->userId)
                    ->latest()
                    ->first();

                if ($subProduct) {
                    $values = CustomFieldValue::where('record_id', $subProduct->id)
                        ->whereIn('field_id', $this->customFields->pluck('id'))
                        ->get()
                        ->keyBy('field_id');

                    foreach ($this->customFields as $customField) {
                        $customFieldValues[$customField->id] = isset($values[$customField->id]) 
                            ? $values[$customField->id]->value 
                            : '';
                    }
                } else {
                    // If no sub-product found, fill with empty values
                    foreach ($this->customFields as $customField) {
                        $customFieldValues[$customField->id] = '';
                    }
                }
            } else {
                // If no part_no, fill with empty values
                foreach ($this->customFields as $customField) {
                    $customFieldValues[$customField->id] = '';
                }
            }
        } else {
            // If no item, fill with empty/default values
            $rowData = array_merge($rowData, [
                '-', // Box No
                '-', // Supplier PO No
                '-', // Our PRO No
                '-', // Order Ref
                '-', // Part No
                '-', // Description
                '0.00', // Qty
                '0.00', // Received Qty
                '0.00', // Discrepancy
                '0.00', // Unit Price
                '0.00', // Total Price
                '0.000', // Unit Weight
                '0.000', // Total Weight
            ]);

            // Fill custom fields with empty values
            $customFieldValues = [];
            foreach ($this->customFields as $customField) {
                $customFieldValues[$customField->id] = '';
            }
        }

        // Add custom field values to row
        foreach ($this->customFields as $customField) {
            $rowData[] = $customFieldValues[$customField->id] ?? '';
        }

        return $rowData;
    }
}

