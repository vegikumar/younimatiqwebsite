<?php

namespace App\Providers;

use App\Listeners\RecordLoginActivity;
use App\Models\Setting;
use App\View\Composers\NavigationComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }

    public function boot(): void
    {
        // Cap "Remember me" to 1 day for every role (matches SESSION_LIFETIME).
        $rememberMinutes = max(1, (int) config('session.lifetime', 1440));
        foreach (['web', 'admin', 'tutor', 'parent'] as $guardName) {
            try {
                Auth::guard($guardName)->setRememberDuration($rememberMinutes);
            } catch (\Throwable) {
                // Guard may not support remember duration in some environments.
            }
        }

        // Country-agnostic price rendering for edge-cacheable public pages.
        // The server always renders the USD value; a tiny client script swaps
        // the visible text per visitor country using the data-* attributes.
        // Usage: @price(18)  |  @price(18, ['INR' => 500])  |  @price(18, [], 'text-xs text-error line-through')
        Blade::directive('price', function ($expression) {
            return "<?php echo app(\App\Support\PriceRenderer::class)->render({$expression}); ?>";
        });

        // CSS is render-blocking (it is small, ~26 KB gzipped) so the first paint is
        // fully styled — avoids a large unstyled reflow that inflates LCP render delay.

        // layouts.app (classic Blade) + partials.header (Inertia Embed chrome)
        View::composer(['layouts.app', 'partials.header'], NavigationComposer::class);

        Event::listen(
            Login::class,
            RecordLoginActivity::class,
        );

        try {
            if (Schema::hasTable('settings')) {
                $settings = Cache::rememberForever('global_settings', function () {
                    return Setting::pluck('value', 'key')->toArray();
                });

                foreach ($settings as $key => $value) {
                    match ($key) {
                        'stripe_key' => config(['services.stripe.key' => $value]),
                        'stripe_secret' => config(['services.stripe.secret' => $value]),
                        'stripe_webhook_secret' => config(['services.stripe.webhook_secret' => $value]),
                        'paypal_mode' => config(['paypal.mode' => $value]),
                        'paypal_client_id' => config([
                            'services.paypal.client_id' => $value,
                            'paypal.sandbox.client_id' => $value,
                            'paypal.live.client_id' => $value,
                        ]),
                        'paypal_secret' => config([
                            'services.paypal.secret' => $value,
                            'paypal.sandbox.client_secret' => $value,
                            'paypal.live.client_secret' => $value,
                        ]),
                        'razorpay_key' => config(['services.razorpay.key' => $value]),
                        'razorpay_secret' => config(['services.razorpay.secret' => $value]),
                        'zoom_account_id' => config(['services.zoom.account_id' => $value]),
                        'zoom_client_id' => config(['services.zoom.client_id' => $value]),
                        'zoom_client_secret' => config(['services.zoom.client_secret' => $value]),
                        'enable_custom_meet' => config(['services.meet.enabled' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === '1' || $value === 1]),
                        'meeting_provider' => config([
                            'services.meet.provider' => in_array($value, ['meet', 'zoom'], true) ? $value : 'zoom',
                            'services.meet.enabled' => $value === 'meet',
                        ]),
                        'custom_meet_org_id' => config(['services.meet.org_id' => $value]),
                        'custom_meet_org_name' => config(['services.meet.org_name' => $value]),
                        'custom_meet_base_url' => config(['services.meet.base_url' => $value]),
                        'meet_api_secret' => config(['services.meet.api_secret' => $value]),
                        'mail_host' => config(['mail.mailers.smtp.host' => $value]),
                        'mail_port' => config(['mail.mailers.smtp.port' => (int) $value]),
                        'mail_username' => config(['mail.mailers.smtp.username' => $value]),
                        'mail_password' => config(['mail.mailers.smtp.password' => str_replace(' ', '', $value)]),
                        'mail_encryption' => (function () use ($value) {
                            // Laravel 13: scheme replaces encryption. tls=smtp, ssl=smtps
                            $scheme = match (strtolower((string) $value)) {
                                'ssl' => 'smtps',
                                'tls' => 'smtp',
                                default => 'smtp',
                            };
                            config([
                                'mail.mailers.smtp.scheme' => $scheme,
                                'mail.mailers.smtp.encryption' => null,
                            ]);

                            return null;
                        })(),
                        'mail_from_address' => config(['mail.from.address' => $value]),
                        'mail_from_name' => config(['mail.from.name' => $value]),
                        default => null,
                    };
                }

                // Override the default mailer to smtp if host is configured in DB
                if (! empty($settings['mail_host'])) {
                    config(['mail.default' => 'smtp']);
                }
            }
        } catch (\Exception $e) {
            //
        }
    }
}
