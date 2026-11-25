<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Services\BookingWindowService;
use App\Services\EmailService;
use App\Services\ScheduleConflictService;
use App\Services\StudentApiService;
use App\Services\TimezoneService;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use RespondsWithJson;

    public function __construct(private StudentApiService $studentApi) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $bookingModels = Booking::with(['course', 'tutor'])
            ->where('student_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // One entry per student/course series (recurring-schedule summary), and a flat
        // list of the individual scheduled classes so clients can expand a series.
        $series = $this->studentApi->formatBookingSeries($bookingModels, $user->timezone);

        $classModels = $bookingModels
            ->filter(fn ($b) => $b->session_type === 'scheduled_class')
            ->sortBy('scheduled_at')
            ->values();

        $bookings = $bookingModels
            ->filter(fn ($b) => $b->session_type !== 'scheduled_class')
            ->map(fn ($b) => $this->studentApi->formatBooking($b));

        $classes = $classModels->map(fn ($b) => $this->studentApi->formatBooking($b));

        $enrolledCourseIds = Enrollment::where('user_id', $user->id)->pluck('course_id');

        $availableSessions = LiveSession::with(['course', 'tutor', 'module'])
            ->whereIn('course_id', $enrolledCourseIds)
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => $this->studentApi->formatAvailableSession($s));

        $demoSessions = CampBooking::with(['course', 'tutor'])
            ->where('email', $user->email)
            ->orderByRaw('CASE WHEN scheduled_at IS NOT NULL THEN 0 ELSE 1 END ASC, scheduled_at ASC')
            ->get()
            ->map(fn ($d) => $this->studentApi->formatDemoSession($d));

        return $this->success([
            'series' => $series,
            'bookings' => $bookings,
            'classes' => $classes,
            'available_sessions' => $availableSessions,
            'demo_sessions' => $demoSessions,
        ]);
    }

    public function show(Request $request, Booking $booking)
    {
        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return $this->error('Booking not found.', 404);
        }

        $booking->load(['course', 'tutor']);

        return $this->success([
            'booking' => $this->studentApi->formatBooking($booking),
        ]);
    }

    public function store(Request $request, EmailService $emailService)
    {
        $data = $request->validate([
            'live_session_id' => 'required|exists:live_sessions,id',
        ]);

        $session = LiveSession::with('course')->findOrFail($data['live_session_id']);

        $existing = Booking::where('student_id', $request->user()->id)
            ->where('course_id', $session->course_id)
            ->where('session_type', 'live_session')
            ->where('scheduled_at', $session->start_time)
            ->exists();

        if ($existing) {
            return $this->error('You already booked this session.', 422);
        }

        $booking = Booking::create([
            'student_id' => $request->user()->id,
            'tutor_id' => $session->tutor_id,
            'course_id' => $session->course_id,
            'status' => 'active',
            'session_type' => 'live_session',
            'scheduled_at' => $session->start_time,
            'zoom_join_url' => $session->join_url,
            'zoom_passcode' => $session->zoom_passcode,
            'description' => 'Live session for '.$session->course->title,
        ]);

        try {
            $emailService->notifyAdmins(
                'New Live Session Booked',
                'Student: '.$request->user()->name.'<br>Course: '.$session->course->title
            );
        } catch (\Exception $e) {
            // non-blocking
        }

        return $this->success($this->studentApi->formatBooking($booking->load(['course', 'tutor'])), 'Session booked successfully.', 201);
    }

    public function cancel(Request $request, Booking $booking)
    {
        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return $this->error('Booking not found.', 404);
        }

        if ($booking->status === 'cancelled') {
            return $this->error('This booking is already cancelled.', 422);
        }

        if (! in_array((string) $booking->status, ['active', 'scheduled', 'confirmed', 'pending'], true)) {
            return $this->error('This booking cannot be cancelled.', 422);
        }

        if ($booking->scheduled_at && $booking->scheduled_at->isPast()) {
            return $this->error('Past bookings cannot be cancelled.', 422);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->load(['course', 'tutor']);

        return $this->success(
            $this->studentApi->formatBooking($booking),
            'Booking cancelled.'
        );
    }

    public function availableSlots(
        Request $request,
        Booking $booking,
        BookingWindowService $windows,
        ScheduleConflictService $conflicts
    ) {
        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return $this->error('Booking not found.', 404);
        }

        if (! $this->canMutateSchedule($booking)) {
            return $this->error('This booking cannot be rescheduled.', 422);
        }

        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'timezone' => 'nullable|string|max:100',
        ]);

        $timezone = TimezoneService::sanitizeTimezone(
            $data['timezone'] ?? $booking->parent_timezone ?? $booking->timezone ?? $request->user()->timezone
        );

        $slots = $windows->candidateSlotsForLocalDate($data['date'], $timezone, true);
        $tutorId = $booking->tutor_id;

        foreach ($slots as &$slot) {
            $utc = Carbon::parse($slot['utc_iso'])->utc();
            $available = empty($slot['past']);
            if ($available && $tutorId) {
                $available = ! $conflicts->hasConflict(
                    (int) $tutorId,
                    $utc,
                    BookingWindowService::SLOT_MINUTES,
                    (int) $booking->id
                );
            }
            // Soft policy: require at least 24 hours notice.
            if ($available && $utc->lt(now('UTC')->addHours(24))) {
                $available = false;
            }
            $slot['available'] = $available;
        }
        unset($slot);

        return $this->success([
            'booking_id' => $booking->id,
            'date' => $data['date'],
            'timezone' => $timezone,
            'windows_label' => $windows->windowsLabel(),
            'slots' => $slots,
        ]);
    }

    public function reschedule(
        Request $request,
        Booking $booking,
        BookingWindowService $windows,
        ScheduleConflictService $conflicts,
        ZoomService $zoomService
    ) {
        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return $this->error('Booking not found.', 404);
        }

        if (! $this->canMutateSchedule($booking)) {
            return $this->error('This booking cannot be rescheduled.', 422);
        }

        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'scheduled_date' => 'nullable|date_format:Y-m-d',
            'scheduled_time' => 'nullable|string|max:64',
            'timezone' => 'nullable|string|max:100',
        ]);

        $timezone = TimezoneService::sanitizeTimezone(
            $data['timezone'] ?? $booking->parent_timezone ?? $booking->timezone ?? $request->user()->timezone
        );

        $scheduledAt = null;
        if (! empty($data['scheduled_at'])) {
            $scheduledAt = Carbon::parse($data['scheduled_at'], $timezone)->utc()->second(0);
        } else {
            $scheduledAt = $windows->resolveFromRequest(
                $data['scheduled_date'] ?? null,
                $data['scheduled_time'] ?? null,
                $timezone
            );
        }

        if (! $scheduledAt) {
            return $this->error('Choose a valid date and time.', 422);
        }

        if ($scheduledAt->lt(now('UTC')->addHours(24))) {
            return $this->error('Please reschedule at least 24 hours in advance.', 422);
        }

        if (! $windows->isWithinWindow($scheduledAt)) {
            return $this->error($windows->windowValidationMessage($timezone), 422);
        }

        if ($booking->tutor_id && $conflicts->hasConflict(
            (int) $booking->tutor_id,
            $scheduledAt,
            BookingWindowService::SLOT_MINUTES,
            (int) $booking->id
        )) {
            return $this->error('That time conflicts with your tutor\'s schedule. Pick another slot.', 422);
        }

        $booking->scheduled_at = $scheduledAt;
        $booking->timezone = $timezone;
        $booking->parent_timezone = $timezone;

        if ($booking->zoom_join_url) {
            $booking->loadMissing(['course', 'student']);
            $topic = ($booking->course->title ?? 'Live Session').' - '.($booking->student->name ?? 'Student');
            $zoomData = $zoomService->createMeeting(
                $topic,
                $booking->scheduled_at->format('Y-m-d H:i:s'),
                45,
                $booking->timezone
            );
            foreach (ZoomService::bookingFields($zoomData) as $key => $value) {
                $booking->{$key} = $value;
            }
        }

        $booking->save();
        $booking->load(['course', 'tutor']);

        return $this->success(
            $this->studentApi->formatBooking($booking),
            'Booking rescheduled.'
        );
    }

    private function canMutateSchedule(Booking $booking): bool
    {
        if (! in_array((string) $booking->status, ['active', 'scheduled', 'confirmed', 'pending'], true)) {
            return false;
        }

        if ($booking->scheduled_at && $booking->scheduled_at->isPast()) {
            return false;
        }

        // Live sessions are fixed catalogue slots — cancel + rebook instead.
        if ($booking->session_type === 'live_session') {
            return false;
        }

        return in_array((string) $booking->session_type, ['scheduled_class', 'enrollment', ''], true)
            || $booking->session_type === null;
    }
}
