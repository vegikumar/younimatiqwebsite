<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingSeriesApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
            'timezone' => 'Asia/Kolkata',
        ], $overrides));
    }

    private function makeCourse(array $overrides = []): Course
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);

        return Course::create(array_merge([
            'tutor_id' => $tutor->id,
            'title' => 'Live Science',
            'description' => 'Recurring live classes',
            'price' => 120,
            'course_type' => 'live',
            'format' => 'tutoring',
            'number_of_classes' => 8,
            'status' => 'published',
        ], $overrides));
    }

    private function enrollWithSchedule(User $student, Course $course, array $schedule): void
    {
        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'transaction_id' => 'TXN-SERIES-'.$student->id.'-'.$course->id,
            'gateway' => 'stripe',
            'currency' => 'USD',
            'amount' => $course->price,
            'pricing_model' => 'custom',
            'delivery_mode' => 'live',
            'schedule' => $schedule,
            'status' => 'completed',
        ]);

        app(EnrollmentService::class)->enrollFromPayment($payment);
    }

    public function test_bookings_index_collapses_recurring_classes_into_one_series_per_course(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();

        $this->enrollWithSchedule($student, $course, [
            'classes' => 6,
            'start_date' => now()->addDay()->toDateString(),
            'days' => [1, 4],
            'times' => [1 => '16:30', 4 => '16:30'],
            'timezone' => 'Asia/Kolkata',
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/v1/student/bookings')->assertOk();

        $data = $response->json('data');

        // One entry for the course, not one per class.
        $this->assertCount(1, $data['series']);
        $this->assertSame($course->id, $data['series'][0]['course_id']);
        $this->assertSame(6, $data['series'][0]['classes']);
        $this->assertTrue($data['series'][0]['is_recurring']);
        $this->assertSame([1, 4], $data['series'][0]['days']);
        $this->assertSame('Asia/Kolkata', $data['series'][0]['timezone']);
        $this->assertNotEmpty($data['series'][0]['next_class_at']);

        // The flat class list still carries all 6 instances.
        $this->assertCount(6, $data['classes']);
        $this->assertSame('scheduled_class', $data['classes'][0]['session_type']);

        // Placeholder/enrollment rows are kept out of the class list.
        $this->assertSame(
            0,
            collect($data['classes'])->where('session_type', '!=', 'scheduled_class')->count()
        );
    }

    public function test_series_timezone_falls_back_to_student_timezone_without_hardcoding_utc(): void
    {
        $student = $this->makeStudent(['timezone' => 'America/Toronto']);
        $course = $this->makeCourse();

        $this->enrollWithSchedule($student, $course, [
            'classes' => 3,
            'start_date' => now()->addDay()->toDateString(),
            'days' => [2],
            'times' => [2 => '18:00'],
            'timezone' => 'UTC', // intentionally wrong; booking rows carry no parent_timezone
        ]);

        // bookings.timezone is NOT NULL defaulting to 'UTC'; parent_timezone is nullable.
        Booking::where('student_id', $student->id)
            ->update(['parent_timezone' => null, 'timezone' => 'UTC']);

        Sanctum::actingAs($student);

        $series = $this->getJson('/api/v1/student/bookings')->assertOk()->json('data.series');

        $this->assertSame('America/Toronto', $series[0]['timezone']);
    }

    public function test_series_reports_paid_class_count_when_no_classes_are_scheduled_yet(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse(['number_of_classes' => 10]);

        $this->enrollWithSchedule($student, $course, [
            'classes' => 4,
            'start_date' => now()->addDay()->toDateString(),
            'days' => [0],
            'times' => [0 => '10:00'],
            'timezone' => 'UTC',
        ]);

        // Simulate "bought but not scheduled yet".
        Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->delete();

        Sanctum::actingAs($student);

        $series = $this->getJson('/api/v1/student/bookings')->assertOk()->json('data.series');

        $this->assertSame(1, count($series));
        $this->assertFalse($series[0]['is_recurring']);
        $this->assertSame([], $series[0]['days']);
        // Falls back to the enrollment's paid_classes_count, not the course default.
        $this->assertSame(4, $series[0]['classes']);
    }

    public function test_series_groups_separate_courses_into_separate_entries(): void
    {
        $student = $this->makeStudent();
        $courseA = $this->makeCourse(['title' => 'Course A']);
        $courseB = $this->makeCourse(['title' => 'Course B']);

        $this->enrollWithSchedule($student, $courseA, [
            'classes' => 2,
            'start_date' => now()->addDay()->toDateString(),
            'days' => [1],
            'times' => [1 => '09:00'],
            'timezone' => 'UTC',
        ]);
        $this->enrollWithSchedule($student, $courseB, [
            'classes' => 3,
            'start_date' => now()->addDays(2)->toDateString(),
            'days' => [3, 5],
            'times' => [3 => '11:00', 5 => '11:00'],
            'timezone' => 'UTC',
        ]);

        Sanctum::actingAs($student);

        $series = $this->getJson('/api/v1/student/bookings')->assertOk()->json('data.series');

        $this->assertCount(2, $series);
        $byCourse = collect($series)->keyBy('course_id');
        $this->assertSame(2, $byCourse[$courseA->id]['classes']);
        $this->assertSame(3, $byCourse[$courseB->id]['classes']);
        $this->assertSame([1], $byCourse[$courseA->id]['days']);
        $this->assertSame([3, 5], $byCourse[$courseB->id]['days']);
    }
}
