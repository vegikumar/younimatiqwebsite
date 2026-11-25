<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\ApiAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuizService
{
    public function __construct(private ImageUploadService $images) {}

    public function storeQuiz(Request $request, $creatorId)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:1|max:100',
            'xp_reward' => 'required|integer|min:0',
            'questions_json' => 'required|string',
            'questions' => 'nullable|array', // Handles file uploads dynamically from the frontend builder
        ]);

        $questions = json_decode($data['questions_json'], true);

        if (! is_array($questions)) {
            throw new \Exception('Invalid JSON format for questions.');
        }

        // Handle image uploads
        if ($request->has('questions') && is_array($request->questions)) {
            foreach ($request->questions as $index => $qData) {
                if (isset($qData['remove_image']) && $qData['remove_image'] == '1') {
                    $questions[$index]['image_url'] = null;
                }

                if ($request->hasFile("questions.{$index}.image_file")) {
                    $path = $this->images->storePath($request->file("questions.{$index}.image_file"), 'quizzes/questions', 'public');
                    $questions[$index]['image_url'] = '/secure-asset/'.$path;
                }
            }
        }

        $quiz = Quiz::create([
            'creator_id' => $creatorId,
            'title' => $data['title'],
            'description' => $data['description'],
            'passing_score' => $data['passing_score'],
            'xp_reward' => $data['xp_reward'],
            'questions' => $questions,
        ]);

        return $quiz;
    }

    public function updateQuiz(Quiz $quiz, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:1|max:100',
            'xp_reward' => 'required|integer|min:0',
            'questions_json' => 'required|string',
            'questions' => 'nullable|array',
        ]);

        $questions = json_decode($data['questions_json'], true);

        if (! is_array($questions)) {
            throw new \Exception('Invalid JSON format for questions.');
        }

        // Handle image uploads
        if ($request->has('questions') && is_array($request->questions)) {
            foreach ($request->questions as $index => $qData) {
                if (isset($qData['remove_image']) && $qData['remove_image'] == '1') {
                    $questions[$index]['image_url'] = null;
                }

                if ($request->hasFile("questions.{$index}.image_file")) {
                    $path = $this->images->storePath($request->file("questions.{$index}.image_file"), 'quizzes/questions', 'public');
                    $questions[$index]['image_url'] = '/secure-asset/'.$path;
                }
            }
        }

        $quiz->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'passing_score' => $data['passing_score'],
            'xp_reward' => $data['xp_reward'],
            'questions' => $questions,
        ]);

        return $quiz;
    }

    public function assignQuiz(Quiz $quiz, $courseId, $userIds = [])
    {
        // First delete existing assignments
        $quiz->assignments()->delete();

        if ($courseId) {
            QuizAssignment::create([
                'quiz_id' => $quiz->id,
                'assignable_type' => Course::class,
                'assignable_id' => $courseId,
            ]);
        }

        if (! empty($userIds) && is_array($userIds)) {
            foreach ($userIds as $userId) {
                QuizAssignment::create([
                    'quiz_id' => $quiz->id,
                    'assignable_type' => User::class,
                    'assignable_id' => $userId,
                ]);
            }
        }
    }

    /**
     * @return array{answers: array, score: int, passed: bool, earned_xp: int, correct_count: int, total: int}
     */
    public function scoreSubmission(Request $request, Quiz $quiz): array
    {
        $questions = is_array($quiz->questions) ? $quiz->questions : [];
        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            $answers = [];
        }

        $stored = [];
        $correctCount = 0;
        $gradedTotal = 0;
        $earnedXp = 0;

        foreach ($questions as $question) {
            if (! is_array($question)) {
                continue;
            }
            $qId = $question['id'] ?? null;
            if (! $qId) {
                continue;
            }

            $type = $question['type'] ?? 'mcq';

            if ($type === 'draw') {
                $drawing = $request->input("answer_drawings.{$qId}");
                if (! is_string($drawing) || ! str_starts_with($drawing, 'data:image')) {
                    throw new \InvalidArgumentException('Please complete the drawing for every draw question.');
                }
                $stored[$qId] = [
                    'type' => 'draw',
                    'image_path' => $this->storeBase64Image($drawing, 'quizzes/submissions/drawings'),
                ];
                $earnedXp += (int) ($question['xp'] ?? 0);
                continue;
            }

            if ($type === 'image_upload') {
                $file = $request->file("answer_files.{$qId}");
                $extension = strtolower((string) $file?->getClientOriginalExtension());
                if (! $file || ! $file->isValid() || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    throw new \InvalidArgumentException('Please upload an image for every image question.');
                }
                $stored[$qId] = [
                    'type' => 'image_upload',
                    'image_path' => $file->store('quizzes/submissions/uploads', 'public'),
                ];
                $earnedXp += (int) ($question['xp'] ?? 0);
                continue;
            }

            $gradedTotal++;
            $studentAnswer = $answers[$qId] ?? null;
            $stored[$qId] = $studentAnswer;
            $isCorrect = false;

            if ($type === 'short_answer') {
                $isCorrect = strcasecmp(trim((string) $studentAnswer), trim((string) ($question['correctText'] ?? ''))) === 0;
            } else {
                $isCorrect = (string) $studentAnswer === (string) ($question['correctOption'] ?? '');
            }

            if ($isCorrect) {
                $correctCount++;
                $earnedXp += (int) ($question['xp'] ?? 0);
            }
        }

        $total = count(array_filter($questions, fn ($question) => is_array($question) && ! empty($question['id'])));
        if ($gradedTotal > 0) {
            $score = (int) round(($correctCount / $gradedTotal) * 100);
        } else {
            $score = $total > 0 ? 100 : 0;
        }

        return [
            'answers' => $stored,
            'score' => $score,
            'passed' => $score >= (int) ($quiz->passing_score ?? 70),
            'earned_xp' => $earnedXp,
            'correct_count' => $correctCount,
            'total' => $gradedTotal > 0 ? $gradedTotal : $total,
        ];
    }

    public function review(Quiz $quiz, QuizAttempt $attempt, bool $forApi = false): array
    {
        $answers = $attempt->answers ?? [];
        $review = [];

        foreach ($quiz->questions ?? [] as $index => $question) {
            if (! is_array($question)) {
                continue;
            }
            $qId = $question['id'] ?? null;
            if (! $qId) {
                continue;
            }

            $type = $question['type'] ?? 'mcq';
            $studentAnswer = $answers[$qId] ?? null;
            $isCorrect = false;
            $isManual = in_array($type, ['draw', 'image_upload'], true);
            $studentDisplay = '—';
            $correctDisplay = '—';
            $answerImageUrl = null;

            if ($type === 'draw' || $type === 'image_upload') {
                $path = is_array($studentAnswer) ? ($studentAnswer['image_path'] ?? null) : null;
                $studentDisplay = $path ? ($type === 'draw' ? 'Drawing submitted' : 'Image submitted') : 'No answer';
                $correctDisplay = '';
                $answerImageUrl = $path ? $this->answerImageUrl($path, $forApi) : null;
            } elseif ($type === 'short_answer') {
                $studentDisplay = trim((string) ($studentAnswer ?? '')) !== '' ? (string) $studentAnswer : 'No answer';
                $correctDisplay = $question['correctText'] ?? '—';
                $isCorrect = strcasecmp(trim((string) $studentAnswer), trim((string) ($question['correctText'] ?? ''))) === 0;
            } else {
                $correctIndex = $question['correctOption'] ?? null;
                $options = $question['options'] ?? [];
                $studentIndex = is_numeric($studentAnswer) ? (int) $studentAnswer : null;
                $studentDisplay = $studentIndex !== null
                    ? ($options[$studentIndex]['text'] ?? 'Option '.($studentIndex + 1))
                    : 'No answer';
                $correctDisplay = $correctIndex !== null && $correctIndex !== ''
                    ? ($options[(int) $correctIndex]['text'] ?? 'Option '.(((int) $correctIndex) + 1))
                    : '—';
                $isCorrect = (string) $studentAnswer === (string) $correctIndex;
            }

            $imageUrl = $question['image_url'] ?? null;
            $review[] = [
                'number' => $index + 1,
                'question' => $question['question'] ?? 'Question',
                'image_url' => $forApi ? ApiAssetUrl::fromPath($imageUrl) : $imageUrl,
                'type' => $type,
                'student_answer' => $studentDisplay,
                'correct_answer' => $correctDisplay,
                'is_correct' => $isManual ? true : $isCorrect,
                'is_manual' => $isManual,
                'answer_image_url' => $answerImageUrl,
            ];
        }

        return $review;
    }

    private function answerImageUrl(string $path, bool $forApi): string
    {
        $url = route('secure.asset', ['path' => $path]);

        return $forApi ? (ApiAssetUrl::fromPath($url) ?? $url) : $url;
    }

    private function storeBase64Image(string $dataUrl, string $directory): string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            throw new \InvalidArgumentException('Invalid drawing image data.');
        }

        $extension = strtolower($matches[1]);
        if (! in_array($extension, ['png', 'jpeg', 'jpg', 'webp'], true)) {
            $extension = 'png';
        }

        $imageData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($imageData === false || strlen($imageData) > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('Could not save that drawing. Try a smaller one.');
        }

        $filename = $directory.'/'.uniqid('draw_', true).'.'.$extension;
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }
}
