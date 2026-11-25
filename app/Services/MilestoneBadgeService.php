<?php

namespace App\Services;

use App\Models\Course;
use App\Models\MilestoneBadge;
use App\Models\StudentMilestone;
use App\Models\User;
use Illuminate\Support\Str;

class MilestoneBadgeService
{
    /**
     * Grant milestone badge & certificate for level progress.
     */
    public function grantMilestone(int $studentId, int $courseId, int $levelNumber): StudentMilestone
    {
        $badge = MilestoneBadge::firstOrCreate(
            ['level_number' => $levelNumber],
            [
                'title' => "Level {$levelNumber} Mastery Badge",
                'description' => "Successfully mastered Level {$levelNumber} core skills and hands-on projects.",
                'badge_icon' => 'workspace_premium',
            ]
        );

        $hash = Str::random(12);

        $milestone = StudentMilestone::firstOrCreate(
            [
                'user_id' => $studentId,
                'course_id' => $courseId,
                'badge_id' => $badge->id,
            ],
            [
                'certificate_hash' => $hash,
                'unlocked_at' => now(),
            ]
        );

        return $milestone->loadMissing('badge');
    }
}
