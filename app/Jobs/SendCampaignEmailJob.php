<?php

namespace App\Jobs;

use App\Mail\TemplateMail;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $body,
        public string $fromName,
        public ?string $replyTo = null,
        public string $templateSlug = 'trending_classes',
        public ?string $unsubscribeUrl = null,
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
            $fromAddress = config('mail.from.address') ?: 'support@younimatiq.com';
            $replyTo = $this->replyTo ?: 'support@younimatiq.com';

            $mailable = (new TemplateMail($this->subject, $this->body, $this->unsubscribeUrl))
                ->from($fromAddress, $this->fromName)
                ->replyTo($replyTo, $this->fromName);

            Mail::to($this->toEmail)->send($mailable);
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
