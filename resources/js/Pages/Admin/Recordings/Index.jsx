import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function Index({
    tab = 'timeline',
    retentionDays = 90,
    filters = {},
    students = [],
    pendingRequests = [],
    recordings = { data: [], links: [] },
    timeline = [],
}) {
    const { csrfToken, flash } = usePage().props;
    const recordingRows = recordings.data || [];
    const tabLink = (t) => `/admin/recordings?tab=${t}`;

    return (
        <DashboardLayout title="Class Recordings">
            <Head title="Class Recordings" />
            <div className="staff-page">
                <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-extrabold text-on-surface tracking-tight" style={{ fontFamily: 'Nunito, system-ui, sans-serif' }}>
                            Class recordings & timeline
                        </h1>
                        <p className="text-sm text-on-surface-variant mt-1">Recordings are kept for {retentionDays} days from creation.</p>
                    </div>
                    <div className="flex gap-2 text-sm font-bold">
                        <Link href={tabLink('timeline')} className={`px-3 py-1.5 rounded-full ${tab === 'timeline' ? 'bg-primary text-white' : 'bg-surface-container-low'}`}>Timeline</Link>
                        <Link href={tabLink('recordings')} className={`px-3 py-1.5 rounded-full ${tab === 'recordings' ? 'bg-primary text-white' : 'bg-surface-container-low'}`}>All recordings</Link>
                        <Link href={tabLink('requests')} className={`px-3 py-1.5 rounded-full ${tab === 'requests' ? 'bg-primary text-white' : 'bg-surface-container-low'}`}>
                            Requests{pendingRequests.length ? <span className="ml-1 inline-flex min-w-[1.25rem] h-5 px-1 rounded-full bg-tertiary text-white text-[10px] items-center justify-center">{pendingRequests.length}</span> : null}
                        </Link>
                    </div>
                </div>
                {flash?.success ? <div className="rounded-xl bg-tertiary-fixed text-tertiary px-4 py-3 text-sm font-semibold">{flash.success}</div> : null}

                {tab === 'requests' ? (
                    <PortalCard className="bg-white overflow-hidden divide-y">
                        {pendingRequests.length === 0 ? <div className="p-8 text-center text-on-surface-variant text-sm">No pending requests.</div> : pendingRequests.map((req) => (
                            <div key={req.id} className="p-5 flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
                                <div>
                                    <p className="font-bold">{req.studentName} <span className="font-normal text-on-surface-variant text-sm">({req.studentEmail})</span></p>
                                    <p className="text-sm mt-1">{req.recordingTitle}</p>
                                    <p className="text-xs text-on-surface-variant mt-1">Recorded {req.recordedAtLabel} · Deletes {req.expiresAtLabel}</p>
                                    {req.note ? <p className="text-xs mt-2 italic text-on-surface-variant">“{req.note}”</p> : null}
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <form method="POST" action="/admin/recordings/assign">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="class_recording_id" value={req.classRecordingId} />
                                        <input type="hidden" name="recording_request_id" value={req.id} />
                                        <input type="hidden" name="student_ids[]" value={req.studentId} />
                                        <button className="btn-primary btn-sm">Assign & approve</button>
                                    </form>
                                    <form method="POST" action={req.rejectUrl}><input type="hidden" name="_token" value={csrfToken} /><button className="btn btn-outline-primary btn-sm">Reject</button></form>
                                </div>
                            </div>
                        ))}
                    </PortalCard>
                ) : null}

                {tab === 'recordings' ? (
                    <>
                        <form method="GET" className="flex gap-2">
                            <input type="hidden" name="tab" value="recordings" />
                            <input type="search" name="q" defaultValue={filters.q || ''} placeholder="Search title or room…" className="flex-1 rounded-xl border px-3 py-2 text-sm" />
                            <button className="btn-primary btn-sm">Search</button>
                        </form>
                        <div className="space-y-4">
                            {recordingRows.length === 0 ? <PortalCard className="p-10 text-center text-on-surface-variant">No recordings synced yet.</PortalCard> : recordingRows.map((r) => (
                                <div key={r.id} className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 space-y-3">
                                    <h2 className="font-bold text-lg">{r.title}</h2>
                                    <p className="text-xs text-on-surface-variant">Course: {r.courseTitle ?? '—'} · Tutor: {r.tutorName ?? '—'}</p>
                                    <p className="text-xs text-on-surface-variant">Recorded {r.recordedAtLabel} · Deletes {r.expiresAtLabel}{r.deletedFromStorageAtLabel ? ` · Removed ${r.deletedFromStorageAtLabel}` : r.isAvailable ? ' · Available' : ' · Expired'}</p>
                                    <p className="text-xs">Assigned: {r.assignedStudents.length ? r.assignedStudents.join(', ') : 'none'}</p>
                                    {r.isAvailable ? (
                                        <form method="POST" action="/admin/recordings/assign" className="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end border-t pt-3">
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="class_recording_id" value={r.id} />
                                            <select name="student_ids[]" multiple size={5} className="staff-multiselect flex-1 min-h-[7.5rem] max-h-[10rem]">
                                                {students.map((s) => <option key={s.id} value={s.id}>{s.name} ({s.email})</option>)}
                                            </select>
                                            <button className="btn-primary btn-sm shrink-0">Assign</button>
                                        </form>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                        <PaginationLinks links={recordings.links} />
                    </>
                ) : null}

                {tab === 'timeline' ? (
                    <>
                        <form method="GET" className="flex flex-wrap gap-2 items-end">
                            <input type="hidden" name="tab" value="timeline" />
                            <div><label className="text-[11px] font-bold uppercase text-on-surface-variant">From</label><input type="date" name="from" defaultValue={filters.from} className="block mt-1 rounded-xl border px-3 py-2 text-sm" /></div>
                            <div><label className="text-[11px] font-bold uppercase text-on-surface-variant">To</label><input type="date" name="to" defaultValue={filters.to} className="block mt-1 rounded-xl border px-3 py-2 text-sm" /></div>
                            <button className="btn-primary btn-sm">Filter</button>
                        </form>
                        <PortalCard className="bg-white overflow-x-auto">
                            <table className="staff-table w-full text-sm">
                                <thead className="bg-surface-container-low text-left text-[11px] uppercase text-on-surface-variant">
                                    <tr><th className="font-bold">Class date</th><th className="font-bold">Type</th><th className="font-bold">Class / course</th><th className="font-bold">Tutor</th><th className="font-bold">Student</th><th className="font-bold">Recording</th><th className="font-bold">Deletes on</th></tr>
                                </thead>
                                <tbody className="divide-y divide-outline-variant/20">
                                    {timeline.length === 0 ? <tr><td colSpan={7} className="px-4 py-10 text-center text-on-surface-variant">No classes in this range.</td></tr> : timeline.map((row, i) => (
                                        <tr key={i}>
                                            <td className="whitespace-nowrap font-semibold">{row.whenLabel ?? '—'}</td>
                                            <td className="text-on-surface-variant">{row.type}</td>
                                            <td><div className="font-bold">{row.title}</div><div className="text-xs text-on-surface-variant">Status: {row.status ?? '—'}</div></td>
                                            <td>{row.tutor ?? '—'}</td>
                                            <td>{row.student ?? '—'}</td>
                                            <td>{row.recordingTitle ? <><div className="font-semibold">{row.recordingTitle}</div><div className="text-xs">Created {row.recordedAtLabel}</div>{row.deletedAtLabel ? <div className="text-xs text-error font-bold">Deleted {row.deletedAtLabel}</div> : null}</> : <span className="text-on-surface-variant">No recording yet</span>}</td>
                                            <td className="whitespace-nowrap"><span className={`font-bold ${row.deletesAtPast || row.deletedAtLabel ? 'text-error' : ''}`}>{row.deletesAtLabel ?? '—'}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </PortalCard>
                    </>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
