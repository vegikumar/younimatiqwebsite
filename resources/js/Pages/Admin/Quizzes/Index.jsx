import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Index({ quizzes = [] }) {
    const { flash } = usePage().props;

    return (
        <DashboardLayout title="Quizzes">
            <Head title="Manage Quizzes" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Learning"
                    title="Manage quizzes"
                    description="Create and assign quizzes to your students."
                    actions={
                        <Link href="/admin/quizzes/create" className="btn-primary flex items-center gap-2 shrink-0">
                            <Icon name="add" /> Create Quiz
                        </Link>
                    }
                />
                {flash?.success ? <div className="bg-success-container text-on-success-container p-4 rounded-xl font-bold">{flash.success}</div> : null}
                <div className="grid gap-4">
                    {quizzes.length ? (
                        quizzes.map((q) => (
                            <PortalCard key={q.id} className="flex flex-col md:flex-row justify-between md:items-center gap-4 min-w-0">
                                <div>
                                    <h3 className="text-lg font-bold text-primary">{q.title}</h3>
                                    <p className="text-sm text-on-surface-variant">
                                        {q.questionCount} questions &bull; {q.xpReward} XP Reward
                                    </p>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {q.assignments?.length ? (
                                            q.assignments.map((a, i) => (
                                                <span key={i} className="px-2 py-1 bg-surface-container-high text-on-surface-variant rounded-lg text-xs font-bold">
                                                    {a.label}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="px-2 py-1 bg-surface-container-high text-on-surface-variant rounded-lg text-xs font-bold">Unassigned</span>
                                        )}
                                    </div>
                                </div>
                                <Link href={q.editUrl} className="px-4 py-2 bg-surface-container-low text-on-surface hover:bg-surface-container rounded-xl font-bold text-sm shrink-0">
                                    Edit
                                </Link>
                            </PortalCard>
                        ))
                    ) : (
                        <div className="text-center p-6 md:p-12 bg-surface-container-low rounded-3xl border border-dashed border-outline-variant">
                            <Icon name="quiz" className="text-4xl text-on-surface-variant mb-2" />
                            <h3 className="text-lg font-bold">No quizzes yet</h3>
                            <p className="text-on-surface-variant mb-4">You haven&apos;t created any quizzes.</p>
                            <Link href="/admin/quizzes/create" className="btn-primary inline-flex">
                                Create First Quiz
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
