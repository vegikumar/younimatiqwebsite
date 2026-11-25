@extends('layouts.app')
@section('seo_title', 'Online Classes for Kids | Younimatiq')
@section('seo_description', 'Explore online classes for kids at Younimatiq. Learn Coding, AI, Robotics, Math, English, Minecraft and more with live 1:1 and small-group coaching.')
@section('seo_keywords', 'Younimatiq, personalized learning, online classes for kids, K-12 tutoring, coding for kids, STEM courses, AI for kids, robotics for kids, online math classes')
@section('seo_image', asset('og-default.png'))

@php
    $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    $gradeBands = \App\Models\AgeGroup::active()
        ->orderBy('sort_order')
        ->get()
        ->mapWithKeys(fn ($g) => [$g->slug => ['label' => $g->name, 'grade_min' => (int) $g->slug, 'grade_max' => (int) $g->slug]])
        ->all();
@endphp
@section('schema_faq')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "How often are classes, and how long do they run?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Most students attend 2–3 sessions per week, 45–55 minutes each. Schedules flex around school, sports, and family commitments."
      }
    },
    {
      "@@type": "Question",
      "name": "What devices does my child need?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "A laptop or tablet with a webcam and stable internet connection. Coding and AI Mastery tracks work best on a laptop."
      }
    },
    {
      "@@type": "Question",
      "name": "Is the curriculum aligned with my child's school?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Yes — coaches customize sessions to complement your child's grade-level curriculum, aligned to Common Core standards."
      }
    },
    {
      "@@type": "Question",
      "name": "Can my child try a class before enrolling?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Absolutely — every family starts with one free trial class, no commitment required."
      }
    },
    {
      "@@type": "Question",
      "name": "Can my child combine two subjects, like Coding and AI Mastery?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Yes, many families mix and match tracks. We'll help you build a schedule that fits."
      }
    }
  ]
}
</script>
@endsection

