<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Quiz;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $enrollments = Enrollment::with('course:id,title,slug')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->latest()
            ->get();

        $courseIds = $enrollments->pluck('course_id')->all();

        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->latest()
            ->take(40)
            ->get(['id', 'course_id', 'title', 'due_at']);

        $quizzes = Quiz::whereIn('course_id', $courseIds)
            ->latest()
            ->take(40)
            ->get(['id', 'course_id', 'title']);

        $courseTitle = $enrollments->mapWithKeys(
            fn ($e) => [$e->course_id => $e->course?->title ?? 'Course']
        );

        return $this->success([
            'enrollments' => $enrollments->map(fn ($e) => [
                'id' => $e->id,
                'course_id' => $e->course_id,
                'course_title' => $e->course?->title,
                'progress' => (float) ($e->progress ?? 0),
            ])->values()->all(),
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'course_id' => $a->course_id,
                'course_title' => $courseTitle[$a->course_id] ?? 'Course',
                'due_at' => $a->due_at?->toIso8601String(),
                'due_at_label' => optional($a->due_at)?->format('M j, Y'),
            ])->values()->all(),
            'quizzes' => $quizzes->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'course_id' => $q->course_id,
                'course_title' => $courseTitle[$q->course_id] ?? 'Course',
            ])->values()->all(),
        ]);
    }
}
