import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

export default function Qa({ questions = [] }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Student Q&A">
            <Head title="Student Q&A" />
            <div className="staff-page">
                <PageHeader variant="staff" title="Student Questions" description="Answer course questions from your learners." />
                <div className="space-y-4">
                    {questions.map((q) => (
                        <PortalCard key={q.id}>
                            <p className="text-xs text-on-surface-variant break-words">
                                {q.courseTitle} · {q.studentName}
                            </p>
                            <h3 className="font-bold mt-1 break-words">{q.title}</h3>
                            <p className="text-sm mt-2 break-words">{q.body}</p>
                            {q.answers?.map((a) => (
                                <div key={a.id} className="mt-3 pl-4 border-l-2 border-primary text-sm min-w-0">
                                    <strong>Your answer:</strong> <span className="break-words">{a.body}</span>
                                </div>
                            ))}
                            <form method="POST" action={`/tutor/qa/${q.id}/answer`} className="mt-4 flex flex-wrap gap-2 min-w-0">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="text" name="body" required placeholder="Write your answer..." className="flex-1 min-w-[200px] px-4 py-2 border border-outline-variant/30 rounded-xl" />
                                <button type="submit" className="btn-primary btn-sm shrink-0">
                                    Reply
                                </button>
                            </form>
                        </PortalCard>
                    ))}
                    {!questions.length ? <p className="text-on-surface-variant">No questions yet.</p> : null}
                </div>
            </div>
        </DashboardLayout>
    );
}
