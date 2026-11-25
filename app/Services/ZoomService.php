<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZoomService
{
    private $accountId;

    private $clientId;

    private $clientSecret;

    public function __construct()
    {
        $this->accountId = config('services.zoom.account_id');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
    }

    private function getAccessToken()
    {
        try {
            $cached = Cache::get('zoom_access_token');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        } catch (\Throwable $e) {
            Log::warning('Zoom token cache read failed: '.$e->getMessage());
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to get Zoom access token: '.$response->body());
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new \Exception('Zoom access token response was empty.');
        }

        // Cache is best-effort — never block host launch if the cache dir is unwritable.
        try {
            Cache::put('zoom_access_token', $token, 3500);
        } catch (\Throwable $e) {
            Log::warning('Zoom token cache write failed: '.$e->getMessage());
        }

        return $token;
    }

    public function createMeeting($topic, $startTime = null, $duration = 45, $timezone = 'UTC')
    {
        if ($this->activeProvider() === 'meet') {
            return app(MeetService::class)->createMeeting($topic, $startTime, (int) $duration, (string) $timezone);
        }

        if ($this->accountId && $this->clientId && $this->clientSecret) {
            try {
                $token = $this->getAccessToken();
                $payload = [
                    'topic' => $topic,
                    'type' => 2, // Scheduled meeting
                    'start_time' => $startTime ? date('Y-m-d\TH:i:s\Z', strtotime($startTime)) : date('Y-m-d\TH:i:s\Z'),
                    'duration' => $duration,
                    'timezone' => $timezone,
                    'password' => rand(100000, 999999), // Simple numeric passcode
                    'settings' => [
                        'host_video' => true,
                        'participant_video' => true,
                        'join_before_host' => false,
                        'mute_upon_entry' => false,
                        'waiting_room' => false,
                        'meeting_authentication' => false, // Students join as guests without a Zoom login
                        'authentication_option' => '',
                        'authentication_domains' => '',
                        'auto_recording' => 'cloud', // Auto cloud recording starts when teacher/host enters
                        'allow_multiple_devices' => true,
                        'show_share_button' => false,
                        'recording_disallow_stop' => true, // Host/teacher cannot stop recording
                    ],
                ];

                $response = Http::withToken($token)
                    ->post('https://api.zoom.us/v2/users/me/meetings', $payload);

                if ($response->successful()) {
                    $json = $response->json();

                    $joinUrl = $json['join_url'] ?? null;
                    $startUrl = $json['start_url'] ?? null;

                    // Extract encrypted pwd from join_url and append to start_url to prevent passcode prompts
                    if ($joinUrl && $startUrl && ! str_contains($startUrl, 'pwd=')) {
                        $parsedUrl = parse_url($joinUrl);
                        if (isset($parsedUrl['query'])) {
                            parse_str($parsedUrl['query'], $queryParams);
                            if (isset($queryParams['pwd'])) {
                                $separator = str_contains($startUrl, '?') ? '&' : '?';
                                $startUrl .= $separator.'pwd='.$queryParams['pwd'];
                            }
                        }
                    }

                    return [
                        'id' => $json['id'] ?? null,
                        'join_url' => $joinUrl,
                        'start_url' => $startUrl,
                        'passcode' => $json['password'] ?? null,
                        'auto_recording' => 'cloud',
                        'provider' => 'zoom',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Zoom API call failed, using fallback generator: '.$e->getMessage());
            }
        }

        // Fallback for pending Zoom credentials in Admin Panel
        $meetingId = rand(1000000000, 9999999999);
        $passcode = Str::random(8);

        return [
            'id' => $meetingId,
            'join_url' => 'https://zoom.us/j/'.$meetingId.'?pwd='.$passcode,
            'start_url' => 'https://zoom.us/s/'.$meetingId.'?role=host&pwd='.$passcode,
            'passcode' => $passcode,
            'auto_recording' => 'cloud',
            'provider' => 'zoom',
        ];
    }

    /** @return array{zoom_join_url:?string,zoom_start_url:?string,zoom_passcode:?string,meeting_provider:string,meeting_room_id:?string} */
    public static function bookingFields(array $meeting): array
    {
        return [
            'zoom_join_url' => $meeting['join_url'] ?? null,
            'zoom_start_url' => $meeting['start_url'] ?? null,
            'zoom_passcode' => $meeting['passcode'] ?? null,
            'meeting_provider' => $meeting['provider'] ?? 'zoom',
            'meeting_room_id' => isset($meeting['id']) ? (string) $meeting['id'] : null,
        ];
    }

    /**
     * Ensure a booking/camp booking has meeting links for the admin-selected provider.
     * Regenerates when links are missing or the stored provider no longer matches settings
     * (e.g. switched Zoom → Younimatiq Meet, then reassigned).
     *
     * @param  object{zoom_join_url?:?string,zoom_start_url?:?string,meeting_provider?:?string,meeting_room_id?:?string}  $record
     * @return array{zoom_join_url:?string,zoom_start_url:?string,zoom_passcode:?string,meeting_provider:string,meeting_room_id:?string}
     */
    public function ensureMeetingFields(
        object $record,
        string $topic,
        $startTime = null,
        int $duration = 45,
        string $timezone = 'UTC',
        bool $force = false
    ): array {
        $active = $this->activeProvider();
        $existingProvider = $record->meeting_provider ?? null;
        $joinUrl = $record->zoom_join_url ?? null;
        $startUrl = $record->zoom_start_url ?? null;
        $hasLinks = filled($joinUrl) && filled($startUrl);
        $providerMatches = $existingProvider === $active
            || ($existingProvider === null && $hasLinks && $this->urlLooksLikeProvider((string) $joinUrl, $active));

        if (! $force && $hasLinks && $providerMatches) {
            return [
                'zoom_join_url' => $joinUrl,
                'zoom_start_url' => $startUrl,
                'zoom_passcode' => $record->zoom_passcode ?? null,
                'meeting_provider' => $existingProvider ?: $active,
                'meeting_room_id' => isset($record->meeting_room_id) ? (string) $record->meeting_room_id : null,
            ];
        }

        $meeting = $this->createMeeting($topic, $startTime, $duration, $timezone);

        return self::bookingFields($meeting);
    }

    public function providerLabel(?string $provider = null): string
    {
        $provider = $provider ?: $this->activeProvider();

        return $provider === 'meet' ? 'Younimatiq Meet' : 'Zoom';
    }

    /**
     * Resolve a host launch URL for a booking/session.
     * Zoom start_url ZAK tokens expire (~2h), so we mint a fresh host URL on each launch.
     *
     * @param  object{zoom_start_url?:?string,zoom_join_url?:?string,meeting_provider?:?string,meeting_room_id?:?string,start_url?:?string,join_url?:?string}  $record
     * @return array{url:?string,fields?:array<string,mixed>}
     */
    public function resolveHostLaunchUrl(object $record, ?string $topic = null, $startTime = null, int $duration = 45, string $timezone = 'UTC'): array
    {
        $provider = $record->meeting_provider
            ?? (($this->urlLooksLikeProvider((string) ($record->zoom_join_url ?? $record->join_url ?? ''), 'meet')) ? 'meet' : null)
            ?? $this->activeProvider();

        $storedStart = $record->zoom_start_url ?? $record->start_url ?? null;

        if ($provider === 'meet') {
            return ['url' => $storedStart ?: ($record->zoom_join_url ?? $record->join_url ?? null)];
        }

        $meetingId = $this->extractMeetingId($record);
        if ($meetingId) {
            $fresh = $this->freshHostStartUrl($meetingId);
            if ($fresh) {
                return ['url' => $fresh, 'fields' => ['zoom_start_url' => $fresh]];
            }
        }

        // Stored start_url still valid? Use it.
        if (filled($storedStart) && ! $this->zoomStartUrlExpired($storedStart)) {
            return ['url' => $storedStart];
        }

        // No read/ZAK scopes (common) or meeting missing → create a brand-new Zoom meeting
        // so the tutor gets a fresh host ZAK without signing into Zoom.
        $topic = $topic ?: 'Younimatiq Class';
        $meeting = $this->createMeeting($topic, $startTime, $duration, $timezone);
        $fields = self::bookingFields($meeting);
        if (empty($fields['zoom_start_url']) || $this->isFallbackZoomUrl($fields['zoom_start_url'])) {
            return ['url' => null];
        }

        // Best-effort cleanup of the old meeting (needs delete scope; ignore failures).
        if ($meetingId) {
            $this->deleteMeeting($meetingId);
        }

        return ['url' => $fields['zoom_start_url'], 'fields' => $fields];
    }

    public function zoomStartUrlExpired(?string $startUrl): bool
    {
        if (! filled($startUrl) || ! preg_match('/[?&]zak=([^&]+)/', $startUrl, $m)) {
            // No ZAK (or fake fallback URL) → treat as unusable for host auto-join.
            return true;
        }

        $parts = explode('.', urldecode($m[1]));
        if (count($parts) < 2) {
            return true;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);
        if (! is_array($payload) || empty($payload['exp'])) {
            return true;
        }

        // Refresh 2 minutes before Zoom's own expiry.
        return ((int) $payload['exp']) <= (time() + 120);
    }

    public function isFallbackZoomUrl(?string $url): bool
    {
        if (! filled($url)) {
            return true;
        }

        // Our offline fallback generator uses role=host without a ZAK.
        return str_contains($url, 'role=host') && ! str_contains($url, 'zak=');
    }

    public function deleteMeeting(string $meetingId): void
    {
        $meetingId = preg_replace('/\D+/', '', $meetingId) ?: $meetingId;
        if ($meetingId === '' || ! $this->accountId) {
            return;
        }

        try {
            $token = $this->getAccessToken();
            Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->delete('https://api.zoom.us/v2/meetings/'.$meetingId);
        } catch (\Throwable $e) {
            Log::info('Zoom delete meeting skipped: '.$e->getMessage());
        }
    }

    /**
     * Mint a fresh Zoom host start URL (no Zoom login required for the tutor).
     */
    public function freshHostStartUrl(string $meetingId): ?string
    {
        $meetingId = preg_replace('/\D+/', '', $meetingId) ?: $meetingId;
        if ($meetingId === '' || ! $this->accountId || ! $this->clientId || ! $this->clientSecret) {
            return null;
        }

        try {
            $token = $this->getAccessToken();

            // GET meeting returns a regenerated start_url with a fresh ZAK.
            $meetingRes = Http::timeout(12)
                ->withToken($token)
                ->acceptJson()
                ->get('https://api.zoom.us/v2/meetings/'.$meetingId);

            if ($meetingRes->successful()) {
                $startUrl = $meetingRes->json('start_url');
                if (filled($startUrl)) {
                    return $startUrl;
                }

                $hostId = $meetingRes->json('host_id') ?: 'me';
            } else {
                Log::warning('Zoom GET meeting failed for host launch: '.$meetingRes->body());
                $hostId = 'me';
            }

            // Explicit ZAK fallback → https://zoom.us/s/{id}?zak=...
            $zakRes = Http::timeout(12)
                ->withToken($token)
                ->acceptJson()
                ->get('https://api.zoom.us/v2/users/'.$hostId.'/token', ['type' => 'zak']);

            if ($zakRes->successful() && filled($zakRes->json('token'))) {
                return 'https://zoom.us/s/'.$meetingId.'?zak='.urlencode((string) $zakRes->json('token'));
            }

            Log::warning('Zoom ZAK token failed for host launch: '.$zakRes->body());
        } catch (\Throwable $e) {
            Log::warning('Zoom fresh host start URL failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * @param  object{meeting_room_id?:?string,zoom_join_url?:?string,zoom_start_url?:?string,join_url?:?string,start_url?:?string}  $record
     */
    public function extractMeetingId(object $record): ?string
    {
        $roomId = trim((string) ($record->meeting_room_id ?? ''));
        if ($roomId !== '' && preg_match('/^\d{9,13}$/', $roomId)) {
            return $roomId;
        }

        foreach ([$record->zoom_join_url ?? null, $record->zoom_start_url ?? null, $record->join_url ?? null, $record->start_url ?? null] as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }
            if (preg_match('#zoom\.us/(?:j|s|wc/join)/(\d{9,13})#i', $url, $m)) {
                return $m[1];
            }
            if (preg_match('#[?&]confno=(\d{9,13})#i', $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    public function activeProvider(): string
    {
        // Always prefer DB setting so queue workers / long-lived processes pick up admin switches
        // without relying solely on boot-time config.
        try {
            $stored = Setting::where('key', 'meeting_provider')->value('value');
            if ($stored === 'meet' || $stored === 'zoom') {
                return $stored;
            }
            $enabled = Setting::where('key', 'enable_custom_meet')->value('value');
            if ((bool) filter_var($enabled, FILTER_VALIDATE_BOOLEAN) || $enabled === 1 || $enabled === '1') {
                return 'meet';
            }
        } catch (\Throwable $e) {
            //
        }

        $provider = config('services.meet.provider');
        if ($provider === 'meet' || $provider === 'zoom') {
            return $provider;
        }

        $enabled = config('services.meet.enabled');
        if ((bool) filter_var($enabled, FILTER_VALIDATE_BOOLEAN) || $enabled === 1 || $enabled === '1') {
            return 'meet';
        }

        return 'zoom';
    }

    private function urlLooksLikeProvider(string $url, string $provider): bool
    {
        $url = strtolower($url);
        if ($provider === 'meet') {
            $meetBase = strtolower((string) config('services.meet.base_url', ''));

            return str_contains($url, '/room/')
                || ($meetBase !== '' && str_contains($url, parse_url($meetBase, PHP_URL_HOST) ?: $meetBase))
                || str_contains($url, 'meet.younimatiq')
                || (str_contains($url, 'younimatiq') && str_contains($url, 'meet'));
        }

        return str_contains($url, 'zoom.us') || str_contains($url, 'zoom.com');
    }

    private function usesCustomMeet(): bool
    {
        return $this->activeProvider() === 'meet';
    }
}
