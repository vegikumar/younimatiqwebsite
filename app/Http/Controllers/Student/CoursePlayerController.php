<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ModuleProgress;
use App\Services\EnrollmentService;
use App\Support\StudentInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursePlayerController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    public function show(Course $course)
    {
        $course->load('modules', 'tutor');
        $enrollment = \App\Models\Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->first();

        $progress = ModuleProgress::where('user_id', Auth::id())
            ->whereIn('course_module_id', $course->modules->pluck('id'))
            ->get()
            ->keyBy('course_module_id');

        $progressMap = $progress->mapWithKeys(fn ($p, $moduleId) => [
            $moduleId => ['completed' => (bool) $p->completed],
        ])->all();

        return \App\Support\InertiaPage::react('Student/CoursePlayer', [
            'course' => array_merge(StudentInertiaProps::courseBrief($course), [
                'modules' => $course->modules->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'content_type' => $m->content_type ?? $m->module_type,
                ])->values()->all(),
            ]),
            'enrollment' => $enrollment ? StudentInertiaProps::enrollment($enrollment) : null,
            'progress' => $progressMap,
        ]);
    }

    public function completeModule(Request $request, Course $course, $moduleId)
    {
        $module = $course->modules()->findOrFail($moduleId);

        ModuleProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'course_module_id' => $module->id],
            ['completed' => true, 'completed_at' => now()]
        );

        $this->enrollmentService->updateProgress(Auth::id(), $course->id);

        $result = app(\App\Services\LearningActivityService::class)->record(Auth::id(), $course->id);
        $msg = 'Module marked as complete!';
        if (! empty($result['skill']['mastered']['title'])) {
            $msg .= ' Skill unlocked: '.$result['skill']['mastered']['title'].'.';
        }
        if (! empty($result['skill']['milestones'][0]['badge'])) {
            $msg .= ' Milestone: '.$result['skill']['milestones'][0]['badge'].'!';
        }

        return back()->with('success', $msg);
    }
}
