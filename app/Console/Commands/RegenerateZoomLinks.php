<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\LiveSession;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RegenerateZoomLinks extends Command
{
    protected $signature = 'zoom:regenerate-links
        {--bookings : Regenerate 1:1 / course booking links}
        {--camp-bookings : Regenerate camp / demo booking links}
        {--sessions : Regenerate live session links}
        {--all : Regenerate all of the above}
        {--dry-run : List what would be regenerated without changing anything}';

    protected $description = 'Regenerate Zoom meeting links (join + host) using the current meeting settings.';

    public function handle(ZoomService $zoomService): int
    {
        $all = $this->option('all') || (! $this->option('bookings') && ! $this->option('camp-bookings') && ! $this->option('sessions'));

        $targets = [];
        if ($all || $this->option('bookings')) {
            $targets['bookings'] = Booking::where(function ($q) {
                $q->whereNotNull('zoom_join_url')->orWhereNotNull('zoom_start_url');
            })->get();
        }
        if ($all || $this->option('camp-bookings')) {
            $targets['camp_bookings'] = CampBooking::where(function ($q) {
                $q->whereNotNull('zoom_join_url')->orWhereNotNull('zoom_start_url');
            })->get();
        }
        if ($all || $this->option('sessions')) {
            $targets['live_sessions'] = LiveSession::where(function ($q) {
                $q->whereNotNull('join_url')->orWhereNotNull('start_url');
            })->get();
        }

        $dryRun = $this->option('dry-run');

        foreach ($targets as $type => $records) {
            $this->info(sprintf('%s: %d record(s)', $type, $records->count()));

            foreach ($records as $record) {
                $topic = $this->topicFor($record, $type);
                $startTime = $this->startTimeFor($record, $type);

                if ($dryRun) {
                    $this->line("  [dry-run] {$type} #{$record->id} → {$topic} @ {$startTime}");

                    continue;
                }

                try {
                    $meeting = $zoomService->createMeeting($topic, $startTime, 45, $record->timezone ?? $record->parent_timezone ?? 'UTC');

                    if ($type === 'live_sessions') {
                        $record->update([
                            'join_url' => $meeting['join_url'] ?? null,
                            'start_url' => $meeting['start_url'] ?? null,
                            'zoom_meeting_id' => $meeting['id'] ?? $record->zoom_meeting_id,
                            'zoom_passcode' => $meeting['passcode'] ?? null,
                            'meeting_provider' => $meeting['provider'] ?? 'zoom',
                            'meeting_room_id' => isset($meeting['id']) ? (string) $meeting['id'] : $record->meeting_room_id,
                        ]);
                    } else {
                        $record->update(ZoomService::bookingFields($meeting));
                    }

                    $this->line("  ✓ {$type} #{$record->id} regenerated");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$type} #{$record->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function topicFor($record, string $type): string
    {
        return match ($type) {
            'camp_bookings' => ($record->camp_name ?? 'Demo Class').' - '.($record->child_name ?? $record->parent_name ?? 'Learner'),
            'live_sessions' => ($record->course->title ?? 'Live Session').' Live Session',
            default => ($record->course->title ?? 'Live Session').' - '.($record->student->name ?? 'Student'),
        };
    }

    private function startTimeFor($record, string $type): string
    {
        $start = $type === 'live_sessions' ? $record->start_time : $record->scheduled_at;

        return $start
            ? Carbon::parse($start)->format('Y-m-d H:i:s')
            : now()->addDay()->format('Y-m-d 10:00:00');
    }
}
