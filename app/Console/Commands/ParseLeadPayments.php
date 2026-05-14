<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;

class ParseLeadPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:parse-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse and store payment values from message column for all leads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $leads = Lead::all();
        $updated = 0;
        foreach ($leads as $lead) {
            $payment = $lead->parsePaymentFromMessage();
            if ($payment !== null && $lead->payment !== $payment) {
                $lead->payment = $payment;
                $lead->save();
                $updated++;
            }
        }
        $this->info("Updated $updated leads with payment values.");
    }
}
