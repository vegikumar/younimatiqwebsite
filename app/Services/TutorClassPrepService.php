<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\TutorClassPrepCompletion;
use App\Models\TutorTrainingAssignment;
use App\Support\ClassroomGames;
use Illuminate\Support\Collection;

class TutorClassPrepService
{
    public const STEPS = [
        ['key' => 'materials', 'label' => 'Class materials', 'icon' => 'check'],
        ['key' => 'previous-report', 'label' => 'Previous class report', 'icon' => '1'],
        ['key' => 'objectives', 'label' => 'Objectives & outcomes', 'icon' => '2'],
        ['key' => 'lesson-plan', 'label' => 'Lesson plan & guide', 'icon' => '3'],
        ['key' => 'presentation', 'label' => 'Presentation & activity', 'icon' => '4'],
        ['key' => 'resources', 'label' => 'Resources & software', 'icon' => '5'],
        ['key' => 'checklist', 'label' => 'Pre-class checklist', 'icon' => '6'],
        ['key' => 'build-notes', 'label' => 'Build notes', 'icon' => 'x', 'optional' => true],
    ];

    /**
     * @return array{focus: ?array, upcoming: list<array>}
     */
    public function dashboardPayload(int $tutorId, ?string $focusKey = null): array
    {
        $upcoming = $this->upcomingClasses($tutorId);
        if ($upcoming->isEmpty()) {
            return ['focus' => null, 'upcoming' => []];
        }

        $focus = null;
        if ($focusKey) {
            $focus = $upcoming->first(fn ($c) => $c['key'] === $focusKey);
        }
        $focus ??= $upcoming->first();

        return [
            'focus' => $this->hydrateFocus($tutorId, $focus),
            'upcoming' => $upcoming->map(fn ($c) => [
                'key' => $c['key'],
                'label' => $c['title'],
                'when' => $c['scheduledAt'],
                'studentName' => $c['studentName'],
            ])->values()->all(),
        ];
    }

    public function markComplete(int $tutorId, string $contextType, int $contextId, string $itemKey, bool $done = true): void
    {
        abort_unless(in_array($contextType, ['booking', 'camp_booking', 'live_session'], true), 422);
        abort_unless($this->tutorOwnsContext($tutorId, $contextType, $contextId), 403);

        if ($done) {
            TutorClassPrepCompletion::query()->updateOrCreate(
                [
                    'tutor_id' => $tutorId,
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                    'item_key' => $itemKey,
                ],
                ['completed_at' => now()]
            );
        } else {
            TutorClassPrepCompletion::query()
                ->where('tutor_id', $tutorId)
                ->where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->where('item_key', $itemKey)
                ->delete();
        }
    }

    private function upcomingClasses(int $tutorId): Collection
    {
        $rows = collect();

        Booking::query()
            ->with(['course.modules', 'course.units', 'student'])
            ->where('tutor_id', $tutorId)
            ->where('scheduled_at', '>=', now()->subHour())
            ->whereIn('status', ['confirmed', 'scheduled', 'pending'])
            ->orderBy('scheduled_at')
            ->limit(12)
            ->get()
            ->each(function (Booking $b) use ($rows) {
                $rows->push($this->fromBooking($b));
            });

        CampBooking::query()
            ->where('tutor_id', $tutorId)
            ->where('scheduled_at', '>=', now()->subHour())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->each(function (CampBooking $b) use ($rows) {
                $rows->push($this->fromCamp($b));
            });

        LiveSession::query()
            ->with(['course.modules', 'course.units', 'module'])
            ->where('tutor_id', $tutorId)
            ->where('start_time', '>=', now()->subHour())
            ->orderBy('start_time')
            ->limit(8)
            ->get()
            ->each(function (LiveSession $s) use ($rows) {
                $rows->push($this->fromLiveSession($s));
            });

        return $rows->sortBy('scheduledAt')->values();
    }

    private function fromBooking(Booking $b): array
    {
        $enrollment = null;
        if ($b->student_id && $b->course_id) {
            $enrollment = Enrollment::query()
                ->where('user_id', $b->student_id)
                ->where('course_id', $b->course_id)
                ->first();
        }

        $classIndex = $this->classIndexForBooking($b, $enrollment);
        $totalClasses = max((int) ($enrollment?->paid_classes_count ?: 0), $b->course?->modules?->count() ?: 0, 8);
        $module = $this->moduleForIndex($b->course, $classIndex);

        return [
            'key' => 'booking-'.$b->id,
            'contextType' => 'booking',
            'contextId' => $b->id,
            'kind' => $b->session_type === 'trial' ? 'Trial' : '1:1',
            'title' => $module?->title ?: ('Class '.$classIndex.': '.($b->course?->title ?? 'Upcoming class')),
            'courseTitle' => $b->course?->title ?? 'Course',
            'studentName' => $b->student?->name ?? 'Student',
            'studentGrade' => $b->student?->grade,
            'scheduledAt' => optional($b->scheduled_at)?->toIso8601String(),
            'durationMinutes' => 60,
            'classIndex' => $classIndex,
            'totalClasses' => $totalClasses,
            'carryForward' => $classIndex > 1 ? 'Carry-forward from Class '.($classIndex - 1) : null,
            'launchUrl' => route('tutor.bookings.launch', $b),
            'module' => $module,
            'course' => $b->course,
        ];
    }

