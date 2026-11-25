<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassroomGameAssignment;
use App\Models\ClassroomGameReward;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Support\ClassroomGames;
use App\Support\StudentQuizAccess;
use Illuminate\Support\Facades\Auth;

class ActivitiesController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
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

        $quizzes = Quiz::query()
            ->whereIn('id', StudentQuizAccess::quizIdsFor($userId))
            ->latest()
            ->take(40)
            ->get(['id', 'course_id', 'title']);

        $courseTitle = $enrollments->mapWithKeys(fn ($e) => [$e->course_id => $e->course?->title ?? 'Course']);

        $rewards = ClassroomGameReward::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('game_key');

        // Only games explicitly assigned to this student (not every published activity).
        $assignedKeys = ClassroomGameAssignment::query()
            ->where('user_id', $userId)
            ->latest()
            ->pluck('game_key')
            ->unique()
            ->values()
            ->all();

        $games = collect($assignedKeys)
            ->map(function ($gameKey) use ($rewards) {
                $meta = ClassroomGames::resolve($gameKey, activeOnly: false);
                if (! $meta) {
                    return null;
                }

                $reward = $rewards->get($meta['key']);

                return [
                    'key' => $meta['key'],
                    'title' => $meta['title'],
                    'summary' => $meta['summary'],
                    'url' => route('student.games.show', $meta['key']),
                    'xp_max' => ClassroomGames::maxPoints($meta['key']),
                    'xp_awarded' => $reward !== null,
                    'xp_points' => (int) ($reward->points ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return \App\Support\InertiaPage::react('Student/Activities', [
            'games' => $games,
            'enrollments' => $enrollments->map(fn ($e) => [
                'id' => $e->id,
                'course_id' => $e->course_id,
                'course_title' => $e->course?->title,
                'progress' => (float) ($e->progress ?? 0),
                'player_url' => route('student.courses.player', $e->course_id),
                'skill_tree_url' => route('student.courses.skill-tree', $e->course_id),
            ])->values()->all(),
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'course_title' => $courseTitle[$a->course_id] ?? 'Course',
                'due_at_label' => optional($a->due_at)?->format('M j, Y'),
                'url' => route('student.assignments.take', $a->id),
            ])->values()->all(),
            'quizzes' => $quizzes->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'course_title' => $courseTitle[$q->course_id] ?? 'Course',
                'url' => route('student.quizzes.take', $q->id),
            ])->values()->all(),
        ]);
    }
}
