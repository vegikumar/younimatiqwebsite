<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }
    public function index()
    {
        $courses = Course::orderBy('title')->get();
        $students = \App\Models\User::where('role', 'student')->orderBy('name')->get();
        $assignments = Assignment::with(['course', 'submissions.user', 'students'])->latest()->get();
        $enrollments = \App\Models\Enrollment::all();
        
        return \App\Support\InertiaPage::react('Admin/Assignments/Index', [
            'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values()->all(),
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'courseIds' => $enrollments->where('user_id', $s->id)->pluck('course_id')->values()->all(),
            ])->values()->all(),
            'assignments' => $assignments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'courseTitle' => $a->course?->title,
                    'createdAtLabel' => $a->created_at?->format('M d, Y'),
                    'fileUrl' => $a->file_path ? route('secure.asset', ['path' => $a->file_path]) : null,
                    'questionCount' => is_array($a->questions) ? count($a->questions) : 0,
                    'hasQuestions' => $a->hasQuestions(),
                    'students' => $a->students->map(fn ($st) => $st->name)->values()->all(),
                    'submissions' => $a->submissions->map(fn ($s) => [
                        'id' => $s->id,
                        'studentName' => $s->user?->name ?? 'Student',
                        'createdAtLabel' => $s->created_at?->format('M d, Y g:i A'),
                        'grade' => $s->grade,
                        'feedback' => $s->feedback,
                        'status' => $s->status,
                        'answersSummary' => $this->readableAnswers($s),
                        'gradeUrl' => route('admin.assignments.grade', $s),
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'assignment_file' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:10240',
            'xp_reward' => 'nullable|integer|min:0',
            'questions_json' => 'nullable|string',
            'questions' => 'nullable|array',
        ]);

        $decodedQuestions = json_decode($data['questions_json'] ?? '[]', true);
        if (! is_array($decodedQuestions)) {
            $decodedQuestions = [];
        }
        $filledQuestions = array_values(array_filter(
            $decodedQuestions,
            fn ($q) => is_array($q) && trim((string) ($q['question'] ?? '')) !== ''
        ));
        // PDF/worksheet-only activities are allowed (questions can live in Quizzes instead).
        if (count($filledQuestions) < 1 && ! $request->hasFile('assignment_file')) {
            return back()->withErrors([
                'assignment_file' => 'Upload a PDF (or file), or add at least one question. For question-only work, use Quizzes.',
            ])->withInput();
        }

        $assignmentData = [
            'course_id' => $data['course_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'xp_reward' => $data['xp_reward'] ?? 0,
        ];

        if ($request->hasFile('assignment_file')) {
            $assignmentData['file_path'] = $request->file('assignment_file')->store('assignments', 'public');
        }

        $assignment = $this->assignmentService->storeAssignment($request, $assignmentData);
        $assignment->students()->attach($data['student_ids']);

        try {
            app(\App\Services\EmailService::class)->notifyAssignmentAssigned($assignment, $data['student_ids']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send assignment assigned emails: ' . $e->getMessage());
        }

        return back()->with('success', 'Assignment created and assigned to students. Notes sent to students and parents.');
    }

    private function readableAnswers(AssignmentSubmission $submission): string
    {
        $lines = [];
        if (filled($submission->content)) {
            $lines[] = $submission->content;
        }
        if ($submission->file_path) {
            $lines[] = 'Uploaded file: '.route('secure.asset', ['path' => $submission->file_path]);
        }
        foreach ($submission->answers ?? [] as $answer) {
            if (! is_array($answer)) {
                $lines[] = (string) $answer;
                continue;
            }
            $type = $answer['type'] ?? '';
            if ($type === 'short_answer') {
                $lines[] = (string) ($answer['value'] ?? '');
            } elseif ($type === 'mcq') {
                $lines[] = 'Selected choice '.(((int) ($answer['value'] ?? 0)) + 1);
            } elseif (! empty($answer['image_path'])) {
                $lines[] = 'Answer file: '.route('secure.asset', ['path' => $answer['image_path']]);
            }
        }

        return implode("\n", array_filter($lines, fn ($line) => $line !== ''));
    }

    public function grade(Request $request, AssignmentSubmission $submission)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'status' => 'graded',
        ]);

        try {
            $this->sendGradingEmails($submission);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send assignment grading emails: ' . $e->getMessage());
        }

        return back()->with('success', 'Submission graded.');
    }

    private function sendGradingEmails(AssignmentSubmission $submission): void
    {
        $submission->loadMissing(['user', 'assignment.course.tutor']);
        $student = $submission->user;
        $assignment = $submission->assignment;
        $emailService = new \App\Services\EmailService();

        $studentSubject = "Assignment Graded: {$assignment->title}";
        $studentBody = "<p>Hi {$student->name},</p><p>Your assignment <strong>{$assignment->title}</strong> has been graded.</p><p><strong>Grade:</strong> {$submission->grade}%</p><p><strong>Feedback:</strong><br/>" . nl2br(e($submission->feedback)) . "</p>";
        $emailService->sendCustomEmail($student->email, $studentSubject, $studentBody);

        $adminSubject = "Assignment Graded: {$student->name} - {$assignment->title}";
        $adminBody = "<p>Student <strong>{$student->name}</strong> ({$student->email})'s assignment <strong>{$assignment->title}</strong> was graded.</p><p><strong>Grade:</strong> {$submission->grade}%</p>";
        $emailService->notifyAdmins($adminSubject, $adminBody);

        $tutorEmail = null;
        $tutorName = 'Tutor';
        if ($assignment->course_id) {
            $booking = \App\Models\Booking::with('tutor')->where('course_id', $assignment->course_id)->where('student_id', $student->id)->first();
            if ($booking && $booking->tutor) {
                $tutorEmail = $booking->tutor->email;
                $tutorName = $booking->tutor->name;
            } else {
                if ($assignment->course && $assignment->course->tutor) {
                    $tutorEmail = $assignment->course->tutor->email;
                    $tutorName = $assignment->course->tutor->name;
                }
            }
        }

        if ($tutorEmail) {
            $tutorSubject = "Student Assignment Graded: {$student->name} - {$assignment->title}";
            $tutorBody = "<p>Hi {$tutorName},</p><p>Your student <strong>{$student->name}</strong>'s assignment <strong>{$assignment->title}</strong> has been graded.</p><p><strong>Grade:</strong> {$submission->grade}%</p><p><strong>Feedback:</strong><br/>" . nl2br(e($submission->feedback)) . "</p>";
            $emailService->sendCustomEmail($tutorEmail, $tutorSubject, $tutorBody);
        }
    }
}
