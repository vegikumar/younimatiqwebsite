@extends('layouts.dashboard')

@section('title', 'Newsletter Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-tertiary/10 text-tertiary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">mail</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Newsletter Integration</h2>
                <p class="font-body-md text-on-surface-variant">Configure Mailchimp or internal newsletter collection settings.</p>
            </div>
        </div>

        

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Mailchimp API Key</label>
                <input type="password" name="mailchimp_api_key" value="{{ $settings['mailchimp_api_key'] ?? '' }}" placeholder="Enter Mailchimp API Key" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Mailchimp Audience/List ID</label>
                <input type="text" name="mailchimp_list_id" value="{{ $settings['mailchimp_list_id'] ?? '' }}" placeholder="Enter List ID" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
            </div>

            <div class="pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Save Newsletter Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
