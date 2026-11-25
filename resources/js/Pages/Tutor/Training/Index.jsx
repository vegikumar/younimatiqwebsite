import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import EmptyState from '../../../Components/Portal/EmptyState';
import StatusBadge from '../../../Components/Portal/StatusBadge';

function stripExtension(name) {
    return String(name || '')
        .replace(/\.[a-z0-9]{1,5}$/i, '')
        .replace(/[_-]+/g, ' ')
        .trim();
}

function contentNameFromDoc(doc, item) {
    const raw = stripExtension(doc?.label || '') || stripExtension(item?.title) || 'Document';
    const lower = raw.toLowerCase();
    if (/lesson\s*plan|lessonplan/.test(lower)) return 'Lesson Plan';
    if (/\bin[\s-]?class\b/.test(lower) && /game/.test(lower)) return 'In class Game';
    if (/\bgame\b/.test(lower)) return 'In class Game';
    if (/\bbook\b|\bdiary\b|\bwimpy\b/.test(lower)) return 'Book';
    if (/^open link$/i.test(raw)) return item?.title || 'Link';
    // Prefer a short type-like prefix before a dash: "Lesson Plan - Foo"
    const dash = raw.split(/\s*[-–—]\s*/)[0]?.trim();
    if (dash && dash.length < 40 && dash.length < raw.length) return dash;
    return raw;
}

function buildRows(item) {
    if (item.type === 'document') {
        const docs = item.documents?.length
            ? item.documents
            : [
                  ...(item.documentUrl ? [{ kind: 'url', label: 'Open link', url: item.documentUrl }] : []),
                  ...(item.documentSrc ? [{ kind: 'file', label: item.title || 'Download', url: item.documentSrc }] : []),
              ];
        if (!docs.length) {
            return [
                {
                    key: `${item.id}-empty`,
                    contentName: item.title || 'Document',
                    linkUrl: null,
                    linkLabel: '—',
                    item,
                    action: 'document',
                },
            ];
        }
        return docs.map((doc, di) => ({
            key: `${item.id}-doc-${di}`,
            contentName: contentNameFromDoc(doc, item),
            linkUrl: doc.url,
            linkLabel: doc.kind === 'url' ? 'Open link ↗' : doc.label || 'Open 📎',
            item,
            action: 'document',
        }));
    }

    if (item.type === 'video') {
        return [
            {
                key: `${item.id}-video`,
                contentName: item.title || 'Video',
                linkUrl: item.videoSrc || null,
                linkLabel: item.videoSrc ? 'Watch video' : '—',
                item,
                action: 'video',
            },
        ];
    }

    if (item.type === 'assignment') {
        return [
            {
                key: `${item.id}-assignment`,
                contentName: item.title || 'Assignment',
                linkUrl: null,
                linkLabel: item.instructions || 'Submit below',
                item,
                action: 'assignment',
            },
        ];
    }

    if (item.type === 'quiz') {
        return [
            {
                key: `${item.id}-quiz`,
                contentName: item.title || 'Quiz',
                linkUrl: null,
                linkLabel: `Pass ≥ ${item.passScore ?? 70}%`,
                item,
                action: 'quiz',
            },
        ];
    }

    return [
        {
            key: `${item.id}-other`,
            contentName: item.title || item.type,
            linkUrl: null,
            linkLabel: '—',
            item,
            action: 'other',
        },
    ];
}

function moduleHeading(module) {
    const course = module.courseName || module.title || 'Course';
    const className = module.className || module.title || 'Class';
    const num = module.classNumber ? String(module.classNumber) : null;
    return num ? `${course}, ${className} - Class ${num}` : `${course}, ${className}`;
}

