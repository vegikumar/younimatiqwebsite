import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';

export default function Edit({ coupon }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Edit Coupon">
            <Head title="Edit Coupon" />
            <div className="staff-page">
                <PortalCard className="p-6 bg-white max-w-xl">
                    <h1 className="font-headline-sm text-lg font-bold mb-4">Edit Coupon</h1>
                    <form method="POST" action={`/admin/coupons/${coupon.id}`} className="space-y-4">
                        <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="_method" value="PUT" />
                        <div><label className="block text-sm font-bold mb-1">Code *</label><input type="text" name="code" defaultValue={coupon.code} required className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm uppercase" /></div>
                        <div><label className="block text-sm font-bold mb-1">Type *</label><select name="type" defaultValue={coupon.type} required className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm"><option value="percent">Percent</option><option value="fixed">Fixed</option></select></div>
                        <div><label className="block text-sm font-bold mb-1">Value *</label><input type="number" step="0.01" min={0} name="value" defaultValue={coupon.value} required className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm" /></div>
                        <div><label className="block text-sm font-bold mb-1">Min Amount</label><input type="number" step="0.01" min={0} name="min_amount" defaultValue={coupon.min_amount ?? ''} className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm" /></div>
                        <div><label className="block text-sm font-bold mb-1">Max Uses</label><input type="number" min={1} name="max_uses" defaultValue={coupon.max_uses ?? ''} className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm" /></div>
                        <div><label className="block text-sm font-bold mb-1">Expires At</label><input type="date" name="expires_at" defaultValue={coupon.expires_at || ''} className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm" /></div>
                        <div className="flex gap-3 pt-2"><button type="submit" className="btn-primary py-2 px-4 text-sm font-bold">Save</button><Link href="/admin/coupons" className="btn-outline-primary py-2 px-4 text-sm font-bold">Cancel</Link></div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
