<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Question;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\Booking;
use App\Models\ClassroomGameAssignment;
use App\Models\ClassroomGameReward;
use App\Services\TimezoneService;
use App\Support\ClassroomGames;
use App\Support\StudentInertiaProps;

class StudentDashboardController extends Controller
{
    public function dashboard()
    {
        $userId = Auth::id();
        $user = Auth::user();
        $enrollments = Enrollment::with('course')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->get();
        $upcomingSessions = Booking::with(['course', 'tutor'])
            ->where('student_id', $userId)
            ->where(function ($q) {
                $q->whereNull('session_type')
                    ->orWhere('session_type', '!=', 'enrollment');
            })
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now()->subMinutes(60))
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        $enrolledCourseIds = Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $userXp = $user->xp ?? 0;
        $rank = (int) \Illuminate\Support\Facades\Cache::remember(
            'student_rank_'.$userId.'_'.(int) $userXp,
            now()->addMinutes(10),
            fn () => \App\Models\User::where('role', 'student')->where('xp', '>', $userXp)->count() + 1
        );
        
        if ($rank == 1) {
            $tier = '🥇 Gold Champion';
        } elseif ($rank == 2) {
            $tier = '🥈 Silver Champion';
        } elseif ($rank == 3) {
            $tier = '🥉 Bronze Champion';
        } else {
            $tier = '🌟 Top ' . (ceil($rank / 5) * 5); 
        }

        $stats = [
            'enrolled' => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'in_progress' => $enrollments->where('status', 'active')->count(),
            'avg_progress' => $enrollments->avg('progress') ?? 0,
            'xp' => $userXp,
            'level' => intval($userXp / 500) + 1,
            'rank' => $rank,
            'tier' => $tier,
        ];

