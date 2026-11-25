import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import AssignmentSubmissionAnswers from '../../Components/Tutor/AssignmentSubmissionAnswers';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Assignments({ assignments = [] }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Assignments">
            <Head title="Post class Activity" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Post class Activity"
                    description="Review and grade submissions for activities assigned by admin. Creating and assigning post-class work is admin-only."
                />

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium">{flash.success}</div> : null}
                {flash?.error ? <div className="p-4 bg-error-container rounded-xl font-medium">{flash.error}</div> : null}

                <div className="space-y-4">
                    <h3 className="text-base font-extrabold">Activities for your students</h3>
                    {assignments.length ? (
                        assignments.map((a) => (
                            <PortalCard key={a.id}>
                                <div className="flex justify-between items-start gap-3 mb-2 min-w-0">
                                    <div className="min-w-0">
                                        <h3 className="font-bold text-lg flex items-center gap-2 flex-wrap">
                                            <span className="break-words">{a.title}</span>
                                            {a.courseTitle ? <span className="px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full shrink-0">{a.courseTitle}</span> : null}
                                        </h3>
                                        <p className="text-sm text-on-surface-variant mt-1 break-words">
                                            Assigned to:{' '}
                                            {a.studentNames.map((name) => (
                                                <span key={name} className="inline-block bg-surface-container px-2 py-0.5 rounded-full text-xs font-medium mr-1">
                                                    {name}
                                                </span>
                                            ))}
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-sm text-on-surface-variant">{formatLocalTime(a.createdAt, 'date')}</span>
                                </div>
                                {a.fileUrl ? (
                                    <a href={a.fileUrl} target="_blank" rel="noreferrer" className="text-primary text-sm hover:underline flex items-center gap-1 mt-1">
                                        <span className="material-symbols-outlined text-[16px]">attachment</span> View Attached Prompt
                                    </a>
                                ) : null}
                                {a.questionsCount ? <p className="text-sm text-on-surface-variant mt-1">{a.questionsCount} question(s)</p> : null}
                                <div className="mt-4 border-t border-outline-variant/30 pt-4">
                                    <h4 className="font-bold text-sm mb-3">Submissions ({a.submissions.length})</h4>
                                    {a.submissions.length ? (
                                        a.submissions.map((s) => (
                                            <div key={s.id} className="mt-2 p-4 bg-surface-container-low rounded-xl min-w-0">
                                                <p className="text-sm flex justify-between gap-3 min-w-0">
                                                    <strong className="truncate">{s.studentName}</strong>
                                                    <span className="shrink-0 text-xs text-on-surface-variant">Submitted: {formatLocalTime(s.createdAt, 'datetime-short')}</span>
                                                </p>
                                                <AssignmentSubmissionAnswers submission={s} />
                                                <form method="POST" action={s.gradeUrl} className="mt-4 flex flex-wrap gap-2">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input type="number" name="grade" min={0} max={100} placeholder="Grade" defaultValue={s.grade ?? ''} className="w-24 px-3 py-2 border rounded-lg text-sm" />
                                                    <input type="text" name="feedback" placeholder="Feedback" defaultValue={s.feedback ?? ''} className="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm" />
                                                    <button type="submit" className="btn-enroll btn-sm px-4">
                                                        {s.status === 'graded' ? 'Update Grade' : 'Grade'}
                                                    </button>
                                                </form>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-sm text-on-surface-variant italic">No submissions yet.</p>
                                    )}
                                </div>
                            </PortalCard>
                        ))
                    ) : (
                        <p className="text-on-surface-variant">No post-class activities for your students yet. Admin assigns these from the admin portal.</p>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
