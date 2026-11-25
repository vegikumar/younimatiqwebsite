import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PublicLayout from '../../Layouts/PublicLayout';
import { openRazorpayCheckout, preloadRazorpayScript } from '../../lib/razorpay';

const CURRENCY_SYMBOLS = { USD: '$', AED: 'د.إ', INR: '₹' };

export default function Checkout({
    course,
    enableStripe,
    enablePaypal,
    enableRazorpay,
    paypalClientId = null,
    paypalCurrency = 'USD',
    razorpayKey = null,
    originalPrice,
    sessionCurrency,
    couponCode: initialCoupon = '',
    discountAmount = 0,
    couponError,
    siblingDiscount = null,
    bundleDiscount = null,
    bundles = [],
    deliveryModes = [],
    selectedDeliveryMode,
    numberOfClasses,
    classesCount = 0,
}) {
    const { auth, csrfToken, flash } = usePage().props;
    const isLoggedIn = auth?.guard === 'web' && Boolean(auth?.user);
    const currency = String(sessionCurrency || paypalCurrency || 'USD').toUpperCase();
    const symbol = CURRENCY_SYMBOLS[currency] || '$';
    const payableClasses = classesCount > 0 ? classesCount : numberOfClasses;
    const finalAmount = Math.max(0, Number(originalPrice || 0) - Number(discountAmount || 0));
    const displayAmount = finalAmount.toFixed(2);

    const defaultGateway = enableStripe ? 'stripe' : enablePaypal ? 'paypal' : enableRazorpay ? 'razorpay' : '';
    const form = useForm({
        coupon_code: initialCoupon || '',
        delivery_mode: selectedDeliveryMode || deliveryModes[0] || 'live',
        payment_method: defaultGateway,
        timezone: typeof Intl !== 'undefined' ? Intl.DateTimeFormat().resolvedOptions().timeZone : 'UTC',
        currency,
        classes: payableClasses,
        name: '',
        email: '',
        password: '',
    });
    const postUrl = `/checkout/${course.id}`;
    const [razorpayBusy, setRazorpayBusy] = useState(false);
    const [paypalBusy, setPaypalBusy] = useState(false);
    const [localError, setLocalError] = useState('');

    useEffect(() => {
        if (enableRazorpay) preloadRazorpayScript();
    }, [enableRazorpay]);

    const checkoutPayload = () => ({
        coupon_code: form.data.coupon_code || null,
        delivery_mode: form.data.delivery_mode,
        timezone: form.data.timezone,
        currency: form.data.currency || currency,
        classes: Number(form.data.classes) || payableClasses,
        ...(isLoggedIn
            ? { is_logged_in: '1' }
            : {
                  name: form.data.name,
                  email: form.data.email,
                  password: form.data.password,
              }),
    });

    const requireGuestFields = () => {
        if (isLoggedIn) return true;
        if (!form.data.name?.trim() || !form.data.email?.trim() || !form.data.password || form.data.password.length < 8) {
            setLocalError('Enter your name, email, and a password (min 8 characters) to continue.');
            return false;
        }
        return true;
    };

    const payStripe = () => {
        setLocalError('');
        if (!requireGuestFields()) return;
        form.transform((data) => ({
            ...data,
            payment_method: 'stripe',
            classes: Number(data.classes) || payableClasses,
            ...(isLoggedIn ? { is_logged_in: '1' } : {}),
        })).post(postUrl);
    };

    const payPaypal = async () => {
        setLocalError('');
        if (paypalBusy) return;
        if (!paypalClientId) {
            setLocalError('PayPal Client ID is missing in Admin → Gateway.');
            return;
        }
        if (!requireGuestFields()) return;
        setPaypalBusy(true);
        try {
            const res = await fetch(`/checkout/${course.id}/paypal`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(checkoutPayload()),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error || !data.approval_url) {
                const msg =
                    data.error ||
                    data.message ||
                    (data.errors ? Object.values(data.errors).flat().join(' ') : null) ||
                    'Could not start PayPal checkout';
                throw new Error(msg);
            }
            window.location.href = data.approval_url;
        } catch (err) {
            setLocalError(err.message || 'PayPal error');
            setPaypalBusy(false);
        }
    };

    const payRazorpay = async () => {
        setLocalError('');
        if (razorpayBusy) return;
        if (!razorpayKey) {
            setLocalError('Razorpay key is missing in Admin → Gateway.');
            return;
        }
        if (!requireGuestFields()) return;
        setRazorpayBusy(true);
        try {
            const res = await fetch(`/checkout/${course.id}/razorpay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(checkoutPayload()),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error || !data.order_id) {
                const msg =
                    data.error ||
                    data.message ||
                    (data.errors ? Object.values(data.errors).flat().join(' ') : null) ||
                    'Could not create Razorpay order';
                throw new Error(msg);
            }

            await openRazorpayCheckout({
                key: data.key || razorpayKey,
                amount: data.amount,
                currency: data.currency || currency || 'INR',
                description: course.title,
                orderId: data.order_id,
                onSuccess(response) {
                    const params = new URLSearchParams({
                        payment_id: String(data.payment_id),
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                    });
                    window.location.href = `/payment/success?${params.toString()}`;
                },
                onFailure() {
                    if (data.payment_id) {
                        window.location.href = `/payment/cancel?payment_id=${data.payment_id}`;
                    }
                },
            });
        } catch (err) {
            setLocalError(err.message || 'Razorpay error');
            setRazorpayBusy(false);
        }
    };

    const body = (
        <>
            <Head title={`Checkout · ${course.title || 'Course'}`} />
            <div className={`${isLoggedIn ? 'p-5 md:p-8' : 'px-4 py-10 md:py-14'} max-w-3xl mx-auto space-y-6`}>
                {!isLoggedIn ? (
                    <div className="text-center space-y-2">
                        <p className="text-[11px] font-extrabold uppercase tracking-[0.14em] text-primary">Secure checkout</p>
                        <h1 className="font-headline-lg text-on-surface">Complete your enrollment</h1>
                        <p className="text-sm text-on-surface-variant">
                            Already have an account?{' '}
                            <Link href="/login/student" className="font-bold text-primary hover:underline">
                                Sign in
                            </Link>
                        </p>
                    </div>
                ) : null}

                {flash?.error || localError ? (
                    <div className="p-3 rounded-xl bg-error-container text-error text-sm font-medium">{flash?.error || localError}</div>
                ) : null}

                <div className="grid md:grid-cols-2 gap-6">
                    <div className="clay-card overflow-hidden">
                        <div className="aspect-square bg-surface-container-low flex items-center justify-center overflow-hidden">
                            {course.thumbnail_url ? (
                                <img
                                    src={course.thumbnail_url}
                                    alt={course.title || 'Course'}
                                    className="w-full h-full object-contain"
                                    loading="lazy"
                                />
                            ) : (
                                <span className="material-symbols-outlined text-primary text-5xl">menu_book</span>
                            )}
                        </div>
                        <div className="p-6 space-y-3">
                            <h2 className="text-xl font-bold">{course.title}</h2>
                            {course.description ? (
                                <p className="text-sm text-on-surface-variant leading-relaxed line-clamp-4">{course.description}</p>
                            ) : null}
                            <p className="text-sm text-on-surface-variant">
                                {payableClasses} {payableClasses === 1 ? 'class' : 'classes'} · {form.data.delivery_mode}
                            </p>
                            <p className="text-3xl font-extrabold text-primary">
                                {symbol}
                                {displayAmount}
                            </p>
                            {discountAmount > 0 ? (
                                <p className="text-sm text-on-surface-variant">
                                    Savings {symbol}
                                    {Number(discountAmount).toFixed(2)}
                                    {siblingDiscount?.has_sibling_discount
                                        ? ` · includes ${siblingDiscount.discount_percent}% sibling discount`
                                        : ''}
                                </p>
                            ) : null}
                            {siblingDiscount?.has_sibling_discount ? (
                                <p className="text-xs font-bold text-secondary">Sibling family discount applied</p>
                            ) : null}
                            {bundleDiscount?.has_bundle_discount ? (
                                <p className="text-xs font-bold text-secondary">
                                    Bundle discount: {bundleDiscount.bundle_name} ({bundleDiscount.discount_percent}% off)
                                </p>
                            ) : null}
                            {bundles.length > 0 ? (
                                <div className="mt-2 p-3 rounded-xl bg-surface-container-low text-sm space-y-1">
                                    <p className="font-bold">Bundle deals</p>
                                    {bundles.map((b) => (
                                        <p key={b.id} className="text-on-surface-variant">
                                            {b.name}: {b.discount_percent}% when you already own another course in this
                                            bundle
                                        </p>
                                    ))}
                                </div>
                            ) : null}
                            {couponError ? <p className="text-error text-sm">{couponError}</p> : null}
                            {form.errors.payment_method ? (
                                <p className="text-error text-sm">{form.errors.payment_method}</p>
                            ) : null}
                            <p className="flex items-center gap-1.5 text-xs text-on-surface-variant pt-2 border-t border-outline-variant/40">
                                <span className="material-symbols-outlined text-base text-secondary">lock</span>
                                256-bit SSL encrypted checkout
                            </p>
                        </div>
                    </div>

                    <div className="clay-card p-6 space-y-4">
                        {!isLoggedIn ? (
                            <div className="space-y-3">
                                <label className="block text-sm font-bold">
                                    Full name
                                    <input
                                        type="text"
                                        autoComplete="name"
                                        className="w-full mt-1 rounded-xl border px-4 py-3"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                    />
                                </label>
                                {form.errors.name ? <p className="text-error text-sm">{form.errors.name}</p> : null}
                                <label className="block text-sm font-bold">
                                    Email
                                    <input
                                        type="email"
                                        autoComplete="email"
                                        className="w-full mt-1 rounded-xl border px-4 py-3"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                    />
                                </label>
                                {form.errors.email ? <p className="text-error text-sm">{form.errors.email}</p> : null}
                                <label className="block text-sm font-bold">
                                    Create password
                                    <input
                                        type="password"
                                        autoComplete="new-password"
                                        className="w-full mt-1 rounded-xl border px-4 py-3"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                    />
                                </label>
                                {form.errors.password ? <p className="text-error text-sm">{form.errors.password}</p> : null}
                            </div>
                        ) : null}
                        {deliveryModes.length > 1 ? (
                            <label className="block text-sm font-bold">
                                Delivery mode
                                <select
                                    className="w-full mt-1 rounded-xl border px-3 py-2"
                                    value={form.data.delivery_mode}
                                    onChange={(e) => form.setData('delivery_mode', e.target.value)}
                                >
                                    {deliveryModes.map((m) => (
                                        <option key={m} value={m}>
                                            {m}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        ) : null}
                        <label className="block text-sm font-bold">
                            Coupon code
                            <input
                                placeholder="Optional"
                                className="w-full mt-1 rounded-xl border px-4 py-3 uppercase tracking-wider font-bold"
                                value={form.data.coupon_code}
                                onChange={(e) => form.setData('coupon_code', e.target.value)}
                            />
                        </label>
                        {!enableStripe && !enablePaypal && !enableRazorpay ? (
                            <p className="text-sm text-on-surface-variant">No payment gateways are enabled. Please contact support.</p>
                        ) : null}
                        {enableStripe ? (
                            <button type="button" disabled={form.processing} onClick={payStripe} className="btn-enroll w-full disabled:opacity-60">
                                {form.processing
                                    ? 'Redirecting to Stripe…'
                                    : `Pay with Stripe (${symbol}${displayAmount} ${currency})`}
                            </button>
                        ) : null}
                        {enablePaypal ? (
                            <button
                                type="button"
                                disabled={paypalBusy || !paypalClientId}
                                onClick={payPaypal}
                                className="btn-primary w-full disabled:opacity-60"
                            >
                                {paypalBusy
                                    ? 'Redirecting to PayPal…'
                                    : currency === 'AED'
                                      ? 'Pay with PayPal (USD)'
                                      : `Pay with PayPal (${currency === 'INR' ? 'USD' : currency})`}
                            </button>
                        ) : null}
                        {enableRazorpay ? (
                            <div className="space-y-1">
                                <button
                                    type="button"
                                    disabled={razorpayBusy || !razorpayKey}
                                    onClick={payRazorpay}
                                    className="w-full border-2 border-primary text-primary font-bold rounded-full py-3 disabled:opacity-60"
                                >
                                    {razorpayBusy ? 'Opening Razorpay…' : 'Pay with Razorpay (INR)'}
                                </button>
                                {currency !== 'INR' ? (
                                    <p className="text-[11px] text-on-surface-variant text-center">
                                        Razorpay charges in Indian Rupees using the INR course price.
                                    </p>
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
        </>
    );

    if (isLoggedIn) {
        return <DashboardLayout title="Checkout">{body}</DashboardLayout>;
    }
    return <PublicLayout title="Checkout">{body}</PublicLayout>;
}
