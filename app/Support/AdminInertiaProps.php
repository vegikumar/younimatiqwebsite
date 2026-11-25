<?php

namespace App\Support;

use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\ClassFeedback;
use App\Models\ClassRecording;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\EmailAutomation;
use App\Models\EmailAutomationRun;
use App\Models\EmailLog;
use App\Models\EmailSuppression;
use App\Models\FeaturedProject;
use App\Models\MeetContactAlert;
use App\Models\ParentClassFeedback;
use App\Models\Quiz;
use App\Models\StudentClassFeedback;
use App\Models\Testimonial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminInertiaProps
{
    /**
     * Map a LengthAwarePaginator through a transformer for Inertia tables.
     *
     * @param  callable(mixed): array  $map
     */
    public static function paginate(LengthAwarePaginator $paginator, callable $map): array
    {
        return [
            'data' => collect($paginator->items())->map($map)->values()->all(),
            'links' => $paginator->linkCollection()->toArray(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public static function category(Category $category): array
    {
        $image = $category->image;
        $imageUrl = null;
        if ($image) {
            $imageUrl = Str::startsWith($image, ['http://', 'https://', '/'])
                ? $image
                : Storage::disk('public')->url($image);
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'sort_order' => (int) ($category->sort_order ?? 0),
            'is_active' => (bool) $category->is_active,
            'image' => $image,
            'imageUrl' => $imageUrl,
            'seo_metadata' => is_array($category->seo_metadata) ? $category->seo_metadata : [],
            'landingPageUrl' => route('admin.categories.landing-page', $category),
        ];
    }

    public static function recording(ClassRecording $recording): array
    {
        $tutorName = $recording->liveSession?->tutor?->name
            ?? $recording->booking?->tutor?->name
            ?? null;

        return [
            'id' => $recording->id,
            'title' => $recording->title ?: 'Untitled recording',
            'courseTitle' => $recording->course?->title,
            'tutorName' => $tutorName,
            'recordedAtLabel' => optional($recording->recorded_at ?? $recording->created_at)?->format('M j, Y g:i A'),
            'expiresAtLabel' => optional($recording->expires_at)?->format('M j, Y'),
            'deletedFromStorageAtLabel' => optional($recording->deleted_from_storage_at)?->format('M j, Y'),
            'isAvailable' => method_exists($recording, 'isAvailable') ? $recording->isAvailable() : true,
            'assignedStudents' => $recording->assignments
                ? $recording->assignments->map(fn ($a) => $a->student?->name)->filter()->values()->all()
                : [],
        ];
    }

    public static function meetAlert(MeetContactAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'roomId' => $alert->room_id,
            'source' => $alert->source,
            'sourceLabel' => method_exists($alert, 'sourceLabel') ? $alert->sourceLabel() : ucfirst((string) $alert->source),
            'typeLabel' => method_exists($alert, 'typeLabel') ? $alert->typeLabel() : ucfirst((string) $alert->primary_type),
            'actorName' => $alert->actor_name,
            'actorRole' => $alert->actor_role,
            'sharedText' => $alert->shared_text,
            'matches' => collect($alert->matches ?? [])->map(function ($m) {
                if (is_array($m)) {
                    return [
                        'type' => $m['type'] ?? $m['kind'] ?? 'match',
                        'value' => $m['value'] ?? $m['match'] ?? null,
                    ];
                }

                return ['type' => 'match', 'value' => (string) $m];
            })->values()->all(),
            'occurredAtLabel' => optional($alert->occurred_at)?->format('M j, Y g:i A'),
            'reviewedAt' => optional($alert->reviewed_at)?->toIso8601String(),
            'reviewedAtHuman' => optional($alert->reviewed_at)?->diffForHumans(),
            'reviewUrl' => route('admin.meet-alerts.reviewed', $alert),
        ];
    }

    public static function feedbackListItem(ClassFeedback $fb): array
    {
        return [
            'id' => $fb->id,
            'sessionType' => $fb->session_type,
            'studentName' => $fb->student?->name ?? 'Student',
            'tutorName' => $fb->tutor?->name ?? 'Tutor',
            'createdAtLabel' => optional($fb->created_at)?->format('M j, Y g:i A'),
            'customerType' => $fb->customer_type,
            'sharedWithParent' => (bool) $fb->shared_with_parent_at,
            'showUrl' => route('admin.feedback.show', $fb),
        ];
    }

    public static function feedbackDetail(ClassFeedback $fb): array
    {
        $raw = $fb->feedback_data;
        $items = [];
        if (is_array($raw)) {
            if (array_is_list($raw)) {
                foreach ($raw as $row) {
                    if (is_array($row)) {
                        $items[] = [
                            'question' => (string) ($row['question'] ?? $row['label'] ?? 'Question'),
                            'answer' => is_scalar($row['answer'] ?? null)
                                ? (string) $row['answer']
                                : json_encode($row['answer'] ?? $row['value'] ?? $row),
                        ];
                    } else {
                        $items[] = ['question' => 'Note', 'answer' => (string) $row];
                    }
                }
            } else {
                foreach ($raw as $question => $answer) {
                    $items[] = [
                        'question' => self::feedbackLabel((string) $question),
                        'answer' => is_scalar($answer) ? (string) $answer : json_encode($answer),
                    ];
                }
            }
        }

        $parentEmails = [];
        if ($fb->student) {
            $parentEmails = $fb->student->parents()->pluck('email')->filter()->values()->all();
        }

        return [
            'id' => $fb->id,
            'sessionType' => $fb->session_type,
            'customerType' => $fb->customer_type,
            'adminNotes' => $fb->admin_notes,
            'parentMessage' => $fb->parent_message,
            'sharedWithParentAt' => optional($fb->shared_with_parent_at)?->format('M j, Y g:i A'),
            'canSendToParent' => (bool) $fb->student_id && $fb->session_type !== 'demo',
            'parentEmails' => $parentEmails,
            'feedbackItems' => $items,
            'student' => [
                'name' => $fb->student?->name ?? 'Student',
                'email' => $fb->student?->email ?? '',
                'profileUrl' => $fb->student_id ? '/admin/students' : null,
            ],
            'tutor' => [
                'name' => $fb->tutor?->name ?? 'Tutor',
                'email' => $fb->tutor?->email ?? '',
            ],
            'updateUrl' => route('admin.feedback.update', $fb),
            'sendToParentUrl' => route('admin.feedback.send-to-parent', $fb),
        ];
    }

    public static function feedbackLabel(string $key): string
    {
        return match ($key) {
            'progress' => "Student's progress today",
            'improvement_areas' => 'Areas of improvement',
            'behavior' => 'General behavior',
            'notes' => 'Additional notes',
            'enjoyed_class' => 'Did the student enjoy the class?',
            'skill_level' => 'Skill level',
            'customer_type_recommendation' => 'Recommended follow-up',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    public static function parentFeedbackListItem(ParentClassFeedback $fb): array
    {
        return [
            'id' => $fb->id,
            'source' => 'parent',
            'studentName' => $fb->student?->name ?? 'Student',
            'submitterName' => $fb->parent?->name ?? 'Parent',
            'parentName' => $fb->parent?->name ?? 'Parent',
            'tutorName' => $fb->tutor?->name ?? '—',
            'rating' => (int) $fb->rating,
            'createdAtLabel' => optional($fb->created_at)?->format('M j, Y g:i A'),
            'reviewed' => (bool) $fb->reviewed_at,
            'showUrl' => route('admin.feedback.parent.show', $fb),
        ];
    }

    public static function studentFeedbackListItem(StudentClassFeedback $fb): array
    {
        return [
            'id' => $fb->id,
            'source' => 'student',
            'studentName' => $fb->student?->name ?? 'Student',
            'submitterName' => $fb->student?->name ?? 'Student',
            'tutorName' => $fb->tutor?->name ?? '—',
            'rating' => (int) $fb->rating,
            'createdAtLabel' => optional($fb->created_at)?->format('M j, Y g:i A'),
            'reviewed' => (bool) $fb->reviewed_at,
            'showUrl' => route('admin.feedback.student.show', $fb),
        ];
    }

    public static function parentFeedbackDetail(ParentClassFeedback $fb): array
    {
        return [
            'id' => $fb->id,
            'source' => 'parent',
            'rating' => (int) $fb->rating,
            'classExperience' => $fb->class_experience,
            'tutorCommunication' => $fb->tutor_communication,
            'wouldRecommend' => $fb->would_recommend,
            'comments' => $fb->comments,
            'adminNotes' => $fb->admin_notes,
            'reviewedAtLabel' => optional($fb->reviewed_at)?->format('M j, Y g:i A'),
            'createdAtLabel' => optional($fb->created_at)?->format('M j, Y g:i A'),
            'classTitle' => $fb->booking?->course?->title,
            'classAtLabel' => optional($fb->booking?->scheduled_at)?->format('M j, Y g:i A'),
            'submitterLabel' => 'Parent',
            'submitter' => [
                'name' => $fb->parent?->name ?? 'Parent',
                'email' => $fb->parent?->email ?? '',
            ],
            'parent' => [
                'name' => $fb->parent?->name ?? 'Parent',
                'email' => $fb->parent?->email ?? '',
            ],
            'student' => [
                'name' => $fb->student?->name ?? 'Student',
                'email' => $fb->student?->email ?? '',
            ],
            'tutor' => [
                'name' => $fb->tutor?->name ?? '—',
                'email' => $fb->tutor?->email ?? '',
            ],
            'backUrl' => route('admin.feedback.index', ['type' => 'parent']),
            'updateUrl' => route('admin.feedback.parent.update', $fb),
        ];
    }

    public static function studentFeedbackDetail(StudentClassFeedback $fb): array
    {
        return [
            'id' => $fb->id,
            'source' => 'student',
            'rating' => (int) $fb->rating,
            'classExperience' => $fb->class_experience,
            'tutorCommunication' => $fb->tutor_communication,
            'wouldRecommend' => $fb->would_recommend,
            'comments' => $fb->comments,
            'adminNotes' => $fb->admin_notes,
            'reviewedAtLabel' => optional($fb->reviewed_at)?->format('M j, Y g:i A'),
            'createdAtLabel' => optional($fb->created_at)?->format('M j, Y g:i A'),
            'classTitle' => $fb->booking?->course?->title,
            'classAtLabel' => optional($fb->booking?->scheduled_at)?->format('M j, Y g:i A'),
            'submitterLabel' => 'Student',
            'submitter' => [
                'name' => $fb->student?->name ?? 'Student',
                'email' => $fb->student?->email ?? '',
            ],
            'parent' => [
                'name' => '—',
                'email' => '',
            ],
            'student' => [
                'name' => $fb->student?->name ?? 'Student',
                'email' => $fb->student?->email ?? '',
            ],
            'tutor' => [
                'name' => $fb->tutor?->name ?? '—',
                'email' => $fb->tutor?->email ?? '',
            ],
            'backUrl' => route('admin.feedback.index', ['type' => 'student']),
            'updateUrl' => route('admin.feedback.student.update', $fb),
        ];
    }

    public static function adminQuiz(Quiz $quiz): array
    {
        $assignments = $quiz->relationLoaded('assignments')
            ? $quiz->assignments
            : $quiz->assignments()->with('assignable')->get();

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'passingScore' => $quiz->passing_score,
            'passing_score' => $quiz->passing_score,
            'xpReward' => $quiz->xp_reward,
            'xp_reward' => $quiz->xp_reward,
            'questions' => $quiz->questions ?? [],
            'questionCount' => is_countable($quiz->questions) ? count($quiz->questions) : 0,
            'assignments' => $assignments->map(function ($a) {
                $label = 'Unknown';
                if ($a->assignable_type === Course::class) {
                    $label = 'Course: '.($a->assignable?->title ?? 'Unknown');
                } elseif ($a->assignable_type === User::class) {
                    $label = 'Student: '.($a->assignable?->name ?? 'Unknown');
                }

                return ['label' => $label, 'type' => class_basename((string) $a->assignable_type)];
            })->values()->all(),
            'editUrl' => route('admin.quizzes.edit', $quiz),
        ];
    }

    public static function featuredProject(FeaturedProject $project): array
    {
        $path = $project->video_path;
        $mediaUrl = null;
        $isImage = false;
        if ($path) {
            $mediaUrl = Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : Storage::disk('public')->url($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
        }

        return [
            'id' => $project->id,
            'title' => $project->title,
            'category' => $project->category,
            'author_name' => $project->author_name,
            'project_url' => $project->project_url,
            'views_count' => (int) ($project->views_count ?? 0),
            'likes_count' => (int) ($project->likes_count ?? 0),
            'mediaUrl' => $mediaUrl,
            'isImage' => $isImage,
            'media_url' => $mediaUrl,
            'is_image' => $isImage,
        ];
    }

    public static function testimonial(Testimonial $t): array
    {
        $avatarUrl = null;
        if ($t->avatar_path) {
            $avatarUrl = Str::startsWith($t->avatar_path, ['http://', 'https://'])
                ? $t->avatar_path
                : Storage::disk('public')->url($t->avatar_path);
        }
        $videoUrl = null;
        if ($t->video_path) {
            $videoUrl = Str::startsWith($t->video_path, ['http://', 'https://'])
                ? $t->video_path
                : Storage::disk('public')->url($t->video_path);
        }

        return [
            'id' => $t->id,
            'author_name' => $t->author_name,
            'text' => $t->text,
            'rating' => (int) ($t->rating ?? 5),
            'avatarUrl' => $avatarUrl,
            'videoUrl' => $videoUrl,
            'is_active' => (bool) ($t->is_active ?? true),
            'sort_order' => (int) ($t->sort_order ?? 0),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::query()->orderBy('name')->get(['id', 'name'])->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
        ])->values()->all();
    }

    public static function ageGroupOptions(): array
    {
        return AgeGroup::active()->get(['id', 'name', 'slug'])->map(fn (AgeGroup $ag) => [
            'id' => $ag->id,
            'name' => $ag->name,
            'slug' => $ag->slug,
        ])->values()->all();
    }

    public static function paginatorMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->values()->all(),
            'links' => $paginator->linkCollection()->toArray(),
            'total' => $paginator->total(),
        ];
    }

    public static function courseIndexRows(Collection $courses): array
    {
        return $courses->map(function (Course $course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'categoryLabel' => $course->categories->count()
                    ? $course->categories->pluck('name')->join(', ')
                    : 'Uncategorized',
                'priceUsd' => (float) ($course->price_usd ?? 18),
                'priceUsdLabel' => number_format((float) ($course->price_usd ?? 18), 2),
                'createdByLabel' => $course->tutor
                    ? 'Tutor: '.$course->tutor->name
                    : 'Admin',
                'createdByType' => $course->tutor ? 'tutor' : 'admin',
                'status' => $course->status,
                'isActive' => (bool) $course->is_active,
                'createdAt' => optional($course->created_at)?->toIso8601String(),
                'reviewUrl' => route('courses.review.create', $course),
                'editUrl' => route('admin.courses.edit', $course),
                'destroyUrl' => route('admin.courses.destroy', $course),
                'statusUrl' => route('admin.courses.status', $course),
                'toggleActiveUrl' => route('admin.courses.toggle-active', $course),
            ];
        })->values()->all();
    }

    public static function syllabusModuleRows(Course $course): array
    {
        $existing = CourseModule::query()
            ->where('course_id', $course->id)
            ->whereNull('unit_id')
            ->orderBy('order')
            ->get();

        $rows = $existing->map(fn (CourseModule $m) => [
            'id' => $m->id,
            'class_name' => $m->title,
            'description' => $m->description ?? $m->content_text ?? '',
            'outcome' => $m->learning_outcome ?? '',
            'practical_video_url' => $m->practical_video_url ?? '',
        ])->values();

        if ($rows->isEmpty()) {
            $rows = collect(range(0, 7))->map(fn ($i) => [
                'id' => time() + $i,
                'class_name' => '',
                'description' => '',
                'outcome' => '',
                'practical_video_url' => '',
            ]);
        }

        return $rows->all();
    }

    public static function courseForEdit(Course $course, array $selectedAgeGroupIds): array
    {
        $course->loadMissing(['categories', 'units.modules', 'ageGroups']);

        $units = $course->units->map(fn ($unit) => [
            'id' => $unit->id,
            'order' => $unit->order,
            'title' => $unit->title,
            'description' => $unit->description,
            'updateUrl' => route('admin.courses.units.update', $unit),
            'destroyUrl' => route('admin.courses.units.destroy', $unit),
            'modules' => $unit->modules->map(fn ($mod) => [
                'id' => $mod->id,
                'title' => $mod->title,
                'content_type' => $mod->content_type,
                'duration_minutes' => $mod->duration_minutes,
                'content_url' => $mod->content_url,
                'updateUrl' => route('admin.courses.modules.update', $mod),
                'destroyUrl' => route('admin.courses.modules.destroy', $mod),
                'unit_id' => $unit->id,
            ])->values()->all(),
        ])->values()->all();

        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'categoryIds' => $course->categories->pluck('id')->all(),
            'price_usd' => $course->price_usd ?? 18,
            'price_aed' => $course->price_aed ?? 30,
            'price_inr' => $course->price_inr ?? 600,
            'course_type' => $course->course_type ?? 'live',
            'self_paced_enabled' => (bool) $course->self_paced_enabled,
            'format' => $course->format ?? 'group',
            'learning_outcomes' => implode("\n", $course->learning_outcomes ?? []),
            'thumbnail_url' => $course->thumbnail_url,
            'video_url' => $course->video_url,
            'welcome_video_url' => $course->welcome_video_url,
            'welcome_message' => $course->welcome_message,
            'is_top_course' => (bool) $course->is_top_course,
            'seo_title' => $course->seo_metadata['title'] ?? '',
            'seo_description' => $course->seo_metadata['description'] ?? '',
            'published_at' => $course->published_at?->format('Y-m-d\TH:i'),
            'selectedAgeGroupIds' => array_map('intval', $selectedAgeGroupIds),
            'syllabusModules' => self::syllabusModuleRows($course),
            'units' => $units,
            'updateUrl' => route('admin.courses.update', $course),
            'unitsStoreUrl' => route('admin.courses.units.store', $course),
            'modulesStoreUrl' => route('admin.courses.modules.store', $course),
            'importSyllabusUrl' => route('admin.courses.import-syllabus', $course),
            'emailTriggerPreviewBaseUrl' => route('admin.email-automations.course.preview', $course),
            'emailTriggerSendUrl' => route('admin.email-automations.course.send', $course),
        ];
    }

    public static function emailAutomationSummaries(Collection $automations, array $audienceCounts, array $nextSendAts, array $dayLabels): array
    {
        return $automations->map(function (EmailAutomation $automation) use ($audienceCounts, $nextSendAts, $dayLabels) {
            $days = collect($automation->days_of_week ?? [])
                ->map(fn ($d) => $dayLabels[(int) $d] ?? $d)
                ->implode(', ');
            $next = $nextSendAts[$automation->id] ?? null;

            return [
                'id' => $automation->id,
                'name' => $automation->name,
                'subject' => $automation->subject,
                'is_enabled' => (bool) $automation->is_enabled,
                'audienceCount' => $audienceCounts[$automation->id] ?? 0,
                'scheduleLabel' => ($days ?: 'No days set').' · '.$automation->send_time.' '.$automation->timezone,
                'nextSendLabel' => $next
                    ? $next->timezone($automation->timezone ?: 'Asia/Kolkata')->format('D, M j · g:i A T')
                    : null,
                'lastSentAt' => optional($automation->last_sent_at)?->toIso8601String(),
                'lastSentCount' => (int) $automation->last_sent_count,
                'editUrl' => route('admin.email-automations.edit', $automation),
                'destroyUrl' => route('admin.email-automations.destroy', $automation),
            ];
        })->values()->all();
    }

    public static function emailAutomationForEdit(EmailAutomation $automation): array
    {
        return [
            'id' => $automation->id,
            'name' => $automation->name,
            'slug' => $automation->slug,
            'is_enabled' => (bool) $automation->is_enabled,
            'subject' => $automation->subject,
            'heading' => $automation->heading,
            'intro_text' => $automation->intro_text,
            'cta_text' => $automation->cta_text,
            'cta_url' => $automation->cta_url,
            'from_name' => $automation->from_name,
            'sign_off_name' => $automation->sign_off_name,
            'reply_to' => $automation->reply_to,
            'days_of_week' => array_map('intval', $automation->days_of_week ?? [1, 3, 5]),
            'send_time' => $automation->send_time ?: '10:00',
            'timezone' => $automation->timezone,
            'audiences' => $automation->audiences ?? [],
            'exclude_emails' => $automation->exclude_emails,
            'include_emails' => $automation->include_emails,
            'course_source' => $automation->course_source ?? 'top',
            'course_ids' => array_map('intval', $automation->course_ids ?? []),
            'max_courses' => (int) $automation->max_courses,
            'exclude_camps' => (bool) ($automation->exclude_camps ?? true),
            'skip_recent_days' => (int) ($automation->skip_recent_days ?? 2),
            'utm_campaign' => $automation->utm_campaign ?: 'email_campaign',
            'notify_admin_on_send' => (bool) ($automation->notify_admin_on_send ?? true),
            'theme_html' => $automation->theme_html,
            'last_sent_at' => optional($automation->last_sent_at)?->toIso8601String(),
            'last_sent_count' => (int) $automation->last_sent_count,
            'updateUrl' => route('admin.email-automations.update', $automation),
            'destroyUrl' => route('admin.email-automations.destroy', $automation),
            'previewUrl' => route('admin.email-automations.preview', $automation),
            'testUrl' => route('admin.email-automations.test', $automation),
            'sendNowUrl' => route('admin.email-automations.send-now', $automation),
            'customPreviewUrl' => route('admin.email-automations.custom.preview', $automation),
            'customSendUrl' => route('admin.email-automations.custom.send', $automation),
        ];
    }

    public static function emailAutomationRuns(Collection $runs): array
    {
        return $runs->map(fn (EmailAutomationRun $run) => [
            'id' => $run->id,
            'automationName' => $run->automation?->name ?? 'Campaign',
            'trigger' => $run->trigger,
            'status' => $run->status,
            'recipient_count' => (int) $run->recipient_count,
            'sent_count' => (int) $run->sent_count,
            'created_at' => optional($run->created_at)?->toIso8601String(),
        ])->values()->all();
    }

    public static function emailLogs(Collection $logs): array
    {
        return $logs->map(fn (EmailLog $log) => [
            'id' => $log->id,
            'to_email' => $log->to_email,
            'status' => $log->status,
            'created_at' => optional($log->created_at)?->toIso8601String(),
        ])->values()->all();
    }

    public static function emailSuppressions(Collection $rows): array
    {
        return $rows->map(fn (EmailSuppression $row) => [
            'id' => $row->id,
            'email' => $row->email,
            'reason' => $row->reason,
            'suppressed_at_human' => optional($row->suppressed_at)?->diffForHumans(),
            'destroyUrl' => route('admin.email-automations.suppressions.destroy', $row),
        ])->values()->all();
    }

    public static function timezoneOptions(array $extras = []): array
    {
        return \App\Services\TimezoneService::commonOptionsForSelect(null, $extras);
    }

    public static function tutorAvailabilityItem(array $item): array
    {
        /** @var User $tutor */
        $tutor = $item['tutor'];

        return [
            'tutorId' => $tutor->id,
            'tutorName' => $tutor->name,
            'tutorEmail' => $tutor->email,
            'isOccupied' => (bool) $item['is_occupied'],
            'occupiedStudent' => $item['occupied_student'] ?? '',
            'occupiedCourse' => $item['occupied_course'] ?? '',
        ];
    }

    public static function enrollmentIndexPage(
        LengthAwarePaginator $enrollments,
        LengthAwarePaginator $bookings,
        LengthAwarePaginator $campBookings,
        Collection $students,
        Collection $courses,
        array $tutorsAvailability,
        array $campTutorsAvailability,
        array $bookingsSchedule,
        ?string $search,
        $courseId,
        ?string $status,
        string $tab
    ): array {
        $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $enrollmentRows = collect($enrollments->items())->map(function ($e) {
            return [
                'id' => $e->id,
                'studentName' => $e->user->name ?? '',
                'studentEmail' => $e->user->email ?? '',
                'courseTitle' => $e->course->title ?? '',
                'progress' => (int) $e->progress,
                'status' => $e->status,
                'createdAt' => optional($e->created_at)?->toIso8601String(),
                'hasCertificate' => method_exists($e, 'hasUploadedCertificate') ? $e->hasUploadedCertificate() : false,
                'certificateUploadUrl' => route('admin.enrollments.certificate.upload', $e),
                'cancelUrl' => route('admin.enrollments.cancel', $e),
                'sendEmailUrl' => route('admin.enrollments.send_email', $e),
            ];
        })->values()->all();

        $bookingRows = collect($bookings->items())->map(function ($b) use ($tutorsAvailability, $bookingsSchedule, $dayLabels) {
            $bs = $bookingsSchedule[$b->id] ?? ['classes' => 1, 'days' => [], 'times' => [], 'start_date' => date('Y-m-d'), 'timezone' => 'UTC', 'next_at' => null];
            $displayAt = $b->scheduled_at
                ? $b->scheduled_at->toIso8601String()
                : ($bs['next_at'] ?? null);
            $displayTz = \App\Services\TimezoneService::resolveForDisplay(
                $bs['timezone'] ?? null,
                $b->parent_timezone,
                $b->timezone,
                $b->student?->timezone,
            );
            $studentOptedLabel = null;
            if ($displayAt && ! \App\Services\TimezoneService::isUnset($displayTz)) {
                try {
                    $local = Carbon::parse($displayAt)->setTimezone($displayTz);
                    $studentOptedLabel = $local->format('h:i A').' ('.\App\Services\TimezoneService::label($displayTz).')';
                } catch (\Throwable) {
                    $studentOptedLabel = null;
                }
            }

            return [
                'id' => $b->id,
                'studentName' => $b->student->name ?? 'Student',
                'studentEmail' => $b->student->email ?? '',
                'courseTitle' => $b->course->title ?? 'Live Course',
                'sessionTypeLabel' => str_replace('_', ' ', $b->session_type),
                'scheduledAt' => $displayAt,
                'studentOptedLabel' => $studentOptedLabel,
                'scheduleSummary' => [
                    'classes' => $bs['classes'],
                    'daysLabel' => collect($bs['days'] ?? [])->map(fn ($d) => $dayLabels[$d] ?? $d)->implode(', '),
                    'days' => $bs['days'] ?? [],
                    'times' => $bs['times'] ?? [],
                    'start_date' => $bs['start_date'],
                    'timezone' => $bs['timezone'],
                ],
                'scheduleUpdateUrl' => route('admin.bookings.update-schedule', $b->id),
                'autoAssignUrl' => route('admin.bookings.auto-assign-tutor', $b->id),
                'assignTutorUrl' => route('admin.bookings.assign-tutor', $b->id),
                'tutorId' => $b->tutor_id,
                'tutorName' => $b->tutor?->name,
                'tutorsAvailability' => collect($tutorsAvailability[$b->id] ?? [])->map(fn ($item) => self::tutorAvailabilityItem($item))->values()->all(),
                'zoomJoinUrl' => $b->zoom_join_url,
                'zoomStartUrl' => $b->zoom_start_url,
                'status' => $b->status,
                'createdAt' => optional($b->created_at)?->toIso8601String(),
            ];
        })->values()->all();

        $campRows = collect($campBookings->items())->map(function ($cb) use ($campTutorsAvailability) {
            $studentOptedLabel = null;
            if ($cb->scheduled_at && $cb->timezone) {
                try {
                    $local = Carbon::parse($cb->scheduled_at)->setTimezone(\App\Services\TimezoneService::sanitizeTimezone($cb->timezone));
                    $studentOptedLabel = $local->format('h:i A').' ('.\App\Services\TimezoneService::label($cb->timezone).')';
                } catch (\Throwable) {
                    $studentOptedLabel = null;
                }
            }
            $scheduledLocal = $cb->scheduled_at && $cb->timezone
                ? Carbon::parse($cb->scheduled_at)->setTimezone(\App\Services\TimezoneService::sanitizeTimezone($cb->timezone))->format('Y-m-d\TH:i')
                : '';

            return [
                'id' => $cb->id,
                'childName' => $cb->child_name,
                'parentName' => $cb->parent_name,
                'email' => $cb->email,
                'phone' => $cb->phone,
                'campName' => $cb->camp_name,
                'age' => $cb->age,
                'country' => $cb->country,
                'scheduledAt' => optional($cb->scheduled_at)?->toIso8601String(),
                'studentOptedLabel' => $studentOptedLabel,
                'scheduledLocal' => $scheduledLocal,
                'timezone' => $cb->timezone ?? 'UTC',
                'scheduleUpdateUrl' => route('admin.camp-bookings.update-schedule', $cb->id),
                'assignTutorUrl' => route('admin.camp-bookings.assign-tutor', $cb->id),
                'tutorId' => $cb->tutor_id,
                'tutorName' => $cb->tutor?->name,
                'tutorsAvailability' => collect($campTutorsAvailability[$cb->id] ?? [])->map(fn ($item) => self::tutorAvailabilityItem($item))->values()->all(),
                'zoomJoinUrl' => $cb->zoom_join_url,
                'zoomStartUrl' => $cb->zoom_start_url,
                'statusLabel' => $cb->tutor_id ? 'Assigned' : 'Pending Assignment',
                'statusClass' => ($cb->payment_status === 'paid' || $cb->tutor_id) ? 'assigned' : 'pending',
                'createdAt' => optional($cb->created_at)?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'tab' => $tab,
            'filters' => [
                'search' => $search ?? '',
                'courseId' => $courseId ?? '',
                'status' => $status ?? '',
            ],
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'timezone' => $s->timezone ?: $s->parent_timezone,
            ])->values()->all(),
            'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values()->all(),
            'timezones' => self::timezoneOptions(),
            'weekDays' => [
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
                ['value' => 0, 'label' => 'Sunday'],
            ],
            'scheduleWeekDays' => [
                ['value' => 1, 'label' => 'Mon'],
                ['value' => 2, 'label' => 'Tue'],
                ['value' => 3, 'label' => 'Wed'],
                ['value' => 4, 'label' => 'Thu'],
                ['value' => 5, 'label' => 'Fri'],
                ['value' => 6, 'label' => 'Sat'],
                ['value' => 0, 'label' => 'Sun'],
            ],
            'enrollments' => [
                'data' => $enrollmentRows,
                'links' => $enrollments->linkCollection()->toArray(),
            ],
            'bookings' => [
                'data' => $bookingRows,
                'links' => $bookings->linkCollection()->toArray(),
            ],
            'campBookings' => [
                'data' => $campRows,
                'links' => $campBookings->linkCollection()->toArray(),
                'total' => $campBookings->total(),
            ],
            'routes' => [
                'index' => route('admin.enrollments'),
                'store' => route('admin.enrollments.store'),
            ],
        ];
    }
}
