import { Head } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import SoftStatCard from '../../../Components/Portal/SoftStatCard';

export default function Nps({ avgNps = 0, atRiskTutors = [], roster = [] }) {
    return (
        <DashboardLayout title="Instructor NPS">
            <Head title="Instructor NPS Dashboard" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Operations"
                    title="Instructor performance & NPS"
                    description="Monitor Net Promoter Scores and early health warnings."
                    actions={
                        <div className="rounded-lg bg-primary-container text-on-primary-container px-4 py-2 text-center">
                            <p className="text-[10px] uppercase tracking-wider font-bold">Roster avg NPS</p>
                            <p className="text-xl font-extrabold tabular-nums">{avgNps} / 10</p>
                        </div>
                    }
                />

                <SoftStatCard label="Roster average NPS" value={`${avgNps} / 10`} icon="health_metrics" tone="lilac" accentBar className="md:hidden" />

                {atRiskTutors.length ? (
                    <PortalCard title={`At-risk instructors (${atRiskTutors.length})`} className="border-error/30">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {atRiskTutors.map((t, i) => (
                                <div key={i} className="p-3 bg-error-container/30 rounded-lg text-xs font-bold text-on-surface flex justify-between gap-3 min-w-0">
                                    <div className="min-w-0">
                                        <p className="text-sm break-words">{t.name}</p>
                                        <p className="text-[10px] font-normal text-on-surface-variant truncate">{t.email}</p>
                                    </div>
                                    <span className="px-2 py-0.5 rounded bg-error-container text-error shrink-0">Rating: ★ {t.rating}</span>
                                </div>
                            ))}
                        </div>
                    </PortalCard>
                ) : null}

                <PortalCard title="Instructor health roster">
                    <DataTableShell
                        minWidth="720px"
                        mobileCards={roster.map((row, i) => (
                            <div key={i} className="staff-mobile-card min-w-0 space-y-1">
                                <p className="font-bold break-words">{row.name}</p>
                                <p className="text-sm">NPS {row.npsScore}/10 · ★ {row.rating}</p>
                                <span className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${row.isAtRisk ? 'bg-error-container text-error' : 'bg-tertiary-fixed text-tertiary'}`}>
                                    {row.isAtRisk ? 'Needs support' : 'Healthy'}
                                </span>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b text-xs">
                                    <th className="font-bold">Instructor</th>
                                    <th className="font-bold">Rating</th>
                                    <th className="font-bold">NPS</th>
                                    <th className="font-bold">Punctuality</th>
                                    <th className="font-bold">Re-enrollment</th>
                                    <th className="font-bold">Health</th>
                                </tr>
                            </thead>
                            <tbody>
                                {roster.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-8 text-center text-on-surface-variant text-xs">
                                            No instructor records found.
                                        </td>
                                    </tr>
                                ) : (
                                    roster.map((row, i) => (
                                        <tr key={i} className="border-b text-xs hover:bg-surface-container-low">
                                            <td className="font-bold min-w-0 break-words">{row.name}</td>
                                            <td className="font-bold text-warning">★ {row.rating}</td>
                                            <td className="font-bold">{row.npsScore} / 10</td>
                                            <td>{row.punctualityRate}%</td>
                                            <td>{row.reEnrollmentRate}%</td>
                                            <td>
                                                <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${row.isAtRisk ? 'bg-error-container text-error' : 'bg-tertiary-fixed text-tertiary'}`}>
                                                    {row.isAtRisk ? 'Needs Support' : 'Healthy'}
                                                </span>
                                            </td>
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
