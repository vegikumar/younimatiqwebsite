<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Services\AssignmentService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();
        
        // Find courses where tutor is the primary tutor OR assigned via 1:1 bookings
        $courseIdsFromCourses = Course::where('tutor_id', $tutorId)->pluck('id');
        $courseIdsFromBookings = \App\Models\Booking::where('tutor_id', $tutorId)->pluck('course_id');
        $courseIds = $courseIdsFromCourses->concat($courseIdsFromBookings)->unique();
        
        $courses = Course::whereIn('id', $courseIds)->orderBy('title')->get();

        // Get students: all from their primary courses, and specific ones from 1:1 bookings
        $studentIdsFromGroupCourses = \App\Models\Enrollment::whereIn('course_id', $courseIdsFromCourses)->pluck('user_id');
        $studentIdsFromBookings = \App\Models\Booking::where('tutor_id', $tutorId)->pluck('student_id');
        $studentIds = $studentIdsFromGroupCourses->concat($studentIdsFromBookings)->unique();
        
        $students = \App\Models\User::whereIn('id', $studentIds)->orderBy('name')->get();

        $assignments = Assignment::with(['course', 'submissions.user', 'students'])
            ->where(function ($query) use ($courseIds, $studentIds) {
                $query->whereIn('course_id', $courseIds)
                    ->orWhereHas('students', fn ($q) => $q->whereIn('users.id', $studentIds));
            })
            ->latest()
            ->get();
        $enrollments = \App\Models\Enrollment::whereIn('course_id', $courseIds)->get();
        
        return \App\Support\InertiaPage::react('Tutor/Assignments', [
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::courseOption($c))->values()->all(),
            'students' => $students->map(fn ($s) => TutorInertiaProps::studentOption($s->loadMissing('enrollments')))->values()->all(),
            'assignments' => $assignments->map(fn ($a) => TutorInertiaProps::tutorAssignment($a))->values()->all(),
            'enrollments' => $enrollments->map(fn ($e) => ['userId' => $e->user_id, 'courseId' => $e->course_id])->values()->all(),
            'storeUrl' => route('tutor.assignments.store'),
        ]);
    }

    public function store(Request $request)
    {
        abort(403, 'Only admins can create and assign post-class activities.');
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
            \Illuminate\Support\Facades\Log::error('Failed to send assignment grading emails (Tutor): ' . $e->getMessage());
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
        $studentBody = "<p>Hi {$student->name},</p><p>Your assignment <strong>{$assignment->title}</strong> has been graded by your tutor.</p><p><strong>Grade:</strong> {$submission->grade}%</p><p><strong>Feedback:</strong><br/>" . nl2br(e($submission->feedback)) . "</p>";
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
