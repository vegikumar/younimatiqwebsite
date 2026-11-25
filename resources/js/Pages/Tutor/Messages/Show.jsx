import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import { formatLocalTime } from '../../../utils/formatLocalTime';

export default function MessagesShow({ conversation = {}, messages = [] }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Conversation">
            <Head title={`Chat with ${conversation.studentName ?? 'Student'}`} />
            <div className="staff-page max-w-3xl">
                <Link href="/tutor/messages" className="text-sm font-bold text-primary">
                    &larr; Back to inbox
                </Link>
                <PageHeader variant="staff" title={conversation.studentName ?? 'Student'} description="Private conversation with your learner." />
                <PortalCard>
                    <div className="min-h-[400px] flex flex-col">
                    <div className="flex-1 space-y-3 overflow-y-auto mb-4 max-h-[50vh] min-w-0">
                        {messages.map((m) => (
                            <div key={m.id} className={`max-w-[85%] p-3 rounded-2xl text-sm break-words ${m.isMine ? 'ml-auto bg-primary text-white' : 'bg-surface-container-low'}`}>
                                <p>{m.body}</p>
                                <p className={`text-[10px] mt-1 ${m.isMine ? 'text-white/80' : 'text-on-surface-variant'}`}>{formatLocalTime(m.createdAt, 'datetime-short')}</p>
                            </div>
                        ))}
                        {!messages.length ? <p className="text-center text-on-surface-variant text-sm">No messages yet.</p> : null}
                    </div>
                    <form method="POST" action={`/tutor/messages/${conversation.id}`} className="flex gap-2 border-t border-outline-variant/25 pt-4 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="message" required placeholder="Type your message..." className="flex-1 min-w-0 px-4 py-2 border border-outline-variant/30 rounded-xl" />
                        <button type="submit" className="btn-primary shrink-0 px-5">
                            Send
                        </button>
                    </form>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
