import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import EmptyState from '../../Components/Portal/EmptyState';
import Icon from '../../Components/Icon';

export default function Activities({ enrollments = [], assignments = [], quizzes = [], games = [] }) {
    const { flash } = usePage().props;
    return (
        <DashboardLayout title="Activities">
            <Head title="Activities" />
            <div className="p-5 md:p-8 max-w-5xl mx-auto space-y-6">
                <PageHeader
                    eyebrow="Learning hub"
                    title="Your activities"
                    description="Play assigned classroom games, then jump into lessons, quizzes, and post-class work."
                />

                {flash?.error ? <p className="clay-card p-4 text-sm font-bold text-error">{flash.error}</p> : null}

                <section className="space-y-3">
                    <h2 className="font-bold text-lg flex items-center gap-2">
                        <Icon name="sports_esports" /> Classroom games
                    </h2>
                    {games.length === 0 ? (
                        <p className="text-sm text-on-surface-variant clay-card p-4">No games assigned yet. Your teacher can assign one from the admin panel.</p>
                    ) : (
                        <div className="grid sm:grid-cols-2 gap-4">
                            {games.map((game) => (
                                <Link key={game.key} href={game.url} className="block clay-card p-5 hover:border-primary transition-colors">
                                    <h3 className="font-bold">{game.title}</h3>
                                    <p className="text-sm text-on-surface-variant mt-1">{game.summary}</p>
                                    {game.xp_max ? (
                                        <p className="text-xs font-bold mt-2 text-primary">
                                            {game.xp_awarded
                                                ? `${game.xp_points} XP added`
                                                : `Play, learn, and earn up to ${game.xp_max} XP once`}
                                        </p>
                                    ) : null}
                                    <span className="inline-block mt-3 text-sm font-bold text-primary">Play game</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-bold text-lg">Courses & skill trees</h2>
                    {enrollments.length === 0 ? (
                        <EmptyState
                            icon="school"
                            title="No enrollments yet"
                            description="Browse the catalog to start a course."
                            action={
                                <a href="/courses" className="btn-primary">
                                    Browse courses
                                </a>
                            }
                        />
                    ) : (
                        <div className="grid sm:grid-cols-2 gap-4">
                            {enrollments.map((e) => (
                                <div key={e.id} className="clay-card p-5">
                                    <h3 className="font-bold">{e.course_title}</h3>
                                    <div className="mt-3 h-2 rounded-full bg-surface-container-high overflow-hidden">
                                        <div className="h-full bg-primary" style={{ width: `${Math.min(100, e.progress || 0)}%` }} />
                                    </div>
                                    <p className="text-xs text-on-surface-variant mt-1">{Math.round(e.progress || 0)}% progress</p>
                                    <div className="flex flex-wrap gap-2 mt-4">
                                        <Link href={e.player_url} className="btn-primary btn-sm">
                                            Open player
                                        </Link>
                                        <Link href={e.skill_tree_url} className="px-3 py-1.5 rounded-xl bg-surface-container-high text-sm font-bold">
                                            Skill tree
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="grid md:grid-cols-2 gap-6">
                    <div className="space-y-3">
                        <h2 className="font-bold text-lg flex items-center gap-2">
                            <Icon name="assignment" /> Assignments
                        </h2>
                        {assignments.length === 0 ? (
                            <p className="text-sm text-on-surface-variant clay-card p-4">No assignments yet.</p>
                        ) : (
                            assignments.slice(0, 12).map((a) => (
                                <Link key={a.id} href={a.url} className="block clay-card p-4 hover:border-primary transition-colors">
                                    <p className="font-bold text-sm">{a.title}</p>
                                    <p className="text-xs text-on-surface-variant mt-1">
                                        {a.course_title}
                                        {a.due_at_label ? ` · Due ${a.due_at_label}` : ''}
                                    </p>
                                </Link>
                            ))
                        )}
                    </div>
                    <div className="space-y-3">
                        <h2 className="font-bold text-lg flex items-center gap-2">
                            <Icon name="quiz" /> Quizzes
                        </h2>
                        {quizzes.length === 0 ? (
                            <p className="text-sm text-on-surface-variant clay-card p-4">No quizzes yet.</p>
                        ) : (
                            quizzes.slice(0, 12).map((q) => (
                                <Link key={q.id} href={q.url} className="block clay-card p-4 hover:border-primary transition-colors">
                                    <p className="font-bold text-sm">{q.title}</p>
                                    <p className="text-xs text-on-surface-variant mt-1">{q.course_title}</p>
                                </Link>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </DashboardLayout>
    );
}
