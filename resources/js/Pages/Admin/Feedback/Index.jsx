import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PaginationLinks from '../../../Components/PaginationLinks';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';

export default function Index({ feedbacks = { data: [], links: [] }, filterType = null, source = 'tutor' }) {
    const rows = feedbacks.data || [];
    const isLearner = source === 'parent' || source === 'student' || filterType === 'parent' || filterType === 'student';
    const submitterLabel = filterType === 'student' ? 'Student' : 'Parent';
    const tabClass = (active) =>
        `inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-outline-variant/30 ${active ? 'bg-primary text-white' : 'bg-white text-primary'}`;

    return (
        <DashboardLayout title="Class Feedback">
            <Head title="Class Feedback" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Quality"
                    title="Class feedback"
                    description={isLearner
                        ? `Class session feedback from ${submitterLabel.toLowerCase()}s — admin only (tutors cannot see these). Separate from Course Reviews.`
                        : 'Tutor-submitted class notes. Parent/student feedback and course reviews stay admin-only.'}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Link href="/admin/feedback" className={tabClass(!filterType)}>Tutor · All</Link>
                            <Link href="/admin/feedback?type=demo" className={tabClass(filterType === 'demo')}>Demo</Link>
                            <Link href="/admin/feedback?type=regular" className={tabClass(filterType === 'regular')}>Regular</Link>
                            <Link href="/admin/feedback?type=parent" className={tabClass(filterType === 'parent')}>From Parents</Link>
                            <Link href="/admin/feedback?type=student" className={tabClass(filterType === 'student')}>From Students</Link>
                        </div>
                    }
                />
                <PortalCard>
                    {isLearner ? (
                        <>
                            <DataTableShell
                                minWidth="960px"
                                mobileCards={rows.map((fb) => (
                                    <div key={fb.id} className="staff-mobile-card min-w-0 space-y-1">
                                        <p className="font-medium break-words">{fb.studentName}</p>
                                        <p className="text-sm text-on-surface-variant">{fb.submitterName || fb.parentName} · {fb.tutorName}</p>
                                        <p className="text-xs">{fb.rating}/5 · {fb.createdAtLabel}</p>
                                        <Link href={fb.showUrl} className="text-xs font-bold text-primary">Open</Link>
                                    </div>
                                ))}
                            >
                                <table className="staff-table w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b border-outline-variant/30 text-on-surface-variant">
                                            <th className="font-bold">Student</th>
                                            <th className="font-bold">{submitterLabel}</th>
                                            <th className="font-bold">Tutor</th>
                                            <th className="font-bold">Rating</th>
                                            <th className="font-bold">Submitted</th>
                                            <th className="font-bold">Status</th>
                                            <th className="font-bold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.length === 0 ? (
                                            <tr><td colSpan={7} className="py-8 text-center text-on-surface-variant">No {submitterLabel.toLowerCase()} class feedback yet.</td></tr>
                                        ) : (
                                            rows.map((fb) => (
                                                <tr key={fb.id} className="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                                                    <td className="font-medium break-words">{fb.studentName}</td>
                                                    <td className="break-words">{fb.submitterName || fb.parentName}</td>
                                                    <td className="break-words">{fb.tutorName}</td>
                                                    <td className="font-bold text-primary">{fb.rating}/5</td>
                                                    <td className="text-sm">{fb.createdAtLabel}</td>
                                                    <td>
                                                        {fb.reviewed ? (
                                                            <span className="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold">Reviewed</span>
                                                        ) : (
                                                            <span className="text-sm text-outline italic">New</span>
                                                        )}
                                                    </td>
                                                    <td className="text-right">
                                                        <Link href={fb.showUrl} className="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm">Open</Link>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </DataTableShell>
                        </>
                    ) : (
                        <DataTableShell
                            minWidth="960px"
                            mobileCards={rows.map((fb) => (
                                <div key={fb.id} className="staff-mobile-card min-w-0 space-y-1">
                                    <p className="font-medium break-words">{fb.studentName}</p>
                                    <p className="text-sm text-on-surface-variant">{fb.tutorName}</p>
                                    <p className="text-xs">{fb.sessionType === 'demo' ? 'Demo' : 'Regular'} · {fb.createdAtLabel}</p>
                                    {fb.sharedWithParent ? <p className="text-xs font-bold text-secondary">Sent to parent</p> : null}
                                    <Link href={fb.showUrl} className="text-xs font-bold text-primary">Review & act</Link>
                                </div>
                            ))}
                        >
                            <table className="staff-table w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-outline-variant/30 text-on-surface-variant">
                                        <th className="font-bold">Class Type</th>
                                        <th className="font-bold">Student</th>
                                        <th className="font-bold">Tutor</th>
                                        <th className="font-bold">Submitted</th>
                                        <th className="font-bold">Customer Type</th>
                                        <th className="font-bold">Parent</th>
                                        <th className="font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length === 0 ? (
                                        <tr><td colSpan={7} className="py-8 text-center text-on-surface-variant">No feedback records found.</td></tr>
                                    ) : (
                                        rows.map((fb) => (
                                            <tr key={fb.id} className="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                                                <td>
                                                    {fb.sessionType === 'demo' ? (
                                                        <span className="px-2 py-1 bg-warning-container text-warning text-xs rounded-full font-bold uppercase">Demo</span>
                                                    ) : (
                                                        <span className="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold uppercase">Regular</span>
                                                    )}
                                                </td>
                                                <td className="font-medium text-on-surface min-w-0 break-words">{fb.studentName}</td>
                                                <td className="text-on-surface min-w-0 break-words">{fb.tutorName}</td>
                                                <td className="text-on-surface text-sm">{fb.createdAtLabel}</td>
                                                <td>
                                                    {fb.customerType ? (
                                                        <span className="px-3 py-1 bg-tertiary-fixed text-tertiary rounded-lg text-sm font-bold border border-tertiary-fixed-dim">{fb.customerType}</span>
                                                    ) : (
                                                        <span className="text-sm text-outline italic">Pending Decision</span>
                                                    )}
                                                </td>
                                                <td className="text-sm">
                                                    {fb.sharedWithParent ? (
                                                        <span className="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold">Sent</span>
                                                    ) : (
                                                        <span className="text-outline italic text-xs">Not sent</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    <Link href={fb.showUrl} className="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm">Review & Act</Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </DataTableShell>
                    )}
                    <div className="mt-6"><PaginationLinks links={feedbacks.links} /></div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
