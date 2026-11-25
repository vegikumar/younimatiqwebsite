<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $sessionName;

    public string $timeString;

    public string $recipientName;

    public ?string $joinUrl;

    public bool $isTutor;

    public ?string $whenLabel;

    public string $kind;

    public ?string $cantAttendUrl;

    public function __construct(
        string $sessionName,
        string $timeString,
        string $recipientName,
        ?string $joinUrl = null,
        bool $isTutor = false,
        ?string $whenLabel = null,
        string $kind = 'class',
        ?string $cantAttendUrl = null,
    ) {
        $this->sessionName = $sessionName;
        $this->timeString = $timeString;
        $this->recipientName = $recipientName;
        $this->joinUrl = $joinUrl;
        $this->isTutor = $isTutor;
        $this->whenLabel = $whenLabel;
        $this->kind = $kind === 'trial' ? 'trial' : 'class';
        $this->cantAttendUrl = $cantAttendUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->sessionName} starts in {$this->timeString}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.session_reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
