@extends('layouts.dashboard')

@section('title', 'Resource Library & Business Toolkit')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Educator Resource Library & Toolkit</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Access the Educator Handbook, marketing templates, curriculum structuring guides, and business toolkits to scale your teaching brand.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card bg-white p-6 md:p-8 space-y-3 border border-outline-variant/20">
            <div class="w-12 h-12 rounded-2xl bg-primary-container text-on-primary-container font-bold flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">menu_book</span>
            </div>
            <h3 class="font-bold text-lg text-on-surface">Educator Handbook</h3>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Comprehensive guide on parent communication best practices, classroom engagement strategies, and teaching guidelines.
            </p>
            <button class="btn-outline-primary w-full py-2 text-xs font-bold mt-2">Open Handbook</button>
        </div>

        <div class="clay-card bg-white p-6 md:p-8 space-y-3 border border-outline-variant/20">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container text-on-secondary-container font-bold flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">campaign</span>
            </div>
            <h3 class="font-bold text-lg text-on-surface">Marketing & Growth Guide</h3>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Learn how to build your teacher brand, optimize your public storefront bio, and maximize student re-enrollments.
            </p>
            <button class="btn-outline-primary w-full py-2 text-xs font-bold mt-2">View Marketing Tips</button>
        </div>

        <div class="clay-card bg-white p-6 md:p-8 space-y-3 border border-outline-variant/20">
            <div class="w-12 h-12 rounded-2xl bg-tertiary-container text-on-tertiary-container font-bold flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">extension</span>
            </div>
            <h3 class="font-bold text-lg text-on-surface">Business & Lesson Toolkit</h3>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Slide deck templates, class setup checklists, and syllabus planning worksheets.
            </p>
            <button class="btn-outline-primary w-full py-2 text-xs font-bold mt-2">Open Toolkit</button>
        </div>
    </div>
</div>
@endsection
