<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::with('assignments.assignable', 'creator')->get();
        return \App\Support\InertiaPage::react('Admin/Quizzes/Index', [
            'quizzes' => $quizzes->map(fn ($q) => \App\Support\AdminInertiaProps::adminQuiz($q))->values()->all(),
        ]);
    }

    public function create()
    {
        $courses = Course::all();
        $students = \App\Models\User::where('role', 'student')->with('enrollments')->orderBy('name')->get();
        $courseLinks = $this->studentCourseLinks();

        return \App\Support\InertiaPage::react('Admin/Quizzes/Create', [
            'courses' => $courses->map(fn ($c) => \App\Support\TutorInertiaProps::courseOption($c))->values()->all(),
            'students' => $students->map(fn ($s) => $this->studentAssignOption($s, $courseLinks))->values()->all(),
        ]);
    }

    public function store(Request $request, QuizService $quizService)
    {
        try {
            $quiz = $quizService->storeQuiz($request, Auth::id());
            
            // Handle Assignment
            $quizService->assignQuiz($quiz, $request->course_id, $request->student_ids);
            
            try {
                app(\App\Services\EmailService::class)->notifyQuizAssigned(
                    $quiz,
                    $request->course_id,
                    is_array($request->student_ids) ? $request->student_ids : []
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send quiz assignment emails: ' . $e->getMessage());
            }

            return redirect()->route('admin.quizzes.index')->with('success', 'Quiz created and assigned successfully. Notes sent to students and parents.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create quiz: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Quiz $quiz)
    {
        $courses = Course::all();
        $students = \App\Models\User::where('role', 'student')->with('enrollments')->orderBy('name')->get();
        $courseLinks = $this->studentCourseLinks();
        $assignedCourseId = $quiz->assignments()->where('assignable_type', \App\Models\Course::class)->value('assignable_id');
        $assignedStudentIds = $quiz->assignments()->where('assignable_type', \App\Models\User::class)->pluck('assignable_id')->toArray();

        return \App\Support\InertiaPage::react('Admin/Quizzes/Edit', [
            'quiz' => \App\Support\AdminInertiaProps::adminQuiz($quiz),
            'courses' => $courses->map(fn ($c) => \App\Support\TutorInertiaProps::courseOption($c))->values()->all(),
            'students' => $students->map(fn ($s) => $this->studentAssignOption($s, $courseLinks))->values()->all(),
            'assignedCourseId' => $assignedCourseId,
            'assignedStudentIds' => $assignedStudentIds,
        ]);
    }

    public function update(Request $request, Quiz $quiz, QuizService $quizService)
    {
        try {
            $quizService->updateQuiz($quiz, $request);
            
            // Handle Assignment
            $quizService->assignQuiz($quiz, $request->course_id, $request->student_ids);

            try {
                app(\App\Services\EmailService::class)->notifyQuizAssigned(
                    $quiz,
                    $request->course_id,
                    is_array($request->student_ids) ? $request->student_ids : []
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send quiz assignment emails on update: '.$e->getMessage());
            }

            return redirect()->route('admin.quizzes.index')->with('success', 'Quiz updated and assigned successfully. Notes sent to students and parents.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update quiz: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Course ids a student is tied to via enrollment, booking, or payment.
     *
     * @return array<int, array<int, int>>
     */
    private function studentCourseLinks(): array
    {
        $links = [];
        $add = function ($studentId, $courseId) use (&$links) {
            if (!$studentId || !$courseId) {
                return;
            }
            $links[(int) $studentId][(int) $courseId] = (int) $courseId;
        };

        foreach (\App\Models\Enrollment::query()->get(['user_id', 'course_id']) as $row) {
            $add($row->user_id, $row->course_id);
        }
        foreach (\App\Models\Booking::query()->get(['student_id', 'course_id']) as $row) {
            $add($row->student_id, $row->course_id);
        }
        foreach (\App\Models\Payment::query()->get(['student_id', 'course_id']) as $row) {
            $add($row->student_id, $row->course_id);
        }

        return $links;
    }

    private function studentAssignOption(\App\Models\User $student, array $courseLinks): array
    {
        $ids = array_values($courseLinks[$student->id] ?? []);

        return array_merge(\App\Support\TutorInertiaProps::studentOption($student), [
            'email' => $student->email,
            'enrolledCourseIds' => $ids,
        ]);
    }

}
