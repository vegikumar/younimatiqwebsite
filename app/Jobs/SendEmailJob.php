<?php

namespace App\Jobs;

use App\Mail\TemplateMail;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $body,
        public ?string $templateSlug = null,
        public string $mailerProfile = \App\Services\EmailService::MAILER_ADMISSION,
    ) {}

    public function handle(): void
    {
        $log = EmailLog::create([
            'to_email' => $this->toEmail,
            'subject' => $this->subject,
            'template_slug' => $this->templateSlug,
            'status' => 'queued',
        ]);

        try {
            $emailService = app(\App\Services\EmailService::class);
            $emailService->applyAdminSmtpConfig($this->mailerProfile);

            $mail = Mail::to($this->toEmail);
            if ($this->templateSlug !== 'admin_notification') {
                $adminEmails = $emailService->getAdminEmails();
                if (! empty($adminEmails)) {
                    $mail->bcc($adminEmails);
                }
            }
            $mail->send(new TemplateMail($this->subject, $this->body));
            $log->update(['status' => 'sent']);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
