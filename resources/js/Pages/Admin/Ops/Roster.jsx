import { Head } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import SoftStatCard from '../../../Components/Portal/SoftStatCard';

export default function Roster({ leaderboard = [], rosterData = [] }) {
    return (
        <DashboardLayout title="Teacher Roster">
            <Head title="Teacher Roster & Leaderboard" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Operations"
                    title="Teacher roster & leaderboard"
                    description="View instructor ratings, enrollments, punctuality, and ranking."
                />

                {leaderboard.length > 0 ? (
                    <PortalCard title="Teacher performance leaderboard" description="Top instructors by rating and renewals">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {leaderboard.map((item, index) => (
                                <SoftStatCard
                                    key={index}
                                    label={`#${index + 1} · ${item.name}`}
                                    value={`★ ${item.rating}`}
                                    sub={`Renewals ${item.reEnrollmentRate}% · ${item.email}`}
                                    icon="emoji_events"
                                    tone={index === 0 ? 'peach' : index === 1 ? 'sky' : 'lilac'}
                                    accentBar
                                    className="relative"
                                />
                            ))}
                        </div>
                    </PortalCard>
                ) : null}

                <PortalCard title="Full instructor operations roster">
                    <DataTableShell
                        minWidth="800px"
                        mobileCards={rosterData.map((row, i) => (
                            <div key={i} className="staff-mobile-card min-w-0 space-y-1">
                                <p className="font-bold break-words">{row.name}</p>
                                <p className="text-xs text-on-surface-variant truncate">{row.email}</p>
                                <p className="text-sm">★ {row.rating} · {row.learnersTaught} learners</p>
                                <p className="text-xs text-primary font-bold">${row.monthlyEarnings} / mo</p>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b text-xs">
                                    <th className="font-bold">Instructor</th>
                                    <th className="font-bold">Avg Rating</th>
                                    <th className="font-bold">Sections</th>
                                    <th className="font-bold">Learners</th>
                                    <th className="font-bold">Punctuality</th>
                                    <th className="font-bold">Re-enrollment</th>
                                    <th className="font-bold">Monthly Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rosterData.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 text-center text-xs text-on-surface-variant">
                                            No instructor records found.
                                        </td>
                                    </tr>
                                ) : (
                                    rosterData.map((row, i) => (
                                        <tr key={i} className="border-b text-xs hover:bg-surface-container-low">
                                            <td className="font-bold min-w-0">
                                                <span className="break-words">{row.name}</span>
                                                <p className="text-[10px] font-normal text-on-surface-variant truncate">{row.email}</p>
                                            </td>
                                            <td className="text-warning font-bold">★ {row.rating}</td>
                                            <td>{row.sectionsCount} sections</td>
                                            <td className="font-bold">{row.learnersTaught}</td>
                                            <td>{row.punctualityRate}%</td>
                                            <td>{row.reEnrollmentRate}%</td>
                                            <td className="font-extrabold text-primary">${row.monthlyEarnings}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
