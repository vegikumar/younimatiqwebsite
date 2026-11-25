import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';
import { formatLocalTime } from '../../../utils/formatLocalTime';

const AUDIENCE_OPTIONS = [
    { value: 'students', title: 'All student/parent accounts', hint: 'Active users with role = student' },
    { value: 'enrolled', title: 'Currently enrolled families', hint: 'Users with an active enrollment' },
    { value: 'newsletter', title: 'Newsletter subscribers', hint: 'Site newsletter emails' },
    { value: 'camp_bookings', title: 'Trial / camp booking emails', hint: 'Parent emails from bookings' },
];

export default function Edit({
    automation = {},
    courses = [],
    recipientCount = 0,
    previewCourses = [],
    nextSendLabel,
    recentRuns = [],
    dayLabels = {},
    timezoneOptions = [],
    routes = {},
}) {
    const { flash, errors, csrfToken } = usePage().props;
    const errList = errors ? Object.values(errors).flat() : [];
    const [courseSource, setCourseSource] = useState(automation.course_source || 'top');
    const [customSubject, setCustomSubject] = useState('');
    const [customBody, setCustomBody] = useState('');

    const dayEntries = useMemo(() => Object.entries(dayLabels).map(([value, label]) => ({ value: Number(value), label })), [dayLabels]);
    const selectedDays = automation.days_of_week || [1, 3, 5];
    const selectedAudiences = automation.audiences || [];
    const selectedCourseIds = automation.course_ids || [];

    const destroy = () => {
        if (!confirm(`Delete “${automation.name}”?`)) return;
        router.delete(automation.destroyUrl);
    };

    const previewCustom = () => {
        const q = new URLSearchParams({ subject: customSubject || '', body: customBody || '' });
        window.open(`${automation.customPreviewUrl}?${q.toString()}`, '_blank');
    };

    return (
        <DashboardLayout title={`Edit · ${automation.name}`}>
            <Head title={`Edit · ${automation.name}`} />
            <div className="staff-page">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <Link href={routes.index} className="text-sm font-bold text-primary hover:underline">← All automations</Link>
                    <button type="button" onClick={destroy} className="text-sm font-bold text-error">Delete this automation</button>
                </div>

                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">{automation.name}</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Edit copy, schedule, audiences, and featured classes for this campaign.</p>
                    <div className="mt-4 flex flex-wrap gap-3 text-sm">
                        <span className={`px-3 py-1 rounded-full font-bold ${automation.is_enabled ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>
                            {automation.is_enabled ? 'Automation ON' : 'Automation OFF'}
                        </span>
                        <span className="px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant font-medium">Audience size: {Number(recipientCount).toLocaleString()}</span>
                        {nextSendLabel ? <span className="px-3 py-1 rounded-full bg-primary/10 text-primary font-medium">Next auto-send: {nextSendLabel}</span> : null}
                        {automation.last_sent_at ? (
                            <span className="px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant font-medium">
                                Last send: {formatLocalTime(automation.last_sent_at, 'datetime-short')} ({automation.last_sent_count} queued)
                            </span>
                        ) : null}
                    </div>
                </PortalCard>

                {flash?.success && <div className="mb-6 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-medium">{flash.success}</div>}
                {flash?.error && <div className="mb-6 p-4 rounded-xl bg-error-container text-on-error-container font-medium">{flash.error}</div>}
                {errList.length > 0 && (
                    <div className="mb-6 p-4 rounded-xl bg-error-container text-on-error-container">
                        <ul className="list-disc pl-5 space-y-1">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                    </div>
                )}

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
                    <div className="xl:col-span-2 space-y-6">
                        <form method="POST" action={automation.updateUrl} encType="multipart/form-data" className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 md:p-8 space-y-6">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PUT" />

                            <div className="flex items-start justify-between gap-4">
                                <div className="flex-1">
                                    <label className="block text-sm font-bold mb-2">Campaign name</label>
                                    <input type="text" name="name" defaultValue={automation.name} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <label className="inline-flex items-center gap-2 cursor-pointer select-none mt-8">
                                    <input type="checkbox" name="is_enabled" value="1" defaultChecked={automation.is_enabled} className="w-5 h-5 accent-primary" />
                                    <span className="font-bold text-sm">Enable auto-send</span>
                                </label>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-bold mb-2">Email subject</label>
                                    <input type="text" name="subject" defaultValue={automation.subject} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Headline inside email</label>
                                    <input type="text" name="heading" defaultValue={automation.heading} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">From name</label>
                                    <input type="text" name="from_name" defaultValue={automation.from_name} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="Amy from Younimatiq" />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-bold mb-2">Intro line</label>
                                    <textarea name="intro_text" rows={2} required defaultValue={automation.intro_text} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Button text</label>
                                    <input type="text" name="cta_text" defaultValue={automation.cta_text} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Button URL</label>
                                    <input type="url" name="cta_url" defaultValue={automation.cta_url || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Sign-off name</label>
                                    <input type="text" name="sign_off_name" defaultValue={automation.sign_off_name} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Reply-to email</label>
                                    <input type="email" name="reply_to" defaultValue={automation.reply_to || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="support@younimatiq.com" />
                                </div>
                            </div>

                            <hr className="border-outline-variant" />

                            <div className="space-y-3">
                                <h3 className="font-bold">Custom HTML email theme</h3>
                                <p className="text-xs text-on-surface-variant">Placeholders: subject, heading, intro_text, cta_text, cta_url, courses_block, unsubscribe_url, recipient_name, sign_off_name, logo_url.</p>
                                <input type="file" name="theme_file" accept=".html,.htm,text/html" className="w-full text-sm" />
                                <textarea name="theme_html" rows={8} defaultValue={automation.theme_html || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl font-mono text-xs" placeholder="Paste full HTML theme…" />
                                <label className="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="clear_theme" value="1" /> Clear custom theme</label>
                            </div>

                            <hr className="border-outline-variant" />

                            <div>
                                <h3 className="font-bold mb-3">Schedule</h3>
                                <div className="flex flex-wrap gap-3 mb-4">
                                    {dayEntries.map(({ value, label }) => (
                                        <label key={value} className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                                            <input type="checkbox" name="days_of_week[]" value={value} defaultChecked={selectedDays.includes(value)} />
                                            <span className="text-sm font-medium">{label}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Send time</label>
                                        <input type="time" name="send_time" defaultValue={automation.send_time} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Timezone</label>
                                        <select name="timezone" defaultValue={automation.timezone} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                            {timezoneOptions.map((tz) => (
                                                <option key={tz} value={tz}>{tz}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr className="border-outline-variant" />

                            <div>
                                <h3 className="font-bold mb-3">Who receives this email</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {AUDIENCE_OPTIONS.map((opt) => (
                                        <label key={opt.value} className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                                            <input type="checkbox" name="audiences[]" value={opt.value} defaultChecked={selectedAudiences.includes(opt.value)} className="mt-1" />
                                            <span><span className="font-bold block text-sm">{opt.title}</span><span className="text-xs text-on-surface-variant">{opt.hint}</span></span>
                                        </label>
                                    ))}
                                </div>
                                <p className="text-xs text-on-surface-variant mt-3">Estimated unique recipients: <strong>{Number(recipientCount).toLocaleString()}</strong></p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-bold mb-2">Always include these emails</label>
                                    <textarea name="include_emails" rows={3} defaultValue={automation.include_emails || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="vip@example.com" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Always exclude these emails</label>
                                    <textarea name="exclude_emails" rows={3} defaultValue={automation.exclude_emails || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="one@example.com" />
                                </div>
                            </div>

                            <hr className="border-outline-variant" />

                            <div>
                                <h3 className="font-bold mb-3">Which classes to feature</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Course source</label>
                                        <select name="course_source" value={courseSource} onChange={(e) => setCourseSource(e.target.value)} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                            <option value="top">Top / featured courses</option>
                                            <option value="recent">Recently published</option>
                                            <option value="manual">Manual pick below</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Max classes in email</label>
                                        <input type="number" name="max_courses" min={1} max={12} defaultValue={automation.max_courses} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Skip people emailed in last N days</label>
                                        <input type="number" name="skip_recent_days" min={0} max={30} defaultValue={automation.skip_recent_days} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold mb-2">UTM campaign tag</label>
                                        <input type="text" name="utm_campaign" defaultValue={automation.utm_campaign} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-4 mb-4">
                                    <label className="inline-flex items-center gap-2 text-sm font-medium">
                                        <input type="checkbox" name="exclude_camps" value="1" defaultChecked={automation.exclude_camps} /> Exclude camp-only courses
                                    </label>
                                    <label className="inline-flex items-center gap-2 text-sm font-medium">
                                        <input type="checkbox" name="notify_admin_on_send" value="1" defaultChecked={automation.notify_admin_on_send} /> Email admin when a full send is queued
                                    </label>
                                </div>
                                {courseSource === 'manual' && (
                                    <div>
                                        <label className="block text-sm font-bold mb-2">Pick courses</label>
                                        <select name="course_ids[]" multiple size={10} defaultValue={selectedCourseIds.map(String)} className="staff-multiselect">
                                            {courses.map((c) => (
                                                <option key={c.id} value={c.id}>{c.title}{c.is_top_course ? ' ★' : ''}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>

                            <div className="flex flex-wrap gap-3 pt-2">
                                <button type="submit" className="btn-primary">Save settings</button>
                                <a href={automation.previewUrl} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm hover:border-primary">Preview email</a>
                            </div>
                        </form>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <form method="POST" action={automation.testUrl} className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 md:p-8 space-y-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <h2 className="font-headline-md font-bold">Send test email</h2>
                                <p className="text-sm text-on-surface-variant">Uses saved settings. Does not mark the scheduled send as done.</p>
                                <input type="email" name="test_email" required defaultValue="younimatiq@gmail.com" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                <button type="submit" className="btn-primary btn-block">Send test</button>
                            </form>

                            <form method="POST" action={automation.sendNowUrl} className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 md:p-8 space-y-4" onSubmit={(e) => { if (!confirm(`Queue this campaign to ${Number(recipientCount).toLocaleString()} recipients now?`)) e.preventDefault(); }}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <h2 className="font-headline-md font-bold">Send now</h2>
                                <p className="text-sm text-on-surface-variant">Immediately queues this digest to its audiences.</p>
                                <button type="submit" className="w-full px-4 py-3 rounded-xl bg-primary text-white font-bold">Queue full send</button>
                            </form>
                        </div>

                        <PortalCard className="p-5 md:p-8 space-y-4">
                            <h2 className="font-headline-md font-bold flex items-center gap-2">
                                <Icon name="edit_note" className="text-primary" /> Custom one-off email
                            </h2>
                            <p className="text-sm text-on-surface-variant">Uses this automation’s audiences and include/exclude lists.</p>
                            <form
                                method="POST"
                                action={automation.customSendUrl}
                                className="space-y-4"
                                onSubmit={(e) => {
                                    const sub = e.nativeEvent.submitter;
                                    if (sub?.value === 'audience' && !confirm(`Queue this custom email to ${Number(recipientCount).toLocaleString()} recipients now?`)) {
                                        e.preventDefault();
                                    }
                                }}
                            >
                                <input type="hidden" name="_token" value={csrfToken} />
                                <div>
                                    <label className="block text-sm font-bold mb-2">Subject</label>
                                    <input type="text" name="subject" value={customSubject} onChange={(e) => setCustomSubject(e.target.value)} required maxLength={180} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Message body</label>
                                    <textarea name="body" value={customBody} onChange={(e) => setCustomBody(e.target.value)} rows={8} required maxLength={50000} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary font-mono text-sm" placeholder="Plain text or HTML" />
                                </div>
                                <div className="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                                    <div className="flex-1">
                                        <label className="block text-sm font-bold mb-2">Test inbox</label>
                                        <input type="email" name="test_email" defaultValue="younimatiq@gmail.com" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                    </div>
                                    <button type="button" onClick={previewCustom} className="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Preview</button>
                                    <button type="submit" name="mode" value="test" className="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                                    <button type="submit" name="mode" value="audience" className="btn-primary">Send to audiences</button>
                                </div>
                            </form>
                        </PortalCard>
                    </div>

                    <div className="space-y-6">
                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">Featured in next send</h2>
                            <ul className="space-y-3">
                                {previewCourses.map((c, i) => (
                                    <li key={i} className="border-b border-outline-variant/50 pb-3">
                                        <a href={c.url} target="_blank" rel="noreferrer" className="font-bold text-primary text-sm">{c.title}</a>
                                        <div className="text-xs text-on-surface-variant">{c.age_label}</div>
                                    </li>
                                ))}
                                {!previewCourses.length && <li className="text-sm text-on-surface-variant">No published active courses found.</li>}
                            </ul>
                        </PortalCard>

                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">Recent runs</h2>
                            <div className="space-y-2 max-h-72 overflow-y-auto text-sm">
                                {recentRuns.map((run) => (
                                    <div key={run.id} className="p-3 rounded-xl bg-surface-container-low">
                                        <div className="flex justify-between gap-2">
                                            <span className="font-bold">{run.trigger} · {run.status}</span>
                                            <span className="text-xs text-on-surface-variant">{formatLocalTime(run.created_at, 'datetime-short')}</span>
                                        </div>
                                        <div className="text-xs text-on-surface-variant mt-1">Recipients {run.recipient_count} · Queued {run.sent_count}</div>
                                    </div>
                                ))}
                                {!recentRuns.length && <p className="text-on-surface-variant">No sends yet.</p>}
                            </div>
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
