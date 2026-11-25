<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_student_cannot_login(): void
    {
        $student = User::factory()->create([
            'email' => 'suspended@test.com',
            'role' => 'student',
            'status' => 'suspended',
            'password' => bcrypt('password'),
        ]);
        // Suspended users with enrollments are hard-blocked (trial-expired
        // bypass only applies to suspended users with no enrollments).
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => Course::create([
                'tutor_id' => User::factory()->create(['role' => 'tutor'])->id,
                'title' => 'Suspended Test Course',
                'description' => 'Desc',
                'price' => 10,
                'course_type' => 'live',
            ])->id,
            'status' => 'active',
        ]);

        $response = $this->from('/login/student')->post('/login/student', [
            'email' => 'suspended@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login/student');
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_pending_tutor_redirected_to_pending_page(): void
    {
        User::factory()->create([
            'email' => 'pending@test.com',
            'role' => 'tutor',
            'status' => 'pending',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login/tutor')->post('/login/tutor', [
            'email' => 'pending@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('tutor.pending'));
        $this->assertAuthenticated('tutor');
    }
}
