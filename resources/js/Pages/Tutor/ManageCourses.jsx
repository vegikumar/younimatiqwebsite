import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import EmptyState from '../../Components/Portal/EmptyState';
import { formatLocalTime, formatMoney } from '../../utils/formatLocalTime';

export default function ManageCourses({ courses = [] }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Assigned Classes">
            <Head title="My Assigned Classes" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="My Assigned Classes"
                    description="View assigned classes and access course materials."
                    actions={
                        <Link href="/tutor/courses/create" className="btn-primary flex items-center gap-2">
                            <span className="material-symbols-outlined">add</span> Create Course
                        </Link>
                    }
                />
                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl">{flash.success}</div> : null}
                <div className="space-y-4">
                    {courses.length ? (
                        courses.map((course) => (
                            <PortalCard key={course.id}>
                                <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-6 border-b border-outline-variant/25 pb-6">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex flex-wrap items-center gap-2 mb-2">
                                            <h3 className="text-xl font-extrabold break-words">{course.title}</h3>
                                            <StatusPill label={course.format === 'tutoring' ? '1:1 Tutoring' : 'Group Class'} />
                                            <StatusPill label={course.courseType} muted />
                                            <StatusPill label={course.status} />
                                        </div>
                                        <p className="text-sm text-on-surface-variant">
                                            Price: <strong className="text-primary">${formatMoney(course.price)}</strong>
                                        </p>
                                        {course.nextSession ? (
                                            <div className="mt-4 p-4 bg-primary-container/30 rounded-xl border border-primary/20 flex flex-wrap items-center gap-4">
                                                <span className="text-primary font-bold text-sm break-words">
                                                    Next Session: {formatLocalTime(course.nextSession.startTime)} ({course.nextSession.durationMinutes} min)
                                                </span>
                                                {(course.nextSession.startUrl || course.nextSession.joinUrl) && (
                                                    <a href={course.nextSession.startUrl || course.nextSession.joinUrl} target="_blank" rel="noreferrer" className="btn-primary shrink-0 py-2 px-5 text-sm">
                                                        Start Class (Host)
                                                    </a>
                                                )}
                                            </div>
                                        ) : (
                                            <p className="mt-4 text-sm text-on-surface-variant">No upcoming sessions scheduled.</p>
                                        )}
                                    </div>
                                    <div className="flex flex-col gap-3 min-w-[200px] shrink-0">
                                        <Link href={course.classroomUrl} className="px-6 py-3 bg-surface-container border font-bold rounded-xl text-center text-sm">
                                            Classroom Hub
                                        </Link>
                                        {course.isOwner ? (
                                            <>
                                                <div className="flex gap-2">
                                                    <Link href={course.editUrl} className="flex-1 px-4 py-2 bg-primary/10 text-primary font-bold rounded-xl text-center text-sm">
                                                        Edit
                                                    </Link>
                                                    <form action={`/tutor/courses/${course.id}`} method="POST" className="flex-1" onSubmit={(e) => !confirm('Delete this course?') && e.preventDefault()}>
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <input type="hidden" name="_method" value="DELETE" />
                                                        <button type="submit" className="w-full px-4 py-2 bg-error-container font-bold rounded-xl text-sm">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                                {['draft', 'rejected'].includes(course.status) ? (
                                                    <form action={`/tutor/courses/${course.id}/submit`} method="POST" onSubmit={(e) => !confirm('Submit for review?') && e.preventDefault()}>
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <button type="submit" className="w-full px-4 py-2 bg-warning-container text-warning font-bold rounded-xl text-sm">
                                                            Submit for Review
                                                        </button>
                                                    </form>
                                                ) : null}
                                            </>
                                        ) : null}
                                    </div>
                                </div>
                                {course.units?.length ? (
                                    <div className="space-y-4 pt-6">
                                        <h4 className="font-bold flex items-center gap-2">
                                            <span className="material-symbols-outlined text-primary">view_list</span> Syllabus Outline
                                        </h4>
                                        {course.units.map((unit, ui) => (
                                            <div key={ui} className="p-5 border border-outline-variant/25 rounded-2xl bg-surface-container-low/50">
                                                <div className="font-bold flex items-center gap-2 mb-1 min-w-0">
                                                    <span className="w-6 h-6 shrink-0 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center">{unit.order}</span>
                                                    <span className="break-words">{unit.title}</span>
                                                </div>
                                                {unit.description ? <p className="text-xs text-on-surface-variant mb-3 break-words">{unit.description}</p> : null}
                                                <div className="space-y-2 pl-4 border-l-2 border-primary/20">
                                                    {unit.modules?.length ? (
                                                        unit.modules.map((mod, mi) => (
                                                            <div key={mi} className="p-3 bg-white rounded-xl border border-outline-variant/25 flex justify-between gap-2 text-sm min-w-0">
                                                                <span className="font-medium break-words">{mod.title}</span>
                                                                <span className="shrink-0 text-xs uppercase font-bold text-on-surface-variant">{mod.contentType}</span>
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <p className="text-xs italic text-on-surface-variant">No lessons in this unit yet.</p>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : null}
                            </PortalCard>
                        ))
                    ) : (
                        <PortalCard>
                            <EmptyState icon="school" title="No assigned classes" description="You have not been assigned to any classes yet." />
                        </PortalCard>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}

function StatusPill({ label, muted }) {
    return <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase ${muted ? 'bg-surface-container-high text-on-surface-variant' : 'bg-primary-container'}`}>{label}</span>;
}
