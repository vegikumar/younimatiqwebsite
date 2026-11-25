<?php

namespace App\Services;

use App\Models\User;

class AntiBypassSecurityService
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Regex detection for phone numbers, email addresses, WhatsApp links, or social handles.
     */
    public function containsSensitiveContactInfo(string $text): bool
    {
        return $this->primaryContactType($text) !== null;
    }

    /**
     * @return list<array{type: string, value: string}>
     */
    public function extractContactMatches(string $text): array
    {
        $matches = [];

        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emailMatches)) {
            foreach (array_unique($emailMatches[0]) as $email) {
                $matches[] = ['type' => 'email', 'value' => $email];
            }
        }

        if (preg_match_all('/(?:\+|00)?\d[\d\s().\-]{7,18}\d/', $text, $phoneMatches)) {
            foreach (array_unique($phoneMatches[0]) as $phone) {
                $digits = preg_replace('/\D+/', '', $phone);
                if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                    $matches[] = ['type' => 'phone', 'value' => trim($phone)];
                }
            }
        }

        if (preg_match_all('/(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)|(?:\bwhatsapp\b|\btelegram\b)/i', $text, $linkMatches)) {
            foreach (array_unique($linkMatches[0]) as $link) {
                $matches[] = ['type' => 'messaging_link', 'value' => trim($link)];
            }
        }

        return $matches;
    }

    public function primaryContactType(string $text): ?string
    {
        $matches = $this->extractContactMatches($text);
        if (! empty($matches)) {
            return $matches[0]['type'];
        }

        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) {
            return 'email';
        }

        if (preg_match('/(?:\+|00)?\d[\d\s().\-]{7,18}\d/', $text)) {
            $digits = preg_replace('/\D+/', '', $text);
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                return 'phone';
            }
        }

        if (preg_match('/wa\.me|whatsapp|telegram|t\.me/i', $text)) {
            return 'messaging_link';
        }

        return null;
    }

    public function maskSensitiveContactInfo(string $text): string
    {
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[email blocked]', $text);
        $text = preg_replace('/(?:\+|00)?\d[\d\s().\-]{7,18}\d/', '[phone blocked]', $text);
        $text = preg_replace('/(?:https?:\/\/)?(?:wa\.me\/[^\s]+|t\.me\/[^\s]+)/i', '[link blocked]', $text);
        $text = preg_replace('/\b(?:whatsapp|telegram)\b/i', '[link blocked]', $text);

        return $text;
    }

    /**
     * Dispatch dual bell icon notifications to Admin and Teacher when a parent sends a message or when bypass is flagged.
     */
    public function notifyAdminAndTutor(int $tutorId, string $title, string $message, string $link = '#'): void
    {
        // Notify Tutor
        $this->notificationService->notify($tutorId, $title, $message, $link, 'info');

        // Notify Admins
        $adminIds = User::where('role', 'admin')->pluck('id');
        foreach ($adminIds as $adminId) {
            $this->notificationService->notify($adminId, "[Admin Alert] {$title}", $message, $link, 'warning');
        }
    }
}
