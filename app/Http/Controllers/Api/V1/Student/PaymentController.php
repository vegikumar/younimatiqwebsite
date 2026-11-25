<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\EmailService;
use App\Services\EnrollmentService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private EnrollmentService $enrollmentService,
        private PaymentVerificationService $paymentVerification,
        private EmailService $emailService,
    ) {}

    public function verify(Request $request)
    {
        $data = $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'gateway' => 'nullable|string|in:stripe,razorpay,paypal',
            'session_id' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
            'paypal_order_id' => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($data['payment_id']);

        if ((int) $payment->student_id !== (int) $request->user()->id) {
            return $this->error('Invalid payment record.', 403);
        }

        if ($payment->status === 'completed') {
            $payment->load('course');

            return $this->success([
                'payment_id' => $payment->id,
                'status' => 'completed',
                'course' => $payment->course ? [
                    'id' => $payment->course->id,
                    'title' => $payment->course->title,
                    'slug' => $payment->course->slug,
                ] : null,
            ], 'Payment already processed.');
        }

        $gateway = $data['gateway'] ?? $payment->gateway;
        $verified = false;
        $transactionId = $data['transaction_id'] ?? '';

        if ($gateway === 'stripe') {
            $sessionId = $data['session_id'] ?? $data['transaction_id'] ?? null;
            if ($sessionId && $this->paymentVerification->verifyStripe($sessionId)) {
                $verified = true;
                $transactionId = $sessionId;
            }
        } elseif ($gateway === 'razorpay') {
            if ($this->paymentVerification->verifyRazorpay($request)) {
                $verified = true;
                $transactionId = $request->input('razorpay_payment_id');
            }
        } elseif ($gateway === 'paypal') {
            $paypalOrderId = $data['paypal_order_id'] ?? $data['transaction_id'] ?? null;
            if ($paypalOrderId && $this->paymentVerification->verifyPayPal($paypalOrderId)) {
                $verified = true;
                $transactionId = $paypalOrderId;
            }
        }

        if (! $verified) {
            return $this->error('Payment verification failed.', 422);
        }

        $this->paymentVerification->completePayment($payment, (string) $transactionId, $this->enrollmentService);
        $this->sendEnrollmentEmails($payment->fresh(['course', 'student']));

        $payment->load('course');

        return $this->success([
            'payment_id' => $payment->id,
            'status' => 'completed',
            'course' => $payment->course ? [
                'id' => $payment->course->id,
                'title' => $payment->course->title,
                'slug' => $payment->course->slug,
            ] : null,
        ], 'Successfully enrolled in the course!');
    }

    public function cancel(Request $request)
    {
        $data = $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
        ]);

        $payment = Payment::findOrFail($data['payment_id']);

        if ((int) $payment->student_id !== (int) $request->user()->id) {
            return $this->error('Invalid payment record.', 403);
        }

        if ($payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
        }

        return $this->success([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'course_id' => $payment->course_id,
        ], 'Payment cancelled.');
    }

    /**
     * Stripe Checkout returns to HTTPS; complete payment then deep-link into the app.
     */
    public function stripeReturn(Request $request)
    {
        $payment = Payment::find($request->query('payment_id'));
        $status = $request->query('status', 'success');

        if (! $payment) {
            return redirect()->away('younimatiq://payment/cancel?error=invalid_payment');
        }

        if ($status === 'cancel') {
            if ($payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }

            return redirect()->away('younimatiq://payment/cancel?payment_id=' . $payment->id);
        }

        $sessionId = $request->query('session_id');
        if ($payment->status !== 'completed' && $sessionId && $this->paymentVerification->verifyStripe($sessionId)) {
            $this->paymentVerification->completePayment($payment, $sessionId, $this->enrollmentService);
            $this->sendEnrollmentEmails($payment->fresh(['course', 'student']));
        }

        return redirect()->away('younimatiq://payment/success?payment_id=' . $payment->id . ($sessionId ? '&session_id=' . urlencode($sessionId) : ''));
    }

    /**
     * PayPal Checkout returns to HTTPS; capture/complete then deep-link into the app.
     */
    public function paypalReturn(Request $request)
    {
        $payment = Payment::find($request->query('payment_id'));
        $status = $request->query('status', 'success');
        $token = $request->query('token'); // PayPal order id on return

        if (! $payment) {
            return redirect()->away('younimatiq://payment/cancel?error=invalid_payment');
        }

        if ($status === 'cancel') {
            if ($payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }

            return redirect()->away('younimatiq://payment/cancel?payment_id=' . $payment->id . '&gateway=paypal');
        }

        $paypalOrderId = $token ?: $payment->transaction_id;
        if ($payment->status !== 'completed' && $paypalOrderId && $this->paymentVerification->verifyPayPal($paypalOrderId)) {
            $this->paymentVerification->completePayment($payment, $paypalOrderId, $this->enrollmentService);
            $this->sendEnrollmentEmails($payment->fresh(['course', 'student']));
        }

        return redirect()->away(
            'younimatiq://payment/success?payment_id=' . $payment->id
            . '&gateway=paypal'
            . ($paypalOrderId ? '&paypal_order_id=' . urlencode($paypalOrderId) : '')
        );
    }

    private function sendEnrollmentEmails(Payment $payment): void
    {
        if (! $payment->student || ! $payment->course) {
            return;
        }

        $currency = strtoupper($payment->currency);
        $amountFormatted = $currency . ' ' . number_format((float) $payment->amount, 2);

        $this->emailService->notifyAdmins(
            'New Course Enrollment',
            'A student has enrolled in the course: ' . $payment->course->title . '<br>Student: ' . $payment->student->name . '<br>Amount: ' . $amountFormatted
        );
    }
}
