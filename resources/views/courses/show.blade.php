@extends('layouts.app')

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_image', $seoImage)
@section('schema_type', 'Course')
@section('og_type', 'website')

@section('content')
@php
    $ageLabel = $course->seo_metadata['age_label'] ?? null;
    $outcomes = $course->learning_outcomes ?? [];
    $avg = $reviewStats['average'] ?? 0;
    $reviewCount = $reviewStats['count'] ?? 0;
@endphp

<div class="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto mt-8">
    <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-primary mb-6 hover:underline">
        <span class="material-symbols-outlined text-base">arrow_back</span>
        Back to Catalog
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 md:p-8 xl:gap-5 md:p-10 items-start">
        {{-- Main content --}}
        <div class="lg:col-span-8 space-y-8 order-2 lg:order-1">
            {{-- About --}}
            <section class="clay-card p-6 md:p-8 bg-white" id="about">
                <h2 class="section-heading mb-4">About this <span class="accent-green">Course</span></h2>
                <div class="text-on-surface-variant leading-relaxed text-base whitespace-pre-line">{!! $course->description !!}</div>
            </section>

            {{-- Who it's for --}}
            <section class="clay-card p-6 md:p-8 bg-white" id="who-for">
                <h2 class="section-heading mb-4">Who this course is <span class="accent-green">for</span></h2>
                <ul class="space-y-3 text-on-surface">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary shrink-0">child_care</span>
                        <span>{{ $ageLabel ? "Learners in the {$ageLabel} range" : 'Kids and teens ready for guided live learning' }} who enjoy creative, hands-on projects.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary shrink-0">psychology</span>
                        <span>Beginners and intermediate students interested in {{ $course->categories->first()?->name ?? 'this subject' }} — no prior expert knowledge required.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary shrink-0">diversity_3</span>
                        <span>Parents looking for structured, live online classes with clear progress and a final showcase.</span>
                    </li>
                </ul>
            </section>

            {{-- Outcomes --}}
            @if(count($outcomes) > 0)
            <section class="clay-card p-6 md:p-8 bg-white" id="outcomes">
                <h2 class="section-heading mb-6">Learning <span class="accent-green">Outcomes</span></h2>
                <p class="text-on-surface-variant mb-5 text-sm">By the end of this course, students will:</p>
                <ul class="space-y-3">
                    @foreach($outcomes as $outcome)
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary shrink-0 mt-0.5">check_circle</span>
                            <span class="text-on-surface">{{ $outcome }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Requirements --}}
            <section class="clay-card p-6 md:p-8 bg-white" id="requirements">
                <h2 class="section-heading mb-6">What you’ll <span class="accent-green">need</span></h2>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <li class="flex items-start gap-3 p-3 rounded-2xl bg-surface-container-low">
                        <span class="material-symbols-outlined text-primary shrink-0">laptop_mac</span>
                        <span class="text-on-surface">A laptop or desktop with a stable internet connection</span>
                    </li>
                    <li class="flex items-start gap-3 p-3 rounded-2xl bg-surface-container-low">
                        <span class="material-symbols-outlined text-primary shrink-0">headphones</span>
                        <span class="text-on-surface">Headphones or a quiet space for live sessions</span>
                    </li>
                    <li class="flex items-start gap-3 p-3 rounded-2xl bg-surface-container-low">
                        <span class="material-symbols-outlined text-primary shrink-0">mic</span>
                        <span class="text-on-surface">Working microphone and camera for interaction</span>
                    </li>
                    <li class="flex items-start gap-3 p-3 rounded-2xl bg-surface-container-low">
                        <span class="material-symbols-outlined text-primary shrink-0">schedule</span>
                        <span class="text-on-surface">Consistent attendance for the live class schedule</span>
                    </li>
                </ul>
            </section>

            {{-- Syllabus / Class Topics (live) + Syllabus Units & Modules (self-paced) --}}
            @php
                $hasUnits = $course->units && $course->units->count() > 0;
                $flatTopics = $hasUnits
                    ? $course->modules->whereNull('unit_id')->values()
                    : $course->modules->values();
            @endphp

            @if($flatTopics->count() > 0)
                <section id="curriculum">
                    <h2 class="section-heading mb-6">Syllabus / <span class="accent-green">Class Topics</span></h2>
                    <div x-data="{ showAll: false }">
                        <div class="space-y-4">
                            @foreach($flatTopics as $index => $module)
                                <div class="clay-card p-5 bg-white flex items-start gap-4" x-show="showAll || {{ $index }} < 5" @if($index >= 5) style="display: none;" @endif>
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-on-surface">{{ $module->title }}</h3>
                                        @if($module->description || $module->content_text)
                                            <div class="text-on-surface-variant text-sm mt-1"><strong>Description:</strong> {!! nl2br(e($module->description ?? $module->content_text)) !!}</div>
                                        @endif
                                        @if($module->learning_outcome)
                                            <div class="text-primary text-sm mt-2 font-medium flex gap-1 items-start">
                                                <span class="material-symbols-outlined text-sm mt-0.5">check_circle</span>
                                                <span><strong>Learning Outcome:</strong> {!! nl2br(e($module->learning_outcome)) !!}</span>
                                            </div>
                                        @endif
                                        @if($module->practical_video_url)
                                            <div class="text-secondary text-sm mt-2 font-medium flex gap-1 items-center">
                                                <span class="material-symbols-outlined text-sm">smart_display</span>
                                                <a href="{{ $module->practical_video_url }}" target="_blank" rel="noopener" class="hover:underline"><strong>Practical Video:</strong> Watch demo</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($flatTopics->count() > 5)
                            <div class="mt-6 text-center">
                                <button @click="showAll = !showAll" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full border border-primary text-primary hover:bg-primary/5 transition-colors font-semibold text-sm">
                                    <span x-text="showAll ? 'Show Less' : 'Show More'"></span>
                                    <span class="material-symbols-outlined text-sm" x-text="showAll ? 'expand_less' : 'expand_more'"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if($hasUnits)
                <section id="syllabus">
                    <h2 class="section-heading mb-6">Syllabus <span class="accent-green">Units &amp; Modules</span></h2>
                    <div class="space-y-6">
                        @foreach($course->units as $unit)
                            <div class="clay-card p-5 md:p-6 bg-white">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold">
                                        {{ $unit->order }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-on-surface">{{ $unit->title }}</h3>
                                        @if($unit->description)
                                            <p class="text-on-surface-variant text-sm mt-1">{{ $unit->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="space-y-3 pl-4 border-l-2 border-primary/20">
                                    @forelse($unit->modules->sortBy('order') as $index => $module)
                                        <div class="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low">
                                            <div class="flex-shrink-0 w-7 h-7 rounded-full bg-white text-primary flex items-center justify-center font-bold text-xs border border-outline-variant">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="font-bold text-on-surface">{{ $module->title }}</h4>
                                                    @if($module->content_type)
                                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ $module->content_type }}</span>
                                                    @endif
                                                    @if($module->duration_minutes)
                                                        <span class="text-[11px] text-on-surface-variant font-medium">{{ $module->duration_minutes }} min</span>
                                                    @endif
                                                </div>
                                                @if($module->description || $module->content_text)
                                                    <div class="text-on-surface-variant text-sm mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($module->description ?? $module->content_text), 200) }}</div>
                                                @endif
                                                @if($module->learning_outcome)
                                                    <div class="text-primary text-sm mt-1 font-medium flex gap-1 items-start">
                                                        <span class="material-symbols-outlined text-sm mt-0.5">check_circle</span>
                                                        <span>{{ $module->learning_outcome }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-on-surface-variant text-sm italic">Lessons for this unit are being added.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($flatTopics->count() === 0 && !$hasUnits)
                <section id="curriculum">
                    <h2 class="section-heading mb-6">Course <span class="accent-green">Curriculum</span></h2>
                    <div class="bg-surface-container-low p-5 rounded-2xl">
                        <p class="text-on-surface-variant text-sm">Curriculum details are being updated.</p>
                    </div>
                </section>
            @endif

            <!-- Our Guarantee -->
            <section id="our-guarantee" class="mt-12">
                <h2 class="section-heading mb-6">Our <span class="accent-green">Guarantee</span></h2>
                <div class="space-y-4">
                    <!-- #1 -->
                    <div class="clay-card p-5 bg-white flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">1</div>
                        <div>
                            <h3 class="font-bold text-lg text-on-surface">Expert designed Curriculum</h3>
                            <p class="text-on-surface-variant text-sm mt-1">Designed by MIT, Harvard Experts keeping kids in mind</p>
                        </div>
                    </div>
                    <!-- #2 -->
                    <details class="group clay-card bg-white overflow-hidden cursor-pointer">
                        <summary class="flex items-center justify-between p-5 list-none">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">2</div>
                                <h3 class="font-bold text-lg text-on-surface">100% Satisfaction Guarantee</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180 shrink-0">expand_more</span>
                        </summary>
                        <div class="px-5 pb-5 pl-4 sm:pl-[72px] text-on-surface-variant text-sm">
                            We’ll help you find the right class always, else we follow the <strong class="text-primary">Younimatiq Learning Guarantee</strong>.
                        </div>
                    </details>
                    <!-- #3 -->
                    <div class="clay-card p-5 bg-white flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">3</div>
                        <div>
                            <h3 class="font-bold text-lg text-on-surface">Expert teachers</h3>
                            <p class="text-on-surface-variant text-sm mt-1">World class, background checked, certified and highly trained teachers with 5+ years of experience in their fields.</p>
                        </div>
                    </div>
                    <!-- #4 -->
                    <div class="clay-card p-5 bg-white flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">4</div>
                        <div>
                            <h3 class="font-bold text-lg text-on-surface">Downloadable certificate</h3>
                            <p class="text-on-surface-variant text-sm mt-1">Show the world you did it !! You really did it!!</p>
                        </div>
                    </div>
                </div>
            </section>
                
            @if($course->welcome_message || $course->welcome_video_url)
                <div class="clay-card p-6 bg-surface-container-low mb-8">
                    <h2 class="font-headline-md font-bold mb-4">Welcome Message</h2>
                    
                    @if($course->welcome_video_url)
                        <div class="mb-4 aspect-video rounded-xl overflow-hidden bg-black">
                            @if(str_contains($course->welcome_video_url, 'youtube.com') || str_contains($course->welcome_video_url, 'youtu.be'))
                                @php
                                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $course->welcome_video_url, $matches);
                                    $ytId = $matches[1] ?? '';
                                @endphp
                                @if($ytId)
                                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $ytId }}?enablejsapi=1" frameborder="0" allowfullscreen></iframe>
                                @endif
                            @else
                                <video class="w-full h-full object-contain" controls preload="none" @if($course->thumbnail_url) poster="{{ $course->thumbnail_url }}" @endif>
                                    <source src="{{ $course->welcome_video_url }}" type="video/mp4">
                                </video>
                            @endif
                        </div>
                    @endif

                    @if($course->welcome_message)
                    <div class="prose prose-on-surface max-w-none leading-relaxed">
                        @php
                            $welcomeMessage = (string) $course->welcome_message;
                            $welcomeIsHtml = $welcomeMessage !== strip_tags($welcomeMessage);
                        @endphp
                        {!! $welcomeIsHtml ? $welcomeMessage : nl2br(e($welcomeMessage)) !!}
                    </div>
                    @endif
                </div>
            @endif

            {{-- Certificate --}}
            <section class="clay-card p-6 md:p-8 bg-white" id="certificate">
                <div class="flex flex-col sm:flex-row gap-6 items-start">
                    <div class="w-16 h-16 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-4xl">military_tech</span>
                    </div>
                    <div>
                        <h2 class="section-heading mb-3">Certificate of <span class="accent-green">Completion</span></h2>
                        <p class="text-on-surface-variant leading-relaxed">
                            Students who complete the modules and final project receive a Younimatiq certificate they can share with family, teachers, and future programs — a real milestone for their learning journey.
                        </p>
                    </div>
                </div>
            </section>

            {{-- FAQ --}}
            @if(($faqs ?? collect())->isNotEmpty())
            <section class="clay-card p-6 md:p-8 bg-white" id="faq">
                <h2 class="section-heading mb-2">Frequently Asked <span class="accent-green">Questions</span></h2>
                <p class="text-on-surface-variant text-sm mb-6">Common questions about live classes and enrollment.</p>
                <div class="space-y-3" x-data="{ open: 0 }">
                    @foreach($faqs as $index => $faq)
                        <div class="rounded-2xl border border-outline-variant/20 overflow-hidden">
                            <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}" class="w-full p-4 md:p-5 text-left flex justify-between items-center gap-4 hover:bg-surface-container-low transition-colors">
                                <span class="font-bold text-on-surface">{{ $faq->question }}</span>
                                <span class="material-symbols-outlined text-primary transition-transform shrink-0" :class="open === {{ $index }} ? 'rotate-180' : ''">expand_more</span>
                            </button>
                            <div x-show="open === {{ $index }}" x-cloak class="px-4 md:px-5 pb-5 text-on-surface-variant leading-relaxed">
                                {!! $faq->answer !!}
                            </div>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('faq') }}" class="inline-flex items-center gap-1 mt-6 font-bold text-primary hover:underline">
                    View all FAQs
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </section>
            @endif

            {{-- Related courses --}}
            @if(($relatedCourses ?? collect())->isNotEmpty())
            <section id="related">
                <h2 class="section-heading mb-6">Related <span class="accent-green">Courses</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($relatedCourses as $related)
                        <x-course-card :course="$related" :hideBookFreeClass="true" />
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        <aside class="lg:col-span-4 space-y-5 order-1 lg:order-2">
            <div class="clay-card overflow-hidden bg-white" 
                 x-data="{ 
                     currentSlide: 0,
                     hasVideo: {{ $course->video_url ? 'true' : 'false' }},
                     hasImage: {{ $course->thumbnail_url ? 'true' : 'false' }},
                     slides: {{ $course->video_url && $course->thumbnail_url ? 2 : ($course->thumbnail_url || $course->video_url ? 1 : 0) }},
                     init() {
                         if(this.slides > 1) {
                             setTimeout(() => {
                                 if(this.currentSlide === 0) {
                                     this.currentSlide = 1;
                                 }
                             }, 5000);
                         }
                     }
                 }">
                @if($course->thumbnail_url || $course->video_url)
                    <div class="bg-surface-container-low aspect-square relative flex items-center justify-center overflow-hidden group">
                        
                        @if($course->thumbnail_url)
                        <div class="absolute inset-0 transition-opacity duration-500" 
                             :class="currentSlide === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-full object-contain" loading="lazy">
                        </div>
                        @endif

                        @if($course->video_url)
                        <div class="absolute inset-0 transition-opacity duration-500 bg-black flex items-center justify-center" 
                             :class="currentSlide === (hasImage ? 1 : 0) ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                            @if(str_contains($course->video_url, 'youtube.com') || str_contains($course->video_url, 'youtu.be'))
                                @php
                                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $course->video_url, $matches);
                                    $ytId = $matches[1] ?? '';
                                @endphp
                                @if($ytId)
                                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $ytId }}?autoplay=0&mute=0&controls=1&rel=0&enablejsapi=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                @endif
                            @else
                                <video x-ref="courseVideo" class="w-full h-full object-contain" controls preload="none" @if($course->thumbnail_url) poster="{{ $course->thumbnail_url }}" @endif controlsList="nodownload" oncontextmenu="return false;"
                                    x-effect="if(currentSlide === (hasImage ? 1 : 0)) { $refs.courseVideo.play().catch(() => {}) } else { $refs.courseVideo.pause() }"
                                    x-init="
                                        if (typeof Hls !== 'undefined' && Hls.isSupported() && '{{ $course->video_url }}'.includes('.m3u8')) {
                                            const hls = new Hls();
                                            hls.loadSource('{{ $course->video_url }}');
                                            hls.attachMedia($el);
                                        } else {
                                            $el.src = '{{ $course->video_url }}';
                                        }
                                    ">
                                    Your browser does not support the video tag.
                                </video>
                            @endif
                        </div>
                        @endif

                        @if($course->thumbnail_url && $course->video_url)
                        <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-20">
                            <button @click="currentSlide = 0" class="w-2.5 h-2.5 rounded-full transition-colors" :class="currentSlide === 0 ? 'bg-primary' : 'bg-white/50 hover:bg-white/75'" aria-label="Show image"></button>
                            <button @click="currentSlide = 1" class="w-2.5 h-2.5 rounded-full transition-colors" :class="currentSlide === 1 ? 'bg-primary' : 'bg-white/50 hover:bg-white/75'" aria-label="Show video"></button>
                        </div>
                        
                        <button @click="currentSlide = currentSlide === 0 ? 1 : 0" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/75 hover:bg-white text-on-surface flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 shadow-sm">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </button>
                        <button @click="currentSlide = currentSlide === 0 ? 1 : 0" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/75 hover:bg-white text-on-surface flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 shadow-sm">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </button>
                        @endif
                    </div>
                @endif

                <div class="p-5 md:p-6">
                    <div class="flex flex-wrap gap-2 mb-3">
                        @if($course->categories->first())
                            <span class="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">{{ $course->categories->first()->name }}</span>
                        @endif
                        @if($ageLabel)
                            <span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">{{ $ageLabel }}</span>
                        @endif
                        @if($course->course_type)
                            <span class="px-3 py-1 bg-surface-container-high text-on-surface text-xs font-bold rounded-full uppercase">{{ $course->course_type === 'live' ? 'Live Class' : $course->course_type }}</span>
                        @endif
                    </div>

                    <h1 class="text-xl font-bold text-on-surface mb-2 leading-snug">{{ $course->title }}</h1>

                    @if($reviewCount > 0)
                        <a href="#reviews" class="inline-flex items-center gap-1.5 text-primary font-bold text-sm hover:underline">
                            <span class="material-symbols-outlined text-tertiary text-lg" style="font-variation-settings:'FILL' 1">star</span>
                            <span>{{ number_format($avg, 1) }}</span>
                            <span class="text-on-surface-variant font-medium">({{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }})</span>
                        </a>
                    @endif

                    <div class="mt-5 pt-5 border-t border-outline-variant/20">

                        @if($isEnrolled ?? false)
                            <a href="{{ route('student.courses.player', $course) }}" class="w-full btn-primary py-3.5 flex items-center justify-center">
                                Continue Learning
                            </a>
                        @else
                            @php
                                $pdCurrency = strtoupper(session('display_currency', 'USD'));
                                if ($pdCurrency === 'AED') {
                                    $pdPerClass = floatval($course->price_aed ?? 30);
                                    $pdSymbol = 'د.إ ';
                                } elseif ($pdCurrency === 'INR') {
                                    $pdPerClass = floatval($course->price_inr ?? 600);
                                    $pdSymbol = '₹';
                                } else {
                                    $pdCurrency = 'USD';
                                    $pdPerClass = floatval($course->price_usd ?? 18);
                                    $pdSymbol = '$';
                                }
                            @endphp
                            <div class="space-y-4 w-full" x-data="{
                                classesCount: 1,
                                pricePerClass: {{ $pdPerClass }},
                                currency: '{{ $pdCurrency }}',
                                currencySymbol: '{{ $pdSymbol }}',
                                get totalAmount() {
                                    return this.pricePerClass * this.classesCount;
                                },
                                get paymentLink() {
                                    return `{{ route('student.checkout', $course) }}?classes=${this.classesCount}&currency=${this.currency}&amount=${this.totalAmount}`;
                                }
                            }">

                                <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/30 mt-4">
                                    <h3 class="font-bold text-on-surface mb-3 text-sm">Enroll Now</h3>
                                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Number of classes (max 48)</label>
                                    <input type="number" x-model.number="classesCount" min="1" max="48" class="w-full p-2.5 rounded-lg border border-outline-variant/40 mb-3 bg-white text-sm" @input="if(classesCount > 48) classesCount = 48; if(classesCount < 1) classesCount = 1;">
                                    
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-sm text-on-surface-variant font-medium">Total Amount:</span>
                                        <span class="font-bold text-lg text-primary" x-text="currencySymbol + totalAmount"></span>
                                    </div>

                                    <a :href="paymentLink" class="w-full py-3 flex items-center justify-center gap-2 text-sm font-bold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
                                        Generate Payment Link
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            <section class="clay-card p-5 md:p-6 bg-white" id="instructor">
                <h2 class="text-base font-bold text-on-surface mb-4">Meet your <span class="accent-green">Instructor</span></h2>
                <div class="flex items-start gap-3">
                    <div class="w-14 h-14 rounded-full bg-primary/15 text-primary flex items-center justify-center text-xl font-bold shrink-0">
                        {{ strtoupper(substr($course->tutor->name ?? 'B', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-on-surface mb-0.5">{{ $course->tutor->name ?? 'Younimatiq Educator' }}</h3>
                        <p class="text-xs font-bold text-primary mb-2">Live {{ $course->categories->first()?->name ?? 'STEM' }} Educator</p>
                        <p class="text-sm text-on-surface-variant leading-relaxed">
                            Experienced Younimatiq tutor specializing in engaging, project-based live classes.
                            Students get personal feedback and a safe space to explore {{ strtolower($course->categories->first()?->name ?? 'new skills') }} with confidence.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Write a Review --}}
            <section class="clay-card p-5 md:p-6 bg-white" id="write-review">
                <h2 class="text-base font-bold text-on-surface mb-1">
                    {{ ($userReview ?? null) ? 'Update your review' : 'Write a Review' }}
                </h2>
                <p class="text-xs text-on-surface-variant mb-4">Share your experience with this course.</p>

                

                @auth('web')
                    <form
                        method="POST"
                        action="{{ route('student.reviews.store') }}"
                        class="space-y-4"
                        x-data="{ rating: {{ (int) old('rating', $userReview->rating ?? 5) }} }"
                    >
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <input type="hidden" name="rating" :value="rating">

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-2">Your rating</label>
                            <div class="flex items-center gap-1" role="radiogroup" aria-label="Rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <button
                                        type="button"
                                        @click="rating = {{ $i }}"
                                        class="p-0.5 transition-transform hover:scale-110"
                                        :aria-pressed="rating >= {{ $i }}"
                                    >
                                        <span
                                            class="material-symbols-outlined text-2xl"
                                            :class="rating >= {{ $i }} ? 'text-tertiary' : 'text-outline-variant'"
                                            :style="rating >= {{ $i }} ? 'font-variation-settings:\'FILL\' 1' : 'font-variation-settings:\'FILL\' 0'"
                                        >star</span>
                                    </button>
                                @endfor
                                <span class="ml-2 text-sm font-bold text-on-surface" x-text="rating + ' / 5'"></span>
                            </div>
                            @error('rating')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="review-comment" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-2">Your comment</label>
                            <textarea
                                id="review-comment"
                                name="comment"
                                rows="3"
                                class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-low px-3 py-2.5 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-y"
                                placeholder="What did you or your child enjoy most?"
                            >{{ old('comment', $userReview->comment ?? '') }}</textarea>
                            @error('comment')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full btn-primary py-3 text-sm flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">rate_review</span>
                            {{ ($userReview ?? null) ? 'Update Review' : 'Submit Review' }}
                        </button>
                    </form>
                @else
                    <div class="rounded-xl bg-surface-container-low p-4 text-center">
                        <p class="text-sm text-on-surface-variant mb-3">Log in to leave a review for this course.</p>
                        <a href="{{ route('login.student') }}" class="btn-primary btn-sm inline-flex items-center justify-center px-5 py-2.5">
                            Login to Write a Review
                        </a>
                    </div>
                @endauth
            </section>

            <section class="clay-card p-5 md:p-6 bg-white" id="reviews">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-base font-bold text-on-surface">Reviews</h2>
                    @if($reviewCount > 0)
                        <div class="flex flex-col items-end shrink-0">
                            <p class="text-xl font-bold text-primary leading-none">{{ number_format($avg, 1) }}</p>
                            <div class="flex items-center gap-0.5 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings:'FILL' {{ $i <= round($avg) ? 1 : 0 }}">star</span>
                                @endfor
                            </div>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</p>
                        </div>
                    @endif
                </div>

                @if($reviewCount > 0)
                    <div class="space-y-3 max-h-[24rem] overflow-y-auto pr-1">
                        @foreach($course->reviews->take(6) as $review)
                            <article class="p-3 rounded-xl bg-surface-container-low">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="w-8 h-8 rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($review->reviewer_display_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm text-on-surface truncate">{{ $review->reviewer_display_name }}</p>
                                            <p class="text-[11px] text-on-surface-variant">{{ $review->created_at?->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center shrink-0 ml-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings:'FILL' {{ $i <= $review->rating ? 1 : 0 }}">star</span>
                                        @endfor
                                    </div>
                                </div>
                                @if($review->comment)
                                    <p class="text-sm text-on-surface-variant leading-relaxed pl-10">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-outline-variant/40 py-8 px-4 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-3xl text-primary/40 mb-2 block">reviews</span>
                        <p class="font-bold text-on-surface text-sm mb-1">No reviews yet</p>
                        <p class="text-xs">Be the first to write one above.</p>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>

<script>
    function pauseCourseVideos() {
        document.querySelectorAll('video').forEach(video => {
            if (!video.paused) {
                video.dataset.wasPlaying = 'true';
                video.pause();
            }
        });
        document.querySelectorAll('iframe').forEach(iframe => {
            if (iframe.src.includes('youtube.com')) {
                iframe.contentWindow.postMessage(JSON.stringify({event: "command", func: "pauseVideo", args: ""}), '*');
            }
        });
    }

    function resumeCourseVideos() {
        document.querySelectorAll('video').forEach(video => {
            if (video.dataset.wasPlaying === 'true') {
                video.play().catch(() => {}); // Catch prevents unhandled promise rejections
                video.dataset.wasPlaying = 'false';
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) pauseCourseVideos();
        else resumeCourseVideos();
    });
    window.addEventListener('blur', pauseCourseVideos);
    window.addEventListener('focus', resumeCourseVideos);
</script>

@endsection
