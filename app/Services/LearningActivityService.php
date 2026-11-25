<?php

namespace App\Services;

/**
 * Central hook after quizzes, assignments, and module completions.
 */
class LearningActivityService
{
    public function __construct(
        private LearningStreakService $streaks,
        private SkillTreeService $skillTrees,
    ) {}

    public function record(int $studentId, ?int $courseId = null): array
    {
        $streak = $this->streaks->recordActivity($studentId);
        $advance = null;
        if ($courseId) {
            $advance = $this->skillTrees->advanceForActivity($studentId, $courseId);
        }

        return [
            'streak' => [
                'current' => (int) $streak->current_streak,
                'longest' => (int) $streak->longest_streak,
                'week_count' => (int) $streak->week_activity_count,
            ],
            'skill' => $advance,
        ];
    }
}
