<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\EmailService;
use App\Services\TutorTrainingService;
use App\Support\BundleRedirect;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function redirectToStudent(Request $request)
    {
        return redirect()->route('register.student', $request->query());
    }

    public function showStudentRegisterForm(Request $request)
    {
        $referralCode = $request->query('ref') ?? $request->query('referral_code');

        return \App\Support\InertiaPage::react('Auth/RegisterStudent', [
            'referralCode' => $referralCode,
        ]);
    }

    public function showTutorRegisterForm()
    {
        $skills = Skill::orderBy('name')->get();

        return \App\Support\InertiaPage::react('Auth/RegisterTutor', [
            'skills' => $skills,
        ]);
    }

    public function registerStudent(Request $request, EmailService $emailService, \App\Services\ReferralService $referralService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|max:32',
            'ref' => 'nullable|string|max:32',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($user->is_auto_created) {
                $user->update([
                    'name' => $request->name,
                    'password' => Hash::make($request->password),
                    'timezone' => $request->input('timezone', 'UTC'),
                    'is_auto_created' => false,
                    'trial_ends_at' => $user->trial_ends_at ?: now()->addDays(9),
                ]);
            } else {
                return back()->withErrors(['email' => 'The email has already been taken.'])->withInput();
            }
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'timezone' => $request->input('timezone', 'UTC'),
                'email_verified_at' => now(),
                'trial_ends_at' => now()->addDays(9),
            ]);
        }

        try {
            $referralService->attributeReferral($request->input('referral_code') ?? $request->input('ref'), $user);
        } catch (\Throwable $e) {
            // non-blocking
        }

        Auth::guard('web')->login($user);

        $emailService->sendTemplate('welcome_student', $user->email, [
            'student_name' => $user->name,
        ]);

        return BundleRedirect::route('student.dashboard', flash: ['success' => 'Registration successful!']);
    }

    public function registerTutor(Request $request, EmailService $emailService, TutorTrainingService $trainingService)
    {
        $this->assertTutorUploadsPresent($request);

        // Normalize schedule slots from browsers that send H:MM or HH:MM:SS.
        $schedule = $request->input('preferred_schedule', []);
        if (is_string($schedule)) {
            $decoded = json_decode($schedule, true);
            $schedule = is_array($decoded) ? $decoded : (preg_split('/\s*,\s*/', $schedule) ?: []);
        }
        if (! is_array($schedule)) {
            $schedule = [];
        }
        $normalizedSchedule = [];
        foreach ($schedule as $slot) {
            $slot = trim((string) $slot);
            if (preg_match('/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s+(\d{1,2}):(\d{2})(?::\d{2})?-(\d{1,2}):(\d{2})(?::\d{2})?$/i', $slot, $m)) {
                $from = sprintf('%02d:%02d', (int) $m[2], (int) $m[3]);
                $to = sprintf('%02d:%02d', (int) $m[4], (int) $m[5]);
                if ($from < $to) {
                    $normalizedSchedule[] = $m[1].' '.$from.'-'.$to;
                }
            }
        }
        $request->merge(['preferred_schedule' => array_values(array_unique($normalizedSchedule))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.\-]+$/u'],
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:50',
            'city_country' => 'required|string|max:255',
            'timezone' => 'required|string|max:100',
            'linkedin_url' => 'nullable|url|max:500',
            'source' => 'nullable|string|max:255',
            'profile_photo' => 'required|image|max:10240',
            'is_18_plus' => 'accepted',
            'background_check_consent' => 'accepted',
            'child_safety_consent' => 'accepted',
            'subject_verticals' => 'required|array|min:1',
            'subject_verticals.*' => 'string|max:100',
            'preferred_age_bands' => 'required|array|min:1',
            'preferred_age_bands.*' => 'string|max:20',
            'teaching_experience' => 'required|string|max:500',
            'countries_taught' => 'nullable|string|max:255',
            'platforms_taught' => 'nullable|string|max:255',
            'higher_education' => 'required|string|max:255',
            'higher_institution' => 'required|string|max:255',
            'english_fluency' => 'required|integer|min:1|max:5',
            'about_yourself' => 'required|string|min:10|max:5000',
            'additional_languages' => 'nullable|string|max:255',
            'availability_commitment' => 'required|in:Under 10 hrs/wk,10-20 hrs/wk,20-40 hrs/wk',
            'preferred_schedule' => 'required|array|min:1',
            'preferred_schedule.*' => ['string', 'max:40', 'regex:/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun) \d{2}:\d{2}-\d{2}:\d{2}$/'],
            'start_date' => 'required|date|after_or_equal:yesterday',
            'has_laptop_camera' => 'accepted',
            'has_stable_internet' => 'accepted',
            'internet_speed_screenshot' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'has_quiet_workspace' => 'accepted',
            'comfort_with_zoom' => 'accepted',
            // Broader MIME list — phone cameras often send non-standard types.
            'intro_video' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v,video/3gpp,application/mp4|max:51200',
            'certifications' => 'required|array|min:1',
            'certifications.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'currency' => 'required|string|max:10',
            'expected_hourly_rate' => 'required|string|max:100',
            'references' => 'nullable|string|max:2000',
        ]);

        try {
            $user = DB::transaction(function () use ($request, $validated, $trainingService) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'tutor',
                    'status' => 'pending',
                    'timezone' => $validated['timezone'],
                ]);

                $base = 'tutors/'.$user->id;
                $photoPath = $request->file('profile_photo')->store($base, 'public');
                $speedPath = $request->file('internet_speed_screenshot')->store($base, 'public');
                $videoPath = $request->file('intro_video')->store($base, 'public');
                $certPaths = [];
                foreach ($request->file('certifications', []) as $file) {
                    $certPaths[] = $file->store($base.'/certs', 'public');
                }

                TutorProfile::create([
                    'user_id' => $user->id,
                    'phone' => $validated['phone'],
                    'city_country' => $validated['city_country'],
                    'linkedin_url' => $validated['linkedin_url'] ?? null,
                    'source' => $validated['source'] ?? null,
                    'profile_photo_path' => $photoPath,
                    'is_18_plus' => true,
                    'background_check_consent' => true,
                    'child_safety_consent' => true,
                    'subject_verticals' => $validated['subject_verticals'],
                    'preferred_age_bands' => $validated['preferred_age_bands'],
                    'teaching_experience' => $validated['teaching_experience'],
                    'countries_taught' => $validated['countries_taught'] ?? null,
                    'platforms_taught' => $validated['platforms_taught'] ?? null,
                    'higher_education' => $validated['higher_education'],
                    'higher_institution' => $validated['higher_institution'],
                    'english_fluency' => $validated['english_fluency'],
                    'about_yourself' => $validated['about_yourself'],
                    'additional_languages' => $validated['additional_languages'] ?? null,
                    'availability_commitment' => $validated['availability_commitment'],
                    'preferred_schedule' => $validated['preferred_schedule'],
                    'start_date' => $validated['start_date'],
                    'has_laptop_camera' => true,
                    'has_stable_internet' => true,
                    'internet_speed_screenshot_path' => $speedPath,
                    'has_quiet_workspace' => true,
                    'comfort_with_zoom' => true,
                    'intro_video_path' => $videoPath,
                    'certifications_paths' => $certPaths,
                    'currency' => $validated['currency'],
                    'expected_hourly_rate' => $validated['expected_hourly_rate'],
                    'references' => $validated['references'] ?? null,
                    'pipeline_status' => 'NEW',
                ]);

                $user->update([
                    'avatar' => $photoPath,
                    'intro_video_url' => Storage::disk('public')->url($videoPath),
                    'bio' => Str::limit($validated['about_yourself'], 500),
                ]);

                foreach ($validated['subject_verticals'] as $vertical) {
                    $slug = Str::slug($vertical);
                    $skill = Skill::where(function ($q) use ($vertical, $slug) {
                        $q->where('name', $vertical)->orWhere('slug', $slug);
                    })->first();
                    if ($skill) {
                        $trainingService->grantSkill($user, $skill->id, 'registration', false);
                    }
                }

                return $user;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Tutor registration failed: '.$e->getMessage(), [
                'email' => $validated['email'] ?? null,
                'exception' => $e::class,
            ]);

            throw ValidationException::withMessages([
                'email' => 'Registration could not be completed. Please try again with a smaller intro video (under 50MB) or contact support.',
            ]);
        }

        Auth::guard('tutor')->login($user);

        try {
            $emailService->sendTemplate('welcome_tutor', $user->email, [
                'student_name' => $user->name,
            ]);
        } catch (\Throwable $e) {
            // non-blocking
        }

        // Same public Vite shell as /register/tutor — normal Inertia redirect (no location hang).
        return redirect()
            ->route('tutor.pending')
            ->with('success', 'Registration submitted! We will review your profile shortly.');
    }

    /**
     * Surface PHP/nginx upload failures as validation errors instead of an empty POST hang.
     */
    private function assertTutorUploadsPresent(Request $request): void
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMax = $this->iniBytes((string) ini_get('post_max_size'));
        if ($contentLength > 0 && empty($request->all()) && empty($request->allFiles()) && $postMax > 0 && $contentLength > $postMax) {
            throw ValidationException::withMessages([
                'intro_video' => 'Upload was too large for the server. Please use an intro video under 50MB and try again.',
            ]);
        }

        $errors = [];
        foreach (['profile_photo', 'intro_video', 'internet_speed_screenshot'] as $field) {
            $file = $request->file($field);
            if ($file instanceof UploadedFile && ! $file->isValid()) {
                $errors[$field] = $this->uploadErrorMessage($file, $field === 'intro_video' ? 50 : 10);
            }
        }

        $certs = $request->file('certifications', []);
        if (is_array($certs)) {
            foreach ($certs as $i => $file) {
                if ($file instanceof UploadedFile && ! $file->isValid()) {
                    $errors["certifications.$i"] = $this->uploadErrorMessage($file, 10);
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function uploadErrorMessage(UploadedFile $file, int $maxMb): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File is too large. Max {$maxMb}MB.",
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again on a stable connection.',
            UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
            default => 'Upload failed. Please try a smaller file or a different format.',
        };
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $num = (float) $value;
        return (int) match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (float) $value,
        };
    }

    public function showParentRegisterForm()
    {
        return \App\Support\InertiaPage::react('Auth/RegisterParent');
    }

    public function registerParent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'parent',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Auth::guard('parent')->login($user);

        return BundleRedirect::route('parent.dashboard', flash: ['success' => 'Welcome! Ask an admin to link your children, or email support with student emails.']);
    }
}
