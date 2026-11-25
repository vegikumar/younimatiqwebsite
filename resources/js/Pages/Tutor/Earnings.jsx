import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import { formatLocalTime, formatMoney } from '../../utils/formatLocalTime';

export default function Earnings({ payments = [], totalEarnings = 0 }) {
    return (
        <DashboardLayout title="My Earnings">
            <Head title="My Earnings" />
            <div className="staff-page">
                <PageHeader variant="staff" title="My Earnings" description="Track sales and your share from course enrollments." />

                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <PortalCard padding className="border-l-4 border-l-tertiary">
                        <h3 className="text-xs font-extrabold uppercase tracking-wide text-on-surface-variant mb-2">Total Estimated Earnings</h3>
                        <div className="text-3xl font-extrabold text-tertiary">${formatMoney(totalEarnings)}</div>
                    </PortalCard>
                    <PortalCard>
                        <h3 className="text-xs font-extrabold uppercase tracking-wide text-on-surface-variant mb-2">Total Sales</h3>
                        <div className="text-3xl font-extrabold text-on-surface">{payments.length}</div>
                    </PortalCard>
                    <PortalCard>
                        <h3 className="text-xs font-extrabold uppercase tracking-wide text-on-surface-variant mb-2">Revenue Share</h3>
                        <div className="text-3xl font-extrabold text-on-surface">80%</div>
                    </PortalCard>
                </div>

                <PortalCard title="Recent Transactions">
                    <DataTableShell
                        mobileCards={
                            payments.length
                                ? payments.map((p) => (
                                      <div key={p.id} className="staff-mobile-card">
                                          <p className="text-xs text-on-surface-variant">{formatLocalTime(p.createdAt, 'date')}</p>
                                          <p className="mt-1 text-sm font-extrabold break-words">{p.courseTitle ?? 'Unknown Course'}</p>
                                          <p className="text-xs text-on-surface-variant break-words">{p.studentName ?? 'Unknown Student'}</p>
                                          <div className="mt-2 flex justify-between gap-2 text-sm">
                                              <span>${formatMoney(p.amount)}</span>
                                              <span className="font-extrabold text-tertiary">+${formatMoney(p.amount * 0.8)}</span>
                                          </div>
                                      </div>
                                  ))
                                : null
                        }
                    >
                        <table className="staff-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Student</th>
                                    <th className="text-right">Amount</th>
                                    <th className="text-right">Your Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payments.length ? (
                                    payments.map((p) => (
                                        <tr key={p.id}>
                                            <td className="text-on-surface-variant">{formatLocalTime(p.createdAt, 'date')}</td>
                                            <td>
                                                <span className="break-words">{p.courseTitle ?? 'Unknown Course'}</span>
                                            </td>
                                            <td>
                                                <span className="break-words">{p.studentName ?? 'Unknown Student'}</span>
                                            </td>
                                            <td className="text-right font-medium">${formatMoney(p.amount)}</td>
                                            <td className="text-right text-tertiary font-extrabold">+${formatMoney(p.amount * 0.8)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="py-12 text-center text-on-surface-variant">
                                            <span className="material-symbols-outlined text-4xl mb-2 block">payments</span>
                                            <p>No transactions found yet. Keep building great courses!</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