@section('content')
<!-- Hero Section (Younimatiq Homepage Design with Brand Gradient) -->
<section class="relative py-8 sm:py-12 lg:py-14 min-h-fit flex items-center overflow-hidden px-3 sm:px-6 md:px-8 mx-auto w-full md:w-[98%] max-w-[1600px] mt-0 md:mt-6 rounded-none md:rounded-[2.5rem] hero-panel hero-panel--playful isolate z-0" style="background: var(--brand-gradient);">

    <!-- Soft paper grain overlay (visual only) -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxjaXJjbGUgY3g9IjIiIGN5PSIyIiByPSIxLjIiIGZpbGw9InJnYmEoMTEzLCA0OCwgMjE2LCAwLjA0KSIvPgo8L3N2Zz4=')] opacity-70 pointer-events-none"></div>

    <!-- Playful decorative layer (visual only — does not change content) -->
    <div class="hero-deco hero-deco--with-media pointer-events-none absolute inset-0 z-[1] overflow-hidden" aria-hidden="true">
        <span class="hero-blob hero-blob--purple"></span>
        <span class="hero-blob hero-blob--blue"></span>
        <span class="hero-blob hero-blob--orange"></span>
        <span class="hero-blob hero-blob--green"></span>
        <span class="hero-float hero-float--star" style="top:12%; left:6%; animation-delay:0s;">
            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="#FFC400" d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>
        </span>
        <span class="hero-float hero-float--star" style="bottom:16%; left:12%; animation-delay:0.6s;">
            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#087FF5" d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>
        </span>
        <span class="hero-float hero-float--icon" style="top:55%; left:4%; animation-delay:0.9s; color:#38B91F;">&lt;/&gt;</span>
        <span class="hero-float hero-float--rocket" style="bottom:8%; left:28%; animation-delay:1.1s; font-size:1.15rem;">🤖</span>
        <span class="hero-ring hero-ring--1"></span>
    </div>

    <div class="w-full flex flex-col z-10 relative">
        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 md:p-8 lg:p-10 items-center w-full px-2 sm:px-8 md:px-10 lg:px-12">
        <!-- Hero copy & CTAs -->
        <div class="lg:col-span-5 max-w-3xl mx-auto lg:mx-0 space-y-3 sm:space-y-5 relative z-10 flex flex-col items-center sm:items-start text-center sm:text-left w-full order-2 lg:order-1">
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-[2.75rem] xl:text-5xl font-extrabold leading-tight tracking-tight hero-title text-center sm:text-left m-0">
                Turn Screen Time <br/>
                <span class="hero-title-accent drop-shadow-sm">Into Skill Time</span>
            </h1>
            <p class="font-bold text-brand-ink/90 text-lg sm:text-xl md:text-2xl mt-2 text-center sm:text-left">
                — We help your child become a creator, not just a consumer.
            </p>

            <p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl text-center sm:text-left mb-4 md:mb-6 text-sm sm:text-base md:text-lg">
                <strong>Learning beyond subjects. Learning for the real world.</strong>
                Personalized K–12 learning that blends Math, Science, Art, Coding, and the worlds children love — turning curiosity into creativity and real-world skills.<br/><br/>
                <span class="font-bold">One child. Many interests. Limitless possibilities.</span>
            </p>

            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-center sm:justify-start gap-2 sm:gap-4 pt-1 sm:pt-2 w-full min-h-[7.5rem] sm:min-h-[3.25rem]">
                <a href="{{ route('courses.index') }}" class="btn-enroll text-base sm:text-lg group w-full sm:w-auto text-center justify-center">
                    Get Started
                    <svg class="w-5 h-5 shrink-0 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>
                </a>
                <a href="#how" class="btn see-how text-base sm:text-lg w-full sm:w-auto text-center justify-center">
                    <span>See How Younimatiq Works</span>
                    <svg class="w-4 h-4 shrink-0 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>
                </a>
            </div>

            <div class="trust-row pt-1 sm:pt-3 flex items-center justify-center sm:justify-start gap-3 mt-1 sm:mt-2 w-full bg-white/65 border border-[#0057bf]/25 px-4 py-2.5 rounded-2xl backdrop-blur-md max-w-max hover:bg-white/80 transition-colors">
                <div class="stars flex items-center text-[14px] tracking-[1px]">
                    <span class="text-[#f59e0b]">★★★★</span>
                    <div class="relative inline-block">
                        <span class="text-white">★</span>
                        <span class="absolute left-0 top-0 text-[#f59e0b] overflow-hidden whitespace-nowrap" style="width: 60%;">★</span>
                    </div>
                </div>
                <div class="rating-num font-[800] text-[15px] text-[#003d86]">4.9* Trustpilot</div>
            </div>
        </div>

        <!-- Studio hero video (right) -->
        <div class="lg:col-span-7 relative z-10 order-1 lg:order-2 flex flex-col items-center lg:items-end w-full">
            <div class="hero-media relative w-full max-w-[420px] sm:max-w-[480px] lg:max-w-none xl:max-w-[760px]">
                <div class="hero-media-frame absolute -inset-2 sm:-inset-3 lg:-inset-4 rounded-[1.75rem] lg:rounded-[2rem] bg-gradient-to-br from-[#7130D8]/20 via-[#FFC400]/15 to-[#087FF5]/20" aria-hidden="true"></div>
                <video
                    id="heroProfileVideo"
                    class="hero-media-video relative z-10 w-full h-auto aspect-video object-cover rounded-[1.5rem] lg:rounded-[1.75rem] shadow-[0_20px_50px_-18px_rgba(113,48,216,0.35)] ring-1 ring-white/80 bg-[#1a1030]"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="auto"
                    aria-label="Younimatiq profile video — kids learning and creating together"
                >
                    <source src="{{ asset('videos/younimatiq-hero-mobile.mp4') }}" media="(max-width: 1023px)" type="video/mp4">
                    <source src="{{ asset('videos/younimatiq-hero.mp4') }}" media="(min-width: 1024px)" type="video/mp4">
                </video>
                <span class="hero-media-badge absolute -bottom-3 left-4 sm:left-6 z-20 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs sm:text-sm font-extrabold text-[#7130D8] shadow-md ring-1 ring-black/5">
                    <span aria-hidden="true">✨</span> Learn · Create · Grow
                </span>
            </div>
            
            <div class="mt-6 sm:mt-8 text-[12px] sm:text-[13px] font-semibold text-on-surface-variant flex items-center justify-center lg:justify-start gap-2 sm:gap-3 flex-wrap w-full max-w-[420px] sm:max-w-[480px] lg:max-w-none xl:max-w-[760px]">
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">how_to_reg</span> Background-checked tutors</span>
                <span class="text-outline hidden sm:inline">&bull;</span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">video_camera_front</span> Every class recorded</span>
                <span class="text-outline hidden md:inline">&bull;</span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">policy</span> COPPA-compliant</span>
            </div>
        </div>
        </div>

        <!-- Class Finder Filter Bar -->
        <div class="w-full px-4 sm:px-8 md:px-12 max-w-[1100px] mx-auto pb-4 sm:pb-8">
            <form action="{{ route('courses.index') }}" method="GET" class="clay-card rounded-[1.5rem] p-4 sm:p-6 w-full flex flex-col md:flex-row items-end gap-6 justify-between">
                
                <!-- Grade -->
                <div class="flex flex-col w-full md:w-[28%]">
                    <label for="hero-filter-grade" class="text-[11px] font-[800] text-[#7130D8] uppercase tracking-wider mb-2">Grade</label>
                    <div class="relative">
                        <select id="hero-filter-grade" name="grade" class="w-full bg-transparent border-b border-outline pb-2 text-[15px] text-on-surface font-medium focus:outline-none focus:border-primary appearance-none cursor-pointer pr-6 rounded-none">
                            <option value="" class="bg-white text-on-surface">All Grades (K-12)</option>
                            @foreach($gradeBands as $value => $band)
                                <option value="{{ $value }}" class="bg-white text-on-surface" @selected(request('grade') === $value)>{{ $band['label'] ?? ('Grades '.$value) }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-on-surface-variant pb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Interest -->
                <div class="flex flex-col w-full md:w-[28%]">
                    <label for="hero-filter-interest" class="text-[11px] font-[800] text-[#7130D8] uppercase tracking-wider mb-2">Interest</label>
                    <div class="relative">
                        <select id="hero-filter-interest" name="categories[]" class="w-full bg-transparent border-b border-outline pb-2 text-[15px] text-on-surface font-medium focus:outline-none focus:border-primary appearance-none cursor-pointer pr-6 rounded-none">
                            <option value="" class="bg-white text-on-surface">Any Interest</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}" class="bg-white text-on-surface" @selected(collect(request('categories', []))->contains($category->slug))>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-on-surface-variant pb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Experience Level -->
                <div class="flex flex-col w-full md:w-[28%]">
                    <label for="hero-filter-level" class="text-[11px] font-[800] text-[#7130D8] uppercase tracking-wider mb-2">Experience Level</label>
                    <div class="relative">
                        <select id="hero-filter-level" name="search" class="w-full bg-transparent border-b border-outline pb-2 text-[15px] text-on-surface font-medium focus:outline-none focus:border-primary appearance-none cursor-pointer pr-6 rounded-none">
                            <option value="" class="bg-white text-on-surface">Any Level</option>
                            <option value="beginner" class="bg-white text-on-surface" @selected(request('search') === 'beginner')>Beginner</option>
                            <option value="intermediate" class="bg-white text-on-surface" @selected(request('search') === 'intermediate')>Intermediate</option>
                            <option value="advanced" class="bg-white text-on-surface" @selected(request('search') === 'advanced')>Advanced</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-on-surface-variant pb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="w-full md:w-auto flex-shrink-0 mt-4 md:mt-0">
                    <button type="submit" class="block w-full text-center bg-[#7130D8] text-white rounded-[1.25rem] px-8 py-3 text-[15px] font-[700] hover:bg-[#5b24b3] transition-colors shadow-md hover:shadow-lg whitespace-nowrap">
                        Find Classes
                    </button>
                </div>

            </form>
        </div>
    </div>
</section>

<!-- Stat Bar Section -->
<section class="py-6 md:py-8 max-w-7xl mx-auto px-4 md:px-8 relative z-10 overflow-x-clip">
    <div class="flex md:grid md:grid-cols-4 gap-3 sm:gap-4 overflow-x-auto snap-x snap-mandatory hide-scrollbar py-2 max-w-full">
        <div class="clay-card p-4 sm:p-6 text-center flex flex-col items-center justify-center space-y-2 flex-none w-[min(60vw,16rem)] md:w-auto snap-start">
            <span class="material-symbols-outlined text-primary text-4xl">workspace_premium</span>
            <div class="font-bold text-on-surface text-sm sm:text-base">Certified Tutors</div>
        </div>
        <div class="clay-card p-4 sm:p-6 text-center flex flex-col items-center justify-center space-y-2 flex-none w-[min(60vw,16rem)] md:w-auto snap-start">
            <span class="material-symbols-outlined text-primary text-4xl">groups</span>
            <div class="font-bold text-on-surface text-sm sm:text-base">1:1</div>
        </div>
        <div class="clay-card p-4 sm:p-6 text-center flex flex-col items-center justify-center space-y-2 flex-none w-[min(60vw,16rem)] md:w-auto snap-start">
            <span class="material-symbols-outlined text-primary text-4xl">alt_route</span>
            <div class="font-bold text-on-surface text-sm sm:text-base">8+ Subject Tracks</div>
        </div>
        <div class="clay-card p-4 sm:p-6 text-center flex flex-col items-center justify-center space-y-2 flex-none w-[min(60vw,16rem)] md:w-auto snap-start">
            <span class="material-symbols-outlined text-primary text-4xl">calendar_month</span>
            <div class="font-bold text-on-surface text-sm sm:text-base">Flexible Scheduling</div>
        </div>
    </div>
