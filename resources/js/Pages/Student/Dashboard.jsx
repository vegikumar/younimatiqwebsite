import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';
import EmptyState from '../../Components/Portal/EmptyState';
import DashboardSection from '../../Components/Portal/DashboardSection';

function TrialBanner({ trialEndsAt, trialExpiredUrl }) {
    const [countdown, setCountdown] = useState('--d --h --m --s');

    useEffect(() => {
        if (!trialEndsAt) return undefined;
        const endsAt = new Date(trialEndsAt).getTime();
        const tick = () => {
            const diff = endsAt - Date.now();
            if (diff <= 0) {
                setCountdown('Expired');
                if (trialExpiredUrl) window.location.href = trialExpiredUrl;
                return;
            }
            const s = Math.floor(diff / 1000);
            const d = Math.floor(s / 86400);
            const h = Math.floor((s % 86400) / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            setCountdown(`${d}d ${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(sec).padStart(2, '0')}s`);
        };
        tick();
        const id = window.setInterval(tick, 1000);
        return () => window.clearInterval(id);
    }, [trialEndsAt, trialExpiredUrl]);

    if (!trialEndsAt) return null;

    const endsLabel = new Date(trialEndsAt).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

    return (
        <div className="unslop-trial">
            <div>
                <p className="learn-kicker">Trial dashboard access</p>
                <h2>Free dashboard closes on {endsLabel}</h2>
                <p>Enroll in a course anytime to keep full access.</p>
            </div>
            <div className="unslop-trial__clock">
                <Icon name="timer" className="text-2xl text-[var(--u-muted)]" />
                <div>
                    <span>Time remaining</span>
                    <strong>{countdown}</strong>
                </div>
            </div>
        </div>
    );
}

const BAND_COPY = {
    early: { welcome: 'Play and learn today', sprint: 'Your adventure progress' },
    middle: { welcome: 'Ready for your next challenge', sprint: 'Weekly learning sprint' },
    junior: { welcome: 'Keep building your skills', sprint: 'Skill path progress' },
    senior: { welcome: 'Advance your portfolio and pathway', sprint: 'Mastery and career-ready progress' },
};

export default function Dashboard({
    stats = {},
    upcomingSessions = [],
    demoSessions = [],
    enrollments = [],
    assignedGames = [],
    leaderboard = [],
    pendingAssignments = 0,
    trialEndsAt = null,
    hasPurchasedCourse = true,
    trialExpiredUrl = '/student/trial-expired',
    rewards = [],
    badges = [],
    rewardStats = {},
    gradeBand = 'middle',
    gradeBandLabel = 'Grades 4–6',
    grade = null,
    streak = {},
    milestones = [],
}) {
    const { auth } = usePage().props;
    const userName = auth?.user?.name ?? 'Explorer';
    const showTrial = trialEndsAt && !hasPurchasedCourse;
    const avgProgress = Math.round(stats.avg_progress ?? 0);
    const band = BAND_COPY[gradeBand] || BAND_COPY.middle;
    const showAdvanced = gradeBand === 'junior' || gradeBand === 'senior';
    const showLeaderboard = gradeBand !== 'early';
    const sessions = [...upcomingSessions, ...demoSessions];

    return (
        <DashboardLayout title="Student Dashboard">
            <Head title="Student Dashboard" />
            <div className="learn-home unslop-learn">
                {showTrial ? <TrialBanner trialEndsAt={trialEndsAt} trialExpiredUrl={trialExpiredUrl} /> : null}

                <header className="learn-home-bar">
                    <div className="learn-home-copy">
                        <p className="learn-kicker">
                            {gradeBandLabel}
                            {grade ? ` · Grade ${grade}` : ''}
                            {' · Level '}
                            {stats.level ?? 1}
                        </p>
                        <h1>{userName}</h1>
                        <p className="learn-sub">{band.welcome}</p>
                    </div>
                    <div className="student-ring" style={{ '--progress': `${avgProgress}%` }} role="img" aria-label={`${avgProgress} percent course progress`}>
                        <strong>{avgProgress}%</strong>
                    </div>
                    <dl className="learn-metrics">
                        <div>
                            <dt>{gradeBand === 'early' ? 'Courses' : 'Enrolled'}</dt>
                            <dd>{stats.enrolled ?? 0}</dd>
                        </div>
                        <div>
                            <dt>Streak</dt>
                            <dd>{streak.current ?? 0}d</dd>
                        </div>
                        <div>
                            <dt>XP</dt>
                            <dd>{Number(stats.xp ?? 0).toLocaleString()}</dd>
                        </div>
                        <div>
                            <dt>Done</dt>
                            <dd>{stats.completed ?? 0}</dd>
                        </div>
                    </dl>
                </header>

                <div className="learn-split">
                    <section className="learn-panel">
                        <div className="learn-panel-head">
                            <h2>Upcoming sessions</h2>
                            <Link href="/student/bookings">Schedule</Link>
                        </div>
                        {sessions.length === 0 ? (
                            <p className="learn-empty">No upcoming sessions. Bookings and trial classes will show up here.</p>
                        ) : (
                            <ul className="learn-session-list">
                                {sessions.map((session) => (
                                    <li key={`${session.type}-${session.id}`}>
                                        <div>
                                            <strong>{session.title}</strong>
                                            <span>
                                                {session.scheduled_at_label || 'Live Class'}
                                                {session.timezone_label ? ` · ${session.timezone_label}` : ''}
                                                {session.tutor_name ? ` · ${String(session.tutor_name).split(' ')[0]}` : ''}
                                            </span>
                                        </div>
                                        {session.join_url || session.zoom_join_url ? (
                                            <a href={session.join_url || session.zoom_join_url} target="_blank" rel="noreferrer">
                                                Join class
                                            </a>
                                        ) : (
                                            <em>Link soon</em>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <aside className="learn-rail">
                        <section className="learn-panel">
                            <div className="learn-panel-head">
                                <h2>{band.sprint}</h2>
                                <span className="unslop-mono">{avgProgress}%</span>
                            </div>
                            <div className="learn-bar" style={{ '--progress': `${avgProgress}%` }} />
                            <p className="learn-sub">
                                {avgProgress}% across {stats.enrolled ?? 0} courses
                            </p>
                            {pendingAssignments > 0 ? (
                                <Link href="/student/assignments" className="learn-inline">
                                    <Icon name="assignment" />
                                    {pendingAssignments} pending post-class activities
                                </Link>
                            ) : null}
                            {assignedGames.length > 0 ? (
                                <Link href="/student/activities" className="learn-inline">
                                    <Icon name="sports_esports" />
                                    {assignedGames.length} assigned game{assignedGames.length === 1 ? '' : 's'}
                                </Link>
                            ) : null}
                        </section>
                        <section className="learn-panel learn-xp">
                            <p className="learn-kicker">Leaderboard</p>
                            <strong>{Number(stats.xp ?? 0).toLocaleString()} XP</strong>
                            <div>
                                <span>Rank #{stats.rank ?? '—'}</span>
                                <span className="unslop-chip is-ok">{stats.tier || 'Explorer'}</span>
                            </div>
                            <p className="learn-sub">
                                Best {streak.longest ?? 0}d · Week {streak.week_count ?? 0}/{streak.week_goal ?? 3}
                            </p>
                        </section>
                    </aside>
                </div>

                {streak.week_complete || milestones.length > 0 ? (
                    <div className="unslop-note flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        {streak.week_complete ? (
                            <p>Weekly challenge complete — {streak.week_count} activities this week</p>
                        ) : (
                            <p>
                                Weekly challenge: {streak.week_count ?? 0}/{streak.week_goal ?? 3} learning activities
                            </p>
                        )}
                        {milestones[0] ? <span className="unslop-chip is-ok">Latest: {milestones[0].title}</span> : null}
                    </div>
                ) : null}

                <DashboardSection title="Assigned games" icon="sports_esports" actionHref="/student/activities" actionLabel="All activities">
                    {assignedGames.length === 0 ? (
                        <p className="text-sm text-[var(--u-muted)]">No games assigned yet. Your teacher can assign one from the admin panel.</p>
                    ) : (
                        <ul className="space-y-2">
                            {assignedGames.slice(0, 6).map((game) => (
                                <li key={game.key}>
                                    <Link
                                        href={game.url}
                                        className="block p-3 rounded-xl border border-[var(--u-hairline)] bg-[var(--u-bg)] hover:bg-[var(--u-raised)] transition-colors"
                                    >
                                        <div className="flex justify-between gap-2 text-sm font-medium">
                                            <span className="truncate">{game.title}</span>
                                            <span className="text-[var(--u-accent)] shrink-0 font-bold">Play</span>
                                        </div>
                                        {game.summary ? (
                                            <p className="text-xs text-[var(--u-muted)] mt-1 line-clamp-2">{game.summary}</p>
                                        ) : null}
                                        {game.xp_max ? (
                                            <p className="text-[11px] font-bold mt-1.5 text-[var(--u-accent)]">
                                                {game.xp_awarded
                                                    ? `${game.xp_points} XP added`
                                                    : `Earn up to ${game.xp_max} XP`}
                                            </p>
                                        ) : null}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </DashboardSection>

                <div className="student-home-grid student-home-grid-3">
                    <DashboardSection title="Course sprint" icon="menu_book" actionHref="/student/courses">
                        {enrollments.length === 0 ? (
                            <EmptyState icon="school" title="No enrollments yet" description="Browse the catalog to start learning." />
                        ) : (
                            <ul className="space-y-2">
                                {enrollments.slice(0, 6).map((e) => (
                                    <li key={e.id}>
                                        <Link
                                            href={`/student/courses/${e.course_id}/player`}
                                            className="block p-3 rounded-xl border border-[var(--u-hairline)] bg-[var(--u-bg)] hover:bg-[var(--u-raised)] transition-colors"
                                        >
                                            <div className="flex justify-between gap-2 text-sm font-medium">
                                                <span className="truncate">{e.course_title}</span>
                                                <span className="unslop-mono shrink-0">{Math.round(e.progress || 0)}%</span>
                                            </div>
                                            <div className="mt-2 h-1.5 rounded-full bg-[var(--u-raised)] overflow-hidden">
                                                <div className="h-full bg-[var(--u-accent)]" style={{ width: `${Math.min(100, e.progress || 0)}%` }} />
                                            </div>
                                        </Link>
                                        {showAdvanced ? (
                                            <Link
                                                href={`/student/courses/${e.course_id}/skill-tree`}
                                                className="inline-block mt-1 text-xs font-medium text-[var(--u-accent)] hover:underline px-1"
                                            >
                                                Skill tree
                                            </Link>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </DashboardSection>

                    <DashboardSection title="Recent rewards" icon="emoji_events" actionHref="/student/rewards" actionLabel="Gallery">
                        {rewards.length === 0 ? (
                            <p className="text-sm text-[var(--u-muted)]">Rewards from class will appear here.</p>
                        ) : (
                            <ul className="space-y-2">
                                {rewards.slice(0, 5).map((r) => (
                                    <li key={r.id} className="unslop-row text-sm">
                                        <span className="truncate">{r.compliment || r.badge?.title || 'XP reward'}</span>
                                        <span className="unslop-mono shrink-0">+{r.xp_amount}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {badges.length > 0 ? (
                            <div className="unslop-chips mt-4">
                                {badges.slice(0, 6).map((b) => (
                                    <span key={b.id} className="unslop-chip">
                                        {b.title}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                    </DashboardSection>

                    {showLeaderboard ? (
                        <DashboardSection title="Leaderboard" description="First name + initial only" icon="leaderboard">
                            {leaderboard.length === 0 ? (
                                <p className="text-sm text-[var(--u-muted)]">No leaderboard data yet.</p>
                            ) : (
                                <ul className="space-y-2">
                                    {leaderboard.map((row) => (
                                        <li key={row.rank} className={`unslop-row text-sm ${row.is_current ? 'ring-1 ring-[var(--u-accent)]' : ''}`}>
                                            <span>
                                                #{row.rank} {row.name}
                                            </span>
                                            <span className="unslop-mono">{row.xp} XP</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </DashboardSection>
                    ) : (
                        <DashboardSection title="Keep going" icon="celebration">
                            <p className="text-sm text-[var(--u-muted)] mb-3">Finish a lesson or activity to earn more stars.</p>
                            <Link href="/student/activities" className="unslop-btn unslop-btn--primary inline-flex">
                                Open activities
                            </Link>
                        </DashboardSection>
                    )}
                </div>

                <section className="unslop-panel unslop-quick p-5">
                    <div className="unslop-card-head">
                        <div>
                            <h2>Quick actions</h2>
                        </div>
                    </div>
                    <div className="unslop-shortcuts unslop-shortcuts--6">
                        {[
                            { href: '/student/activities', icon: 'sports_esports', label: 'Assigned games' },
                            { href: '/student/bookings', icon: 'calendar_month', label: 'Tutor bookings' },
                            { href: '/student/assignments', icon: 'assignment', label: 'Post-class activities' },
                            { href: '/student/quizzes', icon: 'quiz', label: 'Quizzes' },
                            { href: '/student/python', icon: 'code', label: 'Python Lab' },
                            { href: '/courses', icon: 'explore', label: 'Browse catalog' },
                        ].map((item) => (
                            <Link key={item.href} href={item.href}>
                                <Icon name={item.icon} className="icon" />
                                <span className="truncate">{item.label}</span>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </DashboardLayout>
    );
}
