import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { openRazorpayCheckout } from '../../../lib/razorpay';

const DAYS = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 0, label: 'Sunday' },
];

export default function Checkout({
    course,
    customPrice,
    customCurrency,
    customClasses,
    finalPrice,
    processUrl,
    razorpayUrl,
    paypalUrl,
    enableStripe = false,
    enablePaypal = false,
    enableRazorpay = false,
    paypalClientId = null,
    razorpayKey = null,
    couponCode: initialCoupon = '',
    discountAmount = 0,
    couponError = null,
    timezones = {},
}) {
    const { auth, csrfToken, flash, errors: pageErrors } = usePage().props;
    const isLoggedIn = auth?.guard === 'web' && Boolean(auth?.user);
    const defaultMethod = enableStripe ? 'stripe' : enablePaypal ? 'paypal' : enableRazorpay ? 'razorpay' : '';
    const [method, setMethod] = useState(defaultMethod);
    const [busy, setBusy] = useState(false);
    const [localError, setLocalError] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [selectedDays, setSelectedDays] = useState({});
    const [dayTimes, setDayTimes] = useState({});
    const latestRef = useRef({});

    const detectedTz =
        typeof Intl !== 'undefined' ? Intl.DateTimeFormat().resolvedOptions().timeZone : 'UTC';
    const tzList = useMemo(() => {
        let list = [];
        if (Array.isArray(timezones)) {
            list = timezones.map((tz) =>
                typeof tz === 'string' ? { value: tz, label: tz } : { value: tz.value || tz, label: tz.label || tz.value || tz }
            );
        } else if (timezones && typeof timezones === 'object') {
            list = Object.entries(timezones).map(([value, label]) => ({ value, label: label || value }));
        }
        if (detectedTz && !list.some((tz) => tz.value === detectedTz)) {
            list = [{ value: detectedTz, label: `${detectedTz} (detected)` }, ...list];
        } else if (detectedTz) {
            list = list.map((tz) =>
                tz.value === detectedTz ? { ...tz, label: `${tz.label || tz.value} (detected)` } : tz
            );
            list = [{ value: detectedTz, label: list.find((t) => t.value === detectedTz)?.label || detectedTz }, ...list.filter((t) => t.value !== detectedTz)];
        }
        return list.length ? list : [{ value: detectedTz || 'UTC', label: `${detectedTz || 'UTC'} (detected)` }];
    }, [timezones, detectedTz]);

    const form = useForm({
        name: '',
        email: '',
        password: '',
        child_name: '',
        timezone: detectedTz || 'UTC',
        coupon_code: initialCoupon || '',
        payment_method: defaultMethod,
        schedule_start_date: new Date().toISOString().slice(0, 10),
        is_logged_in: isLoggedIn ? '1' : '',
        price: customPrice,
        currency: customCurrency,
    });

    // Keep latest form/schedule values for PayPal callbacks (avoid stale closures).
    latestRef.current = {
        formData: form.data,
        selectedDays,
        dayTimes,
        isLoggedIn,
    };

    useEffect(() => {
        if (detectedTz && form.data.timezone !== detectedTz) {
            form.setData('timezone', detectedTz);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- only sync once browser timezone is known
    }, [detectedTz]);

    const displayTotal = finalPrice ?? customPrice;
    const fieldError = (key) => form.errors[key] || pageErrors?.[key];

    const toggleDay = (day) => {
        setSelectedDays((prev) => {
            const next = { ...prev, [day]: !prev[day] };
            if (!next[day]) {
                setDayTimes((t) => {
                    const copy = { ...t };
                    delete copy[day];
                    return copy;
                });
            }
            return next;
        });
    };

    const buildSchedulePayload = (daysMap = selectedDays, timesMap = dayTimes) => {
        const schedule_days = Object.keys(daysMap)
            .filter((d) => daysMap[d])
            .map((d) => Number(d));
        const schedule_times = {};
        schedule_days.forEach((d) => {
            if (timesMap[d]) schedule_times[d] = timesMap[d];
        });
        return { schedule_days, schedule_times };
    };

    const validateGuest = (data = form.data, loggedIn = isLoggedIn) => {
        if (!String(data.child_name || '').trim()) {
            setLocalError("Child's name is required.");
            return false;
        }
        if (loggedIn) return true;
        if (!String(data.name || '').trim()) {
            setLocalError('Parent name is required.');
            return false;
        }
        if (!String(data.email || '').trim() || !data.password || String(data.password).length < 8) {
            setLocalError('Please fill in email and a password of at least 8 characters.');
            return false;
        }
        return true;
    };

    const validateSchedule = (daysMap = selectedDays, timesMap = dayTimes) => {
        const days = Object.keys(daysMap).filter((d) => daysMap[d]);
        if (days.length === 0) {
            setLocalError('Please select at least one preferred class day.');
            return false;
        }
        for (const d of days) {
            if (!timesMap[d]) {
                setLocalError('Please choose a time for each selected day.');
                return false;
            }
        }
        return true;
    };

    const payStripe = (e) => {
        e?.preventDefault();
        setLocalError('');
        if (!validateGuest() || !validateSchedule() || busy) return;
        const schedule = buildSchedulePayload();
        setBusy(true);
        form
            .transform((data) => {
                const payload = {
                    ...data,
                    payment_method: 'stripe',
                    ...schedule,
                    ...(isLoggedIn
                        ? {
                              is_logged_in: '1',
                              name: auth?.user?.name || data.name || '',
                              email: auth?.user?.email || data.email || '',
                          }
                        : {}),
                };
                if (isLoggedIn) {
                    delete payload.password;
                }
                return payload;
            })
            .post(processUrl, {
                onFinish: () => setBusy(false),
                onError: () => setBusy(false),
            });
    };

    const refreshCsrfToken = async () => {
        try {
            const res = await fetch('/csrf-token', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json().catch(() => ({}));
            if (data.token) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', data.token);
                return data.token;
            }
        } catch {
            // fall through to page token
        }
        return csrfToken;
    };

    const jsonHeaders = (token) => {
        const xsrf = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')
            .slice(1)
            .join('=');
        return {
            'X-CSRF-TOKEN': token || csrfToken,
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}),
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
    };

    const checkoutErrorMessage = (res, data, fallback) => {
        if (res.status === 419) {
            return 'Your session expired. Please refresh this page and try again.';
        }
        const firstValidationError = data.errors ? Object.values(data.errors).flat()?.[0] : null;
        return data.error || data.message || firstValidationError || fallback;
    };

    const payPaypal = async () => {
        setLocalError('');
        if (!validateGuest() || !validateSchedule() || busy || !paypalUrl) return;
        if (!paypalClientId) {
            setLocalError('PayPal Client ID is missing in Admin → Gateway.');
            return;
        }
        setBusy(true);
        try {
            const latest = latestRef.current;
            const schedule = buildSchedulePayload(latest.selectedDays, latest.dayTimes);
            const body = {
                ...latest.formData,
                payment_method: 'paypal',
                ...schedule,
                ...(latest.isLoggedIn
                    ? {
                          is_logged_in: '1',
                          name: auth?.user?.name || latest.formData.name || undefined,
                          email: auth?.user?.email || latest.formData.email || undefined,
                          password: undefined,
                      }
                    : {}),
            };
            if (latest.isLoggedIn) {
                delete body.password;
            }
            const token = await refreshCsrfToken();
            const res = await fetch(paypalUrl, {
                method: 'POST',
                credentials: 'same-origin',
                redirect: 'manual',
                headers: jsonHeaders(token),
                body: JSON.stringify(body),
            });
            // Validation failures used to 302 back to this page as HTML; treat redirects as errors.
            if (res.type === 'opaqueredirect' || (res.status >= 300 && res.status < 400)) {
                throw new Error('Checkout could not continue. Refresh this page and try PayPal again.');
            }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error || !data.approval_url) {
                throw new Error(
                    checkoutErrorMessage(
                        res,
                        data,
                        `Could not start PayPal checkout (HTTP ${res.status}). Refresh the page and try again.`
                    )
                );
            }
            window.location.href = data.approval_url;
        } catch (err) {
            setLocalError(err.message || 'PayPal error');
            setBusy(false);
        }
    };

    const payRazorpay = async () => {
        setLocalError('');
        if (!validateGuest() || !validateSchedule() || busy || !razorpayUrl) return;
        if (!razorpayKey) {
            setLocalError('Razorpay key is missing in Admin → Gateway.');
            return;
        }
        setBusy(true);
        try {
            const schedule = buildSchedulePayload();
            const body = {
                ...form.data,
                payment_method: 'razorpay',
                ...schedule,
                ...(isLoggedIn
                    ? {
                          is_logged_in: '1',
                          name: auth?.user?.name || form.data.name || undefined,
                          email: auth?.user?.email || form.data.email || undefined,
                      }
                    : {}),
            };
            if (isLoggedIn) {
                delete body.password;
            }
            const token = await refreshCsrfToken();
            const res = await fetch(razorpayUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(token),
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error || !data.order_id) {
                throw new Error(
                    checkoutErrorMessage(res, data, 'Unable to create Razorpay order')
                );
            }

            // Prefer subunits from API (`amount` + `amount_major`). Legacy responses only had major units.
            const amountSubunits =
                data.amount_major != null
                    ? Math.round(Number(data.amount))
                    : Math.round(Number(data.amount) * 100);

            await openRazorpayCheckout({
                key: data.key || razorpayKey,
                amount: amountSubunits,
                currency: data.currency || customCurrency || 'INR',
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
            setBusy(false);
        } catch (err) {
            setLocalError(err.message || 'Payment error');
            setBusy(false);
        }
    };

    const applyCoupon = () => {
        const url = new URL(window.location.href);
        if (form.data.coupon_code) {
            url.searchParams.set('coupon', form.data.coupon_code.trim());
        } else {
            url.searchParams.delete('coupon');
        }
        window.location.href = url.toString();
    };

    return (
        <PublicLayout title="Custom enrollment checkout">
            <Head title={`Enroll · ${course.title}`} />
            <div className="max-w-6xl mx-auto px-4 py-10 md:py-14">
                <div className="text-center mb-8 space-y-2">
                    <p className="text-[11px] font-extrabold uppercase tracking-[0.14em] text-primary">Custom enrollment</p>
                    <h1 className="text-3xl md:text-4xl font-extrabold tracking-tight">Complete registration</h1>
                    <p className="text-on-surface-variant">Special pricing for {course.title}</p>
                </div>

                {(flash?.error || localError) && (
                    <div className="mb-6 p-4 rounded-xl bg-error-container text-error text-sm font-medium max-w-3xl mx-auto">
                        {flash?.error || localError}
                    </div>
                )}

                <div className="grid lg:grid-cols-12 gap-6">
                    <div className="lg:col-span-7 clay-card p-5 md:p-8 space-y-6">
                        <h2 className="text-xl font-bold border-b border-outline-variant pb-3">Registration details</h2>

                        {isLoggedIn ? (
                            <div className="rounded-2xl bg-surface-container-low p-5 space-y-4">
                                <div>
                                    <h3 className="font-bold mb-1">Logged in as</h3>
                                    <p className="text-sm">
                                        {auth.user.name} ({auth.user.email})
                                    </p>
                                </div>
                                <label className="block text-sm font-bold">
                                    Child&apos;s name <span className="text-error">*</span>
                                    <input
                                        className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                        value={form.data.child_name}
                                        onChange={(e) => form.setData('child_name', e.target.value)}
                                        required
                                    />
                                    {fieldError('child_name') ? (
                                        <span className="text-error text-xs">{fieldError('child_name')}</span>
                                    ) : null}
                                </label>
                            </div>
                        ) : (
                            <div className="rounded-2xl bg-surface-container-low p-5 space-y-4">
                                <h3 className="font-bold">Create your account</h3>
                                <label className="block text-sm font-bold">
                                    Parent name <span className="text-error">*</span>
                                    <input
                                        className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        autoComplete="name"
                                        required
                                    />
                                    {fieldError('name') ? <span className="text-error text-xs">{fieldError('name')}</span> : null}
                                </label>
                                <label className="block text-sm font-bold">
                                    Child&apos;s name <span className="text-error">*</span>
                                    <input
                                        className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                        value={form.data.child_name}
                                        onChange={(e) => form.setData('child_name', e.target.value)}
                                        required
                                    />
                                    {fieldError('child_name') ? (
                                        <span className="text-error text-xs">{fieldError('child_name')}</span>
                                    ) : null}
                                </label>
                                <label className="block text-sm font-bold">
                                    Email <span className="text-error">*</span>
                                    <input
                                        type="email"
                                        className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        autoComplete="email"
                                        required
                                    />
                                    {fieldError('email') ? <span className="text-error text-xs">{fieldError('email')}</span> : null}
                                </label>
                                <label className="block text-sm font-bold">
                                    Password <span className="text-error">*</span>
                                    <div className="relative mt-1">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            className="w-full px-4 py-3 pr-12 border border-outline-variant rounded-xl bg-white"
                                            value={form.data.password}
                                            onChange={(e) => form.setData('password', e.target.value)}
                                            autoComplete="new-password"
                                            minLength={8}
                                            required
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword((v) => !v)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-primary"
                                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                                        >
                                            {showPassword ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5" aria-hidden="true">
                                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                                    <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                                    <line x1="1" y1="1" x2="23" y2="23" />
                                                </svg>
                                            ) : (
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5" aria-hidden="true">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {fieldError('password') ? <span className="text-error text-xs">{fieldError('password')}</span> : null}
                                </label>
                            </div>
                        )}

                        <div className="rounded-2xl bg-surface-container-low p-5 space-y-4">
                            <div>
                                <h3 className="font-bold">Class schedule</h3>
                                <p className="text-sm text-on-surface-variant mt-1">
                                    You are enrolling in <strong>{customClasses}</strong>{' '}
                                    {Number(customClasses) === 1 ? 'class' : 'classes'}. Pick preferred days and times.
                                </p>
                            </div>
                            <label className="block text-sm font-bold">
                                Timezone <span className="font-normal text-on-surface-variant">(auto-detected)</span>
                                <select
                                    className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                    value={form.data.timezone}
                                    onChange={(e) => form.setData('timezone', e.target.value)}
                                >
                                    {tzList.map((tz) => (
                                        <option key={tz.value || tz} value={tz.value || tz}>
                                            {tz.label || tz.value || tz}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block text-sm font-bold">
                                Preferred start date
                                <input
                                    type="date"
                                    className="w-full mt-1 px-4 py-3 border border-outline-variant rounded-xl bg-white"
                                    value={form.data.schedule_start_date}
                                    onChange={(e) => form.setData('schedule_start_date', e.target.value)}
                                />
                            </label>
                            <div className="space-y-3">
                                {DAYS.map((day) => (
                                    <div key={day.value} className="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <label className="flex items-center gap-2 min-w-[140px] font-medium text-sm">
                                            <input
                                                type="checkbox"
                                                checked={Boolean(selectedDays[day.value])}
                                                onChange={() => toggleDay(day.value)}
                                            />
                                            {day.label}
                                        </label>
                                        <input
                                            type="time"
                                            step={300}
                                            disabled={!selectedDays[day.value]}
                                            className="px-3 py-2 border border-outline-variant rounded-lg text-sm disabled:opacity-50 bg-white"
                                            value={dayTimes[day.value] || ''}
                                            onChange={(e) => setDayTimes((t) => ({ ...t, [day.value]: e.target.value }))}
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-2xl bg-surface-container-low p-5 space-y-3">
                            <label className="block text-sm font-bold">Coupon code</label>
                            <div className="flex gap-2">
                                <input
                                    className="flex-1 px-4 py-3 border border-outline-variant rounded-xl uppercase font-bold tracking-wider bg-white"
                                    value={form.data.coupon_code}
                                    onChange={(e) => form.setData('coupon_code', e.target.value)}
                                    placeholder="Enter coupon"
                                />
                                <button type="button" onClick={applyCoupon} className="px-5 py-3 rounded-xl bg-on-surface-variant text-white text-sm font-bold">
                                    Apply
                                </button>
                            </div>
                            {couponError ? <p className="text-error text-xs font-medium">{couponError}</p> : null}
                            {discountAmount > 0 ? (
                                <p className="text-on-tertiary-container text-xs font-medium">Coupon applied successfully</p>
                            ) : null}
                        </div>

                        <div className="space-y-3">
                            <h3 className="font-bold">Select payment method</h3>
                            {enableStripe ? (
                                <label className={`flex gap-3 p-4 rounded-2xl border-2 cursor-pointer ${method === 'stripe' ? 'border-primary bg-primary/5' : 'border-outline-variant'}`}>
                                    <input type="radio" name="pm" checked={method === 'stripe'} onChange={() => setMethod('stripe')} />
                                    <span>
                                        <span className="block font-bold">Stripe</span>
                                        <span className="text-xs text-on-surface-variant">Cards & wallets</span>
                                    </span>
                                </label>
                            ) : null}
                            {enablePaypal ? (
                                <label className={`flex gap-3 p-4 rounded-2xl border-2 cursor-pointer ${method === 'paypal' ? 'border-primary bg-primary/5' : 'border-outline-variant'}`}>
                                    <input type="radio" name="pm" checked={method === 'paypal'} onChange={() => setMethod('paypal')} />
                                    <span>
                                        <span className="block font-bold">PayPal</span>
                                        <span className="text-xs text-on-surface-variant">PayPal balance & cards</span>
                                    </span>
                                </label>
                            ) : null}
                            {enableRazorpay ? (
                                <label className={`flex gap-3 p-4 rounded-2xl border-2 cursor-pointer ${method === 'razorpay' ? 'border-primary bg-primary/5' : 'border-outline-variant'}`}>
                                    <input type="radio" name="pm" checked={method === 'razorpay'} onChange={() => setMethod('razorpay')} />
                                    <span>
                                        <span className="block font-bold">Razorpay</span>
                                        <span className="text-xs text-on-surface-variant">UPI, cards, wallets</span>
                                    </span>
                                </label>
                            ) : null}
                        </div>

                        {method === 'stripe' ? (
                            <button type="button" disabled={busy || form.processing} onClick={payStripe} className="btn-enroll w-full disabled:opacity-60">
                                {busy || form.processing ? 'Processing…' : 'Complete registration & pay'}
                            </button>
                        ) : null}
                        {method === 'paypal' ? (
                            <button
                                type="button"
                                disabled={busy || !paypalClientId || !paypalUrl}
                                onClick={payPaypal}
                                className="btn-primary w-full disabled:opacity-60"
                            >
                                {busy ? 'Redirecting to PayPal…' : 'Pay with PayPal'}
                            </button>
                        ) : null}
                        {method === 'razorpay' ? (
                            <button type="button" disabled={busy || !razorpayKey} onClick={payRazorpay} className="btn-enroll w-full disabled:opacity-60">
                                {busy ? 'Opening Razorpay…' : 'Complete registration & pay'}
                            </button>
                        ) : null}
                        {!method ? <p className="text-sm text-on-surface-variant">No payment gateways enabled.</p> : null}
                    </div>

                    <div className="lg:col-span-5">
                        <div className="clay-card overflow-hidden sticky top-24">
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
                            <div className="p-6 space-y-4">
                                <h2 className="text-xl font-bold">{course.title}</h2>
                                {course.description ? (
                                    <p className="text-sm text-on-surface-variant leading-relaxed line-clamp-4">{course.description}</p>
                                ) : null}
                                <p className="text-sm text-on-surface-variant">
                                    {customClasses} {Number(customClasses) === 1 ? 'class' : 'classes'} · custom offer
                                </p>
                                <div className="flex items-end justify-between gap-3 pt-2 border-t border-outline-variant">
                                    <span className="font-bold text-on-surface-variant">Total</span>
                                    <span className="text-3xl font-extrabold text-primary">
                                        {customCurrency} {Number(displayTotal).toFixed(2)}
                                    </span>
                                </div>
                                {discountAmount > 0 ? (
                                    <p className="text-sm text-on-surface-variant">
                                        Was {customCurrency} {Number(customPrice).toFixed(2)} · saved {customCurrency}{' '}
                                        {Number(discountAmount).toFixed(2)}
                                    </p>
                                ) : null}
                                <p className="flex items-center gap-1.5 text-xs text-on-surface-variant pt-1">
                                    <span className="material-symbols-outlined text-base text-secondary">lock</span>
                                    256-bit SSL encrypted checkout
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
