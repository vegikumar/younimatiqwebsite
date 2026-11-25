<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Builds and persists recurring class schedules.
 *
 * A schedule payload looks like:
 * [
 *   'classes'    => 8,                              // number of classes to place
 *   'start_date' => '2026-10-20',                   // Y-m-d
 *   'days'       => [1, 3, 5],                      // Carbon dayOfWeek ints (0=Sun … 6=Sat)
 *   'times'      => [1 => '17:00', 3 => '18:30'],   // time per weekday
 *   'timezone'   => 'Asia/Kolkata',
 * ]
 */
class ClassScheduleService
{
    /**
     * Normalize loose request input (web form, admin form, API body) into a schedule array.
     */
    public function normalize(array $input): ?array
    {
        $classes = (int) ($input['number_of_classes'] ?? $input['classes'] ?? $input['paid_classes'] ?? 0);

        $days = $input['days'] ?? $input['schedule_days'] ?? [];
        if (! is_array($days)) {
            $days = [];
        }
        $days = array_values(array_unique(array_map('intval', $days)));
        $days = array_values(array_filter($days, fn ($d) => $d >= 0 && $d <= 6));

        $rawTimes = $input['times'] ?? $input['schedule_times'] ?? [];
        $times = [];
        if (is_array($rawTimes)) {
            foreach ($rawTimes as $day => $time) {
                if ($time !== null && $time !== '') {
                    $times[(int) $day] = (string) $time;
                }
            }
        }

        $startDate = $input['start_date'] ?? $input['schedule_start_date'] ?? null;
        $timezone = $input['timezone'] ?? $input['schedule_timezone'] ?? null;

        if ($classes <= 0 || empty($days) || empty($startDate)) {
            return null;
        }

        try {
            $startDate = Carbon::parse($startDate)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'classes' => $classes,
            'start_date' => $startDate,
            'days' => $days,
            'times' => $times,
            'timezone' => TimezoneService::sanitizeTimezone($timezone),
        ];
    }

    /**
     * Expand a schedule into UTC Carbon datetimes, filling exactly $classes future slots.
     *
     * @return Carbon[]
     */
    public function buildDates(array $schedule): array
    {
        $classes = (int) ($schedule['classes'] ?? 0);
        if ($classes <= 0) {
            return [];
        }

        $days = array_values(array_filter(array_map('intval', $schedule['days'] ?? []), fn ($d) => $d >= 0 && $d <= 6));
        if (empty($days)) {
            return [];
        }

        $times = is_array($schedule['times'] ?? null) ? $schedule['times'] : [];
        $timezone = TimezoneService::sanitizeTimezone($schedule['timezone'] ?? null);

        try {
            $startDate = Carbon::parse($schedule['start_date'] ?? now()->toDateString());
        } catch (\Throwable $e) {
            $startDate = now();
        }

        $scheduledDates = [];
        $currentDate = $startDate->copy();
        $guard = 0;

        while (count($scheduledDates) < $classes && $guard < 400) {
            $dow = (int) $currentDate->dayOfWeek;

            if (in_array($dow, $days, true)) {
                $time = $times[$dow] ?? $times[(string) $dow] ?? '17:00';

                try {
                    $dateTime = Carbon::createFromFormat('Y-m-d H:i', $currentDate->format('Y-m-d').' '.$time, $timezone)->setTimezone('UTC');
                } catch (\Throwable $e) {
                    try {
                        $dateTime = Carbon::parse($currentDate->format('Y-m-d').' '.$time, $timezone)->setTimezone('UTC');
                    } catch (\Throwable $e) {
                        $dateTime = null;
                    }
                }

                if ($dateTime && $dateTime->isFuture()) {
                    $scheduledDates[] = $dateTime;
                }
            }

            $currentDate->addDay();
            $guard++;
        }

        return array_slice($scheduledDates, 0, $classes);
    }

    /**
     * Create scheduled_class bookings for a schedule payload.
     *
     * @return Booking[]
     */
    public function createBookings(
        int $studentId,
        int $courseId,
        array $schedule,
        ?int $tutorId = null,
        ?int $paymentId = null,
        ?string $description = null
    ): array {
        $dates = $this->buildDates($schedule);
        $timezone = $schedule['timezone'] ?? 'UTC';

        $created = [];
        foreach ($dates as $dateTime) {
            $created[] = Booking::create([
                'student_id' => $studentId,
                'tutor_id' => $tutorId,
                'course_id' => $courseId,
                'payment_id' => $paymentId,
                'status' => 'scheduled',
                'session_type' => 'scheduled_class',
                'scheduled_at' => $dateTime,
                'timezone' => $timezone,
                'parent_timezone' => $timezone,
                'description' => $description ?? 'Scheduled class',
            ]);
        }

        return $created;
    }

    public function assertWithinBookingWindows(array $schedule): void
    {
        $windows = app(BookingWindowService::class);
        $timezone = $schedule['timezone'] ?? 'UTC';

        foreach ($this->buildDates($schedule) as $dateTime) {
            if (! $windows->isWithinWindow($dateTime)) {
                throw ValidationException::withMessages([
                    'times' => $windows->windowValidationMessage($timezone),
                ]);
            }
        }
    }
}
