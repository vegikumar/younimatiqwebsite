<?php

namespace App\Services;

use App\Models\HrAuditEvent;
use App\Models\TutorSkill;
use App\Models\TutorTrainingAssignment;
use App\Models\TutorTrainingItem;
use App\Models\TutorTrainingModule;
use App\Models\TutorTrainingProgress;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TutorTrainingService
{
    public function assignModule(User $tutor, TutorTrainingModule $module, ?User $assigner = null): TutorTrainingAssignment
    {
        return TutorTrainingAssignment::updateOrCreate(
            ['tutor_id' => $tutor->id, 'module_id' => $module->id],
            [
                'assigned_by' => $assigner?->id,
                'assigned_at' => now(),
            ]
        );
    }

    public function markVideoComplete(User $tutor, TutorTrainingItem $item): TutorTrainingProgress
    {
        $progress = TutorTrainingProgress::updateOrCreate(
            ['tutor_id' => $tutor->id, 'item_id' => $item->id],
            [
                'status' => 'passed',
                'completed_at' => now(),
            ]
        );
        $this->maybeGrantModuleSkills($tutor, $item->module);

        return $progress;
    }

    public function markDocumentComplete(User $tutor, TutorTrainingItem $item): TutorTrainingProgress
    {
        $progress = TutorTrainingProgress::updateOrCreate(
            ['tutor_id' => $tutor->id, 'item_id' => $item->id],
            [
                'status' => 'passed',
                'completed_at' => now(),
            ]
        );
        $this->maybeGrantModuleSkills($tutor, $item->module);

        return $progress;
    }

    public function submitAssignment(User $tutor, TutorTrainingItem $item, ?string $text, ?string $path): TutorTrainingProgress
    {
        $existing = TutorTrainingProgress::where('tutor_id', $tutor->id)
            ->where('item_id', $item->id)
            ->first();

        $status = ($existing && in_array($existing->status, ['revision_requested', 'rejected'], true))
            ? 'resubmitted'
            : 'submitted';

        $progress = TutorTrainingProgress::updateOrCreate(
            ['tutor_id' => $tutor->id, 'item_id' => $item->id],
            [
                'status' => $status,
                'submission_text' => $text,
                'submission_path' => $path ?? $existing?->submission_path,
                'submitted_at' => now(),
                'completed_at' => null,
                'review_notes' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        // Move into under_review once HR queue picks it up; mark for queue immediately
        $progress->update(['status' => 'under_review']);

        $this->notifyHrOfSubmission($tutor, $item, $progress);

        return $progress->fresh();
    }

    public function gradeQuiz(User $tutor, TutorTrainingItem $item, array $answers): TutorTrainingProgress
    {
        $questions = $item->quiz_questions ?? [];
        $total = max(count($questions), 1);
        $correct = 0;
        foreach ($questions as $i => $q) {
            $expected = $q['answer'] ?? null;
            if (isset($answers[$i]) && (string) $answers[$i] === (string) $expected) {
                $correct++;
            }
        }
        $score = (int) round(($correct / $total) * 100);
        $passed = $score >= (int) ($item->pass_score ?? 70);

        $progress = TutorTrainingProgress::updateOrCreate(
            ['tutor_id' => $tutor->id, 'item_id' => $item->id],
            [
                'status' => $passed ? 'passed' : 'failed',
                'score' => $score,
                'quiz_answers' => $answers,
                'completed_at' => $passed ? now() : null,
            ]
        );

        if ($passed) {
            $this->maybeGrantModuleSkills($tutor, $item->module);
        }

        return $progress;
    }

    public function approveSubmission(TutorTrainingProgress $progress, User $reviewer, ?string $notes = null, ?int $score = null): TutorTrainingProgress
    {
        $progress->update([
            'status' => 'passed',
            'score' => $score ?? $progress->score,
            'review_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => now(),
        ]);

        $progress->loadMissing(['tutor', 'item.module']);
        $this->maybeGrantModuleSkills($progress->tutor, $progress->item->module);
        $this->emailTutorReviewResult($progress, true);

        HrAuditEvent::log('training_approve', $reviewer, $progress->tutor, $progress, [
            'score' => $score ?? $progress->score,
            'notes' => $notes,
            'item_id' => $progress->item_id,
        ]);

        return $progress->fresh();
    }

    public function rejectSubmission(TutorTrainingProgress $progress, User $reviewer, ?string $notes = null): TutorTrainingProgress
    {
        $progress->update([
            'status' => 'rejected',
            'review_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => null,
        ]);

        $progress->loadMissing('tutor');
        $this->emailTutorReviewResult($progress, false);

        HrAuditEvent::log('training_reject', $reviewer, $progress->tutor, $progress, [
            'notes' => $notes,
            'item_id' => $progress->item_id,
        ]);

        return $progress->fresh();
    }

    public function requestRevision(TutorTrainingProgress $progress, User $reviewer, ?string $notes = null): TutorTrainingProgress
    {
        $progress->update([
            'status' => 'revision_requested',
            'review_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => null,
        ]);

        $progress->loadMissing('tutor');
        $this->emailTutorReviewResult($progress, false, true);

        HrAuditEvent::log('training_revision', $reviewer, $progress->tutor, $progress, [
            'notes' => $notes,
            'item_id' => $progress->item_id,
        ]);

        return $progress->fresh();
    }

    public function maybeGrantModuleSkills(User $tutor, TutorTrainingModule $module): void
    {
        $module->loadMissing('items', 'skillRewards');
        $itemIds = $module->items->pluck('id');
        if ($itemIds->isEmpty()) {
            return;
        }

        $passedCount = TutorTrainingProgress::where('tutor_id', $tutor->id)
            ->whereIn('item_id', $itemIds)
            ->where('status', 'passed')
            ->count();

        if ($passedCount < $itemIds->count()) {
            return;
        }

        $newlyVerified = [];
        foreach ($module->skillRewards as $skill) {
            $record = TutorSkill::updateOrCreate(
                ['tutor_id' => $tutor->id, 'skill_id' => $skill->id],
                [
                    'source' => 'training',
                    'verified' => true,
                    'earned_at' => now(),
                    'expires_at' => now()->addYear(),
                ]
            );
            $newlyVerified[] = $record;
            HrAuditEvent::log('skill_grant', null, $tutor, $record, [
                'skill_id' => $skill->id,
                'source' => 'training',
                'expires_at' => $record->expires_at?->toIso8601String(),
            ]);
        }

        if (! empty($newlyVerified)) {
            try {
                $tutor->load('tutorSkills');
                app(TutorMatchingService::class)->onSkillsVerified($tutor, collect($newlyVerified)->pluck('skill_id')->all());
            } catch (\Throwable $e) {
                Log::warning('Match after skill verify failed: '.$e->getMessage());
            }
        }
    }

    public function grantSkill(User $tutor, int $skillId, string $source = 'admin', bool $verified = true, ?User $actor = null): TutorSkill
    {
        $skill = TutorSkill::updateOrCreate(
            ['tutor_id' => $tutor->id, 'skill_id' => $skillId],
            [
                'source' => $source,
                'verified' => $verified,
                'earned_at' => now(),
                'expires_at' => $verified ? now()->addYear() : null,
            ]
        );

        HrAuditEvent::log('skill_grant', $actor, $tutor, $skill, [
            'skill_id' => $skillId,
            'source' => $source,
            'verified' => $verified,
            'expires_at' => $skill->expires_at?->toIso8601String(),
        ]);

        if ($verified) {
            try {
                $tutor->load('tutorSkills');
                app(TutorMatchingService::class)->onSkillsVerified($tutor, [$skillId]);
            } catch (\Throwable $e) {
                Log::warning('Match after grantSkill failed: '.$e->getMessage());
            }
        }

        return $skill;
    }

    private function notifyHrOfSubmission(User $tutor, TutorTrainingItem $item, TutorTrainingProgress $progress): void
    {
        try {
            $item->loadMissing('module');
            $subject = 'Tutor training submission awaiting review';
            $message = sprintf(
                '<p>Hi HR,</p><p><strong>%s</strong> (%s) submitted assignment <strong>%s</strong> in module <strong>%s</strong>.</p><p><a href="%s">Open HR review queue</a></p>',
                e($tutor->name),
                e($tutor->email ?? ''),
                e($item->title ?? 'Assignment'),
                e($item->module?->title ?? 'Training'),
                e(route('admin.tutor-training.reviews'))
            );

            // Same Admin-panel SMTP; To: is hr@younimatiq.com (and any extra HR emails)
            $emailService = app(EmailService::class);
            $emailService->applyAdminSmtpConfig();
            $emailService->notifyHr($subject, $message);

            // In-app bell for admins (does not replace the HR email)
            try {
                $admins = User::where('role', 'admin')->where('status', 'active')->get();
                foreach ($admins as $admin) {
                    app(NotificationService::class)->notify(
                        $admin,
                        $subject,
                        sprintf('%s submitted %s — awaiting HR review', $tutor->name, $item->title ?? 'assignment'),
                        route('admin.tutor-training.reviews'),
                        'admin'
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('HR in-app notify failed: '.$e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning('HR submission email failed (submission kept): '.$e->getMessage());
        }
    }

    private function emailTutorReviewResult(TutorTrainingProgress $progress, bool $approved, bool $revision = false): void
    {
        try {
            $tutor = $progress->tutor;
            if (! $tutor?->email) {
                return;
            }
            $progress->loadMissing('item.module');
            $title = $progress->item?->title ?? 'Assignment';
            if ($approved) {
                $subject = 'Training assignment approved';
                $message = "<p>Hi {$tutor->name},</p><p>Your submission for <strong>{$title}</strong> was approved.</p>";
            } elseif ($revision) {
                $subject = 'Training assignment needs revision';
                $notes = e($progress->review_notes ?? 'Please revise and resubmit.');
                $message = "<p>Hi {$tutor->name},</p><p>Please revise <strong>{$title}</strong>.</p><p>Notes: {$notes}</p>";
            } else {
                $subject = 'Training assignment rejected';
                $notes = e($progress->review_notes ?? '');
                $message = "<p>Hi {$tutor->name},</p><p>Your submission for <strong>{$title}</strong> was rejected.</p><p>{$notes}</p>";
            }

            app(EmailService::class)->sendCustomEmail($tutor->email, $subject, $message);
        } catch (\Throwable $e) {
            Log::warning('Tutor review result email failed: '.$e->getMessage());
        }
    }

}
