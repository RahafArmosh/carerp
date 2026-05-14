<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deal;
use App\Models\DealReminder;

class GenerateNoStockDealReminders extends Command
{
    protected $signature = 'reminders:generate-no-stock';

    protected $description = 'Generate reminders for deals in No Stock stage; skip if pending exists';

    public function handle(): int
    {
        $this->info('Scanning deals in No Stock stage...');

        Deal::query()
            ->select('deals.*')
            ->join('stages', 'stages.id', '=', 'deals.stage_id')
            ->whereRaw('LOWER(TRIM(stages.name)) = ?', ['no stock'])
            ->orderBy('deals.id')
            ->chunkById(200, function ($deals) {
                foreach ($deals as $deal) {
                    $assignedUserIds = $deal->users()->pluck('users.id');
                    foreach ($assignedUserIds as $userId) {
                        $exists = DealReminder::where('deal_id', $deal->id)
                            ->where('user_id', $userId)
                            ->where('is_done', false)
                            ->exists();
                        if (!$exists) {
                            DealReminder::create([
                                'deal_id'    => $deal->id,
                                'user_id'    => $userId,
                                'created_by' => $deal->created_by,
                                'message'    => 'Check your stock for the deal ' . $deal->name . ', Please review stock and follow up.',
                            ]);
                            $this->line("Reminder created for deal {$deal->id} user {$userId}");
                        }
                    }
                }
            }, 'id');

        $this->info('Done generating No Stock reminders.');
        return Command::SUCCESS;
    }
}


