import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Pixel({ settings = {} }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Meta Pixel">
            <Head title="Meta Pixel Settings" />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-[#1877F2]/10 text-[#1877F2] rounded-xl flex items-center justify-center">
                            <Icon name="track_changes" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-on-surface">Meta Pixel Configuration</h2>
                            <p className="text-on-surface-variant">Track conversions and user behavior across Younimatiq.</p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    <form method="POST" action="/admin/settings" className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div>
                            <label className="block font-bold mb-2">Meta (Facebook) Pixel ID</label>
                            <input type="text" name="meta_pixel_id" defaultValue={settings.meta_pixel_id ?? ''} placeholder="e.g. 123456789012345" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                        </div>
                        <div>
                            <label className="block font-bold mb-2">Google Analytics 4 Measurement ID</label>
                            <input type="text" name="google_analytics_id" defaultValue={settings.google_analytics_id ?? ''} placeholder="e.g. G-XXXXXXXXXX" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                        </div>
                        <div>
                            <label className="block font-bold mb-2">Microsoft Clarity Project ID</label>
                            <input type="text" name="microsoft_clarity_id" defaultValue={settings.microsoft_clarity_id ?? 'yn3mggp6o4'} className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                        </div>
                        <div>
                            <label className="block font-bold mb-2">Google Search Console verification</label>
                            <input type="text" name="google_site_verification" defaultValue={settings.google_site_verification ?? ''} className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl" />
                        </div>
                        <button type="submit" className="btn-primary">Save Tracking Settings</button>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
