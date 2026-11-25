import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ trainings = [] }) {
    const { flash } = usePage().props;

    const destroy = (id) => {
        if (!confirm('Are you sure you want to delete this training data?')) return;
        router.delete(`/admin/chatbot/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Chatbot Training Data">
            <Head title="Chatbot Training Data" />
            <div className="staff-page">
                <div className="flex flex-col md:flex-row justify-between items-center gap-4 mb-10">
                    <div>
                        <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Chatbot Training Data</h1>
                        <p className="font-body-lg text-body-lg text-on-surface-variant">Train your automated assistant with Q&A pairs.</p>
                    </div>
                    <Link href="/admin/chatbot/create" className="btn-primary btn-sm flex items-center gap-2">
                        <Icon name="add" /> Add New QA Pair
                    </Link>
                </div>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>
                )}

                <PortalCard className="p-5 md:p-8">
                    <div className="overflow-x-auto">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b-2 border-outline-variant">
                                    <th className="font-bold text-on-surface">Question / Keywords</th>
                                    <th className="font-bold text-on-surface">Answer Snippet</th>
                                    <th className="font-bold text-on-surface text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trainings.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="py-12 text-center text-on-surface-variant">
                                            <Icon name="smart_toy" className="text-4xl mb-2 block mx-auto" />
                                            <p>Your chatbot has no training data yet.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    trainings.map((training) => (
                                        <tr key={training.id} className="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                            <td>
                                                <div className="font-bold text-on-surface mb-1">{training.question}</div>
                                                {training.tags ? (
                                                    <div className="flex flex-wrap gap-2">
                                                        {training.tags.split(',').map((tag, i) => (
                                                            <span key={i} className="text-xs bg-primary-container text-on-primary-container px-2 py-0.5 rounded-md">
                                                                {tag.trim()}
                                                            </span>
                                                        ))}
                                                    </div>
                                                ) : null}
                                            </td>
                                            <td className="text-on-surface-variant">{training.answer_snippet}</td>
                                            <td className="text-right">
                                                <Link href={`/admin/chatbot/${training.id}/edit`} className="p-2 text-primary hover:bg-primary-container rounded-lg transition-colors inline-block">
                                                    <Icon name="edit" />
                                                </Link>
                                                <button type="button" onClick={() => destroy(training.id)} className="p-2 text-error hover:bg-error-container rounded-lg transition-colors">
                                                    <Icon name="delete" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
