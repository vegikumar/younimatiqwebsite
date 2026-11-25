import './currency';
import Alpine from 'alpinejs';

// Alpine walks the entire DOM on start (~600ms long task on the heavy home page).
// Expose it immediately, but defer the initial scan until the page is interactive
// so it stays out of the critical path / Total Blocking Time window.
if (typeof window !== 'undefined' && !window.Alpine) {
    window.Alpine = Alpine;
    const startAlpine = () => {
        if (window.__ymAlpineStarted) return;
        window.__ymAlpineStarted = true;
        try {
            Alpine.start();
        } catch (e) {
            /* noop */
        }
    };
    // Start promptly so header dropdowns (x-show) work on Inertia Embed pages.
    // Still defer slightly past first paint to avoid competing with LCP.
    const scheduleAlpine = () => setTimeout(startAlpine, 50);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleAlpine, { once: true });
    } else {
        scheduleAlpine();
    }
    window.__ymStartAlpine = startAlpine;
}

const SCROLL_SENSITIVITY = 0.72;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function isEditableElement(el) {
    return el?.closest('input, textarea, select, [contenteditable="true"]');
}

const DROPDOWN_SCROLL_SEL = '.nav-dd-panel-inner, .hero-select-panel-inner';

function isVisibleScroller(node) {
    if (!node || typeof window.getComputedStyle !== 'function') return false;
    const style = window.getComputedStyle(node);
    if (style.display === 'none' || style.visibility === 'hidden' || style.pointerEvents === 'none') {
        return false;
    }
    const panel = node.closest?.('.nav-dd-panel, .hero-select-panel');
    if (panel) {
        const ps = window.getComputedStyle(panel);
        if (ps.display === 'none' || ps.visibility === 'hidden' || ps.pointerEvents === 'none') {
            return false;
        }
    }
    return true;
}

/** Walk ancestors and return the first visible dropdown panel that can actually scroll. */
function getDropdownScroller(el) {
    let node = el;
    let depth = 0;
    while (node && node !== document.body && depth < 16) {
        const isDropdown =
            node.matches?.(DROPDOWN_SCROLL_SEL) ||
            (node.hasAttribute?.('data-scroll-native') &&
                node.classList?.contains('nav-dd-panel-inner')) ||
            (node.hasAttribute?.('data-scroll-native') &&
                node.classList?.contains('hero-select-panel-inner'));

        if (
            isDropdown &&
            isVisibleScroller(node) &&
            node.scrollHeight > node.clientHeight + 1
        ) {
            return node;
        }
        node = node.parentElement;
        depth += 1;
    }
    return null;
}

function getScrollableParent(el) {
    // Prefer the document scroller on marketing pages to avoid forced reflows
    // from walking the DOM with getComputedStyle on every wheel event.
    if (!el || el === document.body || el === document.documentElement) {
        return document.scrollingElement || document.documentElement;
    }

    const dropdown = getDropdownScroller(el);
    if (dropdown && dropdown.scrollHeight > dropdown.clientHeight + 1) {
        return dropdown;
    }

    let node = el;
    let depth = 0;

    while (node && node !== document.body && depth < 8) {
        depth += 1;
        // Only check overflow via style attribute / class heuristics first
        const overflowY = node.style?.overflowY;
        if (
            (overflowY === 'auto' || overflowY === 'scroll') &&
            node.scrollHeight > node.clientHeight + 1
        ) {
            return node;
        }

        // data attribute opt-in for nested scroll areas
        if (node.hasAttribute?.('data-scroll-native') || node.hasAttribute?.('data-horizontal-scroll')) {
            if (node.scrollHeight > node.clientHeight + 1) {
                return node;
            }
        }

        // Known CSS-class scroll containers (nav / hero dropdowns)
        if (node.matches?.(DROPDOWN_SCROLL_SEL) && node.scrollHeight > node.clientHeight + 1) {
            return node;
        }

        node = node.parentElement;
    }

    return document.scrollingElement || document.documentElement;
}

function canScrollVertically(node, delta) {
    if (delta < 0) {
        return node.scrollTop > 0;
    }

    return node.scrollTop + node.clientHeight < node.scrollHeight - 1;
}

function initHorizontalScrollSliders() {
    document.querySelectorAll('[data-horizontal-scroll]').forEach((container) => {
        if (container.dataset.horizontalScrollInit) {
            return;
        }

        container.dataset.horizontalScrollInit = 'true';

        container.addEventListener(
            'wheel',
            (evt) => {
                if (evt.deltaY === 0 || Math.abs(evt.deltaX) >= Math.abs(evt.deltaY)) {
                    return;
                }

                evt.preventDefault();
                container.scrollLeft += evt.deltaY * SCROLL_SENSITIVITY;
            },
            { passive: false }
        );
    });
}

