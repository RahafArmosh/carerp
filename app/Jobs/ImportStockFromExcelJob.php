<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StockImport;
use Throwable;

class ImportStockFromExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $creatorId;
    
    public $tries = 3;
    public $timeout = 7200; // 2 hours for large imports

    public function __construct($filePath, $creatorId)
    {
        $this->filePath = $filePath;
        $this->creatorId = $creatorId;
    }

    public function handle()
    {
        // Increase memory limit for large imports (ERP level optimization)
        ini_set('memory_limit', '2048M'); // 2GB memory limit
        set_time_limit(7200); // 2 hours timeout
        
        \Log::info('ImportStockFromExcelJob started', [
            'file_path' => $this->filePath,
            'creator_id' => $this->creatorId,
            'memory_limit' => ini_get('memory_limit')
        ]);

        try {
            // Get the full path to the file
            $fullPath = Storage::disk('local')->path($this->filePath);
            
            // Fallback if Storage path doesn't work
            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->filePath));
            }

            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: " . $this->filePath);
            }

            \Log::info('Processing stock import from file', [
                'file_path' => $this->filePath,
                'full_path' => $fullPath,
                'file_size' => filesize($fullPath),
                'creator_id' => $this->creatorId
            ]);

            // Create import instance
            $import = new StockImport($this->creatorId);
            
            // Process the import (this will run synchronously within the job)
            Excel::import($import, $fullPath);

            // Commit any pending batch transaction
            $import->commitPendingBatch();

            // Check for errors
            if ($import->hasErrors()) {
                $errorMessage = $import->getErrorMessage();
                \Log::warning('Stock import completed with errors', [
                    'errors' => $import->getErrors(),
                    'success_count' => $import->getSuccessCount(),
                    'fail_count' => $import->getFailCount()
                ]);
                // Don't throw exception - log errors but mark job as successful
                // Errors are already logged in the import class
            }

            \Log::info('Stock import completed successfully', [
                'success_count' => $import->getSuccessCount(),
                'fail_count' => $import->getFailCount(),
                'creator_id' => $this->creatorId
            ]);

            // Clean up the file after successful import
            if (file_exists($fullPath)) {
                @unlink($fullPath);
                \Log::info('Cleaned up import file', ['file_path' => $this->filePath]);
            }

        } catch (Throwable $e) {
            \Log::error('Stock import job failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'creator_id' => $this->creatorId,
                'file_path' => $this->filePath,
                'attempt' => $this->attempts()
            ]);
            
            // Re-throw exception to let Laravel handle retries
            // Don't delete file on error - might want to retry
            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        \Log::error('ImportStockFromExcelJob permanently failed', [
            'error' => $exception->getMessage(),
            'file_path' => $this->filePath,
            'creator_id' => $this->creatorId
        ]);
    }
}

