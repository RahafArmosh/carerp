<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseStockCountImport extends Model
{
    protected $fillable = [
        'created_by',
        'user_id',
        'warehouse_id',
        'source_filename',
        'import_mode',
        'status',
        'job_token',
        'line_count',
        'error_count',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseStockCountImportLine::class, 'warehouse_stock_count_import_id');
    }
}
