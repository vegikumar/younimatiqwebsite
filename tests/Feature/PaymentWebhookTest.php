<?php

namespace Tests\Feature;

use App\Models\CampBooking;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPaypalPayment(User $student, Course $course, string $orderId): Payment
    {
        return Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 50,
            'currency' => 'USD',
            'status' => 'pending',
            'gateway' => 'paypal',
            'transaction_id' => $orderId,
        ]);
    }

    private function pendingRazorpayPayment(User $student, Course $course, string $orderId): Payment
    {
        return Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 50,
            'currency' => 'USD',
            'status' => 'pending',
            'gateway' => 'razorpay',
            'transaction_id' => $orderId,
        ]);
    }

    public function test_paypal_webhook_completes_pending_payment_without_secret(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Webhook Course',
            'description' => 'Desc',
            'price' => 50,
            'course_type' => 'live',
            'status' => 'published',
        ]);
        $payment = $this->pendingPaypalPayment($student, $course, 'PAYPAL-ORDER-123');

        $payload = json_encode([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-1', 'parent_id' => 'PAYPAL-ORDER-123'],
        ]);

        $response = $this->call('POST', '/webhooks/paypal', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_paypal_webhook_rejects_when_webhook_id_set_without_credentials(): void
    {
        Setting::create(['key' => 'paypal_webhook_id', 'value' => 'WH-TEST-ID']);
        // Fail closed: webhook ID configured but API credentials missing → reject.
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Webhook Course 2',
            'description' => 'Desc',
            'price' => 50,
            'course_type' => 'live',
            'status' => 'published',
        ]);
        $payment = $this->pendingPaypalPayment($student, $course, 'PAYPAL-ORDER-456');

        $payload = json_encode([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAPTURE-2', 'parent_id' => 'PAYPAL-ORDER-456'],
        ]);

        $response = $this->call('POST', '/webhooks/paypal', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_razorpay_webhook_completes_pending_payment_with_valid_signature(): void
    {
        $secret = 'whsec_test_123';
        Setting::create(['key' => 'razorpay_webhook_secret', 'value' => $secret]);

        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'RZP Course',
            'description' => 'Desc',
            'price' => 50,
            'course_type' => 'live',
            'status' => 'published',
        ]);
        $payment = $this->pendingRazorpayPayment($student, $course, 'order_RZP1');

        $payload = json_encode([
            'event' => 'payment.captured',
            'payment' => ['entity' => ['id' => 'pay_RZP1', 'order_id' => 'order_RZP1']],
        ]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT' => 'payment.captured',
        ], $payload);

        $response->assertOk();
        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_razorpay_webhook_rejects_invalid_signature(): void
    {
        Setting::create(['key' => 'razorpay_webhook_secret', 'value' => 'whsec_test_123']);

        $payload = json_encode([
            'event' => 'payment.captured',
            'payment' => ['entity' => ['id' => 'pay_X', 'order_id' => 'order_X']],
        ]);

        $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'invalid',
            'HTTP_X_RAZORPAY_EVENT' => 'payment.captured',
        ], $payload);

        $response->assertStatus(400);
    }

    public function test_razorpay_webhook_marks_camp_booking_paid(): void
    {
        $secret = 'whsec_camp';
        Setting::create(['key' => 'razorpay_webhook_secret', 'value' => $secret]);

        $booking = CampBooking::create([
            'parent_name' => 'Jane Parent',
            'child_name' => 'Kid One',
            'camp_name' => 'Robotics Camp',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'age' => '8-10',
            'payment_status' => 'pending',
            'amount' => 10,
            'currency' => 'USD',
            'razorpay_order_id' => 'order_CAMP1',
        ]);

        $payload = json_encode([
            'event' => 'payment.captured',
            'payment' => ['entity' => ['id' => 'pay_CAMP1', 'order_id' => 'order_CAMP1']],
        ]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT' => 'payment.captured',
        ], $payload);

        $response->assertOk();
        $this->assertSame('paid', $booking->fresh()->payment_status);
    }

    public function test_razorpay_webhook_marks_failed_payment(): void
    {
        $secret = 'whsec_fail';
        Setting::create(['key' => 'razorpay_webhook_secret', 'value' => $secret]);

        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Fail Course',
            'description' => 'Desc',
            'price' => 50,
            'course_type' => 'live',
            'status' => 'published',
        ]);
        $payment = $this->pendingRazorpayPayment($student, $course, 'order_FAIL1');

        $payload = json_encode([
            'event' => 'payment.failed',
            'payment' => ['entity' => ['id' => 'pay_FAIL1', 'order_id' => 'order_FAIL1']],
        ]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT' => 'payment.failed',
        ], $payload);

        $response->assertOk();
        $this->assertSame('failed', $payment->fresh()->status);
    }
}
