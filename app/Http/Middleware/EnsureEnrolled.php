<?php

namespace App\Http\Middleware;

use App\Services\EnrollmentService;
use App\Support\BundleRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrolled
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');

        if (! $course || ! auth('web')->check()) {
            return BundleRedirect::route('login.student');
        }

        if (! $this->enrollmentService->isEnrolled(auth('web')->id(), $course->id)) {
            // Course player is portal; checkout is the public shell.
            return BundleRedirect::route('student.checkout', $course, [
                'error' => 'Please enroll in this course to access the player.',
            ]);
        }

        return $next($request);
    }
}
