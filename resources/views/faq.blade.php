@extends('layouts.app')

@section('seo_title', 'Younimatiq Faq')
@section('seo_description', 'Find answers to common questions about Younimatiq online courses, classes, age groups, learning programs, and enrollment. Explore our FAQ for parents and students.')

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="FAQ">
    <h1 class="brand-title">Frequently <span class="text-brand-accent">Asked Questions</span></h1>
    <p class="brand-description">Everything you need to know about learning with us.</p>
</x-brand-hero>

<div class="pb-20 px-4 md:px-6 xl:px-8 max-w-4xl mx-auto space-y-12">
    @foreach($faqs as $category => $categoryFaqs)
        <section>
            <h2 class="text-2xl font-bold text-on-surface mb-6">{{ $category }}</h2>
            <div class="space-y-4">
                @foreach($categoryFaqs as $faq)
                    <details class="group clay-card bg-white rounded-2xl overflow-hidden">
                        <summary class="flex justify-between items-center p-6 cursor-pointer font-bold text-lg list-none gap-4 text-on-surface">
                            {{ $faq->question }}
                            <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180 shrink-0">expand_more</span>
                        </summary>
                        <div class="px-6 pb-6 text-on-surface-variant font-body-md border-t border-outline-variant/10 prose prose-sm max-w-none">
                            {!! $faq->answer !!}
                        </div>
                    </details>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
