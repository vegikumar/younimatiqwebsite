<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\CourseUnit;
use App\Models\Payment;
use App\Models\TutorAvailability;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorClassAndContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_create_tutoring_class_and_syllabus_units(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'policy_training_completed_at' => now()]);

        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.courses.store'), [
            'title' => '1:1 Algebra Tutoring',
            'description' => 'Personalized one-on-one algebra lessons.',
            'price' => 25.00,
            'course_type' => 'live',
            'format' => 'tutoring',
            'welcome_message' => 'Welcome to 1:1 Algebra!',
            'welcome_video_url' => 'https://youtube.com/watch?v=demo',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'title' => '1:1 Algebra Tutoring',
            'format' => 'tutoring',
            'tutor_id' => $tutor->id,
            'welcome_message' => 'Welcome to 1:1 Algebra!',
        ]);

        $course = Course::where('title', '1:1 Algebra Tutoring')->first();

        // Create a Syllabus Unit
        $unitResponse = $this->actingAs($tutor, 'tutor')->post(route('tutor.courses.units.store', $course), [
            'title' => 'Unit 1: Linear Equations',
            'description' => 'Basics of solving linear equations.',
        ]);

        $unitResponse->assertRedirect();
        $this->assertDatabaseHas('course_units', [
            'course_id' => $course->id,
            'title' => 'Unit 1: Linear Equations',
        ]);

        $unit = CourseUnit::first();

        // Add Lesson Module to Unit
        $moduleResponse = $this->actingAs($tutor, 'tutor')->post(route('tutor.courses.modules.store', $course), [
            'title' => 'Lesson 1: Intro to Variables',
            'unit_id' => $unit->id,
            'content_type' => 'video',
            'content_url' => 'https://youtube.com/watch?v=intro',
        ]);

        $moduleResponse->assertRedirect();
        $this->assertDatabaseHas('course_modules', [
            'course_id' => $course->id,
            'unit_id' => $unit->id,
            'title' => 'Lesson 1: Intro to Variables',
        ]);
    }

    public function test_tutor_availability_management(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);

        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.availability.store'), [
            'format_type' => 'tutoring_1on1',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_recurring' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tutor_availabilities', [
            'tutor_id' => $tutor->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $slot = TutorAvailability::first();

        $deleteResponse = $this->actingAs($tutor, 'tutor')->delete(route('tutor.availability.destroy', $slot));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('tutor_availabilities', ['id' => $slot->id]);
    }

    public function test_announcements_creation_and_duplication(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course1 = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Math Course 1',
            'description' => 'Description 1',
            'price' => 10.00,
            'course_type' => 'live',
            'status' => 'published',
        ]);
        $course2 = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Math Course 2',
            'description' => 'Description 2',
            'price' => 10.00,
            'course_type' => 'live',
            'status' => 'published',
        ]);

        // Publish Announcement
        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.announcements.store'), [
            'course_id' => $course1->id,
            'title' => 'Midterm Prep',
            'content' => 'Review notes attached.',
            'publish_option' => 'now',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('announcements', [
            'course_id' => $course1->id,
            'title' => 'Midterm Prep',
            'status' => 'published',
        ]);

        $announcement = Announcement::first();

        // Duplicate to course2
        $duplicateResponse = $this->actingAs($tutor, 'tutor')->post(route('tutor.announcements.duplicate', $announcement), [
            'target_course_ids' => [$course2->id],
        ]);

        $duplicateResponse->assertRedirect();
        $this->assertDatabaseHas('announcements', [
            'course_id' => $course2->id,
            'title' => 'Midterm Prep',
            'status' => 'published',
        ]);
    }

    public function test_auto_welcome_post_generated_on_enrollment(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Python Masterclass',
            'description' => 'Learn Python',
            'price' => 100.00,
            'course_type' => 'live',
            'status' => 'published',
            'welcome_message' => 'Super excited to welcome you to Python Masterclass!',
            'welcome_video_url' => 'https://youtube.com/watch?v=welcome',
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 100.00,
            'gateway' => 'stripe',
            'transaction_id' => 'TXN-TEST-123',
            'status' => 'completed',
        ]);

        $enrollmentService = app(EnrollmentService::class);
        $enrollmentService->enrollFromPayment($payment);

        $this->assertDatabaseHas('announcements', [
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'is_welcome_post' => true,
            'content' => 'Super excited to welcome you to Python Masterclass!',
            'video_url' => 'https://youtube.com/watch?v=welcome',
        ]);
    }
}