</section>

<!-- Programs Designed by Experts / Founder Section -->
<section class="py-12 md:py-14 px-4 md:px-8 max-w-7xl mx-auto" id="experts">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-center">
        <!-- Content (left) -->
        <div class="lg:col-span-8 order-2 lg:order-1 space-y-5 text-center lg:text-left">
            <p class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-primary">
                <span class="material-symbols-outlined text-[16px] leading-none" aria-hidden="true">verified</span>
                World-class curriculum &amp; mentorship
            </p>

            <h2 class="section-heading text-center lg:text-left max-w-2xl mx-auto lg:mx-0">
                Programs designed by experts from
                <span class="accent-green">Harvard, MIT, and University of Glasgow</span>
            </h2>

            <p class="text-on-surface-variant text-base sm:text-[1.05rem] leading-relaxed max-w-2xl mx-auto lg:mx-0">
                Our curriculum and learning frameworks are crafted by graduates and leaders from top institutions — combining academic rigor with hands-on, 1:1 coaching that adapts to every child.
            </p>

            <!-- Institution credentials -->
            <div class="pt-2">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-on-surface-variant mb-3">Academic pedigree</p>
                <div class="flex items-center justify-center lg:justify-start gap-6 sm:gap-8">
                    <img loading="lazy"
                         src="{{ asset('images/logos/harvard.svg') }}"
                         alt="Harvard University"
                         width="110"
                         height="28"
                         class="h-6 sm:h-7 w-auto object-contain opacity-90" />
                    <span class="w-px h-6 bg-surface-container" aria-hidden="true"></span>
                    <x-responsive-img src="images/logos/mit.webp"
                         alt="MIT"
                         class="h-5 sm:h-6 w-auto object-contain opacity-90"
                         width="72"
                         height="24" />
                    <span class="w-px h-6 bg-surface-container" aria-hidden="true"></span>
                    <img loading="lazy"
                         src="{{ asset('images/logos/glasgow.webp') }}"
                         alt="University of Glasgow"
                         width="218"
                         height="41"
                         class="h-6 sm:h-7 w-auto object-contain opacity-90" />
                </div>
            </div>
        </div>

        <!-- Portrait (right) -->
        <div class="lg:col-span-4 order-1 lg:order-2 flex justify-center lg:justify-start">
            <figure class="w-full max-w-[240px] sm:max-w-[260px]">
                <div class="group block relative aspect-square overflow-hidden rounded-2xl bg-surface-container-high ring-1 ring-black/5 shadow-[0_8px_30px_rgba(15,23,42,0.08)]">
                    <x-responsive-img src="images/experts/sharul.webp"
                         alt="Sharul — Founder, MIT"
                         class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]"
                         width="260"
                         height="260"
                         sizes="(max-width: 640px) 240px, 260px"
                         srcset="{{ asset('images/experts/sharul-380.webp') }} 380w, {{ asset('images/experts/sharul-640.webp') }} 640w, {{ asset('images/experts/sharul.webp') }} 1024w" />
                </div>
                <figcaption class="mt-4 text-center">
                    <p class="text-xl font-bold text-brand-ink tracking-tight">Sharul</p>
                    <p class="mt-0.5 text-sm text-on-surface-variant"> MIT</p>

                </figcaption>
            </figure>
        </div>
    </div>
</section>

<!-- Featured Projects Section -->
<section class="py-12 md:py-10 px-4 md:px-8 bg-surface-container-high/20" id="projects">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="section-heading">Consumers to <span class="accent-green">Creators</span></h2>
            <p class="text-on-surface-variant mt-2 text-base md:text-lg font-medium">(Look What Kids Create in Our Live Classes)</p>
        </div>

        @if(isset($featuredProjects) && $featuredProjects->count() > 0)
        <style>
            #projects .marquee-wrapper {
                display: flex;
                overflow: hidden;
                width: 100%;
                max-width: 100%;
                position: relative;
                mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
                -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
            }
            #projects .marquee-track {
                display: flex;
                flex-wrap: nowrap;
                align-items: stretch;
                gap: 1.5rem;
                animation: projects-marquee-slow 55s linear infinite;
                padding: 1rem 0;
                width: max-content;
                will-change: transform;
            }
            #projects .marquee-group {
                display: flex;
                flex-wrap: nowrap;
                gap: 1.5rem;
                flex-shrink: 0;
            }
            #projects .marquee-wrapper:hover .marquee-track,
            #projects .marquee-wrapper:focus-within .marquee-track {
                animation-play-state: paused;
            }
            @keyframes projects-marquee-slow {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            @media (prefers-reduced-motion: reduce) {
                #projects .marquee-track {
                    animation: none;
                    overflow-x: auto;
                    width: 100%;
                    max-width: 100%;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                }
                #projects .marquee-track::-webkit-scrollbar { display: none; }
            }
        </style>
        <div class="marquee-wrapper mt-4">
            <div class="marquee-track">
                @foreach([1, 2] as $loop)
                    <div class="marquee-group" @if($loop === 2) aria-hidden="true" @endif>
                        @foreach($featuredProjects as $project)
                            <div class="clay-card flex flex-col overflow-hidden hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[280px] sm:w-[320px] md:w-[350px] snap-start border border-outline-variant shadow-[0_4px_24px_rgba(0,0,0,0.03)] shrink-0 bg-white mx-3">
                                <div class="w-full pt-[56.25%] relative overflow-hidden bg-black">
                                    @if($project->video_path)
                                        @php 
                                            $ext = strtolower(pathinfo($project->video_path, PATHINFO_EXTENSION)); 
                                            $mediaUrl = Str::startsWith($project->video_path, 'http') ? $project->video_path : Storage::disk('public')->url($project->video_path);
                                        @endphp
                                        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                            <img loading="lazy" src="{{ $mediaUrl }}" width="350" height="197" class="absolute top-0 left-0 w-full h-full object-cover" alt="{{ $project->title }}">
                                        @else
                                            <video
                                                data-lazy-src="{{ $mediaUrl }}"
                                                preload="metadata"
                                                muted
                                                loop
                                                playsinline
                                                class="absolute top-0 left-0 w-full h-full object-contain bg-black"
                                                @if($loop === 2) tabindex="-1" @endif
                                            ></video>
                                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none text-white/40 lazy-video-placeholder">
                                                <span class="material-symbols-outlined text-5xl">play_circle</span>
                                            </div>
                                        @endif
                                    @else
                                        <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center text-white/50"><span class="material-symbols-outlined text-4xl">play_circle</span></div>
                                    @endif
                                </div>
                                <div class="p-5 flex flex-col justify-between flex-1 bg-white">
                                    <div>
                                        <h3 class="text-xl font-bold text-on-surface mb-1">{{ $project->title }}</h3>
                                        <p class="text-primary text-sm font-semibold mb-2">{{ $project->category }}</p>
                                        <p class="text-on-surface-variant text-sm">Created by: <span class="font-bold">{{ $project->author_name }}</span></p>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex items-center gap-4 text-on-surface-variant text-sm">
                                            <div class="flex items-center gap-1" title="Views">
                                                <span class="material-symbols-outlined text-base">visibility</span>
                                                <span>{{ number_format($project->views_count ?? 0) }}</span>
                                            </div>
                                            @php $hasLiked = in_array($project->id, $likedProjectIds ?? [], true); @endphp
                                            <button type="button"
                                                class="like-btn flex items-center gap-1 transition-colors {{ $hasLiked ? 'text-error cursor-default' : 'text-on-surface-variant hover:text-error' }}"
                                                data-project-id="{{ $project->id }}"
                                                data-liked="{{ $hasLiked ? '1' : '0' }}"
                                                onclick="event.preventDefault(); event.stopPropagation(); incrementLike({{ $project->id }}, this);"
                                                @if($hasLiked) disabled aria-pressed="true" @else aria-pressed="false" @endif
                                                @if($loop === 2) tabindex="-1" @endif
                                                title="Like">
                                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">favorite</span>
                                                <span class="like-count">{{ number_format($project->likes_count ?? 0) }}</span>
                                            </button>
                                        </div>
                                        @if($project->project_url)
                                        <a href="{{ $project->project_url }}" target="_blank" class="flex items-center gap-1 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform" @if($loop === 2) tabindex="-1" @endif>
                                            View Project <span class="material-symbols-outlined text-base">arrow_forward</span>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @else
        <p class="text-center text-on-surface-variant">Check back soon for our students' amazing creations!</p>
        @endif
    </div>
