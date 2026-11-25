@extends('layouts.dashboard')
@section('title', 'Integrations')
@section('content')
<div class="p-5 md:p-8 space-y-8">
    <h1 class="font-headline-lg text-2xl font-bold">Integrations Hub</h1>

    <div class="bg-primary/5 border border-primary/20 rounded-2xl p-6 flex gap-4">
        <span class="material-symbols-outlined text-primary text-3xl shrink-0">info</span>
        <div>
            <h3 class="font-bold text-lg mb-2">Universal Test Mode Credentials</h3>
            <p class="text-on-surface-variant text-sm mb-4">When your payment gateways are configured in sandbox/test mode, use these universal details to simulate successful student checkouts.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-bold text-sm text-primary mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-sm">credit_card</span> Stripe Test Card</h4>
                    <ul class="text-sm space-y-1 text-on-surface-variant">
                        <li><strong>Card Number:</strong> 4242 4242 4242 4242</li>
                        <li><strong>Expiry Date:</strong> Any future date (e.g., 12/30)</li>
                        <li><strong>CVC:</strong> Any 3 digits (e.g., 123)</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-primary mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-sm">currency_rupee</span> Razorpay Test Card</h4>
                    <ul class="text-sm space-y-1 text-on-surface-variant">
                        <li><strong>Card Number:</strong> 4111 1111 1111 1111</li>
                        <li><strong>Expiry Date:</strong> Any future date</li>
                        <li><strong>CVC:</strong> Any 3 digits</li>
                        <li><strong>OTP:</strong> 1226 (or click Success on the popup)</li>
                    </ul>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-primary/10">
                <h4 class="font-bold text-sm text-primary mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-sm">account_balance_wallet</span> PayPal Test Sandbox</h4>
                <p class="text-sm text-on-surface-variant">Log into <a href="https://developer.paypal.com" target="_blank" class="text-primary hover:underline">developer.paypal.com</a> to create personal Sandbox Accounts. You will use those sandbox email addresses to simulate student payments during checkout.</p>
            </div>
        </div>
    </div>

    <div class="bg-secondary/5 border border-secondary/20 rounded-2xl p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h3 class="font-bold text-lg">Active class meeting provider</h3>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ ($activeProvider ?? 'zoom') === 'meet' ? 'bg-primary-fixed text-primary' : 'bg-secondary-container text-on-secondary-container' }}">
                {{ strtoupper($activeProvider ?? 'zoom') }}
            </span>
        </div>
        <p class="text-sm text-on-surface-variant mb-4">Switch Zoom vs Younimatiq Meet in Gateways settings. Only one provider creates new class links.</p>
        <a href="{{ route('admin.settings.index') }}" class="text-sm font-bold text-primary hover:underline">Open Gateways settings →</a>
    </div>

    @if(session('meeting_test_result'))
        @php $t = session('meeting_test_result'); @endphp
        <div class="bg-white rounded-2xl border border-primary/30 p-6 space-y-3">
            <h3 class="font-bold text-lg">Last meeting test ({{ strtoupper($t['provider'] ?? '') }})</h3>
            <p class="text-sm"><strong>ID:</strong> <code class="bg-surface-container-low px-2 py-1 rounded">{{ $t['id'] ?? '—' }}</code></p>
            <p class="text-sm break-all"><strong>Student join:</strong> <a class="text-primary hover:underline" href="{{ $t['join_url'] ?? '#' }}" target="_blank" rel="noopener">{{ $t['join_url'] ?? '—' }}</a></p>
            <p class="text-sm break-all"><strong>Host start:</strong> <a class="text-primary hover:underline" href="{{ $t['start_url'] ?? '#' }}" target="_blank" rel="noopener">{{ $t['start_url'] ?? '—' }}</a></p>
            @if(!empty($t['passcode']))
                <p class="text-sm"><strong>Passcode:</strong> {{ $t['passcode'] }}</p>
            @endif
            <div class="flex flex-wrap gap-2 pt-2">
                @if(!empty($t['start_url']))
                    <a href="{{ $t['start_url'] }}" target="_blank" rel="noopener" class="btn-primary btn-sm">Open as host</a>
                @endif
                @if(!empty($t['join_url']))
                    <a href="{{ $t['join_url'] }}" target="_blank" rel="noopener" class="px-4 py-2 bg-surface-container-low rounded-lg text-sm font-bold">Open as student</a>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach([
            ['key' => 'stripe', 'name' => 'Stripe', 'icon' => 'payments', 'route' => 'admin.settings.index'],
            ['key' => 'paypal', 'name' => 'PayPal', 'icon' => 'account_balance_wallet', 'route' => 'admin.settings.index'],
            ['key' => 'razorpay', 'name' => 'Razorpay', 'icon' => 'currency_rupee', 'route' => 'admin.settings.index'],
            ['key' => 'zoom', 'name' => 'Zoom', 'icon' => 'videocam', 'route' => 'admin.settings.index'],
            ['key' => 'meet', 'name' => 'Younimatiq Meet', 'icon' => 'meeting_room', 'route' => 'admin.settings.index'],
            ['key' => 'mailchimp', 'name' => 'Mailchimp', 'icon' => 'mail', 'route' => 'admin.settings.newsletter'],
            ['key' => 'smtp', 'name' => 'SMTP Email', 'icon' => 'outgoing_mail', 'route' => 'admin.settings.email'],
            ['key' => 'ga', 'name' => 'Google Analytics', 'icon' => 'analytics', 'route' => 'admin.settings.pixel'],
            ['key' => 'pixel', 'name' => 'Meta Pixel', 'icon' => 'track_changes', 'route' => 'admin.settings.pixel'],
        ] as $integration)
        <div class="bg-white rounded-2xl border p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-2xl">{{ $integration['icon'] }}</span>
                    <h3 class="font-bold">{{ $integration['name'] }}</h3>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ ($status[$integration['key']] ?? false) ? 'bg-tertiary-fixed text-tertiary' : 'bg-error-container text-error' }}">
                    {{ ($status[$integration['key']] ?? false) ? 'Configured' : 'Not Set' }}
                </span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route($integration['route']) }}" class="px-4 py-2 bg-surface-container-low rounded-lg text-sm font-bold hover:bg-primary-container">Settings</a>
                <form method="POST" action="{{ route('admin.integrations.test') }}">
                    @csrf
                    <input type="hidden" name="service" value="{{ $integration['key'] }}">
                    <button type="submit" class="btn-primary btn-sm">Test</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
