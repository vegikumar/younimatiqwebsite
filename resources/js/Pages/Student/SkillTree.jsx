import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';
import PageHeader from '../../Components/Portal/PageHeader';
import EmptyState from '../../Components/Portal/EmptyState';
import StatusBadge from '../../Components/Portal/StatusBadge';

const STATUS_TONE = {
    mastered: 'success',
    in_progress: 'primary',
    locked: 'neutral',
};

export default function SkillTree({ course, treeData = {}, badges = [], milestones = [] }) {
    const { csrfToken } = usePage().props;
    const [tab, setTab] = useState('tree');
    const nodes = useMemo(() => {
        if (Array.isArray(treeData)) return treeData;
        return treeData?.nodes || [];
    }, [treeData]);
    const portfolios = treeData?.portfolios || [];
    const completion = treeData?.completion_percentage ?? 0;

    const form = useForm({
        project_name: '',
        project_url: '',
        screenshot_url: '',
    });

    return (
        <DashboardLayout title="Skill Tree">
            <Head title={`Skill Tree · ${course?.title || 'Course'}`} />
            <div className="p-5 md:p-8 max-w-5xl mx-auto space-y-6">
                <PageHeader
                    eyebrow="Learning path"
                    title={treeData?.course_title || course?.title || 'Skill Tree'}
                    description={treeData?.current_level_label || 'Master skills as you complete this course.'}
                    actions={
                        <Link href={`/student/courses/${course?.id}/player`} className="btn-primary btn-sm">
                            Open course
                        </Link>
                    }
                />

                <div className="clay-card p-5">
                    <div className="flex items-center justify-between gap-3 mb-2">
                        <p className="text-sm font-bold text-on-surface">Path progress</p>
                        <span className="text-sm font-extrabold text-primary">{completion}%</span>
                    </div>
                    <div className="h-2.5 rounded-full bg-surface-container-high overflow-hidden">
                        <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${completion}%` }} />
                    </div>
                    <p className="text-xs text-on-surface-variant mt-2">
                        {treeData?.mastered_nodes ?? 0} of {treeData?.total_nodes ?? nodes.length} skills mastered
                    </p>
                </div>

                <div className="flex gap-2 border-b border-outline-variant/50 pb-2">
                    {[
                        { id: 'tree', label: 'Skill tree' },
                        { id: 'portfolio', label: 'Portfolio' },
                        { id: 'badges', label: 'Badges' },
                        { id: 'milestones', label: 'Milestones' },
                    ].map((t) => (
                        <button
                            key={t.id}
                            type="button"
                            onClick={() => setTab(t.id)}
                            className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors ${
                                tab === t.id ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface-variant hover:text-primary'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                {tab === 'tree' ? (
                    nodes.length === 0 ? (
                        <EmptyState icon="account_tree" title="No skills yet" description="Skill nodes will appear as this course is structured." />
                    ) : (
                        <div className="grid sm:grid-cols-2 gap-4">
                            {nodes.map((node) => {
                                const unlocked = node.status !== 'locked';
                                return (
                                    <div key={node.id || node.title} className={`clay-card p-5 ${unlocked ? '' : 'opacity-55'}`}>
                                        <div className="flex items-start justify-between gap-3 mb-2">
                                            <Icon name={node.icon || 'emoji_events'} className="text-primary text-3xl" />
                                            <StatusBadge tone={STATUS_TONE[node.status] || 'neutral'}>{String(node.status || 'locked').replace('_', ' ')}</StatusBadge>
                                        </div>
                                        <h2 className="font-bold text-on-surface">{node.title}</h2>
                                        {node.description ? <p className="text-sm text-on-surface-variant mt-1">{node.description}</p> : null}
                                        {node.level_number ? <p className="text-[11px] font-bold text-primary mt-3">Level {node.level_number}</p> : null}
                                    </div>
                                );
                            })}
                        </div>
                    )
                ) : null}

                {tab === 'portfolio' ? (
                    <div className="space-y-6">
                        <form
                            className="clay-card p-5 space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.post(`/student/courses/${course.id}/portfolio`, {
                                    onSuccess: () => form.reset(),
                                });
                            }}
                        >
                            <h2 className="font-bold text-lg">Add a project</h2>
                            <input
                                required
                                placeholder="Project name"
                                className="w-full rounded-xl border px-4 py-3 bg-surface-container-low"
                                value={form.data.project_name}
                                onChange={(e) => form.setData('project_name', e.target.value)}
                            />
                            <input
                                type="url"
                                placeholder="Project URL (optional)"
                                className="w-full rounded-xl border px-4 py-3 bg-surface-container-low"
                                value={form.data.project_url}
                                onChange={(e) => form.setData('project_url', e.target.value)}
                            />
                            <input
                                type="url"
                                placeholder="Screenshot URL (optional)"
                                className="w-full rounded-xl border px-4 py-3 bg-surface-container-low"
                                value={form.data.screenshot_url}
                                onChange={(e) => form.setData('screenshot_url', e.target.value)}
                            />
                            <button type="submit" className="btn-primary" disabled={form.processing}>
                                Save to portfolio
                            </button>
                        </form>

                        {portfolios.length === 0 ? (
                            <EmptyState icon="folder_open" title="No projects yet" description="Add your first project to build your learning portfolio." />
                        ) : (
                            <div className="grid sm:grid-cols-2 gap-4">
                                {portfolios.map((p) => (
                                    <div key={p.id} className="clay-card p-5 space-y-2">
                                        <div className="flex items-start justify-between gap-2">
                                            <h3 className="font-bold">{p.project_name}</h3>
                                            <StatusBadge tone={p.visibility === 'parents' || p.visibility === 'public' ? 'success' : 'neutral'}>
                                                {p.visibility || 'private'}
                                            </StatusBadge>
                                        </div>
                                        {p.project_url ? (
                                            <a href={p.project_url} target="_blank" rel="noreferrer" className="text-sm text-primary font-bold hover:underline inline-block">
                                                Open project
                                            </a>
                                        ) : null}
                                        {p.tutor_feedback ? <p className="text-xs text-on-surface-variant">Tutor: {p.tutor_feedback}</p> : null}
                                        <div className="flex flex-wrap gap-2 pt-1">
                                            {['private', 'parents'].map((vis) => (
                                                <button
                                                    key={vis}
                                                    type="button"
                                                    className="text-xs font-bold px-3 py-1.5 rounded-xl bg-surface-container-high hover:bg-primary/10"
                                                    onClick={() =>
                                                        router.post(`/student/courses/${course.id}/portfolio/${p.id}/share`, {
                                                            _token: csrfToken,
                                                            visibility: vis,
                                                        })
                                                    }
                                                >
                                                    {vis === 'parents' ? 'Share with parents' : 'Keep private'}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ) : null}

                {tab === 'milestones' ? (
                    milestones?.length ? (
                        <div className="space-y-3">
                            {milestones.map((m) => (
                                <div key={m.id} className="clay-card p-4 flex items-center gap-3">
                                    <Icon name={m.icon || 'workspace_premium'} className="text-primary text-3xl" />
                                    <div>
                                        <p className="font-bold">{m.title}</p>
                                        <p className="text-xs text-on-surface-variant">Unlocked {m.unlocked_at_label || '—'}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <EmptyState icon="workspace_premium" title="No milestones yet" description="Complete skill levels by finishing lessons, quizzes, and assignments." />
                    )
                ) : null}

                {tab === 'badges' ? (
                    badges?.length ? (
                        <div className="flex flex-wrap gap-3">
                            {badges.map((b) => (
                                <span key={b.id} className="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-primary/10 text-primary text-sm font-bold">
                                    <Icon name={b.icon || 'military_tech'} className="text-base" />
                                    {b.title}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <EmptyState icon="military_tech" title="No badges yet" description="Earn badges by completing course milestones and class rewards." />
                    )
                ) : null}
            </div>
        </DashboardLayout>
    );
}
