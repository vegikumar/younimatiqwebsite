import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';
import PaginationLinks from '../../../Components/PaginationLinks';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import SoftStatCard from '../../../Components/Portal/SoftStatCard';
import EmptyState from '../../../Components/Portal/EmptyState';
import ConfirmDialog from '../../../Components/Portal/ConfirmDialog';

export default function Index({
    revenueGrouped = [],
    ordersCount = 0,
    campBookingsCount = 0,
    exportUrl,
    payments = { data: [], links: [] },
    orders = [],
    campBookings = [],
}) {
    const { csrfToken, flash } = usePage().props;
    const paymentRows = payments.data || [];
    const [refundTarget, setRefundTarget] = useState(null);
    const [refundBusy, setRefundBusy] = useState(false);

    return (
        <DashboardLayout title="Orders & Sales">
            <Head title="Orders & Sales" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Commerce"
                    title="Orders & sales"
                    description="Payments, orders, and consultation / trial bookings overview."
                    actions={
                        <Link href={exportUrl} className="btn-primary btn-sm inline-flex items-center gap-2 rounded-lg">
                            <Icon name="download" className="text-sm" /> Export CSV
                        </Link>
                    }
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold text-sm">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container text-error font-bold text-sm">{flash.error}</div> : null}

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <SoftStatCard
                        label="Total revenue"
                        value={revenueGrouped.length ? revenueGrouped.map((r) => r.formatted).join(' · ') : '$0.00'}
                        icon="payments"
                        tone="lilac"
                        accentBar
                    />
                    <SoftStatCard label="Recent orders" value={ordersCount} icon="receipt_long" tone="sky" accentBar />
                    <SoftStatCard label="Consultations & trials" value={campBookingsCount} icon="school" tone="mint" accentBar />
                </div>

                <div className="space-y-6">
                    <PortalCard title="Payments" description="Completed and pending gateway payments">
                        {!paymentRows.length ? (
                            <EmptyState title="No payments yet" description="Completed and pending payments will show here." />
                        ) : (
                            <>
                                <DataTableShell
                                    minWidth="1100px"
                                    mobileCards={paymentRows.map((p) => (
                                        <div key={p.id} className="staff-mobile-card min-w-0 space-y-2">
                                            <p className="font-bold break-words">{p.studentName}</p>
                                            <p className="text-xs text-on-surface-variant break-words">{p.courseTitle}</p>
                                            <p className="text-sm font-bold">{p.amountFormatted}</p>
                                            <p className="text-xs text-on-surface-variant">{p.status} · {p.createdAtLabel}</p>
                                            {p.status === 'completed' ? (
                                                <button type="button" className="text-xs font-bold text-error" onClick={() => setRefundTarget(p)}>Refund</button>
                                            ) : null}
                                        </div>
                                    ))}
                                >
                                    <table className="staff-table w-full text-left border-collapse">
                                        <thead>
                                            <tr className="border-b-2 border-outline-variant">
                                                {['Student', 'Course', 'Amount', 'Currency', 'Gateway', 'Status', 'Paid at', 'Actions'].map((h) => (
                                                    <th key={h} className="py-3 px-4 font-bold">
                                                        {h}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {paymentRows.map((p) => (
                                                <tr key={p.id} className="border-b border-outline-variant hover:bg-surface-container-low">
                                                    <td className="sticky left-0 bg-white min-w-0"><span className="break-words">{p.studentName}</span></td>
                                                    <td className="min-w-0"><span className="break-words line-clamp-2">{p.courseTitle}</span></td>
                                                    <td className="font-bold">{p.amountFormatted}</td>
                                                    <td>
                                                        <span className="px-2 py-0.5 rounded text-xs font-mono bg-surface-container">{p.currency}</span>
                                                    </td>
                                                    <td className="text-on-surface-variant">{p.gateway}</td>
                                                    <td>
                                                        <span
                                                            className={`px-3 py-1 rounded-full text-xs font-bold ${
                                                                p.status === 'completed'
                                                                    ? 'bg-secondary-container'
                                                                    : p.status === 'failed'
                                                                      ? 'bg-error-container text-on-error-container'
                                                                      : 'bg-surface-container'
                                                            }`}
                                                        >
                                                            {p.status}
                                                        </span>
                                                    </td>
                                                    <td className="text-sm text-on-surface-variant">{p.createdAtLabel}</td>
                                                    <td>
                                                        {p.status === 'completed' ? (
                                                            <button
                                                                type="button"
                                                                className="text-xs font-bold text-error hover:underline"
                                                                onClick={() => setRefundTarget(p)}
                                                            >
                                                                Refund
                                                            </button>
                                                        ) : null}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </DataTableShell>
                                <div className="mt-4">
                                    <PaginationLinks links={payments.links} />
                                </div>
                            </>
                        )}
                    </PortalCard>

                    <PortalCard title="Orders" description="Course checkout orders">
                        {!orders.length ? (
                            <EmptyState title="No orders yet" description="Course orders will appear after checkout." />
                        ) : (
                            <DataTableShell
                                minWidth="1100px"
                                mobileCards={orders.map((o, i) => (
                                    <div key={i} className="staff-mobile-card min-w-0 space-y-1">
                                        <p className="font-mono text-sm font-bold">#{o.orderNumber}</p>
                                        <p className="break-words">{o.userName}</p>
                                        <p className="text-xs text-on-surface-variant break-words">{o.courseTitle}</p>
                                        <p className="text-sm font-bold">{o.amountFormatted} · {o.status}</p>
                                    </div>
                                ))}
                            >
                                <table className="staff-table w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b-2 border-outline-variant">
                                            {['Order #', 'User', 'Course', 'Amount', 'Currency', 'Method', 'Status', 'Date & time'].map((h) => (
                                                <th key={h} className="py-3 px-4 font-bold">
                                                    {h}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {orders.map((o, i) => (
                                            <tr key={i} className="border-b hover:bg-surface-container-low">
                                                <td className="font-mono text-sm">#{o.orderNumber}</td>
                                                <td className="min-w-0"><span className="break-words">{o.userName}</span></td>
                                                <td className="min-w-0"><span className="break-words line-clamp-2">{o.courseTitle}</span></td>
                                                <td className="font-bold">{o.amountFormatted}</td>
                                                <td>
                                                    <span className="px-2 py-0.5 rounded text-xs font-mono bg-surface-container">{o.currency}</span>
                                                </td>
                                                <td className="text-sm">{o.paymentMethod}</td>
                                                <td>
                                                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-primary-container">{o.status}</span>
                                                </td>
                                                <td className="text-sm text-on-surface-variant">{o.createdAtLabel}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </DataTableShell>
                        )}
                    </PortalCard>

                    <PortalCard title="Consultation & trial bookings" description="Camp and trial registrations">
                        {!campBookings.length ? (
                            <EmptyState title="No bookings yet" description="Camp and trial bookings will show here." />
                        ) : (
                            <DataTableShell
                                minWidth="1200px"
                                mobileCards={campBookings.map((b, i) => (
                                    <div key={i} className="staff-mobile-card min-w-0 space-y-1">
                                        <p className="font-medium break-words">{b.campName}</p>
                                        <p className="text-sm break-words">{b.childName} · {b.parentName}</p>
                                        <p className="text-xs text-on-surface-variant truncate">{b.email}</p>
                                        <p className="text-xs">{b.paymentStatus} · {b.createdAtLabel}</p>
                                    </div>
                                ))}
                            >
                                <table className="staff-table w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b-2 border-outline-variant">
                                            {['Camp', 'Child', 'Parent', 'Email', 'Amount', 'Currency', 'Gateway', 'Status', 'Date & time'].map((h) => (
                                                <th key={h} className="py-3 px-4 font-bold">
                                                    {h}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {campBookings.map((b, i) => (
                                            <tr key={i} className="border-b hover:bg-surface-container-low">
                                                <td className="font-medium min-w-0"><span className="break-words">{b.campName}</span></td>
                                                <td className="min-w-0 break-words">{b.childName}</td>
                                                <td className="min-w-0 break-words">{b.parentName}</td>
                                                <td className="text-sm min-w-0"><span className="truncate block max-w-[12rem]">{b.email}</span></td>
                                                <td className="font-bold">
                                                    {b.amountFormatted || <span className="text-sm text-on-surface-variant">Free</span>}
                                                </td>
                                                <td>
                                                    {b.currency ? (
                                                        <span className="px-2 py-0.5 rounded text-xs font-mono bg-surface-container">{b.currency}</span>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td>
                                                    <span className="text-sm font-bold bg-surface-container-low px-2 py-1 rounded-md">{b.paymentMethod}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        className={`px-3 py-1 rounded-full text-xs font-bold ${
                                                            b.paymentStatus === 'paid'
                                                                ? 'bg-secondary-container'
                                                                : 'bg-error-container text-on-error-container'
                                                        }`}
                                                    >
                                                        {b.paymentStatus}
                                                    </span>
                                                </td>
                                                <td className="text-sm text-on-surface-variant">{b.createdAtLabel}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </DataTableShell>
                        )}
                    </PortalCard>
                </div>
            </div>

            <ConfirmDialog
                open={Boolean(refundTarget)}
                title="Refund payment?"
                message={
                    refundTarget
                        ? `Refund ${refundTarget.amountFormatted} for ${refundTarget.studentName} (${refundTarget.courseTitle})?`
                        : ''
                }
                confirmLabel="Refund"
                busy={refundBusy}
                onCancel={() => (!refundBusy ? setRefundTarget(null) : null)}
                onConfirm={() => {
                    if (!refundTarget?.refundUrl || refundBusy) return;
                    setRefundBusy(true);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = refundTarget.refundUrl;
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = csrfToken;
                    form.appendChild(token);
                    document.body.appendChild(form);
                    form.submit();
                }}
            />
        </DashboardLayout>
    );
}
