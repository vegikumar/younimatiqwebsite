import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function Toggle({ name, defaultChecked, label }) {
    return (
        <label className="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name={name} value="1" defaultChecked={defaultChecked} className="sr-only peer" />
            <div className="w-11 h-6 bg-surface-container rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" />
            <span className="ml-3 text-sm font-bold text-on-surface">{label}</span>
        </label>
    );
}

export default function Gateway({ settings = {}, activeProvider = 'zoom', webhookUrls = {} }) {
    const { csrfToken, flash } = usePage().props;
    const [provider, setProvider] = useState(activeProvider);

    // DB stores "0"/"1" strings. In JS, !!"0" is true — treat only 1/true/"1" as on.
    const chk = (key) => {
        const v = settings[key];
        if (v === undefined || v === null || v === '') return true;
        return v === true || v === 1 || v === '1';
    };

    return (
        <DashboardLayout title="Gateway Settings">
            <Head title="API & Gateway Settings" />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                            <Icon name="payments" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-headline-md text-on-surface">API & Gateway Settings</h2>
                            <p className="font-body-md text-on-surface-variant">Configure Zoom API, Stripe, PayPal, and Razorpay.</p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    {flash?.error ? <div className="mb-6 p-4 bg-error-container rounded-xl font-bold">{flash.error}</div> : null}

                    <form method="POST" action="/admin/settings" className="space-y-8">
                        <input type="hidden" name="_token" value={csrfToken} />

                        <div className="space-y-4">
                            <h3 className="font-bold text-lg text-primary border-b border-outline-variant pb-2">Zoom Integration</h3>
                            <input type="text" name="zoom_account_id" defaultValue={settings.zoom_account_id ?? ''} placeholder="Zoom Account ID" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <input type="text" name="zoom_client_id" defaultValue={settings.zoom_client_id ?? ''} placeholder="Zoom Client ID" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <input type="password" name="zoom_client_secret" defaultValue={settings.zoom_client_secret ?? ''} placeholder="Zoom Client Secret" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                        </div>

                        <div className="space-y-4">
                            <div className="border-b border-outline-variant pb-2">
                                <h3 className="font-bold text-lg text-secondary">Class Meeting Provider</h3>
                                <p className="text-xs text-on-surface-variant mt-1">
                                    Only one provider is active for <strong>new</strong> class links (tutor assign, demos, reschedules). Existing Zoom links stay Zoom until regenerated.
                                </p>
                            </div>
                            <div className="grid sm:grid-cols-2 gap-3">
                                <label className={`staff-card rounded-xl border bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-4 flex items-start gap-3 cursor-pointer ${provider === 'zoom' ? 'border-primary' : 'border-outline-variant/25'}`}>
                                    <input type="radio" name="meeting_provider" value="zoom" checked={provider === 'zoom'} onChange={() => setProvider('zoom')} className="mt-1" />
                                    <span><span className="font-bold block">Zoom</span><span className="text-xs text-on-surface-variant">Zoom cloud meetings for all new class links</span></span>
                                </label>
                                <label className={`staff-card rounded-xl border bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-4 flex items-start gap-3 cursor-pointer ${provider === 'meet' ? 'border-primary' : 'border-outline-variant/25'}`}>
                                    <input type="radio" name="meeting_provider" value="meet" checked={provider === 'meet'} onChange={() => setProvider('meet')} className="mt-1" />
                                    <span><span className="font-bold block">Younimatiq Meet</span><span className="text-xs text-on-surface-variant">Self-hosted HD rooms on this server (LiveKit) + contact guard</span></span>
                                </label>
                            </div>
                            <p className="text-xs text-on-surface-variant">
                                Meet connection is fixed on the server. After saving, use <a className="text-primary font-bold" href="/admin/integrations">Integrations → Test Meet</a> to verify room creation.
                            </p>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between border-b pb-2">
                                <h3 className="font-bold text-lg text-secondary">Stripe Integration</h3>
                                <Toggle name="enable_stripe" defaultChecked={chk('enable_stripe')} label="Enable Stripe" />
                            </div>
                            <input type="text" name="stripe_key" defaultValue={settings.stripe_key ?? ''} placeholder="Stripe Publishable Key" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            <input type="password" name="stripe_secret" defaultValue={settings.stripe_secret ?? ''} placeholder="Stripe Secret Key" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            <input type="password" name="stripe_webhook_secret" defaultValue={settings.stripe_webhook_secret ?? ''} placeholder="Stripe Webhook Secret" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            <p className="text-xs text-on-surface-variant">Webhook URL: {webhookUrls.stripe}</p>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between border-b pb-2">
                                <h3 className="font-bold text-lg text-[#003087]">PayPal Integration</h3>
                                <Toggle name="enable_paypal" defaultChecked={chk('enable_paypal')} label="Enable PayPal" />
                            </div>
                            <select name="paypal_mode" defaultValue={settings.paypal_mode ?? 'sandbox'} className="w-full p-4 bg-surface-container-low border rounded-xl">
                                <option value="sandbox">Sandbox</option>
                                <option value="live">Live</option>
                            </select>
                            <input type="text" name="paypal_client_id" defaultValue={settings.paypal_client_id ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="PayPal Client ID" />
                            <input type="password" name="paypal_secret" defaultValue={settings.paypal_secret ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="PayPal Secret" />
                            <input type="text" name="paypal_webhook_id" defaultValue={settings.paypal_webhook_id ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="PayPal Webhook ID" />
                            <p className="text-xs text-on-surface-variant">Webhook URL: {webhookUrls.paypal}</p>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between border-b pb-2">
                                <h3 className="font-bold text-lg text-tertiary">Razorpay Integration</h3>
                                <Toggle name="enable_razorpay" defaultChecked={chk('enable_razorpay')} label="Enable Razorpay" />
                            </div>
                            <input type="text" name="razorpay_key" defaultValue={settings.razorpay_key ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="Razorpay Key ID" />
                            <input type="password" name="razorpay_secret" defaultValue={settings.razorpay_secret ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="Razorpay Key Secret" />
                            <input type="password" name="razorpay_webhook_secret" defaultValue={settings.razorpay_webhook_secret ?? ''} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="Razorpay Webhook Secret" />
                            <p className="text-xs text-on-surface-variant">Webhook URL: {webhookUrls.razorpay}</p>
                        </div>

                        <div className="pt-6 border-t border-outline-variant">
                            <button type="submit" className="btn-primary">Save API Settings</button>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
