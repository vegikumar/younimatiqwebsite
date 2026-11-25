<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\CampBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BackfillZoomPasscodes extends Command
{
    protected $signature = 'zoom:backfill-passcodes';
    protected $description = 'Fetch and save missing Zoom passcodes for existing bookings';

    private function getAccessToken()
    {
        $clientId = config('services.zoom.client_id');
        $clientSecret = config('services.zoom.client_secret');
        $accountId = config('services.zoom.account_id');

        return Cache::remember('zoom_access_token', 3500, function () use ($clientId, $clientSecret, $accountId) {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }
            throw new \Exception('Failed to get token');
        });
    }

    public function handle()
    {
        try {
            $token = $this->getAccessToken();
        } catch (\Exception $e) {
            $this->error("Failed to authenticate with Zoom API: " . $e->getMessage());
            return;
        }

        $this->processTable(Booking::class, $token, 'Booking');
        $this->processTable(CampBooking::class, $token, 'Camp Booking');
        
        $this->info("Passcode backfill complete.");
    }

    private function processTable($modelClass, $token, $label)
    {
        $records = $modelClass::whereNotNull('zoom_join_url')
            ->whereNull('zoom_passcode')
            ->where('zoom_join_url', 'like', '%zoom.us/j/%')
            ->get();

        $this->info("Found " . $records->count() . " $label records needing passcodes.");

        foreach ($records as $record) {
            $parsed = parse_url($record->zoom_join_url);
            if (!isset($parsed['path'])) continue;
            
            $meetingId = basename($parsed['path']);
            if (!is_numeric($meetingId)) continue;

            $response = Http::withToken($token)->get("https://api.zoom.us/v2/meetings/{$meetingId}");
            
            if ($response->successful()) {
                $passcode = $response->json('password');
                if ($passcode) {
                    $record->update(['zoom_passcode' => $passcode]);
                    $this->info("Updated $label ID {$record->id} with passcode.");
                } else {
                    $this->warn("$label ID {$record->id} has no passcode in Zoom.");
                }
            } else {
                if ($response->status() == 404) {
                    $this->warn("Meeting $meetingId not found for $label ID {$record->id}.");
                } else {
                    $this->error("API Error for $meetingId: " . $response->body());
                }
            }
        }
    }
}
