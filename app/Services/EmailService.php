<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

class EmailService
{
    public const MAILER_ADMISSION = 'admission';
    public const MAILER_SUPPORT = 'support';

    /**
     * Apply SMTP / from-address from Admin → Email settings (DB), not .env alone.
     * Default profile is admissions (student/parent ops). Use "support" for
     * promotional / helpdesk mail from support@younimatiq.com.
     * Safe to call from HTTP and queue workers.
     */
    public function applyAdminSmtpConfig(string $profile = self::MAILER_ADMISSION): void
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->all();
        $profile = $profile === self::MAILER_SUPPORT ? self::MAILER_SUPPORT : self::MAILER_ADMISSION;

        if ($profile === self::MAILER_SUPPORT) {
            $host = $settings['support_mail_host'] ?? $settings['mail_host'] ?? null;
            $port = $settings['support_mail_port'] ?? $settings['mail_port'] ?? 587;
            $username = $settings['support_mail_username'] ?? $settings['mail_username'] ?? null;
            $password = $settings['support_mail_password'] ?? $settings['mail_password'] ?? '';
            $encryption = $settings['support_mail_encryption'] ?? $settings['mail_encryption'] ?? 'tls';
            $fromAddress = $settings['support_mail_from_address'] ?? $username ?? 'support@younimatiq.com';
            $fromName = $settings['support_mail_from_name'] ?? $settings['mail_from_name'] ?? 'Younimatiq Support';
        } else {
            $host = $settings['mail_host'] ?? null;
            $port = $settings['mail_port'] ?? 587;
            $username = $settings['mail_username'] ?? null;
            $password = $settings['mail_password'] ?? '';
            $encryption = $settings['mail_encryption'] ?? 'tls';
            $fromAddress = $settings['mail_from_address'] ?? $username ?? 'admission@younimatiq.com';
            $fromName = $settings['mail_from_name'] ?? 'Younimatiq Admissions';
        }

        if (empty($host)) {
            return;
        }

