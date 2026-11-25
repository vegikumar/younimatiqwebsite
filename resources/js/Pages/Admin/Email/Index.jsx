import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ templates = [], recentLogs = [] }) {
    return (
        <DashboardLayout title="Email Templates">
            <Head title="Email Templates" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Email Templates & Logs</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Manage transactional email templates and view delivery logs.</p>
                </PortalCard>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5 md:p-8 mb-10">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2"><Icon name="mail" className="text-primary" /> Templates</h2>
                        <div className="space-y-3">
                            {templates.length === 0 ? <p className="text-on-surface-variant text-center py-6">No email templates configured.</p> : templates.map((t) => (
                                <div key={t.id} className="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant hover:border-primary/30">
                                    <div><h3 className="font-bold">{t.name}</h3><p className="text-sm text-on-surface-variant">{t.subject}</p><span className="text-xs font-mono text-on-surface-variant">{t.slug}</span></div>
                                    <div className="flex items-center gap-2"><span className={`px-2 py-1 rounded-full text-xs font-bold ${t.is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>{t.is_active ? 'Active' : 'Off'}</span><Link href={t.editUrl} className="p-2 text-on-surface-variant hover:text-primary"><Icon name="edit" className="text-sm" /></Link></div>
                                </div>
                            ))}
                        </div>
                    </PortalCard>
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2"><Icon name="history" className="text-secondary" /> Recent Email Logs</h2>
                        <div className="overflow-x-auto max-h-[500px] overflow-y-auto">
                            <table className="staff-table w-full text-left text-sm">
                                <thead className="sticky top-0 bg-white"><tr className="border-b border-outline-variant"><th className="font-bold">To</th><th className="font-bold">Subject</th><th className="font-bold">Status</th><th className="font-bold">Date</th></tr></thead>
                                <tbody>
                                    {recentLogs.length === 0 ? <tr><td colSpan={4} className="py-6 text-center text-on-surface-variant">No emails sent yet.</td></tr> : recentLogs.map((log, i) => (
                                        <tr key={i} className="border-b border-outline-variant/50 hover:bg-surface-container-low"><td className="text-xs">{log.to_email}</td><td>{log.subjectShort}</td><td><span className={`px-2 py-0.5 rounded-full text-xs font-bold ${log.status === 'sent' ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container'}`}>{log.status}</span></td><td className="text-xs text-on-surface-variant">{log.createdAtLabel}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </PortalCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
