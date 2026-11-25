<?php

namespace App\Jobs;

use App\Models\CampBooking;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $booking;
    public $action;

    public function __construct(CampBooking $booking, string $action = 'all')
    {
        $this->booking = $booking;
        $this->action = $action;
    }

    public function handle(EmailService $emailService): void
    {
        if (in_array($this->action, ['all', 'parent'])) {
            $this->sendCampConfirmation($this->booking, $emailService);
        }
        if (in_array($this->action, ['all', 'admin'])) {
            $this->sendAdminNotification($this->booking);
        }
    }

    private function generateZoomAndSchedule(CampBooking $booking): void
    {
        $parentTz = $booking->timezone ?: session('timezone', 'America/New_York');
        $tutorTz = 'America/Los_Angeles';

        // If scheduled_at is already set (from the new timing fields), we use it. Otherwise default to tomorrow 5 PM
        if ($booking->scheduled_at) {
            $scheduledAt = \Carbon\Carbon::parse($booking->scheduled_at);
        } else {
            $scheduledAt = \Carbon\Carbon::now()->addDays(1)->setHour(17)->setMinute(0)->setSecond(0);
        }

        try {
            $zoomService = app(\App\Services\ZoomService::class);
            $fields = $zoomService->ensureMeetingFields(
                $booking,
                $booking->camp_name ?: 'Younimatiq Session',
                $scheduledAt->toDateTimeString(),
                45,
                $parentTz
            );

            $booking->update(array_merge([
                'parent_timezone' => $parentTz,
                'tutor_timezone' => $tutorTz,
                'scheduled_at' => $scheduledAt,
            ], $fields));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Meeting generation notice: '.$e->getMessage());
        }
    }

    private function sendCampConfirmation(CampBooking $booking, EmailService $emailService): void
    {
        $this->generateZoomAndSchedule($booking);
        $booking->refresh();

        $joinUrl = $booking->zoom_join_url ?: url('/student/dashboard');
        $meetLabel = app(\App\Services\ZoomService::class)->providerLabel($booking->meeting_provider);

        $parentFirstName = explode(' ', $booking->parent_name)[0] ?? 'Parent';
        $courseName = $booking->camp_name
            ? str_replace(['Free Trial - ', 'Consultation - ', 'Demo Class - '], '', $booking->camp_name)
            : 'a course';

        $isConsultation = str_starts_with((string) $booking->camp_name, 'Consultation');
        $sessionNoun = $isConsultation ? 'free consultation' : 'free trial class';
        $subject = $isConsultation
            ? 'Free Consultation Confirmation - Younimatiq'
            : 'Free Trial Class Confirmation - Younimatiq';
        $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">'
            . '<p>Dear ' . e($parentFirstName) . ',</p>'
            . '<p>Thank you for booking a ' . $sessionNoun . ' for <strong>' . e($courseName) . '</strong> with Younimatiq. We\'re glad you\'re here, and we\'re looking forward to meeting your child.</p>'
            . '<p>Your ' . $sessionNoun . ' is confirmed for:</p>'
            . '<ul style="list-style: none; padding: 0;">'
            . '<li style="margin-bottom: 8px;"><strong>Course:</strong> ' . e($courseName) . '</li>'
            . '<li style="margin-bottom: 8px;"><strong>Student:</strong> ' . e($booking->child_name) . '</li>'
            . '<li style="margin-bottom: 8px;"><strong>Date:</strong> ' . e($booking->scheduled_at ? \App\Services\TimezoneService::formatDateForEmail($booking->scheduled_at, $booking->parent_timezone, $booking->timezone) : 'Scheduled Upon Confirmation') . '</li>'
            . '<li style="margin-bottom: 8px;"><strong>Time:</strong> ' . e($booking->scheduled_at ? \App\Services\TimezoneService::formatTimeForEmail($booking->scheduled_at, $booking->parent_timezone, $booking->timezone) : 'Scheduled Upon Confirmation') . '</li>'
            . '</ul>'
            . '<p style="margin:24px 0;text-align:center;">'
            . '<a href="' . e($joinUrl) . '" style="background:#2D8CFF;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;font-size:16px;">Join ' . e($meetLabel) . ' Meeting</a>'
            . '</p>'
            . '<p style="font-size:13px;color:#666;text-align:center;">Or copy this link: <a href="' . e($joinUrl) . '" style="color:#2D8CFF;">' . e($joinUrl) . '</a></p>'
            . '<p style="background: #f0f7ff; border-left: 4px solid #0057bf; padding: 12px 16px; border-radius: 6px; font-size: 14px; color: #1e293b; margin: 18px 0;"><strong>Important:</strong> Please ensure your child joins the meeting using their <strong>full name (' . e($booking->child_name) . ')</strong> so the teacher can quickly verify and admit them from the waiting room.</p>'
            . '<h4 style="margin-top: 24px; color: #0057bf;">WHAT HAPPENS IN THIS ' . strtoupper($sessionNoun) . '?</h4>'
            . '<p>This is a real teaching session built around your child. A subject teacher will join you to:</p>'
            . '<ul style="line-height: 1.6;">'
            . '<li>Understand your child\'s current level, strengths, and learning goals</li>'
            . '<li>Give your child a firsthand experience of our teaching style</li>'
            . '<li>Help you choose the course that delivers real, valued outcomes</li>'
            . '</ul>'
            . ($isConsultation ? '' : (
                '<h4 style="margin-top: 24px; color: #0057bf;">AFTER THE FREE TRIAL CLASS</h4>'
                . '<p>You\'ll get <strong>9 days of free dashboard access</strong> after the class, so your child can explore the learning platform and get a real feel for everyday learning with Younimatiq.</p>'
                . '<p>Log in anytime at <a href="' . e(route('login.student')) . '" style="color: #0057bf;">' . e(route('login.student')) . '</a>. If a temporary password was created for you, it was shown on the booking success page and follows the pattern <em>firstname@123</em>.</p>'
            ))
            . '<p>Only once you and your child both feel confident does enrollment become the next step. There\'s no pressure, and no obligation, at any stage.</p>'
            . \App\Http\Controllers\SessionNoteController::emailButtonHtml($booking->zoom_join_url, [
                'kind' => 'trial',
                'ref' => 'camp:'.$booking->id,
                'session' => ($isConsultation ? 'Consultation - ' : 'Free Trial - ').$courseName,
                'when' => $booking->scheduled_at
                    ? \App\Services\TimezoneService::formatForEmail($booking->scheduled_at, $booking->parent_timezone, $booking->timezone)
                    : null,
                'name' => $booking->parent_name ?: $booking->child_name,
                'email' => $booking->email,
            ])
            . '<p>We look forward to meeting you and your child soon!</p>'
            . '<p>Warm regards,<br>The Younimatiq Team</p>'
            . '<div style="font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 30px;">'
            . '<p style="margin: 0 0 5px;">Questions before then? Just reply to this email, or reach us at <a href="mailto:support@younimatiq.com" style="color: #0057bf;">support@younimatiq.com</a>.</p>'
            . '<p style="margin: 0 0 5px;">Younimatiq · United States</p>'
            . '<p style="margin: 0 0 5px;">Phone numbers: +1 (202) 794 4843</p>'
            . '</div>'
            . '</div>';

        try {
            \Illuminate\Support\Facades\Mail::to($booking->email)->send(new \App\Mail\TemplateMail($subject, $body));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed sending parent booking confirmation email: ' . $e->getMessage());
        }
    }

    private function sendAdminNotification(CampBooking $booking): void
    {
        $this->generateZoomAndSchedule($booking);
        $booking->refresh();

        $scheduledParent = $booking->scheduled_at
            ? \App\Services\TimezoneService::formatForEmail($booking->scheduled_at, $booking->parent_timezone, $booking->timezone)
            : 'N/A';
        $scheduledTeacher = $booking->scheduled_at
            ? \App\Services\TimezoneService::formatForEmail(
                $booking->scheduled_at,
                $booking->tutor_timezone,
                $booking->tutor?->timezone,
                $booking->parent_timezone,
                $booking->timezone,
            )
            : 'N/A';

        try {
            // In-app Notification for Admin Panel
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'booking',
                    'title' => 'New Class Booked: ' . $booking->child_name,
                    'body' => 'Child: ' . $booking->child_name . ' | Program: ' . $booking->camp_name . ' | Parent Email: ' . $booking->email,
                    'action_url' => route('admin.orders'),
                ]);
            }

            // Emails to younimatiq@gmail.com and Admin Addresses
            $adminEmails = app(\App\Services\EmailService::class)->getAdminEmails();

            $isConsultation = str_starts_with((string) $booking->camp_name, 'Consultation');
            $subject = ($isConsultation ? 'New Free Consultation Booked: ' : 'New Free Trial Class Booked: ') . $booking->child_name;
            $body = '<h3>' . ($isConsultation ? 'New Free Consultation Booking' : 'New Free Trial Class Booking') . '</h3>'
                . '<p><strong>Program / Track:</strong> ' . e($booking->camp_name) . '</p>'
                . '<p><strong>Child Name:</strong> ' . e($booking->child_name) . '</p>'
                . '<p><strong>Parent / Contact:</strong> ' . e($booking->parent_name) . '</p>'
                . '<p><strong>Parent Email:</strong> ' . e($booking->email) . '</p>'
                . '<p><strong>Phone Number:</strong> ' . e($booking->phone) . '</p>'
                . '<p><strong>WhatsApp Number:</strong> ' . e($booking->whatsapp_number) . '</p>'
                . '<p><strong>Age & Details:</strong> ' . e($booking->age) . '</p>'
                . '<p><strong>Parent Time:</strong> ' . e($scheduledParent) . '</p>'
                . '<p><strong>Teacher Time:</strong> ' . e($scheduledTeacher) . '</p>'
                . ($booking->zoom_start_url
                    ? '<p style="margin:16px 0;text-align:center;"><a href="' . e($booking->zoom_start_url) . '" style="background:#0B5CFF;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;">Start ' . e(app(\App\Services\ZoomService::class)->providerLabel($booking->meeting_provider)) . ' as Host</a></p>'
                    : '')
                . ($booking->zoom_join_url
                    ? '<p style="margin:16px 0;text-align:center;"><a href="' . e($booking->zoom_join_url) . '" style="background:#2D8CFF;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;">Join ' . e(app(\App\Services\ZoomService::class)->providerLabel($booking->meeting_provider)) . ' Meeting</a></p>'
                    : '')
                . '<p><strong>Auto Cloud Recording:</strong> Enabled on Teacher Host Entry</p>';

            foreach ($adminEmails as $aEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::to($aEmail)->send(new \App\Mail\TemplateMail($subject, $body));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed sending booking email to ' . $aEmail . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send admin notification email/in-app: ' . $e->getMessage());
        }
    }
}
