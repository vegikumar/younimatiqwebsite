<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassRecording;
use App\Models\RecordingRequest;
use App\Services\MeetService;
use App\Services\NotificationService;
use App\Services\StudentRecordingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\StudentInertiaProps;
use Illuminate\Support\Facades\Http;

class RecordingController extends Controller
{
    public function index(StudentRecordingAccess $access)
    {
        $user = Auth::user();
        $recordings = $access->queryForStudent($user)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'assigned');

        $requestable = $access->requestableQueryForStudent($user)
            ->with(['course:id,title'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $pendingRequestIds = RecordingRequest::query()
            ->where('student_id', $user->id)
            ->where('status', 'pending')
            ->pluck('class_recording_id')
            ->all();

        return \App\Support\InertiaPage::react('Student/Recordings', [
            'recordings' => collect($recordings->items())->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'course_title' => $r->course?->title,
                'recorded_at_label' => $r->recorded_at?->format('M j, Y'),
                'play_url' => route('student.recordings.stream', $r),
            ])->all(),
            'recordingsLinks' => $recordings->linkCollection()->toArray(),
            'requestable' => $requestable->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->title ?? $r->course?->title,
                'request_url' => route('student.recordings.request', $r),
            ])->values()->all(),
            'pendingRequestIds' => $pendingRequestIds,
        ]);
    }

    public function requestAccess(Request $request, ClassRecording $recording, StudentRecordingAccess $access, NotificationService $notifications)
    {
        $user = Auth::user();
        abort_unless($access->studentRelatedToRecording($user, $recording), 403);
        abort_unless($recording->isAvailable(), 422, 'This recording is no longer available.');

        if ($access->studentCanAccess($user, $recording)) {
            return back()->with('success', 'You already have access to this recording.');
        }

        $req = RecordingRequest::query()->firstOrCreate(
            [
                'student_id' => $user->id,
                'class_recording_id' => $recording->id,
            ],
            [
                'status' => 'pending',
                'note' => $request->input('note'),
            ]
        );

        if ($req->wasRecentlyCreated || $req->status === 'rejected') {
            $req->update([
                'status' => 'pending',
                'note' => $request->input('note', $req->note),
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

        return back()->with('success', 'Request sent. An admin will assign the recording if approved.');
    }

    public function stream(Request $request, ClassRecording $recording, StudentRecordingAccess $access)
    {
        abort_unless($access->studentCanAccess(Auth::user(), $recording), 403);

        if (($recording->provider ?? null) === 'meet' && $recording->meet_recording_id) {
            $secret = (string) config('services.meet.api_secret');
            $url = app(MeetService::class)->recordingFileUrl($recording->meet_recording_id);
            $upstream = Http::withHeaders(['X-Meet-Secret' => $secret])->timeout(60)->get($url);
            abort_unless($upstream->successful(), 404);

            return response($upstream->body(), 200, [
                'Content-Type' => $upstream->header('Content-Type') ?: 'video/webm',
                'Content-Disposition' => 'inline; filename="recording-'.$recording->id.'.webm"',
            ]);
        }

        if ($recording->recording_url) {
            return redirect()->away($recording->recording_url);
        }

        abort(404);
    }
}
