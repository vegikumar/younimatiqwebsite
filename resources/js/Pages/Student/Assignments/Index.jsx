import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';

export default function Index({ assignments = [] }) {
    const { flash } = usePage().props;

    return (
        <DashboardLayout title="Post class Activity">
            <Head title="Post class Activity" />
            <div className="p-5 md:p-8 space-y-8 max-w-[1600px] mx-auto">
                <div>
                    <h1 className="font-headline-lg text-headline-lg font-bold mb-2">Post class Activity 📝</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Keep track of your pending and completed tasks.</p>
                </div>
                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium">{flash.success}</div> : null}
                {assignments.length === 0 ? (
                    <div className="bg-surface-container-low border-2 border-dashed rounded-2xl p-12 text-center text-on-surface-variant">
                        <Icon name="assignment" className="text-5xl mb-4 mx-auto" />
                        <p className="font-bold">No assignments yet.</p>
                    </div>
                ) : (
                    assignments.map((a) => (
                        <AssignmentCard key={a.id} assignment={a} />
                    ))
                )}
            </div>
        </DashboardLayout>
    );
}

function AssignmentCard({ assignment }) {
    const submitted = assignment.submission;

    return (
        <div className="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
            <h3 className="font-headline-md font-bold mb-1">{assignment.title}</h3>
            <p className="text-sm text-on-surface-variant">Course: {assignment.course?.title || 'N/A'}</p>
            {assignment.description ? <p className="text-sm text-on-surface-variant mt-2">{assignment.description}</p> : null}
            <div className="mt-6 pt-6 border-t flex flex-wrap items-center gap-3">
                <Link href={assignment.take_url} className="btn-primary btn-sm inline-flex items-center gap-2">
                    <Icon name={submitted ? 'edit' : 'play_arrow'} />
                    {submitted ? 'Update answers' : 'Take assignment'}
                </Link>
                {submitted ? <span className="text-sm text-on-surface-variant">Submitted {submitted.submitted_at_label}{submitted.grade != null ? ` · Grade ${submitted.grade}%` : ''}</span> : null}
            </div>
        </div>
    );
}
