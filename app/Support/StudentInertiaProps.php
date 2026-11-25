<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentInertiaProps
{
    public static function courseBrief(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'thumbnail_url' => $course->thumbnail_url,
        ];
    }

    public static function assignment(Assignment $assignment): array
    {
        $submission = $assignment->submissions->first();

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'due_at_label' => $assignment->due_at?->format('M d, Y'),
            'due_day' => $assignment->due_at?->format('d'),
            'due_month' => $assignment->due_at?->format('M'),
            'file_path' => $assignment->file_path,
            'file_url' => $assignment->file_path ? route('secure.asset', ['path' => $assignment->file_path]) : null,
            'file_kind' => static::assignmentFileKind($assignment->file_path),
            'has_questions' => $assignment->hasQuestions(),
            'questions_count' => count($assignment->questions ?? []),
            'questions' => collect($assignment->questions ?? [])->map(function ($question) {
                $options = collect($question['options'] ?? [])->map(function ($option) {
                    $text = is_array($option) ? ($option['text'] ?? '') : $option;

                    return ['text' => (string) $text];
                })->values()->all();

                return [
                    'id' => $question['id'] ?? null,
                    'type' => $question['type'] ?? 'short_answer',
                    'question' => $question['question'] ?? '',
                    'image_url' => $question['image_url'] ?? null,
                    'options' => $options,
                ];
            })->values()->all(),
            'course' => $assignment->course ? static::courseBrief($assignment->course) : null,
            'take_url' => route('student.assignments.take', $assignment),
            'submit_url' => route('student.assignments.submit', $assignment),
            'submission' => $submission ? static::submission($submission) : null,
        ];
    }

    private static function assignmentFileKind(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            return 'pdf';
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        return 'file';
    }

    public static function submission(AssignmentSubmission $s): array
    {
        return [
            'id' => $s->id,
            'content' => $s->content,
            'file_path' => $s->file_path,
            'file_url' => $s->file_path ? route('secure.asset', ['path' => $s->file_path]) : null,
            'answers' => $s->answers ?? [],
            'status' => $s->status,
            'grade' => $s->grade,
            'feedback' => $s->feedback,
            'submitted_at_label' => $s->created_at?->format('M d, Y'),
        ];
    }

    public static function quiz(Quiz $quiz, bool $withQuestions = false): array
    {
        $latestAttempt = $quiz->relationLoaded('attempts') ? $quiz->attempts->first() : null;

        $payload = [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'passing_score' => $quiz->passing_score,
            'xp_reward' => $quiz->xp_reward,
            'question_count' => count($quiz->questions ?? []),
            'course_title' => $quiz->course?->title,
            'take_url' => route('student.quizzes.take', $quiz),
            'results_url' => route('student.quizzes.results', $quiz),
            'latest_attempt' => $latestAttempt ? static::quizAttempt($latestAttempt) : null,
        ];

        if ($withQuestions) {
            $payload['questions'] = static::questionsForAttempt($quiz);
        }

        return $payload;
    }

    /**
     * Questions safe to send to a student: no answer keys, no editor previews.
     */
    public static function questionsForAttempt(Quiz $quiz): array
    {
        $questions = $quiz->questions ?? [];
        if (! is_array($questions)) {
            return [];
        }

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
                'image_url' => $question['image_url'] ?? null,
                'options' => $options,
            ];
        }

        return $out;
    }

    public static function quizAttempt(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'passed' => (bool) $attempt->passed,
            'created_at_label' => $attempt->created_at?->format('M j, Y g:i A'),
        ];
    }

    public static function enrollment(Enrollment $e): array
    {
        return [
            'id' => $e->id,
            'status' => $e->status,
            'progress' => (float) ($e->progress ?? 0),
            'certificate_path' => $e->certificate_path,
        ];
    }

    public static function checkoutCourse(Course $course): array
    {
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))) ?? '');

        return array_merge(static::courseBrief($course), [
            'number_of_classes' => $course->number_of_classes,
            'price' => (float) ($course->price ?? 0),
            'price_usd' => $course->price_usd,
            'price_aed' => $course->price_aed,
            'price_inr' => $course->price_inr,
            'per_class_price' => $course->per_class_price,
            'course_type' => $course->course_type,
            'description' => $description !== '' ? $description : null,
        ]);
    }

    public static function booking(Booking $b): array
    {
        return [
            'id' => $b->id,
            'course_title' => $b->course?->title,
            'tutor_name' => $b->tutor?->name,
            'scheduled_at_label' => $b->scheduled_at?->format('M j, Y g:i A'),
            'status' => $b->status,
            'zoom_join_url' => $b->zoom_join_url,
        ];
    }

    public static function liveSession(LiveSession $s): array
    {
        return [
            'id' => $s->id,
            'course_title' => $s->course?->title,
            'start_time_label' => $s->start_time?->format('M j, Y g:i A'),
            'join_url' => $s->join_url,
        ];
    }

    public static function campBooking(CampBooking $b): array
    {
        return [
            'id' => $b->id,
            'camp_name' => $b->camp_name,
            'scheduled_at_label' => $b->scheduled_at?->format('M j, Y g:i A'),
        ];
    }

    public static function payment(Payment $p): array
    {
        return [
            'id' => $p->id,
            'createdAt' => optional($p->created_at)?->toIso8601String(),
            'courseTitle' => $p->course?->title ?? 'N/A',
            'amount' => (float) ($p->amount ?? 0),
            'gateway' => $p->gateway,
            'status' => $p->status,
        ];
    }

    public static function order(Order $o): array
    {
        return [
            'id' => $o->id,
            'orderNumber' => $o->order_number ?? (string) $o->id,
            'createdAt' => optional($o->created_at)?->toIso8601String(),
            'courseTitle' => $o->course?->title ?? 'N/A',
            'amount' => (float) ($o->amount ?? 0),
            'status' => $o->status,
        ];
    }

    public static function review(Review $r): array
    {
        return [
            'id' => $r->id,
            'courseId' => $r->course_id,
            'courseTitle' => $r->course?->title ?? 'Deleted Course',
            'rating' => (int) $r->rating,
            'comment' => $r->comment,
            'createdAt' => optional($r->created_at)?->toIso8601String(),
        ];
    }

    public static function question(Question $q): array
    {
        return [
            'id' => $q->id,
            'title' => $q->title,
            'body' => $q->body,
            'courseTitle' => $q->course?->title ?? 'N/A',
            'createdAt' => optional($q->created_at)?->toIso8601String(),
            'answers' => ($q->answers ?? collect())->map(fn ($a) => [
                'id' => $a->id,
                'body' => $a->body,
                'authorName' => $a->user?->name ?? 'Tutor',
                'createdAt' => optional($a->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public static function studentBooking(Booking $b): array
    {
        return [
            'id' => $b->id,
            'tutorName' => $b->tutor?->name ?? 'Tutor',
            'courseTitle' => $b->course?->title ?? 'N/A',
            'scheduledAt' => optional($b->scheduled_at)?->toIso8601String(),
            'createdAt' => optional($b->created_at)?->toIso8601String(),
            'status' => $b->status,
            'zoomJoinUrl' => $b->zoom_join_url,
            'meetingProvider' => $b->meeting_provider,
            'meetingRoomId' => $b->meeting_room_id,
            'zoomPasscode' => $b->zoom_passcode,
        ];
    }

    public static function bookableLiveSession(LiveSession $s): array
    {
        return [
            'id' => $s->id,
            'courseTitle' => $s->course?->title ?? 'Live Session',
            'startTime' => optional($s->start_time)?->toIso8601String(),
            'durationMinutes' => (int) ($s->duration_minutes ?? 60),
        ];
    }

    public static function demoSession(CampBooking $demo): array
    {
        $campName = (string) ($demo->camp_name ?? '');

        return [
            'id' => $demo->id,
            'campName' => $campName,
            'displayTitle' => trim(str_replace(['Free Trial - ', 'Consultation - '], '', $campName)),
            'kindLabel' => str_starts_with($campName, 'Consultation') ? 'Free Consultation' : 'Free Trial Class',
            'scheduledAt' => optional($demo->scheduled_at)?->toIso8601String(),
            'zoomJoinUrl' => $demo->zoom_join_url,
            'meetingProvider' => $demo->meeting_provider,
            'meetingRoomId' => $demo->meeting_room_id,
            'zoomPasscode' => $demo->zoom_passcode,
        ];
    }
}
