import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import EmptyState from '../../Components/Portal/EmptyState';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Growth({ leads = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [nudgingId, setNudgingId] = useState(null);

    return (
        <DashboardLayout title="Growth">
            <Head title="Growth & Re-engagement Hub" />
            <div className="staff-page max-w-6xl">
                <PageHeader
                    variant="staff"
                    title="Growth & re-engagement"
                    description="Follow up with families who showed interest or abandoned checkout for your classes."
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold text-sm">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container text-error font-bold text-sm">{flash.error}</div> : null}

                <PortalCard title="Interested families">
                    {!leads.length ? (
                        <EmptyState
                            icon="trending_up"
                            title="No leads yet"
                            description="Families who explore your classes or abandon checkout will appear here automatically."
                        />
                    ) : (
                        <DataTableShell
                            mobileCards={leads.map((lead) => (
                                <div key={lead.id} className="staff-mobile-card space-y-2">
                                    <p className="text-sm font-extrabold break-words">{lead.familyName}</p>
                                    <p className="text-xs text-on-surface-variant break-words">{lead.courseTitle}</p>
                                    <p className="text-xs text-on-surface-variant">{formatLocalTime(lead.createdAt, 'date')}</p>
                                    <div className="flex flex-wrap items-center justify-between gap-2 pt-1">
                                        <span
                                            className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                                lead.status === 'nudged'
                                                    ? 'bg-secondary-container text-on-secondary-container'
                                                    : 'bg-warning-container text-warning'
                                            }`}
                                        >
                                            {lead.status}
                                        </span>
                                        {lead.status !== 'enrolled' ? (
                                            <form method="POST" action={lead.nudgeUrl} onSubmit={() => setNudgingId(lead.id)}>
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button type="submit" disabled={nudgingId === lead.id} className="btn-enroll py-1.5 px-3 text-[11px] font-bold disabled:opacity-60">
                                                    {nudgingId === lead.id ? 'Sending…' : 'Send nudge'}
                                                </button>
                                            </form>
                                        ) : (
                                            <span className="text-xs text-on-tertiary-container font-bold">Enrolled</span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        >
                            <table className="staff-table text-xs">
                                <thead>
                                    <tr>
                                        <th>Family / Learner</th>
                                        <th>Class Explored</th>
                                        <th>Interest Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {leads.map((lead) => (
                                        <tr key={lead.id}>
                                            <td className="font-bold">
                                                <span className="break-words">{lead.familyName}</span>
                                            </td>
                                            <td className="font-medium">
                                                <span className="break-words">{lead.courseTitle}</span>
                                            </td>
                                            <td className="text-on-surface-variant">{formatLocalTime(lead.createdAt, 'date')}</td>
                                            <td>
                                                <span
                                                    className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                                        lead.status === 'nudged'
                                                            ? 'bg-secondary-container text-on-secondary-container'
                                                            : 'bg-warning-container text-warning'
                                                    }`}
                                                >
                                                    {lead.status}
                                                </span>
                                            </td>
                                            <td>
                                                {lead.status !== 'enrolled' ? (
                                                    <form method="POST" action={lead.nudgeUrl} onSubmit={() => setNudgingId(lead.id)}>
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <button type="submit" disabled={nudgingId === lead.id} className="btn-enroll py-1.5 px-3 text-[11px] font-bold disabled:opacity-60">
                                                            {nudgingId === lead.id ? 'Sending…' : 'Send re-engagement nudge'}
                                                        </button>
                                                    </form>
                                                ) : (
                                                    <span className="text-xs text-on-tertiary-container font-bold">Enrolled</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </DataTableShell>
                    )}
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
