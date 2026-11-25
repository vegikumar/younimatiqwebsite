<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParentalAiGate
{
    /**
     * Whether AI-assisted parent emails/summaries are allowed for this student.
     * Defaults to true when no parent link exists.
     */
    public function allowsForStudent(int $studentId): bool
    {
        $rows = DB::table('parent_student')->where('student_id', $studentId)->get();
        if ($rows->isEmpty()) {
            return true;
        }

        // All linked parents must allow (restrictive when any denies)
        return $rows->every(fn ($row) => (bool) ($row->ai_assist_allowed ?? true));
    }

    /**
     * Parents who should receive AI session summaries for a set of students.
     *
     * @param  array<int>  $studentIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function parentsAllowedForStudents(array $studentIds)
    {
        if (empty($studentIds)) {
            return collect();
        }

        $allowedParentIds = DB::table('parent_student')
            ->whereIn('student_id', $studentIds)
            ->where(function ($q) {
                $q->whereNull('ai_assist_allowed')->orWhere('ai_assist_allowed', true);
            })
            ->pluck('parent_id')
            ->unique()
            ->all();

        if (empty($allowedParentIds)) {
            return collect();
        }

        return User::whereIn('id', $allowedParentIds)->where('role', 'parent')->get();
    }
}
