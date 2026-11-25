import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function CopyField({ label, value, openLabel }) {
    const [copied, setCopied] = useState(false);
    if (!value) return null;

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            setTimeout(() => setCopied(false), 1600);
        } catch (_) {
            /* ignore */
        }
    };

    return (
        <div className="rounded-xl border border-outline-variant/30 bg-surface-container-low/40 p-4 space-y-2 min-w-0">
            <div className="text-xs font-bold uppercase tracking-wide text-on-surface-variant">{label}</div>
            <p className="text-sm break-all text-on-surface">{value}</p>
            <div className="flex flex-wrap gap-2 pt-1">
                <a href={value} target="_blank" rel="noopener noreferrer" className="btn-primary btn-sm inline-flex items-center gap-1">
                    <Icon name="open_in_new" className="text-base" />
                    {openLabel}
                </a>
                <button type="button" onClick={copy} className="px-3 py-2 rounded-lg text-sm font-bold bg-white border border-outline-variant/40 hover:bg-primary-container">
                    {copied ? 'Copied' : 'Copy link'}
                </button>
            </div>
        </div>
    );
}

export default function Index({ status = {}, activeProvider = 'zoom', lastMeetingTest = null, integrations = [] }) {
    const { csrfToken, flash } = usePage().props;
    const tutorUrl = lastMeetingTest?.tutor_url || lastMeetingTest?.start_url || null;
    const studentUrl = lastMeetingTest?.student_url || lastMeetingTest?.join_url || null;

    return (
        <DashboardLayout title="Integrations">
            <Head title="Integrations Hub" />
            <div className="staff-page">
                <PageHeader variant="staff" eyebrow="Platform" title="Integrations hub" description="Payment, meeting, and third-party service connections." />

                {flash?.success ? <div className="mb-4 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="mb-4 p-4 bg-error-container rounded-xl font-bold">{flash.error}</div> : null}

                <PortalCard className="border-primary/25 space-y-4 min-w-0">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <h3 className="font-bold text-lg flex items-center gap-2">
                                <Icon name="meeting_room" className="text-primary text-2xl" />
                                Younimatiq Meet — test class
                            </h3>
                            <p className="text-sm text-on-surface-variant mt-1">
                                Creates a real Meet room on this server and shows Tutor + Student links so you can join from two browsers.
                            </p>
                        </div>
                        <form method="POST" action="/admin/integrations/test">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="service" value="meet" />
                            <button type="submit" className="btn-primary inline-flex items-center gap-2">
                                <Icon name="add_circle" className="text-xl" />
                                Create test Meet class
                            </button>
                        </form>
                    </div>

                    {lastMeetingTest && (tutorUrl || studentUrl) ? (
                        <div className="space-y-3 border-t border-outline-variant/25 pt-4">
                            <div className="flex flex-wrap items-center gap-2 text-sm text-on-surface-variant">
                                <span className="font-bold text-on-surface">Last test class</span>
                                {lastMeetingTest.topic ? <span>· {lastMeetingTest.topic}</span> : null}
                                {lastMeetingTest.id ? (
                                    <code className="px-2 py-0.5 rounded bg-surface-container-low text-xs">{lastMeetingTest.id}</code>
                                ) : null}
                            </div>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                <CopyField label="Tutor link (host)" value={tutorUrl} openLabel="Open as tutor" />
                                <CopyField label="Student link (join)" value={studentUrl} openLabel="Open as student" />
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-on-surface-variant border-t border-outline-variant/25 pt-4">
                            No test class yet — click the button above.
                        </p>
                    )}
                </PortalCard>

                <PortalCard className="bg-primary/5 border-primary/20 flex gap-4 min-w-0">
                    <Icon name="info" className="text-primary text-3xl shrink-0" />
                    <div>
                        <h3 className="font-bold text-lg mb-2">Universal Test Mode Credentials</h3>
                        <p className="text-on-surface-variant text-sm mb-4">Use these details to simulate successful student checkouts in sandbox mode.</p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-on-surface-variant">
                            <div>
                                <h4 className="font-bold text-primary mb-2">Stripe Test Card</h4>
                                <ul className="space-y-1">
                                    <li>4242 4242 4242 4242</li>
                                    <li>Any future expiry, any CVC</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-bold text-primary mb-2">Razorpay Test Card</h4>
                                <ul className="space-y-1">
                                    <li>4111 1111 1111 1111</li>
                                    <li>OTP: 1226</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </PortalCard>

                <PortalCard className="bg-secondary/5 border-secondary/20 min-w-0">
                    <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h3 className="font-bold text-lg">Active class meeting provider</h3>
                        <span className={`px-3 py-1 rounded-full text-xs font-bold ${activeProvider === 'meet' ? 'bg-primary-fixed text-primary' : 'bg-secondary-container text-on-secondary-container'}`}>
                            {String(activeProvider).toUpperCase()}
                        </span>
                    </div>
                    <Link href="/admin/settings" className="text-sm font-bold text-primary hover:underline">
                        Open Gateways settings →
                    </Link>
                </PortalCard>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                    {integrations.map((integration) => (
                        <PortalCard key={integration.key} className="min-w-0">
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-3">
                                    <Icon name={integration.icon} className="text-primary text-2xl" />
                                    <h3 className="font-bold">{integration.name}</h3>
                                </div>
                                <span className={`px-3 py-1 rounded-full text-xs font-bold ${status[integration.key] ? 'bg-tertiary-fixed text-tertiary' : 'bg-error-container text-error'}`}>
                                    {status[integration.key] ? 'Configured' : 'Not Set'}
                                </span>
                            </div>
                            <div className="flex gap-2">
                                <Link href={integration.settingsRoute} className="px-4 py-2 bg-surface-container-low rounded-lg text-sm font-bold hover:bg-primary-container">
                                    Settings
                                </Link>
                                <form method="POST" action="/admin/integrations/test">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="service" value={integration.key} />
                                    <button type="submit" className="btn-primary btn-sm">
                                        Test
                                    </button>
                                </form>
                            </div>
                        </PortalCard>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
