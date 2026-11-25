<?php

namespace App\Console\Commands;

use App\Http\Controllers\SessionNoteController;
use App\Mail\SessionReminderMail;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSessionReminders extends Command
{
    protected $signature = 'app:send-session-reminders';

    protected $description = 'Send 24h, 1h, and 30m reminders for trials and classes to students.';

    /**
     * Reminder tiers: minutes-before → flag column.
     * Catch-up uses half-open windows so a missed cron still delivers
     * the most relevant reminder without spamming every tier at once.
     *
     * @var array<int, array{minutes:int, flag:string, minRemaining:int}>
     */
    private array $tiers = [
        // 24h: from ~12h+ through 24h+5m
        ['minutes' => 24 * 60, 'flag' => 'reminder_24h_sent', 'minRemaining' => 60 + 5],
        // 1h: from ~35m through 1h+5m
        ['minutes' => 60, 'flag' => 'reminder_1h_sent', 'minRemaining' => 30 + 5],
        // 30m: from just before start through 35m
        ['minutes' => 30, 'flag' => 'reminder_30m_sent', 'minRemaining' => 0],
    ];

    public function handle(): int
    {
        $this->info('Starting session reminders...');

        $sent = 0;
        foreach ($this->tiers as $tier) {
            $sent += $this->processTier($tier['minutes'], $tier['flag'], $tier['minRemaining']);
        }

        $this->info("Session reminders completed ({$sent} recipient emails queued).");

        return self::SUCCESS;
    }

    protected function processTier(int $minutes, string $flagColumn, int $minRemainingMinutes): int
    {
        $now = now();
        $windowEnd = $now->copy()->addMinutes($minutes + 5);
        $windowStart = $now->copy()->addMinutes($minRemainingMinutes);

        $sent = 0;

        // 1) Trials / camps — always email the booking address (student/parent signup email)
        $camps = CampBooking::query()
            ->whereNotNull('scheduled_at')
            ->where($flagColumn, false)
            ->where('scheduled_at', '>', $windowStart)
            ->where('scheduled_at', '<=', $windowEnd)
            ->where(function ($q) {
                $q->whereIn('payment_status', ['paid', 'free', 'completed'])
                    ->orWhereNull('payment_status');
            })
            ->with(['tutor', 'course'])
            ->get();

        foreach ($camps as $camp) {
            $sessionName = $camp->camp_name
                ?: ($camp->course->title ?? 'Trial Class');
            $timeLabel = $this->timeLabel($camp->scheduled_at, $minutes);
            $parentWhen = TimezoneService::formatForEmail(
                $camp->scheduled_at,
                $camp->parent_timezone,
                $camp->timezone,
            );
            $tutorWhen = TimezoneService::formatForEmail(
                $camp->scheduled_at,
                $camp->tutor?->timezone,
                $camp->tutor_timezone,
                $camp->parent_timezone,
                $camp->timezone,
            );

            $cantAttendUrl = SessionNoteController::emailButtonUrl([
                'kind' => 'trial',
                'ref' => 'camp:'.$camp->id,
                'session' => $sessionName,
                'when' => $parentWhen,
                'name' => $camp->parent_name ?: $camp->child_name,
                'email' => $camp->email,
            ]);

            $ok = $this->sendEmail(
                $camp->email,
                $camp->parent_name ?: ($camp->child_name ?: 'Learner'),
                $sessionName,
                $timeLabel,
                $camp->zoom_join_url,
                false,
                $parentWhen,
                'trial',
                $cantAttendUrl
            );

            if ($camp->tutor?->email) {
                $this->sendEmail(
                    $camp->tutor->email,
                    $camp->tutor->name,
                    $sessionName,
                    $timeLabel,
                    $camp->zoom_start_url,
                    true,
                    $tutorWhen,
                    'trial'
                );
            }

            if ($ok) {
                $camp->update([$flagColumn => true]);
                $sent++;
            }
        }

        // 2) Bookings (1:1 / scheduled classes) — email the student
        $bookings = Booking::query()
            ->whereNotNull('scheduled_at')
            ->where($flagColumn, false)
            ->where('scheduled_at', '>', $windowStart)
            ->where('scheduled_at', '<=', $windowEnd)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('session_type')
                    ->orWhereNotIn('session_type', ['enrollment', 'course_start']);
            })
            ->with(['tutor', 'student', 'course'])
            ->get();

        foreach ($bookings as $booking) {
            $sessionName = $booking->course->title ?? 'Class';
            $timeLabel = $this->timeLabel($booking->scheduled_at, $minutes);
            $studentWhen = TimezoneService::formatForEmail(
                $booking->scheduled_at,
                $booking->student?->timezone,
                $booking->parent_timezone,
                $booking->timezone,
            );
            $tutorWhen = TimezoneService::formatForEmail(
                $booking->scheduled_at,
                $booking->tutor?->timezone,
                $booking->tutor_timezone,
                $booking->parent_timezone,
                $booking->timezone,
            );

            $cantAttendUrl = SessionNoteController::emailButtonUrl([
                'kind' => 'class',
                'ref' => 'booking:'.$booking->id,
                'session' => $sessionName,
                'when' => $studentWhen,
                'name' => $booking->student?->name,
                'email' => $booking->student?->email,
            ]);

            $studentOk = false;
            if ($booking->student?->email && $booking->student->wantsEmail('email_reminders')) {
                $studentOk = $this->sendEmail(
                    $booking->student->email,
                    $booking->student->name,
                    $sessionName,
                    $timeLabel,
                    $booking->zoom_join_url,
                    false,
                    $studentWhen,
                    'class',
                    $cantAttendUrl
                );
            }

            if ($booking->tutor?->email) {
                $this->sendEmail(
                    $booking->tutor->email,
                    $booking->tutor->name,
                    $sessionName,
                    $timeLabel,
                    $booking->zoom_start_url,
                    true,
                    $tutorWhen,
                    'class'
                );
            }

            $booking->update([$flagColumn => true]);
            if ($studentOk) {
                $sent++;
            }
        }

        // 3) Class schedules (legacy per-student live rows)
        $classes = ClassSchedule::query()
            ->whereNotNull('scheduled_at')
            ->where($flagColumn, false)
            ->where('scheduled_at', '>', $windowStart)
            ->where('scheduled_at', '<=', $windowEnd)
            ->where('status', 'scheduled')
            ->with(['tutor', 'student', 'liveSession.course'])
            ->get();

        foreach ($classes as $class) {
            $sessionName = $class->liveSession?->course?->title ?? 'Class';
            $timeLabel = $this->timeLabel($class->scheduled_at, $minutes);
            $studentWhen = TimezoneService::formatForEmail(
                $class->scheduled_at,
                $class->student?->timezone,
            );
            $tutorWhen = TimezoneService::formatForEmail(
                $class->scheduled_at,
                $class->tutor?->timezone,
                $class->student?->timezone,
            );

            $cantAttendUrl = SessionNoteController::emailButtonUrl([
                'kind' => 'class',
                'ref' => null,
                'session' => $sessionName,
                'when' => $studentWhen,
                'name' => $class->student?->name,
                'email' => $class->student?->email,
            ]);

            $studentOk = false;
            if ($class->student?->email && $class->student->wantsEmail('email_reminders')) {
                $studentOk = $this->sendEmail(
                    $class->student->email,
                    $class->student->name,
                    $sessionName,
                    $timeLabel,
                    $class->zoom_join_url,
                    false,
                    $studentWhen,
                    'class',
                    $cantAttendUrl
                );
            }

            if ($class->tutor?->email) {
                $this->sendEmail(
                    $class->tutor->email,
                    $class->tutor->name,
                    $sessionName,
                    $timeLabel,
                    $class->zoom_start_url,
                    true,
                    $tutorWhen,
                    'class'
                );
            }

            $class->update([$flagColumn => true]);
            if ($studentOk) {
                $sent++;
            }
        }

        // 4) Group live sessions — email every actively enrolled student
        if (\Illuminate\Support\Facades\Schema::hasColumn('live_sessions', $flagColumn)) {
            $sessions = LiveSession::query()
                ->whereNotNull('start_time')
                ->where($flagColumn, false)
                ->where('start_time', '>', $windowStart)
                ->where('start_time', '<=', $windowEnd)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['cancelled', 'canceled', 'completed']);
                })
                ->with(['tutor', 'course'])
                ->get();

            foreach ($sessions as $session) {
                $sessionName = $session->course?->title ?? 'Class';
                $timeLabel = $this->timeLabel($session->start_time, $minutes);
                $students = Enrollment::with('user')
                    ->where('course_id', $session->course_id)
                    ->whereIn('status', ['active', 'completed'])
                    ->get()
                    ->pluck('user')
                    ->filter();

                $anyStudent = false;
                foreach ($students as $student) {
                    if (! $student?->email || ! $student->wantsEmail('email_reminders')) {
                        continue;
                    }
                    $whenLabel = TimezoneService::formatForEmail($session->start_time, $student->timezone);
                    $cantAttendUrl = SessionNoteController::emailButtonUrl([
                        'kind' => 'class',
                        'session' => $sessionName,
                        'when' => $whenLabel,
                        'name' => $student->name,
                        'email' => $student->email,
                    ]);
                    if ($this->sendEmail(
                        $student->email,
                        $student->name,
                        $sessionName,
                        $timeLabel,
                        $session->join_url,
                        false,
                        $whenLabel,
                        'class',
                        $cantAttendUrl
                    )) {
                        $anyStudent = true;
                        $sent++;
                    }
                }

                if ($session->tutor?->email) {
                    $whenLabel = TimezoneService::formatForEmail(
                        $session->start_time,
                        $session->tutor->timezone,
                    );
                    $this->sendEmail(
                        $session->tutor->email,
                        $session->tutor->name,
                        $sessionName,
                        $timeLabel,
                        $session->start_url,
                        true,
                        $whenLabel,
                        'class'
                    );
                }

                $session->update([$flagColumn => true]);
                if (! $anyStudent) {
                    // still marked to avoid retry loops when a course has no enrolled students yet
                }
            }
        }

        return $sent;
    }

    protected function timeLabel(Carbon|string $scheduledAt, int $fallbackMinutes): string
    {
        $at = Carbon::parse($scheduledAt);
        $mins = max(1, (int) now()->diffInMinutes($at, false));

        if ($mins >= 60) {
            $hours = (int) round($mins / 60);
            if ($hours <= 1) {
                return '1 hour';
            }

            return "{$hours} hours";
        }

        if ($mins >= 25 && $mins <= 40) {
            return '30 minutes';
        }

        return $mins.' minutes';
    }

    protected function sendEmail(
        ?string $email,
        string $name,
        string $sessionName,
        string $timeString,
        ?string $url,
        bool $isTutor,
        ?string $whenLabel = null,
        string $kind = 'class',
        ?string $cantAttendUrl = null,
    ): bool {
        $email = trim((string) $email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($email)->queue(new SessionReminderMail(
                $sessionName,
                $timeString,
                $name,
                $url,
                $isTutor,
                $whenLabel,
                $kind,
                // Only show “Can't join?” when this email includes a meeting join link.
                ($isTutor || ! filled($url)) ? null : $cantAttendUrl,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send session reminder', [
                'email' => $email,
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            $this->error("Failed to send reminder to {$email}: ".$e->getMessage());

            return false;
        }
    }
}