        $demoSessions = \App\Models\CampBooking::where('email', $user->email)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '>=', now()->subMinutes(60));
            })
            ->orderByRaw('CASE WHEN scheduled_at IS NOT NULL THEN 0 ELSE 1 END ASC, scheduled_at DESC')
            ->take(10)
            ->get();

        $hasPurchasedCourse = $enrollments->whereIn('status', ['active', 'completed'])->isNotEmpty();
        $trialEndsAt = null;
        if (!$hasPurchasedCourse) {
            $trialEndsAt = \App\Http\Middleware\CheckTrialAccess::resolveTrialEndsAt($user);
        }

        $assignments = $user->assignments()->with(['course', 'submissions'])->get();

        $leaderboardUsers = \App\Models\User::where('role', 'student')
            ->where(function ($q) {
                $q->whereNull('leaderboard_opt_out')->orWhere('leaderboard_opt_out', false);
            })
            ->orderByDesc('xp')
            ->take(5)
            ->get(['id', 'name', 'xp']);

        $leaderboard = [];
        foreach ($leaderboardUsers as $index => $boardUser) {
            $display = \App\Support\DisplayName::public($boardUser->name);
            $leaderboard[] = [
                'name' => $display,
                'xp' => $boardUser->xp,
                'rank' => $index + 1,
                'badge' => $index === 0 ? 'Champion' : ($index === 1 ? 'Runner up' : 'Top 5'),
                'avatar' => substr($display ?? 'L', 0, 1),
                'is_current' => $boardUser->id === $userId,
            ];
        }

        $rewards = \App\Models\StudentReward::with(['badge', 'tutor', 'course'])
            ->where('student_id', $userId)
            ->latest('awarded_at')
            ->take(10)
            ->get();

        $badges = $rewards->pluck('badge')->filter()->unique('id')->values();

        $rewardAgg = \App\Models\StudentReward::where('student_id', $userId)
            ->selectRaw('COALESCE(SUM(xp_amount),0) as total_xp, COUNT(*) as reward_count')
            ->first();

        $rewardStats = [
            'total' => (int) ($rewardAgg->total_xp ?? 0),
            'count' => (int) ($rewardAgg->reward_count ?? 0),
            'badges' => $badges->count(),
        ];

        $pendingAssignments = $assignments->filter(fn ($a) => $a->submissions->isEmpty())->count();

        $gameRewards = ClassroomGameReward::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('game_key');

        $assignedGames = ClassroomGameAssignment::query()
            ->where('user_id', $userId)
            ->latest()
            ->pluck('game_key')
            ->unique()
            ->values()
            ->map(function ($gameKey) use ($gameRewards) {
                $meta = ClassroomGames::resolve($gameKey, activeOnly: false);
                if (! $meta) {
                    return null;
                }
                $reward = $gameRewards->get($meta['key']);

                return [
                    'key' => $meta['key'],
                    'title' => $meta['title'],
                    'summary' => $meta['summary'] ?? '',
                    'url' => route('student.games.show', $meta['key']),
                    'xp_max' => ClassroomGames::maxPoints($meta['key']),
                    'xp_awarded' => $reward !== null,
                    'xp_points' => (int) ($reward->points ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $enrollmentRows = $enrollments->map(fn ($e) => [
            'id' => $e->id,
            'course_id' => $e->course_id,
            'course_title' => $e->course?->title ?? 'Course',
            'status' => $e->status,
            'progress' => (float) ($e->progress ?? 0),
        ])->values()->all();

        $userTz = TimezoneService::resolveForDisplay($user->timezone ?? null);

        $upcomingPayload = $upcomingSessions->map(function ($session) use ($userTz) {
            $tz = TimezoneService::resolveForDisplay(
                $userTz,
                $session->parent_timezone,
                $session->timezone,
                config('app.timezone')
            );
            $label = $session->scheduled_at
                ? \Carbon\Carbon::parse($session->scheduled_at)->timezone($tz)->format('M j, Y g:i A')
                : null;

            return [
                'id' => $session->id,
                'type' => 'booking',
                'title' => $session->course->title ?? $session->camp_name ?? 'Live Interactive Session',
                'scheduled_at' => optional($session->scheduled_at)?->toIso8601String(),
                'scheduled_at_label' => $label,
                'timezone' => $tz,
                'timezone_label' => TimezoneService::shortLabel($tz),
                'zoom_join_url' => $session->zoom_join_url,
                'join_url' => $session->zoom_join_url,
                'tutor_name' => $session->tutor?->name,
            ];
        })->values()->all();

        $demoPayload = $demoSessions->map(function ($session) use ($userTz) {
            $tz = TimezoneService::resolveForDisplay(
                $userTz,
                $session->parent_timezone,
                $session->timezone,
                config('app.timezone')
            );
            $label = $session->scheduled_at
                ? \Carbon\Carbon::parse($session->scheduled_at)->timezone($tz)->format('M j, Y g:i A')
                : null;

            return [
                'id' => $session->id,
                'type' => 'demo',
                'title' => $session->camp_name ?? 'Free trial class',
                'scheduled_at_label' => $label,
                'timezone' => $tz,
                'timezone_label' => TimezoneService::shortLabel($tz),
            ];
        })->values()->all();

        return \App\Support\InertiaPage::react('Student/Dashboard', [
            'stats' => $stats,
            'upcomingSessions' => $upcomingPayload,
            'demoSessions' => $demoPayload,
            'enrollments' => $enrollmentRows,
            'assignedGames' => $assignedGames,
            'leaderboard' => $leaderboard,
            'pendingAssignments' => $pendingAssignments,
            'trialEndsAt' => $trialEndsAt?->toIso8601String(),
            'hasPurchasedCourse' => $hasPurchasedCourse,
            'trialExpiredUrl' => route('student.trial_expired'),
            'rewards' => $rewards->map(fn ($r) => [
                'id' => $r->id,
                'xp_amount' => (int) ($r->xp_amount ?? 0),
                'compliment' => $r->compliment,
                'awarded_at_label' => optional($r->awarded_at)?->format('M j, Y'),
                'badge' => $r->badge ? [
                    'id' => $r->badge->id,
                    'title' => $r->badge->title,
                    'icon' => $r->badge->badge_icon,
                ] : null,
                'tutor_name' => $r->tutor?->name,
                'course_title' => $r->course?->title,
            ])->values()->all(),
            'badges' => $badges->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'icon' => $b->badge_icon,
            ])->values()->all(),
            'rewardStats' => $rewardStats,
            'gradeBand' => \App\Support\GradeBand::fromGrade($user->grade !== null ? (int) $user->grade : null),
            'gradeBandLabel' => \App\Support\GradeBand::label(
                \App\Support\GradeBand::fromGrade($user->grade !== null ? (int) $user->grade : null)
            ),
            'grade' => $user->grade,
            'streak' => app(\App\Services\LearningStreakService::class)->summary($userId),
            'milestones' => \App\Models\StudentMilestone::with('badge')
                ->where('user_id', $userId)
                ->latest('unlocked_at')
                ->take(5)
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->badge?->title ?? 'Milestone',
                    'icon' => $m->badge?->badge_icon ?? 'workspace_premium',
                    'unlocked_at_label' => optional($m->unlocked_at)?->format('M j'),
                ])->values()->all(),
        ]);
    }

    public function wishlist()
    {
        $wishlists = Wishlist::with('course')->where('user_id', Auth::id())->get();

        return \App\Support\InertiaPage::react('Student/Wishlist', [
            'items' => $wishlists->map(fn ($w) => [
                'id' => $w->id,
                'course' => array_merge(StudentInertiaProps::courseBrief($w->course), [
                    'description' => trim(preg_replace('/\s+/', ' ', strip_tags((string) ($w->course?->description ?? '')))),
                    'price' => (float) ($w->course?->price ?? 0),
                ]),
            ])->values()->all(),
        ]);
    }

    public function paymentHistory()
    {
        // Manual enrollments create $0 completed payments — don't show those as paid amounts.
        $payments = Payment::with('course')
            ->where('student_id', Auth::id())
            ->where('amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $orders = Order::with('course')
            ->where('user_id', Auth::id())
            ->where('amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return \App\Support\InertiaPage::react('Student/PaymentHistory', [
            'payments' => $payments->map(fn ($p) => StudentInertiaProps::payment($p))->values()->all(),
            'orders' => $orders->map(fn ($o) => StudentInertiaProps::order($o))->values()->all(),
        ]);
    }

    public function reviews()
    {
        $reviews = Review::with('course')->where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        $enrolledCourseIds = Enrollment::where('user_id', Auth::id())->pluck('course_id');
        $reviewedCourseIds = $reviews->pluck('course_id');
        
        $unreviewedCourses = \App\Models\Course::whereIn('id', $enrolledCourseIds)
            ->whereNotIn('id', $reviewedCourseIds)
            ->get();

        return \App\Support\InertiaPage::react('Student/Reviews', [
            'reviews' => $reviews->map(fn ($r) => StudentInertiaProps::review($r))->values()->all(),
            'unreviewedCourses' => $unreviewedCourses->map(fn ($c) => StudentInertiaProps::courseBrief($c))->values()->all(),
            'storeUrl' => route('student.reviews.store'),
        ]);
    }

    public function qa()
    {
        $questions = Question::with(['course', 'answers.user'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $enrolledCourses = Enrollment::with('course')
            ->where('user_id', Auth::id())
            ->get()
            ->filter(fn ($e) => $e->course)
            ->map(fn ($e) => StudentInertiaProps::courseBrief($e->course))
            ->values();

        return \App\Support\InertiaPage::react('Student/Qa', [
            'questions' => $questions->map(fn ($q) => StudentInertiaProps::question($q))->values()->all(),
            'enrolledCourses' => $enrolledCourses->all(),
            'storeUrl' => route('student.qa.store'),
        ]);
    }

    public function bookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['course', 'tutor'])
            ->where('student_id', Auth::id())
            ->where('session_type', '!=', 'enrollment')
            ->orderBy('created_at', 'desc')
            ->get();

        $enrolledCourseIds = Enrollment::where('user_id', Auth::id())->pluck('course_id');
        $availableSessions = LiveSession::with('course')
            ->whereIn('course_id', $enrolledCourseIds)
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderByDesc('start_time')
            ->get();

        $demoSessions = \App\Models\CampBooking::where('email', $user->email)
            ->orderByRaw('CASE WHEN scheduled_at IS NOT NULL THEN 0 ELSE 1 END ASC, scheduled_at DESC')
            ->get();

        return \App\Support\InertiaPage::react('Student/Bookings', [
            'bookings' => $bookings->map(fn ($b) => StudentInertiaProps::studentBooking($b))->values()->all(),
            'availableSessions' => $availableSessions->map(fn ($s) => StudentInertiaProps::bookableLiveSession($s))->values()->all(),
            'demoSessions' => $demoSessions->map(fn ($d) => StudentInertiaProps::demoSession($d))->values()->all(),
            'bookSessionUrl' => route('student.bookings.store'),
        ]);
    }
}
