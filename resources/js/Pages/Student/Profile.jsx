import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import FormField from '../../Components/Portal/FormField';
import FormErrors from '../../Components/Portal/FormErrors';
import SubmitButton from '../../Components/Portal/SubmitButton';

function detectBrowserTimezone() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    } catch {
        return 'UTC';
    }
}

function isUnsetTimezone(tz) {
    return !tz || ['UTC', 'GMT', 'Etc/UTC', 'Etc/GMT'].includes(String(tz).trim());
}

function normalizeTimezoneOptions(timezones) {
    if (Array.isArray(timezones)) {
        return timezones.map((tz) => ({
            id: tz.id || tz.value || tz,
            label: tz.label || String(tz.id || tz).replace(/_/g, ' '),
        }));
    }
    if (timezones && typeof timezones === 'object') {
        return Object.entries(timezones).map(([id, label]) => ({ id, label }));
    }
    return [{ id: 'UTC', label: 'UTC' }];
}

function mergeTimezoneOptions(base = [], ...extras) {
    const seen = new Set();
    const out = [];
    for (const opt of [...base, ...extras.filter(Boolean)]) {
        const id = opt?.id || opt;
        const label = opt?.label || String(id).replace(/_/g, ' ');
        if (!id || seen.has(id)) continue;
        seen.add(id);
        out.push({ id, label });
    }
    return out;
}

export default function Profile({ user, timezones = [] }) {
    const { flash } = usePage().props;
    const [browserTz, setBrowserTz] = useState('UTC');
    const form = useForm({
        name: user.name || '',
        email: user.email || '',
        grade: user.grade || '',
        timezone: user.timezone ?? 'UTC',
        leaderboard_opt_out: !!user.leaderboard_opt_out,
        avatar: null,
    });

    useEffect(() => {
        const detected = detectBrowserTimezone();
        setBrowserTz(detected);
        if (isUnsetTimezone(form.data.timezone)) {
            form.setData('timezone', detected);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- detect once on mount
    }, []);

    const timezoneOptions = useMemo(
        () =>
            mergeTimezoneOptions(
                normalizeTimezoneOptions(timezones),
                user.timezone ? { id: user.timezone, label: user.timezone.replace(/_/g, ' ') } : null,
                { id: browserTz, label: `${browserTz.replace(/_/g, ' ')} (detected)` }
            ),
        [timezones, user.timezone, browserTz]
    );

    const applyDetectedTimezone = () => {
        const detected = detectBrowserTimezone();
        setBrowserTz(detected);
        form.setData('timezone', detected);
    };

    return (
        <DashboardLayout title="My Profile">
            <Head title="My Profile" />
            <div className="p-5 md:p-8 space-y-8">
                <PageHeader title="My profile" description="Manage your account information." />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}

                <div className="clay-card overflow-hidden">
                    <div className="p-5 md:p-8 border-b border-surface-container-high flex items-center gap-5">
                        <img src={user.avatar_url} className="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-surface object-cover" alt="" />
                        <div>
                            <h2 className="font-headline-md font-bold text-2xl">{user.name}</h2>
                            <p className="text-on-surface-variant mb-2">{user.email}</p>
                            {user.member_since ? <p className="text-sm text-on-surface-variant">Member since {user.member_since}</p> : null}
                        </div>
                    </div>
                    <div className="p-5 md:p-8">
                        <form
                            className="space-y-6 max-w-2xl"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.post('/student/profile', { forceFormData: true });
                            }}
                        >
                            <FormErrors errors={form.errors} />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <FormField label="Full name" name="name" required error={form.errors.name}>
                                    <input
                                        id="name"
                                        type="text"
                                        required
                                        className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Email address" name="email" required error={form.errors.email}>
                                    <input
                                        id="email"
                                        type="email"
                                        required
                                        className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Grade (1–12)" name="grade" error={form.errors.grade}>
                                    <select
                                        id="grade"
                                        className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.grade}
                                        onChange={(e) => form.setData('grade', e.target.value)}
                                    >
                                        <option value="">Not set</option>
                                        {Array.from({ length: 12 }, (_, i) => i + 1).map((g) => (
                                            <option key={g} value={g}>
                                                Grade {g}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                                <div className="flex items-end">
                                    <label className="flex items-center gap-2 text-sm font-bold pb-3">
                                        <input
                                            type="checkbox"
                                            checked={form.data.leaderboard_opt_out}
                                            onChange={(e) => form.setData('leaderboard_opt_out', e.target.checked)}
                                        />
                                        Hide me from leaderboards
                                    </label>
                                </div>
                            </div>
                            <FormField
                                label="Timezone"
                                name="timezone"
                                error={form.errors.timezone}
                                hint="Class times on your dashboard use this zone."
                            >
                                <div className="flex gap-2">
                                    <select
                                        id="timezone"
                                        className="flex-1 bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.timezone}
                                        onChange={(e) => form.setData('timezone', e.target.value)}
                                    >
                                        {timezoneOptions.map((tz) => (
                                            <option key={tz.id} value={tz.id}>
                                                {tz.label}
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        type="button"
                                        className="shrink-0 px-4 py-3 rounded-xl border border-surface-container-high text-sm font-bold hover:bg-surface-container-low"
                                        onClick={applyDetectedTimezone}
                                    >
                                        Detect
                                    </button>
                                </div>
                            </FormField>
                            <FormField label="Profile photo (optional)" name="avatar" error={form.errors.avatar}>
                                <input
                                    id="avatar"
                                    type="file"
                                    accept="image/*"
                                    className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white"
                                    onChange={(e) => form.setData('avatar', e.target.files?.[0] || null)}
                                />
                            </FormField>
                            <SubmitButton processing={form.processing}>Save profile</SubmitButton>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