        $scheme = match (strtolower((string) $encryption)) {
            'ssl' => 'smtps',
            default => 'smtp',
        };

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => str_replace(' ', '', (string) $password),
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.encryption' => null,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);
    }

    public function sendTemplate(string $slug, string $toEmail, array $variables = [], bool $queue = true): bool
    {
        $template = EmailTemplate::where('slug', $slug)->where('is_active', true)->first();

        if (!$template) {
            $template = $this->defaultTemplate($slug);
            if (!$template) {
                return false;
            }
        }

        $subject = $this->replaceVariables($template->subject, $variables);
        $body = $this->replaceVariables($template->body, $variables);

        if ($queue) {
            SendEmailJob::dispatch($toEmail, $subject, $body, $slug);
            return true;
        }

        try {
            $this->applyAdminSmtpConfig();
            $mail = \Illuminate\Support\Facades\Mail::to($toEmail);
            if ($slug !== 'admin_notification') {
                $adminEmails = $this->getAdminEmails();
                if (!empty($adminEmails)) {
                    $mail->bcc($adminEmails);
                }
            }
            $mail->send(new \App\Mail\TemplateMail($subject, $body));
            \App\Models\EmailLog::create([
                'to_email' => $toEmail,
                'subject' => $subject,
                'template_slug' => $slug,
                'status' => 'sent',
            ]);
            return true;
        } catch (\Exception $e) {
            \App\Models\EmailLog::create([
                'to_email' => $toEmail,
                'subject' => $subject,
                'template_slug' => $slug,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function replaceVariables(string $text, array $variables): string
    {
        $aliases = [
            'parent_name' => [
                'parent_name', 'parent', 'parent name', 'Parent Name',
                'parent_Name', 'Parent_name', 'Parent_Name',
            ],
            'child_name' => [
                'child_name', 'student_name', 'child name', 'Child Name',
                'student name', 'Student Name', 'child', 'Child', 'student', 'Student',
            ],
            'course_name' => [
                'course_name', 'course_title', 'course name', 'Course Name',
                'course title', 'Course Title', 'camp_name', 'Camp Name',
                'course', 'Course', 'program_name', 'Program Name',
            ],
            'login_credentials' => [
                'login_credentials', 'dashboard_login_credentials', 'login credentials',
                'Login Credentials', 'Dashboard Login credentials', 'dashboard login credentials',
                'Dashboard Login Credentials', 'dashboard_credentials', 'Dashboard Credentials',
            ],
            'scheduled_at' => [
                'scheduled_at', 'class_time', 'scheduled time', 'Scheduled Time',
                'date_time', 'Date & Time', 'display_slot', 'slot',
            ],
            'amount' => ['amount', 'amount_paid', 'Amount Paid', 'price', 'Price'],
        ];

        foreach ($aliases as $canonical => $variants) {
            if (isset($variables[$canonical])) {
                foreach ($variants as $var) {
                    $text = str_ireplace([
                        '{{' . $var . '}}',
                        '{{ ' . $var . ' }}',
                        '[' . $var . ']',
                    ], (string) $variables[$canonical], $text);
                }
            } else {
                foreach ($variants as $var) {
                    if (isset($variables[$var])) {
                        $val = (string) $variables[$var];
                        foreach ($variants as $innerVar) {
                            $text = str_ireplace([
                                '{{' . $innerVar . '}}',
                                '{{ ' . $innerVar . ' }}',
                                '[' . $innerVar . ']',
                            ], $val, $text);
                        }
                        break;
                    }
                }
            }
        }

        foreach ($variables as $key => $value) {
            $val = (string) $value;
            $text = str_ireplace([
                '{{' . $key . '}}',
                '{{ ' . $key . ' }}',
                '[' . $key . ']',
            ], $val, $text);

            $spaced = str_replace('_', ' ', $key);
            $text = str_ireplace([
                '{{' . $spaced . '}}',
                '{{ ' . $spaced . ' }}',
                '[' . $spaced . ']',
            ], $val, $text);
        }

        return $text;
    }

    private function defaultTemplate(string $slug): ?EmailTemplate
    {
        $defaults = [
            'welcome_student' => [
                'name' => 'Welcome Student',
                'subject' => 'Welcome to Younimatiq, {{student_name}}!',
                'body' => '<p>Hi {{student_name}},</p><p>Welcome to Younimatiq! Start exploring courses and accelerate your learning journey.</p>',
            ],
            'welcome_tutor' => [
                'name' => 'Welcome Tutor',
                'subject' => 'Welcome to Younimatiq, {{student_name}}!',
                'body' => '<p>Hi {{student_name}},</p><p>Your tutor account is ready. Create courses and start teaching!</p>',
            ],
            'enrollment_confirmation' => [
                'name' => 'Enrollment Confirmation',
                'subject' => 'Welcome to YounimatiQ! Let’s Begin an Exciting Learning Journey',
                'body' => '<p>Dear {{parent_name}},</p>
<p>A warm welcome to YounimatiQ! Thank you for choosing us as your child\'s learning partner. We are delighted to welcome <strong>{{child_name}}</strong> to the YounimatiQ learning community!</p>
<p>We are excited to have <strong>{{child_name}}</strong> enrolled in our <strong>{{course_name}}</strong> program and look forward to making this learning journey engaging, interactive, and meaningful.</p>
<p>At YounimatiQ, we believe every child has unique potential. Our goal is to create an environment where children explore new ideas, develop practical skills, build confidence, and most importantly, enjoy learning through hands-on experiences.</p>
<div style="background-color: #f8fafc; border-left: 4px solid #046bd2; padding: 18px 24px; border-radius: 8px; margin: 24px 0;">
    <h4 style="margin: 0 0 12px 0; color: #0f172a; font-size: 16px; font-weight: 700;">Here are the enrollment details:</h4>
    <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 15px;">
        <li style="margin-bottom: 6px;">● <strong>Parent Name:</strong> {{parent_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Student Name:</strong> {{child_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Enrolled Course:</strong> {{course_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Live Class Link:</strong> {{class_link}}</li>
        <li style="margin-bottom: 6px;">● <strong>Student Dashboard:</strong> {{dashboard_url}}</li>
    </ul>
</div>
<h4 style="color: #0f172a; margin: 24px 0 8px 0; font-size: 16px; font-weight: 700;">What happens next?</h4>
<p>Our team will coordinate with you regarding the class schedule, session details, and access to the learning resources. We will also keep you updated on your child\'s learning progress and share important feedback along the way.</p>
<p>We encourage you to stay connected with us and share your thoughts or questions whenever needed. Your feedback is valuable to us and helps us make your child\'s learning experience even better.</p>
<p>Once again, thank you for trusting YounimatiQ! We look forward to creating wonderful learning experiences and celebrating <strong>{{child_name}}</strong>\'s achievements together.</p>
<p style="margin-top: 24px; line-height: 1.6;">Warm regards,<br>
<strong style="color: #0f172a;">Team YounimatiQ</strong><br>
<a href="https://www.younimatiq.com" target="_blank" style="color: #046bd2; text-decoration: none; font-weight: 600;">www.younimatiq.com</a><br>
<a href="mailto:admission@younimatiq.com" style="color: #046bd2; text-decoration: none;">admission@younimatiq.com</a></p>',
            ],
            'camp_booking_confirmation' => [
                'name' => 'Camp Booking Confirmation',
                'subject' => 'Your YounimatiQ Trial Class is Confirmed! Welcome, {{parent_name}}!',
                'body' => '<p>Dear {{parent_name}},</p>
<p>A warm welcome to YounimatiQ! Thank you for booking a FREE trial class with us. We are delighted that <strong>{{child_name}}</strong> will be joining us for an exciting learning experience!</p>
<p>We have successfully received your trial class booking for <strong>{{course_name}}</strong>.</p>
<p>At YounimatiQ, we believe learning should be fun, interactive, and full of discovery. Our trial session is designed to give <strong>{{child_name}}</strong> an opportunity to experience our teaching approach, interact with the instructor, and explore the subject through engaging, hands-on activities.</p>
<div style="background-color: #f8fafc; border-left: 4px solid #0057bf; padding: 18px 24px; border-radius: 8px; margin: 24px 0;">
    <h4 style="margin: 0 0 12px 0; color: #0f172a; font-size: 16px; font-weight: 700;">Your Trial Class Details</h4>
    <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 15px;">
        <li style="margin-bottom: 6px;">● <strong>Parent Name:</strong> {{parent_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Student Name:</strong> {{child_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Course:</strong> {{course_name}}</li>
        <li style="margin-bottom: 6px;">● <strong>Class Type:</strong> Free Trial Session</li>
        {{scheduled_time_row}}
    </ul>
    {{meeting_button}}
</div>
{{cant_attend_button}}
<h4 style="color: #0f172a; margin: 24px 0 8px 0; font-size: 16px; font-weight: 700;">What to Expect During the Trial Class?</h4>
<ul style="padding-left: 20px; margin: 0 0 16px 0; line-height: 1.8; color: #334155;">
    <li>An engaging introduction to the course and its concepts.</li>
    <li>Interactive, hands-on learning activities.</li>
    <li>An opportunity to experience our instructor\'s teaching style.</li>
    <li>A chance for your child to explore, participate, and ask questions.</li>
</ul>
<h4 style="color: #0f172a; margin: 24px 0 8px 0; font-size: 16px; font-weight: 700;">A Little Preparation Before the Class</h4>
<p>Please ensure that <strong>{{child_name}}</strong> joins from a laptop or desktop with a stable internet connection, a working microphone, and a quiet learning space. Any course-specific software or setup requirements will be communicated separately, if applicable.</p>
<p>We look forward to meeting <strong>{{child_name}}</strong> and making this first learning experience exciting and memorable!</p>
<p>If you have any questions or need assistance before the session, simply reply to this email (<a href="mailto:support@younimatiq.com" style="color: #046bd2; text-decoration: none;">support@younimatiq.com</a>). Our team will be happy to help.</p>
<p style="margin-top: 20px; font-weight: 700; color: #0057bf; font-size: 16px;">See you in class!</p>
<p style="margin-top: 24px; line-height: 1.6;">Warm regards,<br>
<strong style="color: #0f172a;">Team YounimatiQ</strong><br>
<a href="https://www.younimatiq.com" target="_blank" style="color: #046bd2; text-decoration: none; font-weight: 600;">www.younimatiq.com</a></p>',
            ],
            'camp_follow_up' => [
                'name' => 'Camp Follow Up',
                'subject' => 'How was the trial class, {{parent_name}}?',
                'body' => '<p>Hi {{parent_name}},</p><p>We hope {{child_name}} enjoyed {{camp_name}}. Explore our full courses today!</p>',
            ],
            'password_reset' => [
                'name' => 'Password Reset',
                'subject' => 'Reset your Younimatiq password',
                'body' => '<p>Click here to reset your password: <a href="{{reset_url}}">{{reset_url}}</a></p>',
            ],
            'tutor_mention_notification' => [
                'name' => 'Tutor Mention Notification',
                'subject' => '{{tutor_name}} mentioned {{student_name}} in {{course_title}}',
                'body' => '<p>Hi {{student_name}}\'s Family,</p><p>{{tutor_name}} mentioned {{student_name}} in <strong>{{course_title}}</strong> classroom:</p>blockquote><p>"{{comment_excerpt}}"</p></blockquote>',
            ],
            'class_reminder' => [
                'name' => 'Class Reminder',
                'subject' => 'Reminder: {{course_title}} class at {{class_time}}',
                'body' => '<p>Hi {{student_name}},</p><p>Your class <strong>{{course_title}}</strong> starts at {{class_time}} ({{timezone}}).</p>',
            ],
            'contact_received' => [
                'name' => 'Contact Form Received',
                'subject' => 'We received your message',
                'body' => '<p>Hi {{name}},</p><p>Thank you for contacting Younimatiq. We will respond shortly.</p>',
            ],
            'qa_answered' => [
                'name' => 'Q&A Answered',
                'subject' => 'Your question was answered',
                'body' => '<p>Hi {{student_name}},</p><p>Your tutor answered your question about {{course_title}}.</p>',
            ],
            'review_request' => [
                'name' => 'Review Request',
                'subject' => 'How was {{course_title}}?',
                'body' => '<p>Hi {{student_name}},</p><p>Congratulations on completing {{course_title}}! Please leave a review.</p>',
            ],
            'tutor_payout_summary' => [
                'name' => 'Tutor Payout Summary',
                'subject' => 'Your monthly earnings: ${{amount}}',
                'body' => '<p>Hi {{student_name}},</p><p>Your earnings this month: ${{amount}}.</p>',
            ],
            'admin_notification' => [
                'name' => 'Admin Notification',
                'subject' => '{{subject}}',
                'body' => '<p>Hi Admin,</p><p>{{message}}</p>',
            ],
        ];

        if (!isset($defaults[$slug])) {
            return null;
        }

        return EmailTemplate::firstOrCreate(
            ['slug' => $slug],
            array_merge($defaults[$slug], ['is_active' => true])
        );
    }

    public function sendCustomEmail(
        string $toEmail,
        string $subject,
        string $body,
        bool $queue = true,
        string $mailerProfile = self::MAILER_ADMISSION
    ): bool {
        if ($queue) {
            \App\Jobs\SendEmailJob::dispatch($toEmail, $subject, $body, 'custom', $mailerProfile);
            return true;
        }

        try {
            $this->applyAdminSmtpConfig($mailerProfile);
            $mail = \Illuminate\Support\Facades\Mail::to($toEmail);
            $mail->send(new \App\Mail\TemplateMail($subject, $body));
            \App\Models\EmailLog::create([
                'to_email' => $toEmail,
                'subject' => $subject,
                'template_slug' => 'custom',
                'status' => 'sent',
            ]);
            return true;
        } catch (\Exception $e) {
            \App\Models\EmailLog::create([
                'to_email' => $toEmail,
                'subject' => $subject,
                'template_slug' => 'custom',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Email student + linked parents when a post-class activity is assigned.
     * Template: Mail Templates.pdf — "Your Class Learning Notes Are Ready!"
     */
    public function notifyAssignmentAssigned(\App\Models\Assignment $assignment, array $studentIds): void
    {
        $assignment->loadMissing('course');
        $title = trim((string) $assignment->title) ?: 'Post-class activity';
        $courseName = $assignment->course?->title ?: $title;
        $activityUrl = route('student.assignments.take', $assignment);
        $students = \App\Models\User::whereIn('id', $studentIds)->with('parents')->get();

        $this->notifyAdmins(
            "New post-class activity assigned: {$title}",
            '<p>The post-class activity <strong>'.e($title).'</strong> for <strong>'.e($courseName).'</strong> was assigned to '.count($students).' student(s).</p>'
        );

        foreach ($students as $student) {
            if (! empty($student->email)) {
                $this->sendCustomEmail(
                    $student->email,
                    'Your Class Learning Notes Are Ready! 🌟 Review, Practise & Shine!',
                    $this->postClassActivityStudentBody($student->name, $courseName, $title, $activityUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }

            foreach ($student->parents as $parent) {
                if (empty($parent->email)) {
                    continue;
                }
                $this->sendCustomEmail(
                    $parent->email,
                    "{$student->name}'s Class Learning Notes Are Ready! 🌟",
                    $this->postClassActivityParentBody($parent->name, $student->name, $courseName, $title, $activityUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }
        }
    }

    /**
     * Email student + linked parents when a quiz is assigned.
     * Template: Mail Templates (1).pdf — "Your Post-Class Quiz Is Ready!"
     */
    public function notifyQuizAssigned(\App\Models\Quiz $quiz, $courseId, array $studentIds = []): void
    {
        $studentIdsToEmail = [];
        if ($courseId) {
            $studentIdsToEmail = array_merge(
                $studentIdsToEmail,
                \App\Models\Enrollment::where('course_id', $courseId)
                    ->whereIn('status', ['active', 'completed'])
                    ->pluck('user_id')
                    ->all()
            );
        }
        if (! empty($studentIds)) {
            $studentIdsToEmail = array_merge($studentIdsToEmail, $studentIds);
        }
        $studentIdsToEmail = array_values(array_unique(array_filter($studentIdsToEmail)));
        if ($studentIdsToEmail === []) {
            return;
        }

        $quiz->loadMissing('course');
        $course = $courseId
            ? (\App\Models\Course::find($courseId) ?: $quiz->course)
            : $quiz->course;
        $courseName = $course?->title ?: ($quiz->title ?: 'your course');
        $quizTitle = trim((string) $quiz->title) ?: 'Post-Class Quiz';
        $quizUrl = route('student.quizzes.take', $quiz);
        $students = \App\Models\User::whereIn('id', $studentIdsToEmail)->with('parents')->get();

        $this->notifyAdmins(
            "New quiz assigned: {$quizTitle}",
            '<p>The quiz <strong>'.e($quizTitle).'</strong> for <strong>'.e($courseName).'</strong> was assigned to '.count($students).' student(s).</p>'
        );

        foreach ($students as $student) {
            if (! empty($student->email)) {
                $this->sendCustomEmail(
                    $student->email,
                    'Your Post-Class Quiz Is Ready! 🎯 Time to Test Your Learning!',
                    $this->postClassQuizStudentBody($student->name, $courseName, $quizTitle, $quizUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }

            foreach ($student->parents as $parent) {
                if (empty($parent->email)) {
                    continue;
                }
                $this->sendCustomEmail(
                    $parent->email,
                    "{$student->name}'s Post-Class Quiz Is Ready! 🎯",
                    $this->postClassQuizParentBody($parent->name, $student->name, $courseName, $quizTitle, $quizUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }
        }
    }

    /**
     * Email tutors when a training module is newly assigned.
     *
     * @param  array<int, int|\App\Models\User>  $tutors
     */
    public function notifyTutorTrainingAssigned(\App\Models\TutorTrainingModule $module, array $tutors): void
    {
        $tutorModels = collect($tutors)
            ->map(function ($tutor) {
                if ($tutor instanceof \App\Models\User) {
                    return $tutor->role === 'tutor' ? $tutor : null;
                }

                $id = (int) $tutor;
                if ($id < 1) {
                    return null;
                }

                return \App\Models\User::query()
                    ->where('role', 'tutor')
                    ->whereKey($id)
                    ->first();
            })
            ->filter(fn ($t) => $t instanceof \App\Models\User && filled($t->email))
            ->unique(fn (\App\Models\User $t) => (int) $t->id)
            ->values();

        if ($tutorModels->isEmpty()) {
            return;
        }

        $moduleTitle = trim((string) $module->title) ?: 'Training module';
        $trainingUrl = route('tutor.training.index');
        $itemCount = method_exists($module, 'items')
            ? (int) ($module->items_count ?? $module->items()->count())
            : 0;

        $recipientList = $tutorModels
            ->map(fn ($t) => e(($t->name ?: 'Tutor').' &lt;'.$t->email.'&gt;'))
            ->implode(', ');

        $this->notifyAdmins(
            "Training assigned: {$moduleTitle}",
            '<p>The training module <strong>'.e($moduleTitle).'</strong> was assigned to:</p><p>'.$recipientList.'</p>'
        );

        foreach ($tutorModels as $tutor) {
            $name = $tutor->name ?: 'Tutor';
            $this->sendCustomEmail(
                $tutor->email,
                "New training assigned to {$name}: {$moduleTitle}",
                $this->tutorTrainingAssignedBody($name, $moduleTitle, $trainingUrl, $itemCount),
                true,
                self::MAILER_SUPPORT
            );
        }
    }

    private function tutorTrainingAssignedBody(string $tutorName, string $moduleTitle, string $trainingUrl, int $itemCount): string
    {
        $itemsNote = $itemCount > 0
            ? '<p>This module includes <strong>'.$itemCount.'</strong> item'.($itemCount === 1 ? '' : 's').' (documents, videos, quizzes, or assignments) for you to complete.</p>'
            : '<p>Open your training page to review the materials and complete the required items.</p>';

        return '<p>Hi '.e($tutorName).',</p>'
            .'<p>A new training module has been assigned to you on YounimatiQ:</p>'
            .'<p style="font-size:18px;font-weight:700;color:#0f172a;margin:16px 0;">'.e($moduleTitle).'</p>'
            .$itemsNote
            .'<p style="margin:22px 0;"><a href="'.e($trainingUrl).'" style="display:inline-block;background:#0057bf;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Open my training</a></p>'
            .'<p>Please complete the module at your earliest convenience. If you have questions, reply to this email or contact support@younimatiq.com.</p>'
            .'<p style="margin-top:28px;line-height:1.7;">Warm regards,<br><strong>Team YounimatiQ</strong><br>'
            .'<a href="https://www.younimatiq.com" style="color:#046bd2;font-weight:600;text-decoration:none;">www.younimatiq.com</a></p>';
    }

    /**
     * Email student + linked parents when an Activity Link / gamified mission is assigned.
     * Template: Mail Templates (2).pdf — "A New Mission Awaits!"
     *
     * @param  array{key:string,title?:string,subject?:string|null,summary?:string|null}  $gameMeta
     * @param  array<int, int>  $studentIds
     */
    public function notifyActivityLinkAssigned(array $gameMeta, array $studentIds): void
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if ($studentIds === []) {
            return;
        }

        $missionTitle = trim((string) ($gameMeta['title'] ?? '')) ?: 'Live Gamified Worksheet Mission';
        $courseName = trim((string) ($gameMeta['subject'] ?? '')) ?: $missionTitle;
        $gameKey = (string) ($gameMeta['key'] ?? '');
        $missionUrl = $gameKey !== ''
            ? route('student.games.show', $gameKey)
            : route('student.activities');
        $students = \App\Models\User::whereIn('id', $studentIds)->with('parents')->get();

        $this->notifyAdmins(
            "New gamified mission assigned: {$missionTitle}",
            '<p>The activity link <strong>'.e($missionTitle).'</strong> was assigned to '.count($students).' student(s).</p>'
        );

        foreach ($students as $student) {
            if (! empty($student->email)) {
                $this->sendCustomEmail(
                    $student->email,
                    'A New Mission Awaits! Complete It, Earn XPs & Climb the Leaderboard!',
                    $this->activityLinkStudentBody($student->name, $courseName, $missionTitle, $missionUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }

            foreach ($student->parents as $parent) {
                if (empty($parent->email)) {
                    continue;
                }
                $this->sendCustomEmail(
                    $parent->email,
                    "A New Mission Awaits for {$student->name}! Complete It, Earn XPs & Climb the Leaderboard!",
                    $this->activityLinkParentBody($parent->name, $student->name, $courseName, $missionTitle, $missionUrl),
                    true,
                    self::MAILER_ADMISSION
                );
            }
        }
    }

    private function academicTeamFooter(string $closing = 'Happy Learning!'): string
    {
        return '<p style="margin-top:28px;line-height:1.7;color:#0f172a;">'
            .'<strong>'.e($closing).'</strong><br>'
            .'Academic Evaluation Team<br>'
            .'<em>Learn. Create. Innovate.</em><br>'
            .'<a href="https://www.younimatiq.com" style="color:#046bd2;font-weight:600;text-decoration:none;">www.younimatiq.com</a><br>'
            .'<a href="mailto:admission@younimatiq.com" style="color:#046bd2;text-decoration:none;">admission@younimatiq.com</a>'
            .'</p>';
    }

    private function activityLinkStudentBody(string $studentName, string $courseName, string $missionTitle, string $missionUrl): string
    {
        return '<p>Hi '.e($studentName).',</p>'
            .'<p>Ready for your next learning adventure? 🎮</p>'
            .'<p>Your educator has just assigned a <strong>NEW LIVE GAMIFIED WORKSHEET MISSION</strong> for <strong>'.e($courseName).'</strong>!'
            .( $missionTitle !== $courseName ? ' (<strong>'.e($missionTitle).'</strong>)' : '' )
            .'</p>'
            .'<p>This isn\'t just another worksheet. It\'s a fun, mission-based learning challenge where you get to test your knowledge, solve exciting challenges, earn Experience Points (XPs), and climb the YounimatiQ leaderboard!</p>'
            .'<h3 style="color:#0f172a;font-size:16px;margin:24px 0 10px;">🎯 Your Mission Starts Now!</h3>'
            .'<p>Here\'s how to get ahead:</p>'
            .'<ol style="padding-left:20px;line-height:1.8;color:#334155;">'
            .'<li><strong>Launch Your Mission:</strong> Click the link below to access your assigned gamified worksheet.</li>'
            .'<li><strong>Solve the Challenges:</strong> Use what you\'ve learned in class to complete the mission.</li>'
            .'<li><strong>Earn XPs:</strong> Every successful learning challenge brings you closer to earning more Experience Points.</li>'
            .'<li><strong>Climb the Leaderboard:</strong> Collect XPs, improve your ranking, and challenge yourself to reach the top!</li>'
            .'</ol>'
            .'<p style="margin:22px 0;"><a href="'.e($missionUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Launch mission</a></p>'
            .'<p><strong>🏆 Can You Make It to the Top?</strong><br>'
            .'Every mission completed is another step towards becoming a YounimatiQ Learning Champion!</p>'
            .'<p>A little reminder for our young learners: Focus on learning, enjoy the challenges, and give every mission your best shot. The XPs and leaderboard rankings will follow!</p>'
            .$this->academicTeamFooter('Happy Mission Solving!');
    }

    private function activityLinkParentBody(string $parentName, string $studentName, string $courseName, string $missionTitle, string $missionUrl): string
    {
        return '<p>Hi '.e($parentName).',</p>'
            .'<p>Ready for <strong>'.e($studentName).'</strong>\'s next learning adventure? 🎮</p>'
            .'<p>Their educator has just assigned a <strong>NEW LIVE GAMIFIED WORKSHEET MISSION</strong> for <strong>'.e($courseName).'</strong>!'
            .( $missionTitle !== $courseName ? ' (<strong>'.e($missionTitle).'</strong>)' : '' )
            .'</p>'
            .'<p>This mission-based challenge helps them practise class concepts, earn Experience Points (XPs), and climb the YounimatiQ leaderboard.</p>'
            .'<p style="margin:22px 0;"><a href="'.e($missionUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Open mission in student portal</a></p>'
            .'<p>Encourage them to focus on learning, enjoy the challenges, and give every mission their best shot!</p>'
            .$this->academicTeamFooter('Happy Mission Solving!');
    }

    private function postClassActivityStudentBody(string $studentName, string $courseName, string $activityTitle, string $activityUrl): string
    {
        return '<p>Hi '.e($studentName).',</p>'
            .'<p>Great learning today!</p>'
            .'<p>Your <strong>Post-Class Summary and Learning Notes</strong> for <strong>'.e($courseName).'</strong> are now available'
            .( $activityTitle !== $courseName ? ' (<strong>'.e($activityTitle).'</strong>)' : '' )
            .'.</p>'
            .'<p>These notes capture the key concepts covered in today\'s session, important explanations, examples, and learning takeaways.</p>'
            .'<h3 style="color:#0f172a;font-size:16px;margin:24px 0 10px;">Your Next Learning Mission</h3>'
            .'<p>Before attempting the worksheets, we encourage you to:</p>'
            .'<ol style="padding-left:20px;line-height:1.8;color:#334155;">'
            .'<li><strong>Go Through the Notes:</strong> Take a few minutes to read the class summary and revisit the concepts learned.</li>'
            .'<li><strong>Refresh Your Understanding:</strong> Pay attention to the examples and explanations shared by your educator.</li>'
            .'<li><strong>Attempt the Worksheets:</strong> Once you feel confident, move on to the practice worksheets and apply what you have learned.</li>'
            .'<li><strong>Learn Independently:</strong> Try solving the questions on your own before referring back to the notes.</li>'
            .'</ol>'
            .'<p style="margin:22px 0;"><a href="'.e($activityUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Open learning notes</a></p>'
            .'<p><strong>A Little Reminder for Our Young Learner!</strong><br>'
            .'Learning doesn\'t end when the class is over. Revisiting what you learned and practising independently is what helps turn new concepts into lasting knowledge.</p>'
            .'<p>Keep exploring, keep practising, and keep growing!</p>'
            .$this->academicTeamFooter();
    }

    private function postClassActivityParentBody(string $parentName, string $studentName, string $courseName, string $activityTitle, string $activityUrl): string
    {
        return '<p>Hi '.e($parentName).',</p>'
            .'<p>Great learning today for <strong>'.e($studentName).'</strong>!</p>'
            .'<p>Their <strong>Post-Class Summary and Learning Notes</strong> for <strong>'.e($courseName).'</strong> are now available'
            .( $activityTitle !== $courseName ? ' (<strong>'.e($activityTitle).'</strong>)' : '' )
            .'.</p>'
            .'<p>These notes capture the key concepts from the session, important explanations, examples, and learning takeaways to support independent practice at home.</p>'
            .'<h3 style="color:#0f172a;font-size:16px;margin:24px 0 10px;">Suggested next steps</h3>'
            .'<ol style="padding-left:20px;line-height:1.8;color:#334155;">'
            .'<li>Encourage '.e($studentName).' to review the class summary and notes.</li>'
            .'<li>Then attempt the practice worksheets independently.</li>'
            .'<li>Celebrate effort and curiosity — that\'s how lasting learning happens.</li>'
            .'</ol>'
            .'<p style="margin:22px 0;"><a href="'.e($activityUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">View activity in student portal</a></p>'
            .$this->academicTeamFooter();
    }

    private function postClassQuizStudentBody(string $studentName, string $courseName, string $quizTitle, string $quizUrl): string
    {
        return '<p>Hi '.e($studentName).',</p>'
            .'<p>Great job completing your recent learning session with YounimatiQ!</p>'
            .'<p>Your <strong>Post-Class Quiz</strong> for <strong>'.e($courseName).'</strong> has now been assigned and is ready to attempt'
            .( $quizTitle ? ' (<strong>'.e($quizTitle).'</strong>)' : '' )
            .'.</p>'
            .'<p>This quiz is designed to help you revisit the concepts covered in class, check your understanding, and build confidence through independent practice.</p>'
            .'<h3 style="color:#0f172a;font-size:16px;margin:24px 0 10px;">Your Next Learning Challenge</h3>'
            .'<p>Here\'s what we recommend:</p>'
            .'<ol style="padding-left:20px;line-height:1.8;color:#334155;">'
            .'<li><strong>Review Before You Begin:</strong> Go through the class summary and learning notes shared by your educator.</li>'
            .'<li><strong>Think Independently:</strong> Read each question carefully and try to answer using your own understanding.</li>'
            .'<li><strong>Attempt the Quiz:</strong> Complete all the assigned questions without rushing.</li>'
            .'<li><strong>Learn from the Experience:</strong> Review your answers and identify concepts that may need more practice.</li>'
            .'</ol>'
            .'<p style="margin:22px 0;"><a href="'.e($quizUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Start the quiz</a></p>'
            .'<p><strong>Ready for the Challenge?</strong><br>Keep learning, keep exploring, and celebrate every little achievement!</p>'
            .$this->academicTeamFooter();
    }

    private function postClassQuizParentBody(string $parentName, string $studentName, string $courseName, string $quizTitle, string $quizUrl): string
    {
        return '<p>Hi '.e($parentName).',</p>'
            .'<p>Great job by <strong>'.e($studentName).'</strong> in their recent YounimatiQ learning session!</p>'
            .'<p>A <strong>Post-Class Quiz</strong> for <strong>'.e($courseName).'</strong> has been assigned and is ready to attempt'
            .( $quizTitle ? ' (<strong>'.e($quizTitle).'</strong>)' : '' )
            .'.</p>'
            .'<p>This quiz helps revisit class concepts, check understanding, and build confidence through independent practice.</p>'
            .'<p style="margin:22px 0;"><a href="'.e($quizUrl).'" style="display:inline-block;background:#046bd2;color:#fff;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px;">Open quiz in student portal</a></p>'
            .'<p>Keep learning, keep exploring, and celebrate every little achievement!</p>'
            .$this->academicTeamFooter();
    }

    /**
     * Recipients for operational admin alerts (not SMTP credentials).
     */
    public function getAdminEmails(): array
    {
        return array_values(array_filter(array_unique([
            \App\Models\Setting::where('key', 'admin_email')->value('value'),
        ])));
    }

    /**
     * HR inbox for tutor-training submissions. Uses admin-panel SMTP; only the To: address differs.
     */
    public function getHrEmails(): array
    {
        $default = 'hr@younimatiq.com';
        $raw = trim((string) (\App\Models\Setting::where('key', 'hr_review_emails')->value('value') ?? ''));
        if ($raw === '') {
            $raw = $default;
        }

        $emails = array_values(array_unique(array_filter(array_map(
            'strtolower',
            array_map('trim', preg_split('/[,;]+/', $raw) ?: [])
        ), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));

        if (! in_array($default, $emails, true)) {
            $emails[] = $default;
        }

        return $emails;
    }

    public function notifyHr(string $subject, string $message): void
    {
        foreach ($this->getHrEmails() as $email) {
            $this->sendTemplate('admin_notification', $email, [
                'subject' => $subject,
                'message' => $message,
            ]);
        }
    }

    public function notifyAdmins(string $subject, string $message): void
    {
        $adminEmails = $this->getAdminEmails();
        foreach ($adminEmails as $email) {
            if ($email) {
                $this->sendTemplate('admin_notification', trim($email), [
                    'subject' => $subject,
                    'message' => $message,
                ]);
            }
        }

        // Also create in-app notifications for admin panel bell
        $plainBody = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $message))));
        $admins = \App\Models\User::where('role', 'admin')->where('status', 'active')->get();
        foreach ($admins as $admin) {
            try {
                app(NotificationService::class)->notify(
                    $admin,
                    $subject,
                    \Illuminate\Support\Str::limit($plainBody, 240),
                    route('admin.dashboard'),
                    'admin'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Admin in-app notification failed: '.$e->getMessage());
            }
        }
    }
}
