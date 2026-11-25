<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\EmailService;
use App\Services\EnrollmentService;
use App\Services\PaymentVerificationService;
use App\Support\BundleRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private PaymentVerificationService $paymentVerification,
        private EmailService $emailService,
    ) {}

    public function success(Request $request)
    {
        $payment = Payment::find($request->query('payment_id'));

        if (! $payment || $payment->student_id !== Auth::guard('web')->id()) {
            return BundleRedirect::route('student.courses', flash: ['error' => 'Invalid payment record.']);
        }

        if ($payment->status === 'completed') {
            return BundleRedirect::route('student.courses', flash: ['success' => 'Payment already processed.']);
        }

        $verified = false;
        $transactionId = $request->query('transaction_id', '');

        if ($payment->gateway === 'stripe' && $request->query('session_id')) {
            if ($this->paymentVerification->verifyStripe($request->query('session_id'))) {
                $verified = true;
                $transactionId = $request->query('session_id');
            }
        } elseif ($payment->gateway === 'razorpay') {
            if ($this->paymentVerification->verifyRazorpay($request)) {
                $verified = true;
                $transactionId = $request->input('razorpay_payment_id');
            }
        } elseif ($payment->gateway === 'paypal') {
            // Hosted PayPal return sends token=ORDER_ID; our redirects send paypal_order_id.
            $paypalOrderId = $request->query('paypal_order_id')
                ?? $request->query('token')
                ?? $request->query('transaction_id')
                ?? $payment->transaction_id;
            if ($paypalOrderId && ! str_starts_with((string) $paypalOrderId, 'init_')
                && $this->paymentVerification->verifyPayPal($paypalOrderId)) {
                $verified = true;
                $transactionId = $paypalOrderId;
            }
        }

        if (! $verified) {
            return BundleRedirect::route('student.courses', flash: ['error' => 'Payment verification failed.']);
        }

        $this->paymentVerification->completePayment($payment, $transactionId, $this->enrollmentService);

        $payment->load('course', 'student');
        $currency = strtoupper($payment->currency);
        $amountFormatted = $currency . ' ' . number_format($payment->amount, 2);

        $customDetails = session("payment_{$payment->id}_custom_enrollment");

        if ($customDetails) {
            $scheduleSummary = $this->customEnrollmentScheduleSummary($payment, $customDetails);

            $adminSubject = 'New Custom Course Enrollment';
            $adminBody = "A student has enrolled in a custom course.<br>
            <strong>Course:</strong> {$payment->course->title}<br>
            <strong>Student:</strong> {$payment->student->name} ({$payment->student->email})<br>
            <strong>Amount:</strong> {$amountFormatted}<br>
            {$scheduleSummary}";

            $this->emailService->notifyAdmins($adminSubject, $adminBody);

            // Clean up session
            session()->forget("payment_{$payment->id}_custom_enrollment");
        } else {
            $this->emailService->notifyAdmins(
                'New Course Enrollment',
                'A student has enrolled in the course: ' . $payment->course->title . '<br>Student: ' . $payment->student->name . '<br>Amount: ' . $amountFormatted
            );
        }

        return BundleRedirect::route('student.courses', flash: ['success' => 'Successfully enrolled in the course!']);
    }

    public function cancel(Request $request)
    {
        $payment = Payment::find($request->query('payment_id'));

        if ($payment && $payment->status === 'pending') {
            $payment->update(['status' => 'failed']);

            // Checkout uses the public shell.
            return redirect()->route('student.checkout', $payment->course_id)->with('error', 'Payment cancelled.');
        }

        return redirect()->route('courses.index')->with('error', 'Payment cancelled.');
    }

    /**
     * Prefer recurring schedule from the payment row; fall back to legacy class_date/time.
     */
    private function customEnrollmentScheduleSummary(Payment $payment, array $customDetails): string
    {
        $schedule = is_array($payment->schedule) ? $payment->schedule : null;
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        if ($schedule && ! empty($schedule['days']) && is_array($schedule['days'])) {
            $parts = [];
            foreach ($schedule['days'] as $day) {
                $label = $dayNames[(int) $day] ?? ('Day '.$day);
                $time = $schedule['times'][$day] ?? $schedule['times'][(string) $day] ?? '';
                $parts[] = trim($label.($time !== '' ? ' '.$time : ''));
            }
            $tz = e($schedule['timezone'] ?? ($customDetails['timezone'] ?? 'UTC'));
            $start = e($schedule['start_date'] ?? ($customDetails['class_date'] ?? ''));
            $daysLine = e(implode(', ', $parts));

            return "<p><strong>Preferred start:</strong> {$start}</p><p><strong>Preferred days/times:</strong> {$daysLine} ({$tz})</p>";
        }

        $start = e($customDetails['class_date'] ?? '');
        $time = e($customDetails['class_time'] ?? '');
        $tz = e($customDetails['timezone'] ?? 'UTC');

        return "<p><strong>Start Date:</strong> {$start}</p><p><strong>Class Time:</strong> {$time} ({$tz})</p>";
    }
}
