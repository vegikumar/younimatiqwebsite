<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\Payment;
use App\Models\TutorAvailability;
use App\Services\ClassRewardService;
use App\Services\ImageUploadService;
use App\Services\TutorAnalyticsService;
use App\Services\ZoomService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TutorController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    private function tutorCourseIds(int $tutorId)
    {
        return Course::where('tutor_id', $tutorId)->pluck('id');
    }

    private function upcomingBookingsForTutor(int $tutorId, ?int $limit = null)
    {
        $tutorCourseIds = $this->tutorCourseIds($tutorId);

        $query = Booking::with(['student', 'course'])
            ->where(function ($q) use ($tutorId, $tutorCourseIds) {
                $q->where('tutor_id', $tutorId)
                    ->orWhereIn('course_id', $tutorCourseIds);
            })
            ->where('session_type', '!=', 'enrollment')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '>=', now());
            })
            ->orderByRaw('scheduled_at IS NULL, scheduled_at DESC');

        return $limit ? $query->take($limit)->get() : $query->get();
    }

    private function assignedBookingsForTutor(int $tutorId)
    {
        $tutorCourseIds = $this->tutorCourseIds($tutorId);

        return Booking::with(['student', 'course'])
            ->where(function ($q) use ($tutorId, $tutorCourseIds) {
                $q->where('tutor_id', $tutorId)
                    ->orWhereIn('course_id', $tutorCourseIds);
            })
            ->where('session_type', '!=', 'enrollment')
            ->where('status', '!=', 'cancelled')
            ->orderByRaw('scheduled_at IS NULL, scheduled_at DESC')
            ->get();
    }

    private function upcomingCampBookingsForTutor(int $tutorId, ?int $limit = null)
    {
        $query = CampBooking::with('course')
            ->where('tutor_id', $tutorId)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '>=', now());
            })
            ->orderByRaw('scheduled_at IS NULL, scheduled_at DESC');

        return $limit ? $query->take($limit)->get() : $query->get();
    }

    private function assignedCampBookingsForTutor(int $tutorId)
    {
        return CampBooking::with('course')
            ->where('tutor_id', $tutorId)
            ->orderByRaw('scheduled_at IS NULL, scheduled_at DESC')
            ->get();
    }

    private function upcomingLiveSessionsForTutor(int $tutorId, ?int $limit = null)
    {
        $tutorCourseIds = $this->tutorCourseIds($tutorId);

        $query = LiveSession::with([
            'course:id,title',
            'course.units:id,course_id,title,description,order',
        ])
            ->where(function ($q) use ($tutorId, $tutorCourseIds) {
                $q->where('tutor_id', $tutorId)
                    ->orWhereIn('course_id', $tutorCourseIds);
            })
            ->where('start_time', '>=', now())
            ->orderByDesc('start_time');

        return $limit ? $query->take($limit)->get() : $query->get();
    }

    public function dashboard(
        TutorAnalyticsService $analyticsService,
        ClassRewardService $rewards,
        \App\Services\TutorClassPrepService $classPrep,
        \Illuminate\Http\Request $request
    ) {
        $tutorId = Auth::guard('tutor')->id();
        $courseIds = Course::where('tutor_id', $tutorId)->pluck('id');

        $metrics = $analyticsService->getMetrics($tutorId);

        $bookings = $this->upcomingBookingsForTutor($tutorId, 5);
        $campBookings = $this->upcomingCampBookingsForTutor($tutorId, 5);
        $sessions = $this->upcomingLiveSessionsForTutor($tutorId, 5);

        $stats = [
            'courses' => $courseIds->count(),
            'students' => $metrics['learners_taught'],
            'upcoming' => $bookings->count() + $campBookings->count() + $sessions->count(),
            'earnings' => $metrics['monthly_earnings'],
        ];

        $skills = Auth::guard('tutor')->user()->tutorSkills()->with('skill')->get();

        $trainingAssignments = \App\Models\TutorTrainingAssignment::with('module')
            ->where('tutor_id', $tutorId)
            ->latest()
            ->take(5)
            ->get();
        $pendingReviews = \App\Models\TutorTrainingProgress::where('tutor_id', $tutorId)
            ->whereIn('status', ['under_review', 'submitted', 'resubmitted', 'revision_requested'])
            ->count();

        $prep = $classPrep->dashboardPayload($tutorId, $request->query('class'));

        return \App\Support\InertiaPage::react('Tutor/Dashboard', [
            'stats' => $stats,
            'metrics' => TutorInertiaProps::metrics($metrics),
            'skills' => TutorInertiaProps::tutorSkills($skills),
            'bookings' => $bookings->map(fn ($b) => TutorInertiaProps::booking($b))->values()->all(),
            'campBookings' => $campBookings->map(fn ($b) => TutorInertiaProps::campBooking($b))->values()->all(),
            'sessions' => $sessions->map(fn ($s) => TutorInertiaProps::liveSession($s))->values()->all(),
            'training' => [
                'pending_reviews' => $pendingReviews,
                'modules' => $trainingAssignments->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->module?->title ?? 'Training module',
                    'assigned_at_label' => optional($a->assigned_at)?->format('M j, Y'),
                ])->values()->all(),
            ],
            'classPrep' => $prep,
            'completePrepUrl' => route('tutor.class-prep.complete'),
        ]);
    }

    public function completeClassPrep(\Illuminate\Http\Request $request, \App\Services\TutorClassPrepService $classPrep)
    {
        $tutorId = Auth::guard('tutor')->id();
        $data = $request->validate([
            'context_type' => 'required|in:booking,camp_booking,live_session',
            'context_id' => 'required|integer',
            'item_key' => 'required|string|max:120',
            'done' => 'nullable|boolean',
        ]);

        $classPrep->markComplete(
            $tutorId,
            $data['context_type'],
            (int) $data['context_id'],
            $data['item_key'],
            $request->boolean('done', true)
        );

        return back()->with('success', $request->boolean('done', true) ? 'Marked complete.' : 'Marked incomplete.');
    }

    public function classes(ClassRewardService $rewards)
    {
        $tutorId = Auth::guard('tutor')->id();

        $bookings = $this->assignedBookingsForTutor($tutorId);
        $campBookings = $this->assignedCampBookingsForTutor($tutorId);
        $sessions = $this->upcomingLiveSessionsForTutor($tutorId);

        $badgeCatalog = $rewards->badgeCatalog($tutorId);

        return \App\Support\InertiaPage::react('Tutor/Classes', [
            'bookings' => $bookings->map(fn ($b) => TutorInertiaProps::booking($b))->values()->all(),
            'campBookings' => $campBookings->map(fn ($b) => TutorInertiaProps::campBooking($b))->values()->all(),
            'sessions' => $sessions->map(fn ($s) => TutorInertiaProps::liveSession($s))->values()->all(),
            'rewardPresets' => TutorInertiaProps::xpPresets(),
            'badgeCatalog' => TutorInertiaProps::badgeCatalog($badgeCatalog),
        ]);
    }

    /**
     * Redirect tutor into Zoom/Meet as host with a fresh start URL (no Zoom sign-in).
     */
    public function launchBooking(Booking $booking, ZoomService $zoom)
    {
        $this->assertTutorCanAccessBooking($booking);
        $booking->loadMissing(['course', 'student']);

        $resolved = $zoom->resolveHostLaunchUrl(
            $booking,
            ($booking->course?->title ?? 'Live Session').' - '.($booking->student?->name ?? 'Student'),
            optional($booking->scheduled_at)?->format('Y-m-d H:i:s') ?? now()->addHour()->format('Y-m-d H:i:s'),
            45,
            $booking->timezone ?? 'UTC'
        );

        $url = $resolved['url'] ?? null;
        if (! $url) {
            return redirect()
                ->route('tutor.classes')
                ->with('error', 'Could not start as host. Ask admin to verify Zoom credentials, then try Launch again.');
        }

        if (! empty($resolved['fields'])) {
            $booking->forceFill($resolved['fields'])->save();
        }

        return redirect()->away($url);
    }

    public function launchCampBooking(CampBooking $campBooking, ZoomService $zoom)
    {
        $tutorId = Auth::guard('tutor')->id();
        abort_unless((int) $campBooking->tutor_id === (int) $tutorId, 403);

        $resolved = $zoom->resolveHostLaunchUrl(
            $campBooking,
            ($campBooking->camp_name ?? 'Demo Class').' - '.($campBooking->child_name ?? 'Learner'),
            $campBooking->scheduled_at
                ? \Carbon\Carbon::parse($campBooking->scheduled_at)->format('Y-m-d H:i:s')
                : now()->addHour()->format('Y-m-d H:i:s'),
            45,
            $campBooking->timezone ?? 'UTC'
        );

        $url = $resolved['url'] ?? null;
        if (! $url) {
            return redirect()
                ->route('tutor.classes')
                ->with('error', 'Could not start as host. Ask admin to verify Zoom credentials, then try Launch again.');
        }

        if (! empty($resolved['fields'])) {
            $campBooking->forceFill($resolved['fields'])->save();
        }

        return redirect()->away($url);
    }

    public function launchSession(LiveSession $session, ZoomService $zoom)
    {
        $tutorId = Auth::guard('tutor')->id();
        $owns = (int) $session->tutor_id === (int) $tutorId
            || Course::where('id', $session->course_id)->where('tutor_id', $tutorId)->exists();
        abort_unless($owns, 403);
        $session->loadMissing('course');

        $resolved = $zoom->resolveHostLaunchUrl(
            $session,
            ($session->course?->title ?? 'Live Session').' Live Class',
            optional($session->start_time)?->format('Y-m-d H:i:s') ?? now()->addHour()->format('Y-m-d H:i:s'),
            (int) ($session->duration_minutes ?: 45),
            'UTC'
        );

        $url = $resolved['url'] ?? null;
        if (! $url) {
            return redirect()
                ->route('tutor.classes')
                ->with('error', 'Could not start as host.');
        }

        if (! empty($resolved['fields'])) {
            $map = $resolved['fields'];
            $session->forceFill([
                'start_url' => $map['zoom_start_url'] ?? $session->start_url,
                'join_url' => $map['zoom_join_url'] ?? $session->join_url,
                'zoom_passcode' => $map['zoom_passcode'] ?? $session->zoom_passcode,
                'meeting_provider' => $map['meeting_provider'] ?? $session->meeting_provider,
                'meeting_room_id' => $map['meeting_room_id'] ?? $session->meeting_room_id,
                'zoom_meeting_id' => $map['meeting_room_id'] ?? $session->zoom_meeting_id,
            ])->save();
        }

        return redirect()->away($url);
    }

    private function assertTutorCanAccessBooking(Booking $booking): void
    {
        $tutorId = Auth::guard('tutor')->id();
        $owns = (int) $booking->tutor_id === (int) $tutorId
            || Course::where('id', $booking->course_id)->where('tutor_id', $tutorId)->exists();
        abort_unless($owns, 403);
    }

    public function students(ClassRewardService $rewards)
    {
        $tutorId = Auth::guard('tutor')->id();

        $badgeCatalog = $rewards->badgeCatalog($tutorId);

        $bookedCourseIds = Booking::where('tutor_id', $tutorId)->pluck('course_id')->toArray();
        $sessionCourseIds = LiveSession::where('tutor_id', $tutorId)->pluck('course_id')->toArray();
        $ownedCourseIds = Course::where('tutor_id', $tutorId)->pluck('id')->toArray();
        $assignedCourseIds = array_unique(array_merge($bookedCourseIds, $sessionCourseIds, $ownedCourseIds));

        $bookings = Booking::with(['student', 'course'])
            ->whereIn('course_id', $assignedCourseIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $enrollments = Enrollment::with(['user', 'course'])
            ->whereIn('course_id', $assignedCourseIds)
            ->whereIn('status', ['active', 'completed'])
            ->orderByDesc('created_at')
            ->get();

        return \App\Support\InertiaPage::react('Tutor/Students', [
            'enrollments' => $enrollments->map(fn ($e) => TutorInertiaProps::enrollment($e))->values()->all(),
            'bookings' => $bookings->map(fn ($b) => TutorInertiaProps::booking($b))->values()->all(),
            'rewardPresets' => TutorInertiaProps::xpPresets(),
            'badgeCatalog' => TutorInertiaProps::badgeCatalog($badgeCatalog),
        ]);
    }

    public function earnings()
    {
        $tutorId = Auth::guard('tutor')->id();

        // Simple logic for MVP: just get payments for courses this tutor owns
        $courseIds = Course::where('tutor_id', $tutorId)->pluck('id');

        $payments = Payment::with(['user', 'course'])
            ->whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalEarnings = $payments->sum('amount') * 0.8; // Assuming 80% rev share

        return \App\Support\InertiaPage::react('Tutor/Earnings', [
            'payments' => $payments->map(fn ($p) => TutorInertiaProps::payment($p))->values()->all(),
            'totalEarnings' => $totalEarnings,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('avatar')) {
            $path = $this->images->storePath($request->file('avatar'), 'avatars', 'public');
            $user->avatar = $path;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function profile()
    {
        $user = Auth::guard('tutor')->user();

        $availabilities = TutorAvailability::where('tutor_id', $user->id)
            ->whereNull('course_id') // generic availability
            ->get();

        // Map to a grid array [day_of_week => [hour, hour]]
        $grid = [];
        foreach ($availabilities as $av) {
            $hour = (int) substr($av->start_time, 0, 2);
            $grid[$av->day_of_week][] = $hour;
        }

        return \App\Support\InertiaPage::react('Tutor/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatarUrl' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            ],
        ]);
    }
}
