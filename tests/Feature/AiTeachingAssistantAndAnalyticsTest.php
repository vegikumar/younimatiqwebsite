<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AiTeachingAssistantService;
use App\Services\AntiBypassSecurityService;
use App\Services\TutorAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTeachingAssistantAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_parent_note_generation_from_bullets(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Charlie Brown']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Python Basics',
            'description' => 'Description',
            'price' => 50,
            'course_type' => 'live',
        ]);

        $aiService = app(AiTeachingAssistantService::class);
        $note = $aiService->generateParentNote($tutor->id, $course->id, $student->id, "• Mastered if statements\n• Built mini quiz game");

        $this->assertDatabaseHas('ai_parent_notes', [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => 'draft',
        ]);
        $this->assertStringContainsString('Mastered if statements', $note->ai_generated_note);
    }

    public function test_ai_session_recap_creation_with_4_hour_window(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Scratch Coding',
            'description' => 'Description',
            'price' => 40,
            'course_type' => 'live',
        ]);

        $aiService = app(AiTeachingAssistantService::class);
        $recap = $aiService->generateSessionRecap(null, $tutor->id, $course->id, 'Zoom transcript content...');

        $this->assertEquals('pending_review', $recap->status);
        $this->assertTrue($recap->auto_send_at->isFuture());
    }

    public function test_anti_bypass_security_service_masks_contact_info(): void
    {
        $securityService = app(AntiBypassSecurityService::class);

        $textWithEmail = 'Contact me at teacher@gmail.com for private lessons.';
        $textWithPhone = 'Call me at +1 234-567-8900 anytime.';

        $this->assertTrue($securityService->containsSensitiveContactInfo($textWithEmail));
        $this->assertTrue($securityService->containsSensitiveContactInfo($textWithPhone));

        $maskedEmail = $securityService->maskSensitiveContactInfo($textWithEmail);
        $maskedPhone = $securityService->maskSensitiveContactInfo($textWithPhone);

        $this->assertStringContainsString('[email blocked]', $maskedEmail);
        $this->assertStringContainsString('[phone blocked]', $maskedPhone);
    }

    public function test_tutor_analytics_metrics_and_stickiness_calculation(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Arduino Robotics',
            'description' => 'Description',
            'price' => 60,
            'course_type' => 'live',
        ]);

        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);

        Enrollment::create(['user_id' => $student1->id, 'course_id' => $course->id, 'status' => 'active']);
        Enrollment::create(['user_id' => $student2->id, 'course_id' => $course->id, 'upsold_by_tutor_id' => $tutor->id, 'status' => 'active']);

        $analyticsService = app(TutorAnalyticsService::class);
        $metrics = $analyticsService->getMetrics($tutor->id);

        $this->assertEquals(2, $metrics['learners_taught']);
        $this->assertEquals(50.0, $metrics['re_enrollment_rate']); // 1 out of 2 upsold/re-enrolled
    }

    public function test_admin_ops_roster_and_leaderboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tutor = User::factory()->create(['role' => 'tutor']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.ops.roster'));
        $response->assertStatus(200);
        $response->assertViewHas('rosterData');
        $response->assertViewHas('leaderboard');
    }
}
