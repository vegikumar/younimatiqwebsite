<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassroomDiscussion;
use App\Models\Course;
use App\Models\StudentReward;
use App\Services\AntiBypassSecurityService;
use App\Services\ClassRewardService;
use App\Services\MentionService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    public function show(Course $course, ClassRewardService $rewards)
    {
        if ($course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $discussions = ClassroomDiscussion::where('course_id', $course->id)
            ->whereNull('parent_id')
            ->with(['author', 'replies.author'])
            ->latest()
            ->get();

        $recordings = $course->recordings;
        $enrolledStudents = $course->enrollments()->with('user')->get()->pluck('user')->filter();

        $badgeCatalog = $rewards->badgeCatalog(Auth::guard('tutor')->id(), $course->id);
        $recentRewards = StudentReward::with(['student', 'badge'])
            ->where('course_id', $course->id)
            ->latest('awarded_at')
            ->take(15)
            ->get();

        return \App\Support\InertiaPage::react('Tutor/Classroom/Show', [
            'course' => TutorInertiaProps::classroomCourse($course),
            'discussions' => $discussions->map(fn ($d) => TutorInertiaProps::classroomDiscussion($d))->values()->all(),
            'recordings' => collect($recordings)->map(fn ($r) => TutorInertiaProps::classroomRecording($r))->values()->all(),
            'enrolledStudents' => $enrolledStudents->map(fn ($s) => TutorInertiaProps::enrolledStudent($s, $course))->values()->all(),
            'badgeCatalog' => TutorInertiaProps::badgeCatalog($badgeCatalog),
            'recentRewards' => $recentRewards->map(fn ($r) => TutorInertiaProps::classroomReward($r))->values()->all(),
            'rewardPresets' => TutorInertiaProps::xpPresets(),
        ]);
    }

    public function storeDiscussion(Request $request, Course $course, MentionService $mentionService, AntiBypassSecurityService $security)
    {
        if ($course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:classroom_discussions,id',
            'attachment' => 'nullable|file|max:1048576', // up to 1GB for teacher video attachments
        ]);

        $attachmentData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('classroom_attachments', 'public');
            $attachmentData = [
                'name' => $file->getClientOriginalName(),
                'url' => route('secure.asset', ['path' => $path]),
                'size' => $file->getSize(),
                'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'file',
            ];
        }

        $discussion = ClassroomDiscussion::create([
            'course_id' => $course->id,
            'user_id' => Auth::guard('tutor')->id(),
            'parent_id' => $request->parent_id,
            'content' => $security->maskSensitiveContactInfo((string) $request->content),
            'attachments' => $attachmentData ? [$attachmentData] : null,
        ]);

        // Parse @mentions and trigger email notifications
        $mentionService->parseAndNotifyMentions($discussion);

        return redirect()->back()->with('success', 'Discussion posted successfully!');
    }
}
