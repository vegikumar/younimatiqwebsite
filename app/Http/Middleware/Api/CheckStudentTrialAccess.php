<?php

namespace App\Http\Middleware\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Middleware\CheckTrialAccess;
use App\Models\Enrollment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentTrialAccess
{
    use RespondsWithJson;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return $next($request);
        }

        // Expired trial may still browse catalog, checkout, and finish payment verify/cancel
        // so they can enroll and restore full access (matches Flutter trial-allowed routes).
        if ($request->routeIs('api.v1.student.trial-status')
            || $request->routeIs('api.v1.auth.*')
            || $this->isTrialEnrollmentPath($request)) {
            return $next($request);
        }

        $hasPurchasedCourse = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($hasPurchasedCourse) {
            return $next($request);
        }

        $trialEndsAt = CheckTrialAccess::resolveTrialEndsAt($user);

        if ($trialEndsAt && now()->greaterThanOrEqualTo($trialEndsAt)) {
            return $this->error('Trial access has expired.', 403, null, [
                'trial_expired' => true,
            ]);
        }

        return $next($request);
    }

    private function isTrialEnrollmentPath(Request $request): bool
    {
        $path = $request->path();

        return str_contains($path, '/student/catalog')
            || str_contains($path, '/student/checkout')
            || str_contains($path, '/student/payments/');
    }
}
