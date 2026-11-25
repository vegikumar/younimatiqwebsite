<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BundleRedirect;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonate(User $user)
    {
        if (! Auth::guard('admin')->check()) {
            abort(403, 'Unauthorized');
        }

        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot impersonate another admin.');
        }

        if ($user->status === 'suspended') {
            return back()->with('error', 'Cannot login as a suspended account.');
        }

        $adminId = Auth::guard('admin')->id();

        // Leave the admin session so the student or tutor sidebar is the one on screen.
        Auth::guard('web')->logout();
        Auth::guard('tutor')->logout();
        Auth::guard('admin')->logout();

        session()->put('impersonated_by', $adminId);

        $guard = $user->role === 'tutor' ? 'tutor' : 'web';
        Auth::guard($guard)->login($user);
        request()->session()->regenerate();
        // Preserve after regenerate
        session()->put('impersonated_by', $adminId);

        if ($user->role === 'tutor') {
            return BundleRedirect::route('tutor.dashboard', flash: [
                'success' => "You are now impersonating Tutor: {$user->name}",
            ]);
        }

        return BundleRedirect::route('student.dashboard', flash: [
            'success' => "You are now impersonating Student: {$user->name}",
        ]);
    }

    public function leave()
    {
        if (! session()->has('impersonated_by')) {
            abort(403, 'Not impersonating anyone.');
        }

        $adminId = session()->get('impersonated_by');

        Auth::guard('web')->logout();
        Auth::guard('tutor')->logout();

        session()->forget('impersonated_by');
        request()->session()->regenerate();

        Auth::guard('admin')->loginUsingId($adminId);

        // /impersonate/leave is public-shell; admin dashboard needs the portal shell.
        return BundleRedirect::route('admin.dashboard', flash: [
            'success' => 'You have returned to your admin account.',
        ]);
    }
}
