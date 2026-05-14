<?php

namespace App\Jobs;

use App\Http\Controllers\WarehouseController;
use App\Models\WarehouseStockCountImport;
use App\Models\warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApplyStockCountImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min

    public function __construct(
        public string $token,
        public int $warehouseId,
        public int $creatorId,
        public string $cacheKey
    ) {}

    public function handle(): void
    {
        $statusKey = 'stock_count_import_status:' . $this->token;

        Cache::put($statusKey, [
            'status' => 'running',
            'progress' => 0,
            'message' => 'Processing...',
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        try {
            /** @var array $importedData */
            $importedData = Cache::get($this->cacheKey, []);
            if (empty($importedData) || !is_array($importedData)) {
                throw new \RuntimeException('Imported data not found (cache expired). Please import again.');
            }

            $warehouse = warehouse::findOrFail($this->warehouseId);

            // Use controller method for now (logic already implemented there)
            $controller = app(WarehouseController::class);
            $controller->applyStockCountFromImportedDataForJob($warehouse, $importedData, $this->creatorId, $statusKey);

            Cache::put($statusKey, [
                'status' => 'done',
                'progress' => 100,
                'message' => 'Completed',
                'updated_at' => now()->toDateTimeString(),
            ], now()->addHours(2));

            WarehouseStockCountImport::where('job_token', $this->token)->update(['status' => 'applied']);
        } catch (\Throwable $e) {
            WarehouseStockCountImport::where('job_token', $this->token)->update(['status' => 'apply_failed']);
            Log::error('ApplyStockCountImportJob failed', [
                'token' => $this->token,
                'warehouse_id' => $this->warehouseId,
                'creator_id' => $this->creatorId,
                'error' => $e->getMessage(),
            ]);
            Cache::put($statusKey, [
                'status' => 'error',
                'progress' => 0,
                'message' => $e->getMessage(),
                'updated_at' => now()->toDateTimeString(),
            ], now()->addHours(2));
        } finally {
            Cache::forget($this->cacheKey);
        }
    }
}

