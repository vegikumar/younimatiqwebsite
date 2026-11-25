<?php

namespace App\Console\Commands;

use App\Models\ClassRecording;
use App\Models\RecordingAssignment;
use App\Services\MeetService;
use Illuminate\Console\Command;

class PurgeExpiredRecordings extends Command
{
    protected $signature = 'recordings:purge-expired';

    protected $description = 'Delete Meet recording files and mark Laravel rows past 60-day retention';

    public function handle(MeetService $meet): int
    {
        $expired = ClassRecording::query()
            ->whereNull('deleted_from_storage_at')
            ->where(function ($q) {
                $q->where('expires_at', '<=', now())
                    ->orWhere(function ($inner) {
                        $inner->whereNull('expires_at')
                            ->where('recorded_at', '<=', now()->subDays(ClassRecording::RETENTION_DAYS));
                    })
                    ->orWhere(function ($inner) {
                        $inner->whereNull('expires_at')
                            ->whereNull('recorded_at')
                            ->where('created_at', '<=', now()->subDays(ClassRecording::RETENTION_DAYS));
                    });
            })
            ->orderBy('id')
            ->limit(200)
            ->get();

        $purged = 0;
        foreach ($expired as $recording) {
            if (($recording->provider ?? null) === 'meet' && $recording->meet_recording_id) {
                $meet->deleteRecording($recording->meet_recording_id);
            }

            $recording->deleted_from_storage_at = now();
            if (! $recording->expires_at) {
                $base = $recording->recorded_at ?? $recording->created_at ?? now();
                $recording->expires_at = $base->copy()->addDays(ClassRecording::RETENTION_DAYS);
            }
            $recording->save();
            $purged++;
        }

        $assignmentsCleared = RecordingAssignment::query()
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Purged {$purged} recordings; cleared {$assignmentsCleared} expired assignments.");

        return self::SUCCESS;
    }
}
