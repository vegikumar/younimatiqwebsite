<style>
    /* Custom Header CSS — visual theme only */
    .custom-header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(255, 253, 248, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(113, 48, 216, 0.08);
      box-shadow: 0 1px 0 rgba(31, 22, 53, 0.03);
    }

    .custom-header .logo-img {
      background: transparent;
      border-radius: 0;
      padding: 0;
    }

    .custom-header .desktop-nav {
      display: none;
      align-items: center;
      gap: 24px;
      font-weight: 700;
      font-size: 16px;
      font-family: 'Nunito', sans-serif;
      color: var(--color-on-surface);
    }

    .custom-header .desktop-nav a {
      transition: color 0.2s ease;
    }

    /* Colored hover accents (nav stays neutral by default) */
    .custom-header .desktop-nav > a:nth-of-type(1):hover { color: #087FF5; } /* Home → Blue */
    .custom-header .desktop-nav > a:nth-of-type(2):hover { color: #38B91F; } /* Community → Green */
    .custom-header .desktop-nav > div:nth-of-type(1) > a:hover { color: #FF9800; } /* Courses → Orange */
    .custom-header .desktop-nav > div:nth-of-type(2) > a:hover { color: #FFC400; } /* Age Groups → Yellow */
    .custom-header .desktop-nav > a:nth-of-type(3):hover { color: #F52B76; } /* About → Pink */
    .custom-header .desktop-nav > a:nth-of-type(4):hover { color: #7130D8; } /* Contact → Purple */

    @media (min-width: 900px) {
      .custom-header .desktop-nav { display: flex; }
      .custom-header #menuBtn { display: none; }
    }

    .custom-header .header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 0 max(3%, calc((100% - 1440px) / 2));
      height: 72px;
    }

    @media (min-width: 900px) {
      .custom-header .header-row {
        padding: 18px max(4%, calc((100% - 1440px) / 2));
        height: 112px;
        gap: 20px;
      }
    }

    .custom-header .logo-img {
      height: 48px;
      width: auto;
      max-width: min(52vw, 180px);
      object-fit: contain;
    }

    @media (min-width: 900px) {
      .custom-header .logo-img {
        height: 72px;
        max-width: 200px;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 12px;
        padding: 4px 10px;
      }
    }

    .custom-header .header-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }

    @media (min-width: 900px) {
      .custom-header .header-actions { gap: 16px; }
    }

    .custom-header .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(113, 48, 216, 0.08);
      color: #7130D8;
      border: none;
      font-size: 24px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }

    .custom-header .icon-btn:hover {
      background: rgba(113, 48, 216, 0.16);
      transform: translateY(-1px);
    }

    .custom-header .btn-sm {
      padding: 8px 12px;
      font-size: 12px;
      white-space: nowrap;
      line-height: 1.2;
    }

    @media (min-width: 480px) {
      .custom-header .btn-sm {
        padding: 10px 18px;
        font-size: 13px;
      }
    }

    @media (min-width: 900px) {
      .custom-header .btn-sm {
        padding: 10px 24px;
        font-size: 14px;
      }
    }

    .custom-header .book-cta .book-cta-short { display: inline; }
    .custom-header .book-cta .book-cta-full { display: none; }

    @media (min-width: 400px) {
      .custom-header .book-cta .book-cta-short { display: none; }
      .custom-header .book-cta .book-cta-full { display: inline; }
    }

    /* Mobile Menu CSS */
    .menu-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(10, 16, 32, .4);
      backdrop-filter: blur(4px);
      z-index: 100;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .menu-overlay.open {
      opacity: 1;
      visibility: visible;
    }

    .menu-panel {
      position: absolute;
      top: 0; right: -320px;
      width: 320px;
      height: 100%;
      background: #FFFDF8;
      box-shadow: -4px 0 24px rgba(113, 48, 216, 0.12);
      display: flex;
      flex-direction: column;
      padding: 80px 24px 24px;
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.05);
    }

    .menu-overlay.open .menu-panel {
      transform: translateX(-320px);
    }

    .menu-panel a {
      padding: 16px 0;
      font-size: 18px;
      font-weight: 700;
      color: var(--color-on-surface);
      border-bottom: 1px solid rgba(113, 48, 216, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-decoration: none;
      transition: color 0.2s;
    }

    .menu-panel > a:nth-of-type(1):hover { color: #087FF5; }
    .menu-panel > a:nth-of-type(2):hover { color: #38B91F; }

    .menu-panel .mclose {
      position: absolute;
      top: 24px;
      right: 24px;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: rgba(113, 48, 216, 0.1);
      color: #7130D8;
      border: none;
      font-size: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
</style>

<header class="custom-header">
    <div class="header-row">
        <a href="{{ route('home') }}" aria-label="Younimatiq home" style="display: flex; align-items: center;">
            <video 
                id="logoVideoAnimation"
                src="{{ asset('videos/younimatiq-logo-anim.mp4') }}"
                class="logo-img object-contain"
                width="240"
                height="80"
                muted
                playsinline
                preload="none"
                poster="{{ asset('images/younimatiq-logo-poster.webp') }}"
                style="pointer-events: none;"
            ></video>
        </a>
        <script>
            (function () {
                function bootLogoVideo() {
                    var logoVid = document.getElementById('logoVideoAnimation');
                    if (!logoVid || logoVid.dataset.ymBooted === '1') return;
                    logoVid.dataset.ymBooted = '1';
                    // Defer decorative logo animation until after first paint so it
                    // does not compete with the hero LCP on slow devices.
                    var started = false;
                    function start() {
                        if (started) return;
                        started = true;
                        logoVid.preload = 'auto';
                        var p = logoVid.play();
                        if (p && p.catch) p.catch(function () {});
                    }
                    function schedule() {
                        if ('requestIdleCallback' in window) {
                            requestIdleCallback(start, { timeout: 2500 });
                        } else {
                            setTimeout(start, 1500);
                        }
                    }
                    if (document.readyState === 'complete') schedule();
                    else window.addEventListener('load', schedule, { once: true });
                    // Inertia Embed injects after load — still start promptly.
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                        setTimeout(start, 300);
                    }
                    logoVid.addEventListener('ended', function () {
                        setTimeout(function () { logoVid.play().catch(function () {}); }, 5000);
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootLogoVideo, { once: true });
                } else {
                    bootLogoVideo();
                }
            })();
        </script>
        <nav class="desktop-nav">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('community') }}">Community</a>
            <a href="{{ route('pricing') }}">Pricing</a>
            <a href="{{ route('blog.index') }}">Blog</a>
            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                <a href="{{ route('courses.index') }}" class="flex items-center gap-1" style="display: flex; align-items: center; gap: 4px; cursor: pointer;">
                    Courses
                    <span class="material-symbols-outlined text-[10px] transition-transform duration-200 leading-none" :class="{'rotate-180': open}">expand_more</span>
                </a>
                <div x-show="open" x-transition.opacity.duration.200ms class="absolute top-full left-0 mt-4 bg-white rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-outline-variant py-4 px-4 overflow-hidden" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 16px; width: 480px; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; z-index: 100;">
                    <a href="{{ route('courses.index') }}" style="display: block; padding: 0 12px 12px; font-size: 15px; font-weight: 700; color: var(--color-primary); border-bottom: 1px solid #f1f5f9; text-decoration: none; margin-bottom: 8px;">View All Courses</a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px;">
                        @if(isset($navCategories))
                            @foreach($navCategories as $category)
                                <a href="{{ route('courses.category', $category->slug) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; font-size: 15px; color: var(--color-on-surface); text-decoration: none; font-weight: 600; border-radius: 8px; transition: color 0.2s, background 0.2s;" onmouseover="this.style.color='var(--color-primary)'; this.style.backgroundColor='#f8fafc';" onmouseout="this.style.color='var(--color-on-surface)'; this.style.backgroundColor='transparent';">
                                    <span>{{ $category->name }}</span>
                                    <span style="font-size: 12px; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 999px;">{{ $category->courses_count }}</span>
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                <a href="{{ route('courses.index') }}" class="flex items-center gap-1" style="display: flex; align-items: center; gap: 4px; cursor: pointer;">
                    Grades
                    <span class="material-symbols-outlined text-[10px] transition-transform duration-200 leading-none" :class="{'rotate-180': open}">expand_more</span>
                </a>
                <div x-show="open" x-transition.opacity.duration.200ms class="absolute top-full left-0 mt-4 w-64 bg-white rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-outline-variant py-2 overflow-hidden" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 16px; width: 256px; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; padding: 8px 0; z-index: 100;">
                    @if(isset($gradeBands))
                        @foreach($gradeBands as $value => $band)
                            <a href="{{ route('grade-group', ['grade' => $value]) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; font-size: 15px; color: var(--color-on-surface); text-decoration: none; font-weight: 600; transition: color 0.2s, background 0.2s;" onmouseover="this.style.color='var(--color-primary)'; this.style.backgroundColor='#f8fafc';" onmouseout="this.style.color='var(--color-on-surface)'; this.style.backgroundColor='transparent';">
                                <span>{{ $band['label'] }}</span>
                                <span style="font-size: 12px; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 999px;">{{ $band['courses_count'] ?? 0 }}</span>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
            <a href="{{ route('about') }}">About Us</a>
            <a href="{{ route('contact') }}">Contact</a>
        </nav>
        <div class="header-actions">
            @auth('web')
                <a class="btn btn-primary btn-sm" href="{{ route('student.dashboard') }}">Dashboard</a>
                <form action="{{ route('logout.student') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">Logout</button>
                </form>
            @else
                <a class="btn btn-outline-primary btn-sm header-login-btn" href="{{ route('login.student') }}">Log In</a>
                <a class="btn btn-primary btn-sm book-cta" href="#leadModal" onclick="openLeadModal(event, 'free_class')" style="cursor:pointer;"><span class="book-cta-short">Book free</span><span class="book-cta-full">Book a free trial class</span></a>
            @endauth
            <button aria-label="Open menu" class="icon-btn" id="menuBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</header>
<style>
.custom-header .header-login-btn {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  background: #ffffff !important;
  color: #7130D8 !important;
  border: 2px solid #7130D8 !important;
  box-shadow: 0 4px 0 #4D1FA3 !important;
  border-radius: 9999px !important;
  font-weight: 800;
  white-space: nowrap;
}
.custom-header .header-login-btn:hover {
  background: #7130D8 !important;
  color: #ffffff !important;
  border-color: #7130D8 !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 0 #4D1FA3 !important;
}
@media (max-width: 639px) {
  .custom-header .header-login-btn { display: none !important; }
}
</style>

<div class="menu-overlay" id="menuOverlay">
    <nav class="menu-panel" x-data="{ coursesOpen: false, gradesOpen: false }" style="overflow-y: auto;">
        <button class="mclose" id="menuClose" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <a href="{{ route('home') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">Home <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        <a href="{{ route('community') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">Community <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        <a href="{{ route('pricing') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">Pricing <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        <a href="{{ route('blog.index') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">Blog <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        
        <div class="w-full">
            <button @click="coursesOpen = !coursesOpen" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 16px 0; font-size: 18px; font-weight: 700; color: var(--color-on-surface); border-bottom: 1px solid rgba(193, 198, 215, .3); background: transparent; border-top: none; border-left: none; border-right: none; cursor: pointer; transition: background 0.2s;">
                Courses <span class="material-symbols-outlined text-sm transition-transform duration-300" :style="coursesOpen ? 'transform: rotate(180deg);' : ''">expand_more</span>
            </button>
            <div x-show="coursesOpen" x-transition.opacity.duration.300ms style="display: none; padding: 16px 0 8px 0;">
                <a href="{{ route('courses.index') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')" style="border-bottom: none; padding: 0 0 16px 0; font-size: 16px; color: var(--color-primary);">View All Courses</a>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    @if(isset($navCategories))
                        @foreach($navCategories as $category)
                        <a href="{{ route('courses.category', $category->slug) }}" onclick="document.getElementById('menuOverlay').classList.remove('open')" style="display: flex; justify-content: space-between; border-bottom: none; padding: 8px 0; font-size: 15px; font-weight: 500; color: #64748b;">
                            <span>{{ $category->name }}</span>
                            <span>({{ $category->courses_count }})</span>
                        </a>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="w-full">
            <button @click="gradesOpen = !gradesOpen" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 16px 0; font-size: 18px; font-weight: 700; color: var(--color-on-surface); border-bottom: 1px solid rgba(193, 198, 215, .3); background: transparent; border-top: none; border-left: none; border-right: none; cursor: pointer; transition: background 0.2s;">
                Grades <span class="material-symbols-outlined text-sm transition-transform duration-300" :style="gradesOpen ? 'transform: rotate(180deg);' : ''">expand_more</span>
            </button>
            <div x-show="gradesOpen" x-transition.opacity.duration.300ms style="display: none; padding-left: 16px; padding-bottom: 8px;">
                @if(isset($gradeBands))
                    @foreach($gradeBands as $value => $band)
                        <a href="{{ route('grade-group', ['grade' => $value]) }}" onclick="document.getElementById('menuOverlay').classList.remove('open')" style="display: flex; justify-content: space-between; border-bottom: none; padding: 8px 0; font-size: 15px; font-weight: 500; color: #64748b;">
                            <span>{{ $band['label'] }}</span>
                            <span>({{ $band['courses_count'] ?? 0 }})</span>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        <a href="{{ route('about') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">About Us <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        <a href="{{ route('contact') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')">Contact <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        
        @auth('web')
            <a href="{{ route('student.dashboard') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')" style="margin-top: 12px; color: var(--color-primary);">Dashboard <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        @else
            <a href="{{ route('login.student') }}" onclick="document.getElementById('menuOverlay').classList.remove('open')" style="margin-top: 12px; color: var(--color-primary);">Login <span class="material-symbols-outlined text-sm">chevron_right</span></a>
        @endauth
        <a class="btn btn-primary btn-block" href="#leadModal" onclick="openLeadModal(event, 'free_class')" style="margin-top: 12px; text-decoration: none; justify-content: center; padding: 12px 0;">Book a free trial class</a>
    </nav>
</div>

<script>
    (function () {
        function bootMobileMenu() {
            const menuBtn = document.getElementById('menuBtn');
            const menuClose = document.getElementById('menuClose');
            const menuOverlay = document.getElementById('menuOverlay');
            if (!menuBtn || !menuClose || !menuOverlay || menuBtn.dataset.ymBound === '1') return;
            menuBtn.dataset.ymBound = '1';
            menuBtn.addEventListener('click', () => menuOverlay.classList.add('open'));
            menuClose.addEventListener('click', () => menuOverlay.classList.remove('open'));
            menuOverlay.addEventListener('click', (e) => {
                if (e.target === menuOverlay) menuOverlay.classList.remove('open');
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootMobileMenu, { once: true });
        } else {
            bootMobileMenu();
        }
    })();
</script>
