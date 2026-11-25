/**
 * Lock page scroll while a modal is open; keep scrolling inside the modal only.
 */
let lockCount = 0;
let savedScrollY = 0;

export function lockPageScroll() {
    if (typeof document === 'undefined') return;
    if (lockCount === 0) {
        savedScrollY = window.scrollY || window.pageYOffset || 0;
        const body = document.body;
        const html = document.documentElement;
        body.dataset.ymScrollY = String(savedScrollY);
        body.style.position = 'fixed';
        body.style.top = `-${savedScrollY}px`;
        body.style.left = '0';
        body.style.right = '0';
        body.style.width = '100%';
        body.style.overflow = 'hidden';
        html.style.overflow = 'hidden';
        body.classList.add('modal-open');
        html.classList.add('modal-open');
    }
    lockCount += 1;
}

export function unlockPageScroll() {
    if (typeof document === 'undefined') return;
    lockCount = Math.max(0, lockCount - 1);
    if (lockCount > 0) return;

    const body = document.body;
    const html = document.documentElement;
    const y = Number(body.dataset.ymScrollY || savedScrollY || 0);
    body.style.position = '';
    body.style.top = '';
    body.style.left = '';
    body.style.right = '';
    body.style.width = '';
    body.style.overflow = '';
    html.style.overflow = '';
    body.classList.remove('modal-open');
    html.classList.remove('modal-open');
    delete body.dataset.ymScrollY;
    window.scrollTo(0, y);
}

/**
 * Watch #leadModal for .open class changes (idempotent lock/unlock).
 */
export function watchModalScrollLock(selectors = ['#leadModal']) {
    if (typeof document === 'undefined' || typeof MutationObserver === 'undefined') {
        return () => {};
    }

    const lockedFor = new WeakMap();

    const sync = (el) => {
        if (!el) return;
        const isOpen = el.classList.contains('open');
        const wasLocked = lockedFor.get(el) === true;
        if (isOpen && !wasLocked) {
            lockedFor.set(el, true);
            lockPageScroll();
            el.style.overscrollBehavior = 'contain';
        } else if (!isOpen && wasLocked) {
            lockedFor.set(el, false);
            unlockPageScroll();
        }
    };

    const observers = [];
    const cleanups = [];

    const attach = (el) => {
        if (!el || el.dataset.ymScrollWatch === '1') return;
        el.dataset.ymScrollWatch = '1';
        sync(el);
        const obs = new MutationObserver(() => sync(el));
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
        observers.push(obs);
    };

    selectors.forEach((sel) => {
        attach(document.querySelector(sel));
    });

    // In case modal mounts slightly later after HTML inject
    const poll = window.setInterval(() => {
        selectors.forEach((sel) => attach(document.querySelector(sel)));
    }, 300);
    cleanups.push(() => window.clearInterval(poll));

    const onWheel = (e) => {
        const modal = document.querySelector('#leadModal.open');
        if (!modal) return;
        if (!modal.contains(e.target)) {
            e.preventDefault();
            return;
        }
        // Prefer scrolling the form fields area; keep the sticky submit footer fixed
        const formScroll = modal.querySelector('.lead-form-scroll');
        const form = modal.querySelector('#leadForm');
        const preferred = formScroll || form;
        const scroller =
            preferred && preferred.contains(e.target) && preferred.scrollHeight > preferred.clientHeight + 1
                ? preferred
                : modal.scrollHeight > modal.clientHeight + 1
                  ? modal
                  : preferred || modal;
        const atTop = scroller.scrollTop <= 0;
        const atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1;
        if ((e.deltaY < 0 && atTop) || (e.deltaY > 0 && atBottom)) {
            e.preventDefault();
        }
    };

    const onTouchMove = (e) => {
        const modal = document.querySelector('#leadModal.open');
        if (!modal) return;
        if (!modal.contains(e.target)) {
            e.preventDefault();
        }
    };

    document.addEventListener('wheel', onWheel, { capture: true, passive: false });
    document.addEventListener('touchmove', onTouchMove, { capture: true, passive: false });
    cleanups.push(() => {
        document.removeEventListener('wheel', onWheel, { capture: true });
        document.removeEventListener('touchmove', onTouchMove, { capture: true });
    });

    return () => {
        observers.forEach((o) => o.disconnect());
        cleanups.forEach((fn) => fn());
        while (lockCount > 0) unlockPageScroll();
    };
}
