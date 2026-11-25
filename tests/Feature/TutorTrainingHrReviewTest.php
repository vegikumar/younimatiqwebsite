<?php

namespace Tests\Feature;

use Tests\TestCase;

class TutorTrainingHrReviewTest extends TestCase
{
    public function test_hr_review_routes_require_admin(): void
    {
        $this->get('/admin/tutor-training/reviews')->assertRedirect();
    }

    public function test_student_activities_requires_auth(): void
    {
        $this->get('/student/activities')->assertRedirect();
    }

    public function test_student_rewards_requires_auth(): void
    {
        $this->get('/student/rewards')->assertRedirect();
    }
}
