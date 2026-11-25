import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import RewardModal, { openRewardModal } from '../../Components/Tutor/RewardModal';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Rewards({ stats = {}, rewards = {}, students = [], rewardPresets = [], badgeCatalog = [] }) {
    const { flash } = usePage().props;
    const rows = rewards.data ?? [];

    return (
        <DashboardLayout title="Class Rewards">
            <Head title="Class Rewards" />
            <RewardModal presets={rewardPresets} badges={badgeCatalog} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Celebrate Your Learners"
                    description="Award XP compliments, level-up tips and badges during class."
                    actions={
                        students[0] ? (
                            <button type="button" className="btn-enroll py-3 px-5 text-sm font-bold shrink-0" onClick={() => openRewardModal({ id: students[0].id, name: students[0].name })}>
                                Celebrate a Student
                            </button>
                        ) : null
                    }
                />
                {flash?.success ? <div className="p-3 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-2">
                    <Stat label="XP Awarded" value={stats.xpAwarded} />
                    <Stat label="Badges Awarded" value={stats.badgesAwarded} />
                    <Stat label="Students Celebrated" value={stats.studentsCelebrated} />
                </div>
                <PortalCard title="Recent Rewards">
                    <div className="space-y-3">
                        {rows.length ? (
                            rows.map((r) => (
                                <div key={r.id} className="p-4 border border-outline-variant/30 rounded-2xl flex justify-between gap-4 min-w-0">
                                    <div className="text-sm min-w-0">
                                        <p className="font-bold break-words">
                                            {r.studentName}
                                            {r.isTrial ? <span className="ml-1 text-[10px] uppercase bg-secondary/20 px-2 py-0.5 rounded-full">Trial</span> : null}
                                        </p>
                                        <p className="text-xs text-on-surface-variant break-words">
                                            {r.courseTitle ?? 'General'} · {formatLocalTime(r.awardedAt, 'datetime-short')}
                                        </p>
                                        {r.badge ? (
                                            <p className="text-xs font-bold text-primary mt-1 break-words">{r.badge.title}</p>
                                        ) : null}
                                        {r.levelUpTip ? <p className="text-xs mt-1 break-words">{r.levelUpTip}</p> : null}
                                    </div>
                                    <span className="font-bold text-tertiary shrink-0">+{r.xpAmount} XP</span>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-on-surface-variant">No rewards awarded yet.</p>
                        )}
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}

function Stat({ label, value }) {
    return (
        <PortalCard>
            <p className="text-xs font-bold uppercase text-on-surface-variant">{label}</p>
            <p className="text-3xl font-bold text-primary mt-1">{Number(value || 0).toLocaleString()}</p>
        </PortalCard>
    );
}
