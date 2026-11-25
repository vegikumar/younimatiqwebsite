<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Support\BundleRedirect;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showAdminLoginForm()
    {
        return \App\Support\InertiaPage::react('Auth/Login', ['role' => 'admin']);
    }

    public function adminIndex()
    {
        if (Auth::guard('admin')->check()) {
            return BundleRedirect::route('admin.dashboard');
        }

        return redirect()->route('login.admin');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'admin-login:'.strtolower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->where('role', 'admin')->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records or you are not an admin.',
            ])->onlyInput('email');
        }

        if ($user->status === 'suspended') {
            return back()->withErrors(['email' => 'Your account has been suspended.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        $otpEmail = $this->adminOtpDeliveryEmail($user);

        $request->session()->put('admin_otp_user_id', $user->id);
        $request->session()->put('admin_otp_email', $otpEmail);
        $request->session()->put('admin_otp_remember', $request->boolean('remember'));

        $mailSent = $this->sendAdminOtp($user, $otpEmail);
        $otp = (string) $request->session()->get('admin_otp_code');

        if (! $mailSent) {
            return redirect()
                ->route('login.admin.otp')
                ->with('status', 'Email delivery is temporarily unavailable. Your verification code is: '.$otp);
        }

        return redirect()
            ->route('login.admin.otp')
            ->with('status', 'A verification code has been sent to your email.');
    }

    public function showAdminOtpForm(Request $request)
    {
        if (! $request->session()->has('admin_otp_user_id')) {
            return redirect()->route('login.admin');
        }

        return \App\Support\InertiaPage::react('Auth/AdminOtp', [
            'email' => $request->session()->get('admin_otp_email'),
        ]);
    }

    public function verifyAdminOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('admin_otp_user_id');
        if (! $userId) {
            return redirect()->route('login.admin')->withErrors([
                'email' => 'Your verification session expired. Please sign in again.',
            ]);
        }

        $user = User::where('id', $userId)->where('role', 'admin')->first();
        if (! $user || $user->status === 'suspended') {
            $request->session()->forget(['admin_otp_user_id', 'admin_otp_email', 'admin_otp_remember']);

            return redirect()->route('login.admin')->withErrors([
                'email' => 'Unable to verify this admin account.',
            ]);
        }

        $expected = $request->session()->get('admin_otp_code');
        $expiresAt = (int) $request->session()->get('admin_otp_expires_at', 0);
        if (! $expected || $expiresAt < time() || ! hash_equals((string) $expected, (string) $request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $remember = (bool) $request->session()->pull('admin_otp_remember', false);
        $request->session()->forget([
            'admin_otp_user_id',
            'admin_otp_email',
            'admin_otp_code',
            'admin_otp_expires_at',
        ]);

        Auth::guard('admin')->login($user, $remember);
        // SessionGuard::login already regenerates the session; regenerating
        // again is unnecessary and can race with Inertia::location cookie updates.

        return Inertia::location($this->intendedForPortal($request, '/admin', '/admin/dashboard'));
    }

    /**
     * Pull url.intended only when it belongs to the portal that just signed in.
     * Leftover intended paths from another role (e.g. /student/bookings during
     * an admin login) must not hijack the post-login redirect.
     */
    private function intendedForPortal(Request $request, string $prefix, string $fallback): string
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: $intended;
        if (! is_string($path) || ! str_starts_with($path, $prefix)) {
            return $fallback;
        }

        return $intended;
    }

    public function resendAdminOtp(Request $request)
    {
        $userId = $request->session()->get('admin_otp_user_id');
        if (! $userId) {
            return redirect()->route('login.admin');
        }

        $user = User::where('id', $userId)->where('role', 'admin')->first();
        if (! $user) {
            return redirect()->route('login.admin');
        }

        $throttleKey = 'admin-otp-resend:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'otp' => "Please wait {$seconds} seconds before requesting another code.",
            ]);
        }

        $otpEmail = $this->adminOtpDeliveryEmail($user);
        $request->session()->put('admin_otp_email', $otpEmail);

        $mailSent = $this->sendAdminOtp($user, $otpEmail);
        RateLimiter::hit($throttleKey, 60);

        if (! $mailSent) {
            $otp = (string) $request->session()->get('admin_otp_code');

            return back()->with('status', 'Email delivery is temporarily unavailable. Your verification code is: '.$otp);
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    public function adminLogout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return BundleRedirect::to('/');
    }

    private function adminOtpDeliveryEmail(User $user): string
    {
        $configured = trim((string) (\App\Models\Setting::where('key', 'admin_email')->value('value') ?? ''));

        return $configured !== '' ? $configured : $user->email;
    }

    private function sendAdminOtp(User $user, ?string $deliveryEmail = null): bool
    {
        $otp = (string) random_int(100000, 999999);
        $to = $deliveryEmail ?: $this->adminOtpDeliveryEmail($user);

        session([
            'admin_otp_code' => $otp,
            'admin_otp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        // Playwright bridge: only when E2E_SECRET is configured.
        if (filled(config('app.e2e_secret'))) {
            Cache::put('e2e.admin_otp.'.$user->id, $otp, now()->addMinutes(10));
            Cache::put('e2e.admin_otp.email.'.strtolower((string) $user->email), $otp, now()->addMinutes(10));
        }

        $subject = 'Your Younimatiq Admin Login Code';
        $body = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#111827;">'
            .'<h2 style="color:#4f46e5;">Admin verification</h2>'
            .'<p>Hi '.e($user->name).',</p>'
            .'<p>Use this one-time code to finish signing in to the admin panel:</p>'
            .'<p style="font-size:32px;font-weight:700;letter-spacing:6px;margin:24px 0;">'.e($otp).'</p>'
            .'<p>This code expires in 10 minutes. If you did not try to sign in, you can ignore this email.</p>'
            .'</div>';

        try {
            Mail::to($to)->send(new TemplateMail($subject, $body));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    // Tutor Login
    public function showTutorLoginForm()
    {
        return \App\Support\InertiaPage::react('Auth/Login', ['role' => 'tutor']);
    }

    public function tutorLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $tutorResponse = $this->attemptTutorLogin($request, $credentials);
        if ($tutorResponse !== null) {
            return $tutorResponse;
        }

        // Student (or other role) used the teacher form with valid credentials — send them home.
        if (Auth::guard('web')->attempt(array_merge($credentials, ['role' => 'student']), $request->boolean('remember'))) {
            return $this->finishStudentLogin($request);
        }

        $user = User::where('email', $credentials['email'])->first();
        if ($user && $user->role !== 'tutor') {
            return back()->withErrors([
                'email' => 'This account is registered as a '.$user->role.'. Please use the correct login page or register as a tutor.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * @param  array{email:string,password:string}  $credentials
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    protected function attemptTutorLogin(Request $request, array $credentials)
    {
        if (! Auth::guard('tutor')->attempt(array_merge($credentials, ['role' => 'tutor']), $request->boolean('remember'))) {
            return null;
        }

        $user = Auth::guard('tutor')->user();
        if ($user->status === 'suspended') {
            Auth::guard('tutor')->logout();

            return back()->withErrors(['email' => 'Your account has been suspended.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->status === 'pending') {
            return redirect()->route('tutor.pending');
        }

        // Public login shell cannot resolve Tutor/* pages — force a full reload into the portal bundle.
        return Inertia::location($this->intendedForPortal($request, '/tutor', '/tutor/dashboard'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function finishStudentLogin(Request $request)
    {
        $user = Auth::guard('web')->user();
        if ($user->status === 'suspended') {
            $hasEnrollment = \App\Models\Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
            if ($hasEnrollment) {
                Auth::guard('web')->logout();

                return back()->withErrors([
                    'email' => 'Your account has been suspended. Ask an admin to set your status to Active (Admin → Students → Activate / Edit).',
                ])->onlyInput('email');
            }
            $request->session()->regenerate();

            return Inertia::location(route('student.trial_expired'));
        }
        if ($user->status === 'pending') {
            Auth::guard('web')->logout();

            return back()->withErrors(['email' => 'Your account is pending approval.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        return Inertia::location($this->intendedForPortal($request, '/student', '/student/dashboard'));
    }

    public function tutorLogout(Request $request)
    {
        Auth::guard('tutor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return BundleRedirect::to('/');
    }

    // Student/User Login
    public function showStudentLoginForm()
    {
        return \App\Support\InertiaPage::react('Auth/Login', ['role' => 'student']);
    }

    public function studentLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('web')->attempt(array_merge($credentials, ['role' => 'student']), $request->boolean('remember'))) {
            return $this->finishStudentLogin($request);
        }

        // Teachers often land on the Explorer form — accept valid tutor credentials here too.
        $tutorResponse = $this->attemptTutorLogin($request, $credentials);
        if ($tutorResponse !== null) {
            return $tutorResponse;
        }

        $user = User::where('email', $credentials['email'])->first();
        if ($user && $user->role === 'tutor') {
            return back()->withErrors([
                'email' => 'This is a teacher account. Check your password, or use Teacher sign-in below.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function studentLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return BundleRedirect::to('/');
    }

    public function showParentLoginForm()
    {
        return \App\Support\InertiaPage::react('Auth/Login', ['role' => 'parent']);
    }

    public function parentLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('parent')->attempt(array_merge($credentials, ['role' => 'parent']), $request->boolean('remember'))) {
            $user = Auth::guard('parent')->user();
            if ($user->status === 'suspended') {
                Auth::guard('parent')->logout();

                return back()->withErrors(['email' => 'Your account has been suspended.'])->onlyInput('email');
            }
            $request->session()->regenerate();

            return Inertia::location($this->intendedForPortal($request, '/parent', '/parent/dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function parentLogout(Request $request)
    {
        Auth::guard('parent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return BundleRedirect::to('/');
    }
}
