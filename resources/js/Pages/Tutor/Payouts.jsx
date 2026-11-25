import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import DataTableShell from '../../Components/Portal/DataTableShell';
import { formatMoney } from '../../utils/formatLocalTime';

const MONTHS = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

export default function Payouts({ payouts = [] }) {
    return (
        <DashboardLayout title="Payouts">
            <Head title="Itemized Payouts Report" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Itemized Payouts Report"
                    description="Transparent itemized payment tracking including base earnings, bonuses, and upsell commissions."
                    actions={
                        <Link href="/tutor/analytics" className="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
                            <span className="material-symbols-outlined text-sm">arrow_back</span> Back to Analytics
                        </Link>
                    }
                />

                <PortalCard>
                    <DataTableShell
                        minWidth="720px"
                        mobileCards={
                            payouts.length
                                ? payouts.map((p) => (
                                      <div key={p.id} className="staff-mobile-card space-y-2">
                                          <p className="text-sm font-extrabold">
                                              {MONTHS[p.periodMonth] ?? p.periodMonth} {p.periodYear}
                                          </p>
                                          <p className="text-xs text-on-surface-variant">
                                              Base ${formatMoney(p.baseEarnings)} · Upsell +${formatMoney(p.upsellCommission)} · Bonus +${formatMoney(p.bonus)}
                                          </p>
                                          <div className="flex items-center justify-between gap-2">
                                              <span className="text-base font-extrabold text-primary">${formatMoney(p.netPayout)}</span>
                                              <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${p.status === 'paid' ? 'bg-tertiary-fixed text-tertiary' : 'bg-warning-container text-warning'}`}>{p.status}</span>
                                          </div>
                                      </div>
                                  ))
                                : null
                        }
                    >
                        <table className="staff-table text-xs">
                            <thead>
                                <tr>
                                    <th>Pay Period</th>
                                    <th>Base Earnings</th>
                                    <th>Upsell Commission</th>
                                    <th>Bonuses</th>
                                    <th>Net Payout</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payouts.length ? (
                                    payouts.map((p) => (
                                        <tr key={p.id}>
                                            <td className="font-bold">
                                                {MONTHS[p.periodMonth] ?? p.periodMonth} {p.periodYear}
                                            </td>
                                            <td>${formatMoney(p.baseEarnings)}</td>
                                            <td className="text-on-tertiary-container font-bold">+${formatMoney(p.upsellCommission)}</td>
                                            <td className="text-on-tertiary-container font-bold">+${formatMoney(p.bonus)}</td>
                                            <td className="font-extrabold text-primary">${formatMoney(p.netPayout)}</td>
                                            <td>
                                                <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${p.status === 'paid' ? 'bg-tertiary-fixed text-tertiary' : 'bg-warning-container text-warning'}`}>{p.status}</span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="py-8 text-center text-on-surface-variant">
                                            No itemized payouts processed yet. Earnings roll up automatically at the end of each billing cycle.
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
