<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Services\StudentApiService;
use App\Support\StudentQuizAccess;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    use RespondsWithJson;

    public function __construct(private StudentApiService $studentApi, private QuizService $quizService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $quizIds = StudentQuizAccess::quizIdsFor($userId);

        $quizzes = Quiz::with(['course', 'attempts' => fn ($q) => $q->where('user_id', $userId)->latest()])
            ->whereIn('id', $quizIds)
            ->get()
            ->map(fn ($q) => $this->studentApi->formatQuizSummary($q, $userId));

        return $this->success(['quizzes' => $quizzes]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $userId = $request->user()->id;
        $this->ensureCanAccessQuiz($quiz, $userId);

        $hasPassed = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->where('passed', true)
            ->exists();

        $payload = array_merge($this->studentApi->formatQuizSummary($quiz->load('course'), $userId), [
            'can_retry' => !$hasPassed,
            'questions' => $hasPassed ? [] : $this->normalizeQuestions($quiz->questions ?? []),
        ]);

        return $this->success(['quiz' => $payload]);
    }

    public function results(Request $request, Quiz $quiz)
    {
        $userId = $request->user()->id;
        $this->ensureCanAccessQuiz($quiz, $userId);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if (!$attempt) {
            return $this->error('No attempt found for this quiz.', 404);
        }

        return $this->success([
            'quiz' => $this->studentApi->formatQuizSummary($quiz->load('course'), $userId),
            'attempt' => [
                'score' => $attempt->score,
                'passed' => (bool) $attempt->passed,
                'created_at' => $attempt->created_at?->toIso8601String(),
            ],
            'review' => $this->quizService->review($quiz, $attempt, true),
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        $this->ensureCanAccessQuiz($quiz, $user->id);
        $this->ensureCanAttempt($quiz, $user->id);

        try {
            $graded = $this->quizService->scoreSubmission($request, $quiz);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $answers = $graded['answers'];
        $score = $graded['score'];
        $passed = $graded['passed'];
        $earnedXp = $graded['earned_xp'];
        $correctCount = $graded['correct_count'];
        $total = $graded['total'];

        $hadPassedBefore = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->exists();

        if (!$hadPassedBefore && $earnedXp > 0) {
            $user->xp = ($user->xp ?? 0) + $earnedXp;
            $user->save();
        } else {
            $earnedXp = 0; // Don't award twice if they already passed
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'answers' => $answers,
            'score' => $score,
            'passed' => $passed,
        ]);

        return $this->success([
            'attempt' => [
                'score' => $score,
                'passed' => $passed,
                'earned_xp' => $earnedXp,
                'correct_count' => $correctCount,
                'total' => $total,
            ],
            'review' => $this->quizService->review($quiz, $attempt, true),
            'quiz' => $this->studentApi->formatQuizSummary($quiz->fresh()->load('course'), $user->id),
        ], "Quiz completed! You scored {$score}%.");
    }

    private function normalizeQuestions(array $questions): array
    {
        $out = [];
        foreach (array_values($questions) as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            $options = [];
            foreach ($question['options'] ?? [] as $opt) {
                if (is_array($opt)) {
                    $options[] = ['text' => (string) ($opt['text'] ?? '')];
                } elseif ($opt !== null && $opt !== '') {
                    $options[] = ['text' => (string) $opt];
                }
            }

            $out[] = [
                'id' => (string) ($question['id'] ?? ('q'.$index)),
                'type' => in_array($question['type'] ?? 'mcq', ['short_answer', 'draw', 'image_upload'], true) ? $question['type'] : 'mcq',
                'question' => (string) ($question['question'] ?? ''),
                'xp' => (int) ($question['xp'] ?? 0),
                'image_url' => \App\Support\ApiAssetUrl::fromPath($question['image_url'] ?? null),
                'options' => $options,
            ];
        }

        return $out;
    }

    private function ensureCanAttempt(Quiz $quiz, int $userId): void
    {
        if (QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $userId)->where('passed', true)->exists()) {
            abort(response()->json([
                'success' => false,
                'message' => 'You have already passed this quiz.',
            ], 403));
        }
    }

    private function ensureCanAccessQuiz(Quiz $quiz, int $userId): void
    {
        if (! StudentQuizAccess::canAccess($quiz, $userId)) {
            abort(response()->json([
                'success' => false,
                'message' => 'You do not have access to this quiz.',
            ], 403));
        }
    }
}
