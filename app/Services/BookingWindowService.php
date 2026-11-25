<?php

namespace App\Services;

use App\Models\CampBooking;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BookingWindowService
{
    public const IST = 'Asia/Kolkata';

    public const SLOT_MINUTES = 60;

    public const CONSULTATION_MIN_HOURS = 48;

    /** Free trial / demo class bookings also require this lead time. */
    public const FREE_CLASS_MIN_HOURS = 48;

    /**
     * Class hours in IST. A session may start at the window start and must
     * finish by the window end (60-minute sessions).
     *
     * @var array<int, array{start: string, end: string, label: string}>
     */
    public const WINDOWS = [
        ['start' => '04:30', 'end' => '07:30', 'label' => '4:30 AM – 7:30 AM IST'],
        ['start' => '18:30', 'end' => '23:30', 'label' => '6:30 PM – 11:30 PM IST'],
    ];

    /**
     * Preferred 60-minute start times in IST (HH:mm).
     *
     * @return string[]
     */
    public function preferredIstStarts(): array
    {
        $starts = [];

        foreach (self::WINDOWS as $window) {
            $cursor = Carbon::createFromFormat('H:i', $window['start'], self::IST);
            $end = Carbon::createFromFormat('H:i', $window['end'], self::IST);

            while ($cursor->copy()->addMinutes(self::SLOT_MINUTES)->lte($end)) {
                $starts[] = $cursor->format('H:i');
                $cursor->addMinutes(self::SLOT_MINUTES);
            }
        }

        return $starts;
    }

    public function isWithinWindow(CarbonInterface $utc, int $durationMinutes = self::SLOT_MINUTES): bool
    {
        $ist = Carbon::instance($utc)->utc()->timezone(self::IST);
        $startMin = ($ist->hour * 60) + $ist->minute;
        $endMin = $startMin + $durationMinutes;

        foreach (self::WINDOWS as $window) {
            if ($startMin >= $this->hmToMinutes($window['start']) && $endMin <= $this->hmToMinutes($window['end'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Slots whose local calendar date matches $localDate in $timezone.
     *
     * @return array<int, array<string, mixed>>
     */
    public function candidateSlotsForLocalDate(
        string $localDate,
        string $timezone,
        bool $skipPast = true,
        int $minHoursAhead = 0
    ): array {
        $tz = TimezoneService::sanitizeTimezone($timezone);
        $dayStart = Carbon::createFromFormat('Y-m-d H:i:s', $localDate.' 00:00:00', $tz);
        $dayEnd = $dayStart->copy()->addDay();

        $istFrom = $dayStart->copy()->timezone(self::IST)->startOfDay()->subDay();
        $istTo = $dayEnd->copy()->timezone(self::IST)->startOfDay()->addDay();

        $slots = [];
        $now = now('UTC');
        $earliest = $minHoursAhead > 0 ? $now->copy()->addHours($minHoursAhead) : null;

        for ($day = $istFrom->copy(); $day->lte($istTo); $day->addDay()) {
            foreach ($this->preferredIstStarts() as $time) {
                $istStart = Carbon::createFromFormat('Y-m-d H:i', $day->toDateString().' '.$time, self::IST)->second(0);
                $utc = $istStart->copy()->utc();
                $local = $utc->copy()->timezone($tz);

                if ($local->toDateString() !== $localDate) {
                    continue;
                }

                $past = $utc->lte($now);
                $tooSoon = $earliest ? $utc->lt($earliest) : false;
                if ($skipPast && ($past || $tooSoon)) {
                    continue;
                }

                $localEnd = $local->copy()->addMinutes(self::SLOT_MINUTES);

                $slots[] = [
                    'time' => $time,
                    'ist_date' => $istStart->toDateString(),
                    'utc_iso' => $utc->toIso8601String(),
                    'local_time' => $local->format('H:i'),
                    'label' => $local->format('g:i A').' – '.$localEnd->format('g:i A'),
                    'ist_label' => $istStart->format('g:i A').' IST',
                    'past' => $past,
                    'too_soon' => $tooSoon,
                    'preferred' => true,
                ];
            }
        }

        usort($slots, fn ($a, $b) => strcmp($a['utc_iso'], $b['utc_iso']));

        return $slots;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function slotsForLocalDate(
        string $localDate,
        string $timezone,
        bool $skipPast = true,
        int $minHoursAhead = 0
    ): array {
        $slots = $this->candidateSlotsForLocalDate($localDate, $timezone, $skipPast, $minHoursAhead);

        foreach ($slots as &$slot) {
            $utc = Carbon::parse($slot['utc_iso'])->utc();
            $booked = CampBooking::where('scheduled_at', $utc)->exists();
            $slot['available'] = ! $booked && empty($slot['past']) && empty($slot['too_soon']);
        }

        return $slots;
    }

    public function minHoursForBookingType(?string $bookingType): int
    {
        // Consultation and free trial both need 48h so staff can confirm schedules.
        if (in_array($bookingType, ['consultation', 'free_class', 'trial', 'free_trial'], true)) {
            return self::CONSULTATION_MIN_HOURS;
        }

        return self::FREE_CLASS_MIN_HOURS;
    }

    /**
     * Preferred local times for each weekday, based on the first week from $fromDate.
     *
     * @return array<int, array<int, array{value: string, label: string, utc_iso: string}>>
     */
    public function localTimesByWeekday(string $timezone, string $fromDate): array
    {
        $tz = TimezoneService::sanitizeTimezone($timezone);
        $byWeekday = array_fill_keys(range(0, 6), []);

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::parse($fromDate, $tz)->addDays($i);
            $dow = (int) $date->dayOfWeek;
            foreach ($this->candidateSlotsForLocalDate($date->toDateString(), $tz, skipPast: false) as $slot) {
                $value = $slot['local_time'];
                if (! isset($byWeekday[$dow][$value])) {
                    $byWeekday[$dow][$value] = [
                        'value' => $value,
                        'label' => $slot['label'],
                        'utc_iso' => $slot['utc_iso'],
                    ];
                }
            }
        }

        foreach ($byWeekday as $dow => $times) {
            ksort($times);
            $byWeekday[$dow] = array_values($times);
        }

        return $byWeekday;
    }

    public function windowsLabel(): string
    {
        return '4:30–7:30 AM and 6:30–11:30 PM IST';
    }

    public function localWindowsDescription(string $timezone, ?string $onDate = null): string
    {
        $tz = TimezoneService::sanitizeTimezone($timezone);
        $date = $onDate ?: now($tz)->toDateString();
        $parts = [];

        foreach (self::WINDOWS as $window) {
            $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$window['start'], self::IST)->timezone($tz);
            $end = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$window['end'], self::IST)->timezone($tz);
            $parts[] = $start->format('g:i A').' – '.$end->format('g:i A');
        }

        return implode(' and ', $parts).' ('.$this->timezoneShortLabel($tz).')';
    }

    public function timezoneShortLabel(string $timezone): string
    {
        $tz = TimezoneService::sanitizeTimezone($timezone);

        try {
            $name = (new \DateTimeZone($tz))->getName();
        } catch (\Throwable $e) {
            return $tz;
        }

        $segments = explode('/', $name);

        return str_replace('_', ' ', end($segments));
    }

    /**
     * Resolve a booking form into a UTC datetime.
     *
     * Preferred slots submit an ISO-8601 UTC timestamp. Custom times are
     * interpreted in the parent's timezone on the chosen local date.
     */
    public function resolveFromRequest(
        ?string $scheduledDate,
        ?string $scheduledTime,
        ?string $timezone,
        bool $customSlot = false,
        ?string $customTime = null
    ): ?Carbon {
        if (! $scheduledDate) {
            return null;
        }

        $tz = TimezoneService::sanitizeTimezone($timezone);

        if ($customSlot || $scheduledTime === 'custom') {
            $time = $customTime ?: null;
            if (! $time) {
                return null;
            }
            $time = $this->normalizeTime($time);

            return Carbon::createFromFormat('Y-m-d H:i', $scheduledDate.' '.$time, $tz)->second(0)->utc();
        }

        if (! $scheduledTime) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $scheduledTime) || str_contains($scheduledTime, 'Z')) {
            return Carbon::parse($scheduledTime)->utc()->second(0);
        }

        $time = $this->normalizeTime($scheduledTime);

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $scheduledDate.' '.$time, self::IST)->second(0)->utc();
        } catch (\Throwable $e) {
            return Carbon::createFromFormat('Y-m-d H:i', $scheduledDate.' '.$time, $tz)->second(0)->utc();
        }
    }

    public function windowValidationMessage(string $timezone): string
    {
        return 'Please choose an available time slot.';
    }

    private function hmToMinutes(string $hm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hm));

        return ($h * 60) + $m;
    }

    private function normalizeTime(string $time): string
    {
        if (preg_match('/^\d{1,2}:\d{2}/', $time, $m)) {
            return strlen($m[0]) === 4 ? '0'.$m[0] : substr($m[0], 0, 5);
        }

        return $time;
    }
}
