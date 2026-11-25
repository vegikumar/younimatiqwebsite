/**
 * After Inertia Embed injects Blade HTML, re-start decorative videos that
 * relied on DOMContentLoaded (already fired) or marketing.js first-pass init.
 */
export function reviveDecorativeMedia(root = document) {
    const scope = root || document;

    const playLoop = (video, delayMs = 5000) => {
        if (!video || video.dataset.ymRevived === '1') return;
        video.dataset.ymRevived = '1';
        video.muted = true;
        video.setAttribute('playsinline', '');
        const start = () => {
            const p = video.play();
            if (p && typeof p.catch === 'function') p.catch(() => {});
        };
        start();
        if (!video.dataset.ymLoopBound) {
            video.dataset.ymLoopBound = '1';
            video.addEventListener('ended', () => {
                setTimeout(start, delayMs);
            });
        }
    };

    const headerLogo = scope.querySelector('#logoVideoAnimation');
    if (headerLogo) {
        headerLogo.preload = 'auto';
        playLoop(headerLogo);
    }

    scope.querySelectorAll('video[data-lazy-src]').forEach((video) => {
        const src = video.dataset.lazySrc;
        if (src && !video.getAttribute('src')) {
            video.src = src.includes('#') ? src : `${src}#t=0.001`;
            video.load();
        }
        playLoop(video);
    });

    // Hero background (home): load deferred sources if present
    const hero = scope.querySelector('#heroProfileVideo');
    if (hero && !hero.dataset.ymRevived) {
        const sources = hero.querySelectorAll('source[data-hero-src]');
        if (sources.length) {
            hero.dataset.ymRevived = '1';
            const load = () => {
                sources.forEach((source) => {
                    if (source.dataset.heroSrc) source.src = source.dataset.heroSrc;
                });
                hero.preload = 'auto';
                hero.load();
                const p = hero.play();
                if (p && typeof p.catch === 'function') p.catch(() => {});
            };
            setTimeout(load, 800);
        }
    }

    if (typeof window.__ymInitLazyVideos === 'function') {
        try {
            window.__ymInitLazyVideos();
        } catch {
            // ignore
        }
    }
}