    private function fromCamp(CampBooking $b): array
    {
        return [
            'key' => 'camp-'.$b->id,
            'contextType' => 'camp_booking',
            'contextId' => $b->id,
            'kind' => 'Trial',
            'title' => $b->camp_name ?: 'Free Trial Session',
            'courseTitle' => $b->camp_name ?: 'Trial',
            'studentName' => $b->child_name ?: 'Learner',
            'studentGrade' => null,
            'scheduledAt' => optional($b->scheduled_at)?->toIso8601String(),
            'durationMinutes' => 45,
            'classIndex' => 1,
            'totalClasses' => 1,
            'carryForward' => null,
            'launchUrl' => route('tutor.camp-bookings.launch', $b),
            'module' => null,
            'course' => null,
        ];
    }

    private function fromLiveSession(LiveSession $s): array
    {
        $module = $s->module ?: $this->moduleForIndex($s->course, 1);

        return [
            'key' => 'session-'.$s->id,
            'contextType' => 'live_session',
            'contextId' => $s->id,
            'kind' => 'Group',
            'title' => $module?->title ?: ($s->course?->title ?? 'Live class'),
            'courseTitle' => $s->course?->title ?? 'Course',
            'studentName' => 'Group class',
            'studentGrade' => null,
            'scheduledAt' => optional($s->start_time)?->toIso8601String(),
            'durationMinutes' => (int) ($s->duration_minutes ?: 60),
            'classIndex' => max(1, (int) ($module?->order ?: 1)),
            'totalClasses' => max($s->course?->modules?->count() ?: 0, 8),
            'carryForward' => null,
            'launchUrl' => route('tutor.sessions.launch', $s),
            'module' => $module,
            'course' => $s->course,
        ];
    }

    private function hydrateFocus(int $tutorId, array $raw): array
    {
        $completions = TutorClassPrepCompletion::query()
            ->where('tutor_id', $tutorId)
            ->where('context_type', $raw['contextType'])
            ->where('context_id', $raw['contextId'])
            ->pluck('item_key')
            ->all();

        $materials = $this->buildMaterials($raw, $completions, $tutorId);
        $steps = collect(self::STEPS)->map(function (array $step) use ($completions, $materials) {
            $key = 'step:'.$step['key'];
            $done = in_array($key, $completions, true);
            if ($step['key'] === 'materials') {
                $done = count($materials) > 0 && collect($materials)->every(fn ($m) => $m['completed']);
            }

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'icon' => $step['icon'],
                'optional' => (bool) ($step['optional'] ?? false),
                'completed' => $done,
                'itemKey' => $key,
            ];
        })->values()->all();

        $requiredMaterials = count($materials);
        $doneMaterials = collect($materials)->where('completed', true)->count();
        $requiredSteps = collect($steps)->where('optional', false);
        $doneSteps = $requiredSteps->where('completed', true)->count();
        $progress = $requiredSteps->count()
            ? (int) round(($doneSteps / $requiredSteps->count()) * 100)
            : 0;

        $ageLabel = $raw['studentGrade'] !== null && $raw['studentGrade'] !== ''
            ? 'Grade '.$raw['studentGrade']
            : null;

