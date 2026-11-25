<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Support\StudentInertiaProps;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }

    public function index()
    {
        $assignments = Auth::user()->assignments()
            ->with(['course', 'submissions' => fn ($q) => $q->where('user_id', Auth::id())])
            ->orderBy('due_at')
            ->get();

        return \App\Support\InertiaPage::react('Student/Assignments/Index', [
            'assignments' => $assignments->map(fn ($a) => StudentInertiaProps::assignment($a))->values()->all(),
        ]);
    }

    public function take(Assignment $assignment)
    {
        $isAssigned = Auth::user()->assignments()->where('assignment_id', $assignment->id)->exists();
        abort_unless($isAssigned, 403, 'You are not assigned to this assignment.');
        $assignment->load([
            'course',
            'submissions' => fn ($q) => $q->where('user_id', Auth::id()),
        ]);

        return \App\Support\InertiaPage::react('Student/Assignments/Take', [
            'assignment' => StudentInertiaProps::assignment($assignment),
        ]);
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $isAssigned = Auth::user()->assignments()->where('assignment_id', $assignment->id)->exists();
        abort_unless($isAssigned, 403, 'You are not assigned to this assignment.');

        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($assignment->hasQuestions()) {
            try {
                $result = $this->assignmentService->processSubmissionAnswers($request, $assignment);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }

            $data = [
                'answers' => $result['answers'],
                'status' => 'submitted',
            ];

            if ($result['can_auto_grade'] && $result['auto_score'] !== null) {
                $data['grade'] = $result['auto_score'];
                $data['status'] = 'graded';
                $data['feedback'] = 'Auto-graded based on your answers.';
            }

            if (!empty($result['earned_xp']) && (!$existingSubmission || $existingSubmission->status !== 'graded')) {
                $user = Auth::user();
                $user->xp = ($user->xp ?? 0) + $result['earned_xp'];
                $user->save();
            }

            if ($request->hasFile('submission_file')) {
                $request->validate([
                    'submission_file' => 'file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:20480',
                ]);
                $data['file_path'] = $request->file('submission_file')->store('submissions', 'public');
            }

            AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => Auth::id()],
                $data
            );
        } else {
            $request->validate([
                'content' => 'nullable|string|max:5000',
                'submission_file' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:20480',
            ]);

            if (!$request->content && !$request->hasFile('submission_file')) {
                return back()->withErrors(['error' => 'Please provide text content or upload a file.']);
            }

            $data = ['content' => $request->content, 'status' => 'submitted'];

            if ($request->hasFile('submission_file')) {
                $data['file_path'] = $request->file('submission_file')->store('submissions', 'public');
            }

            $earnedXp = (int) ($assignment->xp_reward ?? 0);
            if ($earnedXp > 0 && !$existingSubmission) {
                $user = Auth::user();
                $user->xp = ($user->xp ?? 0) + $earnedXp;
                $user->save();
            }

            AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => Auth::id()],
                $data
            );
        }

        try {
            $this->sendSubmissionEmails($assignment);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send assignment emails: ' . $e->getMessage());
        }

        $result = app(\App\Services\LearningActivityService::class)->record(Auth::id(), $assignment->course_id);
        $msg = 'Assignment submitted successfully.';
        if (! empty($result['skill']['mastered']['title'])) {
            $msg .= ' Skill unlocked: '.$result['skill']['mastered']['title'].'.';
        }
        if (! empty($result['skill']['milestones'][0]['badge'])) {
            $msg .= ' Milestone: '.$result['skill']['milestones'][0]['badge'].'!';
        }

        if ($assignment->hasQuestions()) {
            return redirect()->route('student.assignments')->with('success', $msg);
        }

        return back()->with('success', $msg);
    }

    private function sendSubmissionEmails(Assignment $assignment): void
    {
        $user = Auth::user();
        $emailService = new \App\Services\EmailService();

        $studentSubject = "Assignment Submitted: {$assignment->title}";
        $studentBody = "<p>Hi {$user->name},</p><p>You have successfully submitted the assignment <strong>{$assignment->title}</strong>.</p>";
        $emailService->sendCustomEmail($user->email, $studentSubject, $studentBody);

        $adminSubject = "Assignment Submission: {$user->name} - {$assignment->title}";
        $adminBody = "<p>Student <strong>{$user->name}</strong> ({$user->email}) has submitted the assignment <strong>{$assignment->title}</strong>.</p>";
        $emailService->notifyAdmins($adminSubject, $adminBody);

        $tutorEmail = null;
        $tutorName = 'Tutor';
        if ($assignment->course_id) {
            $booking = \App\Models\Booking::with('tutor')->where('course_id', $assignment->course_id)->where('student_id', $user->id)->first();
            if ($booking && $booking->tutor) {
                $tutorEmail = $booking->tutor->email;
                $tutorName = $booking->tutor->name;
            } else {
                $assignment->loadMissing('course.tutor');
                if ($assignment->course && $assignment->course->tutor) {
                    $tutorEmail = $assignment->course->tutor->email;
                    $tutorName = $assignment->course->tutor->name;
                }
            }
        }

        if ($tutorEmail) {
            $tutorSubject = "Student Assignment Submission: {$user->name} - {$assignment->title}";
            $tutorBody = "<p>Hi {$tutorName},</p><p>Your student <strong>{$user->name}</strong> has submitted the assignment <strong>{$assignment->title}</strong>.</p>";
            $emailService->sendCustomEmail($tutorEmail, $tutorSubject, $tutorBody);
        }
    }
}
