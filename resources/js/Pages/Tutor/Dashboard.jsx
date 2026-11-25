import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import EmptyState from '../../Components/Portal/EmptyState';
import { formatLocalTime } from '../../utils/formatLocalTime';
import './dashboard-class-lms.css';

function formatCountdown(ms) {
    if (ms <= 0) return 'Class in progress';
    const total = Math.floor(ms / 1000);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

function ProgressRing({ value }) {
    const r = 34;
    const c = 2 * Math.PI * r;
    const offset = c - (Math.min(100, Math.max(0, value)) / 100) * c;

    return (
        <svg className="tcl-ring" viewBox="0 0 80 80" aria-hidden="true">
            <circle className="tcl-ring__track" cx="40" cy="40" r={r} />
            <circle className="tcl-ring__value" cx="40" cy="40" r={r} strokeDasharray={c} strokeDashoffset={offset} />
            <text x="40" y="44" textAnchor="middle" className="tcl-ring__label">
                {value}%
            </text>
        </svg>
    );
}

function MaterialsPanel({ focus, csrfToken, completePrepUrl, activeStep, setActiveStep }) {
    const [busy, setBusy] = useState('');

    const mark = (itemKey, done = true) => {
        setBusy(itemKey);
        router.post(
            completePrepUrl,
            {
                _token: csrfToken,
                context_type: focus.contextType,
                context_id: focus.contextId,
                item_key: itemKey,
                done: done ? 1 : 0,
            },
            {
                preserveScroll: true,
                onFinish: () => setBusy(''),
            }
        );
    };

    if (activeStep === 'previous-report') {
        return (
            <div className="tcl-panel">
                <div className="tcl-panel__head">
                    <h2>Previous class report</h2>
                    <p>What to carry into today’s session.</p>
                </div>
                <p className="tcl-copy">{focus.previousReport}</p>
                {!focus.steps.find((s) => s.key === 'previous-report')?.completed ? (
                    <button type="button" className="tcl-btn tcl-btn--primary" disabled={busy === 'step:previous-report'} onClick={() => mark('step:previous-report')}>
                        Mark report reviewed
                    </button>
                ) : (
                    <p className="tcl-done">Reviewed ✓</p>
                )}
            </div>
        );
    }

    if (activeStep === 'objectives') {
        return (
            <div className="tcl-panel">
                <div className="tcl-panel__head">
                    <h2>Objectives & outcomes</h2>
                    <p>Confirm the learner can leave class with these wins.</p>
                </div>
                <p className="tcl-copy">{focus.objectives}</p>
                {!focus.steps.find((s) => s.key === 'objectives')?.completed ? (
                    <button type="button" className="tcl-btn tcl-btn--primary" disabled={busy === 'step:objectives'} onClick={() => mark('step:objectives')}>
                        Mark objectives ready
                    </button>
                ) : (
                    <p className="tcl-done">Ready ✓</p>
                )}
            </div>
        );
    }

    if (['lesson-plan', 'presentation', 'resources', 'checklist', 'build-notes'].includes(activeStep)) {
        const step = focus.steps.find((s) => s.key === activeStep);
        return (
            <div className="tcl-panel">
                <div className="tcl-panel__head">
                    <h2>{step?.label}</h2>
                    <p>{step?.optional ? 'Optional notes for today’s build.' : 'Review this prep item before class.'}</p>
                </div>
                <p className="tcl-copy">
                    {activeStep === 'lesson-plan' && 'Open the lesson plan from Class materials, then mark this step when you’ve walked the flow.'}
                    {activeStep === 'presentation' && 'Preview the slides / activity flow so transitions stay smooth live.'}
                    {activeStep === 'resources' && 'Confirm software, Scratch/project links, and any files the learner needs are ready.'}
                    {activeStep === 'checklist' && 'Camera, mic, lesson tabs, and join link checked. Greet the learner by name in the first minute.'}
                    {activeStep === 'build-notes' && 'Optional: jot build tips or stretch challenges for fast finishers.'}
                </p>
                {!step?.completed ? (
                    <button type="button" className="tcl-btn tcl-btn--primary" disabled={busy === step?.itemKey} onClick={() => mark(step.itemKey)}>
                        Mark complete
                    </button>
                ) : (
                    <p className="tcl-done">Complete ✓</p>
                )}
                <button type="button" className="tcl-linkish" onClick={() => setActiveStep('materials')}>
                    ← Back to class materials
                </button>
            </div>
        );
    }

    return (
        <div className="tcl-panel">
            <div className="tcl-panel__head tcl-panel__head--row">
                <div>
                    <h2>Class materials</h2>
                    <p>View only. Open each item, then mark it complete when you&apos;ve finished reading.</p>
                </div>
                <span className="tcl-pill tcl-pill--muted">
                    {focus.materialsDone} of {focus.materialsTotal} complete
                </span>
            </div>
            <div className="tcl-table-wrap">
                <table className="tcl-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Type</th>
                            <th>Access</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {focus.materials.map((row) => (
                            <tr key={row.key}>
                                <td className="tcl-table__name">{row.name}</td>
                                <td>{row.type}</td>
                                <td>
                                    {row.url ? (
                                        <a href={row.url} target="_blank" rel="noreferrer" className="tcl-btn tcl-btn--ghost">
                                            View
                                        </a>
                                    ) : (
                                        <span className="tcl-muted">—</span>
                                    )}
                                </td>
                                <td>
                                    {row.completed ? (
                                        <button type="button" className="tcl-btn tcl-btn--ghost is-done" disabled={busy === row.itemKey} onClick={() => mark(row.itemKey, false)}>
                                            Completed ✓
                                        </button>
                                    ) : (
                                        <button type="button" className="tcl-btn tcl-btn--ghost" disabled={busy === row.itemKey || !row.url} onClick={() => mark(row.itemKey)}>
                                            Mark complete
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function ClassLms({ focus, upcoming, completePrepUrl }) {
    const { csrfToken } = usePage().props;
    const [activeStep, setActiveStep] = useState('materials');
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const id = window.setInterval(() => setNow(Date.now()), 1000);
        return () => window.clearInterval(id);
    }, []);

    const startsIn = useMemo(() => {
        if (!focus.scheduledAt) return null;
        return new Date(focus.scheduledAt).getTime() - now;
    }, [focus.scheduledAt, now]);

    const whenLabel = focus.scheduledAt
        ? `${formatLocalTime(focus.scheduledAt, 'datetime-short')} · ${focus.durationMinutes} min`
        : `${focus.durationMinutes} min`;

    return (
        <div className="tcl">
            {upcoming.length > 1 ? (
                <div className="tcl-switcher">
                    {upcoming.map((c) => (
                        <Link
                            key={c.key}
                            href={`/tutor/dashboard?class=${encodeURIComponent(c.key)}`}
                            className={`tcl-switcher__chip ${c.key === focus.key ? 'is-active' : ''}`}
                            preserveScroll
                        >
                            {c.studentName}
                            {c.when ? ` · ${formatLocalTime(c.when, 'time')}` : ''}
                        </Link>
                    ))}
                </div>
            ) : null}

            <header className="tcl-hero">
                <div className="tcl-hero__main">
                    <h1>{focus.headline}</h1>
                    <div className="tcl-tags">
                        <span className="tcl-tag">{focus.courseTitle}</span>
                        <span className="tcl-tag">{focus.studentMeta}</span>
                        <span className="tcl-tag">{whenLabel}</span>
                        <span className="tcl-tag">{focus.classProgressLabel}</span>
                        {focus.carryForward ? <span className="tcl-tag tcl-tag--accent">{focus.carryForward}</span> : null}
                    </div>
                </div>
                <aside className="tcl-status">
                    <ProgressRing value={focus.progress} />
                    <div className="tcl-status__meta">
                        <p className="tcl-status__countdown">
                            {startsIn == null
                                ? 'Schedule TBD'
                                : startsIn > 0
                                  ? `Class starts in ${formatCountdown(startsIn)}`
                                  : 'Class window open'}
                        </p>
                        {focus.launchUrl ? (
                            <a href={focus.launchUrl} className="tcl-btn tcl-btn--solid">
                                Start class
                            </a>
                        ) : null}
                        <button
                            type="button"
                            className="tcl-btn tcl-btn--primary"
                            disabled={!focus.ready}
                            onClick={() => setActiveStep('checklist')}
                        >
                            {focus.ready ? 'Ready for class' : 'Complete required items'}
                        </button>
                    </div>
                </aside>
            </header>

            <div className="tcl-body">
                <nav className="tcl-steps" aria-label="Class prep steps">
                    {focus.steps.map((step) => (
                        <button
                            key={step.key}
                            type="button"
                            className={`tcl-step ${activeStep === step.key ? 'is-active' : ''} ${step.completed ? 'is-done' : ''} ${step.optional ? 'is-optional' : ''}`}
                            onClick={() => setActiveStep(step.key)}
                        >
                            <span className="tcl-step__icon" aria-hidden="true">
                                {step.completed ? '✓' : step.icon === 'x' ? '×' : step.icon === 'check' ? '✓' : step.icon}
                            </span>
                            <span className="tcl-step__label">{step.label}</span>
                        </button>
                    ))}
                </nav>

                <MaterialsPanel
                    focus={focus}
                    csrfToken={csrfToken}
                    completePrepUrl={completePrepUrl}
                    activeStep={activeStep}
                    setActiveStep={setActiveStep}
                />
            </div>
        </div>
    );
}

export default function Dashboard({
    metrics = {},
    skills = [],
    bookings = [],
    campBookings = [],
    sessions = [],
    training = {},
    classPrep = { focus: null, upcoming: [] },
    completePrepUrl = '/tutor/class-prep/complete',
}) {
    const focus = classPrep?.focus ?? null;
    const upcoming = classPrep?.upcoming ?? [];

    return (
        <DashboardLayout title="Dashboard">
            <Head title="Tutor Dashboard" />
            <div className="staff-page">
                {focus ? (
                    <ClassLms focus={focus} upcoming={upcoming} completePrepUrl={completePrepUrl} />
                ) : (
                    <div className="tcl tcl--empty">
                        <EmptyState
                            icon="event_busy"
                            title="No upcoming class to prep"
                            description="When a class is scheduled for you, this dashboard becomes your class-level LMS: materials, checklist, and countdown."
                        />
                        <div className="tcl-empty-actions">
                            <Link href="/tutor/classes" className="tcl-btn tcl-btn--primary">
                                View classes
                            </Link>
                            <Link href="/tutor/availability" className="tcl-btn tcl-btn--ghost">
                                Update availability
                            </Link>
                        </div>
                        {(training.pending_reviews || 0) > 0 ? (
                            <p className="tcl-copy">
                                Training: {training.pending_reviews} submission(s) awaiting HR review ·{' '}
                                <Link href="/tutor/training" className="tcl-linkish">
                                    Open training
                                </Link>
                            </p>
                        ) : null}
                        {skills.length ? (
                            <div className="tcl-tags" style={{ marginTop: 16 }}>
                                {skills.map((s, i) => (
                                    <span key={i} className={`tcl-tag ${s.verified ? '' : 'tcl-tag--accent'}`}>
                                        {s.name}
                                        {s.verified ? '' : ' · pending'}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                        <p className="tcl-muted" style={{ marginTop: 12 }}>
                            Learners taught: {metrics.learnersTaught ?? 0}
                            {bookings.length + campBookings.length + sessions.length
                                ? ` · ${bookings.length + campBookings.length + sessions.length} items in class lists`
                                : ''}
                        </p>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
