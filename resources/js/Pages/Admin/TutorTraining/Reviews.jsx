import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import StatusBadge from '../../../Components/Portal/StatusBadge';
import EmptyState from '../../../Components/Portal/EmptyState';
import FilterBar from '../../../Components/Portal/FilterBar';
import PortalCard from '../../../Components/Portal/PortalCard';

const TONE = {
    under_review: 'warning',
    submitted: 'secondary',
    resubmitted: 'secondary',
    revision_requested: 'warning',
    passed: 'success',
    rejected: 'error',
};

export default function Reviews({ submissions = [], filters = {}, counts = {}, pagination = {} }) {
    const { csrfToken, flash } = usePage().props;
    const [notes, setNotes] = useState({});
    const [scores, setScores] = useState({});
    const [busyKey, setBusyKey] = useState('');

    const setNote = (id, value) => setNotes((prev) => ({ ...prev, [id]: value }));
    const setScore = (id, value) => setScores((prev) => ({ ...prev, [id]: value }));

    const post = (url, body = {}, key = '') => {
        if (busyKey) return;
        setBusyKey(key || url);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        form.appendChild(token);
        Object.entries(body).forEach(([k, v]) => {
            if (v == null || v === '') return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = v;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    };

    return (
        <DashboardLayout title="HR Training Reviews">
            <Head title="HR Training Reviews" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Tutor skills"
                    title="HR training review queue"
                    description="Approve, reject, or request revision before verified skills are granted."
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container font-bold">{flash.error}</div> : null}

                <FilterBar>
                    {['under_review', 'revision_requested', 'passed', 'rejected', 'all'].map((s) => (
                        <button
                            key={s}
                            type="button"
                            onClick={() => router.get('/admin/tutor-training/reviews', { status: s }, { preserveState: true })}
                            className={`px-3 py-1.5 rounded-xl text-sm font-bold ${
                                (filters.status || 'under_review') === s ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface-variant'
                            }`}
                        >
                            {s.replace('_', ' ')}
                            {s === 'under_review' && counts.under_review ? ` (${counts.under_review})` : ''}
                        </button>
                    ))}
                </FilterBar>

                {submissions.length === 0 ? (
                    <EmptyState icon="fact_check" title="Queue clear" description="No submissions match this filter." />
                ) : (
                    <div className="space-y-4">
                        {submissions.map((row) => (
                            <PortalCard key={row.id}>
                                <div className="flex flex-wrap items-start justify-between gap-3 min-w-0 -mt-1">
                                    <div className="min-w-0">
                                        <h2 className="font-bold text-lg break-words">{row.tutor?.name}</h2>
                                        <p className="text-sm text-on-surface-variant">
                                            {row.module_title} · {row.item_title}
                                        </p>
                                        <p className="text-xs text-on-surface-variant mt-1">Submitted {row.submitted_at_label || '—'}</p>
                                    </div>
                                    <StatusBadge tone={TONE[row.status] || 'neutral'}>{row.status?.replace('_', ' ')}</StatusBadge>
                                </div>

                                {row.submission_text ? (
                                    <div className="rounded-xl bg-surface-container-low p-4 text-sm whitespace-pre-wrap">{row.submission_text}</div>
                                ) : null}
                                {row.submission_url ? (
                                    <a href={row.submission_url} target="_blank" rel="noreferrer" className="text-sm font-bold text-primary hover:underline">
                                        Open submission file
                                    </a>
                                ) : null}
                                {row.review_notes ? <p className="text-sm text-on-surface-variant">Prior notes: {row.review_notes}</p> : null}

                                {['under_review', 'submitted', 'resubmitted'].includes(row.status) ? (
                                    <div className="grid md:grid-cols-[1fr_120px_auto] gap-2 pt-2 border-t border-outline-variant/40">
                                        <textarea
                                            rows={2}
                                            placeholder="Review notes"
                                            className="rounded-xl border px-3 py-2 text-sm bg-surface-container-low"
                                            value={notes[row.id] || ''}
                                            onChange={(e) => setNote(row.id, e.target.value)}
                                        />
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="Score"
                                            className="rounded-xl border px-3 py-2 text-sm bg-surface-container-low"
                                            value={scores[row.id] || ''}
                                            onChange={(e) => setScore(row.id, e.target.value)}
                                        />
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                disabled={Boolean(busyKey)}
                                                className="btn-primary btn-sm disabled:opacity-60"
                                                onClick={() =>
                                                    post(
                                                        `/admin/tutor-training/reviews/${row.id}/approve`,
                                                        {
                                                            notes: notes[row.id],
                                                            score: scores[row.id],
                                                        },
                                                        `approve-${row.id}`
                                                    )
                                                }
                                            >
                                                {busyKey === `approve-${row.id}` ? 'Approving…' : 'Approve'}
                                            </button>
                                            <button
                                                type="button"
                                                disabled={Boolean(busyKey)}
                                                className="px-3 py-1.5 rounded-xl bg-warning-container text-warning text-sm font-bold disabled:opacity-60"
                                                onClick={() =>
                                                    post(
                                                        `/admin/tutor-training/reviews/${row.id}/revision`,
                                                        {
                                                            notes: notes[row.id] || 'Please revise and resubmit.',
                                                        },
                                                        `revision-${row.id}`
                                                    )
                                                }
                                            >
                                                {busyKey === `revision-${row.id}` ? 'Saving…' : 'Revision'}
                                            </button>
                                            <button
                                                type="button"
                                                disabled={Boolean(busyKey)}
                                                className="px-3 py-1.5 rounded-xl bg-error-container text-on-error-container text-sm font-bold disabled:opacity-60"
                                                onClick={() =>
                                                    post(
                                                        `/admin/tutor-training/reviews/${row.id}/reject`,
                                                        {
                                                            notes: notes[row.id],
                                                        },
                                                        `reject-${row.id}`
                                                    )
                                                }
                                            >
                                                {busyKey === `reject-${row.id}` ? 'Rejecting…' : 'Reject'}
                                            </button>
                                        </div>
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
            </div>
        </DashboardLayout>
    );
}