function initScrollDampening() {
    if (prefersReducedMotion()) {
        return;
    }

    // Safari wheel hijacking causes hero overlap/jank on the marketing homepage.
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    if (isSafari) {
        return;
    }

    document.addEventListener(
        'wheel',
        (evt) => {
            // Never hijack scroll while consultation / lead modal is open —
            // let the modal form scroll natively without moving the page.
            if (document.getElementById('leadModal')?.classList.contains('open')) {
                return;
            }

            if (evt.ctrlKey || isEditableElement(evt.target)) {
                return;
            }

            if (evt.target.closest('[data-horizontal-scroll]')) {
                return;
            }

            if (evt.deltaY === 0) {
                return;
            }

            // Header / hero dropdowns: use native wheel scrolling (smoother).
            // Only block the event at the edges so the page does not chain-scroll.
            const dropdownScroller = getDropdownScroller(evt.target);
            if (dropdownScroller) {
                if (!canScrollVertically(dropdownScroller, evt.deltaY)) {
                    evt.preventDefault();
                    evt.stopPropagation();
                }
                return;
            }

            if (evt.target.closest('[data-scroll-native]')) {
                return;
            }

            const scrollNode = getScrollableParent(evt.target);

            if (!canScrollVertically(scrollNode, evt.deltaY)) {
                return;
            }

            evt.preventDefault();
            scrollNode.scrollTop += evt.deltaY * SCROLL_SENSITIVITY;
        },
        { passive: false, capture: true }
    );
}

/** Install dropdown scroll guard immediately (do not wait for idle dampening). */
function initDropdownScrollGuard() {
    document.addEventListener(
        'wheel',
        (evt) => {
            if (evt.deltaY === 0 || evt.ctrlKey) return;
            const dropdownScroller = getDropdownScroller(evt.target);
            if (!dropdownScroller) return;
            if (!canScrollVertically(dropdownScroller, evt.deltaY)) {
                evt.preventDefault();
                evt.stopPropagation();
            }
        },
        { passive: false, capture: true }
    );
}

/**
 * Lazy-load marquee/project videos only when near the viewport.
 * Videos use data-src (no src) until intersecting → avoids multi-MB eager downloads.
 */
function initLazyProjectVideos() {
    const videos = document.querySelectorAll('video[data-lazy-src]');
    if (!videos.length) {
        return;
    }

    const loadAndPlay = (video) => {
        const src = video.dataset.lazySrc;
        if (!src) {
            return;
        }
        if (!video.getAttribute('src')) {
            video.src = src.includes('#') ? src : `${src}#t=0.001`;
            video.load();
        }
        const placeholder = video.parentElement?.querySelector('.lazy-video-placeholder');
        video.play()
            .then(() => {
                if (placeholder) {
                    placeholder.remove();
                }
            })
            .catch(() => {});
    };

    const unload = (video) => {
        if (!video.getAttribute('src')) {
            return;
        }
        video.pause();
        // Keep src once loaded so marquee clones do not re-fetch constantly;
        // pause is enough to stop decode when off-screen.
    };

    if (!('IntersectionObserver' in window)) {
        videos.forEach(loadAndPlay);
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const video = entry.target;
                if (entry.isIntersecting) {
                    loadAndPlay(video);
                } else {
                    unload(video);
                }
            });
        },
        { root: null, rootMargin: '200px 0px', threshold: 0.01 }
    );

    videos.forEach((video) => observer.observe(video));
}

/**
 * Hero background video is decorative: the poster is the LCP. Start the video
 * only after the first user interaction (or a long idle fallback) so its decode
 * never competes with first paint / Total Blocking Time on slow devices.
 */
function initHeroVideo() {
    const video = document.getElementById('heroProfileVideo');
    if (!video || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const sources = video.querySelectorAll('source[data-hero-src]');
    if (!sources.length) {
        return;
    }

    let started = false;
    const load = () => {
        if (started) {
            return;
        }
        started = true;
        ['scroll', 'pointerdown', 'mousedown', 'touchstart', 'keydown'].forEach((evt) =>
            window.removeEventListener(evt, load)
        );
        sources.forEach((source) => {
            source.src = source.dataset.heroSrc;
        });
        video.preload = 'auto';
        video.load();
        const play = video.play();
        if (play && typeof play.catch === 'function') {
            play.catch(() => {});
        }
    };

    ['scroll', 'pointerdown', 'mousedown', 'touchstart', 'keydown'].forEach((evt) =>
        window.addEventListener(evt, load, { once: true, passive: true })
    );
    // Fallback for users who never interact (e.g. long dwell on the hero).
    setTimeout(load, 6000);
}

function init() {
    initHorizontalScrollSliders();
    initLazyProjectVideos();
    // Guard nav/hero dropdowns immediately so Grades/Courses scroll before idle work.
    initDropdownScrollGuard();
    // initHeroVideo();

    const scheduleDampening = () => initScrollDampening();
    if ('requestIdleCallback' in window) {
        requestIdleCallback(scheduleDampening, { timeout: 3000 });
    } else {
        setTimeout(scheduleDampening, 1500);
    }
}

// Allow Inertia Embed to re-run lazy video wiring after Blade HTML inject.
if (typeof window !== 'undefined') {
    window.__ymInitLazyVideos = initLazyProjectVideos;
    window.__ymInitMarketing = init;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
