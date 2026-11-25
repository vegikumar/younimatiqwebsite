<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorTrainingProgress;
use App\Services\TutorTrainingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorTrainingReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'under_review');
        $allowed = ['under_review', 'submitted', 'resubmitted', 'revision_requested', 'passed', 'rejected', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'under_review';
        }

        $query = TutorTrainingProgress::query()
            ->with(['tutor:id,name,email', 'item.module', 'reviewer:id,name'])
            ->whereHas('item', fn ($q) => $q->where('type', 'assignment'))
            ->latest('submitted_at')
            ->latest('updated_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested', 'passed', 'rejected']);
        }

        $rows = $query->paginate(25)->withQueryString();

        $payload = $rows->getCollection()->map(function (TutorTrainingProgress $p) {
            return [
                'id' => $p->id,
                'status' => $p->status,
                'score' => $p->score,
                'submission_text' => $p->submission_text,
                'submission_url' => $p->submission_path ? Storage::disk('public')->url($p->submission_path) : null,
                'review_notes' => $p->review_notes,
                'submitted_at_label' => optional($p->submitted_at ?? $p->updated_at)?->format('M j, Y g:i A'),
                'reviewed_at_label' => optional($p->reviewed_at)?->format('M j, Y g:i A'),
                'tutor' => [
                    'id' => $p->tutor?->id,
                    'name' => $p->tutor?->name,
                    'email' => $p->tutor?->email,
                ],
                'item_title' => $p->item?->title,
                'module_title' => $p->item?->module?->title,
                'reviewer_name' => $p->reviewer?->name,
            ];
        })->values()->all();

        return \App\Support\InertiaPage::react('Admin/TutorTraining/Reviews', [
            'submissions' => $payload,
            'pagination' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
            'filters' => ['status' => $status],
            'counts' => [
                'under_review' => TutorTrainingProgress::where('status', 'under_review')->whereHas('item', fn ($q) => $q->where('type', 'assignment'))->count(),
                'revision_requested' => TutorTrainingProgress::where('status', 'revision_requested')->whereHas('item', fn ($q) => $q->where('type', 'assignment'))->count(),
            ],
        ]);
    }

    public function approve(Request $request, TutorTrainingProgress $progress, TutorTrainingService $service)
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:5000',
            'score' => 'nullable|integer|min:0|max:100',
        ]);
        $service->approveSubmission($progress, $request->user('admin'), $data['notes'] ?? null, $data['score'] ?? null);

        return back()->with('success', 'Submission approved. Skills granted when the full module is complete.');
    }

    public function reject(Request $request, TutorTrainingProgress $progress, TutorTrainingService $service)
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);
        $service->rejectSubmission($progress, $request->user('admin'), $data['notes'] ?? null);

        return back()->with('success', 'Submission rejected.');
    }

    public function requestRevision(Request $request, TutorTrainingProgress $progress, TutorTrainingService $service)
    {
        $data = $request->validate([
            'notes' => 'required|string|max:5000',
        ]);
        $service->requestRevision($progress, $request->user('admin'), $data['notes']);

        return back()->with('success', 'Revision requested.');
    }
}
