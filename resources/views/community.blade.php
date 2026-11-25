@extends('layouts.app')
@section('seo_title', 'Students Projects')
@section('seo_description', 'Explore innovative student projects in app development, game design, and robotics. Discover hands-on tech solutions, source code, and creative student builds on Younimatiq.')

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Younimatiq Community">
    <h1 class="brand-title">Our <span class="text-brand-accent">Community</span></h1>
</x-brand-hero>

<div class="pb-20 px-4 max-w-6xl mx-auto space-y-24 select-none" data-clarity-mask="true">
    
    <!-- Featured Projects Section -->
    <section>
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-on-surface mb-4">Featured Student Projects</h2>
            <p class="text-on-surface max-w-2xl mx-auto">Discover the amazing games, apps, and robots built by Younimatiq students.</p>
        </div>

        @if($featuredProjects->count() > 0)
            @php
                $projectRows = $featuredProjects->values()->chunk(max(1, (int) ceil($featuredProjects->count() / 2)));
                if ($projectRows->count() === 1) {
                    $projectRows = collect([$featuredProjects, $featuredProjects]);
                }
            @endphp
            <style>
                .community-projects-lanes {
                    display: flex;
                    flex-direction: column;
                    gap: 1.25rem;
                    width: 100vw;
                    position: relative;
                    left: 50%;
                    right: 50%;
                    margin-left: -50vw;
                    margin-right: -50vw;
                }
                .community-projects-marquee {
                    display: flex;
                    overflow: hidden;
                    width: 100%;
                    mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                    -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                }
                .community-projects-track {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 1.25rem;
                    width: max-content;
                    will-change: transform;
                    padding: 0.35rem 0;
                }
                .community-projects-track.is-left {
                    animation: community-projects-scroll-left 70s linear infinite;
                }
                .community-projects-track.is-right {
                    animation: community-projects-scroll-right 75s linear infinite;
                }
                .community-projects-marquee:hover .community-projects-track,
                .community-projects-marquee:focus-within .community-projects-track {
                    animation-play-state: paused;
                }
                .community-project-card {
                    width: min(78vw, 300px);
                    flex: 0 0 auto;
                    background: #fff;
                }
                .community-project-card video {
                    pointer-events: none;
                }
                @keyframes community-projects-scroll-left {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                @keyframes community-projects-scroll-right {
                    0% { transform: translateX(-50%); }
                    100% { transform: translateX(0); }
                }
                @media (prefers-reduced-motion: reduce) {
                    .community-projects-track {
                        animation: none !important;
                        overflow-x: auto;
                        width: 100%;
                        -webkit-overflow-scrolling: touch;
                        scrollbar-width: none;
                    }
                    .community-projects-track::-webkit-scrollbar { display: none; }
                    .community-projects-track .community-project-card[data-clone="1"] {
                        display: none;
                    }
                }
            </style>

            <div class="community-projects-lanes" aria-label="Featured student projects">
                @foreach($projectRows->values() as $rowIndex => $rowProjects)
                    <div class="community-projects-marquee">
                        <div class="community-projects-track {{ $rowIndex % 2 === 0 ? 'is-left' : 'is-right' }}">
                            @foreach([0, 1] as $dup)
                                @foreach($rowProjects as $project)
                                    <div class="community-project-card clay-card rounded-2xl overflow-hidden shadow-sm border border-outline-variant flex flex-col h-full" data-project-id="{{ $project->id }}" @if($dup === 1) data-clone="1" aria-hidden="true" @endif>
                                        @if($project->video_path)
                                            @php
                                                $ext = strtolower(pathinfo($project->video_path, PATHINFO_EXTENSION));
                                                $mediaUrl = Str::startsWith($project->video_path, ['http://', 'https://'])
                                                    ? $project->video_path
                                                    : asset('storage/' . $project->video_path);
                                            @endphp
                                            <div class="relative w-full aspect-video bg-on-surface-variant shrink-0 overflow-hidden">
                                                @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                                    <img loading="lazy" src="{{ $mediaUrl }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $project->title }}" draggable="false" oncontextmenu="return false;" data-clarity-mask="true">
                                                @else
                                                    <video
                                                        class="absolute inset-0 w-full h-full object-cover"
                                                        data-lazy-src="{{ $mediaUrl }}"
                                                        muted
                                                        loop
                                                        playsinline
                                                        preload="none"
                                                        controlslist="nodownload noplaybackrate nofullscreen"
                                                        disablepictureinpicture
                                                        disableremoteplayback
                                                        oncontextmenu="return false;"
                                                        data-clarity-mask="true"
                                                        @if($dup === 1) tabindex="-1" @endif
                                                    ></video>
                                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none text-white/40 lazy-video-placeholder">
                                                        <span class="material-symbols-outlined text-5xl">play_circle</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="relative w-full aspect-video bg-secondary-container flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-secondary-container text-6xl">videocam_off</span>
                                            </div>
                                        @endif
                                        <div class="p-4 flex-1 flex flex-col">
                                            <div class="mb-3">
                                                <h3 class="font-bold text-base text-on-surface leading-snug">{{ $project->title }}</h3>
                                                <p class="text-xs text-on-surface-variant mt-1">by {{ $project->author_name }} &bull; {{ $project->category }}</p>
                                            </div>

                                            <div class="mt-auto pt-3 border-t border-outline-variant flex items-center justify-between">
                                                <div class="flex gap-4">
                                                    @php $hasLiked = in_array($project->id, $likedProjectIds ?? [], true); @endphp
                                                    <button type="button"
                                                        class="like-btn flex items-center gap-1 transition-colors {{ $hasLiked ? 'text-error cursor-default' : 'text-on-surface-variant hover:text-error' }}"
                                                        data-project-id="{{ $project->id }}"
                                                        data-liked="{{ $hasLiked ? '1' : '0' }}"
                                                        onclick="incrementLike({{ $project->id }}, this)"
                                                        @if($hasLiked) disabled aria-pressed="true" @else aria-pressed="false" @endif
                                                        @if($dup === 1) tabindex="-1" @endif>
                                                        <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">favorite</span>
                                                        <span class="text-sm font-medium like-count">{{ $project->likes_count }}</span>
                                                    </button>
                                                    <div class="flex items-center gap-1 text-on-surface-variant" title="Views">
                                                        <span class="material-symbols-outlined text-base">visibility</span>
                                                        <span class="text-sm font-medium view-count" data-project-id="{{ $project->id }}">{{ $project->views_count }}</span>
                                                    </div>
                                                </div>
                                                @if($project->project_url)
                                                    <a href="{{ $project->project_url }}" target="_blank" class="text-sm font-bold text-primary hover:underline" @if($dup === 1) tabindex="-1" @endif>View Live</a>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Comments Section -->
                                        <div class="px-4 pb-4 border-t border-outline-variant bg-surface-container-low mt-auto rounded-b-2xl">
                                            <div x-data="{ showComments: false }">
                                                <button @click="showComments = !showComments" class="w-full text-left text-sm font-bold text-on-surface hover:text-on-surface py-2 flex items-center justify-between">
                                                    <span>Comments ({{ $project->comments->count() }})</span>
                                                    <span class="material-symbols-outlined text-sm transition-transform" :class="showComments ? 'rotate-180' : ''">expand_more</span>
                                                </button>
                                                
                                                <div x-show="showComments" style="display: none;" class="pt-2 space-y-3">
                                                    <div class="max-h-40 overflow-y-auto space-y-2 pr-1">
                                                        @forelse($project->comments as $comment)
                                                            <div class="text-xs bg-white p-2 rounded-lg border border-outline-variant">
                                                                <div class="font-bold text-on-surface">{{ $comment->visitor_name ?? 'Anonymous' }} <span class="font-normal text-outline text-[10px] ml-1">{{ $comment->created_at->diffForHumans() }}</span></div>
                                                                <div class="text-on-surface mt-1 break-words">{{ $comment->content }}</div>
                                                            </div>
                                                        @empty
                                                            <div class="text-xs text-outline italic">No comments yet. Be the first to comment!</div>
                                                        @endforelse
                                                    </div>
                                                    
                                                    <form action="{{ route('community.project.comment', $project->id) }}" method="POST" class="mt-3 flex flex-col gap-2">
                                                        @csrf
                                                        @guest
                                                            <input type="text" name="visitor_name" placeholder="Your Name (Optional)" class="w-full text-xs p-2 border border-outline-variant rounded-lg outline-none focus:border-primary-fixed0">
                                                        @endguest
                                                        <textarea name="content" required rows="2" placeholder="Write a comment..." class="w-full text-xs p-2 border border-outline-variant rounded-lg outline-none focus:border-primary-fixed0 resize-none"></textarea>
                                                        <button type="submit" class="self-end px-4 py-1.5 bg-primary text-white text-xs font-bold rounded-full hover:bg-primary-container transition-colors shadow-sm">Post</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border border-outline-variant">
                <span class="material-symbols-outlined text-4xl text-outline mb-3">rocket_launch</span>
                <p class="text-on-surface-variant font-medium">New student projects coming soon!</p>
            </div>
        @endif
    </section>

    <!-- Testimonials Section -->
    <section>
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-on-surface mb-4">What Parents Are Saying</h2>
            <p class="text-on-surface max-w-2xl mx-auto">Hear from the families whose children are thriving at Younimatiq.</p>
        </div>

        @if($testimonials->count() > 0)
            <style>
                .community-testimonials-marquee {
                    display: flex;
                    overflow: hidden;
                    width: 100vw;
                    position: relative;
                    left: 50%;
                    right: 50%;
                    margin-left: -50vw;
                    margin-right: -50vw;
                    mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                    -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                }
                .community-testimonials-track {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 1.25rem;
                    width: max-content;
                    will-change: transform;
                    padding: 0.35rem 0;
                    animation: community-testimonials-scroll 85s linear infinite;
                }
                .community-testimonials-marquee:hover .community-testimonials-track,
                .community-testimonials-marquee:focus-within .community-testimonials-track {
                    animation-play-state: paused;
                }
                .community-testimonial-card {
                    width: min(85vw, 340px);
                    flex: 0 0 auto;
                }
                @keyframes community-testimonials-scroll {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                @media (prefers-reduced-motion: reduce) {
                    .community-testimonials-track {
                        animation: none !important;
                        overflow-x: auto;
                        width: 100%;
                        -webkit-overflow-scrolling: touch;
                        scrollbar-width: none;
                    }
                    .community-testimonials-track::-webkit-scrollbar { display: none; }
                    .community-testimonial-card[data-clone="1"] {
                        display: none;
                    }
                }
            </style>

            <div class="community-testimonials-marquee" aria-label="Parent testimonials">
                <div class="community-testimonials-track">
                    @foreach([0, 1] as $dup)
                        @foreach($testimonials as $testimonial)
                            <div class="community-testimonial-card bg-white p-8 rounded-2xl shadow-sm border border-outline-variant flex flex-col relative h-full" @if($dup === 1) data-clone="1" aria-hidden="true" @endif>
                                <div class="flex text-warning mb-4 text-sm gap-0.5">
                                    @for($i = 0; $i < $testimonial->rating; $i++)
                                        <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">star</span>
                                    @endfor
                                    @for($i = $testimonial->rating; $i < 5; $i++)
                                        <span class="material-symbols-outlined text-base text-outline">star</span>
                                    @endfor
                                </div>
                                <p class="text-on-surface leading-relaxed mb-6 italic flex-1">"{{ $testimonial->text }}"</p>

                                <div class="mt-auto flex items-center gap-4">
                                    @if($testimonial->avatar_path)
                                        <img src="{{ Str::startsWith($testimonial->avatar_path, ['http://', 'https://']) ? $testimonial->avatar_path : asset('storage/' . $testimonial->avatar_path) }}" alt="{{ $testimonial->author_name }}" class="w-12 h-12 rounded-full object-cover" draggable="false" oncontextmenu="return false;" data-clarity-mask="true">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-lg">
                                            {{ substr($testimonial->author_name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="font-bold text-on-surface">{{ $testimonial->author_name }}</h4>
                                        <p class="text-xs text-on-surface-variant">Verified Parent</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border border-outline-variant">
                <span class="material-symbols-outlined text-4xl text-outline mb-3">reviews</span>
                <p class="text-on-surface-variant font-medium">Testimonials coming soon!</p>
            </div>
        @endif
    </section>
</div>

<script>
    const likedProjectsStorageKey = 'younimatiq_community_liked_projects';

    function getStoredLikedIds() {
        try {
            const raw = localStorage.getItem(likedProjectsStorageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(Number) : [];
        } catch (e) {
            return [];
        }
    }

    function storeLikedId(projectId) {
        const ids = getStoredLikedIds();
        if (!ids.includes(projectId)) {
            ids.push(projectId);
            localStorage.setItem(likedProjectsStorageKey, JSON.stringify(ids));
        }
    }

    function markProjectLiked(projectId, likesCount) {
        document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
            btn.dataset.liked = '1';
            btn.disabled = true;
            btn.setAttribute('aria-pressed', 'true');
            btn.classList.remove('text-on-surface-variant', 'hover:text-error');
            btn.classList.add('text-error', 'cursor-default');
            if (typeof likesCount !== 'undefined' && likesCount !== null) {
                const countEl = btn.querySelector('.like-count');
                if (countEl) countEl.innerText = likesCount;
            }
        });
        storeLikedId(Number(projectId));
    }

    function revertOptimisticLike(projectId, previousCount) {
        document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
            btn.dataset.liked = '0';
            btn.disabled = false;
            btn.setAttribute('aria-pressed', 'false');
            btn.classList.add('text-on-surface-variant', 'hover:text-error');
            btn.classList.remove('text-error', 'cursor-default');
            const countEl = btn.querySelector('.like-count');
            if (countEl) countEl.innerText = previousCount;
        });
    }

    function incrementLike(projectId, btn) {
        if (!btn || btn.dataset.liked === '1' || btn.disabled) {
            return;
        }

        // Instant optimistic UI — do not wait for the network
        const countEl = btn.querySelector('.like-count');
        const previousCount = countEl ? parseInt(countEl.innerText, 10) || 0 : 0;
        const optimisticCount = previousCount + 1;
        markProjectLiked(projectId, optimisticCount);

        fetch(`/community/projects/${projectId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            keepalive: true
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.error || 'Unable to like project');
            }
            if (typeof data.likes !== 'undefined') {
                markProjectLiked(projectId, data.likes);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            revertOptimisticLike(projectId, previousCount);
        });
    }

    // Hydrate liked state from localStorage for snappy repeat visits
    document.addEventListener('DOMContentLoaded', function () {
        getStoredLikedIds().forEach(function (projectId) {
            document.querySelectorAll('.like-btn[data-project-id="' + projectId + '"]').forEach(function (btn) {
                if (btn.dataset.liked !== '1') {
                    btn.dataset.liked = '1';
                    btn.disabled = true;
                    btn.setAttribute('aria-pressed', 'true');
                    btn.classList.remove('text-on-surface-variant', 'hover:text-error');
                    btn.classList.add('text-error', 'cursor-default');
                }
            });
        });

        initCommunityProjectViews();
    });

    const viewedProjectsStorageKey = 'younimatiq_community_viewed_projects';
    const csrfToken = '{{ csrf_token() }}';

    function getStoredViewedIds() {
        try {
            const raw = sessionStorage.getItem(viewedProjectsStorageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(Number) : [];
        } catch (e) {
            return [];
        }
    }

    function storeViewedId(projectId) {
        const ids = getStoredViewedIds();
        if (!ids.includes(projectId)) {
            ids.push(projectId);
            sessionStorage.setItem(viewedProjectsStorageKey, JSON.stringify(ids));
        }
    }

    function updateViewCounts(projectId, viewsCount) {
        document.querySelectorAll('.view-count[data-project-id="' + projectId + '"]').forEach(function (el) {
            el.innerText = viewsCount;
        });
    }

    function registerProjectView(projectId) {
        projectId = Number(projectId);
        if (!projectId || getStoredViewedIds().includes(projectId)) {
            return;
        }

        // Mark locally first so clones / re-entries don't double count
        storeViewedId(projectId);

        // Optimistic bump
        document.querySelectorAll('.view-count[data-project-id="' + projectId + '"]').forEach(function (el) {
            const current = parseInt(el.innerText, 10) || 0;
            el.innerText = current + 1;
        });

        fetch(`/community/projects/${projectId}/view`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            keepalive: true
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.error || 'Unable to register view');
            }
            if (typeof data.views !== 'undefined') {
                updateViewCounts(projectId, data.views);
            }
        })
        .catch((error) => {
            console.error('View tracking error:', error);
        });
    }

    function initCommunityProjectViews() {
        const cards = document.querySelectorAll('.community-project-card[data-project-id]');
        if (!cards.length) return;

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting || entry.intersectionRatio < 0.01) return;
                    const projectId = entry.target.getAttribute('data-project-id');
                    registerProjectView(projectId);
                    // Stop watching every card for this project (original + clones)
                    document.querySelectorAll('.community-project-card[data-project-id="' + projectId + '"]').forEach(function (card) {
                        observer.unobserve(card);
                    });
                });
            }, { threshold: [0.01], rootMargin: '0px' });

            cards.forEach(function (card) {
                observer.observe(card);
            });
        } else {
            // Fallback for older browsers: count cards currently near viewport on scroll
            function checkVisible() {
                cards.forEach(function (card) {
                    const rect = card.getBoundingClientRect();
                    const visible = rect.top < window.innerHeight * 0.85 && rect.bottom > window.innerHeight * 0.15;
                    if (visible) registerProjectView(card.getAttribute('data-project-id'));
                });
            }
            checkVisible();
            window.addEventListener('scroll', checkVisible, { passive: true });
        }
    }
</script>
@endsection
