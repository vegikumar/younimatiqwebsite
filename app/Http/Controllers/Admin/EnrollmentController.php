<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\EmailLog;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Services\ClassScheduleService;
use App\Services\EnrollmentService;
use App\Services\TimezoneService;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $courseId = $request->query('course_id');
        $status = $request->query('status');

        // Sort by latest activity so re-enrolls (same student+course) surface at the top.
        $enrollmentsQuery = Enrollment::with(['user', 'course', 'payment'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
        $bookingsQuery = Booking::with(['student', 'course', 'tutor'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($search) {
            $enrollmentsQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
            $bookingsQuery->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($courseId) {
            $enrollmentsQuery->where('course_id', $courseId);
            $bookingsQuery->where('course_id', $courseId);
        }

        if ($status) {
            $enrollmentsQuery->where('status', $status);
            $bookingsQuery->where('status', $status);
        }

        // One row per student/course series: drop the individual class rows and
        // collapse duplicates so a student with 8 recurring classes shows once.
        $bookingsQuery->where('session_type', '!=', 'scheduled_class');

        $enrollments = $enrollmentsQuery->paginate(30)->withQueryString();
        $bookings = $this->paginateSeries($bookingsQuery);

        $students = User::where('role', 'student')->orderBy('name')->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $allTutors = User::where('role', 'tutor')->orderBy('name')->get();

        $campBookingsQuery = CampBooking::with(['course', 'tutor'])
            ->where('camp_name', 'not like', 'Consultation%')
            ->orderBy('created_at', 'desc');
        if ($search) {
            $campBookingsQuery->where(function ($q) use ($search) {
                $q->where('parent_name', 'like', "%{$search}%")
                    ->orWhere('child_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        $campBookings = $campBookingsQuery->paginate(30)->withQueryString();

        // Summarise each booking's recurring class series (classes, days, times)
        // BEFORE tutor availability so enrollment rows can inherit next class time.
        $seriesRows = collect();
        if ($bookings->isNotEmpty()) {
            $seriesRows = Booking::with('course')
                ->where(function ($q) use ($bookings) {
                    foreach ($bookings as $b) {
                        $q->orWhere(fn ($qq) => $qq
                            ->where('student_id', $b->student_id)
                            ->where('course_id', $b->course_id));
                    }
                })
                ->where('session_type', 'scheduled_class')
                ->whereNotNull('scheduled_at')
                ->orderBy('scheduled_at')
                ->get()
                ->groupBy(fn ($s) => $s->student_id.'-'.$s->course_id);
        }

        $bookingsSchedule = [];
        foreach ($bookings as $b) {
            $series = $seriesRows->get($b->student_id.'-'.$b->course_id, collect());

            // Resolve the series timezone: booking first, then the classes themselves,
            // then the student's profile — a bare UTC default means "never set".
            $sanitized = collect([
                $b->parent_timezone,
                $b->timezone,
                $series->first()?->parent_timezone,
                $series->first()?->timezone,
                $b->student?->timezone,
            ])
                ->filter(fn ($tz) => ! TimezoneService::isUnset($tz))
                ->map(fn ($tz) => TimezoneService::sanitizeTimezone($tz))
                ->first() ?? 'UTC';

            $days = [];
            $times = [];
            $startDate = null;

            foreach ($series as $s) {
                $local = Carbon::parse($s->scheduled_at)->setTimezone($sanitized);
                $dow = (int) $local->dayOfWeek;
                $days[$dow] = true;
                $times[$dow] = $local->format('H:i');

                $dateStr = $local->toDateString();
                if ($startDate === null || $dateStr < $startDate) {
                    $startDate = $dateStr;
                }
            }

            $classes = $series->count();
            if ($classes === 0) {
                $enrollment = Enrollment::where('user_id', $b->student_id)
                    ->where('course_id', $b->course_id)
                    ->first();
                $classes = (int) ($enrollment->paid_classes_count ?? ($b->course->number_of_classes ?? 1));
            }

            // Enrollment placeholder rows often have null scheduled_at; surface the
            // next upcoming class (or earliest) so the Schedule column is not blank.
            $nextClass = $series->first(fn ($s) => $s->scheduled_at && $s->scheduled_at->gte(now()->subHour()))
                ?: $series->first();
            $nextAt = $nextClass?->scheduled_at;

            if (! $b->scheduled_at && $nextAt && $b->session_type === 'enrollment') {
                $b->scheduled_at = $nextAt;
                if (TimezoneService::isUnset($b->timezone)) {
                    $b->timezone = $sanitized;
                }
                if (TimezoneService::isUnset($b->parent_timezone)) {
                    $b->parent_timezone = $sanitized;
                }
                // Persist so tutor-assign / emails / Zoom keep a real start time.
                $b->save();
            }

            $bookingsSchedule[$b->id] = [
                'classes' => max(1, $classes),
                'days' => array_keys($days),
                'times' => $times,
                'start_date' => $startDate ?? now()->toDateString(),
                'timezone' => $sanitized,
                'next_at' => $nextAt?->toIso8601String(),
            ];
        }

        // Compute Tutor Availability & Conflict Occupied Data per Booking.
        // Batch-load every conflicting booking/session in 2 queries instead of
        // 1-2 queries per (booking × tutor) pair.
        $tutorsAvailability = [];
        $bookingsWithTime = $bookings->filter(fn ($b) => $b->scheduled_at)->values();

        if ($bookingsWithTime->isNotEmpty()) {
            $tutorIds = $allTutors->pluck('id');

            // All candidate conflicts: any non-cancelled booking for these tutors
            // that falls inside ANY of the ±45min windows on the page.
            $windows = $bookingsWithTime->map(fn ($b) => [
                'start' => Carbon::parse($b->scheduled_at)->subMinutes(45),
                'end' => Carbon::parse($b->scheduled_at)->addMinutes(45),
            ])->values();

            $conflictBookings = Booking::with(['student', 'course'])
                ->whereIn('tutor_id', $tutorIds)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('scheduled_at')
                ->where(function ($q) use ($windows) {
                    foreach ($windows as $w) {
                        $q->orWhereBetween('scheduled_at', [$w['start'], $w['end']]);
                    }
                })
                ->get()
                ->groupBy('tutor_id');

            $conflictSessions = LiveSession::with('course')
                ->whereIn('tutor_id', $tutorIds)
                ->whereNotNull('start_time')
                ->where(function ($q) use ($windows) {
                    foreach ($windows as $w) {
                        $q->orWhereBetween('start_time', [$w['start'], $w['end']]);
                    }
                })
                ->get()
                ->groupBy('tutor_id');

            foreach ($bookings as $b) {
                $tutorsAvailability[$b->id] = [];
                $window = $b->scheduled_at
                    ? [Carbon::parse($b->scheduled_at)->subMinutes(45), Carbon::parse($b->scheduled_at)->addMinutes(45)]
                    : null;

                foreach ($allTutors as $tutor) {
                    $isOccupied = false;
                    $occupiedStudent = '';
                    $occupiedCourse = '';

                    if ($window) {
                        $conflictBooking = $conflictBookings->get($tutor->id)?->first(fn ($c) => $c->id !== $b->id
                            && $c->scheduled_at
                            && $c->scheduled_at->between($window[0], $window[1])
                        );

                        if ($conflictBooking) {
                            $isOccupied = true;
                            $occupiedStudent = $conflictBooking->student->name ?? 'Student';
                            $occupiedCourse = $conflictBooking->course->title ?? 'Course Class';
                        } else {
                            $conflictSession = $conflictSessions->get($tutor->id)?->first(fn ($s) => $s->start_time
                                && $s->start_time->between($window[0], $window[1])
                            );

                            if ($conflictSession) {
                                $isOccupied = true;
                                $occupiedStudent = 'Group Class';
                                $occupiedCourse = $conflictSession->course->title ?? 'Live Class';
                            }
                        }
                    }

                    $tutorsAvailability[$b->id][] = [
                        'tutor' => $tutor,
                        'is_occupied' => $isOccupied,
                        'occupied_student' => $occupiedStudent,
                        'occupied_course' => $occupiedCourse,
                    ];
                }
            }
        } else {
            foreach ($bookings as $b) {
                $tutorsAvailability[$b->id] = $allTutors->map(fn ($tutor) => [
                    'tutor' => $tutor,
                    'is_occupied' => false,
                    'occupied_student' => '',
                    'occupied_course' => '',
                ])->all();
            }
        }

        // Compute Tutor Availability per CampBooking — batch-loaded conflicts.
        $campTutorsAvailability = [];
        $campWithTime = $campBookings->filter(fn ($cb) => $cb->scheduled_at)->values();

        if ($campWithTime->isNotEmpty()) {
            $tutorIds = $allTutors->pluck('id');
            $campWindows = $campWithTime->map(fn ($cb) => [
                'start' => Carbon::parse($cb->scheduled_at)->subMinutes(45),
                'end' => Carbon::parse($cb->scheduled_at)->addMinutes(45),
            ])->values();

            $campConflictBookings = Booking::with(['student', 'course'])
                ->whereIn('tutor_id', $tutorIds)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('scheduled_at')
                ->where(function ($q) use ($campWindows) {
                    foreach ($campWindows as $w) {
                        $q->orWhereBetween('scheduled_at', [$w['start'], $w['end']]);
                    }
                })
                ->get()
                ->groupBy('tutor_id');

            $campConflictCamps = CampBooking::whereIn('tutor_id', $tutorIds)
                ->whereNotNull('scheduled_at')
                ->where(function ($q) use ($campWindows) {
                    foreach ($campWindows as $w) {
                        $q->orWhereBetween('scheduled_at', [$w['start'], $w['end']]);
                    }
                })
                ->get()
                ->groupBy('tutor_id');

            foreach ($campBookings as $cb) {
                $campTutorsAvailability[$cb->id] = [];
                $window = $cb->scheduled_at
                    ? [Carbon::parse($cb->scheduled_at)->subMinutes(45), Carbon::parse($cb->scheduled_at)->addMinutes(45)]
                    : null;

                foreach ($allTutors as $tutor) {
                    $isOccupied = false;
                    $occupiedStudent = '';
                    $occupiedCourse = '';

                    if ($window) {
                        $conflictBooking = $campConflictBookings->get($tutor->id)?->first(fn ($c) => $c->scheduled_at
                            && $c->scheduled_at->between($window[0], $window[1])
                        );

                        if ($conflictBooking) {
                            $isOccupied = true;
                            $occupiedStudent = $conflictBooking->student->name ?? 'Student';
                            $occupiedCourse = $conflictBooking->course->title ?? 'Course Class';
                        } else {
                            $conflictCamp = $campConflictCamps->get($tutor->id)?->first(fn ($c) => $c->id !== $cb->id
                                && $c->scheduled_at
                                && $c->scheduled_at->between($window[0], $window[1])
                            );

                            if ($conflictCamp) {
                                $isOccupied = true;
                                $occupiedStudent = $conflictCamp->child_name ?? $conflictCamp->parent_name;
                                $occupiedCourse = $conflictCamp->camp_name;
                            }
                        }
                    }

                    $campTutorsAvailability[$cb->id][] = [
                        'tutor' => $tutor,
                        'is_occupied' => $isOccupied,
                        'occupied_student' => $occupiedStudent,
                        'occupied_course' => $occupiedCourse,
                    ];
                }
            }
        } else {
            foreach ($campBookings as $cb) {
                $campTutorsAvailability[$cb->id] = $allTutors->map(fn ($tutor) => [
                    'tutor' => $tutor,
                    'is_occupied' => false,
                    'occupied_student' => '',
                    'occupied_course' => '',
                ])->all();
            }
        }

        return \App\Support\InertiaPage::react(
            'Admin/Enrollments/Index',
            \App\Support\AdminInertiaProps::enrollmentIndexPage(
                $enrollments,
                $bookings,
                $campBookings,
                $students,
                $courses,
                $tutorsAvailability,
                $campTutorsAvailability,
                $bookingsSchedule,
                $search,
                $courseId,
                $status,
                (string) $request->query('tab', 'enrollments')
            )
        );
    }

    /**
     * Collapse bookings down to one row per student+course series and paginate them.
     *
     * A student who bought 8 recurring classes has 8+ booking rows in the table;
     * admins only need a single row to view/edit the whole series.
     *
     * @return LengthAwarePaginator<int, Booking>
     */
    protected function paginateSeries($query)
    {
        $perPage = 30;
        $page = max(1, (int) request()->query('page', 1));

        $series = $query->get()
            ->groupBy(fn ($b) => $b->student_id.'-'.$b->course_id)
            ->map(fn ($group) => $this->representativeBooking($group))
            ->sortByDesc(fn (Booking $b) => optional($b->updated_at)->timestamp ?? optional($b->created_at)->timestamp ?? 0)
            ->values();

        return new LengthAwarePaginator(
            $series->forPage($page, $perPage)->values(),
            $series->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Pick the row that should represent a student+course series: the enrollment
     * placeholder if one exists, otherwise the newest booking. Stamp it with the
     * series' latest activity so re-enrolls sort to the top of the admin list.
     */
    protected function representativeBooking($group): Booking
    {
        $rep = $group->firstWhere('session_type', 'enrollment')
            ?: $group->sortByDesc('id')->first()
            ?: $group->first();

        $latest = $group->sortByDesc(fn (Booking $b) => optional($b->updated_at)->timestamp ?? 0)->first();
        if ($latest && $rep && $latest->updated_at && (! $rep->updated_at || $latest->updated_at->gt($rep->updated_at))) {
            $rep->setAttribute('updated_at', $latest->updated_at);
        }

        return $rep;
    }

    public function store(Request $request, EnrollmentService $enrollmentService)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'number_of_classes' => 'nullable|integer|min:1|max:48',
            'schedule_start_date' => 'nullable|date',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'integer|between:0,6',
            'schedule_times' => 'nullable|array',
            'delivery_mode' => 'nullable|in:live,self-paced',
            'timezone' => 'nullable|string',
        ]);

        $course = Course::findOrFail($data['course_id']);

        $classes = (int) ($data['number_of_classes'] ?? 0);
        if ($classes <= 0) {
            $classes = max(1, (int) ($course->number_of_classes ?? 1));
        }

        $schedule = app(ClassScheduleService::class)->normalize([
            'number_of_classes' => $classes,
            'start_date' => $data['schedule_start_date'] ?? null,
            'days' => $data['schedule_days'] ?? [],
            'times' => $data['schedule_times'] ?? [],
            'timezone' => $data['timezone'] ?? null,
        ]);

        $payment = Payment::create([
            'student_id' => $data['user_id'],
            'course_id' => $data['course_id'],
            'amount' => 0,
            'status' => 'completed',
            'gateway' => 'manual',
            'transaction_id' => 'manual_'.uniqid(),
            'pricing_model' => 'full',
            'delivery_mode' => $data['delivery_mode'] ?? ($course->course_type ?: 'live'),
            'schedule' => $schedule ?? ['classes' => $classes],
        ]);

        $enrollment = $enrollmentService->enrollFromPayment($payment);
        $wasExisting = $enrollment->wasRecentlyCreated === false
            && $enrollment->created_at
            && $enrollment->created_at->lt(now()->subMinute());

        $msg = $wasExisting
            ? 'Existing enrollment updated for this student/course (now at top of the list).'
            : 'Student enrolled successfully.';

        if (config('mail.enrollment_notifications', false)) {
            $msg .= ' Class details emailed.';
        }

        return back()->with('success', $msg);
    }

    public function sendEnrollmentEmail(Enrollment $enrollment, EnrollmentService $enrollmentService)
    {
        $recipients = $enrollmentService->sendEnrollmentNotification($enrollment);

        if (empty($recipients)) {
            return back()->with('error', 'Unable to send enrollment notification (missing student or course).');
        }

        $list = implode(', ', $recipients);

        return back()->with('success', "Enrollment & class details emailed to {$list}.");
    }

    public function autoAssignTutor(Request $request, Booking $booking, ZoomService $zoomService, \App\Services\TutorMatchingService $matcher)
    {
        if (! $booking->course_id) {
            return back()->with('error', 'Booking has no course to match skills against.');
        }
        $scheduledAt = $booking->scheduled_at ? \Carbon\Carbon::parse($booking->scheduled_at) : null;
        $tutorId = $matcher->bestTutorId((int) $booking->course_id, $scheduledAt);
        if (! $tutorId) {
            return back()->with('error', 'No matching tutor found with required verified skills.');
        }
        $request->merge(['tutor_id' => $tutorId]);

        return $this->assignTutor($request, $booking, $zoomService);
    }

    public function assignTutor(Request $request, Booking $booking, ZoomService $zoomService)
    {
        $data = $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'timezone' => 'nullable|string',
        ]);

        $earliestClass = null;
        if (! empty($data['scheduled_at'])) {
            $tz = $data['timezone'] ?? 'UTC';
            $booking->scheduled_at = Carbon::parse($data['scheduled_at'], $tz)->setTimezone('UTC');
            $booking->timezone = $tz;
            $booking->save();
        } elseif (! $booking->scheduled_at && $booking->student_id && $booking->course_id) {
            // If the row being assigned has no scheduled_at (e.g. session_type = enrollment),
            // find the earliest scheduled class in the series to calculate meeting fields and email schedule
            $earliestClass = Booking::where('student_id', $booking->student_id)
                ->where('course_id', $booking->course_id)
                ->where('session_type', 'scheduled_class')
                ->whereNotNull('scheduled_at')
                ->where('status', '!=', 'cancelled')
                ->orderBy('scheduled_at')
                ->first();

            if ($earliestClass && $earliestClass->scheduled_at) {
                $booking->scheduled_at = $earliestClass->scheduled_at;
                if (TimezoneService::isUnset($booking->timezone)) {
                    $booking->timezone = $earliestClass->timezone ?: $earliestClass->parent_timezone;
                }
                if (TimezoneService::isUnset($booking->parent_timezone)) {
                    $booking->parent_timezone = $earliestClass->parent_timezone ?: $earliestClass->timezone;
                }
                $booking->save();
            }
        }

        $tutor = User::findOrFail($data['tutor_id']);

        $topic = ($booking->course?->title ?? 'Live Session').' - '.($booking->student?->name ?? 'Student');
        $effectiveStartTime = $booking->scheduled_at ?: ($earliestClass?->scheduled_at ?? null);
        $startTime = $effectiveStartTime
            ? $effectiveStartTime->format('Y-m-d H:i:s')
            : now()->addDay()->format('Y-m-d 10:00:00');

        try {
            // Respect admin meeting_provider; regenerate when switching Zoom ↔ Meet or links missing
            $fields = $zoomService->ensureMeetingFields(
                $booking,
                $topic,
                $startTime,
                45,
                $booking->timezone ?? 'UTC'
            );
        } catch (\Throwable $e) {
            Log::warning('Meeting create failed during tutor assign: '.$e->getMessage());
            $fields = [];
        }

        $joinUrl = $fields['zoom_join_url'] ?? $booking->zoom_join_url;
        $startUrl = $fields['zoom_start_url'] ?? $booking->zoom_start_url;
        $provider = $fields['meeting_provider'] ?? $zoomService->activeProvider();
        $providerLabel = $zoomService->providerLabel($provider);

        $booking->update(array_merge([
            'tutor_id' => $tutor->id,
            'status' => 'confirmed',
        ], $fields));

        // Keep the whole student+course series in sync (enrollment + scheduled classes).
        if ($booking->student_id && $booking->course_id) {
            Booking::where('student_id', $booking->student_id)
                ->where('course_id', $booking->course_id)
                ->where('id', '!=', $booking->id)
                ->where('status', '!=', 'cancelled')
                ->update(array_filter([
                    'tutor_id' => $tutor->id,
                    'status' => 'confirmed',
                    'zoom_join_url' => $joinUrl,
                    'zoom_start_url' => $startUrl,
                    'zoom_passcode' => $fields['zoom_passcode'] ?? $booking->zoom_passcode,
                    'meeting_provider' => $provider,
                    'meeting_room_id' => $fields['meeting_room_id'] ?? $booking->meeting_room_id,
                ], fn ($v) => $v !== null));
        }

        // Apply admin SMTP configuration so emails dispatch reliably
        try {
            app(\App\Services\EmailService::class)->applyAdminSmtpConfig();
        } catch (\Throwable $e) {
            Log::warning('SMTP apply in assignTutor failed: '.$e->getMessage());
        }

        $scheduledTimeStr = $this->formatBookingScheduleLabel(
            $booking->scheduled_at ?: ($earliestClass?->scheduled_at ?? null),
            $booking->parent_timezone ?: ($booking->timezone ?: ($earliestClass?->timezone ?? null)),
            $booking
        );
        $courseTitle = $booking->course?->title ?? 'Live 1:1 Course';
        $student = $booking->student;
        $studentName = $student?->name ?? 'Student';

        $enrollment = Enrollment::where('user_id', $booking->student_id)
            ->where('course_id', $booking->course_id)
            ->first();
        $totalSessions = (int) ($enrollment?->paid_classes_count ?: ($booking->course?->number_of_classes ?: 1));
        $sessionsLabel = $totalSessions . ' Class' . ($totalSessions > 1 ? 'es' : '');

        // 1. Notify Tutor
        try {
            Notification::create([
                'user_id' => $tutor->id,
                'type' => 'booking_assignment',
                'title' => 'New Class Booking Assigned',
                'body' => "You have been assigned to teach {$courseTitle} for {$studentName} on {$scheduledTimeStr}.",
                'action_url' => url('/tutor/classes'),
            ]);

            if (config('mail.tutor_assignment_notifications', false)) {
                $tutorSubject = 'Assigned to Teach: '.$courseTitle.' for '.$studentName.' - YounimatiQ';
                $tutorBody = '<h3>New Class Booking Assigned — YounimatiQ</h3>'
                    .'<p>Hi '.e($tutor->name).',</p>'
                    .'<p>You have been assigned as the tutor for an upcoming live 1:1 class session!</p>'
                    .'<div style="background:#f8fafc; border-left:4px solid #0057bf; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                    .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Class & Student Details</h4>'
                    .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($studentName).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> '.e($courseTitle).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Total Sessions:</strong> '.e($sessionsLabel).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Delivery Mode:</strong> Live 1:1</p>'
                    .'<p style="margin:6px 0;"><strong>● Scheduled Date & Time:</strong> '.e($scheduledTimeStr).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Host Start Link:</strong> <a href="'.e((string) $startUrl).'" style="color:#0057bf; font-weight:600; word-break:break-all;">'.e((string) $startUrl).'</a></p>'
                    .'<p style="margin:6px 0;"><strong>● Tutor Portal:</strong> <a href="'.e(route('login.tutor')).'" style="color:#0057bf; font-weight:600;">'.e(route('login.tutor')).'</a></p>'
                    .'</div>'
                    .'<div style="text-align:center; margin:25px 0;">'
                    .'<a href="'.e((string) $startUrl).'" style="background:#0057bf; color:#ffffff; font-weight:700; font-size:15px; padding:14px 32px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(0,87,191,0.3);">🚀 Launch Class as Host ('.e($providerLabel).')</a>'
                    .'</div>'
                    .'<p>Please launch the class 5 minutes before scheduled start time and prepare the lesson materials.</p>'
                    .'<p style="margin-top:25px; line-height:1.6;">Happy Teaching!<br>'
                    .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                    .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

                Mail::to($tutor->email)->send(new TemplateMail($tutorSubject, $tutorBody));
                EmailLog::create([
                    'to_email' => $tutor->email,
                    'subject' => $tutorSubject,
                    'template_slug' => 'tutor_assigned_tutor',
                    'status' => 'sent',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send tutor assignment email: '.$e->getMessage());
            if (config('mail.tutor_assignment_notifications', false)) {
                EmailLog::create([
                    'to_email' => $tutor->email,
                    'subject' => $tutorSubject ?? ('Assigned to Teach: '.$courseTitle),
                    'template_slug' => 'tutor_assigned_tutor',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // 2. Notify Student
        if ($student) {
            try {
                Notification::create([
                    'user_id' => $student->id,
                    'type' => 'booking_confirmed',
                    'title' => 'Tutor Assigned & Class Confirmed!',
                    'body' => "Tutor {$tutor->name} has been assigned to your class {$courseTitle}.",
                    'action_url' => url('/student/dashboard'),
                ]);

                if (config('mail.tutor_assignment_notifications', false)) {
                    $studentSubject = 'Tutor Assigned & Class Confirmed: '.$courseTitle.' - YounimatiQ';
                    $studentBody = '<h3>Great News! Your Tutor Has Been Assigned — YounimatiQ</h3>'
                        .'<p>Hi '.e($studentName).',</p>'
                        .'<p>Great news! Dedicated tutor <strong>'.e($tutor->name).'</strong> has been assigned to your 1:1 live class for <strong>'.e($courseTitle).'</strong>! Your class session is confirmed and your live meeting room is now ready.</p>'
                        .'<div style="background:#f8fafc; border-left:4px solid #046bd2; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                        .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Confirmed Class & Session Details</h4>'
                        .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($studentName).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> '.e($courseTitle).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Total Sessions:</strong> '.e($sessionsLabel).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Delivery Mode:</strong> Live 1:1</p>'
                        .'<p style="margin:6px 0;"><strong>● Assigned Tutor:</strong> Tutor '.e($tutor->name).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Live Class Link:</strong> <a href="'.e((string) $joinUrl).'" style="color:#046bd2; font-weight:600; word-break:break-all;">'.e((string) $joinUrl).'</a></p>'
                        .'<p style="margin:6px 0;"><strong>● Student Portal:</strong> <a href="'.e(route('login.student')).'" style="color:#046bd2; font-weight:600;">'.e(route('login.student')).'</a></p>'
                        .'</div>'
                        .'<div style="text-align:center; margin:25px 0;">'
                        .'<a href="'.e((string) $joinUrl).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:14px 32px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Join Live Class ('.e($providerLabel).' Meeting Room)</a>'
                        .'</div>'
                        .\App\Http\Controllers\SessionNoteController::emailButtonHtml($joinUrl, [
                            'kind' => 'class',
                            'ref' => 'booking:'.$booking->id,
                            'session' => $courseTitle,
                            'when' => $booking->scheduled_at
                                ? \App\Services\TimezoneService::formatForEmail(
                                    $booking->scheduled_at,
                                    $student->timezone,
                                    $booking->parent_timezone,
                                    $booking->timezone,
                                )
                                : null,
                            'name' => $studentName,
                            'email' => $student->email,
                        ])
                        .'<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">Getting Ready for Your Class:</h4>'
                        .'<p>'
                        .'• <strong>Important: Please join the class meeting using your full name ('.e($studentName).')</strong> so your tutor can identify and admit you immediately from the waiting room.<br>'
                        .'• Connect from a desktop, laptop, or tablet with a working microphone and camera in a quiet space.<br>'
                        .'• Click the <strong>Join Live Class</strong> button above to connect directly with Tutor '.e($tutor->name).' in the meeting room.'
                        .'</p>'
                        .'<p>If you have any questions or need help joining, reach out to us anytime at <a href="mailto:support@younimatiq.com">support@younimatiq.com</a>.</p>'
                        .'<p style="margin-top:25px; line-height:1.6;">Happy Learning!<br>'
                        .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                        .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

                    Mail::to($student->email)
                        ->bcc('younimatiq.com+0ae348f29f@invite.trustpilot.com')
                        ->send(new TemplateMail($studentSubject, $studentBody));

                    EmailLog::create([
                        'to_email' => $student->email,
                        'subject' => $studentSubject,
                        'template_slug' => 'tutor_assigned_student',
                        'status' => 'sent',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send student booking confirmation email: '.$e->getMessage());
                if (config('mail.tutor_assignment_notifications', false)) {
                    EmailLog::create([
                        'to_email' => $student->email,
                        'subject' => $studentSubject ?? ('Tutor Assigned: '.$courseTitle),
                        'template_slug' => 'tutor_assigned_student',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }

            // 3. Notify Linked Parents
            $student->loadMissing('parents');
            $parents = $student->parents;
            if (! $parents || $parents->isEmpty()) {
                $parentIds = \App\Models\ParentStudent::where('student_id', $student->id)->pluck('parent_id');
                if ($parentIds->isNotEmpty()) {
                    $parents = User::whereIn('id', $parentIds)->get();
                }
            }

            foreach ($parents as $parent) {
                $parentName = $parent->name ?: 'Parent';
                if (! config('mail.tutor_assignment_notifications', false)) {
                    continue;
                }
                try {
                    $parentSubject = 'Tutor Assigned & Class Confirmed: '.$courseTitle.' for '.$studentName.' - YounimatiQ';
                    $parentBody = '<h3>Tutor Assigned & Class Confirmed — YounimatiQ</h3>'
                        .'<p>Dear '.e($parentName).',</p>'
                        .'<p>We are delighted to confirm that dedicated tutor <strong>'.e($tutor->name).'</strong> has been assigned to teach <strong>'.e($studentName).'</strong> in <strong>'.e($courseTitle).'</strong>! The live 1:1 class session is confirmed and the meeting room is ready.</p>'
                        .'<div style="background:#f8fafc; border-left:4px solid #046bd2; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                        .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Confirmed Class & Session Details</h4>'
                        .'<p style="margin:6px 0;"><strong>● Parent Name:</strong> '.e($parentName).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($studentName).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> '.e($courseTitle).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Total Sessions:</strong> '.e($sessionsLabel).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Delivery Mode:</strong> Live 1:1</p>'
                        .'<p style="margin:6px 0;"><strong>● Assigned Tutor:</strong> Tutor '.e($tutor->name).'</p>'
                        .'<p style="margin:6px 0;"><strong>● Live Class Link:</strong> <a href="'.e((string) $joinUrl).'" style="color:#046bd2; font-weight:600; word-break:break-all;">'.e((string) $joinUrl).'</a></p>'
                        .'<p style="margin:6px 0;"><strong>● Parent Portal:</strong> <a href="'.e(route('login.parent')).'" style="color:#046bd2; font-weight:600;">'.e(route('login.parent')).'</a></p>'
                        .'</div>'
                        .'<div style="text-align:center; margin:25px 0;">'
                        .'<a href="'.e((string) $joinUrl).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:14px 32px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Join Live Class ('.e($providerLabel).' Meeting Room)</a>'
                        .'</div>'
                        .\App\Http\Controllers\SessionNoteController::emailButtonHtml($joinUrl, [
                            'kind' => 'class',
                            'ref' => 'booking:'.$booking->id,
                            'session' => $courseTitle,
                            'when' => $booking->scheduled_at
                                ? \App\Services\TimezoneService::formatForEmail(
                                    $booking->scheduled_at,
                                    $parent->timezone,
                                    $booking->parent_timezone,
                                    $booking->timezone,
                                )
                                : null,
                            'name' => $parentName,
                            'email' => $parent->email,
                        ])
                        .'<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">What happens next?</h4>'
                        .'<p>Tutor '.e($tutor->name).' will guide '.e($studentName).' through the curriculum with interactive, hands-on activities. You can track attendance, class progress, and tutor feedback directly from your Parent Portal.</p>'
                        .'<p><strong>Tips for a Great Class Experience:</strong><br>'
                        .'• <strong>Important: Please ensure '.e($studentName).' joins the meeting with their full name</strong> so the tutor can quickly verify and admit them from the waiting room.<br>'
                        .'• Please ensure '.e($studentName).' joins from a desktop, laptop, or tablet with a working camera and microphone in a quiet space.<br>'
                        .'• Click on the Live Class Link above to connect directly with Tutor '.e($tutor->name).' in the live meeting room.'
                        .'</p>'
                        .'<p>We encourage you to stay connected with us and share your thoughts or questions whenever needed. Reach out anytime at <a href="mailto:support@younimatiq.com">support@younimatiq.com</a>.</p>'
                        .'<p style="margin-top:25px; line-height:1.6;">Warm regards,<br>'
                        .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                        .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

                    Mail::to($parent->email)->send(new TemplateMail($parentSubject, $parentBody));
                    EmailLog::create([
                        'to_email' => $parent->email,
                        'subject' => $parentSubject,
                        'template_slug' => 'tutor_assigned_parent',
                        'status' => 'sent',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send parent booking confirmation email: '.$e->getMessage());
                    EmailLog::create([
                        'to_email' => $parent->email,
                        'subject' => $parentSubject ?? ('Tutor Assigned: '.$courseTitle),
                        'template_slug' => 'tutor_assigned_parent',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $this->redirectAfterAssign(
            $request,
            'bookings',
            "Tutor {$tutor->name} assigned! {$providerLabel} links generated and emailed."
        );
    }

    public function assignCampTutor(Request $request, CampBooking $campBooking, ZoomService $zoomService)
    {
        $data = $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'timezone' => 'nullable|string',
        ]);

        if (! empty($data['scheduled_at'])) {
            $tz = $data['timezone'] ?? 'UTC';
            $campBooking->scheduled_at = Carbon::parse($data['scheduled_at'], $tz)->setTimezone('UTC');
            $campBooking->timezone = $tz;
            $campBooking->save();
        }

        $tutor = User::findOrFail($data['tutor_id']);

        $topic = ($campBooking->camp_name ?? 'Demo Class').' - '.($campBooking->child_name ?? $campBooking->parent_name);
        $startTime = $campBooking->scheduled_at
            ? $campBooking->scheduled_at
            : now()->addDay()->format('Y-m-d 10:00:00');

        try {
            $fields = $zoomService->ensureMeetingFields(
                $campBooking,
                $topic,
                $startTime,
                45,
                $campBooking->timezone ?? 'UTC'
            );
        } catch (\Throwable $e) {
            Log::warning('Meeting create failed during camp tutor assign: '.$e->getMessage());
            $fields = [];
        }

        $joinUrl = $fields['zoom_join_url'] ?? $campBooking->zoom_join_url;
        $startUrl = $fields['zoom_start_url'] ?? $campBooking->zoom_start_url;
        $provider = $fields['meeting_provider'] ?? $zoomService->activeProvider();
        $providerLabel = $zoomService->providerLabel($provider);

        $campBooking->update(array_merge([
            'tutor_id' => $tutor->id,
            'payment_status' => 'paid',
        ], $fields));

        // Apply admin SMTP configuration so emails dispatch reliably
        try {
            app(\App\Services\EmailService::class)->applyAdminSmtpConfig();
        } catch (\Throwable $e) {
            Log::warning('SMTP apply in assignCampTutor failed: '.$e->getMessage());
        }

        $scheduledTimeStr = $this->formatBookingScheduleLabel(
            $campBooking->scheduled_at,
            $campBooking->parent_timezone ?: $campBooking->timezone,
            $campBooking
        );

        // 1. Notify Tutor
        try {
            Notification::create([
                'user_id' => $tutor->id,
                'type' => 'demo_assignment',
                'title' => 'New Demo Class Assigned',
                'body' => "You have been assigned to teach Demo Class: {$campBooking->camp_name} for {$campBooking->child_name} on {$scheduledTimeStr}.",
                'action_url' => url('/tutor/classes'),
            ]);

            $tutorSubject = 'Assigned Demo Class: '.($campBooking->camp_name ?? 'Trial Class').' - YounimatiQ';
            $tutorBody = '<h3>New Demo Class Assigned — YounimatiQ</h3>'
                .'<p>Hi '.e($tutor->name).',</p>'
                .'<p>You have been assigned as the tutor for an upcoming Demo / Trial Class!</p>'
                .'<div style="background:#f8fafc; border-left:4px solid #0057bf; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Demo Class Details</h4>'
                .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($campBooking->child_name ?? 'Learner').'</p>'
                .'<p style="margin:6px 0;"><strong>● Parent Name:</strong> '.e($campBooking->parent_name ?? 'Parent').'</p>'
                .'<p style="margin:6px 0;"><strong>● Class:</strong> '.e($campBooking->camp_name).'</p>'
                .'<p style="margin:6px 0;"><strong>● Scheduled Date & Time:</strong> '.e($scheduledTimeStr).'</p>'
                .'<p style="margin:6px 0;"><strong>● Host Start Link:</strong> <a href="'.e((string) $startUrl).'" style="color:#0057bf; font-weight:600; word-break:break-all;">'.e((string) $startUrl).'</a></p>'
                .'<p style="margin:6px 0;"><strong>● Tutor Portal:</strong> <a href="'.e(route('login.tutor')).'" style="color:#0057bf; font-weight:600;">'.e(route('login.tutor')).'</a></p>'
                .'</div>'
                .'<div style="text-align:center; margin:25px 0;">'
                .'<a href="'.e((string) $startUrl).'" style="background:#0057bf; color:#ffffff; font-weight:700; font-size:15px; padding:14px 32px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(0,87,191,0.3);">🚀 Launch Demo Class as Host ('.e($providerLabel).')</a>'
                .'</div>'
                .'<p style="margin-top:25px; line-height:1.6;">Happy Teaching!<br>'
                .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

            if (config('mail.tutor_assignment_notifications', false)) {
                Mail::to($tutor->email)->send(new TemplateMail($tutorSubject, $tutorBody));
                EmailLog::create([
                    'to_email' => $tutor->email,
                    'subject' => $tutorSubject,
                    'template_slug' => 'camp_tutor_assigned_tutor',
                    'status' => 'sent',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed sending demo tutor email: '.$e->getMessage());
            if (config('mail.tutor_assignment_notifications', false)) {
                EmailLog::create([
                    'to_email' => $tutor->email,
                    'subject' => $tutorSubject ?? 'Assigned Demo Class',
                    'template_slug' => 'camp_tutor_assigned_tutor',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // 2. Notify Parent/Student
        if (config('mail.tutor_assignment_notifications', false)) {
            try {
                $parentSubject = 'Demo Class Confirmed: '.($campBooking->camp_name ?? 'Demo Class').' - YounimatiQ';
                $parentBody = '<h3>Demo Class Confirmed — YounimatiQ</h3>'
                    .'<p>Hi '.e($campBooking->parent_name).',</p>'
                    .'<p>Your demo class for <strong>'.e($campBooking->child_name ?? 'your child').'</strong> is confirmed! Dedicated tutor <strong>'.e($tutor->name).'</strong> has been assigned to lead the session.</p>'
                    .'<div style="background:#f8fafc; border-left:4px solid #046bd2; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                    .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Class Details</h4>'
                    .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($campBooking->child_name ?? 'Learner').'</p>'
                    .'<p style="margin:6px 0;"><strong>● Parent Name:</strong> '.e($campBooking->parent_name ?? 'Parent').'</p>'
                    .'<p style="margin:6px 0;"><strong>● Class:</strong> '.e($campBooking->camp_name).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Assigned Tutor:</strong> Tutor '.e($tutor->name).'</p>'
                    .'<p style="margin:6px 0;"><strong>● Live Class Link:</strong> <a href="'.e((string) $joinUrl).'" style="color:#046bd2; font-weight:600; word-break:break-all;">'.e((string) $joinUrl).'</a></p>'
                    .'</div>'
                    .'<div style="text-align:center; margin:25px 0;">'
                    .'<a href="'.e((string) $joinUrl).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:14px 32px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Join Live Class ('.e($providerLabel).' Meeting Room)</a>'
                    .'</div>'
                    .\App\Http\Controllers\SessionNoteController::emailButtonHtml($joinUrl, [
                        'kind' => 'trial',
                        'ref' => 'camp:'.$campBooking->id,
                        'session' => $campBooking->camp_name ?? 'Demo Class',
                        'when' => $campBooking->scheduled_at
                            ? \App\Services\TimezoneService::formatForEmail(
                                $campBooking->scheduled_at,
                                $campBooking->parent_timezone,
                                $campBooking->timezone,
                            )
                            : null,
                        'name' => $campBooking->parent_name,
                        'email' => $campBooking->email,
                    ])
                    .'<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">Getting Ready:</h4>'
                    .'<p>• <strong>Important: Please ensure the student joins the meeting with their full name ('.e($campBooking->child_name ?? 'Learner').')</strong> so the tutor can quickly admit them from the waiting room.<br>'
                    .'• Join from a laptop, desktop, or tablet with a working mic and camera in a quiet space.</p>'
                    .'<p style="margin-top:25px; line-height:1.6;">Warm regards,<br>'
                    .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                    .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

                Mail::to($campBooking->email)
                    ->bcc('younimatiq.com+0ae348f29f@invite.trustpilot.com')
                    ->send(new TemplateMail($parentSubject, $parentBody));

                EmailLog::create([
                    'to_email' => $campBooking->email,
                    'subject' => $parentSubject,
                    'template_slug' => 'camp_tutor_assigned_parent',
                    'status' => 'sent',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed sending demo parent email: '.$e->getMessage());
                EmailLog::create([
                    'to_email' => $campBooking->email,
                    'subject' => $parentSubject ?? 'Demo Class Confirmed',
                    'template_slug' => 'camp_tutor_assigned_parent',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $this->redirectAfterAssign(
            $request,
            'demos',
            "Tutor {$tutor->name} assigned to demo class! {$providerLabel} links generated and emailed."
        );
    }

    protected function redirectAfterAssign(Request $request, string $defaultTab, string $message)
    {
        $tab = $request->input('return_tab', $defaultTab);
        if (! in_array($tab, ['enrollments', 'bookings', 'demos'], true)) {
            $tab = $defaultTab;
        }

        return redirect()
            ->route('admin.enrollments', ['tab' => $tab])
            ->with('success', $message);
    }

    /**
     * Format a stored UTC scheduled_at into the parent's local timezone (plus IST).
     */
    protected function formatBookingScheduleLabel($scheduledAt, ?string $timezone, $booking = null): string
    {
        if (! $scheduledAt && $booking instanceof Booking) {
            // 1. Try to find the earliest upcoming class in this series
            if ($booking->student_id && $booking->course_id) {
                $seriesBooking = Booking::where('student_id', $booking->student_id)
                    ->where('course_id', $booking->course_id)
                    ->whereNotNull('scheduled_at')
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('scheduled_at')
                    ->first();
                if ($seriesBooking && $seriesBooking->scheduled_at) {
                    $scheduledAt = $seriesBooking->scheduled_at;
                    $timezone = $timezone ?: ($seriesBooking->parent_timezone ?: $seriesBooking->timezone);
                }
            }

            // 2. Try to find recurring schedule in Payment
            if (! $scheduledAt) {
                $payment = $booking->payment ?: Payment::where('student_id', $booking->student_id)
                    ->where('course_id', $booking->course_id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($payment && is_array($payment->schedule) && ! empty($payment->schedule['days'])) {
                    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $parts = [];
                    foreach ($payment->schedule['days'] as $day) {
                        $label = $dayNames[(int) $day] ?? ('Day '.$day);
                        $time = $payment->schedule['times'][$day] ?? $payment->schedule['times'][(string) $day] ?? '';
                        if ($time !== '') {
                            try {
                                $time = Carbon::parse($time)->format('g:i A');
                            } catch (\Throwable $e) {}
                        }
                        $parts[] = trim($label.($time !== '' ? ' at '.$time : ''));
                    }
                    $start = $payment->schedule['start_date'] ?? '';
                    $daysLine = implode(', ', $parts);

                    return ($start ? "Starting {$start} — " : '').$daysLine;
                }
            }
        }

        if (! $scheduledAt) {
            return 'To be coordinated directly with your assigned tutor';
        }

        return TimezoneService::formatForEmail($scheduledAt, $timezone);
    }

    public function cancel(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'cancelled']);

        return back()->with('success', 'Enrollment cancelled.');
    }

    public function updateSchedule(Request $request, Booking $booking, ZoomService $zoomService)
    {
        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'timezone' => 'nullable|string',
            'number_of_classes' => 'nullable|integer|min:1|max:48',
            'schedule_start_date' => 'nullable|date',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'integer|min:0|max:6',
            'schedule_times' => 'nullable|array',
        ]);

        $hasRecurring = ! empty($data['schedule_days'])
            && ! empty($data['schedule_start_date'])
            && ! empty($data['number_of_classes']);

        if ($hasRecurring) {
            return $this->rescheduleSeries($booking, $data);
        }

        if (empty($data['scheduled_at'])) {
            return back()->with('error', 'Please choose a class date/time or a recurring weekly schedule.');
        }

        $tz = $data['timezone'] ?? 'UTC';
        $booking->scheduled_at = Carbon::parse($data['scheduled_at'], $tz)->setTimezone('UTC');
        $booking->timezone = $tz;

        // Keep meeting links in sync with the new start time (Meet patches room; Zoom recreates).
        if ($booking->zoom_join_url || $booking->meeting_room_id) {
            $topic = ($booking->course->title ?? 'Live Session').' - '.($booking->student->name ?? 'Student');
            $meeting = $this->syncMeetingForSchedule(
                $zoomService,
                $topic,
                $booking->scheduled_at,
                $booking->timezone ?? 'UTC',
                $booking->meeting_provider,
                $booking->meeting_room_id
            );
            foreach (ZoomService::bookingFields($meeting) as $key => $value) {
                $booking->{$key} = $value;
            }
        }

        $booking->save();

        return back()->with('success', 'Schedule updated successfully.');
    }

    /**
     * Regenerate a student's recurring class series for a course (like manual enrollment).
     * Every class that hasn't happened yet is replaced; only completed/cancelled rows are
     * kept as history, and the assigned tutor is preserved.
     */
    protected function rescheduleSeries(Booking $booking, array $data)
    {
        $scheduleService = app(ClassScheduleService::class);

        $schedule = $scheduleService->normalize([
            'number_of_classes' => $data['number_of_classes'],
            'start_date' => $data['schedule_start_date'],
            'days' => $data['schedule_days'] ?? [],
            'times' => $data['schedule_times'] ?? [],
            'timezone' => $data['timezone'] ?? null,
        ]);

        if (! $schedule) {
            return back()->with('error', 'Please select at least one day with a time to schedule classes.');
        }

        $studentId = $booking->student_id;
        $courseId = $booking->course_id;

        // Preserve an assigned tutor for the whole series.
        $tutorId = $booking->tutor_id
            ?: Booking::where('student_id', $studentId)->where('course_id', $courseId)->whereNotNull('tutor_id')->value('tutor_id')
            ?: ($booking->course->tutor_id ?? null);

        // Preserve the originating payment where we can.
        $paymentId = $booking->payment_id
            ?: Booking::where('student_id', $studentId)->where('course_id', $courseId)->whereNotNull('payment_id')->value('payment_id');

        // History = classes that already happened or were cancelled. Everything else
        // (including stale past slots) is regenerated so the new start date, days and
        // times actually take effect.
        $series = Booking::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('session_type', 'scheduled_class');

        $retained = (clone $series)->whereIn('status', ['completed', 'cancelled'])->count();

        (clone $series)->whereNotIn('status', ['completed', 'cancelled'])->delete();

        // The form asks for the TOTAL number of classes and the history is kept,
        // so only schedule the difference.
        $requested = (int) ($schedule['classes'] ?? 0);
        $schedule['classes'] = max(0, $requested - $retained);

        $created = [];
        if ($schedule['classes'] > 0) {
            $created = $scheduleService->createBookings(
                $studentId,
                $courseId,
                $schedule,
                $tutorId,
                $paymentId,
                'Scheduled class for '.($booking->course->title ?? 'Course')
            );
        }

        // Keep a placeholder enrollment booking pointing at the first upcoming class.
        if ($booking->session_type === 'enrollment') {
            if (! empty($created)) {
                $booking->scheduled_at = $created[0]->scheduled_at;
            }
            $booking->timezone = $schedule['timezone'];
            $booking->parent_timezone = $schedule['timezone'];
            $booking->save();
        }

        // Keep the enrollment's paid class count aligned with the requested total.
        $enrollment = Enrollment::where('user_id', $studentId)->where('course_id', $courseId)->first();
        if ($enrollment) {
            $total = $retained + count($created);
            $enrollment->update(['paid_classes_count' => $total ?: ($enrollment->paid_classes_count)]);
        }

        $message = count($created).' class'.(count($created) === 1 ? '' : 'es').' scheduled';
        if ($booking->tutor) {
            $message .= ' with '.$booking->tutor->name;
        }

        return back()->with('success', $message.'.');
    }

    public function updateCampSchedule(Request $request, CampBooking $campBooking, ZoomService $zoomService)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date',
            'timezone' => 'nullable|string',
        ]);

        $tz = $data['timezone'] ?? 'UTC';
        $campBooking->scheduled_at = Carbon::parse($data['scheduled_at'], $tz)->setTimezone('UTC');
        $campBooking->timezone = $tz;

        if ($campBooking->zoom_join_url || $campBooking->meeting_room_id) {
            $topic = ($campBooking->camp_name ?? 'Demo Class').' - '.($campBooking->child_name ?? $campBooking->parent_name);
            $meeting = $this->syncMeetingForSchedule(
                $zoomService,
                $topic,
                $campBooking->scheduled_at,
                $campBooking->timezone ?? 'UTC',
                $campBooking->meeting_provider,
                $campBooking->meeting_room_id
            );
            foreach (ZoomService::bookingFields($meeting) as $key => $value) {
                $campBooking->{$key} = $value;
            }
        }

        $campBooking->save();

        return back()->with('success', 'Schedule updated successfully.');
    }

    /**
     * @return array{id?:mixed,join_url?:?string,start_url?:?string,passcode?:?string,provider?:string}
     */
    private function syncMeetingForSchedule(
        ZoomService $zoomService,
        string $topic,
        $scheduledAt,
        string $timezone,
        ?string $provider,
        ?string $roomId
    ): array {
        $start = $scheduledAt instanceof Carbon
            ? $scheduledAt->format('Y-m-d H:i:s')
            : (string) $scheduledAt;

        $configured = $zoomService->activeProvider();
        // If this booking was created under a different provider, create a fresh room for the active one.
        if ($provider && $provider !== $configured) {
            return $zoomService->createMeeting($topic, $start, 45, $timezone);
        }

        $active = $configured;
        if ($active === 'meet' && $roomId) {
            $updated = app(\App\Services\MeetService::class)->updateMeeting($roomId, $topic, $start, $timezone);
            if ($updated) {
                return $updated;
            }
        }

        return $zoomService->createMeeting($topic, $start, 45, $timezone);
    }
}
