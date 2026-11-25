<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\EmailLog;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserManagementController extends Controller
{
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'status' => 'required|in:active,suspended,pending',
            'timezone' => 'nullable|string|in:' . implode(',', TimezoneService::identifiers()),
        ]);
        $wasPending = $user->status === 'pending';
        $oldTimezone = $user->timezone;
        if (array_key_exists('timezone', $data) && $data['timezone'] !== null) {
            $data['timezone'] = TimezoneService::sanitizeTimezone($data['timezone']);
        }
        $user->update($data);

        if ($user->role === 'student' && array_key_exists('timezone', $data) && $data['timezone'] !== null) {
            $newTz = $data['timezone'];
            if ($newTz !== $oldTimezone && ! TimezoneService::isUnset($newTz)) {
                Booking::query()
                    ->where('student_id', $user->id)
                    ->where('scheduled_at', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'canceled', 'completed', 'no_show'])
                    ->update([
                        'timezone' => $newTz,
                        'parent_timezone' => $newTz,
                    ]);
            }
        }

        if ($user->role === 'tutor' && $wasPending && ($data['status'] ?? '') === 'active') {
            app(\App\Services\NotificationService::class)->notify(
                $user,
                'Tutor application approved',
                'Your Younimatiq tutor account is now active. You can start creating courses.',
                route('tutor.dashboard'),
                'success'
            );
        }

        return back()->with('success', 'User updated.');
    }

    /**
     * Permanently delete a student or tutor account from admin kanban.
     * Tutor-owned courses are kept (tutor_id nulled) so catalog content is not wiped.
     */
    public function destroy(Request $request, User $user)
    {
        abort_unless(in_array($user->role, ['student', 'tutor'], true), 404);

        if ((int) $request->user()?->id === (int) $user->id) {
            $msg = 'You cannot delete your own account.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $roleLabel = $user->role === 'tutor' ? 'Tutor' : 'Student';
        $name = $user->name;

        try {
            DB::transaction(function () use ($user) {
                if ($user->role === 'tutor') {
                    // courses.tutor_id is ON DELETE CASCADE — detach first so courses survive.
                    Course::where('tutor_id', $user->id)->update(['tutor_id' => null]);
                }

                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', User::class)
                        ->where('tokenable_id', $user->id)
                        ->delete();
                }

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                $user->delete();
            });
        } catch (\Throwable $e) {
            report($e);
            $msg = "Could not delete {$roleLabel} {$name}. Related records may still be linked.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $msg = "{$roleLabel} {$name} deleted.";
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8|confirmed']);
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password reset.');
    }

    public function extendTrial(Request $request, User $user)
    {
        $data = $request->validate([
            'days' => 'nullable|integer|min:1|max:365|required_without:trial_ends_at',
            'trial_ends_at' => 'nullable|date|required_without:days',
            'reactivate' => 'nullable|boolean',
        ]);

        if (!empty($data['trial_ends_at'])) {
            $newEnd = \Carbon\Carbon::parse($data['trial_ends_at'])->endOfDay();
        } else {
            $base = ($user->trial_ends_at && $user->trial_ends_at->isFuture())
                ? $user->trial_ends_at->copy()
                : now();
            $newEnd = $base->addDays((int) $data['days']);
        }

        $user->trial_ends_at = $newEnd;

        // Extending trial should bring suspended students back into an active trial bucket.
        $reactivated = false;
        $shouldReactivate = $request->boolean('reactivate', true);
        if ($shouldReactivate && in_array($user->status, ['suspended', 'pending'], true) && $newEnd->isFuture()) {
            $user->status = 'active';
            $reactivated = true;
        }

        $user->save();

        $message = "Trial for {$user->name} extended until {$newEnd->format('d M Y')}." . ($reactivated ? ' Account reactivated.' : '');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'trial_ends_at' => $newEnd->toDateTimeString(), 'reactivated' => $reactivated]);
        }

        return back()->with('success', $message);
    }

    public function sendWelcomeEmail(Request $request, User $user)
    {
        $sentTo = [];
        $studentPassword = $request->input('student_password');
        $parentPassword = $request->input('parent_password');
        $tutorPassword = $request->input('tutor_password');
        $sendToStudent = $request->boolean('send_to_student', true);
        $sendToParent = $request->boolean('send_to_parent', true);

        try {
            app(\App\Services\EmailService::class)->applyAdminSmtpConfig();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SMTP apply in sendWelcomeEmail failed: ' . $e->getMessage());
        }

        if ($user->role === 'student') {
            $user->load(['parents', 'enrollments.course']);
            $courses = $user->enrollments->map(fn ($e) => $e->course?->title)->filter()->unique()->join(', ');
            $courseLabel = $courses ?: 'YounimatiQ Interactive Learning Program';

            // 1. Send to all linked parents
            $parents = $user->parents;
            if (! $parents || $parents->isEmpty()) {
                $parentIds = \App\Models\ParentStudent::where('student_id', $user->id)->pluck('parent_id');
                if ($parentIds->isNotEmpty()) {
                    $parents = User::whereIn('id', $parentIds)->get();
                }
            }

            if ($sendToParent && $parents && $parents->isNotEmpty()) {
                foreach ($parents as $parent) {
                    $currParentPassword = $parentPassword ?: ('YQ' . rand(100000, 999999));
                    $parent->update(['password' => Hash::make($currParentPassword)]);

                    $parentPasswordRow = '<p style="margin:6px 0;"><strong>● Temporary Password:</strong> <code style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:15px; font-weight:700; color:#0f172a;">' . e($currParentPassword) . '</code></p>';

                    $parentBody = '<h3>Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey</h3>'
                        . '<p>Dear ' . e($parent->name ?: 'Parent') . ',</p>'
                        . '<p>A warm welcome to YounimatiQ! Thank you for choosing us as your child’s learning partner. We are delighted to welcome <strong>' . e($user->name) . '</strong> to the YounimatiQ learning community!</p>'
                        . '<p>We are excited to have ' . e($user->name) . ' enrolled in our <strong>' . e($courseLabel) . '</strong> program and look forward to making this learning journey engaging, interactive, and meaningful.</p>'
                        . '<p>At YounimatiQ, we believe every child has unique potential. Our goal is to create an environment where children explore new ideas, develop practical skills, build confidence, and most importantly, enjoy learning through hands-on experiences.</p>'
                        . '<div style="background:#f8fafc; padding:18px; border-radius:10px; margin:20px 0; border:1px solid #e2e8f0;">'
                        . '<h4 style="margin-top:0; color:#046bd2; font-size:16px;">Enrollment & Login Details</h4>'
                        . '<p style="margin:6px 0;"><strong>● Parent Name:</strong> ' . e($parent->name ?: 'Parent') . '</p>'
                        . '<p style="margin:6px 0;"><strong>● Student Name:</strong> ' . e($user->name) . '</p>'
                        . '<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> ' . e($courseLabel) . '</p>'
                        . '<p style="margin:6px 0;"><strong>● Parent Dashboard Login:</strong> <a href="' . e(route('login.parent')) . '" style="color:#046bd2; font-weight:600;">' . e(route('login.parent')) . '</a></p>'
                        . '<p style="margin:6px 0;"><strong>● Registered Email:</strong> ' . e($parent->email) . '</p>'
                        . $parentPasswordRow
                        . '</div>'
                        . '<h4 style="color:#0f172a; margin-top:20px;">What happens next?</h4>'
                        . '<p>Our team will coordinate with you regarding the class schedule, session details, and access to the learning resources. We will also keep you updated on your child’s learning progress and share important feedback along the way.</p>'
                        . '<p>We encourage you to stay connected with us and share your thoughts or questions whenever needed. Your feedback is valuable to us and helps us make your child’s learning experience even better.</p>'
                        . '<p>Once again, thank you for trusting YounimatiQ! We look forward to creating wonderful learning experiences and celebrating ' . e($user->name) . '’s achievements together.</p>'
                        . '<p style="margin-top:25px;">Warm regards,<br><strong>Team YounimatiQ</strong><br><a href="https://www.younimatiq.com">www.younimatiq.com</a><br><a href="mailto:admission@younimatiq.com">admission@younimatiq.com</a></p>';

                    $parentSubject = "Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey";
                    try {
                        \Illuminate\Support\Facades\Mail::to($parent->email)->send(new \App\Mail\TemplateMail($parentSubject, $parentBody));
                        EmailLog::create([
                            'to_email' => $parent->email,
                            'subject' => $parentSubject,
                            'template_slug' => 'welcome_parent',
                            'status' => 'sent',
                        ]);
                        $sentTo[] = "Parent {$parent->name} ({$parent->email})";
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Failed sending welcome email to parent {$parent->email}: " . $e->getMessage());
                        EmailLog::create([
                            'to_email' => $parent->email,
                            'subject' => $parentSubject,
                            'template_slug' => 'welcome_parent',
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // 2. Send to Student
            if ($sendToStudent) {
                if (empty($studentPassword)) {
                    $studentPassword = 'YQ' . rand(100000, 999999);
                }
                $user->update(['password' => Hash::make($studentPassword)]);

                $studentPasswordRow = '<p style="margin:6px 0;"><strong>● Temporary Password:</strong> <code style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:15px; font-weight:700; color:#0f172a;">' . e($studentPassword) . '</code></p>';

                $studentBody = '<h3>Welcome to YounimatiQ, ' . e(explode(' ', $user->name)[0]) . '!</h3>'
                    . '<p>Hi ' . e($user->name) . ',</p>'
                    . '<p>We are thrilled to welcome you to the YounimatiQ learning community! Get ready for an exciting learning journey filled with hands-on activities, interactive games, and creative challenges.</p>'
                    . '<div style="background:#f8fafc; padding:18px; border-radius:10px; margin:20px 0; border:1px solid #e2e8f0;">'
                    . '<h4 style="margin-top:0; color:#046bd2; font-size:16px;">Your Student Account Details</h4>'
                    . '<p style="margin:6px 0;"><strong>● Student Name:</strong> ' . e($user->name) . '</p>'
                    . '<p style="margin:6px 0;"><strong>● Program:</strong> ' . e($courseLabel) . '</p>'
                    . '<p style="margin:6px 0;"><strong>● Student Login:</strong> <a href="' . e(route('login.student')) . '" style="color:#046bd2; font-weight:600;">' . e(route('login.student')) . '</a></p>'
                    . '<p style="margin:6px 0;"><strong>● Login Email:</strong> ' . e($user->email) . '</p>'
                    . $studentPasswordRow
                    . '</div>'
                    . '<p>Log into your student portal to view your upcoming sessions, play learning activity games, work on creative projects, and join your live interactive classes.</p>'
                    . '<p>If you have any questions or need assistance, you can always reach our team at <a href="mailto:support@younimatiq.com">support@younimatiq.com</a>.</p>'
                    . '<p style="margin-top:25px;">Happy Learning!<br><strong>Team YounimatiQ</strong><br><a href="https://www.younimatiq.com">www.younimatiq.com</a></p>';

                $studentSubject = "Welcome to YounimatiQ, " . explode(' ', $user->name)[0] . "! Let’s Begin Your Learning Adventure";
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TemplateMail($studentSubject, $studentBody));
                    EmailLog::create([
                        'to_email' => $user->email,
                        'subject' => $studentSubject,
                        'template_slug' => 'welcome_student',
                        'status' => 'sent',
                    ]);
                    $sentTo[] = "Student {$user->name} ({$user->email})";
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed sending welcome email to student {$user->email}: " . $e->getMessage());
                    EmailLog::create([
                        'to_email' => $user->email,
                        'subject' => $studentSubject,
                        'template_slug' => 'welcome_student',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($user->role === 'tutor') {
            if (empty($tutorPassword)) {
                $tutorPassword = 'YQ' . rand(100000, 999999);
            }
            $user->update(['password' => Hash::make($tutorPassword)]);

            $tutorPasswordRow = '<p style="margin:6px 0;"><strong>● Temporary Password:</strong> <code style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:15px; font-weight:700; color:#0f172a;">' . e($tutorPassword) . '</code></p>';

            $tutorBody = '<h3>Welcome to the YounimatiQ Teaching Team!</h3>'
                . '<p>Dear ' . e($user->name) . ',</p>'
                . '<p>A warm welcome to YounimatiQ! We are delighted to have you join our team of passionate educators inspiring students through hands-on, engaging, and interactive learning.</p>'
                . '<div style="background:#f4f6f9; padding:18px; border-radius:10px; margin:20px 0; border:1px solid #e2e8f0;">'
                . '<h4 style="margin-top:0; color:#046bd2; font-size:16px;">Your Tutor Portal</h4>'
                . '<p style="margin:6px 0;"><strong>● Tutor Name:</strong> ' . e($user->name) . '</p>'
                . '<p style="margin:6px 0;"><strong>● Tutor Dashboard:</strong> <a href="' . e(route('login.tutor')) . '" style="color:#046bd2; font-weight:600;">' . e(route('login.tutor')) . '</a></p>'
                . '<p style="margin:6px 0;"><strong>● Registered Email:</strong> ' . e($user->email) . '</p>'
                . $tutorPasswordRow
                . '</div>'
                . '<p>From your Tutor Dashboard, you can access your teaching schedule, student roster, classroom tools, training modules, and session materials.</p>'
                . '<p>Our team is here to support you at every stage. For any assistance or questions, feel free to reply to this email or contact us at <a href="mailto:support@younimatiq.com">support@younimatiq.com</a>.</p>'
                . '<p style="margin-top:25px;">Warm regards,<br><strong>Team YounimatiQ</strong><br><a href="https://www.younimatiq.com">www.younimatiq.com</a></p>';

            $tutorSubject = "Welcome to the YounimatiQ Teaching Team, " . explode(' ', $user->name)[0] . "!";
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TemplateMail($tutorSubject, $tutorBody));
                EmailLog::create([
                    'to_email' => $user->email,
                    'subject' => $tutorSubject,
                    'template_slug' => 'welcome_tutor',
                    'status' => 'sent',
                ]);
                $sentTo[] = "Tutor {$user->name} ({$user->email})";
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed sending welcome email to tutor {$user->email}: " . $e->getMessage());
                EmailLog::create([
                    'to_email' => $user->email,
                    'subject' => $tutorSubject,
                    'template_slug' => 'welcome_tutor',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        } else {
            return back()->with('error', 'Welcome email is only supported for student and tutor accounts.');
        }

        if (empty($sentTo)) {
            $err = 'Failed to send welcome email. Please check your mail configuration.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $err], 500);
            }
            return back()->with('error', $err);
        }

        $summary = implode(' and ', $sentTo);
        $msg = "Welcome email sent successfully to {$summary}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $msg, 'recipients' => $sentTo]);
        }

        return back()->with('success', $msg);
    }
}
