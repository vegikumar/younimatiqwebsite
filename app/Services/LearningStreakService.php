<?php

namespace App\Services;

use App\Models\LearningStreak;
use Carbon\Carbon;

class LearningStreakService
{
    public function recordActivity(int $userId): LearningStreak
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        $streak = LearningStreak::firstOrCreate(
            ['user_id' => $userId],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'week_activity_count' => 0,
                'week_start' => $weekStart,
            ]
        );

        // Reset weekly counter if new week
        if (! $streak->week_start || $streak->week_start->lt($weekStart)) {
            $streak->week_start = $weekStart;
            $streak->week_activity_count = 0;
        }

        $last = $streak->last_activity_date;
        if ($last && $last->isSameDay($today)) {
            // Already counted today — still bump weekly only once per day (done)
            $streak->save();

            return $streak->fresh();
        }

        if ($last && $last->isSameDay($today->copy()->subDay())) {
            $streak->current_streak = (int) $streak->current_streak + 1;
        } else {
            $streak->current_streak = 1;
        }

        $streak->longest_streak = max((int) $streak->longest_streak, (int) $streak->current_streak);
        $streak->last_activity_date = $today;
        $streak->week_activity_count = (int) $streak->week_activity_count + 1;
        $streak->save();

        return $streak->fresh();
    }

    public function summary(int $userId): array
    {
        $streak = LearningStreak::where('user_id', $userId)->first();
        $weekGoal = 3;

        return [
            'current' => (int) ($streak->current_streak ?? 0),
            'longest' => (int) ($streak->longest_streak ?? 0),
            'week_count' => (int) ($streak->week_activity_count ?? 0),
            'week_goal' => $weekGoal,
            'week_complete' => (int) ($streak->week_activity_count ?? 0) >= $weekGoal,
        ];
    }
}
