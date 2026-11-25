<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\Request;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::where('creator_id', Auth::guard('tutor')->id())->with('assignments.assignable')->get();
        return \App\Support\InertiaPage::react('Tutor/Quizzes/Index', [
            'quizzes' => $quizzes->map(fn ($q) => TutorInertiaProps::quiz($q))->values()->all(),
        ]);
    }

    public function create()
    {
        abort(403, 'Only admins can create and assign quizzes.');
    }

    public function store(Request $request, QuizService $quizService)
    {
        abort(403, 'Only admins can create and assign quizzes.');
    }

    public function edit(Quiz $quiz)
    {
        abort_unless((int) $quiz->creator_id === (int) Auth::guard('tutor')->id(), 403);

        $tutorId = Auth::guard('tutor')->id();
        $courses = $this->tutorCourses($tutorId);
        $students = $this->tutorStudents($tutorId);
        $assignedCourseId = $quiz->assignments()->where('assignable_type', \App\Models\Course::class)->value('assignable_id');
        $assignedStudentIds = $quiz->assignments()->where('assignable_type', \App\Models\User::class)->pluck('assignable_id')->toArray();

        return \App\Support\InertiaPage::react('Tutor/Quizzes/Form', [
            'mode' => 'edit',
            'quiz' => TutorInertiaProps::quiz($quiz),
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::courseOption($c))->values()->all(),
            'students' => $students->map(fn ($s) => TutorInertiaProps::studentOption($s))->values()->all(),
            'assignedCourseId' => $assignedCourseId,
            'assignedStudentIds' => $assignedStudentIds,
        ]);
    }

    public function update(Request $request, Quiz $quiz, QuizService $quizService)
    {
        abort_unless((int) $quiz->creator_id === (int) Auth::guard('tutor')->id(), 403);

        try {
            $quizService->updateQuiz($quiz, $request);

            return redirect()->route('tutor.quizzes.index')->with('success', 'Quiz updated. Assignment to students is managed by admin only.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update quiz: '.$e->getMessage())->withInput();
        }
    }

    private function tutorCourses(int $tutorId)
    {
        $fromCourses = Course::where('tutor_id', $tutorId)->pluck('id');
        $fromBookings = \App\Models\Booking::where('tutor_id', $tutorId)->pluck('course_id');

        return Course::whereIn('id', $fromCourses->concat($fromBookings)->unique())->orderBy('title')->get();
    }

    private function tutorStudents(int $tutorId)
    {
        $courseIdsFromCourses = Course::where('tutor_id', $tutorId)->pluck('id');
        $studentIdsFromGroup = \App\Models\Enrollment::whereIn('course_id', $courseIdsFromCourses)->pluck('user_id');
        $studentIdsFromBookings = \App\Models\Booking::where('tutor_id', $tutorId)->pluck('student_id');
        $studentIds = $studentIdsFromGroup->concat($studentIdsFromBookings)->filter()->unique();

        return \App\Models\User::where('role', 'student')
            ->whereIn('id', $studentIds)
            ->with('enrollments')
            ->orderBy('name')
            ->get();
    }
}
