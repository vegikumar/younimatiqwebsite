import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import JoinClassAction from '../../Components/JoinClassAction';
import PageHeader from '../../Components/Portal/PageHeader';
import EmptyState from '../../Components/Portal/EmptyState';
import { bookingHostLinkAvailable, formatLocalTime, sessionEnded } from '../../utils/formatLocalTime';

function tutorAvatar(name) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Tutor')}&background=046bd2&color=fff`;
}

function bookingAction(booking) {
    if (booking.status === 'completed') {
        return (
            <span className="text-on-surface-variant text-xs opacity-70 flex items-center gap-1">
                <span className="material-symbols-outlined text-[14px]">event_busy</span> Session Ended
            </span>
        );
    }
    if (booking.zoomJoinUrl) {
        return <JoinClassAction joinUrl={booking.zoomJoinUrl} passcode={booking.zoomPasscode} />;
    }
    return null;
}

function demoStatus(demo) {
    if (demo.status === 'completed') {
        return <span className="px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-bold">Completed</span>;
    }
    if (demo.zoomJoinUrl) {
        return <span className="px-3 py-1 bg-tertiary-container text-on-tertiary-container rounded-full text-xs font-bold">Confirmed</span>;
    }
    return <span className="px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-bold">Pending Link</span>;
}

function demoAction(demo) {
    if (demo.status === 'completed') {
        return (
            <span className="text-xs text-on-surface-variant opacity-70 flex items-center gap-1">
                <span className="material-symbols-outlined text-[14px]">event_busy</span> Session Ended
            </span>
        );
    }
    if (demo.zoomJoinUrl) {
        return <JoinClassAction joinUrl={demo.zoomJoinUrl} passcode={demo.zoomPasscode} />;
    }
    return <span className="text-xs text-on-surface-variant opacity-70">Link Pending</span>;
}

export default function Bookings({ bookings = [], availableSessions = [], demoSessions = [], bookSessionUrl = '/student/bookings' }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Bookings">
            <Head title="Tutor Bookings" />
            <div className="p-5 md:p-8 space-y-8">
                <PageHeader
                    title="Tutor bookings"
                    description="Book live sessions with your course tutors and join when the class starts."
                />

                {availableSessions.length ? (
                    <div className="bg-white rounded-2xl border border-surface-container-high shadow-sm p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6">Available Sessions</h2>
                        <div className="space-y-4">
                            {availableSessions.map((session) => (
                                <div key={session.id} className="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                                    <div>
                                        <h3 className="font-bold">{session.courseTitle}</h3>
                                        <p className="text-sm text-on-surface-variant">
                                            {formatLocalTime(session.startTime)} · {session.durationMinutes} min
                                        </p>
                                    </div>
                                    <form method="POST" action={bookSessionUrl}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="live_session_id" value={session.id} />
                                        <button type="submit" className="btn-primary btn-sm">
                                            Book Session
                                        </button>
                                    </form>
                                </div>
                            ))}
                        </div>
                    </div>
                ) : null}

                <div className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-surface-container-high">
                        <h2 className="font-headline-md font-bold">My Bookings</h2>
                    </div>
                    <div className="overflow-x-auto w-full">
                        <table className="portal-table w-full text-left">
                            <thead>
                                <tr>
                                    <th>Tutor</th>
                                    <th>Course</th>
                                    <th>When</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-surface-container-high">
                                {bookings.length ? (
                                    bookings.map((booking) => (
                                        <tr key={booking.id} className="hover:bg-surface-container-low transition-colors">
                                            <td>
                                                <div className="flex items-center gap-3">
                                                    <img src={tutorAvatar(booking.tutorName)} className="w-10 h-10 rounded-full" alt="" />
                                                    <span className="font-bold">{booking.tutorName}</span>
                                                </div>
                                            </td>
                                            <td className="font-bold">{booking.courseTitle}</td>
                                            <td className="text-sm text-on-surface-variant whitespace-nowrap">
                                                {booking.scheduledAt ? formatLocalTime(booking.scheduledAt) : '—'}
                                            </td>
                                            <td>
                                                <span className={`px-3 py-1 rounded-full text-xs font-bold ${booking.status === 'active' ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant'}`}>{booking.status ? booking.status.charAt(0).toUpperCase() + booking.status.slice(1) : '—'}</span>
                                            </td>
                                            <td>{bookingAction(booking)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="p-4">
                                            <EmptyState
                                                icon="calendar_month"
                                                title="No bookings yet"
                                                description={
                                                    availableSessions.length
                                                        ? 'Book an available session above to get started.'
                                                        : 'When tutors open sessions for your courses, they will appear here.'
                                                }
                                            />
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {demoSessions.length ? (
                    <div className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
                        <div className="p-6 border-b border-surface-container-high flex items-center gap-3">
                            <span className="material-symbols-outlined text-secondary text-2xl">star</span>
                            <h2 className="font-headline-md font-bold">Free Consultations & Trial Classes</h2>
                        </div>
                        <div className="overflow-x-auto w-full">
                            <table className="portal-table w-full text-left">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-surface-container-high">
                                    {demoSessions.map((demo) => (
                                        <tr key={demo.id} className="hover:bg-surface-container-low transition-colors">
                                            <td>
                                                <div className="font-bold">{demo.displayTitle}</div>
                                                <div className="text-xs text-on-surface-variant mt-0.5">{demo.kindLabel}</div>
                                            </td>
                                            <td>{demoStatus(demo)}</td>
                                            <td>{demoAction(demo)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