export default function TrainingIndex({ skills = [], assignments = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [submittingKey, setSubmittingKey] = useState('');

    return (
        <DashboardLayout title="Training">
            <Head title="My Training" />
            <div className="staff-page max-w-5xl">
                <PageHeader
                    variant="staff"
                    eyebrow="Skills"
                    title="My training"
                    description="Complete assigned content before class. When everything is done, the module shows Ready for Class."
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container font-bold">{flash.error}</div> : null}

                <PortalCard title="My skills">
                    <div className="flex flex-wrap gap-2">
                        {skills.length ? (
                            skills.map((s, i) => (
                                <StatusBadge key={i} tone={s.verified ? 'success' : 'neutral'}>
                                    {s.name}
                                    {s.verified ? ' verified' : ' pending'}
                                </StatusBadge>
                            ))
                        ) : (
                            <p className="text-sm text-on-surface-variant">No skills yet — complete training modules to earn them.</p>
                        )}
                    </div>
                </PortalCard>

                {assignments.length === 0 ? (
                    <EmptyState icon="workspace_premium" title="No training assigned" description="When admin assigns a module, it will appear here." />
                ) : null}

                {assignments.map((a) => {
                    const rows = (a.module.items || []).flatMap(buildRows);
                    const ready = Boolean(a.module.readyForClass);

                    return (
                        <PortalCard
                            key={a.id}
                            title={moduleHeading(a.module)}
                            description={a.module.description}
                            actions={
                                ready ? (
                                    <StatusBadge tone="success">Ready for Class</StatusBadge>
                                ) : (
                                    <StatusBadge tone="neutral">In progress</StatusBadge>
                                )
                            }
                        >
                            <div className="overflow-x-auto rounded-xl border border-outline-variant/25">
                                <table className="w-full min-w-[640px] text-sm text-left">
                                    <thead className="bg-surface-container-low text-on-surface-variant">
                                        <tr>
                                            <th className="px-4 py-3 font-bold">Content Name</th>
                                            <th className="px-4 py-3 font-bold">Link</th>
                                            <th className="px-4 py-3 font-bold w-48">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row) => {
                                            const item = row.item;
                                            const passed = item.status === 'passed';
                                            const pendingReview = ['under_review', 'submitted', 'resubmitted'].includes(item.status);

                                            return (
                                                <tr key={row.key} className="border-t border-outline-variant/20 align-top">
                                                    <td className="px-4 py-3 font-bold text-on-surface">{row.contentName}</td>
                                                    <td className="px-4 py-3">
                                                        {row.linkUrl ? (
                                                            <a
                                                                href={row.linkUrl}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-primary font-bold underline break-all"
                                                            >
                                                                {row.linkLabel}
                                                            </a>
                                                        ) : (
                                                            <span className="text-on-surface-variant break-words">{row.linkLabel}</span>
                                                        )}
                                                        {row.action === 'video' && item.videoSrc ? (
                                                            <video controls className="mt-2 w-full max-w-md max-h-40 rounded-lg bg-black" src={item.videoSrc} />
                                                        ) : null}
                                                        {row.action === 'quiz' && !passed ? (
                                                            <form method="POST" action={item.quizUrl} className="mt-3 space-y-2">
                                                                <input type="hidden" name="_token" value={csrfToken} />
                                                                {(item.quizQuestions || []).map((q, i) => (
                                                                    <div key={i}>
                                                                        <p className="font-bold text-xs mb-1 break-words">{q.q ?? 'Question'}</p>
                                                                        {(q.options || []).map((opt, oi) => (
                                                                            <label key={oi} className="flex gap-2 text-xs break-words">
                                                                                <input type="radio" name={`answers[${i}]`} value={oi} required className="shrink-0" /> {opt}
                                                                            </label>
                                                                        ))}
                                                                    </div>
                                                                ))}
                                                                <button className="px-3 py-1.5 bg-tertiary text-white rounded-lg font-bold text-xs">Submit quiz</button>
                                                            </form>
                                                        ) : null}
                                                        {row.action === 'quiz' && passed ? (
                                                            <p className="mt-1 text-xs text-green-700 font-bold">Score: {item.score}%</p>
                                                        ) : null}
                                                        {row.action === 'assignment' && !passed && !pendingReview ? (
                                                            <form
                                                                method="POST"
                                                                action={item.assignmentUrl}
                                                                encType="multipart/form-data"
                                                                className="mt-3 space-y-2"
                                                                onSubmit={() => setSubmittingKey(`assignment-${item.id}`)}
                                                            >
                                                                <input type="hidden" name="_token" value={csrfToken} />
                                                                {item.status === 'revision_requested' ? (
                                                                    <p className="text-xs text-warning font-bold">Revision requested — please update and resubmit.</p>
                                                                ) : null}
                                                                <textarea
                                                                    name="submission_text"
                                                                    rows={2}
                                                                    placeholder="Your response"
                                                                    className="w-full min-w-0 p-2 rounded-lg border border-outline-variant/30 text-xs"
                                                                />
                                                                <input type="file" name="submission_file" className="block text-xs" />
                                                                <button
                                                                    disabled={Boolean(submittingKey)}
                                                                    className="px-3 py-1.5 bg-secondary text-white rounded-lg font-bold text-xs disabled:opacity-60"
                                                                >
                                                                    {submittingKey === `assignment-${item.id}`
                                                                        ? 'Submitting…'
                                                                        : item.status === 'revision_requested' || item.status === 'rejected'
                                                                          ? 'Resubmit'
                                                                          : 'Submit'}
                                                                </button>
                                                            </form>
                                                        ) : null}
                                                        {row.action === 'assignment' && pendingReview ? (
                                                            <p className="mt-1 text-xs font-bold text-warning">Submitted — waiting for HR review.</p>
                                                        ) : null}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {passed ? (
                                                            <span className="text-xs font-bold text-green-700">Completed ✓</span>
                                                        ) : row.action === 'document' ? (
                                                            <form
                                                                method="POST"
                                                                action={item.documentCompleteUrl}
                                                                onSubmit={() => setSubmittingKey(`doc-${item.id}-${row.key}`)}
                                                            >
                                                                <input type="hidden" name="_token" value={csrfToken} />
                                                                <button
                                                                    disabled={Boolean(submittingKey)}
                                                                    className="px-3 py-2 bg-primary text-white rounded-lg font-bold text-xs disabled:opacity-60 whitespace-nowrap"
                                                                >
                                                                    {submittingKey === `doc-${item.id}-${row.key}` ? 'Saving…' : 'Mark Completed'}
                                                                </button>
                                                            </form>
                                                        ) : row.action === 'video' ? (
                                                            <form
                                                                method="POST"
                                                                action={item.videoUrl}
                                                                onSubmit={() => setSubmittingKey(`video-${item.id}`)}
                                                            >
                                                                <input type="hidden" name="_token" value={csrfToken} />
                                                                <button
                                                                    disabled={Boolean(submittingKey)}
                                                                    className="px-3 py-2 bg-primary text-white rounded-lg font-bold text-xs disabled:opacity-60 whitespace-nowrap"
                                                                >
                                                                    {submittingKey === `video-${item.id}` ? 'Saving…' : 'Mark Completed'}
                                                                </button>
                                                            </form>
                                                        ) : pendingReview ? (
                                                            <span className="text-xs font-bold text-warning">Under review</span>
                                                        ) : (
                                                            <span className="text-xs text-on-surface-variant">Complete via link column</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                            {ready ? (
                                <p className="mt-3 text-sm font-bold text-green-700">Ready for Class — all content completed before class.</p>
                            ) : null}
                        </PortalCard>
                    );
                })}
            </div>
        </DashboardLayout>
    );
}
