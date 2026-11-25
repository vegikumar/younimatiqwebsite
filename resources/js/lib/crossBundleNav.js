import { router } from '@inertiajs/react';

/**
 * Public app.jsx and portal app-portal.jsx resolve different page trees.
 * Any Inertia visit that crosses those shells must be a full document load
 * so the correct Vite entry (and page map) is used.
 */

const PORTAL_PREFIX = /^\/(admin|tutor|student|parent)(\/|$)/i;

/** Routes served by the public shell even if the path mentions student/payment/etc. */
const PUBLIC_PATHS = [
    /^\/checkout(\/|$)/i,
    /^\/payment(\/|$)/i,
    /^\/login(\/|$)/i,
    /^\/register(\/|$)/i,
    /^\/logout(\/|$)/i,
    /^\/impersonate(\/|$)/i,
    /^\/book-/i,
    /^\/booking(\/|$)/i,
    /^\/custom-enroll(\/|$)/i,
];

export function isPortalPath(pathname) {
    const path = pathname || '/';
    if (PUBLIC_PATHS.some((re) => re.test(path))) {
        return false;
    }
    return PORTAL_PREFIX.test(path);
}

export function installCrossBundleNavigation() {
    if (typeof window === 'undefined' || window.__ymCrossBundleNavInstalled) {
        return;
    }
    window.__ymCrossBundleNavInstalled = true;

    router.on('before', (event) => {
        try {
            const visit = event.detail?.visit;
            if (!visit?.url) return;

            const method = String(visit.method || 'get').toLowerCase();
            // Only rewrite safe GET navigations. POSTs (logout, forms) keep Inertia;
            // server redirects that cross bundles are handled on the follow-up GET
            // or via Inertia::location on the backend.
            if (method !== 'get') return;

            const target = new URL(visit.url, window.location.origin);
            if (target.origin !== window.location.origin) return;

            const fromPortal = isPortalPath(window.location.pathname);
            const toPortal = isPortalPath(target.pathname);
            if (fromPortal === toPortal) return;

            event.preventDefault();
            window.location.assign(target.pathname + target.search + target.hash);
        } catch {
            // Never block navigation on helper failure.
        }
    });
}
