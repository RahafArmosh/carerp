<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use App\Jobs\RegenerateInvoiceLedger;
use App\Services\InvoiceLedgerService;
use Illuminate\Support\Facades\DB;
class RegenerateAllInvoiceLedgers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run with: php artisan invoice:regenerate-ledgers
     */
    protected $signature = 'invoice:regenerate-ledgers';

    /**
     * The console command description.
     */
    protected $description = 'Regenerate general ledger entries for all invoices with status = 4';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new InvoiceLedgerService();
        $invoices = Invoice::withTrashed()->where('status', 4)->where('created_by',30)->get();

        $this->info("Found {$invoices->count()} invoices with status 4.");
        
        foreach ($invoices as $invoice) {
            try {
                DB::beginTransaction();

                $result = $service->regenerate($invoice->id, $invoice->send_date);

                DB::commit();
                $this->info("✔ Invoice #{$invoice->id} regenerated.");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✖ Failed to regenerate invoice #{$invoice->id}: " . $e->getMessage());
            }
        }

        $this->info('All invoice ledger regeneration jobs dispatched.');
    }
}

