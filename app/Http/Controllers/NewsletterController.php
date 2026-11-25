<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'] ?? null, 'verification_token' => Str::random(32)]
        );

        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $apiKey = $settings['mailchimp_api_key'] ?? null;
        $listId = $settings['mailchimp_list_id'] ?? null;

        if ($apiKey && $listId) {
            try {
                $client = new Client;
                $hash = md5(strtolower($data['email']));
                $client->put("https://{$this->dc($apiKey)}.api.mailchimp.com/3.0/lists/{$listId}/members/{$hash}", [
                    'auth' => ['anystring', $apiKey],
                    'json' => [
                        'email_address' => $data['email'],
                        'status' => 'subscribed',
                        'merge_fields' => ['FNAME' => $data['name'] ?? ''],
                    ],
                ]);
            } catch (\Exception $e) {
                // Local record still saved
            }
        }

        return back()->with('success', 'Subscribed to newsletter successfully!');
    }

    private function dc(string $apiKey): string
    {
        return substr($apiKey, strpos($apiKey, '-') + 1);
    }
}
