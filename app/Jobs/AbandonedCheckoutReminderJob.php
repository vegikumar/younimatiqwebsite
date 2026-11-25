<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\EmailService;
use App\Services\ReEngagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AbandonedCheckoutReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(EmailService $emailService, ReEngagementService $reEngagement): void
    {
        $payments = Payment::with(['student', 'course'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHour())
            ->where('created_at', '>=', now()->subHours(2))
            ->get();

        foreach ($payments as $payment) {
            if (!$payment->student || !$payment->course) {
                continue;
            }

            try {
                $reEngagement->trackInterest($payment->student_id, $payment->course_id);
            } catch (\Throwable $e) {
                Log::warning('Abandoned trackInterest: '.$e->getMessage());
            }

            if (!$payment->student->wantsEmail('email_reminders')) {
                continue;
            }

            $emailService->sendTemplate('abandoned_checkout', $payment->student->email, [
                'student_name' => $payment->student->name,
                'course_title' => $payment->course->title,
                'amount' => number_format($payment->amount, 2),
                'checkout_url' => route('student.checkout', $payment->course_id),
            ]);
        }
    }
}
