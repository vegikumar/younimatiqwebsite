@extends('layouts.app')

@section('title', 'Pricing & Plans | Younimatiq')

@section('content')
<main style="padding-top: 80px; min-height: 100vh; background: #f8fafc;">
    <section class="pt-6 pb-20 md:pt-8 md:pb-24 mx-4 lg:mx-8 overflow-hidden" id="pricing">
        <div class="w-full px-6 md:px-12 max-w-7xl mx-auto">
            <div class="text-center mb-10 md:mb-12">
                <div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4 text-on-secondary-container bg-secondary-container border border-secondary-container">
                    Younimatiq • Pricing
                </div>
                <h2 class="text-4xl md:text-5xl font-extrabold text-on-surface mb-6" style="font-family: var(--font-display, 'Baloo 2', sans-serif); letter-spacing: -0.02em;">
                    Invest in your child's future.
                </h2>
                <p class="text-lg text-on-surface max-w-3xl mx-auto">
                    1:1 Personalized classes, paced entirely to your child. Choose the plan that fits your goals.
                </p>
                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 16px; color: #38B91F; font-size: 14px; font-weight: 700;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">how_to_reg</span>
                    <span>All tutors are background-checked</span>
                </div>
            </div>
            
            <div data-currency-only="INR">
            <div class="grid md:grid-cols-1 gap-8 max-w-md mx-auto">
                

                <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
                    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                        <span class="text-xs font-semibold text-error line-through">₹1,200<span class="font-normal not-italic">/class</span></span>
                        <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
                    </div>
                    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Program</div>
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-5xl font-extrabold text-on-surface">₹600</span>
                        <span class="text-on-surface-variant font-medium">/ class</span>
                    </div>

                    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, with a dedicated instructor.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1:1 personalized class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1–2 live classes / week</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Track-matched curriculum & projects</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Progress reports after every class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Flexible reschedule, easy make-ups</li>
                    </ul>
                    <a href="#enrollModal" onclick="openEnrollModal(event)" class="btn-primary w-full justify-center mt-6">Enroll Now</a>

                </div>
            </div>
            </div>
<div data-currency-only="OMR">
            <div class="grid md:grid-cols-1 gap-8 max-w-md mx-auto">
                

                <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
                    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                        <span class="text-xs font-semibold text-error line-through">ر.ع. 6<span class="font-normal not-italic">/class</span></span>
                        <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
                    </div>
                    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Program</div>
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-5xl font-extrabold text-on-surface">ر.ع. 3</span>
                        <span class="text-on-surface-variant font-medium">/ class</span>
                    </div>

                    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, with a dedicated instructor.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1:1 personalized class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1–2 live classes / week</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Track-matched curriculum & projects</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Progress reports after every class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Flexible reschedule, easy make-ups</li>
                    </ul>
                    <a href="#enrollModal" onclick="openEnrollModal(event)" class="btn-primary w-full justify-center mt-6">Enroll Now</a>

                </div>
            </div>
</div>
<div data-currency-only="AED">
            <div class="grid md:grid-cols-1 gap-8 max-w-md mx-auto">
                

                <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
                    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                        <span class="text-xs font-semibold text-error line-through">د.إ 60<span class="font-normal not-italic">/class</span></span>
                        <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
                    </div>
                    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Program</div>
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-5xl font-extrabold text-on-surface">د.إ 30</span>
                        <span class="text-on-surface-variant font-medium">/ class</span>
                    </div>

                    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, with a dedicated instructor.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1:1 personalized class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1–2 live classes / week</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Track-matched curriculum & projects</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Progress reports after every class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Flexible reschedule, easy make-ups</li>
                    </ul>
                    <a href="#enrollModal" onclick="openEnrollModal(event)" class="btn-primary w-full justify-center mt-6">Enroll Now</a>

                </div>
            </div>
            </div>
