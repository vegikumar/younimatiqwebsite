import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import EmptyState from '../../../Components/Portal/EmptyState';

export default function QuizzesIndex({ quizzes = [] }) {
    const { flash } = usePage().props;

    return (
        <DashboardLayout title="Quizzes">
            <Head title="Quizzes" />
            <div className="staff-page max-w-5xl">
                <PageHeader
                    variant="staff"
                    title="Quizzes"
                    description="View quizzes linked to your account. Only admins create and assign quizzes to students."
                />
                {flash?.success ? <div className="bg-success-container p-4 rounded-xl font-bold">{flash.success}</div> : null}
                <div className="grid gap-4">
                    {quizzes.length ? (
                        quizzes.map((q) => (
                            <PortalCard key={q.id}>
                                <div className="flex flex-col md:flex-row justify-between gap-4 min-w-0">
                                    <div className="min-w-0">
                                        <h3 className="text-lg font-extrabold text-primary break-words">{q.title}</h3>
                                        <p className="text-sm text-on-surface-variant">
                                            {q.questionCount} questions · {q.xpReward} XP Reward
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {q.assignments?.length ? (
                                                q.assignments.map((a, i) => (
                                                    <span key={i} className="px-2 py-1 bg-surface-container-high rounded-lg text-xs font-bold break-words">
                                                        {a.label}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="px-2 py-1 bg-surface-container-high rounded-lg text-xs font-bold">Not assigned by admin yet</span>
                                            )}
                                        </div>
                                    </div>
                                    <Link href={q.editUrl} className="px-4 py-2 bg-surface-container-low rounded-xl font-bold text-sm self-start shrink-0">
                                        View / Edit content
                                    </Link>
                                </div>
                            </PortalCard>
                        ))
                    ) : (
                        <PortalCard>
                            <EmptyState
                                icon="quiz"
                                title="No quizzes here"
                                description="Admins create and assign quizzes to students from the admin portal."
                            />
                        </PortalCard>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
