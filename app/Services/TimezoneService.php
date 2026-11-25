<?php

namespace App\Services;

use Carbon\Carbon;

class TimezoneService
{
    public static function sanitizeTimezone(?string $tz): string
    {
        if (! $tz) {
            return 'UTC';
        }
        $tzMap = [
            'Asia/Calcutta' => 'Asia/Kolkata',
            'Asia/Katmandu' => 'Asia/Kathmandu',
            'Asia/Saigon' => 'Asia/Ho_Chi_Minh',
            'Asia/Rangoon' => 'Asia/Yangon',
            'Asia/Macao' => 'Asia/Macau',
            'America/Buenos_Aires' => 'America/Argentina/Buenos_Aires',
        ];

        return $tzMap[$tz] ?? $tz;
    }

    /**
     * True when a stored timezone is empty or the useless UTC/GMT default.
     *
     * Booking rows frequently carry a bare 'UTC' written at insert time even
     * though the parent picked a real zone, so treat it as "unset" when
     * resolving what to show a user.
     */
    public static function isUnset(?string $tz): bool
    {
        if ($tz === null) {
            return true;
        }

        return in_array(trim($tz), ['', 'UTC', 'GMT', 'Etc/UTC', 'Etc/GMT'], true);
    }

    /**
     * Pick the first meaningful timezone from preferred + fallbacks.
     * Skips empty / bare UTC defaults so a real student zone wins over
     * placeholder values written at booking insert time.
     */
    public static function resolveForDisplay(?string $preferred = null, ?string ...$fallbacks): string
    {
        foreach (array_merge([$preferred], $fallbacks) as $candidate) {
            if (! self::isUnset($candidate)) {
                return self::sanitizeTimezone($candidate);
            }
        }

        return 'UTC';
    }

    public static function formatForUser($datetime, ?string $timezone = null, string $format = 'M d, Y g:i A'): string
    {
        if (! $datetime) {
            return '';
        }

        $tz = self::resolveForDisplay($timezone, auth()->user()?->timezone ?? null);

        return Carbon::parse($datetime)->timezone($tz)->format($format);
    }

    /**
     * Human-readable datetime for emails: always converts from stored UTC
     * into the best available recipient timezone and appends a clear label
     * (e.g. "Mon, Nov 23, 2026 · 7:30 PM (India Standard Time)").
     */
    public static function formatForEmail($datetime, ?string ...$timezoneCandidates): string
    {
        if (! $datetime) {
            return '';
        }

        $tz = self::resolveForDisplay(...$timezoneCandidates);
        $local = Carbon::parse($datetime)->timezone($tz);

        return $local->format('D, M j, Y · g:i A').' ('.self::shortLabel($tz).')';
    }

    /**
     * Date-only line for emails (same timezone resolution as formatForEmail).
     */
    public static function formatDateForEmail($datetime, ?string ...$timezoneCandidates): string
    {
        if (! $datetime) {
            return '';
        }

        $tz = self::resolveForDisplay(...$timezoneCandidates);

        return Carbon::parse($datetime)->timezone($tz)->format('l, F j, Y');
    }

    /**
     * Time-only line for emails (same timezone resolution as formatForEmail).
     */
    public static function formatTimeForEmail($datetime, ?string ...$timezoneCandidates): string
    {
        if (! $datetime) {
            return '';
        }

        $tz = self::resolveForDisplay(...$timezoneCandidates);
        $local = Carbon::parse($datetime)->timezone($tz);

        return $local->format('g:i A').' ('.self::shortLabel($tz).')';
    }

    public static function toUtc(string $datetime, string $fromTimezone): Carbon
    {
        return Carbon::parse($datetime, self::sanitizeTimezone($fromTimezone))->utc();
    }

    public static function commonTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/St_Johns' => 'Newfoundland (NST/NDT)',
            'America/Toronto' => 'Eastern Time (Canada)',
            'America/New_York' => 'Eastern Time (US)',
            'America/Chicago' => 'Central Time (US)',
            'America/Denver' => 'Mountain Time (US)',
            'America/Los_Angeles' => 'Pacific Time (US)',
            'Europe/London' => 'London (GMT/BST)',
            'Europe/Paris' => 'Central Europe',
            'Europe/Istanbul' => 'Turkey (Istanbul)',
            'Asia/Riyadh' => 'Saudi Arabia (Riyadh)',
            'Asia/Dubai' => 'Gulf Standard Time',
            'Asia/Kolkata' => 'India Standard Time',
            'Asia/Singapore' => 'Singapore',
            'Asia/Tokyo' => 'Japan (Tokyo)',
            'Australia/Sydney' => 'Australia Eastern',
        ];
    }

    /**
     * Short friendly timezone options for admin selects.
     * Includes $current (and optional extras) when not already in the common list.
     *
     * @param  string|null  $current
     * @param  array<int, string|null>  $extras
     * @return array<int, array{id: string, label: string}>
     */
    public static function commonOptionsForSelect(?string $current = null, array $extras = []): array
    {
        $common = self::commonTimezones();
        $options = [];

        foreach ($common as $id => $label) {
            $options[] = ['id' => $id, 'label' => $label];
        }

        $seen = array_flip(array_keys($common));

        foreach (array_merge([$current], $extras) as $tz) {
            if (! is_string($tz) || $tz === '') {
                continue;
            }
            $id = self::sanitizeTimezone($tz);
            if (isset($seen[$id])) {
                continue;
            }
            try {
                new \DateTimeZone($id);
            } catch (\Throwable $e) {
                continue;
            }
            $seen[$id] = true;
            $options[] = ['id' => $id, 'label' => self::shortLabel($id)];
        }

        return $options;
    }

    /** Short label for kanban cards (common name or city). */
    public static function shortLabel(?string $identifier): string
    {
        if (self::isUnset($identifier)) {
            return 'Not set';
        }

        $id = self::sanitizeTimezone($identifier);
        $common = self::commonTimezones();

        if (isset($common[$id])) {
            return $common[$id];
        }

        return self::label($id);
    }

    public static function identifiers(): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    public static function offsetLabel(string $identifier): string
    {
        try {
            $offset = (new \DateTimeImmutable('now', new \DateTimeZone($identifier)))->getOffset();
        } catch (\Throwable $e) {
            $offset = 0;
        }

        $sign = $offset < 0 ? '-' : '+';
        $abs = abs($offset);

        return sprintf('UTC%s%02d:%02d', $sign, intdiv($abs, 3600), intdiv($abs % 3600, 60));
    }

    public static function label(?string $identifier): string
    {
        if (! $identifier) {
            return 'UTC';
        }

        if ($identifier === 'UTC') {
            return 'UTC';
        }

        try {
            new \DateTimeZone($identifier);
        } catch (\Throwable $e) {
            return $identifier;
        }

        $segments = explode('/', $identifier);
        $city = str_replace('_', ' ', end($segments));

        return self::offsetLabel($identifier).' '.$city;
    }

    public static function allTimezones(): array
    {
        $rows = [];

        foreach (self::identifiers() as $id) {
            try {
                $offset = (new \DateTimeImmutable('now', new \DateTimeZone($id)))->getOffset();
            } catch (\Throwable $e) {
                $offset = 0;
            }

            $rows[] = ['id' => $id, 'label' => self::label($id), 'offset' => $offset];
        }

        usort($rows, fn ($a, $b) => [$a['offset'], $a['label']] <=> [$b['offset'], $b['label']]);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['id']] = $row['label'];
        }

        return $out;
    }
}
