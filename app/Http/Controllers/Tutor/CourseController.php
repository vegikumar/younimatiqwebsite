<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Course;
use App\Models\LiveSession;
use App\Services\EmailService;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();

        $bookedCourseIds = Booking::where('tutor_id', $tutorId)->pluck('course_id')->toArray();
        $sessionCourseIds = LiveSession::where('tutor_id', $tutorId)->pluck('course_id')->toArray();

        $assignedCourseIds = array_unique(array_merge($bookedCourseIds, $sessionCourseIds));

        $courses = Course::whereIn('id', $assignedCourseIds)
            ->orWhere('tutor_id', $tutorId)
            ->with(['units.modules', 'modules', 'availabilities'])
            ->get();

        // For each course, fetch the upcoming live session
        foreach ($courses as $course) {
            $course->nextSession = LiveSession::where('course_id', $course->id)
                ->where('start_time', '>=', now()->subHours(1))
                ->orderBy('start_time', 'asc')
                ->first();
        }

        return \App\Support\InertiaPage::react('Tutor/ManageCourses', [
            'courses' => $courses->map(fn ($c) => TutorInertiaProps::manageCourse($c, $tutorId))->values()->all(),
        ]);
    }

    public function create()
    {
        $categories = Category::all();

        return \App\Support\InertiaPage::react('Tutor/Courses/Create', [
            'categories' => $categories->map(fn ($c) => TutorInertiaProps::categoryOption($c))->values()->all(),
            'storeUrl' => route('tutor.courses.store'),
            'backUrl' => route('tutor.manage-courses'),
        ]);
    }

    public function store(Request $request)
    {
        $tutor = Auth::guard('tutor')->user();
        if (! $tutor->fresh()->hasCompletedPolicyTraining()) {
            return redirect()->route('tutor.policy-training');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'per_class_price' => 'nullable|numeric',
            'type' => 'nullable|in:1-on-1,camp,recording',
            'course_type' => 'nullable|string',
            'format' => 'nullable|string',
            'age_group_id' => 'nullable|exists:age_groups,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'welcome_message' => 'nullable|string',
            'welcome_video_url' => 'nullable|string',
        ]);

        $data['tutor_id'] = Auth::guard('tutor')->id();
        $data['slug'] = Str::slug($data['title']).'-'.uniqid();
        $data['status'] = 'draft';

        $type = $data['type'] ?? 'recording';
        if ($type === '1-on-1') {
            $data['course_type'] = $data['course_type'] ?? 'live';
            $data['format'] = $data['format'] ?? '1-on-1';
            $data['is_camp'] = false;
        } elseif ($type === 'camp') {
            $data['course_type'] = $data['course_type'] ?? 'live';
            $data['format'] = $data['format'] ?? 'group';
            $data['is_camp'] = true;
        } else {
            $data['course_type'] = $data['course_type'] ?? 'recorded';
            $data['format'] = $data['format'] ?? ($data['format'] ?? 'group');
            $data['is_camp'] = false;
        }

        unset($data['short_description'], $data['importance']);

        if ($request->hasFile('thumbnail')) {
            $path = $this->images->store($request->file('thumbnail'), 'courses', 'public');
            $data['thumbnail_url'] = $path['url'];
        }

        $course = Course::create($data);

        if (! empty($request->category_ids)) {
            $course->categories()->sync($request->category_ids);
        }

        return redirect()->route('tutor.manage-courses')->with('success', 'Course created successfully as draft.');
    }

    public function edit(Course $course)
    {
        abort_unless($course->tutor_id === Auth::guard('tutor')->id(), 403, 'Unauthorized');
        $course->load('categories');
        $categories = Category::all();

        return \App\Support\InertiaPage::react('Tutor/Courses/Edit', [
            'course' => TutorInertiaProps::courseEdit($course),
            'categories' => $categories->map(fn ($c) => TutorInertiaProps::categoryOption($c))->values()->all(),
            'backUrl' => route('tutor.manage-courses'),
        ]);
    }

    public function update(Request $request, Course $course)
    {
        abort_unless($course->tutor_id === Auth::guard('tutor')->id(), 403, 'Unauthorized');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'per_class_price' => 'nullable|numeric',
            'type' => 'required|in:1-on-1,camp,recording',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($data['type'] === '1-on-1') {
            $data['course_type'] = 'live';
            $data['format'] = '1-on-1';
            $data['is_camp'] = false;
        } elseif ($data['type'] === 'camp') {
            $data['course_type'] = 'live';
            $data['format'] = 'group';
            $data['is_camp'] = true;
        } else {
            $data['course_type'] = 'recorded';
            $data['format'] = 'group';
            $data['is_camp'] = false;
        }

        unset($data['short_description'], $data['importance']);

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail_url) {
                $oldPath = str_replace('/storage/', '', $course->thumbnail_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $this->images->store($request->file('thumbnail'), 'courses', 'public');
            $data['thumbnail_url'] = $path['url'];
        }

        $course->update($data);

        if (isset($request->category_ids)) {
            $course->categories()->sync($request->category_ids);
        }

        return redirect()->route('tutor.manage-courses')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        abort_unless($course->tutor_id === Auth::guard('tutor')->id(), 403, 'Unauthorized');
        $course->delete();

        return back()->with('success', 'Course deleted.');
    }

    public function submitForReview(Course $course, EmailService $emailService)
    {
        abort_unless($course->tutor_id === Auth::guard('tutor')->id(), 403, 'Unauthorized');

        if (in_array($course->status, ['draft', 'rejected'])) {
            $course->update(['status' => 'pending']);

            $emailService->notifyAdmins(
                'Course Submitted for Review: '.$course->title,
                'Tutor <strong>'.Auth::guard('tutor')->user()->name.'</strong> has submitted a course for review.<br><br>'.
                '<strong>Course:</strong> '.htmlspecialchars($course->title).'<br>'.
                '<strong>Status:</strong> Pending Approval<br><br>'.
                'Please log in to the admin panel to review and publish this course.'
            );

            return back()->with('success', 'Course submitted for review successfully. You will be notified once it is approved.');
        }

        return back()->with('error', 'Course cannot be submitted from its current status.');
    }
}
