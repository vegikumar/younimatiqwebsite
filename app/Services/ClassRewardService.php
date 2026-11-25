<?php

namespace App\Services;

use App\Models\MilestoneBadge;
use App\Models\StudentReward;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClassRewardService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Curated XP compliment presets shown to tutors during a class.
     */
    public const XP_PRESETS = [
        ['label' => 'Read aloud with expression', 'xp' => 20, 'icon' => 'record_voice_over'],
        ['label' => 'Amazing storytelling', 'xp' => 30, 'icon' => 'auto_stories'],
        ['label' => 'Answered a tough question', 'xp' => 25, 'icon' => 'psychology'],
        ['label' => 'Helped a classmate', 'xp' => 20, 'icon' => 'volunteer_activism'],
        ['label' => 'Focused and tried their best', 'xp' => 15, 'icon' => 'center_focus_strong'],
        ['label' => 'Shared a creative idea', 'xp' => 25, 'icon' => 'lightbulb'],
        ['label' => 'Brave presentation', 'xp' => 30, 'icon' => 'campaign'],
        ['label' => 'Great problem solving', 'xp' => 25, 'icon' => 'extension'],
        ['label' => 'Finished impressive work', 'xp' => 30, 'icon' => 'emoji_events'],
        ['label' => 'Kind and encouraging', 'xp' => 15, 'icon' => 'favorite'],
    ];

    /**
     * System-wide badge catalog available to every tutor.
     */
    public const SYSTEM_BADGES = [
        ['slug' => 'storyteller', 'title' => 'Storyteller', 'badge_icon' => 'auto_stories', 'description' => 'Told a story or read aloud with expression and confidence.'],
        ['slug' => 'reading-star', 'title' => 'Reading Star', 'badge_icon' => 'menu_book', 'description' => 'Outstanding reading fluency and comprehension.'],
        ['slug' => 'math-wizard', 'title' => 'Math Wizard', 'badge_icon' => 'calculate', 'description' => 'Solved tricky problems with smart maths thinking.'],
        ['slug' => 'coding-champion', 'title' => 'Coding Champion', 'badge_icon' => 'code', 'description' => 'Built something impressive with code.'],
        ['slug' => 'great-listener', 'title' => 'Great Listener', 'badge_icon' => 'hearing', 'description' => 'Followed instructions and listened carefully.'],
        ['slug' => 'team-player', 'title' => 'Team Player', 'badge_icon' => 'groups', 'description' => 'Collaborated and supported the group.'],
        ['slug' => 'creative-thinker', 'title' => 'Creative Thinker', 'badge_icon' => 'lightbulb', 'description' => 'Brought original ideas and imagination.'],
        ['slug' => 'problem-solver', 'title' => 'Problem Solver', 'badge_icon' => 'extension', 'description' => 'Worked through challenges independently.'],
        ['slug' => 'brave-presenter', 'title' => 'Brave Presenter', 'badge_icon' => 'campaign', 'description' => 'Presented their work with confidence.'],
        ['slug' => 'kindness', 'title' => 'Kindness', 'badge_icon' => 'volunteer_activism', 'description' => 'Showed kindness and helped others.'],
        ['slug' => 'persistence', 'title' => 'Persistence', 'badge_icon' => 'fitness_center', 'description' => 'Kept trying and never gave up.'],
        ['slug' => 'quick-learner', 'title' => 'Quick Learner', 'badge_icon' => 'bolt', 'description' => 'Picked up new concepts fast.'],
    ];

    /**
     * Make sure the default badge catalog exists.
     */
    public function ensureSystemBadges(): void
    {
        foreach (self::SYSTEM_BADGES as $badge) {
            MilestoneBadge::updateOrCreate(
                ['slug' => $badge['slug']],
                [
                    'title' => $badge['title'],
                    'description' => $badge['description'],
                    'badge_icon' => $badge['badge_icon'],
                    'is_system' => true,
                    'level_number' => 0,
                ]
            );
        }
    }

    /**
     * Badges a tutor can hand out: system badges + their own custom badges
     * (optionally scoped to a course).
     */
    public function badgeCatalog(int $tutorId, ?int $courseId = null): Collection
    {
        $this->ensureSystemBadges();

        return MilestoneBadge::query()
            ->where(function ($q) use ($tutorId, $courseId) {
                $q->where('is_system', true)
                    ->orWhere('created_by', $tutorId);

                if ($courseId) {
                    $q->orWhere('course_id', $courseId);
                }
            })
            ->orderByDesc('is_system')
            ->orderBy('title')
            ->get();
    }

    /**
     * Award XP compliments, a single level-up tip and an optional badge.
     *
     * @param  array{compliments?: array<int,array{label?:string,xp?:int}>, xp_amount?:int, level_up_tip?:?string, badge_id?:?int, course_id?:?int, booking_id?:?int, is_trial?:bool}  $data
     */
    public function award(User $student, User $tutor, array $data): StudentReward
    {
        $compliments = collect($data['compliments'] ?? [])
            ->map(fn ($c) => [
                'label' => trim((string) ($c['label'] ?? '')),
                'xp' => max(0, (int) ($c['xp'] ?? 0)),
            ])
            ->filter(fn ($c) => $c['label'] !== '' || $c['xp'] > 0)
            ->values()
            ->all();

        $xpFromCompliments = array_sum(array_column($compliments, 'xp'));
        $manualXp = max(0, (int) ($data['xp_amount'] ?? 0));
        $xpTotal = $xpFromCompliments > 0 ? $xpFromCompliments : $manualXp;

        $reward = StudentReward::create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'course_id' => $data['course_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'badge_id' => $data['badge_id'] ?? null,
            'xp_amount' => $xpTotal,
            'compliments' => $compliments ?: null,
            'level_up_tip' => $data['level_up_tip'] ?? null,
            'is_trial' => (bool) ($data['is_trial'] ?? false),
            'awarded_at' => now(),
        ]);

        if ($xpTotal > 0) {
            $student->increment('xp', $xpTotal);
        }

        $this->notifyStudent($student, $tutor, $reward);

        return $reward;
    }

    /**
     * Create a reusable custom badge for a tutor (optionally course-scoped).
     */
    public function createCustomBadge(User $tutor, string $title, ?string $description, ?string $icon, ?int $courseId = null): MilestoneBadge
    {
        $title = trim($title);
        $slug = Str::slug($title).'-'.Str::lower(Str::random(5));

        return MilestoneBadge::create([
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'badge_icon' => $icon ?: 'military_tech',
            'is_system' => false,
            'course_id' => $courseId,
            'created_by' => $tutor->id,
            'level_number' => 0,
        ]);
    }

    /**
     * Distinct badges a student has earned (optionally for one course).
     */
    public function earnedBadges(int $studentId, ?int $courseId = null): Collection
    {
        return StudentReward::query()
            ->where('student_id', $studentId)
            ->whereNotNull('badge_id')
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->with('badge')
            ->latest('awarded_at')
            ->get()
            ->pluck('badge')
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function notifyStudent(User $student, User $tutor, StudentReward $reward): void
    {
        $parts = [];

        if ($reward->xp_amount > 0) {
            $parts[] = "+{$reward->xp_amount} XP earned!";
        }

        if ($reward->badge) {
            $parts[] = "🏅 New badge: {$reward->badge->title}";
        }

        if ($reward->level_up_tip) {
            $parts[] = "💡 Level-up tip: {$reward->level_up_tip}";
        }

        if (empty($parts)) {
            return;
        }

        $this->notifications->notify(
            $student,
            "🌟 {$tutor->name} celebrated you in class!",
            implode(' ', $parts),
            route('student.dashboard'),
            'reward'
        );
    }
}
