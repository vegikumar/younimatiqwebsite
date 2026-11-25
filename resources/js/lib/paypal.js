/**
 * Load the PayPal JS SDK once per page, then render Buttons into a container.
 */
export function loadPaypalSdk({ clientId, currency = 'USD' }) {
    return new Promise((resolve, reject) => {
        if (!clientId) {
            reject(new Error('PayPal Client ID is not configured.'));
            return;
        }

        const code = String(currency || 'USD').toUpperCase();
        const supported = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        const currencyCode = supported.includes(code) ? code : 'USD';

        if (window.paypal && window.__paypalSdkCurrency === currencyCode) {
            resolve(window.paypal);
            return;
        }

        const existing = document.querySelector('script[data-paypal-sdk]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.paypal));
            existing.addEventListener('error', () => reject(new Error('Failed to load PayPal SDK')));
            return;
        }

        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currencyCode)}`;
        script.async = true;
        script.dataset.paypalSdk = '1';
        script.onload = () => {
            window.__paypalSdkCurrency = currencyCode;
            if (!window.paypal) {
                reject(new Error('PayPal SDK loaded without paypal global'));
                return;
            }
            resolve(window.paypal);
        };
        script.onerror = () => reject(new Error('Failed to load PayPal SDK'));
        document.body.appendChild(script);
    });
}

export async function renderPaypalButtons({
    container,
    clientId,
    currency = 'USD',
    createOrder,
    onApprove,
    onError,
    onCancel,
    onClick,
}) {
    const paypal = await loadPaypalSdk({ clientId, currency });
    if (!container) {
        throw new Error('PayPal button container missing');
    }
    container.innerHTML = '';

    return paypal
        .Buttons({
            style: { layout: 'vertical', shape: 'rect', label: 'paypal' },
            onClick: onClick || undefined,
            createOrder,
            onApprove,
            onError: onError || ((err) => console.error('PayPal error', err)),
            onCancel: onCancel || undefined,
        })
        .render(container);
}
