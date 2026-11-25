<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ModuleProgress;
use App\Services\EnrollmentService;
use App\Services\StudentApiService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private StudentApiService $studentApi,
        private EnrollmentService $enrollmentService,
    ) {
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = Enrollment::with(['course.tutor', 'course.modules'])
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'completed']);

        if ($request->filled('search')) {
            $query->whereHas('course', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $enrollments = $query->orderByDesc('created_at')->get();
        $courseIds = $enrollments->pluck('course_id');

        $bookings = Booking::with('tutor')
            ->where('student_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('course_id');

        $items = $enrollments->map(function ($enrollment) use ($bookings) {
            $course = $enrollment->course;
            $courseBookings = $bookings->get($course->id, collect());
            $next = \App\Support\NextLiveBooking::pick($courseBookings);

            return array_merge(
                $this->studentApi->formatCourse($course, $enrollment),
                ['next_booking' => $next ? $this->studentApi->formatBooking($next) : null]
            );
        });

        return $this->success(['courses' => $items]);
    }

    public function show(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return $this->error('You are not enrolled in this course.', 403);
        }

        $course->load(['modules', 'tutor']);

        $next = \App\Support\NextLiveBooking::pick(
            Booking::with('tutor')
                ->where('student_id', $request->user()->id)
                ->where('course_id', $course->id)
                ->orderBy('scheduled_at')
                ->get()
        );

        return $this->success([
            'course' => $this->studentApi->formatCourse($course, $enrollment),
            'next_booking' => $next ? $this->studentApi->formatBooking($next) : null,
            'modules' => $course->modules->sortBy('order')->values()->map(function ($m) use ($request) {
                $progress = ModuleProgress::where('user_id', $request->user()->id)
                    ->where('course_module_id', $m->id)
                    ->first();

                return $this->studentApi->formatModule($m, $progress);
            }),
        ]);
    }

    public function player(Request $request, Course $course)
    {
        return $this->show($request, $course);
    }

    public function completeModule(Request $request, Course $course, $moduleId)
    {
        $module = $course->modules()->findOrFail($moduleId);

        ModuleProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_module_id' => $module->id],
            ['completed' => true, 'completed_at' => now()]
        );

        $this->enrollmentService->updateProgress($request->user()->id, $course->id);

        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->first();

        return $this->success([
            'module_id' => (int) $moduleId,
            'progress' => (float) ($enrollment?->progress ?? 0),
        ], 'Module marked as complete.');
    }
}
