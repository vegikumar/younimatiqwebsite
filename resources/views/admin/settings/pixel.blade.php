@extends('layouts.dashboard')

@section('title', 'Meta Pixel Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-[#1877F2]/10 text-[#1877F2] rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">track_changes</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Meta Pixel Configuration</h2>
                <p class="font-body-md text-on-surface-variant">Track conversions and user behavior across Younimatiq.</p>
            </div>
        </div>

        

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Meta (Facebook) Pixel ID</label>
                <input type="text" name="meta_pixel_id" value="{{ $settings['meta_pixel_id'] ?? '' }}" placeholder="e.g. 123456789012345" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
                <p class="text-sm text-on-surface-variant mt-1">This ID will be automatically injected into the head of all public pages.</p>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Google Analytics 4 Measurement ID</label>
                <input type="text" name="google_analytics_id" value="{{ $settings['google_analytics_id'] ?? '' }}" placeholder="e.g. G-XXXXXXXXXX" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
                <p class="text-sm text-on-surface-variant mt-1">Leave blank to disable. Demo IDs are ignored on the live site.</p>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Microsoft Clarity Project ID</label>
                <input type="text" name="microsoft_clarity_id" value="{{ $settings['microsoft_clarity_id'] ?? 'yn3mggp6o4' }}" placeholder="e.g. yn3mggp6o4" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
                <p class="text-sm text-on-surface-variant mt-1">Injected on all public pages (and admin). Loads after first interaction or ~3.5s so it does not hurt LCP.</p>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Google Search Console verification</label>
                <input type="text" name="google_site_verification" value="{{ $settings['google_site_verification'] ?? '' }}" placeholder="HTML tag content token only" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
                <p class="text-sm text-on-surface-variant mt-1">Paste only the <code>content</code> value from Google’s meta verification tag.</p>
            </div>

            <div class="pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Save Tracking Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
