<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Booking;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to'))->endOfDay() : null;
        $filtered = (bool) ($from || $to);

        $cacheKey = 'admin:dashboard:v2:'.($from?->toDateString() ?: 'all').':'.($to?->toDateString() ?: 'all');

        $payload = Cache::remember($cacheKey, $filtered ? 30 : 90, function () use ($now, $startOfMonth, $from, $to, $filtered) {
            $revenueQuery = Payment::where('status', 'completed');
            $enrollQuery = Enrollment::query();
            if ($from) {
                $revenueQuery->where('created_at', '>=', $from);
                $enrollQuery->where('created_at', '>=', $from);
            }
            if ($to) {
                $revenueQuery->where('created_at', '<=', $to);
                $enrollQuery->where('created_at', '<=', $to);
            }

            $userRoleCounts = User::query()
                ->selectRaw("role, COUNT(*) as total, SUM(CASE WHEN role = 'tutor' AND status = 'pending' THEN 1 ELSE 0 END) as pending_tutors")
                ->whereIn('role', ['student', 'tutor'])
                ->groupBy('role')
                ->get()
                ->keyBy('role');

            $courseCounts = Course::query()
                ->selectRaw("COUNT(*) as total_courses, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_courses")
                ->first();

            $metrics = [
                'total_revenue' => (float) (clone $revenueQuery)->sum('amount'),
                'month_revenue' => (float) Payment::where('status', 'completed')
                    ->where('created_at', '>=', $startOfMonth)
                    ->sum('amount'),
                'total_enrollments' => (int) (clone $enrollQuery)->count(),
                'month_enrollments' => (int) Enrollment::where('created_at', '>=', $startOfMonth)->count(),
                'total_courses' => (int) ($courseCounts->total_courses ?? 0),
                'published_courses' => (int) ($courseCounts->published_courses ?? 0),
                'active_students' => (int) ($userRoleCounts->get('student')->total ?? 0),
                'active_tutors' => (int) ($userRoleCounts->get('tutor')->total ?? 0),
                'pending_tutors' => (int) ($userRoleCounts->get('tutor')->pending_tutors ?? 0),
                'upcoming_sessions' => (int) Booking::where('session_type', 'scheduled_class')
                    ->where('scheduled_at', '>=', $now)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'filtered' => $filtered,
            ];

            $recent_users = User::orderByDesc('created_at')->take(5)->get(['id', 'name', 'email', 'role', 'created_at']);
            $recent_enrollments = Enrollment::with(['course:id,title', 'user:id,name'])
                ->orderByDesc('created_at')
                ->take(5)
                ->get();

            $trendStart = $now->copy()->subDays(13)->startOfDay();
            $revenueByDay = Payment::where('status', 'completed')
                ->where('created_at', '>=', $trendStart)
                ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
                ->groupBy('day')
                ->pluck('total', 'day');
            $enrollmentsByDay = Enrollment::where('created_at', '>=', $trendStart)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $days = collect(range(13, 0))->map(fn ($i) => $now->copy()->subDays($i)->startOfDay());
            $trend = [
                'labels' => $days->map(fn ($d) => $d->format('M j'))->all(),
                'revenue' => $days->map(fn ($d) => round((float) ($revenueByDay[$d->toDateString()] ?? 0), 2))->all(),
                'enrollments' => $days->map(fn ($d) => (int) ($enrollmentsByDay[$d->toDateString()] ?? 0))->all(),
            ];

            $categoryMix = Course::query()
                ->join('category_course', 'courses.id', '=', 'category_course.course_id')
                ->join('categories', 'categories.id', '=', 'category_course.category_id')
                ->join('enrollments', 'enrollments.course_id', '=', 'courses.id')
                ->selectRaw('categories.name as name, COUNT(enrollments.id) as total')
                ->groupBy('categories.name')
                ->orderByDesc('total')
                ->take(6)
                ->pluck('total', 'name')
                ->all();

            $upcoming_bookings = Booking::with(['student:id,name,email', 'course:id,title', 'tutor:id,name,email'])
                ->where('session_type', 'scheduled_class')
                ->where('scheduled_at', '>=', $now)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderByDesc('scheduled_at')
                ->take(6)
                ->get();

            return [
                'metrics' => $metrics,
                'recentUsers' => $recent_users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'created_human' => $u->created_at?->diffForHumans(),
                ])->values()->all(),
                'recentEnrollments' => $recent_enrollments->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'status' => $e->status,
                    'student_name' => $e->user?->name,
                    'course_title' => $e->course?->title,
                    'created_human' => $e->created_at?->diffForHumans(),
                ])->values()->all(),
                'trend' => $trend,
                'categoryMix' => $categoryMix,
                'upcomingBookings' => $upcoming_bookings->map(fn (Booking $b) => [
                    'id' => $b->id,
                    'student_name' => $b->student?->name,
                    'student_email' => $b->student?->email,
                    'tutor_name' => $b->tutor?->name,
                    'tutor_email' => $b->tutor?->email,
                    'course_title' => $b->course?->title,
                    'scheduled_human' => $b->scheduled_at?->diffForHumans(),
                ])->values()->all(),
            ];
        });

        return \App\Support\InertiaPage::react('Admin/Dashboard', [
            'metrics' => $payload['metrics'],
            'dateFilters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'recentUsers' => $payload['recentUsers'],
            'recentEnrollments' => $payload['recentEnrollments'],
            'trend' => $payload['trend'],
            'categoryMix' => $payload['categoryMix'],
            'upcomingBookings' => $payload['upcomingBookings'],
        ]);
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'timezone' => 'nullable|string|in:' . implode(',', \App\Services\TimezoneService::identifiers()),
            'send_email' => 'nullable|boolean',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_password' => 'nullable|string|min:6|confirmed',
            'relationship' => 'nullable|string|in:parent,guardian',
        ]);

        $email = strtolower(trim($data['email']));
        $parentEmail = ! empty($data['parent_email']) ? strtolower(trim($data['parent_email'])) : null;

        if ($parentEmail && $parentEmail === $email) {
            return back()->withErrors([
                'parent_email' => 'Parent email cannot be the same as the student email address.',
            ])->withInput();
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if (in_array($existing->role, ['admin', 'tutor'], true)) {
                return back()->withErrors([
                    'email' => "This email belongs to an existing {$existing->role} staff account and cannot be used as a student.",
                ])->withInput();
            }

            if ($existing->role === 'parent') {
                $existing->update([
                    'name' => $data['name'],
                    'role' => 'student',
                    'status' => 'active',
                    'timezone' => \App\Services\TimezoneService::sanitizeTimezone(
                        ($data['timezone'] ?? null) ?: ($existing->timezone ?: 'UTC')
                    ),
                    'password' => $data['password'],
                    'trial_ends_at' => $existing->trial_ends_at ?: now()->addDays(9),
                ]);

                $user = $existing;
            } elseif ($existing->role === 'student') {
                return back()->withErrors([
                    'email' => "A student account with email '{$existing->email}' already exists (#ID {$existing->id}: {$existing->name}, status: {$existing->status}).",
                ])->withInput();
            }
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'],
                'role' => 'student',
                'status' => 'active',
                'timezone' => \App\Services\TimezoneService::sanitizeTimezone(($data['timezone'] ?? null) ?: 'UTC'),
                'is_auto_created' => true,
                'trial_ends_at' => now()->addDays(9),
                'email_verified_at' => now(),
            ]);
        }

        // Handle optional parent creation & linking
        $parent = null;
        $parentPlainPassword = $data['parent_password'] ?? null;
        if ($parentEmail) {
            $parent = User::where('email', $parentEmail)->first();
            if (! $parent) {
                $parentPlainPassword = $parentPlainPassword ?: str()->random(10);
                $parent = User::create([
                    'name' => ($data['parent_name'] ?? null) ?: 'Parent of ' . $user->name,
                    'email' => $parentEmail,
                    'password' => $parentPlainPassword,
                    'role' => 'parent',
                    'status' => 'active',
                    'is_auto_created' => true,
                    'email_verified_at' => now(),
                ]);
            } else {
                if ($parent->role !== 'parent') {
                    $parent->role = 'parent';
                }
                if (! empty($data['parent_name'])) {
                    $parent->name = $data['parent_name'];
                }
                if (! empty($parentPlainPassword)) {
                    $parent->password = $parentPlainPassword;
                }
                $parent->status = 'active';
                $parent->save();
            }

            \App\Models\ParentStudent::updateOrCreate(
                ['parent_id' => $parent->id, 'student_id' => $user->id],
                [
                    'relationship' => $data['relationship'] ?? 'parent',
                    'consent_at' => now(),
                    'linked_by' => \Illuminate\Support\Facades\Auth::guard('admin')->id(),
                ]
            );
        }

        if ($request->boolean('send_email')) {
            $this->sendAccountCredentialsEmail($user, $data['password'], 'student');

            if ($parent && $parentPlainPassword) {
                $this->sendAccountCredentialsEmail($parent, $parentPlainPassword, 'parent', $user);
            }
        }

        Cache::forget('admin:dashboard:v2:all:all');

        $successMsg = "Student account created for {$user->name}.";
        if ($parent) {
            $successMsg .= " Linked to parent {$parent->name} ({$parent->email}).";
        }
        if ($request->boolean('send_email')) {
            $successMsg .= $parent ? " Credentials sent to student and parent." : " Credentials sent to student.";
        }

        return back()->with('success', $successMsg);
    }

    public function storeTutor(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'timezone' => 'nullable|string|in:' . implode(',', \App\Services\TimezoneService::identifiers()),
            'status' => 'nullable|in:active,pending',
            'send_email' => 'nullable|boolean',
        ]);

        $password = $data['password'];
        $status = $data['status'] ?? 'active';
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'role' => 'tutor',
            'status' => $status,
            'timezone' => ($data['timezone'] ?? null) ?: 'UTC',
            'email_verified_at' => now(),
        ]);

        \App\Models\TutorProfile::create([
            'user_id' => $user->id,
            'pipeline_status' => $status === 'active' ? 'APPROVED' : 'NEW',
            'source' => 'admin',
        ]);

        if ($request->boolean('send_email')) {
            $this->sendAccountCredentialsEmail($user, $password, 'tutor');
        }

        Cache::forget('admin:dashboard:v2:all:all');

        return back()->with('success', "Tutor account created for {$user->name}.");
    }

    private function sendAccountCredentialsEmail(User $user, string $password, string $role, ?User $student = null): void
    {
        $loginRoute = match ($role) {
            'tutor' => 'login.tutor',
            'parent' => 'login.parent',
            default => 'login.student',
        };
        $loginUrl = route($loginRoute);
        $roleLabel = ucfirst($role);

        if ($role === 'parent') {
            $studentName = $student ? $student->name : 'your child';
            $subject = "Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey";
            $body = '<h3>Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey</h3>'
                . '<p>Dear ' . e($user->name ?: 'Parent') . ',</p>'
                . '<p>A warm welcome to YounimatiQ! Thank you for choosing us as your child’s learning partner. We are delighted to welcome <strong>' . e($studentName) . '</strong> to the YounimatiQ learning community!</p>'
                . '<p>We are excited to have ' . e($studentName) . ' enrolled with us and look forward to making this learning journey engaging, interactive, and meaningful.</p>'
                . '<p>At YounimatiQ, we believe every child has unique potential. Our goal is to create an environment where children explore new ideas, develop practical skills, build confidence, and most importantly, enjoy learning through hands-on experiences.</p>'
                . '<div style="background:#f4f6f9; padding:18px; border-radius:10px; margin:20px 0; border:1px solid #e2e8f0;">'
                . '<h4 style="margin-top:0; color:#046bd2; font-size:16px;">Parent Dashboard Login Credentials</h4>'
                . '<p style="margin:6px 0;"><strong>● Parent Name:</strong> ' . e($user->name ?: 'Parent') . '</p>'
                . '<p style="margin:6px 0;"><strong>● Student Name:</strong> ' . e($studentName) . '</p>'
                . '<p style="margin:6px 0;"><strong>● Parent Dashboard Login:</strong> <a href="' . e($loginUrl) . '" style="color:#046bd2; font-weight:600;">' . e($loginUrl) . '</a></p>'
                . '<p style="margin:6px 0;"><strong>● Registered Email:</strong> ' . e($user->email) . '</p>'
                . '<p style="margin:6px 0;"><strong>● Temporary Password:</strong> <code style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:14px; color:#0f172a;">' . e($password) . '</code></p>'
                . '</div>'
                . '<h4 style="color:#0f172a; margin-top:20px;">What happens next?</h4>'
                . '<p>Our team will coordinate with you regarding the class schedule, session details, and access to the learning resources. We will also keep you updated on your child’s learning progress and share important feedback along the way.</p>'
                . '<p>We encourage you to stay connected with us and share your thoughts or questions whenever needed. Your feedback is valuable to us and helps us make your child’s learning experience even better.</p>'
                . '<p>Once again, thank you for trusting YounimatiQ! We look forward to creating wonderful learning experiences and celebrating ' . e($studentName) . '’s achievements together.</p>'
                . '<p style="margin-top:25px;">Warm regards,<br><strong>Team YounimatiQ</strong><br><a href="https://www.younimatiq.com">www.younimatiq.com</a><br><a href="mailto:admission@younimatiq.com">admission@younimatiq.com</a></p>';
        } else {
            $subject = "Welcome to Younimatiq - Your {$roleLabel} Account";
            $body = '<h3>Welcome to Younimatiq!</h3>'
                . '<p>Hi ' . e(explode(' ', $user->name)[0]) . ',</p>'
                . '<p>An account has been created for you on Younimatiq.</p>'
                . '<div style="background:#f4f6f9; padding:18px; border-radius:10px; margin:20px 0; border:1px solid #e2e8f0;">'
                . '<p style="margin:6px 0;"><strong>● Login URL:</strong> <a href="' . e($loginUrl) . '" style="color:#046bd2; font-weight:600;">' . e($loginUrl) . '</a></p>'
                . '<p style="margin:6px 0;"><strong>● Registered Email:</strong> ' . e($user->email) . '</p>'
                . '<p style="margin:6px 0;"><strong>● Temporary Password:</strong> <code style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:14px; color:#0f172a;">' . e($password) . '</code></p>'
                . '</div>'
                . '<p>Please log in and explore your dashboard.</p>'
                . '<p>Warm regards,<br><strong>Team YounimatiQ</strong><br><a href="https://www.younimatiq.com">www.younimatiq.com</a></p>';
        }

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TemplateMail($subject, $body));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed sending admin-created account email: '.$e->getMessage());
        }
    }
}
