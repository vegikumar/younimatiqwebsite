import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Sessions({ sessions = [], courses = [], storeUrl = '/tutor/sessions' }) {
    const { csrfToken, errors } = usePage().props;
    const fieldErrors = errors || {};

    return (
        <DashboardLayout title="Live Sessions">
            <Head title="Live Sessions" />
            <div className="staff-page">
                <PageHeader variant="staff" title="Schedule Live Sessions" description="Create class meetings for your enrolled students." />

                {Object.keys(fieldErrors).length ? (
                    <div className="p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                        <ul className="list-disc list-inside">
                            {Object.values(fieldErrors).flat().map((error, i) => (
                                <li key={i}>{error}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:gap-6">
                    <PortalCard title="New Session">
                        <form method="POST" action={storeUrl} className="space-y-4 min-w-0">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-2">Course</label>
                                <select name="course_id" required className="w-full min-w-0 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                    <option value="">Select course</option>
                                    {courses.map((course) => (
                                        <option key={course.id} value={course.id}>
                                            {course.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-2">Start Time</label>
                                <input type="datetime-local" name="start_time" required className="w-full min-w-0 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="min-w-0">
                                    <label className="block text-sm font-bold mb-2">Duration (min)</label>
                                    <input type="number" name="duration_minutes" defaultValue={60} min={15} max={180} required className="w-full min-w-0 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                </div>
                                <div className="min-w-0">
                                    <label className="block text-sm font-bold mb-2">Max Students</label>
                                    <input type="number" name="max_students" defaultValue={10} min={1} max={50} className="w-full min-w-0 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                </div>
                            </div>
                            <button type="submit" className="btn-primary btn-block">
                                Schedule Session
                            </button>
                        </form>
                    </PortalCard>

                    <div className="lg:col-span-2 min-w-0">
                        <PortalCard title="Your Sessions">
                            <DataTableShell
                                mobileCards={
                                    sessions.length
                                        ? sessions.map((session) => (
                                              <div key={session.id} className="staff-mobile-card space-y-2">
                                                  <p className="text-sm font-extrabold break-words">{session.courseTitle}</p>
                                                  <p className="text-xs text-on-surface-variant">{formatLocalTime(session.startTime)}</p>
                                                  <p className="text-xs text-on-surface-variant">{session.durationMinutes} min</p>
                                                  <span className={`inline-block px-3 py-1 rounded-full text-xs font-bold ${session.status === 'scheduled' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>
                                                      {session.status ? session.status.charAt(0).toUpperCase() + session.status.slice(1) : '—'}
                                                  </span>
                                                  {session.startUrl ? (
                                                      <a href={session.startUrl} target="_blank" rel="noreferrer" className="text-primary font-bold text-sm hover:underline">
                                                          Start (Host)
                                                      </a>
                                                  ) : (
                                                      <span className="text-on-surface-variant text-sm">—</span>
                                                  )}
                                              </div>
                                          ))
                                        : null
                                }
                            >
                                <table className="staff-table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Start Time</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Join</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sessions.length ? (
                                            sessions.map((session) => (
                                                <tr key={session.id}>
                                                    <td className="font-medium">
                                                        <span className="break-words">{session.courseTitle}</span>
                                                    </td>
                                                    <td className="text-sm">{formatLocalTime(session.startTime)}</td>
                                                    <td className="text-sm">{session.durationMinutes} min</td>
                                                    <td>
                                                        <span className={`px-3 py-1 rounded-full text-xs font-bold ${session.status === 'scheduled' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>
                                                            {session.status ? session.status.charAt(0).toUpperCase() + session.status.slice(1) : '—'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        {session.startUrl ? (
                                                            <a href={session.startUrl} target="_blank" rel="noreferrer" className="text-primary font-bold text-sm hover:underline">
                                                                Start (Host)
                                                            </a>
                                                        ) : (
                                                            <span className="text-on-surface-variant text-sm">—</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={5} className="py-8 text-center text-on-surface-variant">
                                                    No sessions scheduled yet.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </DataTableShell>
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
