<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Enrollment;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->status === 'suspended') {
            // Expired trial students may still browse courses / checkout to re-open access
            $isTrialStudent = $guard === 'web'
                && $user->role === 'student'
                && !Enrollment::where('user_id', $user->id)->whereIn('status', ['active', 'completed'])->exists();

            if ($isTrialStudent) {
                if ($request->routeIs('student.trial_expired')
                    || $request->routeIs('student.checkout*')
                    || $request->routeIs('logout*')
                    || $request->routeIs('courses.*')
                    || $request->routeIs('home')) {
                    return $next($request);
                }

                return redirect()->route('student.trial_expired');
            }

            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $loginRoute = match ($guard) {
                'tutor' => 'login.tutor',
                'admin' => 'login.admin',
                'parent' => 'login.parent',
                default => 'login.student',
            };

            // Portal → public login shell needs a full document load.
            return \App\Support\BundleRedirect::route($loginRoute, flash: [
                'error' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        if ($user->status === 'pending' && $guard === 'tutor') {
            if (!$request->routeIs('tutor.pending')) {
                return redirect()->route('tutor.pending');
            }
        }

        return $next($request);
    }
}
