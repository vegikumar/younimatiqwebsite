import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import PaginationLinks from '../../../Components/PaginationLinks';
import DataTableShell from '../../../Components/Portal/DataTableShell';

export default function Index({ reviews = { data: [], links: [], total: 0 }, filterRole = null, search = '' }) {
    const { flash, csrfToken } = usePage().props;
    const rows = reviews.data || [];
    const tabClass = (active) =>
        `inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-outline-variant/30 ${active ? 'bg-primary text-white' : 'bg-white text-primary'}`;

    return (
        <DashboardLayout title="Course Reviews">
            <Head title="Course Reviews" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Quality · Admin only"
                    title="Course Reviews"
                    description="Star ratings from parents and students on full courses. Tutors do not see these submissions."
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Link href="/admin/reviews" className={tabClass(!filterRole)}>All</Link>
                            <Link href="/admin/reviews?role=parent" className={tabClass(filterRole === 'parent')}>Parents</Link>
                            <Link href="/admin/reviews?role=student" className={tabClass(filterRole === 'student')}>Students</Link>
                            <Link href="/admin/feedback" className="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold border border-outline-variant/30 bg-white text-on-surface">
                                Class Feedback →
                            </Link>
                        </div>
                    }
                />

                {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}

                <PortalCard className="mb-4">
                    <form method="GET" action="/admin/reviews" className="flex flex-wrap gap-3 items-end">
                        {filterRole ? <input type="hidden" name="role" value={filterRole} /> : null}
                        <div className="flex-1 min-w-[220px]">
                            <label className="block text-xs font-bold mb-1 text-on-surface-variant">Search</label>
                            <input
                                type="search"
                                name="q"
                                defaultValue={search || ''}
                                placeholder="Course, author, comment…"
                                className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm"
                            />
                        </div>
                        <button type="submit" className="btn-primary text-sm px-4 py-2.5">Search</button>
                    </form>
                </PortalCard>

                <PortalCard>
                    <DataTableShell
                        minWidth="960px"
                        mobileCards={rows.map((r) => (
                            <div key={r.id} className="staff-mobile-card min-w-0 space-y-1">
                                <p className="font-medium break-words">{r.courseTitle}</p>
                                <p className="text-sm">{r.rating}/5 · {r.authorName} ({r.authorRole})</p>
                                {r.comment ? <p className="text-xs text-on-surface-variant line-clamp-3">{r.comment}</p> : null}
                                <p className="text-xs text-on-surface-variant">{r.createdAtLabel}</p>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-outline-variant/30 text-on-surface-variant">
                                    <th className="font-bold">Course</th>
                                    <th className="font-bold">Author</th>
                                    <th className="font-bold">Role</th>
                                    <th className="font-bold">Rating</th>
                                    <th className="font-bold">Comment</th>
                                    <th className="font-bold">Submitted</th>
                                    <th className="font-bold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 ? (
                                    <tr><td colSpan={7} className="py-8 text-center text-on-surface-variant">No course reviews yet.</td></tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.id} className="border-b border-outline-variant/10 align-top">
                                            <td className="font-medium break-words max-w-[220px]">{r.courseTitle}</td>
                                            <td className="break-words">
                                                <div className="font-medium">{r.authorName}</div>
                                                {r.authorEmail ? <div className="text-xs text-on-surface-variant">{r.authorEmail}</div> : null}
                                            </td>
                                            <td>
                                                <span className="px-2 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">{r.authorRole}</span>
                                            </td>
                                            <td className="font-bold text-primary whitespace-nowrap">{r.rating}/5</td>
                                            <td className="text-sm text-on-surface max-w-[320px] break-words">{r.comment || '—'}</td>
                                            <td className="text-sm whitespace-nowrap">{r.createdAtLabel}</td>
                                            <td className="text-right">
                                                <form method="POST" action={r.destroyUrl} onSubmit={(e) => { if (!confirm('Remove this course review?')) e.preventDefault(); }}>
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input type="hidden" name="_method" value="DELETE" />
                                                    <button type="submit" className="text-xs font-bold text-error hover:underline">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </DataTableShell>
                    <div className="mt-6"><PaginationLinks links={reviews.links} /></div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
