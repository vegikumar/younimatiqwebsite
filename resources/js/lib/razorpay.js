const RAZORPAY_SRC = 'https://checkout.razorpay.com/v1/checkout.js';
const LOAD_TIMEOUT_MS = 12000;

function ensurePreconnect() {
    if (typeof document === 'undefined') return;
    const hrefs = ['https://checkout.razorpay.com', 'https://api.razorpay.com', 'https://cdn.razorpay.com'];
    hrefs.forEach((href) => {
        if (document.querySelector(`link[data-razorpay-preconnect="${href}"]`)) return;
        const link = document.createElement('link');
        link.rel = 'preconnect';
        link.href = href;
        link.crossOrigin = 'anonymous';
        link.dataset.razorpayPreconnect = href;
        document.head.appendChild(link);
    });
}

function loadFailureError() {
    return new Error(
        'Failed to load Razorpay. Allow checkout.razorpay.com (disable ad blockers / Brave Shields for this site) and try again.'
    );
}

function waitForExistingScript(script) {
    return new Promise((resolve, reject) => {
        if (window.Razorpay) {
            resolve(window.Razorpay);
            return;
        }
        if (script.dataset.razorpayFailed === '1') {
            reject(loadFailureError());
            return;
        }

        let settled = false;
        const timer = window.setTimeout(() => {
            if (settled) return;
            settled = true;
            if (window.Razorpay) resolve(window.Razorpay);
            else reject(new Error('Timed out loading Razorpay. Check your network and try again.'));
        }, LOAD_TIMEOUT_MS);

        const finish = (ok) => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timer);
            if (ok && window.Razorpay) resolve(window.Razorpay);
            else reject(loadFailureError());
        };

        script.addEventListener('load', () => finish(true), { once: true });
        script.addEventListener(
            'error',
            () => {
                script.dataset.razorpayFailed = '1';
                finish(false);
            },
            { once: true }
        );
    });
}

function injectScript() {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = RAZORPAY_SRC;
        script.async = true;
        script.dataset.razorpaySdk = '1';

        let settled = false;
        const timer = window.setTimeout(() => {
            if (settled) return;
            settled = true;
            script.dataset.razorpayFailed = '1';
            reject(new Error('Timed out loading Razorpay. Check your network and try again.'));
        }, LOAD_TIMEOUT_MS);

        script.onload = () => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timer);
            if (!window.Razorpay) {
                script.dataset.razorpayFailed = '1';
                reject(new Error('Razorpay SDK loaded without Razorpay global'));
                return;
            }
            resolve(window.Razorpay);
        };
        script.onerror = () => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timer);
            script.dataset.razorpayFailed = '1';
            reject(loadFailureError());
        };

        (document.head || document.body || document.documentElement).appendChild(script);
    });
}

export function loadRazorpayScript() {
    ensurePreconnect();

    return (async () => {
        if (typeof window === 'undefined') {
            throw new Error('Razorpay can only load in the browser');
        }
        if (window.Razorpay) return window.Razorpay;

        let lastError = null;
        for (let attempt = 0; attempt < 2; attempt += 1) {
            try {
                if (window.Razorpay) return window.Razorpay;

                const existing = document.querySelector('script[data-razorpay-sdk]');
                if (existing) {
                    if (existing.dataset.razorpayFailed === '1') {
                        existing.remove();
                    } else {
                        return await waitForExistingScript(existing);
                    }
                }

                return await injectScript();
            } catch (err) {
                lastError = err;
                // Drop a failed tag so the next attempt can inject a fresh script.
                document.querySelectorAll('script[data-razorpay-sdk][data-razorpay-failed="1"]').forEach((el) => el.remove());
            }
        }
        throw lastError || loadFailureError();
    })();
}

/** Warm the SDK early on checkout pages so the pay click is less likely to fail. */
export function preloadRazorpayScript() {
    try {
        loadRazorpayScript().catch(() => {});
    } catch {
        // ignore warm failures; pay click will surface the error
    }
}

export async function openRazorpayCheckout({
    key,
    amount,
    currency,
    name = 'Younimatiq',
    description,
    orderId,
    prefill = {},
    themeColor = '#7130D8',
    onSuccess,
    onFailure,
}) {
    const Razorpay = await loadRazorpayScript();
    const amountSubunits = Number(amount);
    if (!key || !orderId || !Number.isFinite(amountSubunits) || amountSubunits < 1) {
        throw new Error('Invalid Razorpay checkout parameters');
    }

    return new Promise((resolve, reject) => {
        const rzp = new Razorpay({
            key,
            amount: Math.round(amountSubunits),
            currency: String(currency || 'INR').toUpperCase(),
            name,
            description: description || 'Payment',
            order_id: orderId,
            prefill,
            theme: { color: themeColor },
            handler(response) {
                try {
                    onSuccess?.(response);
                    resolve(response);
                } catch (e) {
                    reject(e);
                }
            },
        });
        rzp.on('payment.failed', (resp) => {
            const message = resp?.error?.description || 'Payment failed';
            onFailure?.(resp);
            reject(new Error(message));
        });
        rzp.open();
    });
}
