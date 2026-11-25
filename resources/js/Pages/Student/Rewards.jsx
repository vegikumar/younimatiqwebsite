import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import KpiCard from '../../Components/Portal/KpiCard';
import EmptyState from '../../Components/Portal/EmptyState';
import Icon from '../../Components/Icon';

export default function Rewards({ rewards = [], badges = [], stats = {} }) {
    return (
        <DashboardLayout title="Rewards">
            <Head title="Rewards & Badges" />
            <div className="p-5 md:p-8 max-w-5xl mx-auto space-y-6">
                <PageHeader eyebrow="Gamification" title="Rewards gallery" description="XP, compliments, and badges earned in class." />

                <div className="grid sm:grid-cols-3 gap-4">
                    <KpiCard label="Total XP" value={Number(stats.total_xp || 0).toLocaleString()} icon="bolt" tone="primary" />
                    <KpiCard label="Rewards" value={stats.count || 0} icon="celebration" tone="secondary" />
                    <KpiCard label="Badges" value={stats.badges || 0} icon="military_tech" tone="tertiary" />
                </div>

                <section>
                    <h2 className="font-bold mb-3">Badge collection</h2>
                    {badges.length === 0 ? (
                        <EmptyState icon="military_tech" title="No badges yet" description="Complete milestones and earn class badges to fill this wall." />
                    ) : (
                        <div className="flex flex-wrap gap-3">
                            {badges.map((b) => (
                                <span key={b.id} className="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-primary/10 text-primary font-bold text-sm">
                                    <Icon name={b.icon || 'military_tech'} />
                                    {b.title}
                                </span>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-bold">Recent rewards</h2>
                    {rewards.length === 0 ? (
                        <EmptyState icon="emoji_events" title="No rewards yet" description="Your tutor can award XP and compliments during class." />
                    ) : (
                        rewards.map((r) => (
                            <div key={r.id} className="clay-card p-4 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="font-bold">{r.compliment || r.badge?.title || 'XP reward'}</p>
                                    <p className="text-xs text-on-surface-variant mt-1">
                                        {[r.course_title, r.tutor_name, r.awarded_at_label].filter(Boolean).join(' · ')}
                                    </p>
                                </div>
                                <span className="font-extrabold text-primary">+{r.xp_amount} XP</span>
                            </div>
                        ))
                    )}
                </section>
            </div>
        </DashboardLayout>
    );
}
