<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\CoPilotService;
use App\Services\MilestoneBadgeService;
use App\Services\ReferralService;
use App\Services\SkillTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialPlatformFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_skill_tree_visual_level_progress_and_portfolio(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Leo Dash']);
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Python App Lab',
            'description' => 'Python course',
            'price' => 100,
            'course_type' => 'live',
        ]);

        $skillTreeService = app(SkillTreeService::class);
        $treeData = $skillTreeService->getStudentSkillTree($student->id, $course->id);

        $this->assertEquals('Python App Lab', $treeData['course_title']);
        $this->assertStringContainsString('Python App Lab: Level 1 of 4', $treeData['current_level_label']);

        // Submit portfolio project
        $response = $this->actingAs($student)->post(route('student.courses.portfolio.store', $course), [
            'project_name' => 'My Pygame Arcade',
            'project_url' => 'https://replit.com/@leo/arcade',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_portfolios', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'project_name' => 'My Pygame Arcade',
        ]);
    }

    public function test_live_session_copilot_prep_data(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Scratch Champions',
            'description' => 'Scratch',
            'price' => 50,
            'course_type' => 'live',
        ]);

        $coPilotService = app(CoPilotService::class);
        $prepData = $coPilotService->getCoPilotPrep($course->id);

        $this->assertNotNull($prepData);
    }

    public function test_milestone_badge_and_certificate_generation(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Arduino Electronics',
            'description' => 'Arduino',
            'price' => 70,
            'course_type' => 'live',
        ]);

        $milestoneService = app(MilestoneBadgeService::class);
        $milestone = $milestoneService->grantMilestone($student->id, $course->id, 2);

        $this->assertDatabaseHas('student_milestones', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->assertNotNull($milestone->certificate_hash);
    }

    public function test_family_referral_code_and_sibling_discount_calculation(): void
    {
        $parent = User::factory()->create(['role' => 'student', 'name' => 'Smith Family']);
        $referralService = app(ReferralService::class);

        $code = $referralService->getReferralCode($parent);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $parent->id,
            'referral_code' => $code,
        ]);

        // Calculate 15% sibling discount
        $discountInfo = $referralService->applySiblingDiscount($parent->id, 100.00);
        $this->assertNotNull($discountInfo);
    }
}