</section>


<!-- How It Works Section (Horizontal Process Flow Left-to-Right Desktop, Vertical Timeline Mobile) -->
<section class="py-12 md:py-10 px-4 md:px-8 max-w-7xl mx-auto" id="how">
    <div class="text-center mb-4 md:mb-8">
        <h2 class="section-heading">How It <span class="accent-green">Works</span></h2>
    </div>

    <!-- Process Flow Container -->
    <div class="relative max-w-6xl mx-auto px-4 mt-4 md:mt-6">
        <!-- Removed global glowing line in favor of exact segment lines inside the steps -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:p-10 md:gap-12 lg:gap-16 text-left md:text-center relative z-10">
            <!-- Step 1 -->
            <div class="relative flex flex-row md:flex-col items-start md:items-center text-left md:text-center gap-5 md:gap-4">
                <!-- Vertical line segment down to Step 2 (Mobile) -->
                <div class="block md:hidden absolute left-[28px] -translate-x-1/2 top-[28px] bottom-[-40px] w-[3px] rounded-full z-0 pointer-events-none" 
                     style="background: linear-gradient(180deg, #7130D8 0%, #8B4FF0 100%); box-shadow: 0 0 10px rgba(113, 48, 216, 0.45);"></div>
                <!-- Horizontal line segment to Step 2 (Desktop) -->
                <div class="hidden md:block absolute top-[30px] left-[50%] md:w-[calc(100%+3rem)] lg:w-[calc(100%+4rem)] h-[4px] rounded-full z-0 pointer-events-none" 
                     style="background: linear-gradient(90deg, #7130D8 0%, #8B4FF0 100%); box-shadow: 0 0 16px rgba(113, 48, 216, 0.5);"></div>
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-[#F0EAFF] text-[#7130D8] font-extrabold text-xl md:text-2xl flex items-center justify-center border-4 border-[#7130D8] shadow-[0_8px_20px_rgba(113,48,216,0.3)] shrink-0 transition-transform duration-300 hover:scale-110 relative z-10">1</div>
                <div class="pt-1 md:pt-0 relative z-10">
                    <h3 class="text-xl md:text-2xl font-bold text-brand-ink leading-tight">Get Started</h3>
                    <p class="text-sm md:text-base text-on-surface leading-snug max-w-xs md:max-w-sm mx-auto -mt-0.5 md:-mt-1">Tell us a bit about your child — takes two minutes.</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="relative flex flex-row md:flex-col items-start md:items-center text-left md:text-center gap-5 md:gap-4">
                <!-- Vertical line segment down to Step 3 (Mobile) -->
                <div class="block md:hidden absolute left-[28px] -translate-x-1/2 top-[28px] bottom-[-40px] w-[3px] rounded-full z-0 pointer-events-none" 
                     style="background: linear-gradient(180deg, #8B4FF0 0%, #087FF5 100%); box-shadow: 0 0 10px rgba(139, 79, 240, 0.45);"></div>
                <!-- Horizontal line segment to Step 3 (Desktop) -->
                <div class="hidden md:block absolute top-[30px] left-[50%] md:w-[calc(100%+3rem)] lg:w-[calc(100%+4rem)] h-[4px] rounded-full z-0 pointer-events-none" 
                     style="background: linear-gradient(90deg, #8B4FF0 0%, #087FF5 100%); box-shadow: 0 0 16px rgba(139, 79, 240, 0.5);"></div>
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-[#F0EAFF] text-[#7130D8] font-extrabold text-xl md:text-2xl flex items-center justify-center border-4 border-[#7130D8] shadow-[0_8px_20px_rgba(113,48,216,0.3)] shrink-0 transition-transform duration-300 hover:scale-110 relative z-10">2</div>
                <div class="pt-1 md:pt-0 relative z-10">
                    <h3 class="text-xl md:text-2xl font-bold text-brand-ink leading-tight">Get Matched to the Right Tutor &amp; Track</h3>
                    <p class="text-sm md:text-base text-on-surface leading-snug max-w-xs md:max-w-sm mx-auto -mt-0.5 md:-mt-1">We pair your child with a tutor suited to their pace and goals.</p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="relative flex flex-row md:flex-col items-start md:items-center text-left md:text-center gap-5 md:gap-4">
                <!-- Step 3 has NO line segment below it so the line ends cleanly at circle 3 -->
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-[#F0EAFF] text-[#7130D8] font-extrabold text-xl md:text-2xl flex items-center justify-center border-4 border-[#7130D8] shadow-[0_8px_20px_rgba(113,48,216,0.3)] shrink-0 transition-transform duration-300 hover:scale-110 relative z-10">3</div>
                <div class="pt-1 md:pt-0 relative z-10">
                    <h3 class="text-xl md:text-2xl font-bold text-brand-ink leading-tight">Track Real Progress Every Week</h3>
                    <p class="text-sm md:text-base text-on-surface leading-snug max-w-xs md:max-w-sm mx-auto -mt-0.5 md:-mt-1">Follow milestones and projects from your child's dashboard.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-8 md:mt-12">
        <a href="{{ route('courses.index') }}" class="btn-enroll text-lg group inline-flex items-center gap-2">
            Get Started
            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
        </a>
    </div>
</section>

<!-- 8+ Subject Tracks Section (One Platform, Eight Paths Forward) -->
<section class="pt-4 pb-16 px-4 md:px-8 max-w-7xl mx-auto" id="courses">
    <div class="text-center mb-10">
        <h2 class="section-heading">Personalized K–12 Coaching, <span class="accent-green">Blended by Grade &amp; Interest</span></h2>
        <p class="text-on-surface-variant mt-2 text-base md:text-lg max-w-3xl mx-auto">Pick a starting point — or blend a few. Math meets Art. Robotics meets storytelling. Roblox meets Math. Every path is live, hands-on, and personalized to your child's pace.</p>
    </div>

    <!-- Tech & Innovation Group -->
    <div class="mb-8" id="tech-innovation">
        <div class="flex items-center gap-2 mb-6 text-primary font-bold uppercase tracking-wider text-sm">
            <span class="w-3 h-3 rounded-full bg-primary inline-block"></span>
            Tech &amp; Innovation
        </div>
        <style>
            #tech-innovation .tech-marquee-wrapper {
                display: flex;
                overflow: hidden;
                width: 100%;
                mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
            }
            #tech-innovation .tech-marquee-track {
                display: flex;
                flex-wrap: nowrap;
                align-items: stretch;
                gap: 1.5rem;
                width: max-content;
                will-change: transform;
                padding: 1rem 0;
                animation: tech-marquee-scroll 45s linear infinite;
            }
            #tech-innovation .tech-marquee-group {
                display: flex;
                flex-wrap: nowrap;
                gap: 1.5rem;
                flex-shrink: 0;
            }
            #tech-innovation .tech-marquee-wrapper:hover .tech-marquee-track,
            #tech-innovation .tech-marquee-wrapper:focus-within .tech-marquee-track {
                animation-play-state: paused;
            }
            @keyframes tech-marquee-scroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            @media (prefers-reduced-motion: reduce) {
                #tech-innovation .tech-marquee-track {
                    animation: none !important;
                    overflow-x: auto;
                    width: 100%;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                }
                #tech-innovation .tech-marquee-track::-webkit-scrollbar { display: none; }
            }
        </style>
        <div class="tech-marquee-wrapper">
            <div class="tech-marquee-track">
                @foreach([1, 2] as $techLoop)
                <div class="tech-marquee-group" @if($techLoop === 2) aria-hidden="true" @endif>
                    <!-- Coding -->
                    <a href="{{ route('courses.index', ['categories' => ['coding']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-[300px] shrink-0" @if($techLoop === 2) tabindex="-1" @endif>
                        <div class="w-full aspect-video relative overflow-hidden">
                            <x-responsive-img src="images/coding_course_card.webp" alt="Coding Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                        </div>
                        <div class="p-5 flex flex-col justify-between flex-1">
                            <div>
                                <h3 class="text-xl font-bold text-on-surface mb-1">Build & Launch Your Own App</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">Real code, real projects, no copy-paste.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                                Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                    <!-- Game Design -->
                    <a href="{{ route('courses.index', ['categories' => ['game-design']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-[300px] shrink-0" @if($techLoop === 2) tabindex="-1" @endif>
                        <div class="w-full aspect-video relative overflow-hidden">
                            <x-responsive-img src="images/image_copy-800.webp" alt="Game Design Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                        </div>
                        <div class="p-5 flex flex-col justify-between flex-1">
                            <div>
                                <h3 class="text-xl font-bold text-on-surface mb-1">Create & Publish Your Own Game</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">From player to creator — build games kids actually play.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                                Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                    <!-- Robotics -->
                    <a href="{{ route('courses.index', ['categories' => ['robotics-camp']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-[300px] shrink-0" @if($techLoop === 2) tabindex="-1" @endif>
                        <div class="w-full aspect-video relative overflow-hidden">
                            <x-responsive-img src="images/robotics_course_card.webp" alt="Robotics Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                        </div>
                        <div class="p-5 flex flex-col justify-between flex-1">
                            <div>
                                <h3 class="text-xl font-bold text-on-surface mb-1">AI Powered Robotics</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">From circuits to real robots that move and respond.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                                Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                    <!-- AI Mastery -->
                    <a href="{{ route('courses.index', ['categories' => ['ai-mastery']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-[300px] shrink-0" @if($techLoop === 2) tabindex="-1" @endif>
                        <div class="w-full aspect-video relative overflow-hidden">
                            <x-responsive-img src="images/aimastery_course_card.webp" alt="AI Mastery Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                        </div>
                        <div class="p-5 flex flex-col justify-between flex-1">
                            <div>
                                <h3 class="text-xl font-bold text-on-surface mb-1">AI Mastery</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">Future-ready skills, taught early.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                                Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                    <!-- Minecraft Reimagined -->
                    <a href="{{ route('courses.index', ['categories' => ['minecraft-reimagined']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-[300px] shrink-0" @if($techLoop === 2) tabindex="-1" @endif>
                        <div class="w-full aspect-video relative overflow-hidden">
                            <x-responsive-img src="images/gamedesign_course_card.webp" alt="Minecraft Reimagined Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                        </div>
                        <div class="p-5 flex flex-col justify-between flex-1">
                            <div>
                                <h3 class="text-xl font-bold text-on-surface mb-1">Minecraft Reimagined</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">Build, code, and explore new worlds.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                                Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                            </div>
                        </div>
                    </a>

                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Academics & Core Group -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-6 text-secondary font-bold uppercase tracking-wider text-sm">
            <span class="w-3 h-3 rounded-full bg-secondary inline-block"></span>
            Academics &amp; Core
        </div>
        <div class="flex md:grid md:grid-cols-2 lg:grid-cols-4 overflow-x-auto lg:overflow-x-visible gap-6 py-16 -my-16 snap-x snap-mandatory hide-scrollbar px-4 -mx-4 md:mx-0" style="scrollbar-width: none;">
            <!-- Math -->
            <a href="{{ route('courses.index', ['categories' => ['math']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-auto snap-start shrink-0">
                <div class="w-full aspect-video relative overflow-hidden">
                    <x-responsive-img src="images/math_course_card.webp" alt="Math Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                </div>
                <div class="p-5 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="text-xl font-bold text-on-surface mb-1">See Math Everywhere</h3>
                        <p class="text-on-surface-variant text-sm leading-relaxed">Solving real-world problems, hands-on..</p>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                        Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </div>
                </div>
            </a>
            <!-- English -->
            <a href="{{ route('courses.index', ['categories' => ['english']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-auto snap-start shrink-0">
                <div class="w-full aspect-video relative overflow-hidden">
                    <x-responsive-img src="images/english_course_card.webp" alt="English Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                </div>
                <div class="p-5 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="text-xl font-bold text-on-surface mb-1">English (ELA)</h3>
                        <p class="text-on-surface-variant text-sm leading-relaxed">Read, write, and speak with real confidence.</p>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                        Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </div>
                </div>
            </a>
            <!-- Science -->
            <a href="{{ route('courses.index', ['categories' => ['science']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-auto snap-start shrink-0">
                <div class="w-full aspect-video relative overflow-hidden">
                    <x-responsive-img src="images/science_course_card.webp" alt="Science Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                </div>
                <div class="p-5 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="text-xl font-bold text-on-surface mb-1">Science Explorers</h3>
                        <p class="text-on-surface-variant text-sm leading-relaxed">Curiosity turned into scientific thinking.</p>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                        Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </div>
                </div>
            </a>
            <!-- Arts & Animation -->
            <a href="{{ route('courses.index', ['categories' => ['arts-animation-camp']]) }}" class="clay-card overflow-hidden flex flex-col justify-between hover:scale-105 transition-all duration-300 group !p-0 flex-none w-[65vw] sm:w-[280px] md:w-auto snap-start shrink-0">
                <div class="w-full aspect-video relative overflow-hidden">
                    <x-responsive-img src="images/arts_course_card.webp" alt="Arts &amp; Animation Course" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="552" height="310" sizes="(max-width: 640px) 65vw, (max-width: 1024px) 300px, 276px" />
                </div>
                <div class="p-5 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="text-xl font-bold text-on-surface mb-1">Arts &amp; Animation</h3>
                        <p class="text-on-surface-variant text-sm leading-relaxed">From sketch to story to screen.</p>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-1 transition-transform">
                        Explore <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Our Story Section ("Started by a Mom Who Wanted Better") -->
