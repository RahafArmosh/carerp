<?php

namespace App\Exports;

use App\Models\WarehouseStockCountImportLine;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WarehouseStockCountImportLinesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $importId
    ) {
    }

    public function query(): Builder
    {
        return WarehouseStockCountImportLine::query()
            ->where('warehouse_stock_count_import_id', $this->importId)
            ->with('warehouse')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Excel row',
            'Warehouse',
            'Product No',
            'System qty',
            'Counted qty',
            'Difference',
            'Sub product ID',
        ];
    }

    /**
     * @param  WarehouseStockCountImportLine  $line
     */
    public function map($line): array
    {
        $sys = $line->system_qty_before;
        $cnt = $line->counted_qty;
        $diff = $sys !== null ? $cnt - $sys : null;

        return [
            $line->excel_row ?? '',
            $line->warehouse ? $line->warehouse->name : '',
            $line->product_no,
            $sys !== null ? $sys : '',
            $cnt,
            $diff !== null ? $diff : '',
            $line->sub_product_id ?? '',
        ];
    }
}
