<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWebPushNotification implements ShouldQueue
{
    use Queueable;

    public $notification;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Notification $notification, \App\Models\User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscriptions = $this->user->pushSubscriptions;
        
        if ($subscriptions->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $webPush = new \Minishlink\WebPush\WebPush($auth);
        
        $payload = json_encode([
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'url' => $this->notification->action_url ?? url('/'),
            'icon' => '/favicon.ico',
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if (!$report->isSuccess()) {
                if ($report->isSubscriptionExpired()) {
                    \App\Models\PushSubscription::where('endpoint', $endpoint)->delete();
                }
            }
        }
    }
}