<section class="py-12 px-4 md:px-8 max-w-7xl mx-auto" id="story">
    <div class="clay-card p-5 md:p-8 lg:p-14 bg-gradient-to-br from-primary/10 via-white to-primary relative overflow-hidden rounded-3xl shadow-lg w-full flex flex-col md:flex-row gap-8 items-center">
        <div class="space-y-6 w-full z-10">
            <div class="flex items-center gap-3 text-primary font-bold uppercase tracking-wider text-sm">
                <span class="material-symbols-outlined text-primary" aria-hidden="true">favorite</span>
                Our Story
            </div>
            <h2 class="section-heading mb-4 max-w-none">Every Parent Wants Their Child to Succeed. The Rules Just Changed.</h2>
            <div class="space-y-4 font-body-lg text-body-lg text-on-surface-variant leading-relaxed w-full">
                <p>
                    For a generation, success meant knowing the right answers. Today it means knowing how to think — how to question, adapt, and create in a world where AI can already do the recall for you. Younimatiq began with that shift in mind: not another platform teaching kids what to memorize, but one built to teach them how to think critically, understand AI, and create boldly.
                </p>
                <p>
                    So we set out to build the "Future Fit platform” every parent wishes existed for this moment — a single platform spanning AI Robotics, Math, English, Science, and Quantum Computing, anchored by real AI Mastery, with real 1:1 attention from tutors who teach for understanding, not just answers. Because the subjects that mattered yesterday aren't enough to be future fit tomorrow. It's not just what kids know — it's how they think, build, and adapt. That platform became Younimatiq.
                </p>
            </div>
        </div>
        
        {{-- Hiding Nairrita's info for now
        <div class="w-full md:w-1/3 flex justify-center md:justify-end z-10">
            <figure class="w-full max-w-[240px] sm:max-w-[260px]">
                <div class="group block relative aspect-square overflow-hidden rounded-2xl bg-surface-container-high ring-1 ring-black/5 shadow-[0_8px_30px_rgba(15,23,42,0.08)]">
                    <img src="{{ asset('images/nairrita-moha.webp') }}"
                         alt="Nairrita — Co-Founder"
                         class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]"
                         width="260"
                         height="260" />
                </div>
                <figcaption class="mt-4 text-center">
                    <p class="text-xl font-bold text-brand-ink tracking-tight">Nairrita</p>
                    <p class="mt-0.5 text-sm text-on-surface-variant"> Co-Founder</p>

                </figcaption>
            </figure>
        </div>
        --}}
    </div>
