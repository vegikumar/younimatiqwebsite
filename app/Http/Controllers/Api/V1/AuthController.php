<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckTrialAccess;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EmailService;
use App\Services\StudentApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    use RespondsWithJson;

    public function __construct(private StudentApiService $studentApi)
    {
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $credentials['email'])->where('role', 'student')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid credentials.', 401);
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

        $token = $user->createToken($credentials['device_name'] ?? 'mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->studentApi->formatUser($user),
        ], 'Login successful.');
    }

    public function register(Request $request, EmailService $emailService, \App\Services\ReferralService $referralService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'timezone' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'referral_code' => 'nullable|string|max:32',
            'ref' => 'nullable|string|max:32',
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            if ($user->is_auto_created) {
                $user->update([
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'is_auto_created' => false,
                    'trial_ends_at' => $user->trial_ends_at ?: now()->addDays(9),
                ]);
            } else {
                return $this->error('The email has already been taken.', 422, ['email' => ['The email has already been taken.']]);
            }
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'timezone' => $data['timezone'] ?? 'UTC',
                'email_verified_at' => now(),
                'trial_ends_at' => now()->addDays(9),
            ]);
        }

        try {
            $referralService->attributeReferral($data['referral_code'] ?? $data['ref'] ?? null, $user);
        } catch (\Throwable $e) {
            // non-blocking
        }

        try {
            $emailService->sendTemplate('welcome_student', $user->email, [
                'student_name' => $user->name,
            ]);
        } catch (\Exception $e) {
            // non-blocking
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->studentApi->formatUser($user),
        ], 'Registration successful.', 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    public function me(Request $request)
    {
        return $this->success($this->studentApi->formatUser($request->user()));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('users')->sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error(__($status), 422);
        }

        return $this->success(null, __($status));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(null, __($status));
    }

    public function emailStatus(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'verified' => (bool) $user->hasVerifiedEmail(),
            'email' => $user->email,
        ]);
    }

    public function emailResend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success([
                'verified' => true,
            ], 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return $this->success([
            'verified' => false,
        ], 'A new verification link has been sent to your email.');
    }
}
