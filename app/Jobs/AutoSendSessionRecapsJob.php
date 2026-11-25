<?php

namespace App\Jobs;

use App\Models\AiSessionRecap;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EmailService;
use App\Services\ParentalAiGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutoSendSessionRecapsJob implements ShouldQueue
{
    use Queueable;

    public function handle(ParentalAiGate $gate, EmailService $emailService): void
    {
        $due = AiSessionRecap::query()
            ->where('status', 'pending_review')
            ->whereNotNull('auto_send_at')
            ->where('auto_send_at', '<=', now())
            ->where('red_flag', false)
            ->with('course')
            ->limit(50)
            ->get();

        foreach ($due as $recap) {
            try {
                $summary = $recap->edited_summary ?: $recap->transcript_summary;
                if (! $summary || ! $recap->course_id) {
                    $recap->update(['status' => 'skipped']);
                    continue;
                }

                $studentIds = Enrollment::where('course_id', $recap->course_id)
                    ->whereIn('status', ['active', 'completed'])
                    ->pluck('user_id')
                    ->unique()
                    ->all();

                $parents = $gate->parentsAllowedForStudents($studentIds);
                $courseTitle = $recap->course?->title ?? 'your child\'s course';

                foreach ($parents as $parent) {
                    if (! $parent->email) {
                        continue;
                    }
                    $emailService->sendCustomEmail(
                        $parent->email,
                        'Session summary — '.$courseTitle,
                        '<p>Hi '.e($parent->name).',</p>'
                        .'<p>Here is an automatic summary from a recent <strong>'.e($courseTitle).'</strong> session:</p>'
                        .'<div style="margin:16px 0;padding:12px;border-radius:8px;background:#f5f5f5;">'.nl2br(e($summary)).'</div>'
                    );
                }

                if ($parents->isEmpty()) {
                    $students = User::whereIn('id', $studentIds)->get();
                    foreach ($students as $student) {
                        if (! $student->email || ! $gate->allowsForStudent($student->id)) {
                            continue;
                        }
                        $emailService->sendCustomEmail(
                            $student->email,
                            'Class session summary — '.$courseTitle,
                            '<p>Hi '.e($student->name).',</p><p>Here is a short summary from your recent class:</p><div>'.nl2br(e($summary)).'</div>'
                        );
                    }
                }

                $recap->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('AutoSendSessionRecapsJob failed for #'.$recap->id.': '.$e->getMessage());
            }
        }
    }
}
