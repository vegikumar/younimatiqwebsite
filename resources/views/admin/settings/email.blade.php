@extends('layouts.dashboard')

@section('title', 'SMTP Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">outgoing_mail</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">SMTP & Email Settings</h2>
                <p class="font-body-md text-on-surface-variant">Configure outgoing mail server and sender details.</p>
            </div>
        </div>

        

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf

            <div class="space-y-4">
                <h3 class="font-bold text-lg text-primary border-b border-outline-variant pb-2">Mail Server (SMTP)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="smtp.mailgun.org">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">SMTP Port</label>
                        <input type="number" name="mail_port" value="{{ $settings['mail_port'] ?? '587' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">SMTP Username</label>
                        <input type="text" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">SMTP Password</label>
                        <input type="password" name="mail_password" value="{{ $settings['mail_password'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Encryption</label>
                    <select name="mail_encryption" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="tls" {{ ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ ($settings['mail_encryption'] ?? 'tls') === '' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="font-bold text-lg text-secondary border-b border-outline-variant pb-2">Sender Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">From Email</label>
                        <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? 'support@younimatiq.com' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">From Name</label>
                        <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? 'Younimatiq' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Admin Notification Email</label>
                    <input type="email" name="admin_email" value="{{ $settings['admin_email'] ?? '' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="admin@younimatiq.com">
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Save Email Settings</button>
            </div>
        </form>

        <div class="mt-12 pt-8 border-t border-outline-variant">
            <h3 class="font-bold text-lg text-primary mb-4">Send Test Email</h3>
            <p class="text-sm text-on-surface-variant mb-4">Verify that your SMTP settings are working by sending a test email to yourself.</p>
            <form method="POST" action="{{ route('admin.settings.email.test') }}" class="flex flex-col sm:flex-row gap-4">
                @csrf
                <input type="email" name="test_email" class="flex-1 p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Enter email to test..." required>
                <button type="submit" class="btn-enroll px-8 whitespace-nowrap">Send Test</button>
            </form>
        </div>
    </div>
</div>
@endsection
