<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notify(User|int $user, string $title, ?string $body = null, ?string $actionUrl = null, string $type = 'info'): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    public function recent(int $userId, int $limit = 10, bool $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->limit($limit)->get();
    }

    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)->unread()->update(['read_at' => now()]);
    }
}
