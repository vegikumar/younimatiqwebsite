import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';
import EmptyState from '../../Components/Portal/EmptyState';
import UnslopStat from '../../Components/Portal/UnslopStat';
import DataTableShell from '../../Components/Portal/DataTableShell';

export default function Dashboard({
    metrics = {},
    dateFilters = {},
    recentUsers = [],
    recentEnrollments = [],
    trend = { labels: [], revenue: [], enrollments: [] },
    categoryMix = {},
    upcomingBookings = [],
}) {
    const maxRevenue = Math.max(1, ...(trend.revenue?.length ? trend.revenue : [1]));
    const mixTotal = Object.values(categoryMix).reduce((a, b) => a + Number(b), 0) || 1;
    const revenueSum = (trend.revenue || []).reduce((a, b) => a + Number(b), 0);
    const monthRev = Number(metrics.month_revenue || 0);
    const totalRev = Number(metrics.total_revenue || 0);
    const periodLabel = dateFilters?.to
        ? new Date(`${dateFilters.to}T12:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
        : new Date().toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const lastIdx = Math.max(0, (trend.revenue || []).length - 1);

    return (
        <DashboardLayout title="Dashboard">
            <Head title="Admin Dashboard" />
            <div className="staff-page unslop-dash">
                <div className="unslop-top">
                    <div>
                        <h1>{periodLabel}</h1>
                        <p>Revenue, people, and live sessions for daily decisions</p>
                    </div>
                    <div className="unslop-actions">
                        <Link href="/admin/students" className="unslop-btn unslop-btn--primary">
                            <Icon name="group" className="text-base" /> Students
                        </Link>
                        <Link href="/admin/tutor-training" className="unslop-btn">
                            <Icon name="school" className="text-base" /> Tutor Training
                        </Link>
                        <Link href="/admin/orders" className="unslop-btn">
                            <Icon name="payments" className="text-base" /> Orders
                        </Link>
                    </div>
                </div>

                <form
                    className="unslop-filters"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const fd = new FormData(e.currentTarget);
                        router.get(
                            '/admin/dashboard',
                            {
                                from: fd.get('from') || undefined,
                                to: fd.get('to') || undefined,
                            },
                            { preserveState: true }
                        );
                    }}
                >
                    <label>
                        From
                        <input type="date" name="from" defaultValue={dateFilters.from || ''} />
                    </label>
                    <label>
                        To
                        <input type="date" name="to" defaultValue={dateFilters.to || ''} />
                    </label>
                    <button type="submit" className="unslop-btn unslop-btn--primary">
                        Apply
                    </button>
                    {metrics.filtered ? (
                        <Link href="/admin/dashboard" className="unslop-link">
                            Clear
                        </Link>
                    ) : null}
                </form>

                <div className="unslop-stats unslop-stats--5">
                    <UnslopStat
                        label="Total revenue"
                        value={`$${totalRev.toLocaleString(undefined, { maximumFractionDigits: 0 })}`}
                        sub={`$${monthRev.toLocaleString(undefined, { maximumFractionDigits: 0 })} this month`}
                    />
                    <UnslopStat
                        label="Enrollments"
                        value={Number(metrics.total_enrollments || 0).toLocaleString()}
                        delta={`+${metrics.month_enrollments || 0} this month`}
                    />
                    <UnslopStat
                        label="Active students"
                        value={Number(metrics.active_students || 0).toLocaleString()}
                        sub={`${metrics.active_tutors || 0} tutors online`}
                    />
                    <UnslopStat
                        label="Upcoming sessions"
                        value={Number(metrics.upcoming_sessions || 0).toLocaleString()}
                        sub="Next window"
                    />
                    <UnslopStat
                        label="14-day revenue"
                        value={`$${revenueSum.toLocaleString(undefined, { maximumFractionDigits: 0 })}`}
                        className="col-span-2 xl:col-span-1"
                    />
                </div>

                <div className="unslop-grid unslop-grid--main">
                    <section className="staff-card p-5">
                        <div className="unslop-card-head">
                            <div>
                                <h2>Revenue trend</h2>
                                <p>Last 14 days · completed payments</p>
                            </div>
                            <Link href="/admin/orders" className="unslop-mono unslop-link">
                                ${revenueSum.toLocaleString(undefined, { maximumFractionDigits: 0 })}
                            </Link>
                        </div>
                        <div className="unslop-chart" role="img" aria-label="Revenue over the last 14 days">
                            {(trend.revenue || []).map((val, i) => {
                                const h = Math.max(8, Math.round((Number(val) / maxRevenue) * 100));
                                return (
                                    <div key={i} className="unslop-chart__col" title={`${trend.labels?.[i] || ''} · $${Number(val).toFixed(2)}`}>
                                        <div className={`unslop-chart__bar ${i === lastIdx ? 'is-on' : ''}`} style={{ height: `${h}%` }} />
                                        <div className="unslop-chart__label">{trend.labels?.[i] || i + 1}</div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section className="staff-card p-5">
                        <div className="unslop-card-head">
                            <div>
                                <h2>Enrollment mix</h2>
                                <p>By category</p>
                            </div>
                        </div>
                        {Object.keys(categoryMix).length === 0 ? (
                            <EmptyState icon="category" title="No category data" description="Enrollments will populate this mix." />
                        ) : (
                            <div className="unslop-mix">
                                {Object.entries(categoryMix).map(([name, count]) => {
                                    const pct = Math.round((Number(count) / mixTotal) * 100);
                                    return (
                                        <div key={name}>
                                            <div className="unslop-mix__top">
                                                <span>{name}</span>
                                                <span>{pct}%</span>
                                            </div>
                                            <div className="unslop-mix__bar">
                                                <i style={{ width: `${pct}%` }} />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                </div>

                {upcomingBookings.length > 0 ? (
                    <section className="staff-card p-5">
                        <div className="unslop-card-head">
                            <div>
                                <h2>Upcoming sessions</h2>
                                <p>Next live classes with assigned tutors</p>
                            </div>
                            <Link href="/admin/enrollments" className="unslop-link">
                                View all
                            </Link>
                        </div>
                        <div className="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                            {upcomingBookings.map((b) => (
                                <div key={b.id} className="unslop-row">
                                    <div>
                                        <strong>{b.student_name || 'Student'}</strong>
                                        <span>
                                            {[
                                                b.course_title || 'Class',
                                                b.tutor_name ? `Tutor ${b.tutor_name}` : null,
                                                b.scheduled_human || null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                ) : null}

                <div className="unslop-grid unslop-grid--main">
                    <section className="staff-card p-5">
                        <div className="unslop-card-head">
                            <div>
                                <h2>Recent enrollments</h2>
                                <p>Latest completed checkouts</p>
                            </div>
                            <Link href="/admin/enrollments" className="unslop-link">
                                View all
                            </Link>
                        </div>
                        {recentEnrollments.length === 0 ? (
                            <EmptyState icon="school" title="No enrollments yet" description="New course purchases will show here." />
                        ) : (
                            <DataTableShell
                                mobileCards={recentEnrollments.map((row) => (
                                    <div key={row.id} className="staff-mobile-card">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="text-sm font-semibold break-words">{row.student_name || 'Unknown'}</p>
                                            <span className="unslop-status">
                                                <i /> {row.status}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-xs text-[var(--u-muted)] break-words">{row.course_title || '—'}</p>
                                    </div>
                                ))}
                            >
                                <table className="staff-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentEnrollments.map((row) => (
                                            <tr key={row.id}>
                                                <td>
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <div className="unslop-avatar">{(row.student_name || 'S').charAt(0).toUpperCase()}</div>
                                                        <span className="truncate font-medium">{row.student_name || 'Unknown'}</span>
                                                    </div>
                                                </td>
                                                <td className="text-[var(--u-secondary)]">
                                                    <span className="line-clamp-2 max-w-[16rem]">{row.course_title || '—'}</span>
                                                </td>
                                                <td>
                                                    <span className="unslop-status">
                                                        <i /> {row.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </DataTableShell>
                        )}
                    </section>

                    <section className="staff-card p-5">
                        <div className="unslop-card-head">
                            <div>
                                <h2>Recent users</h2>
                                <p>Newest students and tutors</p>
                            </div>
                            <Link href="/admin/students" className="unslop-link">
                                View all
                            </Link>
                        </div>
                        {recentUsers.length === 0 ? (
                            <EmptyState icon="group" title="No users yet" description="New students and tutors will appear here." />
                        ) : (
                            <div className="space-y-2">
                                {recentUsers.map((user) => (
                                    <div key={user.id} className="unslop-row">
                                        <div>
                                            <strong>{user.name}</strong>
                                            <span>{user.email}</span>
                                        </div>
                                        <span className="unslop-chip">{user.role}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>

                <section className="staff-card p-5">
                    <div className="unslop-card-head">
                        <div>
                            <h2>Operations shortcuts</h2>
                        </div>
                    </div>
                    <div className="unslop-shortcuts">
                        {[
                            { href: '/admin/enrollments', icon: 'how_to_reg', label: 'Enrollments' },
                            { href: '/admin/courses', icon: 'menu_book', label: 'Courses' },
                            { href: '/admin/bundles', icon: 'package_2', label: 'Bundles' },
                            { href: '/admin/platform-settings', icon: 'settings', label: 'Settings' },
                        ].map((item) => (
                            <Link key={item.href} href={item.href}>
                                <Icon name={item.icon} className="icon" />
                                <span className="truncate">{item.label}</span>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </DashboardLayout>
    );
}
