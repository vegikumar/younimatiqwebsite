/**
 * Move fixed overlays out of the Inertia content tree onto document.body
 * so position:fixed + overflow scrolling work (modals, mobile menu).
 */
export function portalOverlays(root) {
    if (!root || typeof document === 'undefined') return;

    const selectors = ['#leadModal', '#menuOverlay', '#enrollModal'];
    selectors.forEach((sel) => {
        const el = root.querySelector(sel);
        if (!el) return;
        if (el.parentElement === document.body) return;
        document.body.appendChild(el);
    });
}

export function cleanupPortaledOverlays() {
    // Keep lead/menu on body across Inertia visits; re-inject replaces them.
}
