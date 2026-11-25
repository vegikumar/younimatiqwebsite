@props([
    'course',
    'badge' => null,
    'showDemo' => false,
    'hideButtons' => false,
    'hideBookFreeClass' => false,
])

<div {{ $attributes->merge(['class' => 'clay-card overflow-hidden group bg-white flex flex-col h-full']) }} 
    @mouseenter="isHovered = true; isPlaying = false"
    @mouseleave="isHovered = false; isPlaying = isOnScreen"
    x-data="{ 
    isOnScreen: false,
    isHovered: false,
    isPlaying: false, 
    loaded: false, 
    hasVideo: {{ $course->video_url ? 'true' : 'false' }},
    init() { 
        if (!this.hasVideo) return;
        this.$watch('isPlaying', value => { 
            if(value) this.loaded = true; 
        }); 
        
        let observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                this.isOnScreen = entry.isIntersecting;
                if (!this.isHovered) {
                    this.isPlaying = this.isOnScreen;
                }
            });
        }, { threshold: 0.1 });
        observer.observe(this.$el);
    }
}">
    <a href="{{ route('courses.show', $course) }}" class="relative block bg-surface-container-low aspect-square flex items-center justify-center overflow-hidden">
        @if($course->video_url)
            <template x-if="loaded">
                <div x-show="isPlaying" x-transition.opacity.duration.300ms class="absolute inset-0 w-full h-full z-10 pointer-events-none overflow-hidden flex items-center justify-center bg-black">
                    @if(str_contains($course->video_url, 'youtube.com') || str_contains($course->video_url, 'youtu.be'))
                        @php
                            $videoId = '';
                            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $course->video_url, $match)) {
                                $videoId = $match[1];
                            }
                        @endphp
                        @if($videoId)
                            <iframe src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&mute=1&controls=0&loop=1&playlist={{ $videoId }}&playsinline=1" class="w-full h-full border-0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                        @endif
                    @else
                        <video x-init="
                                if (typeof Hls !== 'undefined' && Hls.isSupported() && '{{ $course->video_url }}'.includes('.m3u8')) {
                                    const hls = new Hls();
                                    hls.loadSource('{{ $course->video_url }}');
                                    hls.attachMedia($el);
                                } else {
                                    $el.src = '{{ $course->video_url }}';
                                }
                                $watch('isPlaying', value => {
                                    if (value) {
                                        setTimeout(() => { $el.play().catch(e => console.error(e)); }, 50);
                                    } else {
                                        $el.pause();
                                    }
                                });
                                if (isPlaying) {
                                    setTimeout(() => { $el.play().catch(e => console.error(e)); }, 50);
                                }
                            " class="w-full h-full object-contain bg-black" muted autoplay loop playsinline oncontextmenu="return false;" controlsList="nodownload"></video>
                    @endif
                </div>
            </template>
        @endif

        <div x-show="!isPlaying" class="w-full h-full relative z-0">
            @if($course->thumbnail_url)
                <img
                    src="{{ $course->thumbnail_url }}"
                    alt="{{ $course->title }}"
                    class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
                    loading="lazy"
                    width="512"
                    height="512"
                >
            @else
                <div class="w-full h-full bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-5xl">menu_book</span>
                </div>
            @endif
        </div>

        <div class="absolute top-3 left-3 z-20 flex flex-col gap-2 items-start">
            @if($badge)
                <span class="bg-secondary-container text-on-secondary-container text-xs font-bold px-3 py-1 rounded-full shadow-sm">{{ $badge }}</span>
            @endif
            @if($course->self_paced_enabled)
                <span class="bg-tertiary-container text-on-tertiary-container text-xs font-bold px-3 py-1 rounded-full shadow-sm">Self-Paced Available</span>
            @endif
        </div>
    </a>

    <a href="{{ route('courses.show', $course) }}" class="p-5 flex-1 flex flex-col block cursor-pointer group">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            @if($course->course_type)
                <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full uppercase">
                    {{ $course->course_type === 'live' ? 'Live Class' : 'Self-Paced' }}
                </span>
            @endif
            @if($course->categories)
                @foreach($course->categories as $category)
                    <span class="px-2 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">{{ $category->name }}</span>
                @endforeach
            @endif
        </div>

        <h3 class="font-headline-md font-bold mb-2 text-on-surface group-hover:text-primary transition-colors line-clamp-2">{{ $course->title }}</h3>
        <p class="text-on-surface-variant text-sm flex-1 line-clamp-3">{{ strip_tags($course->description) }}</p>
    </a>

    @if(!$hideButtons)
        <div class="px-5 pb-5">
            @if(!$hideBookFreeClass)
            <a href="#leadModal" data-category-id="{{ $course->categories->first()->id ?? '' }}" data-course-title="{{ $course->title }}" onclick="openLeadModal(event, 'universal', this.getAttribute('data-category-id'), this.getAttribute('data-course-title'))" class="w-full py-2.5 mb-3 text-[15px] font-bold text-center border-[2.5px] border-[#7e22ce] text-[#7e22ce] bg-white rounded-full shadow-[0_4px_0_#581c87] active:translate-y-[4px] active:shadow-none transition-all flex items-center justify-center hover:bg-primary-fixed">
                <span>Book my free class</span>
            </a>
            @endif
            <a href="{{ route('courses.show', $course) }}" class="w-full py-2.5 text-[15px] font-bold text-center bg-[#7e22ce] text-white rounded-full shadow-[0_4px_0_#581c87] active:translate-y-[4px] active:shadow-none transition-all flex items-center justify-center hover:bg-[#6b21a8]">
                <span>Enroll now</span>
            </a>
            @php
                $cardCurrency = strtoupper(session('display_currency', 'USD'));
                if ($cardCurrency === 'AED') {
                    $cardSymbol = 'د.إ';
                    $cardPrice = floatval($course->price_aed ?? 30);
                } elseif ($cardCurrency === 'INR') {
                    $cardSymbol = '₹';
                    $cardPrice = floatval($course->price_inr ?? 600);
                } else {
                    $cardSymbol = '$';
                    $cardPrice = floatval($course->price_usd ?? 18);
                }
            @endphp
            <div class="text-right mt-2 flex justify-end items-baseline gap-1">
                <span class="font-normal text-on-surface-variant text-xs">{{ $cardSymbol }}</span>
                <span class="font-extrabold text-on-surface text-sm">{{ $cardPrice }}</span>
                <span class="font-normal text-on-surface-variant text-xs">/ class</span>
            </div>
        </div>
    @endif
</div>
