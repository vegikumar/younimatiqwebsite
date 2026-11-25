import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';
import { formatLocalTime } from '../../../utils/formatLocalTime';

export default function Index({ automations = [], stats = {}, recentRuns = [], recentLogs = [], suppressions = [], routes = {} }) {
    const { flash, csrfToken } = usePage().props;

    const destroy = (url, name) => {
        if (!confirm(`Delete “${name}”? This cannot be undone.`)) return;
        router.delete(url, { preserveScroll: true });
    };

    const statCards = [
        { label: 'Sent 7d', value: stats.sent_7d },
        { label: 'Failed 7d', value: stats.failed_7d },
        { label: 'Sent 30d', value: stats.sent_30d },
        { label: 'Queued now', value: stats.queued },
        { label: 'Unsubscribed', value: stats.suppressions },
    ];

    return (
        <DashboardLayout title="Email Automations">
            <Head title="Email Automations" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                        <div>
                            <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Email Automations</h1>
                            <p className="font-body-lg text-body-lg text-on-surface-variant">
                                Create multiple campaigns — each with its own schedule, audiences, include/exclude lists, and copy.
                            </p>
                        </div>
                        <Link href={routes.create} className="btn-primary inline-flex items-center gap-2 shrink-0">
                            <Icon name="add" className="text-base" /> New automation
                        </Link>
                    </div>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                <div className="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
                    {statCards.map((s) => (
                        <PortalCard key={s.label} className="p-4">
                            <div className="text-xs text-on-surface-variant">{s.label}</div>
                            <div className="text-xl font-bold">{Number(s.value || 0).toLocaleString()}</div>
                        </PortalCard>
                    ))}
                </div>

                {flash?.success && <div className="mb-6 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-medium">{flash.success}</div>}
                {flash?.error && <div className="mb-6 p-4 rounded-xl bg-error-container text-on-error-container font-medium">{flash.error}</div>}

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
                    <div className="xl:col-span-2 space-y-4">
                        {automations.map((a) => (
                            <div key={a.id} className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 md:p-6 flex flex-col gap-4">
                                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2 mb-1">
                                            <h2 className="font-headline-md font-bold text-on-surface">{a.name}</h2>
                                            <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${a.is_enabled ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>
                                                {a.is_enabled ? 'ON' : 'OFF'}
                                            </span>
                                        </div>
                                        <p className="text-sm text-on-surface-variant">{a.subject}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2 shrink-0">
                                        <Link href={a.editUrl} className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm hover:border-primary">
                                            Edit
                                        </Link>
                                        <button type="button" onClick={() => destroy(a.destroyUrl, a.name)} className="px-4 py-2 rounded-xl border border-error/40 text-error font-bold text-sm hover:bg-error-container">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2 text-xs">
                                    <span className="px-2.5 py-1 rounded-full bg-surface-container-low">Audience: {Number(a.audienceCount).toLocaleString()}</span>
                                    <span className="px-2.5 py-1 rounded-full bg-surface-container-low">{a.scheduleLabel}</span>
                                    {a.nextSendLabel ? <span className="px-2.5 py-1 rounded-full bg-primary/10 text-primary">Next: {a.nextSendLabel}</span> : null}
                                    {a.lastSentAt ? (
                                        <span className="px-2.5 py-1 rounded-full bg-surface-container-low">
                                            Last: {formatLocalTime(a.lastSentAt, 'datetime-short')} ({a.lastSentCount})
                                        </span>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                        {!automations.length && (
                            <PortalCard className="p-8 text-center">
                                <p className="text-on-surface-variant mb-4">No email automations yet.</p>
                                <Link href={routes.create} className="btn-primary">
                                    Create your first automation
                                </Link>
                            </PortalCard>
                        )}
                    </div>

                    <div className="space-y-6">
                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">How to use</h2>
                            <ol className="space-y-2 text-sm text-on-surface-variant list-decimal pl-5">
                                <li><strong className="text-on-surface">New automation</strong> — create as many campaigns as you need.</li>
                                <li><strong className="text-on-surface">Edit</strong> — set copy, schedule, audiences, include/exclude.</li>
                                <li><strong className="text-on-surface">Test → enable</strong> — send a test, then turn auto-send on.</li>
                                <li><strong className="text-on-surface">Delete</strong> — remove campaigns you no longer need (keep at least one).</li>
                                <li><strong className="text-on-surface">Course triggers</strong> — pick which automation’s audience to use on a course edit page.</li>
                            </ol>
                        </PortalCard>

                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">Suppression / unsubscribe list</h2>
                            <form method="POST" action={routes.suppressionsStore} className="flex flex-col sm:flex-row gap-2 mb-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="email" name="email" required placeholder="email@example.com" className="flex-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                                <button type="submit" className="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Block</button>
                            </form>
                            <div className="space-y-2 max-h-56 overflow-y-auto text-sm">
                                {suppressions.map((row) => (
                                    <div key={row.id} className="flex items-center justify-between gap-2 p-2 rounded-lg bg-surface-container-low">
                                        <div>
                                            <div className="font-medium">{row.email}</div>
                                            <div className="text-xs text-on-surface-variant">{row.reason} · {row.suppressed_at_human}</div>
                                        </div>
                                        <form method="POST" action={row.destroyUrl}>
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="_method" value="DELETE" />
                                            <button type="submit" className="text-xs font-bold text-error">Remove</button>
                                        </form>
                                    </div>
                                ))}
                                {!suppressions.length && <p className="text-on-surface-variant">No suppressions yet.</p>}
                            </div>
                        </PortalCard>

                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">Recent runs</h2>
                            <div className="space-y-2 max-h-72 overflow-y-auto text-sm">
                                {recentRuns.map((run) => (
                                    <div key={run.id} className="p-3 rounded-xl bg-surface-container-low">
                                        <div className="flex justify-between gap-2">
                                            <span className="font-bold">{run.automationName} · {run.trigger}</span>
                                            <span className="text-xs text-on-surface-variant">{formatLocalTime(run.created_at, 'datetime-short')}</span>
                                        </div>
                                        <div className="text-xs text-on-surface-variant mt-1">
                                            {run.status} · Recipients {run.recipient_count} · Queued {run.sent_count}
                                        </div>
                                    </div>
                                ))}
                                {!recentRuns.length && <p className="text-on-surface-variant">No sends yet.</p>}
                            </div>
                        </PortalCard>

                        <PortalCard className="p-5 md:p-8">
                            <h2 className="font-headline-md font-bold mb-4">Recent delivery logs</h2>
                            <div className="overflow-x-auto max-h-72 overflow-y-auto">
                                <table className="staff-table w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-outline-variant">
                                            <th className="py-2 pr-2">To</th>
                                            <th className="py-2 pr-2">Status</th>
                                            <th className="py-2">When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentLogs.map((log) => (
                                            <tr key={log.id} className="border-b border-outline-variant/40">
                                                <td className="py-2 pr-2">{log.to_email}</td>
                                                <td className="py-2 pr-2">{log.status}</td>
                                                <td className="py-2">{formatLocalTime(log.created_at, 'datetime-short')}</td>
                                            </tr>
                                        ))}
                                        {!recentLogs.length && (
                                            <tr>
                                                <td colSpan={3} className="py-4 text-on-surface-variant">No campaign emails logged yet.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
