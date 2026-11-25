<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'action_url', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            try {
                // Real-time in-app broadcasting (queued; failures must not break assigns)
                event(new \App\Events\NotificationCreated($notification));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notification broadcast skipped: '.$e->getMessage());
            }

            // Native OS Web Push Notifications
            if ($notification->user) {
                try {
                    \App\Jobs\SendWebPushNotification::dispatch($notification, $notification->user);
                    \App\Jobs\SendFcmNotification::dispatch($notification, $notification->user);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Push notification dispatch skipped: '.$e->getMessage());
                }
            }
        });
    }
}
