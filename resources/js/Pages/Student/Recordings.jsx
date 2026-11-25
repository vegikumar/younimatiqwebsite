import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';

export default function Recordings({ recordings = [], requestable = [], pendingRequestIds = [] }) {
    return (
        <DashboardLayout title="Class recordings">
            <Head title="Class recordings" />
            <div className="p-6 md:p-8 max-w-4xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold">Recordings</h1>
                {recordings.length === 0 ? (
                    <p className="text-on-surface-variant">No recordings available yet.</p>
                ) : (
                    recordings.map((r) => (
                        <div key={r.id} className="clay-card p-5 flex items-center justify-between gap-4">
                            <div>
                                <p className="font-bold">{r.title || r.course_title}</p>
                                <p className="text-sm text-on-surface-variant">{r.recorded_at_label}</p>
                            </div>
                            {r.play_url ? (
                                <a href={r.play_url} className="btn-primary btn-sm">
                                    Watch
                                </a>
                            ) : null}
                        </div>
                    ))
                )}
                {requestable?.length ? (
                    <section>
                        <h2 className="font-bold mb-3">Request a recording</h2>
                        {requestable.map((item) => (
                            <div key={item.id} className="flex items-center justify-between py-2 border-b">
                                <span>{item.label}</span>
                                {pendingRequestIds.includes(item.id) ? (
                                    <span className="text-xs font-bold text-warning">Pending</span>
                                ) : (
                                    <Link href={item.request_url} method="post" as="button" className="text-primary text-sm font-bold">
                                        Request
                                    </Link>
                                )}
                            </div>
                        ))}
                    </section>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
