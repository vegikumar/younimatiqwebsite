<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $overrides = []): Course
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);

        return Course::create(array_merge([
            'tutor_id' => $tutor->id,
            'title' => 'Live Math',
            'description' => 'Live classes',
            'price' => 100,
            'course_type' => 'live',
            'format' => 'tutoring',
            'number_of_classes' => 8,
            'status' => 'published',
        ], $overrides));
    }

    private function makeStudent(): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_enrollment_schedules_purchased_classes_on_recurring_days(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'transaction_id' => 'TXN-SCHED-1',
            'gateway' => 'stripe',
            'currency' => 'USD',
            'amount' => 100,
            'pricing_model' => 'custom',
            'delivery_mode' => 'live',
            'schedule' => [
                'classes' => 5,
                'start_date' => now()->addDay()->toDateString(),
                'days' => [0, 1, 2, 3, 4, 5, 6],
                'times' => [],
                'timezone' => 'UTC',
            ],
            'status' => 'completed',
        ]);

        $enrollment = app(EnrollmentService::class)->enrollFromPayment($payment);

        $this->assertSame(5, $enrollment->paid_classes_count);

        $bookings = Booking::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('session_type', 'scheduled_class')
            ->get();

        $this->assertCount(5, $bookings);
        $this->assertTrue($bookings->every(fn ($b) => $b->scheduled_at->isFuture()));
    }

    public function test_self_paced_enrollment_does_not_create_scheduled_bookings(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse(['course_type' => 'self-paced']);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'transaction_id' => 'TXN-SCHED-2',
            'gateway' => 'stripe',
            'currency' => 'USD',
            'amount' => 100,
            'delivery_mode' => 'self-paced',
            'schedule' => [
                'classes' => 3,
                'start_date' => now()->addDay()->toDateString(),
                'days' => [1, 3, 5],
                'times' => [1 => '17:00', 3 => '17:00', 5 => '17:00'],
                'timezone' => 'UTC',
            ],
            'status' => 'completed',
        ]);

        app(EnrollmentService::class)->enrollFromPayment($payment);

        $this->assertSame(0, Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->count());
    }
}
