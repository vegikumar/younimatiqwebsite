@extends('layouts.dashboard')

@section('title', 'SEO Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">search</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Global SEO Settings</h2>
                <p class="font-body-md text-on-surface-variant">Manage meta tags for better search engine visibility.</p>
            </div>
        </div>

        

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Global Meta Title</label>
                <input type="text" name="seo_meta_title" value="{{ $settings['seo_meta_title'] ?? 'Younimatiq — Personalized K–12 Online Learning' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Global Meta Description</label>
                <textarea name="seo_meta_description" rows="3" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">{{ $settings['seo_meta_description'] ?? 'Personalized K–12 online learning with expert mentors. Coding, AI, robotics, math, science, arts and more — built around your child’s interests.' }}</textarea>
            </div>
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Global Meta Keywords (comma separated)</label>
                <input type="text" name="seo_meta_keywords" value="{{ $settings['seo_meta_keywords'] ?? 'Younimatiq, personalized learning, online classes for kids, K-12 tutoring, coding for kids, STEM courses, AI for kids, robotics for kids' }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-lg">
            </div>

            <div class="pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-enroll">Save SEO Data</button>
            </div>
        </form>
    </div>
</div>
@endsection
