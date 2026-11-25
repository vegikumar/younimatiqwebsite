<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\TutorAvailability;
use App\Models\User;
use App\Services\AutoSchedulingService;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorSchedulingAndAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_create_separate_1on1_and_group_availabilities(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);

        // Create 1:1 availability
        $response1 = $this->actingAs($tutor, 'tutor')->post(route('tutor.availability.store'), [
            'format_type' => 'tutoring_1on1',
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_recurring' => 1,
        ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('tutor_availabilities', [
            'tutor_id' => $tutor->id,
            'format_type' => 'tutoring_1on1',
            'start_time' => '10:00',
        ]);

        // Create Group availability
        $response2 = $this->actingAs($tutor, 'tutor')->post(route('tutor.availability.store'), [
            'format_type' => 'group_class',
            'day_of_week' => 3,
            'start_time' => '14:00',
            'end_time' => '15:30',
            'max_capacity' => 10,
        ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('tutor_availabilities', [
            'tutor_id' => $tutor->id,
            'format_type' => 'group_class',
            'max_capacity' => 10,
        ]);
    }

    public function test_conflict_service_detects_overlapping_sessions(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Physics Group',
            'description' => 'Physics class',
            'price' => 50,
            'course_type' => 'live',
        ]);

        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);

        // Create live group session at 2:00 PM - 3:00 PM
        LiveSession::create([
            'course_id' => $course->id,
            'tutor_id' => $tutor->id,
            'zoom_meeting_id' => '123456789',
            'start_time' => $startTime,
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $conflictService = app(ScheduleConflictService::class);

        // Check 2:30 PM slot (overlaps)
        $hasConflict = $conflictService->hasConflict($tutor->id, (clone $startTime)->addMinutes(30), 60);
        $this->assertTrue($hasConflict);

        // Check 4:00 PM slot (no overlap)
        $noConflict = $conflictService->hasConflict($tutor->id, (clone $startTime)->addHours(2), 60);
        $this->assertFalse($noConflict);
    }

    public function test_auto_scheduling_parent_request_with_timezone(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'timezone' => 'America/New_York']);
        $student = User::factory()->create(['role' => 'student', 'timezone' => 'Europe/London']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => '1:1 Science Tutoring',
            'description' => 'Science',
            'price' => 40,
            'course_type' => 'live',
            'format' => 'tutoring',
        ]);

        // Add 1:1 slot
        TutorAvailability::create([
            'tutor_id' => $tutor->id,
            'format_type' => 'tutoring_1on1',
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $requestedTime = Carbon::now()->addDays(2)->setTime(14, 0); // 2:00 PM London time

        $autoService = app(AutoSchedulingService::class);
        $result = $autoService->scheduleRequest($student, $course, $requestedTime, 'Europe/London');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('bookings', [
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'status' => 'active',
        ]);
    }
}
