<?php

namespace App\Services;

use App\Http\Middleware\CheckTrialAccess;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Faq;
use App\Models\LiveSession;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\Tip;
use App\Models\TipAssignment;
use App\Models\TipFolder;
use App\Models\User;
use App\Support\ApiAssetUrl;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentApiService
{
    public function formatUser(User $user): array
    {
        $trial = $this->getTrialStatus($user);

        $grade = $user->grade !== null ? (int) $user->grade : null;
        $gradeBand = \App\Support\GradeBand::fromGrade($grade);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => ApiAssetUrl::avatar($user->avatar, $user->name),
            'timezone' => $user->timezone ?? 'UTC',
            'bio' => $user->bio,
            'grade' => $grade,
            'grade_band' => $gradeBand,
            'grade_band_label' => \App\Support\GradeBand::label($gradeBand),
            'xp' => (int) ($user->xp ?? 0),
            'level' => intval(($user->xp ?? 0) / 500) + 1,
            'status' => $user->status,
            'notification_preferences' => $user->notification_preferences ?? [
                'email_reminders' => true,
                'email_marketing' => true,
            ],
            'trial' => $trial,
        ];
    }

    public function getTrialStatus(User $user): array
    {
        $hasPurchasedCourse = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        $trialEndsAt = $hasPurchasedCourse ? null : CheckTrialAccess::resolveTrialEndsAt($user);
        $expired = $trialEndsAt && now()->greaterThanOrEqualTo($trialEndsAt);

        return [
            'has_purchased_course' => $hasPurchasedCourse,
            'trial_ends_at' => $trialEndsAt?->toIso8601String(),
            'trial_expired' => $expired,
            'seconds_remaining' => $trialEndsAt && ! $expired
                ? max(0, now()->diffInSeconds($trialEndsAt, false))
                : 0,
        ];
    }

    public function getDashboardData(User $user): array
    {
        $userId = $user->id;
        $enrollments = Enrollment::with('course')->where('user_id', $userId)->get();

        $userXp = $user->xp ?? 0;
        $rank = User::where('role', 'student')->where('xp', '>', $userXp)->count() + 1;

        if ($rank == 1) {
            $tier = 'Gold Champion';
        } elseif ($rank == 2) {
            $tier = 'Silver Champion';
        } elseif ($rank == 3) {
            $tier = 'Bronze Champion';
        } else {
            $tier = 'Top '.(ceil($rank / 5) * 5);
        }

        $stats = [
            'enrolled' => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'in_progress' => $enrollments->where('status', 'active')->count(),
            'avg_progress' => round($enrollments->avg('progress') ?? 0, 1),
            'xp' => $userXp,
            'level' => intval($userXp / 500) + 1,
            'rank' => $rank,
            'tier' => $tier,
        ];

        $quizIds = \App\Support\StudentQuizAccess::quizIdsFor($userId);

        $quizzes = Quiz::with(['course', 'attempts' => fn ($q) => $q->where('user_id', $userId)])
            ->whereIn('id', $quizIds)
            ->take(4)
            ->get()
            ->map(fn ($q) => $this->formatQuizSummary($q, $userId));

        $upcomingSessions = Booking::with(['course', 'tutor'])
            ->where('student_id', $userId)
            ->where('session_type', '!=', 'enrollment')
            ->where('scheduled_at', '>=', now()->subMinutes(60))
            ->orderBy('scheduled_at')
            ->take(5)
            ->get()
            ->map(fn ($b) => $this->formatBooking($b));

        $demoSessions = CampBooking::with(['course', 'tutor'])
            ->where('email', $user->email)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now()->subMinutes(60));
            })
            ->orderByRaw('CASE WHEN scheduled_at IS NOT NULL THEN 0 ELSE 1 END ASC, scheduled_at ASC')
            ->take(10)
            ->get()
            ->map(fn ($d) => $this->formatDemoSession($d));

        $leaderboardUsers = User::where('role', 'student')->orderByDesc('xp')->take(5)->get();
        $leaderboard = $leaderboardUsers->map(function ($boardUser, $index) use ($userId) {
            return [
                'name' => $boardUser->name,
                'xp' => (int) ($boardUser->xp ?? 0),
                'rank' => $index + 1,
                'avatar_url' => ApiAssetUrl::avatar($boardUser->avatar, $boardUser->name),
                'is_current' => $boardUser->id === $userId,
            ];
        });

        $tipsPayload = $this->formatAssignedTips($userId);
        $tipsFolders = $tipsPayload['tips_folders'];
        $looseTips = $tipsPayload['loose_tips'];

        $assignments = $user->assignments()->with('course')->get()->map(fn ($a) => $this->formatAssignmentSummary($a, $userId));

        $enrolledCourses = $enrollments->map(fn ($e) => [
            'enrollment_id' => $e->id,
            'id' => $e->course_id, // alias for clients that expect course.id
            'course_id' => $e->course_id,
            'title' => $e->course?->title,
            'thumbnail_url' => ApiAssetUrl::fromPath($e->course?->thumbnail_url ?? $e->course?->thumbnail ?? $e->course?->image ?? null),
            'progress' => (float) ($e->progress ?? 0),
            'status' => $e->status,
        ]);

        return [
            'user' => $this->formatUser($user),
            'stats' => $stats,
            'trial' => $this->getTrialStatus($user),
            'enrolled_courses' => $enrolledCourses,
            'upcoming_sessions' => $upcomingSessions,
            'demo_sessions' => $demoSessions,
            'quizzes' => $quizzes,
            'assignments' => $assignments,
            'leaderboard' => $leaderboard,
            'tips_folders' => $tipsFolders,
            'loose_tips' => $looseTips,
        ];
    }

    public function formatCourse(Course $course, ?Enrollment $enrollment = null): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'thumbnail_url' => ApiAssetUrl::fromPath($course->thumbnail_url ?? $course->thumbnail ?? $course->image ?? null),
            'price' => (float) ($course->price ?? 0),
            'per_class_price' => (float) ($course->per_class_price ?? 0),
            'tutor' => $course->tutor ? [
                'id' => $course->tutor->id,
                'name' => $course->tutor->name,
                'avatar_url' => ApiAssetUrl::avatar($course->tutor->avatar, $course->tutor->name),
            ] : null,
            'modules_count' => $course->modules?->count() ?? 0,
            'format' => $course->format,
            'course_type' => $course->course_type,
            'self_paced_enabled' => (bool) ($course->self_paced_enabled ?? false),
            'delivery_modes' => $this->deliveryModes($course),
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'progress' => (float) ($enrollment->progress ?? 0),
                'has_certificate' => $enrollment->hasUploadedCertificate(),
                'certificate_download_url' => $enrollment->hasUploadedCertificate()
                    ? url('/api/v1/student/courses/'.$course->getRouteKey().'/certificate/download')
                    : null,
            ] : null,
        ];
    }

    /**
     * Public catalog card / rich course detail for the mobile Learn tab.
     * Does not expose module content URLs (enrolled player does).
     */
    public function formatCatalogCourse(Course $course, bool $isEnrolled = false, bool $detailed = false): array
    {
        $ageLabel = is_array($course->seo_metadata)
            ? ($course->seo_metadata['age_label'] ?? null)
            : null;

        $categoryName = $course->relationLoaded('categories')
            ? ($course->categories->first()?->name ?? null)
            : null;

        $base = [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'thumbnail_url' => ApiAssetUrl::fromPath($course->thumbnail_url ?? $course->thumbnail ?? $course->image ?? null),
            'video_url' => ApiAssetUrl::fromPath($course->video_url ?: null),
            'price' => (float) ($course->price ?? 0),
            'per_class_price' => (float) ($course->per_class_price ?? 0),
            'age_group' => $course->age_group,
            'age_label' => $ageLabel,
            'course_type' => $course->course_type,
            'self_paced_enabled' => (bool) ($course->self_paced_enabled ?? false),
            'delivery_modes' => $this->deliveryModes($course),
            'is_camp' => (bool) ($course->is_camp ?? false),
            'number_of_classes' => $course->number_of_classes,
            'categories' => $course->relationLoaded('categories')
                ? $course->categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])->values()->all()
                : [],
            'tutor' => $course->tutor ? [
                'id' => $course->tutor->id,
                'name' => $course->tutor->name,
                'avatar_url' => ApiAssetUrl::avatar($course->tutor->avatar, $course->tutor->name),
            ] : null,
            'modules_count' => $course->modules?->count() ?? 0,
            'is_enrolled' => $isEnrolled,
            'checkout_path' => '/student/checkout/'.$course->id,
        ];

        if (! $detailed) {
            return $base;
        }

        $outcomes = is_array($course->learning_outcomes) ? array_values($course->learning_outcomes) : [];
        $highlights = is_array($course->highlights) ? array_values($course->highlights) : [];

        $whoFor = [
            ($ageLabel
                ? "Learners in the {$ageLabel} range"
                : 'Kids and teens ready for guided live learning').' who enjoy creative, hands-on projects.',
            'Beginners and intermediate students interested in '.($categoryName ?? 'this subject').' — no prior expert knowledge required.',
            'Parents looking for structured, live online classes with clear progress and a final showcase.',
        ];

        $requirements = [
            'A laptop or desktop with a stable internet connection',
            'Headphones or a quiet space for live sessions',
            'Working microphone and camera for interaction',
            'Consistent attendance for the live class schedule',
        ];

        $reviews = $course->relationLoaded('reviews') ? $course->reviews : $course->reviews()->with('user')->latest()->get();
        $reviewCount = $reviews->count();

        $moduleSource = $course->relationLoaded('modules') ? $course->modules : collect();
        // When a course is organised into units (self-paced), the flat curriculum
        // should only contain standalone class topics (modules without a unit).
        if ($course->relationLoaded('units') && $course->units->count() > 0) {
            $moduleSource = $moduleSource->whereNull('unit_id');
        }
        $modules = $moduleSource->sortBy('order')->values()->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description ?? $m->content_text,
            'learning_outcome' => $m->learning_outcome,
            'order' => $m->order,
        ])->all();

        $units = ($course->relationLoaded('units')
            ? $course->units
            : $course->units()->with('modules')->orderBy('order')->get())
            ->sortBy('order')->values()->map(fn ($u) => [
                'id' => $u->id,
                'title' => $u->title,
                'description' => $u->description,
                'order' => $u->order,
                'modules' => $u->modules->sortBy('order')->values()->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'description' => $m->description ?? $m->content_text,
                    'learning_outcome' => $m->learning_outcome,
                    'content_type' => $m->content_type,
                    'duration_minutes' => $m->duration_minutes,
                    'order' => $m->order,
                ])->all(),
            ])->all();

        $categoryIds = $course->relationLoaded('categories')
            ? $course->categories->pluck('id')
            : $course->categories()->pluck('categories.id');

        $relatedCourses = Course::with(['tutor', 'categories', 'modules'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->where('id', '!=', $course->id)
            ->where(function ($q) use ($course, $categoryIds) {
                $q->where('age_group', $course->age_group);
                if ($categoryIds->isNotEmpty()) {
                    $q->orWhereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds));
                }
            })
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Course $related) => $this->formatCatalogCourse($related, false, false))
            ->values()
            ->all();

        $faqs = Faq::where('is_active', true)
            ->whereIn('category', ['Classes', 'Enrollment', 'General', 'Safety'])
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
            ])
            ->values()
            ->all();

        $reviewItems = $reviews->take(6)->map(fn ($review) => [
            'id' => $review->id,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'author_name' => $review->reviewer_display_name,
            'created_at' => $review->created_at?->toIso8601String(),
        ])->values()->all();

        return array_merge($base, [
            'format' => $course->format,
            'welcome_message' => $course->welcome_message,
            'welcome_video_url' => ApiAssetUrl::fromPath($course->welcome_video_url ?: null),
            'importance' => $course->importance,
            'learning_outcomes' => $outcomes,
            'highlights' => $highlights,
            'who_for' => $whoFor,
            'requirements' => $requirements,
            'curriculum' => $modules,
            'units' => $units,
            'faqs' => $faqs,
            'related_courses' => $relatedCourses,
            'reviews' => $reviewItems,
            'review_stats' => [
                'count' => $reviewCount,
                'average' => $reviewCount > 0 ? round((float) $reviews->avg('rating'), 1) : 0.0,
            ],
            'cta' => $isEnrolled
                ? ['label' => 'Continue learning', 'action' => 'open_enrolled', 'course_id' => $course->id]
                : ['label' => 'Enroll / Checkout', 'action' => 'checkout', 'course_id' => $course->id],
        ]);
    }

    public function deliveryModes(Course $course): array
    {
        return $course->availableDeliveryModes();
    }

    public function formatModule(CourseModule $module, ?ModuleProgress $progress = null): array
    {
        $rawUrl = $module->content_url ?: $module->host_url;
        $type = strtolower((string) ($module->content_type ?? ''));
        $isZoom = str_contains($type, 'zoom');

        // Media assets go through ApiAssetUrl; Zoom meeting links stay absolute as-is.
        $contentUrl = null;
        $zoomJoinUrl = null;
        if ($rawUrl) {
            if ($isZoom && (str_contains($rawUrl, 'zoom.') || str_starts_with($rawUrl, 'http'))) {
                $zoomJoinUrl = $rawUrl;
                $contentUrl = $rawUrl;
            } else {
                $contentUrl = ApiAssetUrl::fromPath($rawUrl);
                if ($isZoom) {
                    $zoomJoinUrl = $contentUrl;
                }
            }
        }

        $canJoin = $isZoom && ! empty($zoomJoinUrl);

        return [
            'id' => $module->id,
            'title' => $module->title,
            'order' => $module->order,
            'content_type' => $module->content_type,
            'content_url' => $contentUrl,
            'content_text' => $module->content_text,
            'practical_video_url' => ApiAssetUrl::fromPath($module->practical_video_url ?: null),
            'description' => $module->description,
            'learning_outcome' => $module->learning_outcome,
            'zoom_join_url' => $zoomJoinUrl,
            'join_url' => $zoomJoinUrl,
            'can_join' => (bool) $canJoin,
            'join_window' => null,
            'completed' => (bool) ($progress?->completed ?? false),
            'completed_at' => $progress?->completed_at?->toIso8601String(),
        ];
    }

    public function formatAssignmentSummary(Assignment $assignment, int $userId): array
    {
        $submission = $assignment->submissions->firstWhere('user_id', $userId)
            ?? $assignment->submissions()->where('user_id', $userId)->first();

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'course' => $assignment->course ? ['id' => $assignment->course->id, 'title' => $assignment->course->title] : null,
            'questions_count' => count($assignment->questions ?? []),
            'has_questions' => $assignment->hasQuestions(),
            'file_url' => ApiAssetUrl::fromPath($assignment->file_path),
            'submitted' => (bool) $submission,
            'submission_status' => $submission?->status,
            'grade' => $submission?->grade,
        ];
    }

    public function formatAssignmentDetail(Assignment $assignment, ?AssignmentSubmission $submission = null): array
    {
        return array_merge($this->formatAssignmentSummary($assignment, $submission?->user_id ?? 0), [
            'questions' => $assignment->questions ?? [],
            'file_url' => ApiAssetUrl::fromPath($assignment->file_path),
            'submission' => $submission ? [
                'answers' => $submission->answers,
                'content' => $submission->content,
                'file_url' => ApiAssetUrl::fromPath($submission->file_path),
                'grade' => $submission->grade,
                'feedback' => $submission->feedback,
                'status' => $submission->status,
                'submitted_at' => $submission->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function formatQuizSummary(Quiz $quiz, int $userId): array
    {
        $attempts = $quiz->relationLoaded('attempts')
            ? $quiz->attempts->where('user_id', $userId)
            : $quiz->attempts()->where('user_id', $userId)->latest()->get();

        $bestAttempt = $attempts->sortByDesc('score')->first();
        $passed = $attempts->contains(fn ($a) => $a->passed);

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'passing_score' => $quiz->passing_score,
            'xp_reward' => $quiz->xp_reward,
            'questions_count' => count($quiz->questions ?? []),
            'course' => $quiz->course ? ['id' => $quiz->course->id, 'title' => $quiz->course->title] : null,
            'attempted' => $attempts->isNotEmpty(),
            'score' => $bestAttempt?->score,
            'passed' => $passed,
            'can_retry' => $attempts->isNotEmpty() && ! $passed,
            'results_url' => $attempts->isNotEmpty()
                ? url("/api/v1/student/quizzes/{$quiz->id}/results")
                : null,
        ];
    }

    /**
     * Join window for mobile: open 15 minutes before scheduled start, close 60 minutes after.
     *
     * @return array{opens_at: ?string, closes_at: ?string, can_join: bool, host_present: ?bool}
     */
    public function resolveJoinWindow(
        ?CarbonInterface $scheduledAt,
        ?string $zoomJoinUrl,
        bool $isCancelled = false,
        ?string $meetingProvider = null,
        ?string $meetingRoomId = null
    ): array {
        $joinOpensAt = $scheduledAt?->copy()->subMinutes(15);
        $joinClosesAt = $scheduledAt?->copy()->addMinutes(60);
        $canJoin = ! $isCancelled && ! empty($zoomJoinUrl);

        $hostPresent = null;
        if ($canJoin && ($meetingProvider === 'meet') && $meetingRoomId) {
            $hostPresent = app(MeetService::class)->roomHostPresent($meetingRoomId);
            if ($hostPresent === false) {
                $canJoin = false;
            }
        }

        return [
            'opens_at' => $joinOpensAt?->toIso8601String(),
            'closes_at' => $joinClosesAt?->toIso8601String(),
            'can_join' => (bool) $canJoin,
            'host_present' => $hostPresent,
        ];
    }

    public function formatBooking(Booking $booking): array
    {
        $scheduledAt = $booking->scheduled_at;
        $isCancelled = in_array($booking->status, ['cancelled', 'canceled'], true);
        $window = $this->resolveJoinWindow(
            $scheduledAt,
            $booking->zoom_join_url,
            $isCancelled,
            $booking->meeting_provider,
            $booking->meeting_room_id
        );

        return [
            'id' => $booking->id,
            'course' => $booking->course ? [
                'id' => $booking->course->id,
                'title' => $booking->course->title,
                'slug' => $booking->course->slug ?? null,
            ] : null,
            'tutor' => $booking->tutor ? [
                'id' => $booking->tutor->id,
                'name' => $booking->tutor->name,
                'avatar_url' => ApiAssetUrl::avatar($booking->tutor->avatar, $booking->tutor->name),
            ] : null,
            'status' => $booking->status,
            'session_type' => $booking->session_type,
            'scheduled_at' => $scheduledAt?->toIso8601String(),
            'created_at' => $booking->created_at?->toIso8601String(),
            'zoom_join_url' => $booking->zoom_join_url,
            'join_url' => $booking->zoom_join_url,
            'zoom_passcode' => $booking->zoom_passcode,
            'meeting_provider' => $booking->meeting_provider ?? 'zoom',
            'meeting_room_id' => $booking->meeting_room_id,
            'can_join' => $window['can_join'],
            'host_present' => $window['host_present'],
            'join_window' => [
                'opens_at' => $window['opens_at'],
                'closes_at' => $window['closes_at'],
            ],
            'description' => $booking->description,
        ];
    }

    /**
     * Collapse a student's bookings into one entry per course (a "series"),
     * carrying the recurring-schedule summary the web admin tab shows.
     *
     * @param  Collection  $bookings  Booking models with course+tutor loaded.
     */
    public function formatBookingSeries($bookings, ?string $fallbackTimezone = null): array
    {
        $groups = $bookings->groupBy(fn ($b) => $b->student_id.'-'.$b->course_id);
        $series = [];

        foreach ($groups as $group) {
            $rows = $group->values();
            $courseId = $rows->first()->course_id;

            $classRows = $rows
                ->filter(fn ($b) => $b->session_type === 'scheduled_class' && $b->scheduled_at)
                ->sortBy('scheduled_at')
                ->values();

            // Representative row: the placeholder enrollment row if present, else the oldest booking.
            $representative = $rows
                ->first(fn ($b) => $b->session_type === 'enrollment')
                ?? $rows->sortBy(fn ($b) => $b->created_at)->first();

            // The scheduled class rows carry the real parent timezone; the
            // placeholder row often has none, so never trust it first. Skip
            // bare UTC values written at insert time.
            $timezoneSource = $classRows->first(
                fn ($b) => ! TimezoneService::isUnset($b->parent_timezone)
            ) ?? $representative;

            $candidates = [
                $timezoneSource->parent_timezone,
                $timezoneSource->timezone,
                $representative->parent_timezone,
                $representative->timezone,
                $fallbackTimezone,
            ];

            $timezone = 'UTC';
            foreach ($candidates as $candidate) {
                if (! TimezoneService::isUnset($candidate)) {
                    $timezone = TimezoneService::sanitizeTimezone($candidate);
                    break;
                }
            }

            $days = [];
            $times = [];
            $startDate = null;
            $next = null;
            $remaining = 0;
            $now = now();

            foreach ($classRows as $row) {
                $local = Carbon::parse($row->scheduled_at)->setTimezone($timezone);
                $dow = (int) $local->dayOfWeek;
                $days[$dow] = true;
                $times[$dow] = $local->format('H:i');

                $dateStr = $local->toDateString();
                if ($startDate === null || $dateStr < $startDate) {
                    $startDate = $dateStr;
                }

                if ($row->scheduled_at->greaterThan($now)) {
                    $remaining++;
                    if ($next === null) {
                        $next = $row->scheduled_at;
                    }
                }
            }

            $course = $representative->course;
            $classes = $classRows->count();
            $scheduled = $classRows->count();

            if ($classes === 0) {
                $enrollment = Enrollment::where('user_id', $representative->student_id)
                    ->where('course_id', $courseId)
                    ->first();
                $classes = max(1, (int) ($enrollment->paid_classes_count ?? ($course->number_of_classes ?? 1)));
            }

            $series[] = [
                'id' => $representative->id,
                'course_id' => $courseId,
                'course' => $course ? [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug ?? null,
                    'thumbnail_url' => ApiAssetUrl::fromPath($course->thumbnail_url ?? $course->thumbnail ?? $course->image ?? null),
                ] : null,
                'tutor' => $representative->tutor ? [
                    'id' => $representative->tutor->id,
                    'name' => $representative->tutor->name,
                    'avatar_url' => ApiAssetUrl::avatar($representative->tutor->avatar, $representative->tutor->name),
                ] : null,
                'status' => $representative->status,
                'classes' => $classes,
                'scheduled' => $scheduled,
                'remaining' => $remaining,
                'is_recurring' => $scheduled > 0,
                'days' => (function () use ($days) {
                    $keys = array_keys($days);
                    sort($keys);

                    return $keys;
                })(),
                'times' => $times,
                'start_date' => $startDate ?? ($course->created_at?->toDateString() ?? $now->toDateString()),
                'timezone' => $timezone,
                'next_class_at' => $next?->toIso8601String(),
                'created_at' => $representative->created_at?->toIso8601String(),
            ];
        }

        usort($series, function ($a, $b) {
            // Newest first, but keep a deterministic order when created_at ties
            // (multiple enrollments created in the same second). Fall back to the
            // series id so the API contract is stable across requests.
            $cmp = strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });

        return $series;
    }

    /**
     * Demo / trial CampBooking for dashboard + bookings (Zoom parity with web).
     */
    public function formatDemoSession(CampBooking $demo): array
    {
        $scheduledAt = $demo->scheduled_at;
        $window = $this->resolveJoinWindow(
            $scheduledAt,
            $demo->zoom_join_url,
            false,
            $demo->meeting_provider ?? null,
            $demo->meeting_room_id ?? null
        );

        return [
            'id' => $demo->id,
            'session_type' => 'demo',
            'camp_name' => $demo->camp_name,
            'course' => $demo->course ? [
                'id' => $demo->course->id,
                'title' => $demo->course->title,
                'slug' => $demo->course->slug ?? null,
            ] : null,
            'tutor' => $demo->tutor ? [
                'id' => $demo->tutor->id,
                'name' => $demo->tutor->name,
                'avatar_url' => ApiAssetUrl::avatar($demo->tutor->avatar, $demo->tutor->name),
            ] : null,
            'status' => $demo->status ?? $demo->payment_status ?? null,
            'scheduled_at' => $scheduledAt?->toIso8601String(),
            'created_at' => $demo->created_at?->toIso8601String(),
            'zoom_join_url' => $demo->zoom_join_url,
            'join_url' => $demo->zoom_join_url,
            'zoom_passcode' => $demo->zoom_passcode,
            'meeting_provider' => $demo->meeting_provider ?? 'zoom',
            'can_join' => $window['can_join'],
            'host_present' => $window['host_present'],
            'join_window' => [
                'opens_at' => $window['opens_at'],
                'closes_at' => $window['closes_at'],
            ],
            'awaiting_schedule' => $scheduledAt === null,
        ];
    }

    /**
     * Bookable LiveSession row for “Book new session” UI.
     */
    public function formatAvailableSession(LiveSession $session): array
    {
        $start = $session->start_time;
        $joinUrl = $session->join_url;
        $window = $this->resolveJoinWindow(
            $start,
            $joinUrl,
            $session->status === 'cancelled',
            $session->meeting_provider,
            $session->meeting_room_id
        );

        return [
            'id' => $session->id,
            'title' => $session->module?->title
                ?? ($session->course ? 'Live session — '.$session->course->title : 'Live session'),
            'course' => $session->course ? [
                'id' => $session->course->id,
                'title' => $session->course->title,
                'slug' => $session->course->slug ?? null,
            ] : null,
            'tutor' => $session->tutor ? [
                'id' => $session->tutor->id,
                'name' => $session->tutor->name,
                'avatar_url' => ApiAssetUrl::avatar($session->tutor->avatar, $session->tutor->name),
            ] : null,
            'start_time' => $start?->toIso8601String(),
            'duration_minutes' => $session->duration_minutes,
            'status' => $session->status,
            'join_url' => $joinUrl,
            'zoom_join_url' => $joinUrl,
            'zoom_passcode' => $session->zoom_passcode,
            'meeting_provider' => $session->meeting_provider ?? 'zoom',
            'can_join' => $window['can_join'],
            'host_present' => $window['host_present'],
            'join_window' => [
                'opens_at' => $window['opens_at'],
                'closes_at' => $window['closes_at'],
            ],
        ];
    }

    /**
     * Tips assigned to a student (folders with nested tips + loose tip items).
     *
     * @return array{tips_folders: Collection, loose_tips: Collection}
     */
    public function formatAssignedTips(int $userId): array
    {
        $assignedFolderIds = TipAssignment::where('student_id', $userId)
            ->whereNotNull('tip_folder_id')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('tip_folder_id');

        $assignedTipIds = TipAssignment::where('student_id', $userId)
            ->whereNotNull('tip_id')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('tip_id');

        $formatTip = function ($t) {
            $isExternal = is_string($t->content_url) && filter_var($t->content_url, FILTER_VALIDATE_URL);

            return [
                'id' => $t->id,
                'title' => $t->title,
                'type' => $t->type ?: 'video', // video | file
                'content_url' => $isExternal ? $t->content_url : null,
                'stream_url' => url("/api/v1/student/tips/{$t->id}/stream"),
            ];
        };

        $tipsFolders = TipFolder::with('tips')
            ->whereIn('id', $assignedFolderIds)
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'tips_count' => $f->tips->count(),
                'tips' => $f->tips->map($formatTip)->values(),
            ]);

        $looseTips = Tip::whereIn('id', $assignedTipIds)->get()->map($formatTip);

        return [
            'tips_folders' => $tipsFolders,
            'loose_tips' => $looseTips,
        ];
    }
}
