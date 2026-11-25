<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\TutorProfile;
use App\Models\TutorTrainingModule;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TutorTrainingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorPipelineController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $tutors = User::where('role', 'tutor')
            ->with(['tutorProfile', 'tutorSkills.skill', 'courses'])
            ->withCount('courses')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->get();

        $columns = [];
        foreach (TutorProfile::PIPELINE as $status) {
            $columns[$status] = $tutors->filter(function (User $t) use ($status) {
                $pipe = $t->tutorProfile?->pipeline_status;
                if (! $pipe) {
                    // Legacy tutors without profile
                    if ($status === 'APPROVED' && $t->status === 'active') {
                        return true;
                    }
                    if ($status === 'NEW' && $t->status === 'pending') {
                        return true;
                    }
                    if ($status === 'REJECTED' && $t->status === 'suspended') {
                        return true;
                    }

                    return false;
                }

                return $pipe === $status;
            })->values();
        }

        $modules = TutorTrainingModule::where('is_active', true)->orderBy('sort_order')->get(['id', 'title']);
        $skills = Skill::orderBy('name')->get(['id', 'name']);
        $stats = [
            'total' => $tutors->count(),
            'active' => $tutors->where('status', 'active')->count(),
            'pending' => $tutors->where('status', 'pending')->count(),
            'suspended' => $tutors->where('status', 'suspended')->count(),
        ];

        $serializeTutor = function (User $t) {
            $subs = $t->tutorProfile?->subject_verticals ?? [];
            $photo = $t->tutorProfile?->profile_photo_path;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'status' => $t->status,
                'timezone' => $t->timezone,
                'photo_url' => $photo ? asset('storage/'.$photo) : null,
                'subject_vertical' => $subs[0] ?? null,
                'location' => $t->tutorProfile?->city_country,
                'created_label' => $t->created_at?->format('M j'),
            ];
        };

        $serializedColumns = [];
        foreach ($columns as $key => $collection) {
            $serializedColumns[$key] = $collection->map($serializeTutor)->values()->all();
        }

        return \App\Support\InertiaPage::react('Admin/Tutors', [
            'columns' => $serializedColumns,
            'search' => $search,
            'stats' => $stats,
            'modules' => $modules->map(fn ($m) => ['id' => $m->id, 'title' => $m->title])->values()->all(),
            'skills' => $skills->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'timezones' => collect(\App\Services\TimezoneService::commonTimezones())
                ->map(fn ($label, $id) => ['id' => $id, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }

    public function updateStatus(Request $request, User $user, TutorTrainingService $trainingService)
    {
        abort_unless($user->role === 'tutor', 404);

        $data = $request->validate([
            'pipeline_status' => 'required|in:'.implode(',', TutorProfile::PIPELINE),
            'reviewer_notes' => 'nullable|string|max:5000',
        ]);

        $profile = $user->tutorProfile;
        if (! $profile) {
            $profile = TutorProfile::create([
                'user_id' => $user->id,
                'pipeline_status' => 'NEW',
            ]);
        }

        $profile->pipeline_status = $data['pipeline_status'];
        if (array_key_exists('reviewer_notes', $data)) {
            $profile->reviewer_notes = $data['reviewer_notes'];
        }
        $profile->save();

        if ($data['pipeline_status'] === 'APPROVED') {
            $user->update(['status' => 'active']);
            try {
                app(NotificationService::class)->notify(
                    $user,
                    'Tutor application approved',
                    'Your Younimatiq tutor account is now active. Complete assigned training to unlock skills.',
                    route('tutor.dashboard'),
                    'success'
                );
            } catch (\Throwable $e) {
            }
        } elseif ($data['pipeline_status'] === 'REJECTED') {
            $user->update(['status' => 'suspended']);
        } elseif (in_array($data['pipeline_status'], ['NEW', 'UNDER_REVIEW', 'VIDEO_REVIEWED', 'INTERVIEW'], true)) {
            if ($user->status === 'active') {
                // keep active if already approved before, else pending
            } else {
                $user->update(['status' => 'pending']);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pipeline_status' => $profile->pipeline_status, 'user_status' => $user->fresh()->status]);
        }

        return back()->with('success', 'Tutor pipeline updated.');
    }

    public function assignSkills(Request $request, User $user, TutorTrainingService $trainingService)
    {
        abort_unless($user->role === 'tutor', 404);
        $data = $request->validate([
            'skill_ids' => 'required|array|min:1',
            'skill_ids.*' => 'exists:skills,id',
            'verified' => 'nullable|boolean',
        ]);
        foreach ($data['skill_ids'] as $skillId) {
            $trainingService->grantSkill($user, (int) $skillId, 'admin', $request->boolean('verified', true));
        }

        return back()->with('success', 'Skills assigned.');
    }

    public function assignTraining(Request $request, User $user, TutorTrainingService $trainingService)
    {
        abort_unless($user->role === 'tutor', 404);
        $data = $request->validate([
            'module_ids' => 'required|array|min:1',
            'module_ids.*' => 'exists:tutor_training_modules,id',
        ]);

        $assigner = $request->user('admin') ?? $request->user();
        $tutorId = (int) $user->id;
        $already = \App\Models\TutorTrainingAssignment::query()
            ->where('tutor_id', $tutorId)
            ->pluck('module_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $newlyAssignedModules = [];

        foreach ($data['module_ids'] as $moduleId) {
            $module = TutorTrainingModule::find((int) $moduleId);
            if (! $module) {
                continue;
            }
            $trainingService->assignModule($user, $module, $assigner);
            if (! in_array((int) $module->id, $already, true)) {
                $newlyAssignedModules[] = $module;
            }
        }

        $mailNote = '';
        if ($newlyAssignedModules !== [] && filled($user->email)) {
            try {
                $emailService = app(\App\Services\EmailService::class);
                // Always the same tutor from this pipeline action — never a different user.
                foreach ($newlyAssignedModules as $module) {
                    $emailService->notifyTutorTrainingAssigned($module, [$user]);
                }
                $mailNote = ' Email sent to '.($user->name ?: 'tutor').' <'.$user->email.'>.';
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send tutor training emails from pipeline: '.$e->getMessage(), [
                    'tutor_id' => $tutorId,
                ]);
                $mailNote = ' (Email notify failed — check logs.)';
            }
        }

        return back()->with('success', 'Training modules assigned.'.$mailNote);
    }

    public function show(User $user)
    {
        abort_unless($user->role === 'tutor', 404);
        $user->load(['tutorProfile', 'tutorSkills.skill', 'trainingAssignments.module']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'timezone' => $user->timezone,
            'profile' => $user->tutorProfile,
            'skills' => $user->tutorSkills,
            'photo_url' => $user->tutorProfile?->profile_photo_path
                ? Storage::disk('public')->url($user->tutorProfile->profile_photo_path)
                : null,
            'video_url' => $user->tutorProfile?->intro_video_path
                ? Storage::disk('public')->url($user->tutorProfile->intro_video_path)
                : $user->intro_video_url,
        ]);
    }
}
