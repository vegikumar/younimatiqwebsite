<?php

namespace App\Http\Middleware\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Models\Enrollment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentActive
{
    use RespondsWithJson;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return $this->error('Student access required.', 403);
        }

        if ($user->status === 'pending') {
            return $this->error('Your account is pending approval.', 403);
        }

        if ($user->status === 'suspended') {
            $hasEnrollment = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if ($hasEnrollment) {
                return $this->error('Your account has been suspended.', 403);
            }
        }

        return $next($request);
    }
}
