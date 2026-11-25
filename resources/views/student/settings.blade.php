@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Settings ⚙️</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage your account preferences and security.</p>
    </div>

    

    <form method="POST" action="{{ route('student.settings.update') }}">
        @csrf
        <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
            @if($errors->any())
                <div class="p-4 m-8 mb-0 bg-error-container text-on-error-container rounded-xl text-sm">
                    <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="p-5 md:p-8 border-b border-surface-container-high">
                <h2 class="font-headline-md font-bold mb-6">Password Reset</h2>
                <div class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-sm font-bold mb-2">New Password</label>
                        <input type="password" name="password" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors">
                    </div>
                </div>
            </div>



            <div class="p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-6">Notification Preferences</h2>
                @php $prefs = $user->notification_preferences ?? []; @endphp
                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="email_reminders" value="1" {{ ($prefs['email_reminders'] ?? true) ? 'checked' : '' }} class="w-5 h-5 rounded border-surface-container-high text-primary focus:ring-primary">
                        <span>Email me reminders for upcoming sessions</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="email_marketing" value="1" {{ ($prefs['email_marketing'] ?? false) ? 'checked' : '' }} class="w-5 h-5 rounded border-surface-container-high text-primary focus:ring-primary">
                        <span>Email me about new courses and promotions</span>
                    </label>
                </div>
                <button type="submit" class="mt-6 btn-primary">Save Settings</button>
            </div>
        </div>
    </form>
</div>
@endsection