</section>

<!-- Why Younimatiq Section ("Not Tutoring. Not Screen Time. Real Progress.") -->
<section class="py-12 px-4 md:px-8 bg-surface-container-high/40">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
            <h2 class="section-heading">Not Tutoring. Not Screen Time. <br/><span class="accent-green">Real Progress.</span></h2>
        </div>

        <div class="flex md:grid md:grid-cols-2 lg:grid-cols-4 gap-5 overflow-x-auto snap-x snap-mandatory py-16 -my-16 hide-scrollbar px-4 -mx-4 md:mx-0">
            <div class="clay-card p-5 md:p-8 space-y-4 flex-none w-[65vw] md:w-auto snap-start">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">person_check</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface">1:1 Personalized Coaching</h3>
                <p class="text-on-surface-variant text-base leading-relaxed">
                    Never a one-size-fits-all class — every session adapts to your child.
                </p>
            </div>

            <div class="clay-card p-5 md:p-8 space-y-4 flex-none w-[65vw] md:w-auto snap-start">
                <div class="w-14 h-14 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">folder_open</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface">Real Projects &amp; Portfolios</h3>
                <p class="text-on-surface-variant text-base leading-relaxed">
                    Kids build things they're proud of — not just worksheets.
                </p>
            </div>

            <div class="clay-card p-5 md:p-8 space-y-4 flex-none w-[65vw] md:w-auto snap-start">
                <div class="w-14 h-14 rounded-2xl bg-warning/10 text-warning flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">fact_check</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface">Common Core-Aligned</h3>
                <p class="text-on-surface-variant text-base leading-relaxed">
                    Built to fit what your child is already learning in school.
                </p>
            </div>

            <div class="clay-card p-5 md:p-8 space-y-4 flex-none w-[65vw] md:w-auto snap-start">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">trending_up</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface">Progress You Can See</h3>
                <p class="text-on-surface-variant text-base leading-relaxed">
                    A personalized dashboard with real, weekly updates — no guessing.
                </p>
            </div>
        </div>
    </div>
</section>


