<?php

namespace App\Support;

use App\Models\AiParentNote;
use App\Models\AiSessionRecap;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassroomDiscussion;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\InterestedLead;
use App\Models\LiveSession;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\StudentReward;
use App\Models\TutorTrainingAssignment;
use App\Models\TutorTrainingProgress;
use App\Models\User;
use App\Services\ClassRewardService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TutorInertiaProps
{
    public static function xpPresets(): array
    {
        return ClassRewardService::XP_PRESETS;
    }

    public static function badgeCatalog(Collection $badges): array
    {
        return $badges->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'icon' => $b->badge_icon,
            'isSystem' => (bool) $b->is_system,
        ])->values()->all();
    }

    public static function booking(Booking $b): array
    {
        $canLaunch = filled($b->zoom_start_url) || filled($b->zoom_join_url) || filled($b->meeting_room_id);

        return [
            'id' => $b->id,
            'studentId' => $b->student_id,
            'studentName' => $b->student?->name,
            'courseId' => $b->course_id,
            'courseTitle' => $b->course?->title,
            'scheduledAt' => optional($b->scheduled_at)?->toIso8601String(),
            'zoomStartUrl' => $b->zoom_start_url,
            // Fresh host ZAK is minted server-side on click (Zoom start_url expires ~2h).
            'launchUrl' => $canLaunch ? route('tutor.bookings.launch', $b) : null,
            'sessionType' => $b->session_type,
        ];
    }

    public static function campBooking(CampBooking $b): array
    {
        $canLaunch = filled($b->zoom_start_url) || filled($b->zoom_join_url) || filled($b->meeting_room_id);

        return [
            'id' => $b->id,
            'campName' => $b->camp_name,
            'childName' => $b->child_name,
            'parentName' => $b->parent_name,
            'scheduledAt' => optional($b->scheduled_at)?->toIso8601String(),
            'zoomStartUrl' => $b->zoom_start_url,
            'launchUrl' => $canLaunch ? route('tutor.camp-bookings.launch', $b) : null,
        ];
    }

    public static function liveSession(LiveSession $s): array
    {
        $firstUnit = $s->course?->units?->first();
        $canLaunch = filled($s->start_url) || filled($s->join_url) || filled($s->meeting_room_id);

        return [
            'id' => $s->id,
            'courseTitle' => $s->course?->title,
            'startTime' => optional($s->start_time)?->toIso8601String(),
            'durationMinutes' => $s->duration_minutes,
            'startUrl' => $s->start_url,
            'launchUrl' => $canLaunch ? route('tutor.sessions.launch', $s) : null,
            'lessonPlan' => $firstUnit ? [
                'title' => $firstUnit->title,
                'description' => $firstUnit->description,
            ] : null,
        ];
    }

    public static function enrollment(Enrollment $e): array
    {
        return [
            'id' => $e->id,
            'userId' => $e->user_id,
            'studentName' => $e->user?->name,
            'courseId' => $e->course_id,
            'courseTitle' => $e->course?->title,
            'status' => $e->status,
            'hasCertificate' => method_exists($e, 'hasUploadedCertificate') ? $e->hasUploadedCertificate() : (bool) $e->certificate_path,
        ];
    }

    public static function payment(Payment $p): array
    {
        return [
            'id' => $p->id,
            'createdAt' => optional($p->created_at)?->toIso8601String(),
            'courseTitle' => $p->course?->title,
            'studentName' => $p->user?->name,
            'amount' => (float) $p->amount,
        ];
    }

    public static function manageCourse(Course $course, int $tutorId): array
    {
        $next = $course->nextSession ?? null;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'format' => $course->format,
            'courseType' => $course->course_type,
            'status' => $course->status,
            'price' => (float) ($course->price ?? 0),
            'slug' => $course->slug,
            'isOwner' => (int) $course->tutor_id === $tutorId,
            'unitsCount' => $course->units?->count() ?? 0,
            'modulesCount' => $course->modules?->count() ?? 0,
            'nextSession' => $next ? [
                'startTime' => optional($next->start_time)?->toIso8601String(),
                'durationMinutes' => $next->duration_minutes,
                'startUrl' => $next->start_url,
                'joinUrl' => $next->join_url,
            ] : null,
            'classroomUrl' => route('tutor.classroom.show', $course),
            'editUrl' => route('tutor.courses.edit', $course),
            'units' => ($course->units ?? collect())->map(fn ($unit) => [
                'order' => $unit->order,
                'title' => $unit->title,
                'description' => $unit->description,
                'modules' => ($unit->modules ?? collect())->map(fn ($mod) => [
                    'title' => $mod->title,
                    'contentType' => $mod->content_type ?? $mod->module_type,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public static function metrics(array $m): array
    {
        return [
            'classScore' => $m['class_score'] ?? 5,
            'reviewCount' => $m['review_count'] ?? 0,
            'learnersTaught' => $m['learners_taught'] ?? 0,
            'committedSlots' => $m['committed_slots'] ?? 0,
            'punctualityRate' => $m['punctuality_rate'],
            'pastClasses' => $m['past_classes'] ?? 0,
            'cancellations' => $m['cancellations'] ?? 0,
            'withdrawals' => $m['withdrawals'] ?? 0,
            'reEnrollmentRate' => $m['re_enrollment_rate'] ?? 0,
            'monthlyEarnings' => $m['monthly_earnings'] ?? 0,
            'yearlyEarnings' => $m['yearly_earnings'] ?? 0,
            'hoursWorked' => $m['hours_worked'] ?? 0,
        ];
    }

    public static function payout($p): array
    {
        return [
            'id' => $p->id,
            'periodMonth' => (int) $p->period_month,
            'periodYear' => (int) $p->period_year,
            'baseEarnings' => (float) $p->base_earnings,
            'upsellCommission' => (float) $p->upsell_commission,
            'bonus' => (float) $p->bonus,
            'netPayout' => (float) $p->net_payout,
            'status' => $p->status,
        ];
    }

    public static function question($q): array
    {
        return [
            'id' => $q->id,
            'title' => $q->title,
            'body' => $q->body,
            'courseTitle' => $q->course?->title,
            'studentName' => $q->user?->name ?? 'Student',
            'answers' => ($q->answers ?? collect())->map(fn ($a) => [
                'id' => $a->id,
                'body' => $a->body,
            ])->values()->all(),
        ];
    }

    public static function announcement($a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'content' => $a->content,
            'courseTitle' => $a->course?->title,
            'scheduledAt' => optional($a->scheduled_at)?->toIso8601String(),
            'publishedAt' => optional($a->published_at)?->toIso8601String(),
            'isWelcomePost' => (bool) $a->is_welcome_post,
        ];
    }

    public static function messageStartOptions(Collection $courses): array
    {
        $out = [];
        foreach ($courses as $course) {
            foreach ($course->enrollments ?? [] as $enrollment) {
                if ($enrollment->user) {
                    $out[] = [
                        'studentId' => $enrollment->user->id,
                        'label' => $enrollment->user->name.' ('.$course->title.')',
                        'courseId' => $course->id,
                    ];
                }
            }
        }

        return $out;
    }

    public static function conversation($c): array
    {
        return [
            'id' => $c->id,
            'studentName' => $c->student?->name,
            'lastMessageAt' => optional($c->last_message_at)?->toIso8601String(),
            'preview' => $c->latestMessage?->message ?? 'No messages yet.',
        ];
    }

    public static function directMessage($m, int $tutorId): array
    {
        return [
            'id' => $m->id,
            'body' => $m->message,
            'createdAt' => optional($m->created_at)?->toIso8601String(),
            'isMine' => (int) $m->sender_id === $tutorId,
        ];
    }

    public static function quiz(Quiz $quiz): array
    {
        $assignments = $quiz->assignments ?? collect();

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'passingScore' => $quiz->passing_score,
            'xpReward' => $quiz->xp_reward,
            'questions' => $quiz->questions ?? [],
            'questionCount' => count($quiz->questions ?? []),
            'assignments' => $assignments->map(function ($a) {
                $label = 'Unknown';
                if ($a->assignable_type === Course::class) {
                    $label = 'Course: '.($a->assignable?->title ?? 'Unknown');
                } elseif ($a->assignable_type === User::class) {
                    $label = 'Student: '.($a->assignable?->name ?? 'Unknown');
                }

                return ['label' => $label, 'type' => class_basename($a->assignable_type)];
            })->values()->all(),
            'editUrl' => route('tutor.quizzes.edit', $quiz),
        ];
    }

    public static function courseOption(Course $c): array
    {
        return ['id' => $c->id, 'title' => $c->title];
    }

    public static function studentOption(User $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'enrolledCourseIds' => $s->enrollments?->pluck('course_id')->values()->all() ?? [],
        ];
    }

    public static function trainingAssignments(Collection $assignments, Collection $progress): array
    {
        return $assignments->map(function (TutorTrainingAssignment $assignment) use ($progress) {
            $module = $assignment->module;

            $items = $module->items->map(function ($item) use ($progress) {
                /** @var TutorTrainingProgress|null $p */
                $p = $progress->get($item->id);
                $videoSrc = $item->video_url ?: ($item->video_path ? asset('storage/'.$item->video_path) : null);
                $documents = $item->resolvedDocuments();
                $documentSrc = collect($documents)->firstWhere('kind', 'file')['url']
                    ?? ($item->document_path ? asset('storage/'.$item->document_path) : null);
                $documentUrl = collect($documents)->firstWhere('kind', 'url')['url']
                    ?? $item->document_url;

                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'videoSrc' => $videoSrc,
                    'documents' => $documents,
                    'documentUrl' => $documentUrl,
                    'documentSrc' => $documentSrc,
                    'quizQuestions' => $item->quiz_questions ?? [],
                    'passScore' => $item->pass_score,
                    'status' => $p?->status ?? 'pending',
                    'score' => $p?->score,
                    'videoUrl' => route('tutor.training.video', $item),
                    'documentCompleteUrl' => route('tutor.training.document', $item),
                    'assignmentUrl' => route('tutor.training.assignment', $item),
                    'quizUrl' => route('tutor.training.quiz', $item),
                ];
            })->values()->all();

            $allPassed = count($items) > 0 && collect($items)->every(fn ($i) => ($i['status'] ?? '') === 'passed');

            return [
                'id' => $assignment->id,
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'courseName' => $module->course_name ?: $module->title,
                    'className' => $module->class_name ?: $module->title,
                    'classNumber' => $module->class_number,
                    'description' => $module->description,
                    'items' => $items,
                    'readyForClass' => $allPassed,
                ],
            ];
        })->values()->all();
    }

    public static function tutorSkills(Collection $skills): array
    {
        return $skills->map(fn ($ts) => [
            'name' => $ts->skill?->name,
            'verified' => (bool) $ts->verified,
        ])->values()->all();
    }

    public static function paginatedRewards(LengthAwarePaginator $rewards): array
    {
        return [
            'data' => collect($rewards->items())->map(fn (StudentReward $r) => [
                'id' => $r->id,
                'studentName' => $r->student?->name,
                'courseTitle' => $r->course?->title,
                'isTrial' => (bool) $r->is_trial,
                'awardedAt' => optional($r->awarded_at)?->toIso8601String(),
                'xpAmount' => (int) $r->xp_amount,
                'levelUpTip' => $r->level_up_tip,
                'compliments' => $r->compliments ?? [],
                'badge' => $r->badge ? [
                    'title' => $r->badge->title,
                    'icon' => $r->badge->badge_icon,
                ] : null,
            ])->all(),
            'links' => $rewards->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
            ],
        ];
    }

    public static function categoryOption($category): array
    {
        return ['id' => $category->id, 'name' => $category->name];
    }

    public static function courseFormType(Course $course): string
    {
        if ($course->is_camp) {
            return 'camp';
        }
        if ($course->format === '1-on-1') {
            return '1-on-1';
        }

        return 'recording';
    }

    public static function courseEdit(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'shortDescription' => $course->short_description ?? $course->importance,
            'description' => $course->description,
            'type' => static::courseFormType($course),
            'price' => (float) ($course->price ?? 0),
            'categoryIds' => $course->categories?->pluck('id')->values()->all() ?? [],
            'thumbnailUrl' => $course->thumbnail_url,
            'updateUrl' => route('tutor.courses.update', $course),
        ];
    }

    public static function classroomCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'manageCoursesUrl' => route('tutor.manage-courses'),
            'discussionStoreUrl' => route('tutor.classroom.discussion.store', $course),
            'recordingStoreUrl' => route('tutor.classroom.recording.store', $course),
        ];
    }

    public static function classroomDiscussion(ClassroomDiscussion $post): array
    {
        return [
            'id' => $post->id,
            'authorName' => $post->author?->name ?? 'Tutor',
            'authorInitial' => strtoupper(substr($post->author?->name ?? 'T', 0, 1)),
            'content' => $post->content,
            'createdAt' => optional($post->created_at)?->toIso8601String(),
            'attachments' => $post->attachments ?? [],
            'replies' => ($post->replies ?? collect())->map(fn (ClassroomDiscussion $reply) => [
                'id' => $reply->id,
                'authorName' => $reply->author?->name ?? 'User',
                'content' => $reply->content,
                'createdAt' => optional($reply->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public static function classroomRecording($rec): array
    {
        return [
            'id' => $rec->id,
            'title' => $rec->title,
            'recordingUrl' => $rec->recording_url,
            'recordedAt' => optional($rec->recorded_at ?? $rec->created_at)?->toIso8601String(),
            'destroyUrl' => route('tutor.classroom.recording.destroy', $rec),
        ];
    }

    public static function enrolledStudent(User $student, Course $course): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'courseId' => $course->id,
            'messageStartUrl' => route('tutor.messages.start'),
        ];
    }

    public static function classroomReward(StudentReward $reward): array
    {
        return [
            'studentName' => $reward->student?->name ?? 'Student',
            'badgeTitle' => $reward->badge?->title,
            'complimentLabels' => collect($reward->compliments ?? [])->pluck('label')->filter()->values()->all(),
            'xpAmount' => (int) $reward->xp_amount,
        ];
    }

    public static function copilotCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'classroomUrl' => route('tutor.classroom.show', $course),
            'updateUrl' => route('tutor.copilot.update', $course),
        ];
    }

    public static function aiCourseWithStudents(Course $course): array
    {
        $students = [];
        foreach ($course->enrollments ?? [] as $enrollment) {
            if ($enrollment->user) {
                $students[] = [
                    'id' => $enrollment->user->id,
                    'name' => $enrollment->user->name,
                    'courseTitle' => $course->title,
                ];
            }
        }

        return [
            'id' => $course->id,
            'title' => $course->title,
            'students' => $students,
        ];
    }

    public static function aiParentNote(AiParentNote $note): array
    {
        return [
            'id' => $note->id,
            'studentName' => $note->student?->name ?? 'Student',
            'courseTitle' => $note->course?->title ?? 'Class',
            'aiGeneratedNote' => $note->ai_generated_note,
            'homeworkAssignment' => $note->homework_assignment,
            'createdAt' => optional($note->created_at)?->toIso8601String(),
        ];
    }

    public static function aiSessionRecap(AiSessionRecap $recap): array
    {
        return [
            'id' => $recap->id,
            'courseTitle' => $recap->course?->title ?? 'Live Class Session',
            'status' => $recap->status,
            'editedSummary' => $recap->edited_summary ?? $recap->transcript_summary,
            'updateUrl' => route('tutor.ai-assistant.recap', $recap),
            'autoSendAt' => optional($recap->auto_send_at)?->toIso8601String(),
            'autoSendHuman' => $recap->auto_send_at ? $recap->auto_send_at->diffForHumans() : null,
        ];
    }

    public static function growthLead(InterestedLead $lead): array
    {
        return [
            'id' => $lead->id,
            'familyName' => $lead->user?->name ?? 'Interested Parent',
            'courseTitle' => $lead->course?->title ?? 'Class Listing',
            'createdAt' => optional($lead->created_at)?->toIso8601String(),
            'status' => $lead->status,
            'nudgeUrl' => route('tutor.growth.nudge', $lead),
        ];
    }

    public static function tutorLiveSession(LiveSession $session): array
    {
        return [
            'id' => $session->id,
            'courseTitle' => $session->course?->title ?? 'N/A',
            'startTime' => optional($session->start_time)?->toIso8601String(),
            'durationMinutes' => (int) ($session->duration_minutes ?? 0),
            'status' => $session->status,
            'startUrl' => $session->start_url,
        ];
    }

    public static function tutorStorefrontProfile(User $tutor): array
    {
        return [
            'bio' => $tutor->bio,
            'teachingStyle' => $tutor->teaching_style,
            'introVideoUrl' => $tutor->intro_video_url,
            'previewUrl' => route('tutors.storefront', $tutor),
            'updateUrl' => route('tutor.storefront.update'),
        ];
    }

    public static function publicTutorStorefront(User $tutor, float $averageRating, Collection $courses, Collection $reviews): array
    {
        return [
            'tutor' => [
                'id' => $tutor->id,
                'name' => $tutor->name,
                'bio' => $tutor->bio,
                'teachingStyle' => $tutor->teaching_style,
                'introVideoUrl' => $tutor->intro_video_url,
                'initial' => strtoupper(substr($tutor->name, 0, 1)),
            ],
            'averageRating' => $averageRating,
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'format' => $c->format,
                'price' => (float) ($c->price ?? 0),
                'showUrl' => route('courses.show', $c),
            ])->values()->all(),
        ];
    }

    public static function tutorAssignment(Assignment $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'courseTitle' => $a->course?->title,
            'createdAt' => optional($a->created_at)?->toIso8601String(),
            'filePath' => $a->file_path,
            'fileUrl' => $a->file_path ? route('secure.asset', ['path' => $a->file_path]) : null,
            'questionsCount' => $a->hasQuestions() ? count($a->questions ?? []) : 0,
            'studentNames' => ($a->students ?? collect())->pluck('name')->values()->all(),
            'submissions' => ($a->submissions ?? collect())->map(fn (AssignmentSubmission $s) => static::assignmentSubmission($a, $s))->values()->all(),
        ];
    }

    public static function assignmentSubmission(Assignment $assignment, AssignmentSubmission $s): array
    {
        return [
            'id' => $s->id,
            'studentName' => $s->user?->name ?? 'Student',
            'createdAt' => optional($s->created_at)?->toIso8601String(),
            'content' => $s->content,
            'filePath' => $s->file_path,
            'fileUrl' => $s->file_path ? route('secure.asset', ['path' => $s->file_path]) : null,
            'answers' => $s->answers ?? [],
            'questions' => $assignment->questions ?? [],
            'grade' => $s->grade,
            'feedback' => $s->feedback,
            'status' => $s->status,
            'gradeUrl' => route('tutor.assignments.grade', $s),
        ];
    }

    public static function feedbackCreate(int $bookingId, string $type, string $studentName): array
    {
        return [
            'bookingId' => $bookingId,
            'type' => $type,
            'studentName' => $studentName,
            'storeUrl' => route('tutor.feedback.store'),
            'cancelUrl' => route('tutor.classes'),
        ];
    }
}
