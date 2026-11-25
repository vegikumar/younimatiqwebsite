<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\TutorTrainingAssignment;
use App\Models\TutorTrainingItem;
use App\Models\TutorTrainingProgress;
use App\Services\TutorTrainingService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $tutor = $request->user('tutor');
        $assignments = TutorTrainingAssignment::with(['module.items'])
            ->where('tutor_id', $tutor->id)
            ->latest()
            ->get();
        $progress = TutorTrainingProgress::where('tutor_id', $tutor->id)->get()->keyBy('item_id');
        $skills = $tutor->tutorSkills()->with('skill')->get();

        return \App\Support\InertiaPage::react('Tutor/Training/Index', [
            'skills' => TutorInertiaProps::tutorSkills($skills),
            'assignments' => TutorInertiaProps::trainingAssignments($assignments, $progress),
        ]);
    }

    public function completeVideo(Request $request, TutorTrainingItem $item, TutorTrainingService $service)
    {
        $tutor = $request->user('tutor');
        $this->assertAssigned($tutor->id, $item);
        $service->markVideoComplete($tutor, $item);

        return back()->with('success', 'Video marked complete.');
    }

    public function completeDocument(Request $request, TutorTrainingItem $item, TutorTrainingService $service)
    {
        $tutor = $request->user('tutor');
        $this->assertAssigned($tutor->id, $item);
        $service->markDocumentComplete($tutor, $item);

        return back()->with('success', 'Marked completed.');
    }

    public function submitAssignment(Request $request, TutorTrainingItem $item, TutorTrainingService $service)
    {
        $tutor = $request->user('tutor');
        $this->assertAssigned($tutor->id, $item);
        $data = $request->validate([
            'submission_text' => 'nullable|string|max:10000',
            'submission_file' => 'nullable|file|max:20480',
        ]);
        $path = null;
        if ($request->hasFile('submission_file')) {
            $path = $request->file('submission_file')->store('tutor-training/submissions/'.$tutor->id, 'public');
        }
        $service->submitAssignment($tutor, $item, $data['submission_text'] ?? null, $path);

        return back()->with('success', 'Assignment submitted for HR review.');
    }

    public function submitQuiz(Request $request, TutorTrainingItem $item, TutorTrainingService $service)
    {
        $tutor = $request->user('tutor');
        $this->assertAssigned($tutor->id, $item);
        $answers = $request->input('answers', []);
        $progress = $service->gradeQuiz($tutor, $item, is_array($answers) ? $answers : []);

        $msg = $progress->status === 'passed'
            ? "Quiz passed ({$progress->score}%). Skills unlock when the whole module is complete."
            : "Quiz score {$progress->score}%. Need {$item->pass_score}% to pass.";

        return back()->with($progress->status === 'passed' ? 'success' : 'error', $msg);
    }

    private function assertAssigned(int $tutorId, TutorTrainingItem $item): void
    {
        $ok = TutorTrainingAssignment::where('tutor_id', $tutorId)
            ->where('module_id', $item->module_id)
            ->exists();
        abort_unless($ok, 403);
    }
}
