<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentReward;
use App\Models\User;
use App\Services\ClassRewardService;
use Illuminate\Http\Request;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    public function __construct(private ClassRewardService $rewards)
    {
    }

    /**
     * Tutor's reward history / badge library.
     */
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();

        $this->rewards->ensureSystemBadges();

        $rewards = StudentReward::with(['student', 'course', 'badge'])
            ->where('tutor_id', $tutorId)
            ->latest('awarded_at')
            ->paginate(20);

        $badgeCatalog = $this->rewards->badgeCatalog($tutorId);

        $courseIds = Course::where('tutor_id', $tutorId)->pluck('id');
        $students = User::where('role', 'student')
            ->where(function ($q) use ($tutorId, $courseIds) {
                $q->whereHas('enrollments', fn ($e) => $e->whereIn('course_id', $courseIds))
                    ->orWhereHas('bookings', fn ($b) => $b->where('tutor_id', $tutorId));
            })
            ->orderBy('name')
            ->get();

        $stats = [
            'xp_awarded' => (int) StudentReward::where('tutor_id', $tutorId)->sum('xp_amount'),
            'badges_awarded' => (int) StudentReward::where('tutor_id', $tutorId)->whereNotNull('badge_id')->count(),
            'students_celebrated' => (int) StudentReward::where('tutor_id', $tutorId)->distinct('student_id')->count('student_id'),
        ];

        return \App\Support\InertiaPage::react('Tutor/Rewards', [
            'stats' => [
                'xpAwarded' => $stats['xp_awarded'],
                'badgesAwarded' => $stats['badges_awarded'],
                'studentsCelebrated' => $stats['students_celebrated'],
            ],
            'rewards' => TutorInertiaProps::paginatedRewards($rewards),
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'rewardPresets' => TutorInertiaProps::xpPresets(),
            'badgeCatalog' => TutorInertiaProps::badgeCatalog($badgeCatalog),
        ]);
    }

    /**
     * Award XP compliments, a level-up tip and/or a badge to a student.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'badge_id' => 'nullable|exists:milestone_badges,id',
            'xp_amount' => 'nullable|integer|min:0|max:1000',
            'level_up_tip' => 'nullable|string|max:500',
            'is_trial' => 'nullable|boolean',
            'compliments' => 'nullable|array',
            'compliments.*.label' => 'nullable|string|max:120',
            'compliments.*.xp' => 'nullable|integer|min:0|max:1000',
        ]);

        $student = User::where('role', 'student')->findOrFail($data['student_id']);
        $tutor = Auth::guard('tutor')->user();
        $tutorId = (int) $tutor->id;

        $courseId = $data['course_id'] ?? null;
        $bookingId = $data['booking_id'] ?? null;

        $this->assertTutorMayReward($tutorId, $student->id, $courseId, $bookingId);

        $hasCompliments = collect($data['compliments'] ?? [])->contains(fn ($c) => !empty($c['label']) || (int) ($c['xp'] ?? 0) > 0);
        $hasXp = (int) ($data['xp_amount'] ?? 0) > 0;

        if (!$hasCompliments && !$hasXp && empty($data['level_up_tip']) && empty($data['badge_id'])) {
            return back()->with('error', 'Add at least one compliment, XP amount, level-up tip or badge before saving.');
        }

        $reward = $this->rewards->award($student, $tutor, [
            'compliments' => $data['compliments'] ?? [],
            'xp_amount' => $data['xp_amount'] ?? 0,
            'level_up_tip' => $data['level_up_tip'] ?? null,
            'badge_id' => $data['badge_id'] ?? null,
            'course_id' => $courseId,
            'booking_id' => $bookingId,
            'is_trial' => $request->boolean('is_trial'),
        ]);

        return back()->with('success', "{$student->name} has been celebrated! +{$reward->xp_amount} XP".($reward->badge ? " and the {$reward->badge->title} badge." : '.'));
    }

    /**
     * Create a reusable custom badge.
     */
    public function storeBadge(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'description' => 'nullable|string|max:240',
            'badge_icon' => 'nullable|string|max:60',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $courseId = $data['course_id'] ?? null;

        if ($courseId && Course::where('id', $courseId)->where('tutor_id', Auth::guard('tutor')->id())->doesntExist()) {
            abort(403);
        }

        $badge = $this->rewards->createCustomBadge(
            Auth::guard('tutor')->user(),
            $data['title'],
            $data['description'] ?? null,
            $data['badge_icon'] ?? null,
            $courseId
        );

        return back()->with('success', "Custom badge \"{$badge->title}\" created and ready to award.");
    }

    /**
     * Ensure the tutor is actually allowed to reward this student.
     */
    protected function assertTutorMayReward(int $tutorId, int $studentId, ?int $courseId, ?int $bookingId): void
    {
        // Direct booking between this tutor and student.
        $hasBooking = Booking::where('student_id', $studentId)
            ->where(function ($q) use ($tutorId, $courseId) {
                $q->where('tutor_id', $tutorId);
                if ($courseId) {
                    $q->orWhere('course_id', $courseId);
                }
            })
            ->exists();

        if ($hasBooking) {
            return;
        }

        // Student enrolled in a course owned by this tutor.
        $tutorCourseIds = Course::where('tutor_id', $tutorId)->pluck('id');

        $hasEnrollment = Enrollment::where('user_id', $studentId)
            ->whereIn('course_id', $tutorCourseIds)
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->exists();

        if ($hasEnrollment) {
            return;
        }

        // Booking explicitly passed and assigned to the tutor.
        if ($bookingId && Booking::where('id', $bookingId)->where('student_id', $studentId)->where('tutor_id', $tutorId)->exists()) {
            return;
        }

        abort(403, 'You are not assigned to this student.');
    }
}
