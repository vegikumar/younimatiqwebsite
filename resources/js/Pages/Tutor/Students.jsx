import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import RewardModal, { openRewardModal } from '../../Components/Tutor/RewardModal';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Students({ enrollments = [], bookings = [], rewardPresets = [], badgeCatalog = [] }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="My Students">
            <Head title="My Students" />
            <RewardModal presets={rewardPresets} badges={badgeCatalog} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="My Students"
                    description="View enrollments and upload completion certificates."
                />
                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl">{flash.success}</div> : null}
                {flash?.error ? <div className="p-4 bg-error-container text-error rounded-xl">{flash.error}</div> : null}

                <PortalCard title="Enrollments & Certificates">
                    <DataTableShell
                        minWidth="960px"
                        mobileCards={
                            enrollments.length
                                ? enrollments.map((e) => (
                                      <div key={e.id} className="staff-mobile-card space-y-3">
                                          <p className="text-sm font-extrabold break-words">{e.studentName}</p>
                                          <p className="text-xs text-on-surface-variant break-words">{e.courseTitle}</p>
                                          <span className="inline-block px-3 py-1 bg-primary-container rounded-full text-[10px] font-bold uppercase">{e.status}</span>
                                          <form method="POST" action={`/tutor/enrollments/${e.id}/certificate`} encType="multipart/form-data" className="flex flex-wrap gap-2 items-center">
                                              <input type="hidden" name="_token" value={csrfToken} />
                                              <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required className="text-xs max-w-full min-w-0" />
                                              <button type="submit" className="btn-primary btn-sm text-xs">
                                                  Upload
                                              </button>
                                          </form>
                                          {e.hasCertificate ? <span className="block text-[10px] font-bold text-tertiary">Uploaded</span> : null}
                                          {e.userId ? (
                                              <button type="button" className="text-xs font-bold text-primary" onClick={() => openRewardModal({ id: e.userId, name: e.studentName, courseId: e.courseId })}>
                                                  Reward
                                              </button>
                                          ) : null}
                                      </div>
                                  ))
                                : null
                        }
                    >
                        <table className="staff-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Certificate</th>
                                </tr>
                            </thead>
                            <tbody>
                                {enrollments.map((e) => (
                                    <tr key={e.id}>
                                        <td className="font-bold">
                                            <span className="break-words">{e.studentName}</span>
                                        </td>
                                        <td>
                                            <span className="break-words">{e.courseTitle}</span>
                                        </td>
                                        <td>
                                            <span className="px-3 py-1 bg-primary-container rounded-full text-[10px] font-bold uppercase">{e.status}</span>
                                        </td>
                                        <td className="staff-ops-cell">
                                            <div className="staff-ops-stack">
                                                <form method="POST" action={`/tutor/enrollments/${e.id}/certificate`} encType="multipart/form-data" className="staff-assign-row">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required className="text-xs max-w-[160px] min-w-0" />
                                                    <button type="submit" className="staff-ops-btn">
                                                        Upload
                                                    </button>
                                                </form>
                                                {e.hasCertificate ? <span className="staff-ops-meta staff-ops-meta--ok">Uploaded</span> : null}
                                                {e.userId ? (
                                                    <button type="button" className="staff-ops-link" onClick={() => openRewardModal({ id: e.userId, name: e.studentName, courseId: e.courseId })}>
                                                        Reward
                                                    </button>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {!enrollments.length ? (
                                    <tr>
                                        <td colSpan={4} className="py-8 text-center text-on-surface-variant">
                                            No enrollments found for your courses yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>

                <PortalCard title="Live Bookings">
                    <DataTableShell
                        mobileCards={
                            bookings.length
                                ? bookings.map((b) => (
                                      <div key={b.id} className="staff-mobile-card">
                                          <p className="text-sm font-extrabold break-words">{b.studentName}</p>
                                          <p className="mt-1 text-xs text-on-surface-variant break-words">{b.courseTitle}</p>
                                          <p className="mt-1 text-xs text-on-surface-variant">{b.scheduledAt ? formatLocalTime(b.scheduledAt) : 'TBD'}</p>
                                      </div>
                                  ))
                                : null
                        }
                    >
                        <table className="staff-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Scheduled</th>
                                </tr>
                            </thead>
                            <tbody>
                                {bookings.map((b) => (
                                    <tr key={b.id}>
                                        <td>
                                            <span className="break-words">{b.studentName}</span>
                                        </td>
                                        <td>
                                            <span className="break-words">{b.courseTitle}</span>
                                        </td>
                                        <td className="text-on-surface-variant">{b.scheduledAt ? formatLocalTime(b.scheduledAt) : 'TBD'}</td>
                                    </tr>
                                ))}
                                {!bookings.length ? (
                                    <tr>
                                        <td colSpan={3} className="py-8 text-center text-on-surface-variant">
                                            No bookings yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
