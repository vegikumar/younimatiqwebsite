<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_is_created_from_completed_payment(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'email_verified_at' => now()]);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Test Course',
            'description' => 'Desc',
            'price' => 50,
            'course_type' => 'self-paced',
            'status' => 'published',
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 50,
            'status' => 'pending',
            'gateway' => 'stripe',
            'transaction_id' => 'init_test_1',
        ]);

        app(EnrollmentService::class)->enrollFromPayment($payment->fresh());
        $payment->update(['status' => 'completed']);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }

    public function test_refund_marks_payment_and_cancels_enrollment(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Refund Course',
            'description' => 'Desc',
            'price' => 25,
            'course_type' => 'self-paced',
            'status' => 'published',
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 25,
            'status' => 'completed',
            'gateway' => 'manual',
            'transaction_id' => 'MANUAL-1',
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_id' => $payment->id,
            'status' => 'active',
        ]);

        $result = app(\App\Services\RefundService::class)->refundPayment($payment->fresh());

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('enrollments', ['payment_id' => $payment->id, 'status' => 'cancelled']);
    }
}
