import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

function timeAgo(iso) {
    if (!iso) return '';
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 48) return `${hrs}h ago`;
    return new Date(iso).toLocaleDateString();
}

export default function MessagesIndex({ conversations = [], startStudentOptions = [] }) {
    const { csrfToken } = usePage().props;
    const [startCourseId, setStartCourseId] = useState(startStudentOptions[0]?.courseId ?? '');

    return (
        <DashboardLayout title="Messages">
            <Head title="Direct Messages" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Direct Messages Hub"
                    description="Private 1-on-1 communication with enrolled learners and families."
                />
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <PortalCard className="md:col-span-2" title="Private 1:1 Inbox">
                        <div className="space-y-3">
                            {conversations.length ? (
                                conversations.map((c) => (
                                    <Link key={c.id} href={`/tutor/messages/${c.id}`} className="block rounded-2xl border border-outline-variant/25 p-4 hover:border-primary/50 bg-surface-container-low/50">
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="w-10 h-10 shrink-0 rounded-full bg-primary/20 text-primary font-bold flex items-center justify-center">{(c.studentName || 'S')[0]}</div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex justify-between gap-2">
                                                    <h4 className="font-bold text-sm truncate">{c.studentName}</h4>
                                                    <span className="shrink-0 text-[10px] text-on-surface-variant">{timeAgo(c.lastMessageAt)}</span>
                                                </div>
                                                <p className="text-xs text-on-surface-variant truncate">{c.preview}</p>
                                            </div>
                                        </div>
                                    </Link>
                                ))
                            ) : (
                                <p className="py-8 text-center text-xs text-on-surface-variant">No active conversations yet.</p>
                            )}
                        </div>
                    </PortalCard>
                    <PortalCard title="Start 1:1 Conversation">
                        <form method="POST" action="/tutor/messages/start" className="space-y-3">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <select
                                name="student_id"
                                required
                                className="w-full min-w-0 px-3 py-2 border border-outline-variant/30 rounded-xl text-sm"
                                onChange={(e) => {
                                    const opt = startStudentOptions.find((o) => String(o.studentId) === e.target.value);
                                    setStartCourseId(opt?.courseId ?? '');
                                }}
                            >
                                <option value="">Select enrolled student</option>
                                {startStudentOptions.map((o) => (
                                    <option key={`${o.studentId}-${o.courseId}`} value={o.studentId}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                            <input type="hidden" name="course_id" value={startCourseId} />
                            <button type="submit" className="btn-primary w-full text-sm font-bold py-2">
                                Start Conversation
                            </button>
                        </form>
                    </PortalCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
