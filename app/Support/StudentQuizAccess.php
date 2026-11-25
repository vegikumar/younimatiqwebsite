<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentQuizAccess
{
    public static function courseIdsFor(int $userId): Collection
    {
        $enrolled = Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $booked = Booking::where('student_id', $userId)->pluck('course_id');
        $paid = Payment::where('student_id', $userId)->whereNotNull('course_id')->pluck('course_id');

        return $enrolled->merge($booked)->merge($paid)->filter()->unique()->values();
    }

    public static function quizIdsFor(int $userId): Collection
    {
        $courseIds = static::courseIdsFor($userId);

        return QuizAssignment::query()
            ->where(function ($query) use ($userId) {
                $query->where('assignable_type', User::class)
                    ->where('assignable_id', $userId);
            })
            ->orWhere(function ($query) use ($courseIds) {
                $query->where('assignable_type', Course::class)
                    ->whereIn('assignable_id', $courseIds);
            })
            ->pluck('quiz_id')
            ->unique()
            ->values();
    }

    public static function canAccess(Quiz $quiz, int $userId): bool
    {
        return static::quizIdsFor($userId)->contains($quiz->id);
    }
}
