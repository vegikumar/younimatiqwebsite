<?php

namespace App\Console\Commands;

use App\Services\MeetService;
use Illuminate\Console\Command;

class SyncMeetRecordings extends Command
{
    protected $signature = 'meet:sync-recordings';

    protected $description = 'Pull Meet server recordings into class_recordings for recent meet-provider classes';

    public function handle(MeetService $meetService): int
    {
        $count = $meetService->syncAllRecentMeetings();
        $this->info("Synced {$count} recording(s).");

        return self::SUCCESS;
    }
}
