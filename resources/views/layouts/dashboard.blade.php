<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Younimatiq') }}</title>

    @include('partials.favicon')
    @include('partials.google-analytics')
    @include('partials.meta-pixel')
    @include('partials.microsoft-clarity')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700;800&family=Baloo+2:wght@700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/material-symbols/material-symbols-outlined.woff2') }}?v=20261104f" crossorigin>
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700;800&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    </noscript>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine is bundled into app.js (self-hosted, no third-party CDN dependency) -->

    <!-- HLS.js for video playback -->
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>

    <style>
        /* Compact, professional panel chrome (student / tutor / admin) */
        .panel-shell {
            font-family: Nunito, system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            letter-spacing: -0.01em;
            color: #1F1635;
            --color-primary: #7130D8;
            --color-primary-container: #EFE6FF;
            --color-on-primary-container: #3B1278;
        }
        .panel-shell h1, .panel-shell h2, .panel-shell .font-headline-lg {
            font-family: 'Baloo 2', Nunito, system-ui, sans-serif;
            color: #1F1635;
        }
        .panel-shell .material-symbols-outlined {
            font-size: 1.15rem;
            line-height: 1;
        }
        /* --- Sidebar navigation pills --- */
        .panel-nav-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0.7rem;
            margin: 1px 0;
            border-radius: 0.7rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            color: #4A4160;
            text-decoration: none;
            position: relative;
            transition: background-color .16s ease, color .16s ease, transform .16s ease, box-shadow .16s ease;
        }
        .panel-nav-link > span:last-child {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .panel-nav-link .material-symbols-outlined {
            font-size: 1.15rem;
            opacity: 0.85;
            transition: transform .16s ease, opacity .16s ease;
        }
        .panel-nav-link:hover {
            background: #F3ECFF;
            color: #7130D8;
            transform: translateX(2px);
        }
        .panel-nav-link:hover .material-symbols-outlined { opacity: 1; }
        .panel-nav-link.is-active {
            background: linear-gradient(115deg, #6B27D8, #8B4FF0);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px -10px rgba(113, 48, 216, 0.75);
        }
        .panel-nav-link.is-active .material-symbols-outlined { opacity: 1; }

        .panel-section-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #9A90B0;
            padding: 0.9rem 0.7rem 0.35rem;
        }

        .panel-sidebar .panel-logout {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            width: 100%;
            padding: 0.5rem 0.7rem;
            border-radius: 0.7rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #B91C1C;
            background: transparent;
            text-align: left;
            transition: background-color .16s ease, color .16s ease;
        }
        .panel-sidebar .panel-logout:hover { background: #FEECEC; color: #991B1B; }
        .panel-sidebar .panel-logout .material-symbols-outlined { font-size: 1.15rem; }
        .panel-sidebar .panel-logout-footer {
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid #EAE6F2;
        }

        @media (prefers-reduced-motion: reduce) {
            .panel-nav-link, .panel-nav-link:hover { transition: none; transform: none; }
        }
        .panel-shell main {
            font-size: 0.875rem;
            padding: 0.25rem;
        }
        .panel-shell main h1,
        .panel-shell main .font-headline-xl,
        .panel-shell main .text-headline-xl,
        .panel-shell main .text-3xl,
        .panel-shell main .text-4xl {
            font-size: 1.35rem !important;
            line-height: 1.3 !important;
            font-weight: 650;
            letter-spacing: -0.02em;
        }
        .panel-shell main h2,
        .panel-shell main .font-headline-lg,
        .panel-shell main .text-headline-lg,
        .panel-shell main .text-2xl {
            font-size: 1.125rem !important;
            line-height: 1.35 !important;
        }
        .panel-shell main h3,
        .panel-shell main .font-headline-md,
        .panel-shell main .text-headline-md,
        .panel-shell main .text-xl {
            font-size: 1rem !important;
            line-height: 1.35 !important;
        }
        .panel-shell main .font-body-lg,
        .panel-shell main .text-body-lg,
        .panel-shell main .text-lg {
            font-size: 0.9375rem !important;
        }
        .panel-shell main .p-5.md\:p-8,
        .panel-shell main .p-5.md\:p-8.lg\:p-6.md\:p-12,
        .panel-shell main .clay-card.p-5,
        .panel-shell main .clay-card.p-6,
        .panel-shell main .clay-card.p-8 {
            padding: 1rem 1.15rem !important;
        }
        .panel-shell main .py-12 {
            padding-top: 1.25rem !important;
            padding-bottom: 1.25rem !important;
        }
        .panel-shell main .mb-10,
        .panel-shell main .mb-12 {
            margin-bottom: 1.25rem !important;
        }
        .panel-shell main .gap-6 {
            gap: 0.9rem !important;
        }
        .panel-shell main .gap-8 {
            gap: 1rem !important;
        }
        .panel-shell main .w-16.h-16 {
            width: 2.75rem !important;
            height: 2.75rem !important;
        }
        .panel-shell main .text-4xl {
            font-size: 1.5rem !important;
        }
        .panel-shell main table {
            font-size: 0.8125rem;
        }
        .panel-shell main th {
            font-size: 0.7rem;
            font-weight: 650;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #64748b;
        }
        .panel-shell main .rounded-\[2rem\],
        .panel-shell main .rounded-3xl {
            border-radius: 0.9rem !important;
        }
    </style>
</head>
<body @class([
    'panel-shell font-sans antialiased bg-surface text-on-surface h-screen overflow-hidden flex flex-col selection:bg-primary selection:text-white',
    'assignment-paste-enabled' => request()->routeIs('student.assignments*', 'admin.assignments*', 'tutor.assignments*'),
]) x-data="{ sidebarOpen: false }">

    @php
        $currentUser = auth('admin')->user() ?? auth('tutor')->user() ?? auth('web')->user();
        $isStudent = $currentUser && $currentUser->role === 'student';
        $isTutor = $currentUser && $currentUser->role === 'tutor';
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        
        $profileRoute = 'student.profile';
        if ($isTutor) $profileRoute = 'tutor.profile';
        if ($isAdmin) $profileRoute = 'admin.dashboard'; // Admin has no profile page
        
        $prefix = $isStudent ? 'student.' : ($isTutor ? 'tutor.' : 'admin.');
    @endphp

    <!-- Impersonation Banner -->
    @if(session()->has('impersonated_by'))
        <div class="bg-secondary text-on-secondary px-4 py-2 text-center text-sm font-bold shadow-md relative z-[100]">
            You are currently impersonating {{ $currentUser->name }}.
            <a href="{{ route('impersonate.leave') }}" class="underline hover:text-white ml-2 px-3 py-1 bg-black/20 rounded-lg">Return to Admin</a>
        </div>
    @endif

    <!-- Top Navbar -->
    <nav class="shrink-0 sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-outline-variant/80 h-14 shadow-none overflow-visible">
        <div class="w-full px-4 h-full flex justify-between items-center">
            <!-- Left: Mobile Menu Toggle + Logo -->
            <div class="flex items-center gap-2.5">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 text-on-surface hover:text-primary rounded-lg bg-surface-container-low transition-colors" aria-label="Toggle navigation drawer">
                    <span class="material-symbols-outlined text-xl" x-text="sidebarOpen ? 'close' : 'menu'">menu</span>
                </button>
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <img alt="Younimatiq Logo" class="h-8 md:h-9 w-auto group-hover:opacity-90 transition-opacity object-contain" src="{{ asset('logo.webp') }}"/>
                </a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Notifications -->
                <div x-data="notificationBell()" class="relative" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open" class="relative z-[90] p-1.5 text-on-surface hover:text-primary transition-colors focus:outline-none rounded-lg hover:bg-surface-container-low" aria-label="Notifications" :aria-expanded="open.toString()">
                        <span class="material-symbols-outlined text-xl">notifications</span>
                        <span x-show="unread > 0" x-cloak
                              class="absolute -top-0.5 -right-0.5 z-[100] min-w-[1.05rem] h-[1.05rem] px-1 flex items-center justify-center rounded-full bg-error text-[9px] font-bold text-white leading-none shadow ring-2 ring-white"
                              x-text="unread > 9 ? '9+' : unread"></span>
                    </button>

                    <!-- Compact dropdown directly under the bell -->
                    <div x-show="open" x-cloak
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute right-0 top-full mt-2 z-[80] w-[min(20rem,calc(100vw-1rem))] bg-white rounded-xl shadow-xl border border-outline-variant overflow-hidden"
                         style="height: 320px;">
                        <div class="h-full flex flex-col">
                            <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-outline-variant bg-surface-container-low shrink-0">
                                <h3 class="font-semibold text-xs text-on-surface">Notifications</h3>
                                <button type="button" @click.stop="markAllRead()" x-show="unread > 0" class="text-[11px] text-primary font-semibold hover:underline whitespace-nowrap">Mark all read</button>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-scroll overscroll-contain" style="-webkit-overflow-scrolling: touch;">
                                <template x-if="items.length === 0">
                                    <div class="px-3 py-10 text-center text-xs text-on-surface-variant">
                                        No new notifications.
                                    </div>
                                </template>
                                <template x-for="item in items" :key="item.id">
                                    <a :href="item.action_url || '#'"
                                       @click="markRead(item.id)"
                                       class="block px-3 py-2.5 border-b border-outline-variant hover:bg-surface-container-low active:bg-surface-container-low transition-colors text-left bg-primary/[0.04]">
                                        <p class="text-xs font-semibold text-on-surface break-words" x-text="item.title"></p>
                                        <p class="text-[11px] text-on-surface mt-0.5 line-clamp-2 break-words" x-show="item.body" x-text="item.body"></p>
                                        <span class="text-[10px] text-outline mt-1 block" x-text="item.created_at"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Menu -->
                <a href="{{ route($profileRoute) }}" class="flex items-center gap-2 bg-white py-1 px-2 rounded-full border border-outline-variant cursor-pointer hover:border-primary/40 hover:bg-surface-container-low transition-all group">
                    <img src="{{ $currentUser->avatar ? asset('storage/' . $currentUser->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($currentUser->name ?? 'User').'&background=7130D8&color=fff' }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                    <div class="hidden md:block text-xs">
                        <p class="font-semibold text-on-surface leading-none">{{ $currentUser->name ?? 'Explorer' }}</p>
                    </div>
                    <span class="material-symbols-outlined text-outline text-sm">expand_more</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Layout -->
    <div class="flex-1 min-h-0 flex w-full mx-auto overflow-hidden relative">
        
        <!-- Mobile Backdrop Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 bg-on-surface-variant/50 backdrop-blur-[1px] z-[90] lg:hidden"></div>

        <!-- Sidebar (Fixed Left on Desktop, Off-Canvas Drawer on Mobile) -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="panel-sidebar fixed lg:static inset-y-0 left-0 w-60 lg:w-52 flex-shrink-0 bg-white border-r border-outline-variant overflow-y-auto no-scrollbar py-3 flex flex-col gap-0.5 px-2.5 z-[100] lg:z-40 transition-transform duration-300 ease-in-out">
            
            <div class="panel-section-label flex justify-between items-center !pt-1">
                <span>Main Menu</span>
                <button @click="sidebarOpen = false" class="lg:hidden p-1 text-outline hover:text-primary">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
            
            @if($isAdmin)
                <a href="{{ route('admin.dashboard') }}" class="panel-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="panel-nav-link {{ request()->routeIs('admin.reports') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">bar_chart</span><span>Reports &amp; Analysis</span>
                </a>
                <a href="{{ route('admin.orders') }}" class="panel-nav-link {{ request()->routeIs('admin.orders*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">payments</span><span>Orders &amp; Payments</span>
                </a>

                <div class="panel-section-label">People</div>
                <a href="{{ route('admin.students') }}" class="panel-nav-link {{ request()->routeIs('admin.students') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">group</span><span>Students</span>
                </a>
                <a href="{{ route('admin.tutors') }}" class="panel-nav-link {{ request()->routeIs('admin.tutors*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">badge</span><span>Tutors</span>
                </a>
                <a href="{{ route('admin.tutor-training.index') }}" class="panel-nav-link {{ request()->routeIs('admin.tutor-training*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">school</span><span>Tutor Training</span>
                </a>
                <a href="{{ route('admin.skills.index') }}" class="panel-nav-link {{ request()->routeIs('admin.skills*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">psychology</span><span>Skills</span>
                </a>
                <a href="{{ route('admin.consultations') }}" class="panel-nav-link {{ request()->routeIs('admin.consultations*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">contact_page</span><span>Consultations</span>
                </a>
                <a href="{{ route('admin.enrollments') }}" class="panel-nav-link {{ request()->routeIs('admin.enrollments*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">how_to_reg</span><span>Enrollments</span>
                </a>

                <div class="panel-section-label">Learning</div>
                <a href="{{ route('admin.courses') }}" class="panel-nav-link {{ request()->routeIs('admin.courses*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">menu_book</span><span>Courses</span>
                </a>
                <a href="{{ route('admin.assignments') }}" class="panel-nav-link {{ request()->routeIs('admin.assignments*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">assignment</span><span>Post class Activity</span>
                </a>
                <a href="{{ route('admin.quizzes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.quizzes.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">quiz</span><span>Quizzes</span>
                </a>
                <a href="{{ route('admin.tips') }}" class="panel-nav-link {{ request()->routeIs('admin.tips*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">smart_display</span><span>Tips &amp; Tricks</span>
                </a>
                <a href="{{ route('admin.recordings.index') }}" class="panel-nav-link {{ request()->routeIs('admin.recordings*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">videocam</span><span>Recordings</span>
                </a>
                <a href="{{ route('admin.meet-alerts.index') }}" class="panel-nav-link {{ request()->routeIs('admin.meet-alerts*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">shield_person</span><span>Meet Alerts</span>
                </a>
                <a href="{{ route('admin.feedback.index') }}" class="panel-nav-link {{ request()->routeIs('admin.feedback.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">rate_review</span><span>Class Feedback</span>
                </a>

                <div class="panel-section-label">Content</div>
                <a href="{{ route('admin.categories.index') }}" class="panel-nav-link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">category</span><span>Categories</span>
                </a>
                <a href="{{ route('admin.age-groups') }}" class="panel-nav-link {{ request()->routeIs('admin.age-groups') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">group_work</span><span>Grade Bands</span>
                </a>
                <a href="{{ route('admin.community.projects') }}" class="panel-nav-link {{ request()->routeIs('admin.community.projects') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">stars</span><span>Community Projects</span>
                </a>
                <a href="{{ route('admin.community.testimonials') }}" class="panel-nav-link {{ request()->routeIs('admin.community.testimonials') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">reviews</span><span>Testimonials</span>
                </a>
                <a href="{{ route('admin.blogs.index') }}" class="panel-nav-link {{ request()->routeIs('admin.blogs.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">article</span><span>Blogs</span>
                </a>
                <a href="{{ route('admin.pages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.pages.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">web</span><span>Pages</span>
                </a>
                <a href="{{ route('admin.faqs') }}" class="panel-nav-link {{ request()->routeIs('admin.faqs') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">help_center</span><span>FAQs</span>
                </a>
                <a href="{{ route('admin.coupons') }}" class="panel-nav-link {{ request()->routeIs('admin.coupons') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">local_offer</span><span>Coupons</span>
                </a>

                <div class="panel-section-label">Operations</div>
                <a href="{{ route('admin.ops.roster') }}" class="panel-nav-link {{ request()->routeIs('admin.ops.roster') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">dashboard_customize</span><span>Ops Roster</span>
                </a>
                <a href="{{ route('admin.ops.nps') }}" class="panel-nav-link {{ request()->routeIs('admin.ops.nps') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">sentiment_satisfied</span><span>NPS</span>
                </a>
                <a href="{{ route('admin.contact-messages') }}" class="panel-nav-link {{ request()->routeIs('admin.contact-messages') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">mail</span><span>Contact Forms</span>
                </a>
                <a href="{{ route('admin.login-history.index') }}" class="panel-nav-link {{ request()->routeIs('admin.login-history.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">history</span><span>Login History</span>
                </a>
                <a href="{{ route('admin.chatbot.index') }}" class="panel-nav-link {{ request()->routeIs('admin.chatbot.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">smart_toy</span><span>Chatbot Training</span>
                </a>

                <div class="panel-section-label">Settings</div>
                <a href="{{ route('admin.platform-settings') }}" class="panel-nav-link {{ request()->routeIs('admin.platform-settings') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">settings</span><span>Platform Settings</span>
                </a>
                <a href="{{ route('admin.integrations') }}" class="panel-nav-link {{ request()->routeIs('admin.integrations*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">hub</span><span>Integrations</span>
                </a>
                <a href="{{ route('admin.settings.seo') }}" class="panel-nav-link {{ request()->routeIs('admin.settings.seo') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">search</span><span>SEO Section</span>
                </a>
                <a href="{{ route('admin.settings.newsletter') }}" class="panel-nav-link {{ request()->routeIs('admin.settings.newsletter') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">mail</span><span>Newsletter</span>
                </a>
                <a href="{{ route('admin.email.index') }}" class="panel-nav-link {{ request()->routeIs('admin.email.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">email</span><span>Email Templates</span>
                </a>
                <a href="{{ route('admin.email-automations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.email-automations.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">campaign</span><span>Email Automations</span>
                </a>

            @elseif($isTutor)
                <a href="{{ route('tutor.dashboard') }}" class="panel-nav-link {{ request()->routeIs('tutor.dashboard') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
                </a>
                <a href="{{ route('tutor.profile') }}" class="panel-nav-link {{ request()->routeIs('tutor.profile') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">person</span><span>My Profile</span>
                </a>
                <a href="{{ route('tutor.classes') }}" class="panel-nav-link {{ request()->routeIs('tutor.classes') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">event</span><span>Upcoming Classes</span>
                </a>
                <a href="{{ route('tutor.students') }}" class="panel-nav-link {{ request()->routeIs('tutor.students') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">group</span><span>My Students</span>
                </a>

                <div class="panel-section-label">Teaching</div>
                <a href="{{ route('tutor.manage-courses') }}" class="panel-nav-link {{ request()->routeIs('tutor.manage-courses') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">menu_book</span><span>Assigned Classes</span>
                </a>
                <a href="{{ route('tutor.availability') }}" class="panel-nav-link {{ request()->routeIs('tutor.availability*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">schedule</span><span>Availability</span>
                </a>
                <a href="{{ route('tutor.assignments') }}" class="panel-nav-link {{ request()->routeIs('tutor.assignments*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">assignment</span><span>Post class Activity</span>
                </a>
                <a href="{{ route('tutor.quizzes.index') }}" class="panel-nav-link {{ request()->routeIs('tutor.quizzes.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">quiz</span><span>Quizzes</span>
                </a>
                <a href="{{ route('tutor.rewards.index') }}" class="panel-nav-link {{ request()->routeIs('tutor.rewards.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">celebration</span><span>Class Rewards</span>
                </a>
                <a href="{{ route('tutor.qa') }}" class="panel-nav-link {{ request()->routeIs('tutor.qa') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">forum</span><span>Q &amp; A</span>
                </a>

                <div class="panel-section-label">Communication</div>
                <a href="{{ route('tutor.messages.index') }}" class="panel-nav-link {{ request()->routeIs('tutor.messages.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">chat</span><span>Messages</span>
                </a>
                <a href="{{ route('tutor.announcements.index') }}" class="panel-nav-link {{ request()->routeIs('tutor.announcements.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">campaign</span><span>Announcements</span>
                </a>

                <div class="panel-section-label">Resources</div>
                <a href="{{ route('tutor.resources') }}" class="panel-nav-link {{ request()->routeIs('tutor.resources') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">folder</span><span>Resource Library</span>
                </a>
                <a href="{{ route('tutor.training.index') }}" class="panel-nav-link {{ request()->routeIs('tutor.training*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">workspace_premium</span><span>Training</span>
                </a>
                <a href="{{ route('tutor.policy-training') }}" class="panel-nav-link {{ request()->routeIs('tutor.policy-training*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">verified_user</span><span>Policy Training</span>
                </a>
            @elseif($isStudent)
                <a href="{{ route('student.dashboard') }}" class="panel-nav-link {{ request()->routeIs('student.dashboard') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
                </a>
                <a href="{{ route('student.courses') }}" class="panel-nav-link {{ request()->routeIs('student.courses') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">school</span><span>My Courses</span>
                </a>
                <a href="{{ route('student.bookings') }}" class="panel-nav-link {{ request()->routeIs('student.bookings') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">calendar_month</span><span>Tutor Bookings</span>
                </a>
                <a href="{{ route('student.recordings') }}" class="panel-nav-link {{ request()->routeIs('student.recordings*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">video_library</span><span>Recordings</span>
                </a>

                <div class="panel-section-label">Learning</div>
                <a href="{{ route('student.assignments') }}" class="panel-nav-link {{ request()->routeIs('student.assignments*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">assignment</span><span>Post class Activity</span>
                </a>
                <a href="{{ route('student.quizzes') }}" class="panel-nav-link {{ request()->routeIs('student.quizzes*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">quiz</span><span>Quizzes</span>
                </a>
                <a href="{{ route('student.wishlist') }}" class="panel-nav-link {{ request()->routeIs('student.wishlist*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">favorite</span><span>Wishlist</span>
                </a>
                <a href="{{ route('student.qa') }}" class="panel-nav-link {{ request()->routeIs('student.qa*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">forum</span><span>Q &amp; A</span>
                </a>
                <a href="{{ route('student.reviews') }}" class="panel-nav-link {{ request()->routeIs('student.reviews') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">star</span><span>Reviews</span>
                </a>

                <div class="panel-section-label">Account</div>
                <a href="{{ route('student.payment-history') }}" class="panel-nav-link {{ request()->routeIs('student.payment-history') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">receipt_long</span><span>Payment History</span>
                </a>
                <a href="{{ route('student.referrals') }}" class="panel-nav-link {{ request()->routeIs('student.referrals') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">redeem</span><span>Referrals</span>
                </a>
                <a href="{{ route('student.profile') }}" class="panel-nav-link {{ request()->routeIs('student.profile') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">person</span><span>My Profile</span>
                </a>
                <a href="{{ route('student.settings') }}" class="panel-nav-link {{ request()->routeIs('student.settings') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">settings</span><span>Settings</span>
                </a>
            @endif

            <div class="panel-logout-footer">
                <form method="POST" action="{{ route('logout.' . str_replace('.', '', $prefix)) }}">
                    @csrf
                    <button type="submit" class="panel-logout">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-y-auto no-scrollbar bg-surface-container-low/80 relative">
            <div class="min-h-full p-3 md:p-5">
                <div class="reveal-stagger">
                    @yield('content')
                </div>
            </div>
        </main>

    </div>

    <style>
        /* Purposeful entrance motion for panel content */
        @keyframes panelReveal {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .reveal-stagger > * {
            animation: panelReveal 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .reveal-stagger > *:nth-child(2) { animation-delay: 0.04s; }
        .reveal-stagger > *:nth-child(3) { animation-delay: 0.08s; }
        .reveal-stagger > *:nth-child(4) { animation-delay: 0.12s; }
        .reveal-stagger > *:nth-child(n+5) { animation-delay: 0.16s; }

        /* Unified panel table styling */
        .panel-shell main table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .panel-shell main thead th {
            background: #F8F5FF;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .panel-shell main tbody tr { transition: background-color .14s ease; }
        .panel-shell main tbody tr:hover { background: #FBF9FF; }

        @media (prefers-reduced-motion: reduce) {
            .reveal-stagger > * { animation: none; }
        }
    </style>

    <script>
    function notificationBell() {
        return {
            open: false,
            unread: 0,
            items: [],
            init() {
                this.load();
                if (window.Echo) {
                    const userId = {{ $currentUser?->id ?? 'null' }};
                    if (userId) {
                        window.Echo.private(`App.Models.User.${userId}`)
                            .listen('NotificationCreated', (e) => {
                                this.unread++;
                                this.items.unshift({
                                    id: e.id,
                                    type: e.type,
                                    title: e.title,
                                    body: e.body,
                                    action_url: e.action_url,
                                    read_at: null,
                                    created_at: 'Just now',
                                });
                                window.dispatchEvent(new CustomEvent('notify', { detail: e.title }));
                            });
                    }
                }
            },
            async load() {
                try {
                    const res = await fetch('{{ route('notifications.index') }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.unread = data.unread_count || 0;
                    this.items = data.notifications || [];
                } catch (e) {
                    console.error('Failed to load notifications', e);
                }
            },
            async markRead(id) {
                // Remove from list immediately so it never shows again
                this.items = this.items.filter(i => i.id !== id);
                this.unread = Math.max(0, this.items.length);

                try {
                    const res = await fetch(`{{ url('/notifications') }}/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (typeof data.unread_count !== 'undefined') {
                            this.unread = data.unread_count;
                        }
                    }
                } catch (e) {}
            },
            async markAllRead() {
                this.items = [];
                this.unread = 0;
                try {
                    await fetch('{{ route('notifications.read-all') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                } catch (e) {}
            }
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            navigator.serviceWorker.register('/sw.js').then(swReg => {
                // Check if already subscribed
                swReg.pushManager.getSubscription().then(subscription => {
                    if (!subscription && Notification.permission !== 'denied') {
                        // We can prompt the user or do it automatically. Let's do it after 2 seconds to not block UI immediately.
                        setTimeout(() => subscribeUser(swReg), 2000);
                    }
                });
            });
        }
    });

    function urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function subscribeUser(swReg) {
        const vapidPublicKey = '{{ env('VAPID_PUBLIC_KEY') }}';
        if (!vapidPublicKey) return;

        swReg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlB64ToUint8Array(vapidPublicKey)
        })
        .then(subscription => {
            // Send subscription to server
            fetch('{{ route('push.subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(subscription)
            });
        })
        .catch(err => console.error('Failed to subscribe to web push.', err));
    }
    </script>

    <!-- Toast Notifications -->
    <div x-data="{ toasts: [] }" 
         x-init="
            @if(session('success'))
                setTimeout(() => $dispatch('notify', '{{ addslashes(session('success')) }}'), 500);
            @endif
            @if(session('error'))
                setTimeout(() => $dispatch('notify', '{{ addslashes(session('error')) }}'), 500);
            @endif
         "
         @notify.window="toasts.push({ id: Date.now(), msg: $event.detail, show: true }); setTimeout(() => toasts.shift(), 4000)"
         class="fixed top-4 right-4 flex flex-col gap-2" style="z-index: 99999;">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-4"
                 class="bg-gradient-to-r from-[#38B91F] to-[#7130D8] text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 border border-white/20">
                <span class="material-symbols-outlined text-white">notifications_active</span>
                <span class="font-bold text-sm" x-text="toast.msg"></span>
            </div>
        </template>
    </div>
    @stack('scripts')
    @include('partials.timezone-script')
    @include('partials.anti_copy')
    @include('partials.content-protection')
    @if(request()->routeIs(
        'student.assignments*',
        'student.quizzes*',
        'tutor.assignments*',
        'tutor.classroom*',
        'admin.assignments*',
        'admin.quizzes*'
    ))
        <x-content-watermark />
    @endif
    {{-- mautic tracking disabled: emails.younimatiq.com has no DNS --}}
</body>
</html>
