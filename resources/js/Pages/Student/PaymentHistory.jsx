import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import { formatLocalTime, formatMoney } from '../../utils/formatLocalTime';

export default function PaymentHistory({ payments = [], orders = [] }) {
    return (
        <DashboardLayout title="Payment History">
            <Head title="Payment History" />
            <div className="p-5 md:p-8 space-y-8">
                <div>
                    <h1 className="font-headline-lg text-headline-lg font-bold mb-2">Payment History 🧾</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Review your past transactions and receipts.</p>
                </div>

                {payments.filter((p) => Number(p.amount) > 0).length ? (
                    <div className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
                        <div className="p-6 border-b border-surface-container-high">
                            <h2 className="font-headline-md font-bold">Payments</h2>
                        </div>
                        <div className="overflow-x-auto w-full">
                            <table className="portal-table w-full text-left">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Gateway</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-surface-container-high">
                                    {payments.filter((p) => Number(p.amount) > 0).map((payment) => (
                                        <tr key={payment.id} className="hover:bg-surface-container-low transition-colors">
                                            <td className="text-on-surface-variant">{formatLocalTime(payment.createdAt, 'date')}</td>
                                            <td className="font-bold">{payment.courseTitle}</td>
                                            <td className="font-bold">${formatMoney(payment.amount)}</td>
                                            <td className="text-sm capitalize">{payment.gateway ?? 'N/A'}</td>
                                            <td>
                                                <span className={`px-3 py-1 rounded-full text-xs font-bold ${payment.status === 'completed' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}>{payment.status ? payment.status.charAt(0).toUpperCase() + payment.status.slice(1) : '—'}</span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : null}

                <div className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-surface-container-high">
                        <h2 className="font-headline-md font-bold">Orders</h2>
                    </div>
                    <div className="overflow-x-auto w-full">
                        <table className="portal-table w-full text-left">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-surface-container-high">
                                {orders.filter((o) => Number(o.amount) > 0).length ? (
                                    orders.filter((o) => Number(o.amount) > 0).map((order) => (
                                        <tr key={order.id} className="hover:bg-surface-container-low transition-colors">
                                            <td className="font-mono text-sm">#{order.orderNumber}</td>
                                            <td className="text-on-surface-variant">{formatLocalTime(order.createdAt, 'date')}</td>
                                            <td className="font-bold">{order.courseTitle}</td>
                                            <td className="font-bold">${formatMoney(order.amount)}</td>
                                            <td>
                                                <span className="px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-xs font-bold">{order.status ? order.status.charAt(0).toUpperCase() + order.status.slice(1) : '—'}</span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="p-5 md:p-8 text-center text-on-surface-variant">
                                            No paid charges on file. Manual enrollments do not show a price here.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
