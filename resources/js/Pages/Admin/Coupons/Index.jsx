import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ coupons = [] }) {
    const { flash, csrfToken } = usePage().props;
    const [editCoupon, setEditCoupon] = useState(null);

    const destroy = (id) => {
        if (!confirm('Delete this coupon?')) return;
        router.delete(`/admin/coupons/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Coupons">
            <Head title="Coupons" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Discount Coupons</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Create and manage promotional discount codes.</p>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2">
                            <Icon name="local_offer" className="text-primary" /> Create Coupon
                        </h2>
                        <form method="POST" action="/admin/coupons" className="space-y-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2">Code</label>
                                <input type="text" name="code" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none uppercase" placeholder="SUMMER20" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-bold mb-2">Type</label>
                                    <select name="type" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                        <option value="percent">Percent (%)</option>
                                        <option value="fixed">Fixed ($)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Value</label>
                                    <input type="number" name="value" step="0.01" min="0" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Min Order Amount</label>
                                <input type="number" name="min_amount" step="0.01" min="0" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Optional" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Max Uses</label>
                                <input type="number" name="max_uses" min="1" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Unlimited" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Expires At</label>
                                <input type="date" name="expires_at" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <button type="submit" className="btn-primary btn-block">Create Coupon</button>
                        </form>
                    </PortalCard>

                    <PortalCard className="lg:col-span-2 p-5 md:p-8">
                        <div className="overflow-x-auto">
                            <table className="staff-table w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b-2 border-outline-variant">
                                        <th className="font-bold">Code</th>
                                        <th className="font-bold">Discount</th>
                                        <th className="font-bold">Uses</th>
                                        <th className="font-bold">Expires</th>
                                        <th className="font-bold">Status</th>
                                        <th className="font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {coupons.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="py-8 text-center text-on-surface-variant">
                                                No coupons created yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        coupons.map((coupon) => (
                                            <tr key={coupon.id} className="border-b border-outline-variant hover:bg-surface-container-low">
                                                <td className="font-mono font-bold text-primary">{coupon.code}</td>
                                                <td>{coupon.discount_label}</td>
                                                <td className="text-on-surface-variant">
                                                    {coupon.used_count}
                                                    {coupon.max_uses ? `/${coupon.max_uses}` : ''}
                                                </td>
                                                <td className="text-sm text-on-surface-variant">{coupon.expires_label}</td>
                                                <td>
                                                    <span
                                                        className={`px-3 py-1 rounded-full text-xs font-bold ${coupon.is_valid ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container'}`}
                                                    >
                                                        {coupon.is_valid ? 'Valid' : 'Expired'}
                                                    </span>
                                                </td>
                                                <td className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <button type="button" onClick={() => setEditCoupon(coupon)} className="p-2 text-on-surface-variant hover:text-primary" title="Edit">
                                                            <Icon name="edit" className="text-sm" />
                                                        </button>
                                                        <button type="button" onClick={() => destroy(coupon.id)} className="p-2 text-on-surface-variant hover:text-error">
                                                            <Icon name="delete" className="text-sm" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </PortalCard>
                </div>
            </div>

            {editCoupon ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm text-left">
                    <div className="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative">
                        <button type="button" onClick={() => setEditCoupon(null)} className="absolute top-4 right-4 p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full">
                            <Icon name="close" className="text-sm" />
                        </button>
                        <h3 className="font-bold text-xl mb-4">Edit Coupon</h3>
                        <form method="POST" action={`/admin/coupons/${editCoupon.id}`} className="space-y-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PUT" />
                            <div>
                                <label className="block text-sm font-bold mb-2">Code</label>
                                <input type="text" name="code" defaultValue={editCoupon.code} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none uppercase" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-bold mb-2">Type</label>
                                    <select name="type" defaultValue={editCoupon.type} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                                        <option value="percent">Percent (%)</option>
                                        <option value="fixed">Fixed ($)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Value</label>
                                    <input type="number" name="value" defaultValue={editCoupon.value} step="0.01" min="0" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Min Order Amount</label>
                                <input type="number" name="min_amount" defaultValue={editCoupon.min_amount ?? ''} step="0.01" min="0" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-bold mb-2">Max Uses</label>
                                    <input type="number" name="max_uses" defaultValue={editCoupon.max_uses ?? ''} min="1" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Expires At</label>
                                    <input type="date" name="expires_at" defaultValue={editCoupon.expires_at_date || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" />
                                </div>
                            </div>
                            <button type="submit" className="btn-primary w-full">Save Changes</button>
                        </form>
                    </div>
                </div>
            ) : null}
        </DashboardLayout>
    );
}
