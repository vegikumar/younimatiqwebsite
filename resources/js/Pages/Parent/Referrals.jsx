import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import { formatMoney } from '../../utils/formatLocalTime';

export default function Referrals({ referralCode = '', totalEarned = 0, referrals = [], siblingDiscounts = [] }) {
    const { flash } = usePage().props;
    const copyCode = () => {
        if (referralCode && navigator.clipboard) {
            navigator.clipboard.writeText(referralCode);
        }
    };

    return (
        <DashboardLayout title="Referrals">
            <Head title="Family Referrals & Sibling Discounts" />
            <div className="p-5 md:p-8 max-w-5xl mx-auto space-y-6">
                <PageHeader
                    eyebrow="Family rewards"
                    title="Referrals & sibling discounts"
                    description="Share your family code for tuition credits. Sibling enrollments get 15% off automatically at checkout."
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}

                <div className="grid md:grid-cols-3 gap-4">
                    <section className="clay-card p-5 space-y-3 md:col-span-1">
                        <h2 className="font-bold">Your referral code</h2>
                        <p className="text-3xl font-extrabold text-primary tracking-widest uppercase text-center py-4 bg-surface-container-low rounded-xl">
                            {referralCode || '—'}
                        </p>
                        <button type="button" onClick={copyCode} className="btn-primary w-full text-sm">
                            Copy code
                        </button>
                        <p className="text-xs text-on-surface-variant text-center">
                            Credits earned: ${formatMoney(totalEarned)}
                        </p>
                    </section>

                    <section className="clay-card p-5 space-y-3 md:col-span-2">
                        <h2 className="font-bold">Sibling discounts</h2>
                        {siblingDiscounts.length === 0 ? (
                            <p className="text-sm text-on-surface-variant">
                                Link 2+ children and enroll one — the next sibling checkout gets 15% off.
                            </p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {siblingDiscounts.map((s) => (
                                    <li key={s.id} className="p-3 rounded-xl bg-surface-container-low flex justify-between gap-2">
                                        <span>
                                            {s.sibling || 'Sibling'} {s.primary ? `(primary: ${s.primary})` : ''}
                                        </span>
                                        <span className="font-bold text-primary">{s.discount_percent}%</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                <section className="clay-card p-5 space-y-3">
                    <h2 className="font-bold">Referred families</h2>
                    {referrals.length === 0 ? (
                        <p className="text-sm text-on-surface-variant">No referrals yet — share your code above.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b border-outline-variant/30">
                                        <th className="py-2 pr-3">Email</th>
                                        <th className="py-2 pr-3">Reward</th>
                                        <th className="py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {referrals.map((ref) => (
                                        <tr key={ref.id} className="border-b border-outline-variant/20">
                                            <td className="py-2 pr-3">{ref.referredEmail || ref.referralCode}</td>
                                            <td className="py-2 pr-3 font-bold">+${formatMoney(ref.rewardAmount)}</td>
                                            <td className="py-2 capitalize">{ref.status}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </DashboardLayout>
    );
}
