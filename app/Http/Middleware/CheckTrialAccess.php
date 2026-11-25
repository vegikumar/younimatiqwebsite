<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Enrollment;
use App\Models\CampBooking;
use Carbon\Carbon;

class CheckTrialAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'student') {
            return $next($request);
        }

        // Allow access to checkout, logout, and trial_expired pages
        if ($request->routeIs('student.trial_expired') || $request->routeIs('student.checkout*') || $request->routeIs('logout*')) {
            return $next($request);
        }

        $hasPurchasedCourse = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($hasPurchasedCourse) {
            return $next($request);
        }

        $trialEndsAt = $this->resolveTrialEndsAt($user);

        if ($trialEndsAt && now()->greaterThanOrEqualTo($trialEndsAt)) {
            return redirect()->route('student.trial_expired');
        }

        return $next($request);
    }

    /**
     * Trial dashboard stays open until 9 days after the demo class (scheduled_at).
     */
    public static function resolveTrialEndsAt($user): ?Carbon
    {
        if ($user->trial_ends_at) {
            return Carbon::parse($user->trial_ends_at);
        }

        $demoClass = CampBooking::where('email', $user->email)
            ->orderByRaw('CASE WHEN scheduled_at IS NOT NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$demoClass) {
            return null;
        }

        $referenceDate = $demoClass->scheduled_at
            ? Carbon::parse($demoClass->scheduled_at)
            : Carbon::parse($demoClass->created_at);

        return $referenceDate->copy()->addDays(9);
    }
}
