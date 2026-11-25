import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import { formatMoney } from '../../utils/formatLocalTime';

export default function Analytics({ metrics = {} }) {
    return (
        <DashboardLayout title="Analytics">
            <Head title="Analytics & Insights" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Analytics & Business Insights"
                    description="Track earnings, learners taught, hours worked, punctuality, class scores, and re-enrollment stickiness."
                    actions={
                        <Link href="/tutor/payouts" className="btn-primary py-2.5 px-5 text-xs font-bold flex items-center gap-1 shrink-0">
                            <span className="material-symbols-outlined text-sm">receipt_long</span> Itemized Payouts Report
                        </Link>
                    }
                />
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <MetricCard label="Monthly Earnings" value={`$${formatMoney(metrics.monthlyEarnings)}`} sub={`Yearly: $${formatMoney(metrics.yearlyEarnings)}`} border="border-primary" />
                    <MetricCard label="Learners Taught" value={metrics.learnersTaught ?? 0} sub="Unique enrolled students" border="border-secondary" />
                    <MetricCard label="Hours Worked" value={`${metrics.hoursWorked ?? 0} hrs`} sub="Committed time slots" border="border-accent" />
                    <MetricCard label="Re-enrollment Stickiness" value={`${metrics.reEnrollmentRate ?? 0}%`} sub="Brand stickiness indicator" border="border-tertiary" valueClass="text-on-tertiary-container" />
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:gap-6">
                    <PortalCard title="Class Score">
                        <div className="flex items-center gap-4 p-4 bg-surface-container-low rounded-2xl min-w-0">
                            <div className="text-4xl font-extrabold text-warning shrink-0">{metrics.classScore}</div>
                            <div className="min-w-0">
                                <div className="flex text-warning text-sm">★★★★★</div>
                                <p className="text-xs font-bold text-on-surface-variant">Admin-managed quality score</p>
                            </div>
                        </div>
                    </PortalCard>
                    <PortalCard title="Punctuality">
                        <div className="space-y-2">
                            <Row label="Punctuality Score" value={`${metrics.punctualityRate}%`} />
                            <Row label="Committed Time Slots" value={`${metrics.committedSlots} slots`} />
                        </div>
                    </PortalCard>
                    <PortalCard title="Cancellations">
                        <div className="space-y-2">
                            <Row label="Class Cancellations" value={metrics.cancellations} valueClass="text-error" />
                            <Row label="Student Withdrawals" value={metrics.withdrawals} valueClass="text-error" />
                        </div>
                    </PortalCard>
                </div>
            </div>
        </DashboardLayout>
    );
}

function MetricCard({ label, value, sub, border, valueClass = 'text-primary' }) {
    return (
        <PortalCard>
            <div className={`border-l-4 ${border} pl-1 min-w-0`}>
                <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1 break-words">{label}</p>
                <h3 className={`text-3xl font-extrabold ${valueClass} break-words`}>{value}</h3>
                <p className="text-[10px] text-on-surface-variant mt-1 break-words">{sub}</p>
            </div>
        </PortalCard>
    );
}

function Row({ label, value, valueClass = 'text-on-tertiary-container' }) {
    return (
        <div className="flex justify-between items-center gap-2 text-xs p-3 bg-surface-container-low rounded-xl min-w-0">
            <span className="font-bold break-words">{label}</span>
            <span className={`font-extrabold shrink-0 ${valueClass}`}>{value}</span>
        </div>
    );
}
