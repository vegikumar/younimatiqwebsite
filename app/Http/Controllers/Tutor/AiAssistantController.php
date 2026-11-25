<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\AiParentNote;
use App\Models\AiSessionRecap;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AiTeachingAssistantService;
use App\Services\EmailService;
use App\Services\ParentalAiGate;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();

        $courses = Course::where('tutor_id', $tutorId)->with(['enrollments.user'])->get();
        $recentNotes = AiParentNote::where('tutor_id', $tutorId)->with(['student', 'course'])->latest()->get();
        $recaps = AiSessionRecap::where('tutor_id', $tutorId)->with('course')->latest()->get();

        return \App\Support\InertiaPage::react('Tutor/AiAssistant', [
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::aiCourseWithStudents($c))->values()->all(),
            'recentNotes' => $recentNotes->map(fn ($n) => TutorInertiaProps::aiParentNote($n))->values()->all(),
            'recaps' => $recaps->map(fn ($r) => TutorInertiaProps::aiSessionRecap($r))->values()->all(),
            'pendingRecapCount' => $recaps->where('status', 'pending_review')->count(),
            'parentNoteUrl' => route('tutor.ai-assistant.parent-note'),
            'projectReviewUrl' => route('tutor.ai-assistant.project-review'),
        ]);
    }

    public function draftParentNote(Request $request, AiTeachingAssistantService $aiService)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:users,id',
            'raw_bullets' => 'required|string',
        ]);

        if (! app(ParentalAiGate::class)->allowsForStudent((int) $request->student_id)) {
            return redirect()->back()->with('error', 'A parent has turned off AI-assisted updates for this student.');
        }

        $aiService->generateParentNote(
            Auth::guard('tutor')->id(),
            $request->course_id,
            $request->student_id,
            $request->raw_bullets
        );

        return redirect()->back()->with('success', 'AI Parent Update & Homework drafted successfully!');
    }

    public function reviewRecap(Request $request, AiSessionRecap $recap)
    {
        if ($recap->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'edited_summary' => 'required|string',
            'action' => 'required|in:save,send,skip',
        ]);

        if ($request->action === 'skip') {
            $recap->update(['status' => 'skipped']);
            return redirect()->back()->with('success', 'Session recap skipped.');
        }

        $sending = $request->action === 'send';
        $recap->update([
            'edited_summary' => $request->edited_summary,
            'status' => $sending ? 'sent' : 'pending_review',
            'sent_at' => $sending ? now() : null,
        ]);

        if ($sending) {
            $this->emailParentsSessionSummary($recap->fresh(['course']));
        }

        return redirect()->back()->with('success', $sending ? 'Session recap sent to parents!' : 'Session recap saved.');
    }

    private function emailParentsSessionSummary(AiSessionRecap $recap): void
    {
        $summary = $recap->edited_summary ?: $recap->transcript_summary;
        if (! $summary || ! $recap->course_id) {
            return;
        }

        $studentIds = Enrollment::where('course_id', $recap->course_id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('user_id')
            ->unique()
            ->all();

        if (empty($studentIds)) {
            return;
        }

        $gate = app(ParentalAiGate::class);
        $parents = $gate->parentsAllowedForStudents($studentIds);

        if ($parents->isEmpty()) {
            // Fallback: student emails only when parental AI is allowed (or no parent link)
            $students = User::whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                if (! $student->email || ! $gate->allowsForStudent($student->id)) {
                    continue;
                }
                try {
                    app(EmailService::class)->sendCustomEmail(
                        $student->email,
                        'Class session summary — '.($recap->course?->title ?? 'your course'),
                        '<p>Hi '.e($student->name).',</p><p>Here is a short summary from your recent class:</p><div>'.nl2br(e($summary)).'</div>'
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed student recap email: '.$e->getMessage());
                }
            }

            return;
        }

        $courseTitle = $recap->course?->title ?? 'your child\'s course';
        foreach ($parents as $parent) {
            if (! $parent->email) {
                continue;
            }
            try {
                app(EmailService::class)->sendCustomEmail(
                    $parent->email,
                    'Session summary — '.$courseTitle,
                    '<p>Hi '.e($parent->name).',</p>'
                    .'<p>Your tutor shared a short summary from a recent <strong>'.e($courseTitle).'</strong> session:</p>'
                    .'<div style="margin:16px 0;padding:12px;border-radius:8px;background:#f5f5f5;">'.nl2br(e($summary)).'</div>'
                    .'<p>Reply to your tutor or open the parent dashboard if you have questions.</p>'
                );
            } catch (\Throwable $e) {
                Log::warning('Failed parent recap email: '.$e->getMessage());
            }
        }
    }

    public function reviewProject(Request $request, AiTeachingAssistantService $aiService)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:users,id',
            'project_title' => 'required|string',
            'code_content' => 'nullable|string',
        ]);

        $review = $aiService->generateProjectReview(
            $request->student_id,
            $request->course_id,
            $request->project_title,
            $request->code_content
        );

        return redirect()->back()->with('success', 'AI Project Progress Review generated!');
    }
}
