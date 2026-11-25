<?php

namespace App\Support;

class DisplayName
{
    /**
     * Privacy-safe public display: "Alex J." — never full last name on leaderboards.
     */
    public static function public(?string $fullName): string
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return 'Learner';
        }
        $parts = preg_split('/\s+/', $fullName) ?: [];
        $first = $parts[0] ?? 'Learner';
        if (count($parts) < 2) {
            return $first;
        }
        $lastInitial = mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1));

        return $first.' '.$lastInitial.'.';
    }
}