<div data-currency-except="INR OMR AED">
            <div class="grid md:grid-cols-1 gap-8 max-w-md mx-auto">
                <div class="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
                    <div class="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                        <span class="text-xs font-semibold text-error line-through">@price(40)<span class="font-normal not-italic">/class</span></span>
                        <span class="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">SAVE 50%</span>
                    </div>
                    <div class="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Program</div>
                    <div class="flex items-baseline gap-2 mb-1">
                        @price(20, [], 'text-5xl font-extrabold text-on-surface')
                        <span class="text-on-surface-variant font-medium">/ class</span>
                    </div>

                    <p class="text-on-surface text-sm mb-6 mt-6">Full access to your child's track, with a dedicated instructor.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1:1 personalized class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> 1–2 live classes / week</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Track-matched curriculum & projects</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Progress reports after every class</li>
                        <li class="flex items-start gap-3 text-sm text-on-surface"><span class="material-symbols-outlined text-tertiary text-base mt-0.5 shrink-0">check_circle</span> Flexible reschedule, easy make-ups</li>
                    </ul>
                    <a href="#enrollModal" onclick="openEnrollModal(event)" class="btn-primary w-full justify-center mt-6">Enroll Now</a>

                </div>
            </div>

</div>
        </div>
    </section>

    <!-- Categories Section -->
    @php
        $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    @endphp
    <section class="py-16 md:py-20 bg-surface-container-low border-t border-outline-variant">
        <div class="w-full px-6 md:px-12 max-w-7xl mx-auto text-center">
            <h2 class="text-2xl md:text-3xl font-extrabold text-on-surface mb-10" style="font-family: var(--font-display, 'Baloo 2', sans-serif); letter-spacing: -0.02em;">
                Explore Courses by Category
            </h2>
            <div class="flex flex-wrap justify-center gap-4">
                @foreach($categories as $category)
                <a href="{{ route('courses.index', ['categories' => [$category->slug]]) }}" class="bg-white border border-secondary-container hover:border-secondary hover:shadow-md text-on-secondary-container font-bold py-3 px-6 rounded-full transition-all duration-300">
                    {{ $category->name }}
                </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 md:py-24 bg-white">
        <div class="w-full px-6 md:px-12 max-w-7xl mx-auto">
            <div class="grid md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="w-16 h-16 bg-secondary-container text-on-secondary-container rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <span class="material-symbols-outlined text-3xl">workspace_premium</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface mb-3">Expert Tutors</h3>
                    <p class="text-on-surface">Our instructors are rigorously vetted and trained to provide the best learning experience for your child.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-secondary-container text-on-secondary-container rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <span class="material-symbols-outlined text-3xl">laptop_mac</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface mb-3">Interactive Learning</h3>
                    <p class="text-on-surface">Engaging, project-based curriculum designed to keep students motivated and excited to learn.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-fixed text-primary rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <span class="material-symbols-outlined text-3xl">trending_up</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface mb-3">Track Progress</h3>
                    <p class="text-on-surface">Get detailed progress reports after every class to see exactly how your child is improving.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    @php
        $pricingFaqs = \App\Models\Faq::where('is_active', true)
            ->where('category', 'Pricing')
            ->orderBy('sort_order')
            ->get();
    @endphp
    <section class="py-20 md:py-24 bg-surface-container-low" id="faq">
        <div class="w-full px-6 md:px-12 max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-on-surface mb-6" style="font-family: var(--font-display, 'Baloo 2', sans-serif); letter-spacing: -0.02em;">
                    Frequently Asked Questions
                </h2>
                <p class="text-lg text-on-surface max-w-2xl mx-auto">
                    Have questions? We're here to help you understand our pricing and programs better.
                </p>
            </div>
            <div class="max-w-3xl mx-auto space-y-6">
                @foreach($pricingFaqs as $faq)
                <div class="bg-white rounded-2xl p-6 border border-outline-variant shadow-sm">
                    <h3 class="text-lg font-bold text-on-surface mb-2">{{ $faq->question }}</h3>
                    <div class="text-on-surface">
                        {!! $faq->answer !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
</main>
@include('partials.enroll_modal')
@endsection
