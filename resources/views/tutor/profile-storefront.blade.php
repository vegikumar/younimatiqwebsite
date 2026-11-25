@extends('layouts.dashboard')

@section('title', 'My Brand Storefront Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Teacher Brand & Storefront Profile</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Customize your public storefront bio, teaching style, and video intro that families browse before booking.</p>
            </div>
            <a href="{{ route('tutors.storefront', Auth::user()) }}" target="_blank" class="btn-primary py-2.5 px-5 text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">open_in_new</span> Preview Public Storefront
            </a>
        </div>
    </div>

    

    <div class="clay-card bg-white p-6 md:p-8 max-w-3xl">
        <form method="POST" action="{{ route('tutor.storefront.update') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Teacher Bio (Storefront Headline)</label>
                <textarea name="bio" rows="4" placeholder="Introduce yourself to families... What inspires your teaching?" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary">{{ old('bio', $tutor->bio) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Teaching Philosophy & Style</label>
                <textarea name="teaching_style" rows="4" placeholder="Describe your classroom approach (e.g. hands-on, encouraging, interactive)..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary">{{ old('teaching_style', $tutor->teaching_style) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Intro Video URL (YouTube / Vimeo / Loom)</label>
                <input type="url" name="intro_video_url" value="{{ old('intro_video_url', $tutor->intro_video_url) }}" placeholder="https://youtube.com/watch?v=..." class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary">
            </div>

            <button type="submit" class="btn-enroll py-3 px-8 text-xs font-bold">
                Save Storefront Profile
            </button>
        </form>
    </div>
</div>
@endsection
