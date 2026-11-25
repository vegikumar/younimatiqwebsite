<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MeetService;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->all();
        $activeProvider = $settings['meeting_provider']
            ?? ((($settings['enable_custom_meet'] ?? 0) == 1) ? 'meet' : 'zoom');

        $status = [
            'stripe' => ! empty($settings['stripe_secret']),
            'paypal' => ! empty($settings['paypal_client_id']),
            'razorpay' => ! empty($settings['razorpay_key']),
            'zoom' => ! empty($settings['zoom_client_id']),
            'meet' => ! empty($settings['custom_meet_base_url'] ?? config('services.meet.base_url')),
            'mailchimp' => ! empty($settings['mailchimp_api_key']),
            'smtp' => ! empty($settings['mail_host']),
            'ga' => ! empty($settings['google_analytics_id']),
            'pixel' => ! empty($settings['meta_pixel_id']),
        ];

        $lastMeetingTest = session('meeting_test_result');

        return \App\Support\InertiaPage::react('Admin/Integrations/Index', [
            'status' => $status,
            'activeProvider' => $activeProvider,
            'lastMeetingTest' => $lastMeetingTest,
            'integrations' => [
                ['key' => 'stripe', 'name' => 'Stripe', 'icon' => 'payments', 'settingsRoute' => route('admin.settings.index')],
                ['key' => 'paypal', 'name' => 'PayPal', 'icon' => 'account_balance_wallet', 'settingsRoute' => route('admin.settings.index')],
                ['key' => 'razorpay', 'name' => 'Razorpay', 'icon' => 'currency_rupee', 'settingsRoute' => route('admin.settings.index')],
                ['key' => 'zoom', 'name' => 'Zoom', 'icon' => 'videocam', 'settingsRoute' => route('admin.settings.index')],
                ['key' => 'meet', 'name' => 'Younimatiq Meet', 'icon' => 'meeting_room', 'settingsRoute' => route('admin.settings.index')],
                ['key' => 'mailchimp', 'name' => 'Mailchimp', 'icon' => 'mail', 'settingsRoute' => route('admin.settings.newsletter')],
                ['key' => 'smtp', 'name' => 'SMTP Email', 'icon' => 'outgoing_mail', 'settingsRoute' => route('admin.settings.email')],
                ['key' => 'ga', 'name' => 'Google Analytics', 'icon' => 'analytics', 'settingsRoute' => route('admin.settings.pixel')],
                ['key' => 'pixel', 'name' => 'Meta Pixel', 'icon' => 'track_changes', 'settingsRoute' => route('admin.settings.pixel')],
            ],
        ]);
    }

    public function test(Request $request)
    {
        $service = $request->input('service');

        return match ($service) {
            'stripe' => $this->testStripe(),
            'zoom' => $this->testZoom(),
            'meet' => $this->testMeet(),
            'mailchimp' => $this->testMailchimp(),
            'paypal' => $this->testPayPal(),
            'razorpay' => $this->testRazorpay(),
            'smtp' => $this->testSmtp(),
            'ga' => $this->testGa(),
            'pixel' => $this->testPixel(),
            default => back()->with('error', 'Unknown service.'),
        };
    }

    private function testStripe()
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            \Stripe\Balance::retrieve();

            return back()->with('success', 'Stripe connection OK.');
        } catch (\Exception $e) {
            return back()->with('error', 'Stripe failed: '.$e->getMessage());
        }
    }

    private function testZoom()
    {
        try {
            // Force Zoom path for explicit Zoom test
            config(['services.meet.provider' => 'zoom', 'services.meet.enabled' => false]);
            $meeting = app(ZoomService::class)->createMeeting('Younimatiq Zoom Test', now()->addHour()->toDateTimeString(), 15, 'UTC');

            return back()
                ->with('success', 'Zoom test meeting created.')
                ->with('meeting_test_result', [
                    'provider' => 'zoom',
                    'id' => $meeting['id'] ?? null,
                    'join_url' => $meeting['join_url'] ?? null,
                    'start_url' => $meeting['start_url'] ?? null,
                    'passcode' => $meeting['passcode'] ?? null,
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Zoom failed: '.$e->getMessage());
        }
    }

    private function testMeet()
    {
        try {
            $topic = 'Younimatiq Meet Test '.now()->format('Y-m-d H:i');
            $meeting = app(MeetService::class)->createMeeting($topic, now()->addHour()->toDateTimeString(), 45, 'UTC');

            $studentUrl = $meeting['join_url'] ?? null;
            $tutorUrl = $meeting['start_url'] ?? null;
            if ($tutorUrl && ! str_contains($tutorUrl, 'role=host')) {
                $tutorUrl .= (str_contains($tutorUrl, '?') ? '&' : '?').'role=host';
            }

            return back()
                ->with('success', 'Younimatiq Meet test class created. Use the Tutor and Student links below.')
                ->with('meeting_test_result', [
                    'provider' => 'meet',
                    'topic' => $topic,
                    'id' => $meeting['id'] ?? null,
                    'join_url' => $studentUrl,
                    'start_url' => $tutorUrl,
                    'student_url' => $studentUrl,
                    'tutor_url' => $tutorUrl,
                    'passcode' => $meeting['passcode'] ?? null,
                    'created_at' => now()->toDateTimeString(),
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Meet failed: '.$e->getMessage());
        }
    }

    private function testMailchimp()
    {
        $key = Setting::where('key', 'mailchimp_api_key')->value('value');
        $listId = Setting::where('key', 'mailchimp_list_id')->value('value');

        if (! $key || ! $listId) {
            return back()->with('error', 'Mailchimp keys not configured.');
        }

        try {
            $dc = substr($key, strpos($key, '-') + 1);
            $response = Http::withBasicAuth('anystring', $key)
                ->get("https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}");

            return $response->successful()
                ? back()->with('success', 'Mailchimp connection OK.')
                : back()->with('error', 'Mailchimp failed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Mailchimp failed: '.$e->getMessage());
        }
    }

    private function testPayPal()
    {
        $clientId = Setting::where('key', 'paypal_client_id')->value('value');
        $secret = Setting::where('key', 'paypal_secret')->value('value');

        if (! $clientId || ! $secret) {
            return back()->with('error', 'PayPal keys not configured.');
        }

        try {
            $mode = Setting::where('key', 'paypal_mode')->value('value') === 'live' ? 'api-m.paypal.com' : 'api-m.sandbox.paypal.com';
            $response = Http::withBasicAuth($clientId, $secret)
                ->asForm()
                ->post("https://{$mode}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return back()->with('success', 'PayPal connection OK.');
            }

            return back()->with('error', 'PayPal connection failed: '.$response->body());
        } catch (\Exception $e) {
            return back()->with('error', 'PayPal failed: '.$e->getMessage());
        }
    }

    private function testRazorpay()
    {
        $key = Setting::where('key', 'razorpay_key')->value('value');
        $secret = Setting::where('key', 'razorpay_secret')->value('value');

        if (! $key || ! $secret) {
            return back()->with('error', 'Razorpay keys not configured.');
        }

        try {
            $response = Http::withBasicAuth($key, $secret)
                ->get('https://api.razorpay.com/v1/orders', ['count' => 1]);

            if ($response->successful()) {
                return back()->with('success', 'Razorpay connection OK.');
            }

            return back()->with('error', 'Razorpay connection failed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Razorpay failed: '.$e->getMessage());
        }
    }

    private function testSmtp()
    {
        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Younimatiq integrations hub. If you received this, your SMTP settings are working perfectly!', function ($msg) {
                $msg->to(auth()->user()->email)->subject('Younimatiq SMTP Test');
            });

            return back()->with('success', 'SMTP test email sent successfully to '.auth()->user()->email);
        } catch (\Exception $e) {
            return back()->with('error', 'SMTP failed: '.$e->getMessage());
        }
    }

    private function testGa()
    {
        return back()->with('success', 'Google Analytics is a frontend pixel. If configured, use the Google Tag Assistant browser extension to verify.');
    }

    private function testPixel()
    {
        return back()->with('success', 'Meta Pixel is a frontend pixel. If configured, use the Meta Pixel Helper browser extension to verify.');
    }
}
