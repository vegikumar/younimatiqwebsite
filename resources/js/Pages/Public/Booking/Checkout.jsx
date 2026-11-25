import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { openRazorpayCheckout } from '../../../lib/razorpay';

export default function Checkout({
    booking,
    displayAmount,
    enableStripe = false,
    enablePaypal = false,
    enableRazorpay = false,
    razorpayKey = null,
    paypalClientId = null,
}) {
    const { csrfToken, flash } = usePage().props;
    const defaultMethod = enableStripe ? 'stripe' : enablePaypal ? 'paypal' : enableRazorpay ? 'razorpay' : '';
    const [method, setMethod] = useState(defaultMethod);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

    const methods = useMemo(
        () =>
            [
                enableStripe && { id: 'stripe', label: 'Stripe', desc: 'Cards, Apple Pay & Google Pay' },
                enablePaypal && { id: 'paypal', label: 'PayPal', desc: 'PayPal balance, debit & credit cards' },
                enableRazorpay && { id: 'razorpay', label: 'Razorpay', desc: 'UPI, netbanking, wallets & cards' },
            ].filter(Boolean),
        [enableStripe, enablePaypal, enableRazorpay]
    );

    const payWithPaypal = async () => {
        setError('');
        if (!paypalClientId) {
            setError('PayPal Client ID is missing in Admin → Gateway.');
            return;
        }
        setBusy(true);
        try {
            const res = await fetch(`/booking/paypal/${booking.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error) {
                throw new Error(data.error || 'Unable to create PayPal order');
            }
            if (data.free && data.approval_url) {
                window.location.href = data.approval_url;
                return;
            }
            if (!data.approval_url) {
                throw new Error('PayPal approval URL missing');
            }
            window.location.href = data.approval_url;
        } catch (e) {
            setError(e.message || 'PayPal error');
            setBusy(false);
        }
    };

    const payWithRazorpay = async () => {
        setError('');
        setBusy(true);
        try {
            const res = await fetch(`/booking/razorpay/${booking.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error) {
                throw new Error(data.error || 'Unable to create Razorpay order');
            }
            if (data.status === 'success' && data.booking_id) {
                window.location.href = `/booking/success/${data.booking_id}`;
                return;
            }

            await openRazorpayCheckout({
                key: data.key || razorpayKey,
                amount: data.amount,
                currency: data.currency,
                name: data.name || 'Younimatiq',
                description: data.description,
                orderId: data.order_id,
                prefill: data.prefill || {},
                onSuccess(response) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/booking/razorpay/${booking.id}/verify`;
                    const fields = {
                        _token: csrfToken,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature,
                    };
                    Object.entries(fields).forEach(([k, v]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = k;
                        input.value = v;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
            });
            setBusy(false);
        } catch (e) {
            setError(e.message || 'Payment error');
            setBusy(false);
        }
    };

    return (
        <PublicLayout title="Complete booking payment">
            <Head title="Complete booking payment" />
            <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
                <div className="clay-card w-full max-w-md p-6 md:p-8 space-y-5">
                    <div className="text-center space-y-2">
                        <p className="text-[11px] font-extrabold uppercase tracking-[0.14em] text-primary">Secure payment</p>
                        <h1 className="text-2xl font-extrabold">Complete booking</h1>
                        <p className="text-sm text-on-surface-variant">
                            {booking.camp_name} for {booking.parent_name}
                        </p>
                    </div>

                    {flash?.info ? <div className="p-3 rounded-xl bg-secondary-container text-sm font-medium">{flash.info}</div> : null}
                    {flash?.error || error ? (
                        <div className="p-3 rounded-xl bg-error-container text-error text-sm font-medium">{flash?.error || error}</div>
                    ) : null}

                    <div className="rounded-2xl bg-surface-container-low p-4 flex items-center justify-between">
                        <span className="font-bold text-on-surface-variant">Total due</span>
                        <span className="text-3xl font-extrabold text-primary">{displayAmount}</span>
                    </div>

                    {methods.length === 0 ? (
                        <p className="text-sm text-on-surface-variant text-center">No payment methods available. Contact support.</p>
                    ) : (
                        <div className="space-y-3">
                            <p className="text-sm font-bold">Select payment method</p>
                            {methods.map((m) => (
                                <label
                                    key={m.id}
                                    className={`flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors ${
                                        method === m.id ? 'border-primary bg-primary/5' : 'border-outline-variant/40'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="payment_method_ui"
                                        value={m.id}
                                        checked={method === m.id}
                                        onChange={() => setMethod(m.id)}
                                        className="mt-1"
                                    />
                                    <span>
                                        <span className="block font-bold">{m.label}</span>
                                        <span className="block text-xs text-on-surface-variant">{m.desc}</span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    )}

                    {method === 'razorpay' ? (
                        <button type="button" disabled={busy || !razorpayKey} onClick={payWithRazorpay} className="btn-enroll w-full disabled:opacity-60">
                            {busy ? 'Opening Razorpay…' : `Pay ${displayAmount} with Razorpay`}
                        </button>
                    ) : null}

                    {method === 'paypal' ? (
                        <button type="button" disabled={busy} onClick={payWithPaypal} className="btn-primary w-full disabled:opacity-60">
                            {busy ? 'Redirecting to PayPal…' : `Pay ${displayAmount} with PayPal`}
                        </button>
                    ) : null}

                    {method === 'stripe' ? (
                        <form
                            method="POST"
                            action={`/booking/pay/${booking.id}`}
                            onSubmit={() => setBusy(true)}
                            className="space-y-3"
                        >
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="payment_method" value="stripe" />
                            <button type="submit" disabled={busy} className="btn-enroll w-full disabled:opacity-60">
                                {busy ? 'Redirecting…' : `Pay ${displayAmount} securely`}
                            </button>
                        </form>
                    ) : null}
                </div>
            </div>
        </PublicLayout>
    );
}
