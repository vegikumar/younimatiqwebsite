<?php

namespace App\Services;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentService
{
    public function __construct(private ImageUploadService $images) {}

    public function processQuestionsFromRequest(Request $request, ?array $existingQuestions = null): array
    {
        $data = $request->validate([
            'questions_json' => 'nullable|string',
            'questions' => 'nullable|array',
        ]);

        if (empty($data['questions_json'])) {
            return [];
        }

        $questions = json_decode($data['questions_json'], true);

        if (! is_array($questions)) {
            throw new \InvalidArgumentException('Invalid JSON format for questions.');
        }

        if ($request->has('questions') && is_array($request->questions)) {
            foreach ($request->questions as $index => $qData) {
                if (isset($qData['remove_image']) && $qData['remove_image'] == '1') {
                    $questions[$index]['image_url'] = null;
                }

                if ($request->hasFile("questions.{$index}.image_file")) {
                    $path = $this->images->storePath($request->file("questions.{$index}.image_file"), 'assignments/questions', 'public');
                    $questions[$index]['image_url'] = route('secure.asset', ['path' => $path]);
                } elseif ($existingQuestions && isset($existingQuestions[$index]['image_url'])) {
                    $questions[$index]['image_url'] = $existingQuestions[$index]['image_url'];
                }
            }
        }

        $questions = array_values(array_filter(array_map(function ($q) {
            unset($q['imagePreview'], $q['removeImage']);

            return $q;
        }, $questions), fn ($q) => trim((string) ($q['question'] ?? '')) !== ''));

        return $questions;
    }

    public function storeAssignment(Request $request, array $baseData): Assignment
    {
        $questions = $this->processQuestionsFromRequest($request);

        return Assignment::create(array_merge($baseData, [
            'questions' => $questions,
        ]));
    }

    public function updateAssignment(Assignment $assignment, Request $request, array $baseData): Assignment
    {
        $questions = $this->processQuestionsFromRequest($request, $assignment->questions ?? []);

        $assignment->update(array_merge($baseData, [
            'questions' => $questions,
        ]));

        return $assignment;
    }

    /**
     * @return array{answers: array, auto_score: float|null, can_auto_grade: bool}
     */
    public function processSubmissionAnswers(Request $request, Assignment $assignment): array
    {
        $questions = $assignment->questions ?? [];
        $answers = [];
        $autoGradable = 0;
        $autoCorrect = 0;
        $earnedXp = 0;

        foreach ($questions as $q) {
            $qId = $q['id'] ?? null;
            if (! $qId) {
                continue;
            }

            $type = $q['type'] ?? 'short_answer';

            if ($type === 'mcq') {
                $value = $request->input("answers.{$qId}");
                if ($value === null || $value === '') {
                    throw new \InvalidArgumentException('Please answer all questions before submitting.');
                }
                $selected = (int) $value;
                $entry = [
                    'type' => 'mcq',
                    'value' => $selected,
                ];
                if ($this->questionHasAutoCheck($q)) {
                    $correct = (int) ($q['correctOption'] ?? -1) === $selected;
                    $entry['correct'] = $correct;
                    $autoGradable++;
                    if ($correct) {
                        $autoCorrect++;
                        $earnedXp += (int) ($q['xp'] ?? 0);
                    }
                }
                $answers[$qId] = $entry;
            } elseif ($type === 'short_answer') {
                $value = trim((string) $request->input("answers.{$qId}", ''));
                if ($value === '') {
                    throw new \InvalidArgumentException('Please answer all questions before submitting.');
                }
                $entry = [
                    'type' => 'short_answer',
                    'value' => $value,
                ];
                if ($this->questionHasAutoCheck($q)) {
                    $correctText = strtolower(trim((string) ($q['correctText'] ?? '')));
                    $correct = strtolower($value) === $correctText;
                    $entry['correct'] = $correct;
                    $autoGradable++;
                    if ($correct) {
                        $autoCorrect++;
                        $earnedXp += (int) ($q['xp'] ?? 0);
                    }
                }
                $answers[$qId] = $entry;
            } elseif ($type === 'draw') {
                $drawingData = $request->input("answer_drawings.{$qId}");
                if (! $drawingData || ! str_starts_with($drawingData, 'data:image')) {
                    throw new \InvalidArgumentException('Please complete the drawing for all draw questions.');
                }
                $path = $this->storeBase64Image($drawingData, 'assignments/submissions/drawings');
                $answers[$qId] = [
                    'type' => 'draw',
                    'image_path' => $path,
                ];
            } elseif ($type === 'image_upload') {
                if (! $request->hasFile("answer_files.{$qId}")) {
                    throw new \InvalidArgumentException('Please upload an image for all image upload questions.');
                }
                $file = $request->file("answer_files.{$qId}");
                $path = $file->store('assignments/submissions/uploads', 'public');
                $answers[$qId] = [
                    'type' => 'image_upload',
                    'image_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $totalQuestions = count(array_filter($questions, fn ($q) => ! empty($q['id'])));
        $canAutoGrade = $totalQuestions > 0 && $autoGradable === $totalQuestions;
        $autoScore = $canAutoGrade
            ? round(($autoCorrect / $autoGradable) * 100, 2)
            : null;

        return [
            'answers' => $answers,
            'auto_score' => $autoScore,
            'can_auto_grade' => $canAutoGrade,
            'earned_xp' => $earnedXp,
        ];
    }

    public function questionHasAutoCheck(array $question): bool
    {
        $type = $question['type'] ?? 'short_answer';

        if ($type === 'mcq') {
            return array_key_exists('correctOption', $question)
                && $question['correctOption'] !== null
                && $question['correctOption'] !== '';
        }

        if ($type === 'short_answer') {
            return trim((string) ($question['correctText'] ?? '')) !== '';
        }

        return false;
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

        $imageData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
        if ($imageData === false) {
            throw new \InvalidArgumentException('Could not decode drawing image.');
        }

        $filename = $directory.'/'.uniqid('draw_', true).'.'.$extension;
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }
}
