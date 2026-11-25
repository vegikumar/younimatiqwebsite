<?php

namespace App\Http\Middleware\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Models\Course;
use App\Services\EnrollmentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentEnrolled
{
    use RespondsWithJson;

    public function __construct(private EnrollmentService $enrollmentService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');

        if (!$course instanceof Course) {
            $courseId = $request->route('course') ?? $request->route('courseId');
            $course = Course::find($courseId);
        }

        if (!$course || !$request->user()) {
            return $this->error('Course not found.', 404);
        }

        if (!$this->enrollmentService->isEnrolled($request->user()->id, $course->id)) {
            return $this->error('Please enroll in this course first.', 403, null, [
                'requires_enrollment' => true,
                'course_id' => $course->id,
            ]);
        }

        return $next($request);
    }
}
