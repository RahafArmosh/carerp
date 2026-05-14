<?php

namespace App\Services;

use App\Models\SubProduct;
use App\Models\WarehouseStockCountImport;
use App\Models\WarehouseStockCountImportLine;
use App\Models\warehouse;
use Illuminate\Support\Facades\DB;

class StockCountImportSnapshotService
{
    /**
     * Persist a snapshot of a single-warehouse Excel stock count (parsed rows) before apply / background job.
     */
    public function recordSingleWarehouseSnapshot(
        array $importedData,
        warehouse $warehouse,
        string $sourceFilename,
        int $creatorId,
        ?int $userId,
        int $errorCount,
        ?string $jobToken = null
    ): ?WarehouseStockCountImport {
        if (empty($importedData)) {
            return null;
        }

        $sourceFilename = mb_substr($sourceFilename, 0, 255);

        return DB::transaction(function () use ($importedData, $warehouse, $sourceFilename, $creatorId, $userId, $errorCount, $jobToken) {
            $import = WarehouseStockCountImport::create([
                'created_by' => $creatorId,
                'user_id' => $userId,
                'warehouse_id' => $warehouse->id,
                'source_filename' => $sourceFilename,
                'import_mode' => 'single',
                'status' => $jobToken ? 'queued' : 'recorded',
                'job_token' => $jobToken,
                'line_count' => 0,
                'error_count' => $errorCount,
                'meta' => null,
            ]);

            $subIds = [];
            foreach ($importedData as $data) {
                if (is_array($data) && !empty($data['sub_product_id'])) {
                    $subIds[] = (int) $data['sub_product_id'];
                }
            }
            $subIds = array_values(array_unique($subIds));

            $subsById = $subIds === []
                ? collect()
                : SubProduct::whereIn('id', $subIds)->get()->keyBy('id');

            $rows = [];
            $now = now();
            foreach ($importedData as $productNo => $data) {
                $qty = is_array($data) ? (int) ($data['quantity'] ?? 0) : (int) $data;
                $subId = is_array($data) ? ($data['sub_product_id'] ?? null) : null;
                $sub = $subId ? $subsById->get($subId) : null;
                $rows[] = [
                    'warehouse_stock_count_import_id' => $import->id,
                    'warehouse_id' => $warehouse->id,
                    'product_no' => is_string($productNo) ? $productNo : (string) $productNo,
                    'sub_product_id' => $subId,
                    'counted_qty' => $qty,
                    'system_qty_before' => $sub ? (int) $sub->quantity : null,
                    'excel_row' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                WarehouseStockCountImportLine::insert($chunk);
            }

            $import->update(['line_count' => count($rows)]);

            return $import->fresh();
        });
    }
}
