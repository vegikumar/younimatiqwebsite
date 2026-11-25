<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();
        $courses = Course::where('tutor_id', $tutorId)->get();

        $publishedAnnouncements = Announcement::where('tutor_id', $tutorId)
            ->where('status', 'published')
            ->with('course')
            ->latest()
            ->get();

        $scheduledAnnouncements = Announcement::where('tutor_id', $tutorId)
            ->where('status', 'scheduled')
            ->with('course')
            ->orderBy('scheduled_at')
            ->get();

        $welcomePosts = Announcement::where('tutor_id', $tutorId)
            ->where('is_welcome_post', true)
            ->with('course')
            ->latest()
            ->get();

        return \App\Support\InertiaPage::react('Tutor/Announcements', [
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::courseOption($c))->values()->all(),
            'published' => $publishedAnnouncements->map(fn ($a) => TutorInertiaProps::announcement($a))->values()->all(),
            'scheduled' => $scheduledAnnouncements->map(fn ($a) => TutorInertiaProps::announcement($a))->values()->all(),
            'welcomePosts' => $welcomePosts->map(fn ($a) => TutorInertiaProps::announcement($a))->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'video_url' => 'nullable|url',
            'publish_option' => 'required|in:now,schedule',
            'scheduled_at' => 'nullable|required_if:publish_option,schedule|date|after:now',
            'is_welcome_post' => 'nullable|boolean',
        ]);

        $status = $request->publish_option === 'schedule' ? 'scheduled' : 'published';
        $publishedAt = $status === 'published' ? now() : null;

        Announcement::create([
            'tutor_id' => Auth::guard('tutor')->id(),
            'course_id' => $request->course_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'is_welcome_post' => $request->boolean('is_welcome_post'),
            'status' => $status,
            'scheduled_at' => $request->scheduled_at,
            'published_at' => $publishedAt,
        ]);

        $msg = $status === 'scheduled' ? 'Announcement scheduled for later!' : 'Announcement published immediately!';

        return redirect()->back()->with('success', $msg);
    }

    public function duplicate(Request $request, Announcement $announcement)
    {
        if ($announcement->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'target_course_ids' => 'required|array',
            'target_course_ids.*' => 'exists:courses,id',
        ]);

        $count = 0;
        foreach ($request->target_course_ids as $courseId) {
            Announcement::create([
                'tutor_id' => Auth::guard('tutor')->id(),
                'course_id' => $courseId,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'video_url' => $announcement->video_url,
                'is_welcome_post' => $announcement->is_welcome_post,
                'status' => 'published',
                'published_at' => now(),
            ]);
            $count++;
        }

        return redirect()->back()->with('success', "Announcement duplicated to {$count} course section(s)!");
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully!');
    }
}
