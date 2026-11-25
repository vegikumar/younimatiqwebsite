<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportInboundNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $htmlBody,
        public string $replyToEmail,
        public string $replyToName = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: [
                new Address($this->replyToEmail, $this->replyToName !== '' ? $this->replyToName : $this->replyToEmail),
            ],
        );
    }

    public function content(): Content
    {
        $fullHtml = view('emails.master', [
            'mailSubject' => $this->mailSubject,
            'bodyContent' => $this->htmlBody,
        ])->render();

        return new Content(htmlString: $fullHtml);
    }
}
