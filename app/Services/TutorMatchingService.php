<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Course;
use App\Models\HrAuditEvent;
use App\Models\Setting;
use App\Models\TutorAvailability;
use App\Models\TutorMatchSuggestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TutorMatchingService
{
    public function matchMode(): string
    {
        $mode = (string) (Setting::where('key', 'tutor_match_mode')->value('value') ?? 'suggest');

        return in_array($mode, ['auto', 'suggest', 'manual'], true) ? $mode : 'suggest';
    }

    public function requiredSkillIds(int $courseId): array
    {
        $course = Course::with(['skills', 'categories'])->find($courseId);
        if (! $course) {
            return [];
        }

        $ids = $course->skills->pluck('id')->all();
        if (! empty($ids)) {
            return array_values(array_unique($ids));
        }

        $categoryIds = $course->categories->pluck('id')->all();
        if (empty($categoryIds)) {
            return [];
        }

        return \DB::table('category_skill_requirements')
            ->whereIn('category_id', $categoryIds)
            ->pluck('skill_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function match(int $courseId, ?Carbon $scheduledAt = null, int $limit = 20): Collection
    {
        $required = $this->requiredSkillIds($courseId);

        $tutors = User::query()
            ->where('role', 'tutor')
            ->where('status', 'active')
            ->with(['tutorSkills', 'skills'])
            ->get()
            ->filter(function (User $tutor) use ($required) {
                if (! empty($required)) {
                    $active = $tutor->tutorSkills
                        ->filter(fn ($ts) => $ts->isActive())
                        ->pluck('skill_id')
                        ->all();
                    foreach ($required as $skillId) {
                        if (! in_array($skillId, $active, true)) {
                            return false;
                        }
                    }
                }

                return true;
            });

        $scored = $tutors->map(function (User $tutor) use ($scheduledAt, $courseId) {
            $futureLoad = Booking::where('tutor_id', $tutor->id)
                ->where('scheduled_at', '>=', now())
                ->count();

            $conflict = false;
            $availabilityBonus = 0;
            if ($scheduledAt) {
                $windowStart = $scheduledAt->copy()->subMinutes(45);
                $windowEnd = $scheduledAt->copy()->addMinutes(45);
                $conflict = Booking::where('tutor_id', $tutor->id)
                    ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
                    ->exists();

                $dow = (int) $scheduledAt->dayOfWeek;
                $time = $scheduledAt->format('H:i:s');
                $availabilityBonus = TutorAvailability::where('tutor_id', $tutor->id)
                    ->where('day_of_week', $dow)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>=', $time)
                    ->exists() ? 10 : 0;
            }

            $continuityBonus = 0;
            if ($courseId) {
                $prior = Booking::where('tutor_id', $tutor->id)
                    ->where('course_id', $courseId)
                    ->whereIn('status', ['completed', 'confirmed'])
                    ->exists();
                $continuityBonus = $prior ? 25 : 0;
            }

            $nps = (float) ($tutor->nps_score ?? 0);
            $loadPenalty = $futureLoad * 10;
            $conflictPenalty = $conflict ? 500 : 0;
            $base = 1000;
            $score = $base - $loadPenalty + $availabilityBonus + $nps + $continuityBonus - $conflictPenalty;

            $tutor->match_score = $score;
            $tutor->match_conflict = $conflict;
            $tutor->match_load = $futureLoad;
            $tutor->match_breakdown = [
                'base' => $base,
                'load_penalty' => -$loadPenalty,
                'availability_bonus' => $availabilityBonus,
                'nps' => $nps,
                'continuity_bonus' => $continuityBonus,
                'conflict_penalty' => -$conflictPenalty,
                'total' => $score,
            ];

            return $tutor;
        })
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();

        return $scored;
    }

    public function bestTutorId(int $courseId, ?Carbon $scheduledAt = null): ?int
    {
        $best = $this->match($courseId, $scheduledAt, 1)->first();

        return $best?->id;
    }

    /**
     * After skills are verified, create suggestions or auto-assign open bookings.
     */
    public function onSkillsVerified(User $tutor, array $skillIds): void
    {
        if (empty($skillIds)) {
            return;
        }

        $mode = $this->matchMode();
        if ($mode === 'manual') {
            return;
        }

        $courseIds = \DB::table('course_skill_requirements')
            ->whereIn('skill_id', $skillIds)
            ->pluck('course_id')
            ->unique()
            ->values()
            ->all();

        $categoryIds = \DB::table('category_skill_requirements')
            ->whereIn('skill_id', $skillIds)
            ->pluck('category_id')
            ->unique()
            ->all();

        if (! empty($categoryIds)) {
            $fromCategories = \DB::table('category_course')
                ->whereIn('category_id', $categoryIds)
                ->pluck('course_id')
                ->all();
            $courseIds = array_values(array_unique(array_merge($courseIds, $fromCategories)));
        }

        if (empty($courseIds)) {
            return;
        }

        $created = 0;
        foreach ($courseIds as $courseId) {
            $required = $this->requiredSkillIds((int) $courseId);
            $active = $tutor->tutorSkills->filter(fn ($ts) => $ts->isActive())->pluck('skill_id')->all();
            $eligible = empty($required) || empty(array_diff($required, $active));
            if (! $eligible) {
                continue;
            }

            $matches = $this->match((int) $courseId, null, 5);
            $self = $matches->firstWhere('id', $tutor->id);
            $score = $self?->match_score ?? 0;

            TutorMatchSuggestion::updateOrCreate(
                [
                    'tutor_id' => $tutor->id,
                    'course_id' => $courseId,
                    'booking_id' => null,
                ],
                [
                    'reason' => 'Verified skills match course requirements',
                    'status' => 'suggested',
                    'match_score' => $score,
                    'score_breakdown' => $self?->match_breakdown,
                ]
            );
            $created++;

            if ($mode === 'auto') {
                $this->tryAutoAssignOpenBooking((int) $courseId, $tutor);
            }
        }

        if ($created === 0) {
            try {
                app(\App\Services\EmailService::class)->notifyAdmins(
                    'No course matches after skill verify',
                    "<p>Tutor <strong>{$tutor->name}</strong> verified skills, but no eligible courses were found for matching.</p>"
                );
            } catch (\Throwable $e) {
                Log::warning($e->getMessage());
            }
        }
    }

    /**
     * Suggest or auto-assign for a booking depending on mode. Never overrides an existing tutor.
     */
    public function applyForBooking(Booking $booking): ?User
    {
        if ($booking->tutor_id) {
            return null;
        }

        $mode = $this->matchMode();
        if ($mode === 'manual') {
            return null;
        }

        $scheduledAt = $booking->scheduled_at ? Carbon::parse($booking->scheduled_at) : null;
        $matches = $this->match((int) $booking->course_id, $scheduledAt, 5);

        if ($matches->isEmpty()) {
            try {
                app(\App\Services\EmailService::class)->notifyAdmins(
                    'No tutor match for booking',
                    "<p>Booking #{$booking->id} for course #{$booking->course_id} has no skill-matched tutors.</p>"
                );
            } catch (\Throwable $e) {
                Log::warning($e->getMessage());
            }

            return null;
        }

        foreach ($matches as $tutor) {
            $bd = $tutor->match_breakdown ?? [];
            $parts = ['Skill + availability ranked'];
            if (($bd['continuity_bonus'] ?? 0) > 0) {
                $parts[] = 'prior continuity';
            }
            if (($bd['availability_bonus'] ?? 0) > 0) {
                $parts[] = 'slot fit';
            }
            if (! empty($tutor->match_conflict)) {
                $parts[] = 'conflict risk';
            }

            TutorMatchSuggestion::updateOrCreate(
                [
                    'tutor_id' => $tutor->id,
                    'course_id' => $booking->course_id,
                    'booking_id' => $booking->id,
                ],
                [
                    'reason' => sprintf('Score %.0f · %s', $tutor->match_score ?? 0, implode(' · ', $parts)),
                    'status' => 'suggested',
                    'match_score' => $tutor->match_score ?? null,
                    'score_breakdown' => $tutor->match_breakdown ?? null,
                ]
            );
        }

        HrAuditEvent::log('match_suggest', null, null, $booking, [
            'suggestions_created' => $matches->count(),
            'booking_id' => $booking->id,
            'mode' => $mode,
        ]);

        if ($mode === 'auto') {
            $best = $matches->first();
            $booking->update(['tutor_id' => $best->id]);
            TutorMatchSuggestion::where('booking_id', $booking->id)
                ->where('tutor_id', $best->id)
                ->update(['status' => 'assigned']);

            HrAuditEvent::log('match_assign', null, $best, $booking, [
                'auto' => true,
                'match_score' => $best->match_score ?? null,
                'breakdown' => $best->match_breakdown ?? null,
            ]);

            return $best;
        }

        return null;
    }

    public function assignSuggestion(TutorMatchSuggestion $suggestion, ?User $actor = null): Booking
    {
        $booking = $suggestion->booking;
        if (! $booking) {
            throw new \RuntimeException('Suggestion has no booking.');
        }

        $booking->update([
            'tutor_id' => $suggestion->tutor_id,
            'status' => $booking->status === 'pending' ? 'confirmed' : $booking->status,
        ]);
        $suggestion->update(['status' => 'assigned']);

        TutorMatchSuggestion::where('booking_id', $booking->id)
            ->where('id', '!=', $suggestion->id)
            ->whereIn('status', ['pending', 'suggested'])
            ->update(['status' => 'dismissed']);

        HrAuditEvent::log('match_assign', $actor, $suggestion->tutor, $suggestion, [
            'booking_id' => $booking->id,
            'match_score' => $suggestion->match_score,
            'breakdown' => $suggestion->score_breakdown,
        ]);

        return $booking->fresh(['tutor', 'student', 'course']);
    }

    public function dismissSuggestion(TutorMatchSuggestion $suggestion, ?User $actor = null): TutorMatchSuggestion
    {
        $suggestion->update(['status' => 'dismissed']);
        HrAuditEvent::log('match_dismiss', $actor, $suggestion->tutor, $suggestion, [
            'booking_id' => $suggestion->booking_id,
            'course_id' => $suggestion->course_id,
        ]);

        return $suggestion->fresh();
    }

    private function tryAutoAssignOpenBooking(int $courseId, User $tutor): void
    {
        $booking = Booking::query()
            ->where('course_id', $courseId)
            ->whereNull('tutor_id')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now());
            })
            ->orderBy('scheduled_at')
            ->first();

        if (! $booking) {
            return;
        }

        // Never override manual assignment
        if ($booking->tutor_id) {
            return;
        }

        $booking->update(['tutor_id' => $tutor->id]);
        TutorMatchSuggestion::where('tutor_id', $tutor->id)
            ->where('course_id', $courseId)
            ->whereNull('booking_id')
            ->update(['status' => 'assigned', 'booking_id' => $booking->id]);
    }
}
