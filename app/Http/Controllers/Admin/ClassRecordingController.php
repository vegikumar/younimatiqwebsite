<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassRecording;
use App\Models\LiveSession;
use App\Models\RecordingAssignment;
use App\Models\RecordingRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassRecordingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'timeline');

        $recordings = ClassRecording::query()
            ->with(['course:id,title', 'liveSession.tutor:id,name', 'booking.tutor:id,name', 'booking.student:id,name', 'assignments.student:id,name', 'requests' => fn ($q) => $q->where('status', 'pending')])
            ->when($request->get('q'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('meeting_room_id', 'like', "%{$search}%");
                });
            })
            ->orderByDesc(DB::raw('COALESCE(recorded_at, created_at)'))
            ->paginate(25)
            ->withQueryString();

        $pendingRequests = RecordingRequest::query()
            ->with(['student:id,name,email', 'recording.course:id,title'])
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $students = User::query()
            ->where('role', 'student')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $timeline = $this->buildTimeline($request->get('from'), $request->get('to'));

        return \App\Support\InertiaPage::react('Admin/Recordings/Index', [
            'tab' => $tab,
            'retentionDays' => ClassRecording::RETENTION_DAYS,
            'filters' => [
                'q' => $request->get('q', ''),
                'from' => $request->get('from', now()->subDays(14)->toDateString()),
                'to' => $request->get('to', now()->addDays(7)->toDateString()),
            ],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'email' => $s->email])->values()->all(),
            'pendingRequests' => $pendingRequests->map(fn ($req) => [
                'id' => $req->id,
                'studentName' => $req->student?->name,
                'studentEmail' => $req->student?->email,
                'recordingTitle' => $req->recording?->title,
                'recordedAtLabel' => optional($req->recording?->recorded_at)->format('M j, Y g:i A'),
                'expiresAtLabel' => optional($req->recording?->expires_at)->format('M j, Y'),
                'note' => $req->note,
                'classRecordingId' => $req->class_recording_id,
                'studentId' => $req->student_id,
                'rejectUrl' => route('admin.recordings.requests.reject', $req),
            ])->values()->all(),
            'recordings' => \App\Support\AdminInertiaProps::paginate($recordings, fn ($r) => \App\Support\AdminInertiaProps::recording($r)),
            'timeline' => collect($timeline)->map(function ($row) {
                $recording = $row['recording'];

                return [
                    'whenLabel' => optional($row['when'])->format('M j, Y g:i A'),
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'status' => $row['status'],
                    'tutor' => $row['tutor'],
                    'student' => $row['student'],
                    'recordingTitle' => $recording?->title,
                    'recordedAtLabel' => optional($row['recorded_at'])->format('M j, Y'),
                    'deletesAtLabel' => optional($row['deletes_at'])->format('M j, Y'),
                    'deletesAtPast' => optional($row['deletes_at'])?->isPast() ?? false,
                    'deletedAtLabel' => optional($row['deleted_at'])->format('M j, Y'),
                ];
            })->values()->all(),
        ]);
    }

    public function assign(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'class_recording_id' => 'required|exists:class_recordings,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'recording_request_id' => 'nullable|exists:recording_requests,id',
        ]);

        $recording = ClassRecording::findOrFail($data['class_recording_id']);
        abort_unless($recording->isAvailable(), 422, 'Recording is expired or deleted.');

        $expiresAt = $recording->expires_at
            ?? ($recording->recorded_at ?? $recording->created_at ?? now())->copy()->addDays(ClassRecording::RETENTION_DAYS);

        foreach ($data['student_ids'] as $studentId) {
            RecordingAssignment::query()->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'class_recording_id' => $recording->id,
                ],
                [
                    'assigned_by' => Auth::id(),
                    'expires_at' => $expiresAt,
                ]
            );

            $notifications->notify(
                (int) $studentId,
                'Recording assigned',
                'A class recording is now available for you until '.$expiresAt->format('M j, Y').'.',
                route('student.recordings'),
                'info'
            );
        }

        if (! empty($data['recording_request_id'])) {
            RecordingRequest::where('id', $data['recording_request_id'])->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        } else {
            RecordingRequest::query()
                ->where('class_recording_id', $recording->id)
                ->whereIn('student_id', $data['student_ids'])
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
        }

        return back()->with('success', 'Recording assigned to selected student(s).');
    }

    public function rejectRequest(RecordingRequest $recordingRequest)
    {
        $recordingRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Request rejected.');
    }

    protected function buildTimeline(?string $from, ?string $to): array
    {
        $fromDt = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(14)->startOfDay();
        $toDt = $to ? Carbon::parse($to)->endOfDay() : now()->addDays(7)->endOfDay();

        $items = collect();

        Booking::query()
            ->with(['student:id,name', 'tutor:id,name', 'course:id,title'])
            ->whereBetween('scheduled_at', [$fromDt, $toDt])
            ->orderByDesc('scheduled_at')
            ->limit(200)
            ->get()
            ->each(function (Booking $b) use ($items) {
                $recording = ClassRecording::query()
                    ->where(function ($q) use ($b) {
                        $q->where('booking_id', $b->id);
                        if ($b->meeting_room_id) {
                            $q->orWhere('meeting_room_id', $b->meeting_room_id);
                        }
                    })
                    ->orderByDesc('id')
                    ->first();

                $items->push($this->timelineRow(
                    type: '1:1 / demo',
                    when: $b->scheduled_at,
                    title: $b->course?->title ?? 'Booking #'.$b->id,
                    tutor: $b->tutor?->name,
                    student: $b->student?->name,
                    status: $b->status,
                    roomId: $b->meeting_room_id,
                    recording: $recording,
                    meta: ['booking_id' => $b->id, 'provider' => $b->meeting_provider]
                ));
            });

        LiveSession::query()
            ->with(['course:id,title', 'tutor:id,name'])
            ->whereBetween('start_time', [$fromDt, $toDt])
            ->orderByDesc('start_time')
            ->limit(200)
            ->get()
            ->each(function (LiveSession $s) use ($items) {
                $recording = ClassRecording::query()
                    ->where(function ($q) use ($s) {
                        $q->where('live_session_id', $s->id);
                        if ($s->meeting_room_id) {
                            $q->orWhere('meeting_room_id', $s->meeting_room_id);
                        }
                    })
                    ->orderByDesc('id')
                    ->first();

                $items->push($this->timelineRow(
                    type: 'Group session',
                    when: $s->start_time,
                    title: $s->course?->title ?? 'Live session #'.$s->id,
                    tutor: $s->tutor?->name,
                    student: null,
                    status: $s->status ?? 'scheduled',
                    roomId: $s->meeting_room_id,
                    recording: $recording,
                    meta: ['live_session_id' => $s->id, 'provider' => $s->meeting_provider, 'duration' => $s->duration_minutes]
                ));
            });

        CampBooking::query()
            ->with(['tutor:id,name', 'course:id,title'])
            ->whereBetween('scheduled_at', [$fromDt, $toDt])
            ->orderByDesc('scheduled_at')
            ->limit(100)
            ->get()
            ->each(function (CampBooking $c) use ($items) {
                $recording = $c->meeting_room_id
                    ? ClassRecording::where('meeting_room_id', $c->meeting_room_id)->orderByDesc('id')->first()
                    : null;

                $items->push($this->timelineRow(
                    type: 'Camp',
                    when: $c->scheduled_at,
                    title: $c->camp_name ?? $c->course?->title ?? 'Camp booking #'.$c->id,
                    tutor: $c->tutor?->name,
                    student: $c->child_name ?? $c->email,
                    status: $c->payment_status ?? 'scheduled',
                    roomId: $c->meeting_room_id,
                    recording: $recording,
                    meta: ['camp_booking_id' => $c->id]
                ));
            });

        return $items->sortByDesc(fn ($i) => $i['when']?->timestamp ?? 0)->values()->all();
    }

    protected function timelineRow(
        string $type,
        $when,
        string $title,
        ?string $tutor,
        ?string $student,
        ?string $status,
        ?string $roomId,
        ?ClassRecording $recording,
        array $meta = []
    ): array {
        $recordedAt = $recording?->recorded_at ?? $recording?->created_at;
        $deleteAt = $recording?->expires_at
            ?? ($recordedAt ? $recordedAt->copy()->addDays(ClassRecording::RETENTION_DAYS) : null);

        return [
            'type' => $type,
            'when' => $when instanceof Carbon ? $when : ($when ? Carbon::parse($when) : null),
            'title' => $title,
            'tutor' => $tutor,
            'student' => $student,
            'status' => $status,
            'room_id' => $roomId,
            'recording' => $recording,
            'recorded_at' => $recordedAt,
            'deletes_at' => $deleteAt,
            'deleted_at' => $recording?->deleted_from_storage_at,
            'meta' => $meta,
        ];
    }
}
