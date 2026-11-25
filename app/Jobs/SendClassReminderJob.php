<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\EmailService;
use App\Services\TimezoneService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendClassReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $hoursBefore = 24) {}

    public function handle(EmailService $emailService): void
    {
        $from = now()->addHours($this->hoursBefore)->subMinutes(5);
        $to = now()->addHours($this->hoursBefore)->addMinutes(5);

        $bookings = Booking::with(['student', 'course'])
            ->where('session_type', 'live_session')
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();

        foreach ($bookings as $booking) {
            if (!$booking->student) {
                continue;
            }

            if (!$booking->student->wantsEmail('email_reminders')) {
                continue;
            }

            $tz = TimezoneService::resolveForDisplay(
                $booking->student->timezone,
                $booking->parent_timezone,
                $booking->timezone,
            );
            $emailService->sendTemplate('class_reminder', $booking->student->email, [
                'student_name' => $booking->student->name,
                'course_title' => $booking->course->title ?? 'Your class',
                'class_time' => TimezoneService::formatForEmail(
                    $booking->scheduled_at,
                    $booking->student->timezone,
                    $booking->parent_timezone,
                    $booking->timezone,
                ),
                'timezone' => TimezoneService::shortLabel($tz),
            ]);
        }
    }
}
