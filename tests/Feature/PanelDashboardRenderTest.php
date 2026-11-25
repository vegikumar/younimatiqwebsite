<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelDashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function tutor(): User
    {
        return User::factory()->create(['role' => 'tutor', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_tutor_dashboard_renders(): void
    {
        $tutor = $this->tutor();
        $student = $this->student();

        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Tutor Dashboard Course',
            'description' => 'Desc',
            'price' => 10,
            'course_type' => 'self-paced',
            'status' => 'published',
        ]);

        $course->enrollments()->create([
            'user_id' => $student->id,
            'status' => 'active',
        ]);

        $this->actingAs($tutor, 'tutor')
            ->get(route('tutor.dashboard'))
            ->assertOk();
    }

    public function test_student_dashboard_renders(): void
    {
        $this->actingAs($this->student(), 'web')
            ->get(route('student.dashboard'))
            ->assertOk();
    }
}
