<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LiveSession;
use App\Services\TimezoneService;
use App\Services\ZoomService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveSessionController extends Controller
{
    public function index()
    {
        $courseIds = Course::where('tutor_id', Auth::guard('tutor')->id())->pluck('id');
        $sessions = LiveSession::with('course')
            ->whereIn('course_id', $courseIds)
            ->orderBy('start_time', 'desc')
            ->get();

        $courses = Course::where('tutor_id', Auth::guard('tutor')->id())->get();

        return \App\Support\InertiaPage::react('Tutor/Sessions', [
            'sessions' => $sessions->map(fn ($s) => TutorInertiaProps::tutorLiveSession($s))->values()->all(),
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::courseOption($c))->values()->all(),
            'storeUrl' => route('tutor.sessions.store'),
        ]);
    }

    public function store(Request $request, ZoomService $zoomService)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'start_time' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'max_students' => 'nullable|integer|min:1|max:50',
        ]);

        $course = Course::where('id', $data['course_id'])->where('tutor_id', Auth::guard('tutor')->id())->firstOrFail();

        $tz = Auth::guard('tutor')->user()->timezone ?? 'UTC';
        $startUtc = TimezoneService::toUtc($data['start_time'], $tz);

        $joinUrl = null;
        $startUrl = null;
        $meetingId = 'session_' . uniqid();
        $provider = 'zoom';

        try {
            $meeting = $zoomService->createMeeting($course->title . ' Live Session', $startUtc, (int) $data['duration_minutes'], $tz);
            if ($meeting) {
                $joinUrl = $meeting['join_url'] ?? null;
                $startUrl = $meeting['start_url'] ?? null;
                $meetingId = $meeting['id'] ?? $meetingId;
                $provider = $meeting['provider'] ?? 'zoom';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Zoom meeting creation failed: ' . $e->getMessage());
        }

        LiveSession::create([
            'course_id' => $course->id,
            'tutor_id' => Auth::guard('tutor')->id(),
            'zoom_meeting_id' => $meetingId,
            'start_time' => $startUtc,
            'duration_minutes' => $data['duration_minutes'],
            'max_students' => $data['max_students'] ?? 10,
            'join_url' => $joinUrl,
            'start_url' => $startUrl,
            'meeting_provider' => $provider,
            'meeting_room_id' => isset($meetingId) ? (string) $meetingId : null,
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'Live session scheduled.');
    }
}
