<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TutorPayoutNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(EmailService $emailService): void
    {
        $adminEmail = \App\Models\Setting::where('key', 'admin_email')->value('value')
            ?? config('mail.from.address');

        $totalPlatformRevenue = 0.0;
        $tutors = User::where('role', 'tutor')->where('status', 'active')->get();

        foreach ($tutors as $tutor) {
            $courseIds = Course::where('tutor_id', $tutor->id)->pluck('id');
            $earnings = Payment::whereIn('course_id', $courseIds)
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount') * 0.8;

            if ($earnings > 0) {
                $totalPlatformRevenue += $earnings;
                if ($tutor->wantsEmail('email_reminders')) {
                    $emailService->sendTemplate('tutor_payout_summary', $tutor->email, [
                        'student_name' => $tutor->name,
                        'amount' => number_format($earnings, 2),
                    ]);
                }
            }
        }

        if ($adminEmail) {
            $emailService->sendTemplate('admin_payout_report', $adminEmail, [
                'student_name' => 'Admin',
                'amount' => number_format($totalPlatformRevenue, 2),
            ]);
        }
    }
}
