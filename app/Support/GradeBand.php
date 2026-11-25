<?php

namespace App\Support;

class GradeBand
{
    public const EARLY = 'early';     // 1–3

    public const MIDDLE = 'middle';   // 4–6

    public const JUNIOR = 'junior';   // 7–9

    public const SENIOR = 'senior';   // 10–12

    public static function fromGrade(?int $grade): string
    {
        if ($grade === null || $grade < 1) {
            return self::MIDDLE;
        }
        if ($grade <= 3) {
            return self::EARLY;
        }
        if ($grade <= 6) {
            return self::MIDDLE;
        }
        if ($grade <= 9) {
            return self::JUNIOR;
        }

        return self::SENIOR;
    }

    public static function label(string $band): string
    {
        return match ($band) {
            self::EARLY => 'Grades 1–3',
            self::MIDDLE => 'Grades 4–6',
            self::JUNIOR => 'Grades 7–9',
            self::SENIOR => 'Grades 10–12',
            default => 'Learner',
        };
    }
}
