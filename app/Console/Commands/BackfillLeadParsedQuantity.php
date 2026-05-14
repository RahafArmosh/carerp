<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;

class BackfillLeadParsedQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:backfill-parsed-quantity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse and fill the quantity field for all leads based on their message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of quantity for all leads...');
        $count = 0;
        Lead::chunk(100, function ($leads) use (&$count) {
            foreach ($leads as $lead) {
                $lead->quantity = $lead->parseQuantityFromMessage();
                if ($lead->isDirty('quantity')) {
                    $lead->save();
                    $count++;
                }
            }
        });
        $this->info("Backfill complete. Updated $count leads.");
    }
}
