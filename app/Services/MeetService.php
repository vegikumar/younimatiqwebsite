<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassRecording;
use App\Models\LiveSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MeetService
{
    public function createMeeting(string $topic, $startTime = null, int $duration = 45, string $timezone = 'UTC'): array
    {
        $baseUrl = $this->baseUrl();
        $orgId = (string) config('services.meet.org_id', 'younimatiq');
        $orgName = (string) config('services.meet.org_name', 'younimatiq');
        $secret = (string) config('services.meet.api_secret');
        $scheduledAt = $this->normalizeSchedule($startTime, $timezone);

        $payload = [
            'organization_id' => $orgId,
            'organization_name' => $orgName,
            'topic' => $topic,
            'title' => $topic,
            'start_time' => $scheduledAt,
            'scheduled_at' => $scheduledAt,
            'duration' => $duration,
            'timezone' => $timezone,
            'waiting_room' => true,
            'locked' => false,
            'brand_name' => 'Younimatiq',
            'logo_url' => url('/logo.webp'),
            'primary_color' => '#7130D8',
        ];

        try {
            $response = Http::timeout(12)
                ->withHeaders(['X-Meet-Secret' => $secret])
                ->acceptJson()
                ->post($baseUrl.'/api/create-meeting', $payload);

            if ($response->successful()) {
                $json = $response->json();
                $roomId = $json['roomId'] ?? $json['id'] ?? (string) Str::uuid();
                $joinUrl = $json['join_url'] ?? ($baseUrl.'/room/'.$roomId);
                $startUrl = $json['start_url'] ?? ($joinUrl.'?role=host');

                return [
                    'id' => $roomId,
                    'join_url' => $joinUrl,
                    'start_url' => $startUrl,
                    'passcode' => $json['passcode'] ?? null,
                    'auto_recording' => ! empty($json['recording_enabled']) ? 'local' : 'off',
                    'provider' => 'meet',
                    'scheduled_at' => $json['scheduled_at'] ?? $scheduledAt,
                ];
            }

            Log::warning('Meet API create-meeting failed: '.$response->body());
        } catch (\Throwable $e) {
            Log::warning('Meet API call failed, using local room fallback: '.$e->getMessage());
        }

        return $this->fallbackMeeting($topic, $orgName, $scheduledAt);
    }

    /**
     * Update an existing Meet room schedule/title when a class is rescheduled.
     */
    public function updateMeeting(string $roomId, string $topic, $startTime = null, string $timezone = 'UTC'): ?array
    {
        $baseUrl = $this->baseUrl();
        $secret = (string) config('services.meet.api_secret');
        $scheduledAt = $this->normalizeSchedule($startTime, $timezone);

        try {
            $response = Http::timeout(12)
                ->withHeaders(['X-Meet-Secret' => $secret])
                ->acceptJson()
                ->patch($baseUrl.'/api/meetings/'.$roomId, [
                    'title' => $topic,
                    'scheduled_at' => $scheduledAt,
                ]);

            if ($response->successful()) {
                $hostToken = hash_hmac('sha256', 'host:'.$roomId, $secret);

                return [
                    'id' => $roomId,
                    'join_url' => $baseUrl.'/room/'.$roomId,
                    'start_url' => $baseUrl.'/room/'.$roomId.'?role=host&host_token='.urlencode($hostToken),
                    'passcode' => null,
                    'auto_recording' => 'local',
                    'provider' => 'meet',
                    'scheduled_at' => $scheduledAt,
                ];
            }

            Log::warning('Meet API update-meeting failed: '.$response->body());
        } catch (\Throwable $e) {
            Log::warning('Meet API update-meeting error: '.$e->getMessage());
        }

        return null;
    }

    public function roomHostPresent(?string $roomId): ?bool
    {
        if (! $roomId) {
            return null;
        }

        try {
            $response = Http::timeout(5)->acceptJson()->get($this->baseUrl().'/api/rooms/'.$roomId.'/status');
            if ($response->successful()) {
                return (bool) $response->json('host_present');
            }
        } catch (\Throwable $e) {
            Log::warning('Meet room status failed: '.$e->getMessage());
        }

        return null;
    }

    public function listRecordings(?string $roomId = null, ?string $organization = null): array
    {
        $secret = (string) config('services.meet.api_secret');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Meet-Secret' => $secret])
                ->acceptJson()
                ->get($this->baseUrl().'/api/recordings', array_filter([
                    'room_id' => $roomId,
                    'organization' => $organization,
                ]));

            if ($response->successful()) {
                return $response->json('recordings') ?? [];
            }

            Log::warning('Meet list recordings failed: '.$response->body());
        } catch (\Throwable $e) {
            Log::warning('Meet list recordings error: '.$e->getMessage());
        }

        return [];
    }

    public function recordingFileUrl(int|string $meetRecordingId): string
    {
        return $this->baseUrl().'/api/recordings/'.$meetRecordingId.'/file';
    }

    public function syncRecordingsForRoom(string $roomId, ?int $bookingId = null, ?int $liveSessionId = null, ?int $courseId = null): int
    {
        $recordings = $this->listRecordings($roomId);
        $synced = 0;

        foreach ($recordings as $rec) {
            $meetId = $rec['id'] ?? null;
            if (! $meetId) {
                continue;
            }

            $title = $rec['title']
                ?? $rec['topic']
                ?? ('Class recording · '.substr($roomId, 0, 8));

            $recordedAt = isset($rec['created_at']) ? Carbon::parse($rec['created_at']) : now();

            $model = ClassRecording::query()->updateOrCreate(
                [
                    'meet_recording_id' => $meetId,
                    'meeting_room_id' => $roomId,
                ],
                [
                    'booking_id' => $bookingId,
                    'live_session_id' => $liveSessionId,
                    'course_id' => $courseId,
                    'title' => $title,
                    'provider' => 'meet',
                    'storage_path' => $rec['file_path'] ?? null,
                    'file_size_mb' => isset($rec['file_size']) ? round(((int) $rec['file_size']) / 1048576, 2) : null,
                    'recorded_at' => $recordedAt,
                    'expires_at' => $recordedAt->copy()->addDays(ClassRecording::RETENTION_DAYS),
                    'recording_url' => $this->recordingFileUrl($meetId),
                ]
            );

            $model->recording_url = url('/api/v1/student/recordings/'.$model->id.'/stream');
            $model->save();
            $synced++;
        }

        return $synced;
    }

    public function syncAllRecentMeetings(): int
    {
        $total = 0;

        Booking::query()
            ->where('meeting_provider', 'meet')
            ->whereNotNull('meeting_room_id')
            ->where('scheduled_at', '>=', now()->subDays(60))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->each(function (Booking $booking) use (&$total) {
                $total += $this->syncRecordingsForRoom(
                    (string) $booking->meeting_room_id,
                    $booking->id,
                    null,
                    $booking->course_id
                );
            });

        LiveSession::query()
            ->where('meeting_provider', 'meet')
            ->whereNotNull('meeting_room_id')
            ->where('start_time', '>=', now()->subDays(60))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->each(function (LiveSession $session) use (&$total) {
                $total += $this->syncRecordingsForRoom(
                    (string) $session->meeting_room_id,
                    null,
                    $session->id,
                    $session->course_id
                );
            });

        CampBooking::query()
            ->where('meeting_provider', 'meet')
            ->whereNotNull('meeting_room_id')
            ->where('scheduled_at', '>=', now()->subDays(60))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->each(function (CampBooking $camp) use (&$total) {
                $total += $this->syncRecordingsForRoom(
                    (string) $camp->meeting_room_id,
                    null,
                    null,
                    $camp->course_id
                );
            });

        return $total;
    }

    public function deleteRecording(int|string $meetRecordingId): bool
    {
        $secret = (string) config('services.meet.api_secret');

        try {
            $response = Http::timeout(20)
                ->withHeaders(['X-Meet-Secret' => $secret])
                ->acceptJson()
                ->delete($this->baseUrl().'/api/recordings/'.$meetRecordingId);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Meet delete recording failed: '.$response->body());
        } catch (\Throwable $e) {
            Log::warning('Meet delete recording error: '.$e->getMessage());
        }

        return false;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.meet.base_url', 'https://younimatiq.com'), '/');
    }

    private function normalizeSchedule($startTime, string $timezone): ?string
    {
        if ($startTime === null || $startTime === '') {
            return null;
        }

        try {
            if ($startTime instanceof Carbon) {
                return $startTime->clone()->utc()->toIso8601String();
            }

            return Carbon::parse((string) $startTime, $timezone ?: 'UTC')->utc()->toIso8601String();
        } catch (\Throwable $e) {
            Log::warning('Meet schedule parse failed: '.$e->getMessage());

            return null;
        }
    }

    private function fallbackMeeting(string $topic, string $orgName, ?string $scheduledAt): array
    {
        $baseUrl = $this->baseUrl();
        $secret = (string) config('services.meet.api_secret');
        $roomId = (string) Str::uuid();
        $hostToken = hash_hmac('sha256', 'host:'.$roomId, $secret);

        try {
            $mark = Http::timeout(8)
                ->withHeaders(['X-Meet-Secret' => $secret])
                ->acceptJson()
                ->post($baseUrl.'/api/mark-platform-room', array_filter([
                    'room_id' => $roomId,
                    'organization_name' => $orgName,
                    'title' => $topic,
                    'scheduled_at' => $scheduledAt,
                    'waiting_room' => true,
                ]));
            if (! $mark->successful()) {
                Log::warning('Meet mark-platform-room failed: '.$mark->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Meet mark-platform-room error: '.$e->getMessage());
        }

        return [
            'id' => $roomId,
            'join_url' => $baseUrl.'/room/'.$roomId,
            'start_url' => $baseUrl.'/room/'.$roomId.'?role=host&host_token='.urlencode($hostToken),
            'passcode' => null,
            'auto_recording' => 'local',
            'provider' => 'meet',
            'scheduled_at' => $scheduledAt,
        ];
    }
}