<!-- Testimonials / Reviews Section (Auto-scrolls slowly 65s, pauses on hover) -->
<section class="py-12 md:py-16 bg-surface-container-high/30 overflow-hidden" id="reviews">
    <div class="max-w-7xl mx-auto px-4 md:px-8 mb-10 text-center">
        <h2 class="section-heading">Loved by <span class="accent-green">Families Everywhere</span></h2>
        <p class="text-on-surface-variant mt-2 text-base md:text-lg">Real reviews from parents around the world</p>
    </div>

    <style>
        .marquee-wrapper {
            display: flex;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            position: relative;
            mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
        }
        .marquee-track {
            display: flex;
            gap: 1.5rem;
            animation: marquee-slow 60s linear infinite;
            padding: 1rem 0;
            width: max-content;
            will-change: transform;
        }
        .marquee-group {
            display: flex;
            gap: 1.5rem;
            flex-shrink: 0;
        }
        .marquee-wrapper:hover .marquee-track {
            animation-play-state: paused;
        }
        @keyframes marquee-slow {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-25% - 0.375rem)); }
        }
        .review-card {
            width: 85vw;
            max-width: 360px;
            flex-shrink: 0;
            background: #ffffff;
            border-radius: 1.25rem;
            padding: 1.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 61, 134, 0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 61, 134, 0.12);
        }
    </style>

    <div class="marquee-wrapper">
        <div class="marquee-track">
            <!-- Group 1 -->
            <div class="marquee-group">
                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">JR</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Jenna R.</div>
                            <div class="text-xs text-on-surface-variant">Math · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"She built her first working app in eight weeks. I didn't expect that so soon — she's genuinely proud of it."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">MK</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Marcus K.</div>
                            <div class="text-xs text-on-surface-variant">Coding · Grade 8</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">PS</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Priya S.</div>
                            <div class="text-xs text-on-surface-variant">AI Mastery · Grade 7</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">DL</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">David L.</div>
                            <div class="text-xs text-on-surface-variant">Robotics · Grade 6</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">ST</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Sarah T.</div>
                            <div class="text-xs text-on-surface-variant">English · Grade 4</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Fascinating science experiments every week! My 8-year-old is constantly asking questions about how things work."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">AH</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Ahmed H.</div>
                            <div class="text-xs text-on-surface-variant">Science · Grade 3</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"From drawing paper sketches to animating digital characters — the progress in just 2 months is mind-blowing!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">EM</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Elena M.</div>
                            <div class="text-xs text-on-surface-variant">Arts &amp; Animation · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son stopped just playing video games and started building his own levels. It transformed screen time completely!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">RC</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Robert C.</div>
                            <div class="text-xs text-on-surface-variant">Game Design · Grade 7</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group 2 -->
            <div class="marquee-group" aria-hidden="true">
                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">JR</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Jenna R.</div>
                            <div class="text-xs text-on-surface-variant">Math · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"She built her first working app in eight weeks. I didn't expect that so soon — she's genuinely proud of it."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">MK</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Marcus K.</div>
                            <div class="text-xs text-on-surface-variant">Coding · Grade 8</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">PS</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Priya S.</div>
                            <div class="text-xs text-on-surface-variant">AI Mastery · Grade 7</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">DL</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">David L.</div>
                            <div class="text-xs text-on-surface-variant">Robotics · Grade 6</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">ST</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Sarah T.</div>
                            <div class="text-xs text-on-surface-variant">English · Grade 4</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Fascinating science experiments every week! My 8-year-old is constantly asking questions about how things work."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">AH</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Ahmed H.</div>
                            <div class="text-xs text-on-surface-variant">Science · Grade 3</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"From drawing paper sketches to animating digital characters — the progress in just 2 months is mind-blowing!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">EM</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Elena M.</div>
                            <div class="text-xs text-on-surface-variant">Arts &amp; Animation · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son stopped just playing video games and started building his own levels. It transformed screen time completely!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">RC</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Robert C.</div>
                            <div class="text-xs text-on-surface-variant">Game Design · Grade 7</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group 3 -->
            <div class="marquee-group" aria-hidden="true">
                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">JR</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Jenna R.</div>
                            <div class="text-xs text-on-surface-variant">Math · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"She built her first working app in eight weeks. I didn't expect that so soon — she's genuinely proud of it."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">MK</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Marcus K.</div>
                            <div class="text-xs text-on-surface-variant">Coding · Grade 8</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">PS</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Priya S.</div>
                            <div class="text-xs text-on-surface-variant">AI Mastery · Grade 7</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">DL</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">David L.</div>
                            <div class="text-xs text-on-surface-variant">Robotics · Grade 6</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">ST</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Sarah T.</div>
                            <div class="text-xs text-on-surface-variant">English · Grade 4</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Fascinating science experiments every week! My 8-year-old is constantly asking questions about how things work."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">AH</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Ahmed H.</div>
                            <div class="text-xs text-on-surface-variant">Science · Grade 3</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"From drawing paper sketches to animating digital characters — the progress in just 2 months is mind-blowing!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">EM</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Elena M.</div>
                            <div class="text-xs text-on-surface-variant">Arts &amp; Animation · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son stopped just playing video games and started building his own levels. It transformed screen time completely!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">RC</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Robert C.</div>
                            <div class="text-xs text-on-surface-variant">Game Design · Grade 7</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group 4 -->
            <div class="marquee-group" aria-hidden="true">
                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">JR</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Jenna R.</div>
                            <div class="text-xs text-on-surface-variant">Math · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"She built her first working app in eight weeks. I didn't expect that so soon — she's genuinely proud of it."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">MK</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Marcus K.</div>
                            <div class="text-xs text-on-surface-variant">Coding · Grade 8</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">PS</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Priya S.</div>
                            <div class="text-xs text-on-surface-variant">AI Mastery · Grade 7</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">DL</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">David L.</div>
                            <div class="text-xs text-on-surface-variant">Robotics · Grade 6</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">ST</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Sarah T.</div>
                            <div class="text-xs text-on-surface-variant">English · Grade 4</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"Fascinating science experiments every week! My 8-year-old is constantly asking questions about how things work."</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">AH</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Ahmed H.</div>
                            <div class="text-xs text-on-surface-variant">Science · Grade 3</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"From drawing paper sketches to animating digital characters — the progress in just 2 months is mind-blowing!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">EM</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Elena M.</div>
                            <div class="text-xs text-on-surface-variant">Arts &amp; Animation · Grade 5</div>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div>
                        <div class="ym-stars text-base mb-3 tracking-wide" style="color:#FFC400;" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="text-on-surface text-sm leading-relaxed italic mb-6">"My son stopped just playing video games and started building his own levels. It transformed screen time completely!"</p>
                    </div>
                    <div class="flex items-center gap-3 pt-3 border-t border-outline-variant">
                        <div class="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">RC</div>
                        <div>
                            <div class="font-bold text-brand-ink text-sm">Robert C.</div>
                            <div class="text-xs text-on-surface-variant">Game Design · Grade 7</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AI Spotlight Section -->
