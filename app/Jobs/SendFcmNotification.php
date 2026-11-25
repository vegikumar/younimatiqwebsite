<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFcmNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Notification $notification,
        public User $user,
    ) {
    }

    public function handle(): void
    {
        $serverKey = config('services.fcm.server_key');
        if (! $serverKey) {
            return;
        }

        $tokens = DeviceToken::where('user_id', $this->user->id)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        // Chunk for FCM multicast limit (1000).
        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $chunk,
                    'priority' => 'high',
                    'notification' => [
                        'title' => $this->notification->title,
                        'body' => $this->notification->body ?? '',
                        'sound' => 'default',
                    ],
                    'data' => [
                        'type' => (string) $this->notification->type,
                        'title' => (string) $this->notification->title,
                        'body' => (string) ($this->notification->body ?? ''),
                        'action_url' => (string) ($this->notification->action_url ?? ''),
                        'notification_id' => (string) $this->notification->id,
                    ],
                ]);

                if (! $response->successful()) {
                    Log::warning('FCM send failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                $results = $response->json('results') ?? [];
                foreach ($results as $i => $result) {
                    if (! empty($result['error']) && in_array($result['error'], ['NotRegistered', 'InvalidRegistration'], true)) {
                        DeviceToken::where('token', $chunk[$i] ?? null)->delete();
                    }
                }
            } catch (\Throwable $e) {
                Log::error('FCM send exception: '.$e->getMessage());
            }
        }
    }
}
