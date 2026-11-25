<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\Request;
use App\Support\StudentInertiaProps;
use App\Support\StudentQuizAccess;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function __construct(private QuizService $quizService)
    {
    }
    public function index()
    {
        $userId = Auth::id();
        $quizIds = StudentQuizAccess::quizIdsFor($userId);

        $quizzes = Quiz::with(['course', 'attempts' => fn ($q) => $q->where('user_id', $userId)->latest()])
            ->whereIn('id', $quizIds)
            ->get();

        return \App\Support\InertiaPage::react('Student/Quizzes/Index', [
            'quizzes' => $quizzes->map(fn ($q) => StudentInertiaProps::quiz($q))->values()->all(),
        ]);
    }

    public function take(Quiz $quiz)
    {
        $this->ensureCanAccessQuiz($quiz);
        $this->ensureCanAttempt($quiz);

        return \App\Support\InertiaPage::react('Student/Quizzes/Take', [
            'quiz' => StudentInertiaProps::quiz($quiz, true),
        ]);
    }

    public function results(Quiz $quiz)
    {
        $this->ensureCanAccessQuiz($quiz);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->firstOrFail();

        $review = $this->quizService->review($quiz, $attempt);

        return \App\Support\InertiaPage::react('Student/Quizzes/Results', [
            'quiz' => StudentInertiaProps::quiz($quiz),
            'attempt' => StudentInertiaProps::quizAttempt($attempt),
            'review' => $review,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $this->ensureCanAccessQuiz($quiz);
        $this->ensureCanAttempt($quiz);

        try {
            $graded = $this->quizService->scoreSubmission($request, $quiz);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $answers = $graded['answers'];
        $score = $graded['score'];
        $passed = $graded['passed'];
        $earnedXp = $graded['earned_xp'];

        $hadPassedBefore = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->where('passed', true)
            ->exists();

        if (!$hadPassedBefore && $earnedXp > 0) {
            $user = Auth::user();
            $user->xp = ($user->xp ?? 0) + $earnedXp;
            $user->save();
        } else {
            $earnedXp = 0;
        }

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => Auth::id(),
            'answers' => $answers,
            'score' => $score,
            'passed' => $passed,
        ]);

        try {
            $this->sendSubmissionEmails($quiz, $score, $earnedXp);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send quiz emails: '.$e->getMessage());
        }

        $msg = "Quiz completed! You scored {$score}%.";
        if ($earnedXp > 0) {
            $msg .= " You earned {$earnedXp} XP!";
        } elseif (! $passed) {
            $msg .= ' Review your answers and retry to pass.';
        }

        try {
            if ($passed) {
                $result = app(\App\Services\LearningActivityService::class)->record(Auth::id(), $quiz->course_id);
                if (! empty($result['skill']['mastered']['title'])) {
                    $msg .= ' Skill unlocked: '.$result['skill']['mastered']['title'].'.';
                }
                if (! empty($result['skill']['milestones'][0]['badge'])) {
                    $msg .= ' Milestone: '.$result['skill']['milestones'][0]['badge'].'!';
                }
            } else {
                app(\App\Services\LearningStreakService::class)->recordActivity(Auth::id());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Quiz progress update failed: '.$e->getMessage());
        }

        return redirect()
            ->route('student.quizzes.results', $quiz)
            ->with('success', $msg);
    }

    private function sendSubmissionEmails(Quiz $quiz, $score, $earnedXp): void
    {
        $user = Auth::user();
        $emailService = new \App\Services\EmailService();

        $studentSubject = "Quiz Submitted: {$quiz->title}";
        $studentBody = "<p>Hi {$user->name},</p><p>You have successfully submitted the quiz <strong>{$quiz->title}</strong>.</p><p>Your score is <strong>{$score}%</strong>.</p>";
        if ($earnedXp > 0) {
            $studentBody .= "<p>You earned <strong>{$earnedXp} XP</strong>!</p>";
        }
        $emailService->sendCustomEmail($user->email, $studentSubject, $studentBody);

        $adminSubject = "Quiz Submission: {$user->name} - {$quiz->title}";
        $adminBody = "<p>Student <strong>{$user->name}</strong> ({$user->email}) has submitted the quiz <strong>{$quiz->title}</strong>.</p><p>Score: {$score}%</p>";
        $emailService->notifyAdmins($adminSubject, $adminBody);

        $tutorEmail = null;
        $tutorName = 'Tutor';
        if ($quiz->course_id) {
            $booking = \App\Models\Booking::with('tutor')->where('course_id', $quiz->course_id)->where('student_id', $user->id)->first();
            if ($booking && $booking->tutor) {
                $tutorEmail = $booking->tutor->email;
                $tutorName = $booking->tutor->name;
            } else {
                $quiz->loadMissing('course.tutor');
                if ($quiz->course && $quiz->course->tutor) {
                    $tutorEmail = $quiz->course->tutor->email;
                    $tutorName = $quiz->course->tutor->name;
                }
            }
        }

        if ($tutorEmail) {
            $tutorSubject = "Student Quiz Submission: {$user->name} - {$quiz->title}";
            $tutorBody = "<p>Hi {$tutorName},</p><p>Your student <strong>{$user->name}</strong> has submitted the quiz <strong>{$quiz->title}</strong>.</p><p>Score: {$score}%</p>";
            $emailService->sendCustomEmail($tutorEmail, $tutorSubject, $tutorBody);
        }
    }

    private function ensureCanAttempt(Quiz $quiz): void
    {
        $hasPassed = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->where('passed', true)
            ->exists();

        abort_if($hasPassed, 403, 'You have already passed this quiz.');
    }

    private function ensureCanAccessQuiz(Quiz $quiz): void
    {
        abort_unless(StudentQuizAccess::canAccess($quiz, (int) Auth::id()), 403, 'You do not have access to this quiz.');
    }
}