<section class="py-0 px-0 max-w-7xl mx-auto my-6">
    <div class="p-0 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-0 rounded-3xl shadow-lg" style="background: #EFDECE; border: 1px solid #E4D0BD;">
        <div class="space-y-4 max-w-xl z-10 p-5 md:p-8 lg:p-6 md:p-12">
            <div class="inline-flex items-center gap-2 bg-warning text-white text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wide shadow-sm">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">auto_awesome</span> New Track
            </div>
            <h3 class="text-2xl md:text-4xl font-extrabold text-brand-ink leading-tight">Give Your Child an Edge: AI Mastery for K–12</h3>
            <p class="text-on-surface text-base md:text-lg leading-relaxed">
                Learn to think with AI, build with it, and use it responsibly — skills every future workplace will expect.
            </p>
            <div class="pt-2">
                <a href="{{ route('courses.index', ['categories' => ['ai-mastery']]) }}" class="btn-primary text-base md:text-lg inline-flex items-center gap-2 px-7 py-3 rounded-full font-bold shadow-md hover:scale-105 transition-transform">
                    <span>Learn About AI Mastery</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
        <div class="w-full md:w-1/2 flex justify-end items-center z-10 p-0 m-0 hidden md:flex">
            <x-responsive-img src="images/ai-mastery.webp" alt="Give Your Child an Edge: AI Mastery for K–12" class="w-full h-full max-h-[400px] object-cover object-right block" />
        </div>
    </div>
</section>

<!-- Featured Short Courses (Static) -->
<section class="py-10 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="text-center mb-8 scroll-anim">
        <h3 class="text-2xl font-bold">Top Courses, <span class="text-primary">Loved by Parents and Learners</span></h3>
        <p class="text-on-surface-variant mt-2 text-lg font-medium">Our most-loved courses, picked by families like yours</p>
    </div>
    <div class="flex md:grid md:grid-cols-3 gap-5 scroll-anim overflow-x-auto snap-x snap-mandatory hide-scrollbar py-16 -my-16 px-4 -mx-4 md:mx-0" data-delay="200">
        
        @foreach($topCourses as $course)
            <x-course-card :course="$course" badge="🏆 BEST SELLER" class="flex-none w-[65vw] md:w-[320px] lg:w-auto snap-start shadow-sm border border-outline-variant rounded-2xl relative" />
        @endforeach

    </div>
</section>



<!-- Start Your Learning Journey Today CTA -->
<section class="py-10 px-4 md:px-8 max-w-7xl mx-auto" id="trial">
    <div class="brand-panel px-12 py-6 md:px-24 md:py-12 text-center relative overflow-hidden">
        <div class="relative z-10">
            <div class="hero-badge mb-6 inline-flex">
                <span class="material-symbols-outlined text-[20px] text-white" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
                Get Started
            </div>
            <h2 class="font-headline-xl text-headline-xl mb-6 hero-title">Ready to See What <br class="hidden md:block"/> <span class="hero-title-accent drop-shadow-sm">Your Child Can Do?</span></h2>
            <p class="font-body-lg text-body-lg mb-10 text-white/85 max-w-2xl mx-auto leading-relaxed px-4 md:px-8">
                No commitment. Your first class is free. Give your child the opportunity to explore the future through 1:1 personalized coaching.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('courses.index') }}" class="btn-enroll text-lg group w-full sm:w-auto">
                    Get Started Free
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
                <a href="https://wa.me/12027944843" target="_blank" rel="noopener noreferrer" class="btn-primary text-lg w-full sm:w-auto">
                    <span class="material-symbols-outlined">chat</span>
                    Whatsapp Us
                </a>
            </div>
        </div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-white/30 rounded-full blur-3xl"></div>
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-secondary-container/30 rounded-full blur-3xl"></div>
    </div>
</section>



<!-- Frequently Asked Questions ("We Love Solving Doubts") -->
<section class="py-16 bg-surface-container-low/50" id="faq">
    <div class="max-w-3xl mx-auto px-4 md:px-8" x-data="{ expanded: false }">
        <div class="text-center mb-10">
            <h2 class="section-heading">We Love <span class="accent-green">Solving Doubts</span></h2>
            <p class="text-on-surface-variant mt-2 text-lg">Everything you need to know about Younimatiq</p>
        </div>
        
        @php
            // Filter out Pricing FAQs for the general welcome page if desired, or keep all
            $displayFaqs = $faqs->whereNotIn('category', ['Pricing'])->values();
        @endphp

        <div class="space-y-4">
            @foreach($displayFaqs as $index => $faq)
            <details class="group clay-card bg-white rounded-2xl overflow-hidden" 
                     {{ $index === 0 ? 'open' : '' }} 
                     @if($index >= 5) x-show="expanded" style="display: none;" x-transition @endif>
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
        
        @if($displayFaqs->count() > 5)
        <div class="text-center mt-10">
            <button @click="expanded = !expanded" class="link-primary focus:outline-none text-primary font-bold">
                <span x-text="expanded ? 'Show Less' : 'Show More'"></span>
            </button>
        </div>
        @endif
        
        <div class="text-center mt-4">
            <a href="{{ route('faq') }}" class="text-sm text-on-surface-variant hover:text-primary transition-colors">View all FAQs page</a>
        </div>
    </div>
</section>

<!-- Scroll Popup Banner -->
<div x-data="{ 
        showPopup: false,
        init() {
            if (!sessionStorage.getItem('bannerTriggered')) {
                const handler = () => {
                    if (window.scrollY > 10) {
                        this.showPopup = true;
                        sessionStorage.setItem('bannerTriggered', 'true');
                        window.removeEventListener('scroll', handler);
                    }
                };
                window.addEventListener('scroll', handler, { passive: true });
            }
        }
     }"
     x-show="showPopup"
     x-transition.opacity.duration.500ms
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm"
     style="display: none;"
>
    <div class="relative bg-white rounded-[2rem] p-5 md:p-8 lg:p-6 md:p-12 max-w-lg w-full shadow-2xl text-center border-t-8 border-primary" @click.away="showPopup = false">
        <button class="absolute top-4 right-4 p-2 bg-surface-container-low hover:bg-surface-container-high rounded-full text-on-surface-variant transition-colors" @click="showPopup = false" aria-label="Close popup">
            <span class="material-symbols-outlined">close</span>
        </button>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary/10 text-primary mb-6">
            <span class="material-symbols-outlined text-4xl">local_activity</span>
        </div>
        @php $demoPrice = (float) (\App\Models\Setting::where('key', 'demo_class_price')->value('value') ?? 0); @endphp
        <h3 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-2">Book Your Free Trial Class</h3>
        <p class="text-on-surface-variant font-body-lg mb-8">
            Experience Younimatiq's 1:1 interactive learning environment 
            @if($demoPrice <= 0)
                <span class="font-bold text-primary">completely FREE</span> today!
            @else
                for just <span class="font-bold text-primary">@price($demoPrice)</span>!
            @endif
        </p>
        <a href="#leadModal" onclick="openLeadModal(event)" class="btn-enroll btn-lg w-full flex items-center justify-center gap-2">
            Book Now <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </a>
        <button @click="showPopup = false" class="mt-4 text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            Maybe Later
        </button>
    </div>
</div>

@include('partials.community-likes')

@endsection
