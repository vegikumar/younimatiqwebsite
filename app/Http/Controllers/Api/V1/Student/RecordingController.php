<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ClassRecording;
use App\Models\RecordingRequest;
use App\Services\MeetService;
use App\Services\NotificationService;
use App\Services\StudentRecordingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecordingController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request, StudentRecordingAccess $access)
    {
        $user = $request->user();

        $recordings = $access->queryForStudent($user)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (ClassRecording $r) => $this->mapRecording($r, 'assigned'));

        $pendingIds = RecordingRequest::query()
            ->where('student_id', $user->id)
            ->where('status', 'pending')
            ->pluck('class_recording_id')
            ->all();

        $requestable = $access->requestableQueryForStudent($user)
            ->with(['course:id,title'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (ClassRecording $r) => array_merge(
                $this->mapRecording($r, 'requestable'),
                [
                    'course_title' => $r->course?->title,
                    'request_status' => in_array($r->id, $pendingIds, true) ? 'pending' : null,
                ]
            ));

        return $this->success([
            'recordings' => $recordings,
            'requestable' => $requestable,
        ]);
    }

    public function requestAccess(Request $request, ClassRecording $recording, StudentRecordingAccess $access, NotificationService $notifications)
    {
        $user = $request->user();

        if (! $access->studentRelatedToRecording($user, $recording)) {
            return $this->error('Forbidden', 403);
        }
        if (! $recording->isAvailable()) {
            return $this->error('This recording is no longer available.', 422);
        }
        if ($access->studentCanAccess($user, $recording)) {
            return $this->success(['message' => 'You already have access to this recording.']);
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $req = RecordingRequest::query()->firstOrCreate(
            [
                'student_id' => $user->id,
                'class_recording_id' => $recording->id,
            ],
            [
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]
        );

        if ($req->wasRecentlyCreated || $req->status === 'rejected') {
            $req->update([
                'status' => 'pending',
                'note' => $data['note'] ?? $req->note,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }

        $adminIds = \App\Models\User::where('role', 'admin')->pluck('id');
        foreach ($adminIds as $adminId) {
            $notifications->notify(
                (int) $adminId,
                'Recording access requested',
                $user->name.' requested access to “'.$recording->title.'”.',
                route('admin.recordings.index', ['tab' => 'requests']),
                'warning'
            );
        }

        return $this->success([
            'message' => 'Request sent. An admin will assign the recording if approved.',
            'request_status' => 'pending',
        ]);
    }

    public function stream(Request $request, ClassRecording $recording, StudentRecordingAccess $access)
    {
        if (! $access->studentCanAccess($request->user(), $recording)) {
            return $this->error('Forbidden', 403);
        }

        if (($recording->provider ?? null) === 'meet' && $recording->meet_recording_id) {
            $secret = (string) config('services.meet.api_secret');
            $url = app(MeetService::class)->recordingFileUrl($recording->meet_recording_id);
            $upstream = Http::withHeaders(['X-Meet-Secret' => $secret])
                ->timeout(60)
                ->withOptions(['stream' => true])
                ->get($url);

            if (! $upstream->successful()) {
                return $this->error('Recording unavailable', 404);
            }

            return response($upstream->body(), 200, [
                'Content-Type' => $upstream->header('Content-Type') ?: 'video/webm',
                'Content-Disposition' => 'inline; filename="recording-'.$recording->id.'.webm"',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        if ($recording->recording_url && str_starts_with($recording->recording_url, 'http')) {
            return redirect()->away($recording->recording_url);
        }

        return $this->error('Recording unavailable', 404);
    }

    private function mapRecording(ClassRecording $r, string $access): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'provider' => $r->provider ?? 'manual',
            'course_id' => $r->course_id,
            'booking_id' => $r->booking_id,
            'meeting_room_id' => $r->meeting_room_id,
            'duration_minutes' => $r->duration_minutes,
            'recorded_at' => $r->recorded_at?->toIso8601String(),
            'expires_at' => $r->expires_at?->toIso8601String(),
            'access' => $access,
            'stream_url' => $access === 'assigned'
                ? url('/api/v1/student/recordings/'.$r->id.'/stream')
                : null,
        ];
    }
}
