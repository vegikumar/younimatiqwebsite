import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import RewardModal, { openRewardModal } from '../../Components/Tutor/RewardModal';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Classes({ bookings = [], campBookings = [], sessions = [], rewardPresets = [], badgeCatalog = [] }) {
    return (
        <DashboardLayout title="Upcoming Classes">
            <Head title="Upcoming Classes" />
            <RewardModal presets={rewardPresets} badges={badgeCatalog} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Teaching"
                    title="My assigned & upcoming classes"
                    description="Launch classes as Host and manage your teaching schedule."
                    actions={
                        <Link href="/tutor/availability" className="btn-primary btn-sm">
                            Update availability
                        </Link>
                    }
                />

                <BookingTable title="1:1 Assigned Course Classes" icon="person" rows={bookings} type="regular" />
                <BookingTable title="Assigned Free Consultations & Trials" icon="auto_awesome" rows={campBookings} type="camp" />

                <PortalCard
                    title={
                        <span className="inline-flex items-center gap-2">
                            <span className="material-symbols-outlined text-primary text-xl">groups</span>
                            Upcoming Live Sessions
                        </span>
                    }
                >
                    <div className="space-y-3">
                        {sessions.length ? (
                            sessions.map((s) => (
                                <div key={s.id} className="flex flex-wrap justify-between gap-3 rounded-xl border border-outline-variant/25 p-4">
                                    <div className="min-w-0">
                                        <p className="font-bold break-words">{s.courseTitle}</p>
                                        <p className="text-xs text-on-surface-variant">
                                            {formatLocalTime(s.startTime)} · {s.durationMinutes} min
                                        </p>
                                    </div>
                                    {s.launchUrl || s.startUrl ? (
                                        <a href={s.launchUrl || s.startUrl} className="btn-primary shrink-0 text-xs px-4 py-2 rounded-xl font-bold">
                                            Launch as Host
                                        </a>
                                    ) : (
                                        <span className="text-xs italic text-on-surface-variant">Link pending</span>
                                    )}
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-on-surface-variant">No upcoming live sessions.</p>
                        )}
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}

function BookingTable({ title, icon, rows, type }) {
    const renderActions = (b) => {
        return (
            <div className="staff-ops-stack" style={{ alignItems: 'flex-end' }}>
                {b.launchUrl || b.zoomStartUrl ? (
                    <a href={b.launchUrl || b.zoomStartUrl} className="staff-ops-btn">
                        Launch class
                    </a>
                ) : (
                    <span className="staff-ops-meta">Link pending</span>
                )}
                {type === 'regular' ? (
                    <>
                        <Link href={`/tutor/feedback/create?type=regular&booking_id=${b.id}`} className="staff-ops-btn staff-ops-btn--soft">
                            Feedback
                        </Link>
                        {b.studentId ? (
                            <button
                                type="button"
                                className="staff-ops-link"
                                onClick={() =>
                                    openRewardModal({
                                        id: b.studentId,
                                        name: b.studentName,
                                        courseId: b.courseId,
                                        bookingId: b.id,
                                        isTrial: b.sessionType === 'trial',
                                    })
                                }
                            >
                                Reward
                            </button>
                        ) : null}
                    </>
                ) : null}
            </div>
        );
    };

    return (
        <PortalCard
            title={
                <span className="inline-flex items-center gap-2">
                    <span className="material-symbols-outlined text-primary text-xl">{icon}</span>
                    {title}
                </span>
            }
        >
            <DataTableShell
                minWidth="960px"
                mobileCards={
                    rows.length
                        ? rows.map((b) => {
                              const name = type === 'camp' ? b.childName : b.studentName;
                              const course = type === 'camp' ? b.campName : b.courseTitle;
                              return (
                                  <div key={b.id} className="staff-mobile-card space-y-2">
                                      <p className="text-sm font-extrabold break-words">{name ?? 'Student'}</p>
                                      <p className="text-xs text-on-surface-variant break-words">{course ?? 'Session'}</p>
                                      <p className="text-xs text-on-surface-variant">{b.scheduledAt ? formatLocalTime(b.scheduledAt) : 'TBD'}</p>
                                      <div className="flex flex-wrap gap-2 pt-1">{renderActions(b)}</div>
                                  </div>
                              );
                          })
                        : null
                }
            >
                <table className="staff-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course / Event</th>
                            <th>Scheduled</th>
                            <th className="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length ? (
                            rows.map((b) => {
                                const name = type === 'camp' ? b.childName : b.studentName;
                                const course = type === 'camp' ? b.campName : b.courseTitle;
                                return (
                                    <tr key={b.id}>
                                        <td className="font-bold">
                                            <span className="break-words">{name ?? 'Student'}</span>
                                        </td>
                                        <td>
                                            <span className="break-words">{course ?? 'Session'}</span>
                                        </td>
                                        <td className="text-on-surface-variant">{b.scheduledAt ? formatLocalTime(b.scheduledAt) : 'TBD'}</td>
                                        <td className="text-right">{renderActions(b)}</td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan={4} className="py-6 text-center text-on-surface-variant text-sm">
                                    No classes in this section.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </DataTableShell>
        </PortalCard>
    );
}
