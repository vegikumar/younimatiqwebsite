<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LiveSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BackfillZoomStartUrls extends Command
{
    protected $signature = 'zoom:backfill-start-urls';
    protected $description = 'Backfill missing start_urls for existing LiveSessions';

    public function handle()
    {
        $sessions = LiveSession::whereNull('start_url')->whereNotNull('zoom_meeting_id')->get();
        $this->info('Found ' . $sessions->count() . ' sessions to backfill.');
        
        $accountId = config('services.zoom.account_id');
        $clientId = config('services.zoom.client_id');
        $clientSecret = config('services.zoom.client_secret');
        
        if (!$accountId || !$clientId || !$clientSecret) {
            $this->warn('Zoom credentials not fully set. Falling back to simple start_url construction.');
            foreach ($sessions as $session) {
                if ($session->join_url) {
                    $session->start_url = str_replace('/j/', '/s/', $session->join_url) . '&role=host';
                    if (!str_contains($session->start_url, '?')) {
                        $session->start_url = str_replace('&role=host', '?role=host', $session->start_url);
                    }
                    $session->save();
                    $this->line('Updated session ' . $session->id);
                }
            }
            return;
        }

        $token = Cache::remember('zoom_access_token', 3500, function () use ($clientId, $clientSecret, $accountId) {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ]);
            return $response->successful() ? $response->json('access_token') : null;
        });

        if (!$token) {
            $this->error('Could not get Zoom token.');
            return;
        }

        foreach ($sessions as $session) {
            $response = Http::withToken($token)->get('https://api.zoom.us/v2/meetings/' . $session->zoom_meeting_id);
            if ($response->successful()) {
                $startUrl = $response->json('start_url');
                if ($startUrl) {
                    $session->start_url = $startUrl;
                    $session->save();
                    $this->info('Updated session ' . $session->id);
                }
            } else {
                $this->warn('Could not fetch meeting for session ' . $session->id . '. Creating fallback start_url.');
                if ($session->join_url) {
                    $session->start_url = str_replace('/j/', '/s/', $session->join_url) . '&role=host';
                    if (!str_contains($session->start_url, '?')) {
                        $session->start_url = str_replace('&role=host', '?role=host', $session->start_url);
                    }
                    $session->save();
                    $this->info('Updated session ' . $session->id . ' with fallback URL.');
                }
            }
        }
        $this->info('Done!');
    }
}
