<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudentMilestone;
use App\Models\StudentPortfolio;
use App\Models\StudentReward;
use App\Services\SkillTreeService;
use App\Support\StudentInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillTreeController extends Controller
{
    public function show(Course $course, SkillTreeService $skillTreeService)
    {
        $studentId = Auth::id();
        $treeData = $skillTreeService->getStudentSkillTree($studentId, $course->id);

        $badges = StudentReward::with('badge')
            ->where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->whereNotNull('badge_id')
            ->latest('awarded_at')
            ->get()
            ->pluck('badge')
            ->filter()
            ->unique('id')
            ->values();

        $treeData['portfolios'] = collect($treeData['portfolios'] ?? [])->map(fn ($p) => [
            'id' => $p->id,
            'project_name' => $p->project_name,
            'project_url' => $p->project_url,
            'screenshot_url' => $p->screenshot_url,
            'visibility' => $p->visibility ?? 'private',
            'status' => $p->status ?? 'draft',
            'tutor_feedback' => $p->tutor_feedback,
        ])->values()->all();

        $treeData['nodes'] = collect($treeData['nodes'] ?? [])->values()->all();

        $milestones = StudentMilestone::with('badge')
            ->where('user_id', $studentId)
            ->where('course_id', $course->id)
            ->latest('unlocked_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->badge?->title ?? 'Milestone',
                'icon' => $m->badge?->badge_icon ?? 'workspace_premium',
                'unlocked_at_label' => optional($m->unlocked_at)?->format('M j, Y'),
                'certificate_hash' => $m->certificate_hash,
            ])->values()->all();

        return \App\Support\InertiaPage::react('Student/SkillTree', [
            'course' => StudentInertiaProps::courseBrief($course),
            'treeData' => $treeData,
            'badges' => $badges->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'icon' => $b->badge_icon])->values()->all(),
            'milestones' => $milestones,
        ]);
    }

    public function storePortfolio(Request $request, Course $course)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'project_url' => 'nullable|url',
            'screenshot_url' => 'nullable|url',
        ]);

        StudentPortfolio::create([
            'user_id' => Auth::id(),
            'course_id' => $course->id,
            'project_name' => $request->project_name,
            'project_url' => $request->project_url,
            'screenshot_url' => $request->screenshot_url,
            'visibility' => 'private',
            'status' => 'draft',
        ]);

        return redirect()->back()->with('success', 'Project saved privately. Share with parents when ready.');
    }

    public function sharePortfolio(Request $request, Course $course, StudentPortfolio $portfolio)
    {
        abort_unless($portfolio->user_id === Auth::id() && (int) $portfolio->course_id === (int) $course->id, 403);

        $data = $request->validate([
            'visibility' => 'required|in:private,parents,public',
        ]);

        $portfolio->update([
            'visibility' => $data['visibility'],
            'status' => $data['visibility'] === 'private' ? 'draft' : 'approved',
            'approved_at' => $data['visibility'] === 'private' ? null : now(),
        ]);

        return back()->with('success', $data['visibility'] === 'parents'
            ? 'Project shared with linked parents.'
            : ($data['visibility'] === 'public' ? 'Project marked public.' : 'Project set to private.'));
    }
}
