@extends('layouts.dashboard')

@section('title', 'Gateway Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">API & Gateway Settings</h2>
                <p class="font-body-md text-on-surface-variant">Configure Zoom API, Stripe, PayPal, and Razorpay.</p>
            </div>
        </div>

        

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
            @csrf
            
            <!-- Zoom Settings -->
            <div class="space-y-4">
                <h3 class="font-bold text-lg text-primary border-b border-outline-variant pb-2">Zoom Integration</h3>
                
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Zoom Account ID</label>
                    <input type="text" name="zoom_account_id" value="{{ $settings['zoom_account_id'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Zoom Client ID</label>
                    <input type="text" name="zoom_client_id" value="{{ $settings['zoom_client_id'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Zoom Client Secret</label>
                    <input type="password" name="zoom_client_secret" value="{{ $settings['zoom_client_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
            </div>
            
            <!-- Meeting provider (Zoom XOR Meet) -->
            <div class="space-y-4">
                <div class="border-b border-outline-variant pb-2">
                    <h3 class="font-bold text-lg text-secondary">Class Meeting Provider</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Only one provider can be active. New classes use the selected provider.</p>
                </div>
                @php $activeProvider = ($settings['meeting_provider'] ?? (($settings['enable_custom_meet'] ?? 0) ? 'meet' : 'zoom')); @endphp
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="clay-card p-4 flex items-start gap-3 cursor-pointer border {{ $activeProvider === 'zoom' ? 'border-primary' : 'border-outline-variant' }}">
                        <input type="radio" name="meeting_provider" value="zoom" class="mt-1" {{ $activeProvider === 'zoom' ? 'checked' : '' }}>
                        <span>
                            <span class="font-bold text-on-surface block">Zoom</span>
                            <span class="text-xs text-on-surface-variant">Use Zoom for all new class links</span>
                        </span>
                    </label>
                    <label class="clay-card p-4 flex items-start gap-3 cursor-pointer border {{ $activeProvider === 'meet' ? 'border-primary' : 'border-outline-variant' }}">
                        <input type="radio" name="meeting_provider" value="meet" class="mt-1" {{ $activeProvider === 'meet' ? 'checked' : '' }}>
                        <span>
                            <span class="font-bold text-on-surface block">Younimatiq Meet</span>
                            <span class="text-xs text-on-surface-variant">Use Younimatiq Meet rooms + server recordings</span>
                        </span>
                    </label>
                </div>
                <div class="space-y-2 {{ $activeProvider === 'meet' ? '' : 'opacity-50' }}">
                    <label class="block font-bold text-on-surface">Meet Base URL</label>
                    <input type="text" name="custom_meet_base_url" value="{{ $settings['custom_meet_base_url'] ?? 'https://younimatiq.com' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2 {{ $activeProvider === 'meet' ? '' : 'opacity-50' }}">
                    <label class="block font-bold text-on-surface">Organization ID</label>
                    <input type="text" name="custom_meet_org_id" value="{{ $settings['custom_meet_org_id'] ?? 'younimatiq' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2 {{ $activeProvider === 'meet' ? '' : 'opacity-50' }}">
                    <label class="block font-bold text-on-surface">Organization Name</label>
                    <input type="text" name="custom_meet_org_name" value="{{ $settings['custom_meet_org_name'] ?? 'younimatiq' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Meet API Secret</label>
                    <input type="password" name="meet_api_secret" value="" autocomplete="new-password" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="{{ !empty($settings['meet_api_secret']) ? '•••••••• saved — leave blank to keep' : 'Shared secret with Meet server' }}">
                    <p class="text-xs text-on-surface-variant">
                        Shared key between this website and Meet (same server). Laravel uses it to create meetings and download recordings; Meet rejects calls without it.
                        Saving a new value updates Meet automatically. Leave blank to keep the current secret.
                    </p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-bold text-lg text-secondary">Stripe Integration</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enable_stripe" value="1" class="sr-only peer" {{ ($settings['enable_stripe'] ?? 1) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ml-3 text-sm font-bold text-on-surface">Enable Stripe</span>
                    </label>
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Stripe Publishable Key</label>
                    <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Stripe Secret Key</label>
                    <input type="password" name="stripe_secret" value="{{ $settings['stripe_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Stripe Webhook Secret</label>
                    <input type="password" name="stripe_webhook_secret" value="{{ $settings['stripe_webhook_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    <p class="text-xs text-on-surface-variant">Webhook URL: {{ url('/webhooks/stripe') }}</p>
                </div>
            </div>

            <!-- PayPal Settings -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-bold text-lg text-[#003087]">PayPal Integration</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enable_paypal" value="1" class="sr-only peer" {{ ($settings['enable_paypal'] ?? 1) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ml-3 text-sm font-bold text-on-surface">Enable PayPal</span>
                    </label>
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">PayPal Environment Mode</label>
                    <select name="paypal_mode" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option value="live" {{ ($settings['paypal_mode'] ?? 'live') === 'live' ? 'selected' : '' }}>Live (Production Environment)</option>
                        <option value="sandbox" {{ ($settings['paypal_mode'] ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox (Test Environment - Developer Credentials)</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">PayPal Client ID</label>
                    <input type="text" name="paypal_client_id" value="{{ $settings['paypal_client_id'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">PayPal Secret</label>
                    <input type="password" name="paypal_secret" value="{{ $settings['paypal_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">PayPal Webhook ID</label>
                    <input type="text" name="paypal_webhook_id" value="{{ $settings['paypal_webhook_id'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    <p class="text-xs text-on-surface-variant">Webhook URL: {{ url('/webhooks/paypal') }} — copy the Webhook ID from your PayPal Developer Dashboard after creating the webhook.</p>
                </div>
            </div>

            <!-- Razorpay Settings -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-bold text-lg text-tertiary">Razorpay Integration</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enable_razorpay" value="1" class="sr-only peer" {{ ($settings['enable_razorpay'] ?? 1) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ml-3 text-sm font-bold text-on-surface">Enable Razorpay</span>
                    </label>
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key" value="{{ $settings['razorpay_key'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_secret" value="{{ $settings['razorpay_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Razorpay Webhook Secret</label>
                    <input type="password" name="razorpay_webhook_secret" value="{{ $settings['razorpay_webhook_secret'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    <p class="text-xs text-on-surface-variant">Webhook URL: {{ url('/webhooks/razorpay') }} — set the same secret in Razorpay Dashboard → Settings → Webhooks.</p>
                </div>
            </div>

            <div class="pt-6 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Save API Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
