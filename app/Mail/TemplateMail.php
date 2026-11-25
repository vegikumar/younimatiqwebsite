<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $htmlBody,
        public ?string $listUnsubscribeUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: [
                new \Illuminate\Mail\Mailables\Address('support@younimatiq.com', 'Younimatiq Support'),
            ],
        );
    }

    public function headers(): Headers
    {
        $unsubscribeEmail = 'mailto:support@younimatiq.com?subject=Unsubscribe';
        $unsubscribeUrl = $this->listUnsubscribeUrl
            ?: (rtrim(config('app.url'), '/').'/unsubscribe');

        return new Headers(
            text: [
                'List-Unsubscribe' => "<{$unsubscribeUrl}>, <{$unsubscribeEmail}>",
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Mailer' => 'Younimatiq Mailer',
                'Precedence' => 'bulk',
            ],
        );
    }

    public function content(): Content
    {
        if (str_contains($this->htmlBody, '<html') || str_contains($this->htmlBody, '<!DOCTYPE')) {
            return new Content(htmlString: $this->htmlBody);
        }

        $fullHtml = view('emails.master', [
            'mailSubject' => $this->mailSubject,
            'bodyContent' => $this->htmlBody,
        ])->render();

        return new Content(htmlString: $fullHtml);
    }
}
