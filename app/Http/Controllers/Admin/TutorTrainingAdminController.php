<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\TutorTrainingAssignment;
use App\Models\TutorTrainingItem;
use App\Models\TutorTrainingModule;
use App\Models\User;
use App\Services\EmailService;
use App\Services\TutorTrainingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TutorTrainingAdminController extends Controller
{
    public function index()
    {
        $modules = TutorTrainingModule::with(['items', 'skillRewards'])->orderBy('sort_order')->get();
        $skills = Skill::orderBy('name')->get();
        // Only approved tutors (pipeline APPROVED) for assignment — full list, not truncated.
        $tutors = User::query()
            ->where('role', 'tutor')
            ->where(function ($q) {
                $q->whereHas('tutorProfile', fn ($p) => $p->where('pipeline_status', 'APPROVED'))
                    ->orWhere(function ($q2) {
                        // Legacy tutors with no profile row but already active
                        $q2->where('status', 'active')
                            ->whereDoesntHave('tutorProfile');
                    });
            })
            ->with('tutorProfile:id,user_id,pipeline_status')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        $assignedByModule = TutorTrainingAssignment::query()
            ->whereIn('module_id', $modules->pluck('id'))
            ->get(['module_id', 'tutor_id'])
            ->groupBy('module_id')
            ->map(fn ($rows) => $rows->pluck('tutor_id')->map(fn ($id) => (int) $id)->values()->all());

        return \App\Support\InertiaPage::react('Admin/TutorTraining/Index', [
            'skills' => $skills->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'tutors' => $tutors->map(fn (User $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'status' => $t->status,
                'pipelineStatus' => $t->tutorProfile?->pipeline_status ?? 'APPROVED',
            ])->values()->all(),
            'modules' => $modules->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'courseName' => $m->course_name,
                'className' => $m->class_name,
                'classNumber' => $m->class_number,
                'description' => $m->description,
                'destroyUrl' => route('admin.tutor-training.modules.destroy', $m),
                'addItemUrl' => route('admin.tutor-training.items.store', $m),
                'assignUrl' => route('admin.tutor-training.modules.assign', $m),
                'assignedTutorIds' => $assignedByModule[$m->id] ?? [],
                'skillRewards' => $m->skillRewards->map(fn ($s) => $s->name)->values()->all(),
                'items' => $m->items->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'videoUrl' => $item->video_url,
                    'documents' => $item->resolvedDocuments(),
                    'passScore' => $item->pass_score,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function assignModule(Request $request, TutorTrainingModule $module, TutorTrainingService $trainingService)
    {
        $data = $request->validate([
            'tutor_ids' => 'nullable|array',
            'tutor_ids.*' => 'integer|exists:users,id',
        ]);

        // Exact IDs from the form — cast to int so diffs/emails never mix string/int.
        $requestedIds = collect($data['tutor_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $tutors = User::query()
            ->where('role', 'tutor')
            ->whereIn('id', $requestedIds)
            ->where(function ($q) {
                $q->whereHas('tutorProfile', fn ($p) => $p->where('pipeline_status', 'APPROVED'))
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'active')->whereDoesntHave('tutorProfile');
                    });
            })
            ->get(['id', 'name', 'email', 'role']);

        // Preserve only tutors that passed the approved filter (still keyed by exact requested IDs).
        $tutorIds = $tutors->pluck('id')->map(fn ($id) => (int) $id)->values();

        $assigner = $request->user('admin') ?? $request->user();

        $alreadyAssigned = TutorTrainingAssignment::query()
            ->where('module_id', $module->id)
            ->pluck('tutor_id')
            ->map(fn ($id) => (int) $id);

        $newlyAssignedIds = $tutorIds->diff($alreadyAssigned)->values();
        $newlyAssignedTutors = $tutors->whereIn('id', $newlyAssignedIds)->values();

        // Sync: assign selected tutors; unassign tutors removed from this module.
        TutorTrainingAssignment::query()
            ->where('module_id', $module->id)
            ->when($tutorIds->isNotEmpty(), fn ($q) => $q->whereNotIn('tutor_id', $tutorIds->all()))
            ->delete();

        foreach ($tutors as $tutor) {
            $trainingService->assignModule($tutor, $module, $assigner);
        }

        $mailNote = '';
        if ($newlyAssignedTutors->isNotEmpty()) {
            try {
                // Pass User models only — never a loose ID list that could resolve wrong.
                app(EmailService::class)->notifyTutorTrainingAssigned($module, $newlyAssignedTutors->all());
                $recipients = $newlyAssignedTutors
                    ->map(fn (User $t) => trim(($t->name ?: 'Tutor').' <'.$t->email.'>'))
                    ->implode(', ');
                $mailNote = ' Email sent to: '.$recipients.'.';
                Log::info('Tutor training emails sent', [
                    'module_id' => $module->id,
                    'module' => $module->title,
                    'tutor_ids' => $newlyAssignedIds->all(),
                    'emails' => $newlyAssignedTutors->pluck('email')->all(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send tutor training assignment emails: '.$e->getMessage(), [
                    'module_id' => $module->id,
                    'tutor_ids' => $newlyAssignedIds->all(),
                ]);
                $mailNote = ' (Email notify failed — check logs.)';
            }
        }

        $count = $tutorIds->count();

        return back()->with(
            'success',
            $module->title.' assigned to '.$count.' tutor'.($count === 1 ? '' : 's').'.'.$mailNote
        );
    }

    public function storeModule(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'class_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'skill_ids' => 'nullable|array',
            'skill_ids.*' => 'exists:skills,id',
        ]);
        $module = TutorTrainingModule::create([
            'title' => $data['title'],
            'course_name' => $data['course_name'] ?? null,
            'class_name' => $data['class_name'] ?? null,
            'class_number' => $data['class_number'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) TutorTrainingModule::max('sort_order') + 1,
            'is_active' => true,
        ]);
        if (! empty($data['skill_ids'])) {
            $module->skillRewards()->sync($data['skill_ids']);
        }

        return back()->with('success', 'Module created.');
    }

    public function storeItem(Request $request, TutorTrainingModule $module)
    {
        $data = $request->validate([
            'type' => 'required|in:document,video,assignment,quiz',
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'document_urls' => 'nullable|array',
            'document_urls.*' => 'nullable|url|max:1000',
            'document_files' => 'nullable|array',
            'document_files.*' => 'nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,odt,ods,odp,txt,rtf,csv,png,jpg,jpeg,gif,webp,zip,mp4,webm,mov',
            // Legacy single-field names (kept for older forms / scripts).
            'document_url' => 'nullable|url',
            'document_file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,odt,ods,odp,txt,rtf,csv,png,jpg,jpeg,gif,webp,zip,mp4,webm,mov',
            'video_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:102400',
            'pass_score' => 'nullable|integer|min:1|max:100',
            'quiz_json' => 'nullable|string',
        ]);

        $documents = [];

        foreach ($data['document_urls'] ?? [] as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }
            $documents[] = [
                'kind' => 'url',
                'url' => $url,
                'label' => 'Open link',
            ];
        }

        if (! empty($data['document_url'])) {
            $documents[] = [
                'kind' => 'url',
                'url' => $data['document_url'],
                'label' => 'Open link',
            ];
        }

        $files = [];
        // Supports both document_files[] and document_files (browser / PHP variants).
        $uploaded = $request->file('document_files', []);
        if ($uploaded instanceof \Illuminate\Http\UploadedFile) {
            $uploaded = [$uploaded];
        }
        foreach ($uploaded as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                $files[] = $file;
            }
        }
        if ($request->hasFile('document_file')) {
            $files[] = $request->file('document_file');
        }

        foreach ($files as $file) {
            $path = $file->store('tutor-training/docs', 'public');
            $documents[] = [
                'kind' => 'file',
                'path' => $path,
                'label' => $file->getClientOriginalName() ?: basename($path),
            ];
        }

        // Keep first file/url mirrored on legacy columns for older readers.
        $firstFile = collect($documents)->firstWhere('kind', 'file');
        $firstUrl = collect($documents)->firstWhere('kind', 'url');

        $videoPath = null;
        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('tutor-training', 'public');
        }

        $questions = null;
        if ($data['type'] === 'quiz' && ! empty($data['quiz_json'])) {
            $questions = json_decode($data['quiz_json'], true);
        }

        TutorTrainingItem::create([
            'module_id' => $module->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'document_url' => $firstUrl['url'] ?? null,
            'document_path' => $firstFile['path'] ?? null,
            'documents' => $documents ?: null,
            'video_url' => $data['video_url'] ?? null,
            'video_path' => $videoPath,
            'quiz_questions' => $questions,
            'pass_score' => $data['pass_score'] ?? 70,
            'sort_order' => (int) $module->items()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Training item added.');
    }

    public function destroyModule(TutorTrainingModule $module)
    {
        $module->delete();

        return back()->with('success', 'Module deleted.');
    }
}
