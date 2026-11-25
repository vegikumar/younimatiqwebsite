import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';

function formatLocalTime(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

export default function Index({ activities, filters = {} }) {
    const role = filters.role ?? null;
    const items = activities?.data ?? [];
    const links = activities?.links ?? [];

    const roleClass = (r) => {
        const active = r === null ? !role : role === r;
        return `clay-button inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold transition-colors ${
            active ? 'bg-primary text-white' : 'bg-white text-primary'
        }`;
    };

    return (
        <DashboardLayout title="Login History">
            <Head title="Login History" />
            <div className="staff-page">
                <div className="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="font-headline-xl text-headline-xl text-on-surface">Login History</h1>
                        <p className="font-body-md text-body-md text-on-surface-variant mt-2">Track user login activities, timings, and locations.</p>
                    </div>

                    <div className="flex gap-2 flex-wrap">
                        <Link href="/admin/login-history" className={roleClass(null)} preserveState>
                            All
                        </Link>
                        <Link href="/admin/login-history?role=student" className={roleClass('student')} preserveState>
                            Students
                        </Link>
                        <Link href="/admin/login-history?role=tutor" className={roleClass('tutor')} preserveState>
                            Tutors
                        </Link>
                        <Link href="/admin/login-history?role=admin" className={roleClass('admin')} preserveState>
                            Admins
                        </Link>
                    </div>
                </div>

                <PortalCard className="p-6 md:p-8">
                    <div className="overflow-x-auto">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-outline-variant/30 text-on-surface-variant">
                                    <th className="font-bold">User</th>
                                    <th className="font-bold">Role</th>
                                    <th className="font-bold">IP Address</th>
                                    <th className="font-bold">Location</th>
                                    <th className="font-bold">Login Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="py-8 text-center text-on-surface-variant">
                                            No login activities found.
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((activity) => (
                                        <tr key={activity.id} className="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                                            <td>
                                                <div className="font-bold text-on-surface">{activity.user?.name ?? 'Deleted User'}</div>
                                                <div className="text-sm text-on-surface-variant">{activity.user?.email ?? ''}</div>
                                            </td>
                                            <td>
                                                <span className="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold uppercase">
                                                    {activity.user?.role ?? 'N/A'}
                                                </span>
                                            </td>
                                            <td className="text-on-surface font-mono text-sm">{activity.ip_address ?? 'N/A'}</td>
                                            <td className="text-on-surface">{activity.location ?? 'Unknown'}</td>
                                            <td className="text-on-surface">
                                                <span className="dynamic-local-time">{formatLocalTime(activity.created_at)}</span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {links.length > 3 ? (
                        <div className="mt-6 flex flex-wrap gap-2 justify-center">
                            {links.map((link, i) =>
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`px-3 py-1 rounded-lg text-sm font-bold ${link.active ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface'}`}
                                        preserveState
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span key={i} className="px-3 py-1 text-on-surface-variant text-sm" dangerouslySetInnerHTML={{ __html: link.label }} />
                                ),
                            )}
                        </div>
                    ) : null}
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
