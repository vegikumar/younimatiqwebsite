<?php

namespace App\Services;

use App\Models\Course;
use App\Models\InterestedLead;
use App\Models\User;

class ReEngagementService
{
    public function __construct(private EmailService $emailService) {}

    /**
     * Log family interest or abandoned cart for re-engagement follow-up.
     */
    public function trackInterest(int $userId, int $courseId): InterestedLead
    {
        $course = Course::find($courseId);
        $tutorId = $course->tutor_id ?? 1;

        return InterestedLead::firstOrCreate([
            'user_id' => $userId,
            'tutor_id' => $tutorId,
            'course_id' => $courseId,
        ]);
    }

    /**
     * Send friendly re-engagement checkout nudge email to family.
     */
    public function sendNudge(InterestedLead $lead): bool
    {
        $user = $lead->user;
        $course = $lead->course;
        $tutor = $lead->tutor;

        if (!$user || !$course) {
            return false;
        }

        $this->emailService->sendTemplate('camp_follow_up', $user->email, [
            'parent_name' => $user->name,
            'child_name' => 'your learner',
            'camp_name' => $course->title,
        ]);

        $lead->update([
            'status' => 'nudged',
            'last_nudged_at' => now(),
        ]);

        return true;
    }
}
