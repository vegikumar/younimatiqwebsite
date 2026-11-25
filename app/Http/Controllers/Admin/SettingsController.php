<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->all();
        $activeProvider = ($settings['meeting_provider'] ?? null)
            ?: ((($settings['enable_custom_meet'] ?? 0) == 1) ? 'meet' : 'zoom');

        return \App\Support\InertiaPage::react('Admin/Settings/Gateway', [
            'settings' => $settings,
            'activeProvider' => $activeProvider,
            'hasMeetSecret' => ! empty($settings['meet_api_secret']),
            'webhookUrls' => [
                'stripe' => url('/webhooks/stripe'),
                'paypal' => url('/webhooks/paypal'),
                'razorpay' => url('/webhooks/razorpay'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        $toggles = ['enable_stripe', 'enable_paypal', 'enable_razorpay'];
        foreach ($toggles as $toggle) {
            $data[$toggle] = $request->has($toggle) ? 1 : 0;
        }

        $provider = $request->input('meeting_provider', 'zoom') === 'meet' ? 'meet' : 'zoom';
        $data['meeting_provider'] = $provider;
        $data['enable_custom_meet'] = $provider === 'meet' ? 1 : 0;

        // Empty password field = keep existing Meet API secret
        $incomingSecret = trim((string) $request->input('meet_api_secret', ''));
        if ($incomingSecret === '') {
            unset($data['meet_api_secret']);
        } else {
            $data['meet_api_secret'] = $incomingSecret;
        }

        foreach ($data as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $syncWarning = null;
        if ($incomingSecret !== '') {
            try {
                app(\App\Services\MeetConfigSync::class)->syncApiSecret($incomingSecret);
            } catch (\Throwable $e) {
                report($e);
                $syncWarning = 'Settings saved, but Meet secret sync failed: '.$e->getMessage();
            }
        }

        \Illuminate\Support\Facades\Cache::forget('global_settings');
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:restart');
        } catch (\Throwable $e) {
            report($e);
        }

        if ($syncWarning) {
            return redirect()->back()->with('success', 'Settings updated successfully.')->with('error', $syncWarning);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
    public function pixel()
    {
        $settings = Setting::pluck('value', 'key')->all();
        return \App\Support\InertiaPage::react('Admin/Settings/Pixel', ['settings' => $settings]);
    }

    public function seo()
    {
        $settings = Setting::pluck('value', 'key')->all();

        return \App\Support\InertiaPage::react('Admin/Settings/Seo', ['settings' => $settings]);
    }

    public function newsletter()
    {
        $settings = Setting::pluck('value', 'key')->all();

        return \App\Support\InertiaPage::react('Admin/Settings/Newsletter', ['settings' => $settings]);
    }

    public function email()
    {
        $settings = Setting::pluck('value', 'key')->all();

        return \App\Support\InertiaPage::react('Admin/Settings/Email', ['settings' => $settings]);
    }

    public function testEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);
        $email = $request->test_email;

        // Re-apply DB SMTP settings to ensure admin panel config is used
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        if (! empty($settings['mail_host'])) {
            // Laravel 13: scheme replaces encryption. tls=smtp, ssl=smtps
            $scheme = match(strtolower((string)($settings['mail_encryption'] ?? 'tls'))) {
                'ssl'  => 'smtps',
                default => 'smtp',
            };
            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => $settings['mail_host'],
                'mail.mailers.smtp.port'       => (int) ($settings['mail_port'] ?? 587),
                'mail.mailers.smtp.username'   => $settings['mail_username'] ?? null,
                'mail.mailers.smtp.password'   => str_replace(' ', '', $settings['mail_password'] ?? ''),
                'mail.mailers.smtp.scheme'     => $scheme,
                'mail.mailers.smtp.encryption' => null,
                'mail.from.address'            => $settings['mail_from_address'] ?? $settings['mail_username'],
                'mail.from.name'               => $settings['mail_from_name'] ?? 'Younimatiq',
            ]);
        }

        try {
            $body = '
                <p style="font-size:16px;color:#1e293b;">Hello,</p>
                <p style="font-size:15px;color:#334155;">This is a <strong>test email</strong> from <strong>Younimatiq</strong> to verify your SMTP settings are working correctly.</p>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
                    <tr>
                        <td style="background:#046bd2;border-radius:8px;padding:12px 28px;">
                            <a href="' . config('app.url') . '" style="color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;">✅ SMTP is Working!</a>
                        </td>
                    </tr>
                </table>
                <p style="font-size:13px;color:#64748b;">
                    Sent from: <strong>' . ($settings['mail_from_address'] ?? 'N/A') . '</strong><br>
                    SMTP Host: <strong>' . ($settings['mail_host'] ?? 'N/A') . '</strong><br>
                    Port: <strong>' . ($settings['mail_port'] ?? 'N/A') . '</strong><br>
                    Encryption: <strong>' . ($settings['mail_encryption'] ?? 'none') . '</strong>
                </p>
            ';

            \Illuminate\Support\Facades\Mail::to($email)
                ->send(new \App\Mail\TemplateMail('✅ Younimatiq — SMTP Test Successful', $body));

            return redirect()->back()->with('success', "Test email sent successfully to {$email}! Check your inbox.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
}