        return [
            'key' => $raw['key'],
            'contextType' => $raw['contextType'],
            'contextId' => $raw['contextId'],
            'headline' => $this->headline($raw),
            'courseTitle' => $raw['courseTitle'],
            'studentName' => $raw['studentName'],
            'studentMeta' => trim(implode(' · ', array_filter([
                $raw['studentName'],
                $ageLabel,
                $raw['kind'],
            ]))),
            'scheduledAt' => $raw['scheduledAt'],
            'durationMinutes' => $raw['durationMinutes'],
            'classIndex' => $raw['classIndex'],
            'totalClasses' => $raw['totalClasses'],
            'classProgressLabel' => 'Class '.$raw['classIndex'].' of '.$raw['totalClasses'],
            'carryForward' => $raw['carryForward'],
            'launchUrl' => $raw['launchUrl'],
            'progress' => $progress,
            'materialsDone' => $doneMaterials,
            'materialsTotal' => $requiredMaterials,
            'ready' => $doneSteps === $requiredSteps->count() && $requiredSteps->count() > 0,
            'materials' => $materials,
            'steps' => $steps,
            'objectives' => $raw['module']?->learning_outcome
                ?: ($raw['module']?->description ?: 'Review today’s learning goals with the learner and confirm they can explain the idea in their own words.'),
            'previousReport' => $raw['carryForward']
                ? 'Review carry-forward notes from the previous class before you start.'
                : 'No previous class report for this learner yet.',
        ];
    }

    private function headline(array $raw): string
    {
        $idx = $raw['classIndex'];
        $moduleTitle = $raw['module']?->title;
        if ($moduleTitle) {
            return 'Class '.$idx.': '.$moduleTitle;
        }

        return 'Class '.$idx.': '.$raw['courseTitle'];
    }

    /**
     * @param  list<string>  $completions
     * @return list<array>
     */
    private function buildMaterials(array $raw, array $completions, int $tutorId): array
    {
        /** @var CourseModule|null $module */
        $module = $raw['module'] ?? null;

        $items = [];

        $lessonUrl = $module?->content_url ?: ($module?->host_url ?: null);
        $items[] = $this->material(
            'lesson-plan',
            'Lesson plan',
            $module?->content_type ? strtoupper((string) $module->content_type).' · guide' : 'PDF · lesson guide',
            $lessonUrl ?: route('tutor.training.index'),
            $completions
        );

        $pptUrl = $module?->host_url && $module->host_url !== $lessonUrl ? $module->host_url : null;
        $items[] = $this->material(
            'presentation',
            'Presentation (PPT)',
            'Slides · class deck',
            $pptUrl ?: route('tutor.resources'),
            $completions
        );

        $practiceUrl = $module?->practical_video_url ?: null;
        if (! $practiceUrl) {
            $firstGame = collect(ClassroomGames::all())->first();
            $practiceUrl = $firstGame ? route('tutor.games.preview', $firstGame['key']) : route('tutor.games.index');
        }
        $items[] = $this->material(
            'practice',
            'Practice link',
            'In-class activity · web',
            $practiceUrl,
            $completions
        );

        $bookUrl = $this->firstTrainingDocumentUrl($tutorId);
        if (! $bookUrl && $module?->content_text) {
            $bookUrl = route('tutor.training.index');
        }
        $items[] = $this->material(
            'book',
            'Book / reading',
            $module?->description ? 'Reading · module notes' : 'PDF · chapter reading',
            $bookUrl ?: route('tutor.resources'),
            $completions
        );

        return $items;
    }

    /**
     * @param  list<string>  $completions
     */
    private function material(string $key, string $name, string $type, ?string $url, array $completions): array
    {
        $itemKey = 'material:'.$key;

        return [
            'key' => $key,
            'itemKey' => $itemKey,
            'name' => $name,
            'type' => $type,
            'url' => $url,
            'completed' => in_array($itemKey, $completions, true),
        ];
    }

    private function firstTrainingDocumentUrl(int $tutorId): ?string
    {
        $assignment = TutorTrainingAssignment::query()
            ->with(['module.items'])
            ->where('tutor_id', $tutorId)
            ->latest()
            ->first();

        if (! $assignment?->module) {
            return null;
        }

        foreach ($assignment->module->items as $item) {
            if ($item->type !== 'document') {
                continue;
            }
            $docs = $item->resolvedDocuments();
            if ($docs !== []) {
                return $docs[0]['url'] ?? null;
            }
        }

        return null;
    }

    private function classIndexForBooking(Booking $b, ?Enrollment $enrollment): int
    {
        $past = Booking::query()
            ->where('tutor_id', $b->tutor_id)
            ->where('student_id', $b->student_id)
            ->where('course_id', $b->course_id)
            ->where('id', '<=', $b->id)
            ->whereIn('status', ['confirmed', 'scheduled', 'completed', 'pending'])
            ->count();

        if ($past > 0) {
            return $past;
        }

        $progress = (int) ($enrollment?->progress ?? 0);

        return max(1, $progress + 1);
    }

    private function moduleForIndex(?Course $course, int $classIndex): ?CourseModule
    {
        if (! $course) {
            return null;
        }

        $modules = $course->relationLoaded('modules')
            ? $course->modules
            : $course->modules()->orderBy('order')->get();

        if ($modules->isEmpty()) {
            return null;
        }

        return $modules->firstWhere('order', $classIndex) ?: $modules->values()->get($classIndex - 1) ?: $modules->first();
    }

    private function tutorOwnsContext(int $tutorId, string $type, int $id): bool
    {
        return match ($type) {
            'booking' => Booking::query()->whereKey($id)->where('tutor_id', $tutorId)->exists(),
            'camp_booking' => CampBooking::query()->whereKey($id)->where('tutor_id', $tutorId)->exists(),
            'live_session' => LiveSession::query()->whereKey($id)->where('tutor_id', $tutorId)->exists(),
            default => false,
        };
    }
}
