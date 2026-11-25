<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentStickingPoint;
use Illuminate\Support\Collection;

class CoPilotService
{
    /**
     * Get Co-Pilot pre-class prep data for a given course section.
     */
    public function getCoPilotPrep(int $courseId): Collection
    {
        $enrollments = Enrollment::where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->with('user')
            ->get();

        $studentIds = $enrollments->pluck('user_id');
        $stickingPoints = StudentStickingPoint::where('course_id', $courseId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function ($enrollment) use ($stickingPoints) {
            $student = $enrollment->user;
            $sp = $stickingPoints->get($student->id ?? 0);

            return [
                'student_id' => $student->id ?? 0,
                'name' => $student->name ?? 'Student',
                'avatar' => $student->avatar ?? null,
                'last_sticking_point' => $sp->last_sticking_point ?? 'Mastering basic loops syntax',
                'parent_notes' => $sp->parent_notes ?? 'Responds best to gamified visual challenges.',
                'tutor_prep_notes' => $sp->tutor_prep_notes ?? 'Walk in prepped with step-by-step logic hints.',
            ];
        });
    }
}
