<?php

namespace App\Services;

use App\Mail\TemplateMail;
use App\Models\Announcement;
use App\Models\Booking;
use App\Models\Course;
use App\Models\EmailLog;
use App\Models\Enrollment;
use App\Models\ModuleProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EnrollmentService
{
    public function isEnrolled(int $userId, int $courseId): bool
    {
        return Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function enrollFromPayment(Payment $payment): Enrollment
    {
        $course = Course::findOrFail($payment->course_id);

        $pricingModelRaw = $payment->pricing_model ?: session("payment_{$payment->id}_pricing_model", 'full');
        $customDetails = session("payment_{$payment->id}_custom_enrollment");
        $schedule = is_array($payment->schedule) ? $payment->schedule : null;

        if ($customDetails) {
            $pricingModel = 'per_class';
            $paidClassesCount = $customDetails['classes'] ?? 1;
        } else {
            $pricingModel = $pricingModelRaw === 'per_class' ? 'per_class' : 'full';
            $paidClassesCount = $pricingModel === 'per_class' ? 1 : ($course->number_of_classes ?? 1);
        }

        // An explicit number of classes chosen at enrollment/purchase takes precedence.
        if ($schedule && ! empty($schedule['classes'])) {
            $paidClassesCount = (int) $schedule['classes'];
        }

        $deliveryMode = $payment->delivery_mode
            ?: session("payment_{$payment->id}_delivery_mode")
            ?: ($course->course_type ?: 'live');
        if (! in_array($deliveryMode, ['live', 'self-paced'], true)) {
            $deliveryMode = $course->course_type ?: 'live';
        }

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $payment->student_id, 'course_id' => $course->id],
            [
                'payment_id' => $payment->id,
                'status' => 'active',
                'progress' => 0,
                'pricing_model' => $pricingModel,
                'paid_classes_count' => $paidClassesCount,
                'delivery_mode' => $deliveryMode,
            ]
        );

        if (! $enrollment->wasRecentlyCreated) {
            $enrollment->update([
                'payment_id' => $payment->id,
                'status' => 'active',
                'pricing_model' => $pricingModel,
                'paid_classes_count' => $enrollment->paid_classes_count + $paidClassesCount,
                'delivery_mode' => $deliveryMode,
            ]);
            // Ensure re-enrolls bubble to the top of admin lists sorted by activity.
            $enrollment->touch();
        }

        $scheduleTz = TimezoneService::sanitizeTimezone(
            is_array($schedule) ? ($schedule['timezone'] ?? null) : null
        );

        $bookingData = [
            'tutor_id' => $course->tutor_id,
            'payment_id' => $payment->id,
            'status' => 'active',
            'description' => 'Enrolled in '.$course->title,
            // Remember the timezone the family picked at checkout so the
            // schedule editor and student views resolve it without guessing.
            'timezone' => $scheduleTz,
            'parent_timezone' => $scheduleTz,
        ];

        if ($customDetails && isset($customDetails['class_date']) && isset($customDetails['class_time'])) {
            try {
                $tz = $customDetails['timezone'] ?? 'UTC';
                $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $customDetails['class_date'].' '.$customDetails['class_time'], $tz)->setTimezone('UTC');
                $bookingData['scheduled_at'] = $scheduledAt;
                $bookingData['timezone'] = $tz;
                $bookingData['parent_timezone'] = $tz;
            } catch (\Exception $e) {
                Log::error('Error parsing custom enrollment date/time: '.$e->getMessage());
            }
        }

        $booking = Booking::firstOrCreate(
            [
                'student_id' => $payment->student_id,
                'course_id' => $course->id,
                'session_type' => 'enrollment',
            ],
            $bookingData
        );

        // Re-enroll / new payment: refresh the enrollment placeholder so admin "Live Bookings" updates.
        if (! $booking->wasRecentlyCreated) {
            $booking->fill(array_filter([
                'payment_id' => $payment->id,
                'status' => 'active',
                'description' => 'Enrolled in '.$course->title,
                'timezone' => $scheduleTz ?: $booking->timezone,
                'parent_timezone' => $scheduleTz ?: $booking->parent_timezone,
                'tutor_id' => $booking->tutor_id ?: ($course->tutor_id ?: null),
            ], fn ($v) => $v !== null));
            $booking->save();
            $booking->touch();
        }

        if (! $booking->tutor_id) {
            try {
                app(TutorMatchingService::class)->applyForBooking($booking->fresh());
            } catch (\Throwable $e) {
                Log::warning('Tutor match on enroll failed: '.$e->getMessage());
            }
        }

        // Schedule the purchased classes across the selected recurring days/times.
        if ($schedule && $deliveryMode === 'live') {
            $this->schedulePaidClasses($payment, $course, $enrollment, $schedule);
        }

        Order::updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'order_number' => 'ORD-'.strtoupper(Str::random(8)),
                'user_id' => $payment->student_id,
                'course_id' => $course->id,
                'amount' => $payment->amount,
                'status' => 'completed',
                'payment_method' => $payment->gateway,
            ]
        );

        app(NotificationService::class)->notify(
            $payment->student_id,
            'Enrolled in '.$course->title,
            'You now have access to the course player and materials.',
            route('student.courses.player', $course),
            'success'
        );

        // Re-open dashboard after course opt-in (reactivate suspended / trial-expired accounts)
        $student = User::find($payment->student_id);
        if ($student) {
            $student->forceFill([
                'status' => 'active',
                'trial_ends_at' => null,
            ])->save();
        }

        // Auto-generate Welcome Post / Announcement for newly enrolled family
        if ($course->tutor_id) {
            Announcement::firstOrCreate(
                [
                    'tutor_id' => $course->tutor_id,
                    'course_id' => $course->id,
                    'is_welcome_post' => true,
                ],
                [
                    'title' => 'Welcome to '.$course->title.'!',
                    'content' => $course->welcome_message ?: 'Welcome to our class! We are excited to have you join our learning community.',
                    'video_url' => $course->welcome_video_url,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );
        }

        // Send course assignment & class details email to student and linked parents (without login credentials)
        if (config('mail.enrollment_notifications', false)) {
            try {
                $this->sendEnrollmentNotification($enrollment, $payment);
            } catch (\Throwable $e) {
                Log::warning('Failed sending enrollment notification: '.$e->getMessage());
            }
        }

        return $enrollment;
    }

    /**
     * Create scheduled_class bookings for any purchased classes that are not yet scheduled.
     */
    protected function schedulePaidClasses(Payment $payment, Course $course, Enrollment $enrollment, array $schedule): void
    {
        $alreadyScheduled = Booking::where('student_id', $payment->student_id)
            ->where('course_id', $course->id)
            ->where('session_type', '!=', 'enrollment')
            ->count();

        $paidTotal = (int) ($enrollment->paid_classes_count ?? 0);
        $remaining = $paidTotal - $alreadyScheduled;

        if ($remaining <= 0) {
            return;
        }

        $schedule['classes'] = $remaining;

        app(ClassScheduleService::class)->createBookings(
            $payment->student_id,
            $course->id,
            $schedule,
            $course->tutor_id,
            $payment->id,
            'Scheduled class for '.$course->title
        );
    }

    public function updateProgress(int $userId, int $courseId): void
    {
        $course = Course::with('modules')->find($courseId);
        if (! $course || $course->modules->isEmpty()) {
            return;
        }

        $completedCount = ModuleProgress::where('user_id', $userId)
            ->whereIn('course_module_id', $course->modules->pluck('id'))
            ->where('completed', true)
            ->count();

        $progress = (int) round(($completedCount / $course->modules->count()) * 100);

        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $wasCompleted = $enrollment->status === 'completed';
            $enrollment->update([
                'progress' => $progress,
                'status' => $progress >= 100 ? 'completed' : 'active',
                'completed_at' => $progress >= 100 ? now() : null,
            ]);

            if ($progress >= 100 && ! $wasCompleted) {
                $user = User::find($userId);
                if ($user) {
                    app(EmailService::class)->sendTemplate('review_request', $user->email, [
                        'student_name' => $user->name,
                        'course_title' => $course->title,
                    ]);
                    app(NotificationService::class)->notify(
                        $user,
                        'Course completed: '.$course->title,
                        'Congratulations! Download your certificate of completion.',
                        route('student.courses.certificate', $course),
                        'success'
                    );
                }
            }
        }
    }

    /**
     * Send course assignment & class details notification to student and all linked parents.
     * Note: Does NOT include login credentials/passwords.
     * For 1:1 live classes, the class link is strictly the live meeting link (Zoom / Meet).
     * For self-paced courses, the link is the course player link.
     *
     * @return array<int, string> List of recipient descriptions
     */
    /**
     * @param  string  $audience  one of: all|student|parents
     */
    public function sendEnrollmentNotification(Enrollment $enrollment, ?Payment $payment = null, string $audience = 'all'): array
    {
        if (! config('mail.enrollment_notifications', false)) {
            return [];
        }

        $audience = in_array($audience, ['all', 'student', 'parents'], true) ? $audience : 'all';

        $enrollment->loadMissing(['course.tutor', 'user.parents', 'payment']);

        $student = $enrollment->user;
        $course = $enrollment->course;

        if (! $student || ! $course) {
            return [];
        }

        // Apply admin SMTP configuration so emails dispatch reliably
        try {
            app(EmailService::class)->applyAdminSmtpConfig();
        } catch (\Throwable $e) {
            Log::warning('SMTP config application failed: '.$e->getMessage());
        }

        $isSelfPaced = (
            strtolower((string) $enrollment->delivery_mode) === 'self-paced' ||
            strtolower((string) $course->course_type) === 'self-paced'
        );

        $playerUrl = route('student.courses.player', $course->slug ?: $course->id);

        // 1. Resolve Class Link & Booking
        $booking = Booking::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at')
            ->first();

        if (! $booking) {
            $booking = Booking::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->first();
        }

        if ($isSelfPaced) {
            $classLink = $playerUrl;
            $classLinkLabel = 'Course Player Link';
            $buttonLabel = '🚀 Open Course Player';
        } else {
            if (! $booking) {
                $booking = Booking::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'tutor_id' => $course->tutor_id,
                    'session_type' => 'enrollment',
                    'status' => 'active',
                    'description' => 'Enrolled in '.$course->title,
                ]);
            }
        }

        // 2. Resolve Tutor
        $tutor = $booking?->tutor ?: $course->tutor;
        if (! $tutor && $course->tutor_id) {
            $tutor = User::find($course->tutor_id);
        }
        $tutorName = $tutor ? ('Tutor '.$tutor->name) : 'Dedicated tutor is being assigned by Academic Team';

        // 3. Resolve Class Details
        $classesCount = (int) ($enrollment->paid_classes_count ?: ($course->number_of_classes ?: 1));
        $deliveryMode = ucfirst($enrollment->delivery_mode ?: ($course->course_type ?: ($isSelfPaced ? 'Self-Paced' : 'Live 1:1')));

        // 4. Resolve Schedule
        $scheduleDetails = $isSelfPaced
            ? 'Learn anytime at your own pace'
            : 'Will be confirmed upon tutor assignment';

        if (! $isSelfPaced) {
            if ($booking && $booking->scheduled_at) {
                $scheduleDetails = TimezoneService::formatForEmail(
                    $booking->scheduled_at,
                    $booking->parent_timezone,
                    $booking->timezone,
                    $student->timezone ?? null,
                );
            } elseif ($payment && is_array($payment->schedule) && ! empty($payment->schedule['days'])) {
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $parts = [];
                foreach ($payment->schedule['days'] as $day) {
                    $label = $dayNames[(int) $day] ?? ('Day '.$day);
                    $time = $payment->schedule['times'][$day] ?? $payment->schedule['times'][(string) $day] ?? '';
                    if ($time !== '') {
                        try {
                            $time = Carbon::parse($time)->format('g:i A');
                        } catch (\Throwable $e) {}
                    }
                    $parts[] = trim($label.($time !== '' ? ' at '.$time : ''));
                }
                $start = $payment->schedule['start_date'] ?? '';
                $daysLine = implode(', ', $parts);
                $scheduleDetails = ($start ? "Starting {$start} — " : '').$daysLine;
            }
        }

        $sentRecipients = [];

        // 5. Send to all linked parents
        $parents = collect();
        if ($audience === 'all' || $audience === 'parents') {
            $parents = $student->parents;
            if (! $parents || $parents->isEmpty()) {
                $parentIds = \App\Models\ParentStudent::where('student_id', $student->id)->pluck('parent_id');
                if ($parentIds->isNotEmpty()) {
                    $parents = User::whereIn('id', $parentIds)->get();
                }
            }
        }

        foreach ($parents as $parent) {
            $parentName = $parent->name ?: 'Parent';
            $parentSubject = "Welcome to YounimatiQ! Course Enrollment Confirmed: {$course->title}";

            $parentCardDetails = '<p style="margin:6px 0;"><strong>● Parent Name:</strong> '.e($parentName).'</p>'
                .'<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($student->name).'</p>'
                .'<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> '.e($course->title).'</p>'
                .(! $isSelfPaced ? '<p style="margin:6px 0;"><strong>● Total Sessions:</strong> '.e((string) $classesCount).' Class'.($classesCount > 1 ? 'es' : '').'</p>' : '')
                .'<p style="margin:6px 0;"><strong>● Delivery Mode:</strong> '.e($deliveryMode).'</p>'
                .(! $isSelfPaced ? '<p style="margin:6px 0;"><strong>● Assigned Tutor:</strong> '.e($tutorName).'</p>' : '')
                .(! $isSelfPaced
                    ? '<p style="margin:6px 0;"><strong>● Live Class Meeting Link:</strong> Will be generated and emailed once your tutor is assigned</p>'
                    : '<p style="margin:6px 0;"><strong>● Course Player Link:</strong> <a href="'.e($playerUrl).'" style="color:#046bd2; font-weight:600; word-break:break-all;">'.e($playerUrl).'</a></p>'
                )
                .'<p style="margin:6px 0;"><strong>● Parent Portal:</strong> <a href="'.e(route('login.parent')).'" style="color:#046bd2; font-weight:600;">'.e(route('login.parent')).'</a></p>';

            $parentButtonHtml = ! $isSelfPaced
                ? '<div style="text-align:center; margin:25px 0;"><a href="'.e(route('login.parent')).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:12px 28px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Access Parent Portal</a></div>'
                : '<div style="text-align:center; margin:25px 0;"><a href="'.e($playerUrl).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:12px 28px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Open Course Player</a></div>';

            $parentNextSteps = ! $isSelfPaced
                ? '<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">What happens next?</h4>'
                    .'<p>Our academic team is currently assigning your dedicated expert tutor. Once assigned, you will receive a confirmation email with your live meeting room link (Google Meet / Zoom). You can also track attendance, class progress, quizzes, and tutor feedback directly from your Parent Portal.</p>'
                    .'<p><strong>Tips for a Great Class Experience:</strong><br>'
                    .'• <strong>Important: Please ensure '.e($student->name).' enters the class meeting room with their full name</strong> so the tutor can verify and admit them right away from the waiting room.<br>'
                    .'• Please ensure '.e($student->name).' joins from a desktop, laptop, or tablet with a working camera and microphone in a quiet space.<br>'
                    .'• Once your tutor is assigned, the live class link will be emailed and visible in your portal.</p>'
                : '<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">What happens next?</h4>'
                    .'<p>You can access course videos, materials, and lessons anytime from the student portal and learn at your own pace.</p>';

            $parentBody = '<h3>Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey</h3>'
                .'<p>Dear '.e($parentName).',</p>'
                .'<p>A warm welcome to YounimatiQ! Thank you for choosing us as your child’s learning partner. We are delighted to confirm that <strong>'.e($student->name).'</strong> is now officially enrolled in <strong>'.e($course->title).'</strong>!</p>'
                .'<p>We are excited to have '.e($student->name).' in our learning community and look forward to making this educational journey engaging, interactive, and deeply rewarding.</p>'
                .'<p>At YounimatiQ, we believe every child has unique potential. Our goal is to create an inspiring environment where children explore new ideas, develop practical skills, build confidence, and most importantly, enjoy learning through hands-on experiences.</p>'
                .'<div style="background:#f8fafc; border-left:4px solid #046bd2; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
                .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Enrollment & Class Details</h4>'
                .$parentCardDetails
                .'</div>'
                .$parentButtonHtml
                .$parentNextSteps
                .'<p>We encourage you to stay connected with us and share your thoughts or questions whenever needed. Your feedback is valuable to us and helps us make your child’s learning experience even better.</p>'
                .'<p>Once again, thank you for trusting YounimatiQ! We look forward to creating wonderful learning experiences and celebrating '.e($student->name).'’s achievements together.</p>'
                .'<p style="margin-top:25px; line-height:1.6;">Warm regards,<br>'
                .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
                .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a><br>'
                .'<a href="mailto:admission@younimatiq.com" style="color:#046bd2; text-decoration:none;">admission@younimatiq.com</a></p>';

            try {
                Mail::to($parent->email)->send(new TemplateMail($parentSubject, $parentBody));
                EmailLog::create([
                    'to_email' => $parent->email,
                    'subject' => $parentSubject,
                    'template_slug' => 'enrollment_notification_parent',
                    'status' => 'sent',
                ]);
                $sentRecipients[] = "Parent {$parentName} ({$parent->email})";
            } catch (\Throwable $e) {
                Log::warning("Failed sending enrollment notification to parent {$parent->email}: ".$e->getMessage());
                EmailLog::create([
                    'to_email' => $parent->email,
                    'subject' => $parentSubject,
                    'template_slug' => 'enrollment_notification_parent',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // 6. Send to Student
        if ($audience === 'parents') {
            return $sentRecipients;
        }

        $studentFirstName = explode(' ', trim($student->name))[0];
        $studentSubject = "You're Enrolled in {$course->title}! Here are your class details - YounimatiQ";
            $studentCardDetails = '<p style="margin:6px 0;"><strong>● Student Name:</strong> '.e($student->name).'</p>'
            .'<p style="margin:6px 0;"><strong>● Enrolled Course:</strong> '.e($course->title).'</p>'
            .(! $isSelfPaced ? '<p style="margin:6px 0;"><strong>● Total Sessions:</strong> '.e((string) $classesCount).' Class'.($classesCount > 1 ? 'es' : '').'</p>' : '')
            .'<p style="margin:6px 0;"><strong>● Delivery Mode:</strong> '.e($deliveryMode).'</p>'
            .(! $isSelfPaced ? '<p style="margin:6px 0;"><strong>● Assigned Tutor:</strong> '.e($tutorName).'</p>' : '')
            .(! $isSelfPaced
                ? '<p style="margin:6px 0;"><strong>● Live Class Meeting Link:</strong> Will be generated and emailed once your tutor is assigned</p>'
                : '<p style="margin:6px 0;"><strong>● Course Player Link:</strong> <a href="'.e($playerUrl).'" style="color:#046bd2; font-weight:600; word-break:break-all;">'.e($playerUrl).'</a></p>'
            )
            .'<p style="margin:6px 0;"><strong>● Student Portal:</strong> <a href="'.e(route('login.student')).'" style="color:#046bd2; font-weight:600;">'.e(route('login.student')).'</a></p>';

        $studentButtonHtml = ! $isSelfPaced
            ? '<div style="text-align:center; margin:25px 0;"><a href="'.e(route('login.student')).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:12px 28px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Access Student Portal</a></div>'
            : '<div style="text-align:center; margin:25px 0;"><a href="'.e($playerUrl).'" style="background:#046bd2; color:#ffffff; font-weight:700; font-size:15px; padding:12px 28px; text-decoration:none; border-radius:6px; display:inline-block; box-shadow:0 3px 6px rgba(4,107,210,0.3);">🚀 Open Course Player</a></div>';

        $studentNextSteps = ! $isSelfPaced
            ? '<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">What happens next?</h4>'
                .'<p>Our academic team is currently assigning your dedicated expert tutor. Once assigned, you will receive a confirmation email with your live meeting room link (Google Meet / Zoom). You can also track your course and access learning materials directly from your Student Portal.</p>'
                .'<p><strong>Tips for a Great Class Experience:</strong><br>'
                .'• <strong>Important: Always join class meetings using your full name ('.e($student->name).')</strong> so your tutor can recognize and admit you promptly from the waiting room.<br>'
                .'• Please ensure you join future classes from a desktop, laptop, or tablet with a working camera and microphone in a quiet space.<br>'
                .'• Once your tutor is assigned, your live class join link will be emailed to you and accessible on your dashboard.</p>'
            : '<h4 style="color:#0f172a; margin:22px 0 10px 0; font-size:16px; font-weight:700;">Getting Ready for Class:</h4>'
                .'<p>• Log into the Student Portal to start your lessons and exercises at your own pace.<br>• Learn anytime, anywhere!</p>';

        $studentBody = '<h3>Welcome to YounimatiQ, '.e($studentFirstName).'!</h3>'
            .'<p>Hi '.e($student->name).',</p>'
            .'<p>Great news! You have been successfully enrolled in <strong>'.e($course->title).'</strong>! We are thrilled to welcome you to our learning community and cannot wait to begin this exciting journey together.</p>'
            .'<p>Get ready to explore new concepts, build hands-on projects, solve interactive challenges, and have a ton of fun learning!</p>'
            .'<div style="background:#f8fafc; border-left:4px solid #046bd2; padding:18px 24px; border-radius:8px; margin:22px 0; border:1px solid #e2e8f0;">'
            .'<h4 style="margin:0 0 14px 0; color:#0f172a; font-size:16px; font-weight:700;">Your Class & Enrollment Details</h4>'
            .$studentCardDetails
            .'</div>'
            .$studentButtonHtml
            .$studentNextSteps
            .'<p>If you have any questions or need help joining, reach out to us anytime at <a href="mailto:support@younimatiq.com">support@younimatiq.com</a>.</p>'
            .'<p style="margin-top:25px; line-height:1.6;">Happy Learning!<br>'
            .'<strong style="color:#0f172a;">Team YounimatiQ</strong><br>'
            .'<a href="https://www.younimatiq.com" target="_blank" style="color:#046bd2; text-decoration:none; font-weight:600;">www.younimatiq.com</a></p>';

        try {
            Mail::to($student->email)->send(new TemplateMail($studentSubject, $studentBody));
            EmailLog::create([
                'to_email' => $student->email,
                'subject' => $studentSubject,
                'template_slug' => 'enrollment_notification_student',
                'status' => 'sent',
            ]);
            $sentRecipients[] = "Student {$student->name} ({$student->email})";
        } catch (\Throwable $e) {
            Log::warning("Failed sending enrollment notification to student {$student->email}: ".$e->getMessage());
            EmailLog::create([
                'to_email' => $student->email,
                'subject' => $studentSubject,
                'template_slug' => 'enrollment_notification_student',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $sentRecipients;
    }
}
