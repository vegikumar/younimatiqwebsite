import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';

const DAYS = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 0, label: 'Sunday' },
];

const TZ_OPTIONS = [
    { value: 'America/New_York', label: 'Eastern Time (ET)' },
    { value: 'America/Chicago', label: 'Central Time (CT)' },
    { value: 'America/Denver', label: 'Mountain Time (MT)' },
    { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
    { value: 'Europe/London', label: 'London (GMT/BST)' },
    { value: 'Asia/Kolkata', label: 'India Standard Time (IST)' },
];

function formatSlotLabel(utcIso, tz) {
    if (!utcIso) return '';
    const start = new Date(utcIso);
    if (Number.isNaN(start.getTime())) return '';
    const end = new Date(start.getTime() + 60 * 60000);
    const fmt = { hour: 'numeric', minute: '2-digit', timeZone: tz };
    try {
        return `${new Intl.DateTimeFormat(undefined, fmt).format(start)} – ${new Intl.DateTimeFormat(undefined, fmt).format(end)}`;
    } catch {
        return '';
    }
}

export default function ScheduleWizard({
    course,
    existingBookings = [],
    paidClasses = 0,
    classesLeft,
}) {
    const detectedTz =
        typeof Intl !== 'undefined' ? Intl.DateTimeFormat().resolvedOptions().timeZone : 'UTC';
    const remaining = classesLeft ?? Math.max(0, paidClasses - existingBookings.length);
    const today = useMemo(() => new Date().toISOString().slice(0, 10), []);

    const form = useForm({
        timezone: detectedTz || 'UTC',
        start_date: today,
        days: [],
        times: {},
    });

    const [slotsByDay, setSlotsByDay] = useState({});
    const [slotsError, setSlotsError] = useState('');
    const [customTimes, setCustomTimes] = useState({});

    const timezoneOptions = useMemo(() => {
        const opts = [...TZ_OPTIONS];
        if (form.data.timezone && !opts.some((o) => o.value === form.data.timezone)) {
            opts.push({ value: form.data.timezone, label: `${form.data.timezone} (Auto)` });
        }
        return opts;
    }, [form.data.timezone]);

    useEffect(() => {
        const { timezone, start_date: startDate } = form.data;
        if (!timezone || !startDate) return undefined;

        let cancelled = false;
        setSlotsError('');
        const url = `/api/consultation-slots?group_by_weekday=1&timezone=${encodeURIComponent(timezone)}&week_of=${encodeURIComponent(startDate)}`;
        fetch(url)
            .then((r) => {
                if (!r.ok) throw new Error('Could not load class times');
                return r.json();
            })
            .then((data) => {
                if (!cancelled) setSlotsByDay(data.by_weekday || {});
            })
            .catch((err) => {
                if (!cancelled) setSlotsError(err.message || 'Could not load class times');
            });

        return () => {
            cancelled = true;
        };
    }, [form.data.timezone, form.data.start_date]);

    const toggleDay = (day) => {
        const selected = form.data.days.includes(day);
        const nextDays = selected ? form.data.days.filter((d) => d !== day) : [...form.data.days, day];
        const nextTimes = { ...form.data.times };
        if (selected) delete nextTimes[day];
        form.setData({ ...form.data, days: nextDays, times: nextTimes });
    };

    const setTime = (day, value) => {
        form.setData('times', { ...form.data.times, [day]: value });
    };

    const submit = (e) => {
        e.preventDefault();
        const times = { ...form.data.times };
        Object.entries(customTimes).forEach(([day, val]) => {
            if (times[day] === '__custom__' && val) times[day] = val;
        });
        form.transform((data) => ({
            timezone: data.timezone,
            start_date: data.start_date,
            days: data.days,
            times,
        })).post(`/student/courses/${course.id}/schedule-wizard`);
    };

    return (
        <DashboardLayout title="Schedule classes">
            <Head title="Schedule classes" />
            <div className="p-6 md:p-8 max-w-4xl mx-auto space-y-6">
                <PageHeader
                    title={`Schedule · ${course.title}`}
                    description={`You have ${remaining} class${remaining === 1 ? '' : 'es'} left to schedule for this course.`}
                />
                <div className="clay-card p-5 md:p-8">
                    {Object.keys(form.errors).length > 0 ? (
                        <div className="mb-6 p-4 rounded-xl bg-error-container text-error text-sm">
                            {Object.values(form.errors).flat().join(' ')}
                        </div>
                    ) : null}
                    {slotsError ? (
                        <div className="mb-6 p-4 rounded-xl bg-error-container text-error text-sm">{slotsError}</div>
                    ) : null}

                    <form className="space-y-8" onSubmit={submit}>
                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2" htmlFor="timezone">
                                Your Timezone
                            </label>
                            <select
                                id="timezone"
                                className="w-full md:w-1/2 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl"
                                value={form.data.timezone}
                                onChange={(e) => form.setData('timezone', e.target.value)}
                                required
                            >
                                {timezoneOptions.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.timezone ? <p className="text-error text-sm mt-1">{form.errors.timezone}</p> : null}
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2" htmlFor="start_date">
                                When would you like to start?
                            </label>
                            <input
                                id="start_date"
                                type="date"
                                min={today}
                                required
                                className="w-full md:w-1/2 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl"
                                value={form.data.start_date}
                                onChange={(e) => form.setData('start_date', e.target.value)}
                            />
                            {form.errors.start_date ? <p className="text-error text-sm mt-1">{form.errors.start_date}</p> : null}
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2">
                                Select the days and times you wish to attend
                            </label>
                            <p className="text-sm text-on-surface-variant mb-4">Select the days and a time for each.</p>
                            {slotsError ? <p className="text-error text-sm mb-3">{slotsError}</p> : null}
                            {form.errors.days ? <p className="text-error text-sm mb-3">{form.errors.days}</p> : null}
                            {form.errors.times ? <p className="text-error text-sm mb-3">{form.errors.times}</p> : null}

                            <div className="space-y-4">
                                {DAYS.map(({ value, label }) => {
                                    const checked = form.data.days.includes(value);
                                    const slots = slotsByDay[value] || slotsByDay[String(value)] || [];
                                    const timeVal = form.data.times[value] || '';
                                    return (
                                        <div
                                            key={value}
                                            className="flex flex-col md:flex-row md:items-center gap-3 bg-surface-container-low p-4 rounded-xl border border-outline-variant/30"
                                        >
                                            <label className="flex items-center gap-3 w-40 cursor-pointer shrink-0">
                                                <input
                                                    type="checkbox"
                                                    className="w-5 h-5 text-primary rounded"
                                                    checked={checked}
                                                    onChange={() => toggleDay(value)}
                                                />
                                                <span className="font-medium">{label}</span>
                                            </label>
                                            <div className="flex-1 flex flex-col sm:flex-row sm:items-center gap-3">
                                                <select
                                                    disabled={!checked}
                                                    required={checked}
                                                    className="time-input w-full max-w-md px-3 py-1.5 bg-white border border-outline-variant/30 rounded-lg text-sm disabled:opacity-50"
                                                    value={timeVal}
                                                    onChange={(e) => setTime(value, e.target.value)}
                                                >
                                                    <option value="">Select a recommended time</option>
                                                    {slots.map((slot) => (
                                                        <option key={slot.value || slot.utc_iso} value={slot.value}>
                                                            {formatSlotLabel(slot.utc_iso, form.data.timezone) || slot.label}
                                                        </option>
                                                    ))}
                                                    <option value="__custom__">Choose a different time</option>
                                                </select>
                                                {checked && timeVal === '__custom__' ? (
                                                    <input
                                                        type="time"
                                                        step={300}
                                                        required
                                                        className="w-40 px-3 py-1.5 bg-white border border-outline-variant/30 rounded-lg text-sm"
                                                        value={customTimes[value] || ''}
                                                        onChange={(e) =>
                                                            setCustomTimes((prev) => ({ ...prev, [value]: e.target.value }))
                                                        }
                                                    />
                                                ) : null}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-4 pt-6 border-t border-surface-container-high">
                            <Link href="/student/dashboard" className="btn-secondary px-6 text-center">
                                Cancel
                            </Link>
                            <button type="submit" className="btn-primary px-8" disabled={form.processing || remaining <= 0}>
                                {form.processing ? 'Saving…' : 'Confirm schedule'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
