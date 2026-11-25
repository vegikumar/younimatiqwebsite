import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import KpiCard from '../../Components/Portal/KpiCard';
import EmptyState from '../../Components/Portal/EmptyState';
import StatusBadge from '../../Components/Portal/StatusBadge';
import DashboardSection from '../../Components/Portal/DashboardSection';
import QuickLinks from '../../Components/Portal/QuickLinks';
import Icon from '../../Components/Icon';

export default function Dashboard({ children = [], selectedChildId = null, child = null }) {
    const { csrfToken, flash, auth } = usePage().props;

    return (
        <DashboardLayout title="Parent Dashboard">
            <Head title="Parent Dashboard" />
            <div className="mx-auto max-w-[1400px] space-y-6 p-5 md:p-8">
                <PageHeader
                    variant="parent"
                    eyebrow="Younimatiq family"
                    title={auth?.user?.name ? `Welcome, ${auth.user.name}` : 'Parent dashboard'}
                    description="Track each child's progress, classes, payments, and AI privacy controls."
                    actions={
                        <>
                            <Link href="/parent/referrals" className="inline-flex items-center gap-1.5 rounded-xl bg-white text-tertiary px-3.5 py-2 text-xs font-extrabold shadow-sm">
                                <Icon name="share" className="text-base" /> Referrals
                            </Link>
                            <Link href="/contact" className="inline-flex items-center gap-1.5 rounded-xl bg-white/15 text-white px-3.5 py-2 text-xs font-bold border border-white/25">
                                <Icon name="support_agent" className="text-base" /> Support
                            </Link>
                        </>
                    }
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold text-sm">{flash.success}</div> : null}

                {children.length === 0 ? (
                    <div className="space-y-4">
                        <EmptyState
                            icon="family_restroom"
                            title="No children linked yet"
                            description="Your parent login works — next, link a student profile so you can see classes, progress, and AI privacy controls."
                        />
                        <div className="clay-card p-5 md:p-6 space-y-4 max-w-2xl mx-auto">
                            <h2 className="font-bold text-lg flex items-center gap-2">
                                <Icon name="link" className="text-primary" /> How to link your child
                            </h2>
                            <ol className="list-decimal pl-5 text-sm text-on-surface-variant space-y-2">
                                <li>Confirm the email on this parent account is the one you shared during enrollment.</li>
                                <li>Ask Younimatiq support or admin to attach that email as the parent on your child’s student profile.</li>
                                <li>Refresh this page — your child’s dashboard cards will appear here.</li>
                            </ol>
                            <p className="text-xs text-on-surface-variant rounded-xl bg-surface-container-low p-3">
                                Status: waiting for a linked student. Once linked, you can manage AI class-summary consent and track payments from this page.
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <Link href="/contact" className="btn-primary text-sm px-4 py-2">
                                    Contact support
                                </Link>
                                <Link href="/parent/referrals" className="px-4 py-2 rounded-xl border border-outline-variant text-sm font-bold">
                                    View referrals
                                </Link>
                            </div>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="flex flex-wrap gap-2">
                            {children.map((c) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => router.get('/parent/dashboard', { child: c.id }, { preserveState: true })}
                                    className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-sm font-bold transition-all border ${
                                        selectedChildId === c.id
                                            ? 'bg-primary text-white border-primary shadow-md shadow-primary/25'
                                            : 'bg-white text-on-surface-variant border-outline-variant/50 hover:border-primary/40'
                                    }`}
                                >
                                    <span className={`grid h-7 w-7 place-items-center rounded-lg text-xs ${selectedChildId === c.id ? 'bg-white/20' : 'bg-primary-fixed text-primary'}`}>
                                        {(c.name || '?').charAt(0).toUpperCase()}
                                    </span>
                                    {c.name}
                                    {c.grade ? ` · G${c.grade}` : ''}
                                </button>
                            ))}
                        </div>

                        {child ? (
                            <div className="space-y-6">
                                <div className="grid sm:grid-cols-3 gap-3">
                                    <KpiCard label="XP" value={Number(child.xp || 0).toLocaleString()} icon="bolt" tone="primary" />
                                    <KpiCard label="Courses" value={child.enrollments?.length || 0} icon="school" tone="secondary" />
                                    <KpiCard label="Band" value={child.grade_band_label || '—'} icon="child_care" tone="tertiary" />
                                </div>

                                <div className="grid lg:grid-cols-2 gap-4">
                                    <DashboardSection title="Learning progress" icon="trending_up">
                                        {(child.enrollments || []).length === 0 ? (
                                            <EmptyState icon="school" title="No active enrollments" description="Enrolled courses will show progress here." />
                                        ) : (
                                            <div className="space-y-3">
                                                {child.enrollments.map((e) => (
                                                    <div key={e.id} className="p-3 rounded-xl bg-surface-container-low">
                                                        <div className="flex justify-between gap-2 text-sm font-bold">
                                                            <span className="truncate">{e.course_title}</span>
                                                            <span className="text-primary shrink-0">{Math.round(e.progress || 0)}%</span>
                                                        </div>
                                                        <div className="mt-2 h-1.5 rounded-full bg-surface-container-high overflow-hidden">
                                                            <div className="h-full bg-primary" style={{ width: `${Math.min(100, e.progress || 0)}%` }} />
                                                        </div>
                                                        <StatusBadge tone="neutral" className="mt-2">
                                                            {e.status}
                                                        </StatusBadge>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>

                                    <DashboardSection title="Upcoming classes" icon="event">
                                        {(child.upcoming || []).length === 0 ? (
                                            <EmptyState icon="event_busy" title="No upcoming sessions" description="Scheduled classes will appear here." />
                                        ) : (
                                            <div className="space-y-2">
                                                {child.upcoming.map((u) => (
                                                    <div key={u.id} className="p-3 rounded-xl bg-surface-container-low text-sm flex items-start gap-3">
                                                        <span className="grid h-9 w-9 place-items-center rounded-xl bg-primary-fixed text-primary shrink-0">
                                                            <Icon name="videocam" className="text-lg" />
                                                        </span>
                                                        <div className="min-w-0">
                                                            <p className="font-bold truncate">{u.title}</p>
                                                            <p className="text-on-surface-variant text-xs mt-1">Live Class</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>
                                </div>

                                <div className="grid lg:grid-cols-2 gap-4">
                                    <DashboardSection title="Post-class updates" icon="rate_review" description={`Shared by Younimatiq for ${child.name}`}>
                                        {(child.classUpdates || []).length === 0 ? (
                                            <EmptyState icon="forum" title="No updates yet" description="After a class, admin can send a progress note that will appear here." />
                                        ) : (
                                            <div className="space-y-3">
                                                {child.classUpdates.map((u) => (
                                                    <div key={u.id} className="p-4 rounded-xl bg-surface-container-low space-y-2">
                                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                                            <p className="text-sm font-bold">{u.tutor_name}</p>
                                                            <p className="text-xs text-on-surface-variant">{u.shared_at_label}</p>
                                                        </div>
                                                        {u.parent_message ? (
                                                            <p className="text-sm text-on-surface whitespace-pre-wrap">{u.parent_message}</p>
                                                        ) : null}
                                                        {(u.highlights || []).map((h, i) => (
                                                            <p key={i} className="text-xs text-on-surface-variant">
                                                                <span className="font-bold text-on-surface">{h.label}:</span> {h.value}
                                                            </p>
                                                        ))}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>

                                    <DashboardSection title="Post-class activity" icon="assignment">
                                        {(child.postClassActivities || []).length === 0 ? (
                                            <EmptyState icon="assignment_late" title="No activities yet" description="Homework and practice tasks assigned after class will show here." />
                                        ) : (
                                            <div className="space-y-2">
                                                {child.postClassActivities.map((a) => (
                                                    <div key={a.id} className="p-3 rounded-xl bg-surface-container-low text-sm">
                                                        <p className="font-bold">{a.title}</p>
                                                        {a.description ? <p className="text-on-surface-variant text-xs mt-1 line-clamp-2">{a.description}</p> : null}
                                                        {a.due_at_label ? <p className="text-xs text-primary font-bold mt-2">Due {a.due_at_label}</p> : null}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>
                                </div>

                                <DashboardSection title="Feedback & reviews" icon="forum" description="Two different things — both available for your family">
                                    <div className="grid sm:grid-cols-2 gap-3">
                                        <Link href={`/parent/feedback?child=${child.id}`} className="p-4 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-colors">
                                            <p className="font-bold flex items-center gap-2"><Icon name="forum" className="text-primary" /> Class Feedback</p>
                                            <p className="text-xs text-on-surface-variant mt-1">How a live class went for {child.name}</p>
                                        </Link>
                                        <Link href="/parent/reviews" className="p-4 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-colors">
                                            <p className="font-bold flex items-center gap-2"><Icon name="star" className="text-tertiary" /> Course Reviews</p>
                                            <p className="text-xs text-on-surface-variant mt-1">Star rating + comment on a full course</p>
                                        </Link>
                                    </div>
                                </DashboardSection>

                                <DashboardSection title="AI & privacy" icon="privacy_tip" description={`Class summary emails for ${child.name}`}>
                                    <p className="text-sm text-on-surface-variant mb-3">
                                        Allow tutors to email AI-assisted class summaries. You can turn this off anytime.
                                    </p>
                                    <form method="POST" action={`/parent/children/${child.id}/ai-consent`} className="flex flex-wrap items-center gap-3">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="ai_assist_allowed" value={child.ai_assist_allowed ? '0' : '1'} />
                                        <StatusBadge tone={child.ai_assist_allowed ? 'success' : 'warning'}>
                                            {child.ai_assist_allowed ? 'AI summaries on' : 'AI summaries off'}
                                        </StatusBadge>
                                        <button type="submit" className="px-4 py-2 rounded-xl border border-outline-variant text-sm font-bold hover:bg-surface-container-low">
                                            {child.ai_assist_allowed ? 'Turn off' : 'Turn on'}
                                        </button>
                                    </form>
                                </DashboardSection>

                                <div className="grid md:grid-cols-2 gap-4">
                                    <DashboardSection title="Payments" icon="payments">
                                        {(child.payments || []).filter((p) => Number(p.amount) > 0).length === 0 ? (
                                            <p className="text-sm text-on-surface-variant">
                                                {(child.enrollments || []).length > 0
                                                    ? 'Enrolled in classes — no paid charges on file.'
                                                    : 'No completed payments.'}
                                            </p>
                                        ) : (
                                            <div className="space-y-2">
                                                {child.payments.filter((p) => Number(p.amount) > 0).map((p) => (
                                                    <div key={p.id} className="flex justify-between gap-2 text-sm p-3 rounded-xl bg-surface-container-low">
                                                        <span className="truncate">{p.course_title || 'Payment'}</span>
                                                        <span className="font-bold shrink-0">
                                                            {p.currency} {Number(p.amount).toFixed(2)}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>
                                    <DashboardSection title="Approved portfolio" icon="folder_special">
                                        {(child.portfolios || []).length === 0 ? (
                                            <p className="text-sm text-on-surface-variant">No shared projects yet.</p>
                                        ) : (
                                            <div className="space-y-2">
                                                {child.portfolios.map((p) => (
                                                    <div key={p.id} className="p-3 rounded-xl bg-surface-container-low text-sm">
                                                        <p className="font-bold">{p.project_name}</p>
                                                        {p.project_url ? (
                                                            <a href={p.project_url} target="_blank" rel="noreferrer" className="text-primary font-bold text-xs hover:underline">
                                                                Open project
                                                            </a>
                                                        ) : null}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </DashboardSection>
                                </div>
                            </div>
                        ) : null}
                    </>
                )}

                <QuickLinks
                    items={[
                        { href: '/parent/feedback', icon: 'forum', label: 'Class feedback' },
                        { href: '/parent/reviews', icon: 'star', label: 'Course reviews' },
                        { href: '/parent/referrals', icon: 'share', label: 'Family referrals & sibling discounts' },
                        { href: '/contact', icon: 'support_agent', label: 'Contact support' },
                    ]}
                />
            </div>
        </DashboardLayout>
    );
}
