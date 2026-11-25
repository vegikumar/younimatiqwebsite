import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

export default function Index({ settings = {} }) {
    const { csrfToken, flash } = usePage().props;
    // DB stores "0"/"1" strings. In JS, !!"0" is true — treat only 1/true/"1" as on.
    const chk = (key) => {
        const v = settings[key];
        if (v === undefined || v === null || v === '') return true;
        return v === true || v === 1 || v === '1';
    };

    return (
        <DashboardLayout title="Platform Settings">
            <Head title="Platform Settings" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Configuration"
                    title="Platform settings"
                    description="Manage global settings like pricing, discounts, and platform rules."
                />
                {flash?.success ? <div className="p-3 rounded-lg bg-secondary-container font-bold text-sm">{flash.success}</div> : null}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
                    <PortalCard title="Pricing & payments">
                        <form method="POST" action="/admin/platform-settings" className="space-y-6">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2">Free Trial / Consultation Price ($)</label>
                                <p className="text-xs text-on-surface-variant mb-3">Set to 0 to bypass payment gateway for trials.</p>
                                <div className="relative">
                                    <span className="absolute left-4 top-3 font-bold text-on-surface-variant">$</span>
                                    <input type="number" step="0.01" min={0} name="demo_class_price" defaultValue={settings.demo_class_price ?? 0} required className="w-full p-3 pl-8 bg-surface-container-low border border-outline-variant rounded-lg" />
                                </div>
                            </div>
                            <div className="space-y-4 pt-4 border-t border-outline-variant/30">
                                {[
                                    ['enable_stripe', 'Stripe'],
                                    ['enable_paypal', 'PayPal'],
                                    ['enable_razorpay', 'Razorpay'],
                                ].map(([key, label]) => (
                                    <label key={key} className="flex items-center justify-between p-3 bg-surface-container-low border rounded-lg cursor-pointer">
                                        <span className="text-sm font-medium">{label}</span>
                                        <input type="checkbox" name={key} value="1" defaultChecked={chk(key)} className="w-5 h-5 accent-primary" />
                                    </label>
                                ))}
                            </div>
                            <button type="submit" className="btn-primary w-full rounded-lg">
                                Save settings
                            </button>
                        </form>
                    </PortalCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
