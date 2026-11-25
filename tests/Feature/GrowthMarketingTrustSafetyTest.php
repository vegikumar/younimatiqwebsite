<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InterestedLead;
use App\Models\User;
use App\Services\ReEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthMarketingTrustSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_teacher_storefront_page(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Professor Oak',
            'bio' => 'Expert Pokémon & Coding Tutor',
            'teaching_style' => 'Hands-on project building',
        ]);

        $response = $this->get(route('tutors.storefront', $tutor));
        $response->assertStatus(200);
        $response->assertSee('Professor Oak');
        $response->assertSee('Expert Pokémon & Coding Tutor');
    }

    public function test_policy_training_gatekeeper_blocks_and_ungates_class_creation(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'policy_training_completed_at' => null]);

        // Attempt course creation before completing policy training
        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.courses.store'), [
            'title' => 'Unapproved Class',
            'description' => 'Description',
            'price' => 10,
            'course_type' => 'live',
        ]);

        $response->assertRedirect(route('tutor.policy-training'));
        $this->assertDatabaseMissing('courses', ['title' => 'Unapproved Class']);

        // Complete both policy training modules
        $this->actingAs($tutor, 'tutor')->post(route('tutor.policy-training.complete'), ['module_slug' => 'content_standards']);
        $this->actingAs($tutor, 'tutor')->post(route('tutor.policy-training.complete'), ['module_slug' => 'safety_privacy']);

        $tutor->refresh();
        $this->assertTrue($tutor->hasCompletedPolicyTraining());

        // Now post class creation succeeded
        $response2 = $this->actingAs($tutor, 'tutor')->post(route('tutor.courses.store'), [
            'title' => 'Approved Class',
            'description' => 'Description',
            'price' => 10,
            'course_type' => 'live',
        ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('courses', ['title' => 'Approved Class']);
    }

    public function test_re_engagement_nudge_dispatch(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $parent = User::factory()->create(['role' => 'student', 'email' => 'parent@example.com']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Robotics 101',
            'description' => 'Robotics',
            'price' => 100,
            'course_type' => 'live',
        ]);

        $reEngagementService = app(ReEngagementService::class);
        $lead = $reEngagementService->trackInterest($parent->id, $course->id);

        $this->assertDatabaseHas('interested_leads', [
            'user_id' => $parent->id,
            'course_id' => $course->id,
            'status' => 'interested',
        ]);

        // Trigger nudge
        $nudged = $reEngagementService->sendNudge($lead);
        $this->assertTrue($nudged);
        $this->assertDatabaseHas('interested_leads', [
            'id' => $lead->id,
            'status' => 'nudged',
        ]);
    }

    public function test_educator_resource_library_access(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);

        $response = $this->actingAs($tutor, 'tutor')->get(route('tutor.resources'));
        $response->assertStatus(200);
        $response->assertSee('Educator Handbook');
        $response->assertSee('Educator Resource Library');
    }

    public function test_admin_ops_nps_and_early_warning_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tutor = User::factory()->create(['role' => 'tutor', 'nps_score' => 7.0]); // At risk

        $response = $this->actingAs($admin, 'admin')->get(route('admin.ops.nps'));
        $response->assertStatus(200);
        $response->assertViewHas('atRiskTutors');
        $response->assertSee('Instructor Health Roster');
    }
}
