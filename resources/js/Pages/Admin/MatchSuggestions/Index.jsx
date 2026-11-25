import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import StatusBadge from '../../../Components/Portal/StatusBadge';
import EmptyState from '../../../Components/Portal/EmptyState';
import FilterBar from '../../../Components/Portal/FilterBar';
import PortalCard from '../../../Components/Portal/PortalCard';

const TONE = {
    pending: 'warning',
    suggested: 'secondary',
    assigned: 'success',
    dismissed: 'error',
};

function Breakdown({ breakdown }) {
    if (!breakdown || typeof breakdown !== 'object') return null;
    const rows = [
        ['Base', breakdown.base],
        ['Load', breakdown.load_penalty],
        ['Availability', breakdown.availability_bonus],
        ['NPS', breakdown.nps],
        ['Continuity', breakdown.continuity_bonus],
        ['Conflict', breakdown.conflict_penalty],
        ['Total', breakdown.total],
    ].filter(([, v]) => v != null);

    return (
        <dl className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs mt-2">
            {rows.map(([label, value]) => (
                <div key={label} className="rounded-lg bg-surface-container-low px-2 py-1">
                    <dt className="text-on-surface-variant">{label}</dt>
                    <dd className="font-bold tabular-nums">{value}</dd>
                </div>
            ))}
        </dl>
    );
}

export default function Index({ suggestions = [], filters = {}, counts = {}, pagination = {}, recentAudit = [] }) {
    const { csrfToken, flash } = usePage().props;

    const post = (url) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    };

    return (
        <DashboardLayout title="Match Inbox">
            <Head title="Tutor Match Suggestions" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Matching"
                    title="Tutor match inbox"
                    description="Review ranked suggestions with score breakdowns, assign a tutor, or dismiss."
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container font-bold">{flash.error}</div> : null}

                <FilterBar>
                    {[
                        { key: 'open', label: `Open (${counts.open ?? 0})` },
                        { key: 'assigned', label: `Assigned (${counts.assigned ?? 0})` },
                        { key: 'dismissed', label: `Dismissed (${counts.dismissed ?? 0})` },
                        { key: 'all', label: `All (${counts.all ?? 0})` },
                    ].map((f) => (
                        <Link
                            key={f.key}
                            href={`/admin/match-suggestions?status=${f.key}`}
                            className={`px-3 py-1.5 rounded-full text-sm font-bold ${
                                (filters.status || 'open') === f.key
                                    ? 'bg-primary text-white'
                                    : 'bg-surface-container-high text-on-surface'
                            }`}
                        >
                            {f.label}
                        </Link>
                    ))}
                </FilterBar>

                {suggestions.length === 0 ? (
                    <EmptyState
                        title="No match suggestions"
                        description="Suggestions appear after skill verification. Assign is only available when a booking exists and still needs a tutor."
                    />
                ) : (
                    <div className="space-y-4">
                        {suggestions.map((s) => (
                            <PortalCard key={s.id} padding className="space-y-3">
                                <div className="flex flex-wrap items-start justify-between gap-3 min-w-0">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <StatusBadge tone={TONE[s.status] || 'secondary'}>{s.status}</StatusBadge>
                                            <span className="font-bold text-lg tabular-nums">Score {Math.round(s.match_score ?? 0)}</span>
                                        </div>
                                        <p className="mt-1 font-bold break-words">{s.tutor?.name || 'Unknown tutor'}</p>
                                        <p className="text-sm text-on-surface-variant truncate">{s.tutor?.email}</p>
                                    </div>
                                    <div className="text-sm text-right min-w-0">
                                        <p className="font-bold break-words">{s.course?.title || 'Course'}</p>
                                        {s.booking ? (
                                            <p className="text-on-surface-variant">
                                                Booking #{s.booking.id}
                                                {s.booking.student
                                                    ? ` · ${s.booking.student.name}${s.booking.student.email ? ` (${s.booking.student.email})` : ''}`
                                                    : ''}
                                            </p>
                                        ) : (
                                            <p className="text-on-surface-variant">Course-level (no booking yet)</p>
                                        )}
                                    </div>
                                </div>
                                <p className="text-sm">{s.reason}</p>
                                <Breakdown breakdown={s.score_breakdown} />
                                {['pending', 'suggested'].includes(s.status) ? (
                                    <div className="flex flex-wrap gap-2 pt-1">
                                        {s.booking && !s.booking.has_tutor ? (
                                            <button
                                                type="button"
                                                className="btn-primary text-sm"
                                                onClick={() => post(`/admin/match-suggestions/${s.id}/assign`)}
                                            >
                                                Assign tutor
                                            </button>
                                        ) : (
                                            <p className="text-xs text-on-surface-variant self-center">
                                                {s.booking?.has_tutor
                                                    ? 'Booking already has a tutor'
                                                    : 'Assign available once a booking is linked'}
                                            </p>
                                        )}
                                        <button
                                            type="button"
                                            className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm"
                                            onClick={() => post(`/admin/match-suggestions/${s.id}/dismiss`)}
                                        >
                                            Dismiss
                                        </button>
                                    </div>
                                ) : null}
                            </PortalCard>
                        ))}
                    </div>
                )}

                {pagination.last_page > 1 ? (
                    <p className="text-sm text-on-surface-variant text-center">
                        Page {pagination.current_page} of {pagination.last_page} · {pagination.total} total
                    </p>
                ) : null}

                {recentAudit.length > 0 ? (
                    <PortalCard title="Recent HR / match audit">
                        <ul className="space-y-2 text-sm">
                            {recentAudit.map((e) => (
                                <li key={e.id} className="flex flex-wrap gap-x-3 gap-y-1 border-b border-outline-variant/30 pb-2">
                                    <span className="font-bold">{e.event_type}</span>
                                    <span className="text-on-surface-variant">{e.actor || 'system'}</span>
                                    {e.subject ? <span>→ {e.subject}</span> : null}
                                    <span className="text-on-surface-variant ml-auto">{e.created_at ? new Date(e.created_at).toLocaleString() : ''}</span>
                                </li>
                            ))}
                        </ul>
                    </PortalCard>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
