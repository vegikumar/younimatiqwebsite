import { Head, router } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import SoftStatCard from '../../../Components/Portal/SoftStatCard';

const PERIODS = [
    ['7', 'Last 7 days'],
    ['30', 'Last 30 days'],
    ['90', 'Last 90 days'],
    ['365', 'Last year'],
];

export default function Index({
    revenue = 0,
    enrollments = 0,
    campLeads = 0,
    newStudents = 0,
    revenueByGateway = [],
    topCourses = [],
    funnel = [],
    period = '30',
}) {
    const revenueFormatted = `$${Number(revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    return (
        <DashboardLayout title="Reports">
            <Head title="Reports & Analysis" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Analytics"
                    title="Reports & analysis"
                    description="Revenue and enrollment insights for the selected period."
                />

                <PortalCard title="Time period">
                    <form
                        method="GET"
                        action="/admin/reports"
                        className="flex flex-wrap gap-4 items-end"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const fd = new FormData(e.currentTarget);
                            router.get('/admin/reports', { period: fd.get('period') }, { preserveState: true });
                        }}
                    >
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-2">Time Period</label>
                            <select name="period" defaultValue={String(period)} className="p-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none">
                                {PERIODS.map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <button type="submit" className="btn-primary btn-sm rounded-lg">
                            Update Report
                        </button>
                    </form>
                </PortalCard>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <SoftStatCard label="Revenue" value={revenueFormatted} icon="payments" tone="lilac" accentBar />
                    <SoftStatCard label="New Enrollments" value={enrollments} icon="school" tone="mint" accentBar />
                    <SoftStatCard label="Consultation & Trial Bookings" value={campLeads} icon="event" tone="sky" accentBar />
                    <SoftStatCard label="New Students" value={newStudents} icon="person_add" tone="peach" accentBar />
                </div>

                {funnel.length > 0 ? (
                    <PortalCard
                        title="Growth funnel"
                        description="Conversion through key stages"
                    >
                        <div className="space-y-3">
                            {funnel.map((step) => (
                                <div key={step.key} className="grid grid-cols-[1fr_auto_auto] gap-3 items-center p-3 rounded-lg bg-surface-container-low min-w-0">
                                    <div className="min-w-0">
                                        <p className="font-bold text-sm break-words">{step.label}</p>
                                        <div className="mt-2 h-1.5 rounded-full bg-surface-container-high overflow-hidden">
                                            <div className="h-full bg-primary" style={{ width: `${Math.min(100, step.rate || 0)}%` }} />
                                        </div>
                                    </div>
                                    <span className="font-bold tabular-nums">{step.count}</span>
                                    <span className="text-xs text-on-surface-variant w-14 text-right">{step.rate}%</span>
                                </div>
                            ))}
                        </div>
                    </PortalCard>
                ) : null}

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <PortalCard title="Revenue by Gateway">
                        <div className="space-y-4">
                            {revenueByGateway.length === 0 ? (
                                <p className="text-on-surface-variant text-center py-6">No payment data available.</p>
                            ) : (
                                revenueByGateway.map((row, i) => (
                                    <div key={i} className="flex items-center justify-between p-4 bg-surface-container-low rounded-lg min-w-0 gap-3">
                                        <span className="font-bold capitalize break-words">{row.gateway || 'Unknown'}</span>
                                        <span className="text-primary font-bold shrink-0">${Number(row.total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </PortalCard>

                    <PortalCard title="Top Courses by Enrollment">
                        <div className="space-y-3">
                            {topCourses.length === 0 ? (
                                <p className="text-on-surface-variant text-center py-6">No enrollment data yet.</p>
                            ) : (
                                topCourses.map((course, index) => (
                                    <div key={course.id} className="flex items-center gap-4 p-4 bg-surface-container-low rounded-lg min-w-0">
                                        <span className="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm shrink-0">{index + 1}</span>
                                        <div className="flex-1 min-w-0">
                                            <p className="font-bold break-words">{course.title}</p>
                                            <p className="text-sm text-on-surface-variant">{course.enrollments_count} enrollments</p>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </PortalCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
