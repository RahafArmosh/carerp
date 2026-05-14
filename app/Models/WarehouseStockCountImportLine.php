<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStockCountImportLine extends Model
{
    protected $fillable = [
        'warehouse_stock_count_import_id',
        'warehouse_id',
        'product_no',
        'sub_product_id',
        'counted_qty',
        'system_qty_before',
        'excel_row',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockCountImport::class, 'warehouse_stock_count_import_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }

    public function subProduct(): BelongsTo
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }
}
