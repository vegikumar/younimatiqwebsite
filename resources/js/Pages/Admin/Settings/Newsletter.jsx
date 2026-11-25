import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Newsletter({ settings = {} }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Newsletter">
            <Head title="Newsletter Settings" />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-tertiary/10 text-tertiary rounded-xl flex items-center justify-center">
                            <Icon name="mail" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-on-surface">Newsletter Integration</h2>
                            <p className="text-on-surface-variant">Configure Mailchimp settings.</p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    <form method="POST" action="/admin/settings" className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="password" name="mailchimp_api_key" defaultValue={settings.mailchimp_api_key ?? ''} placeholder="Mailchimp API Key" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                        <input type="text" name="mailchimp_list_id" defaultValue={settings.mailchimp_list_id ?? ''} placeholder="Mailchimp List ID" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                        <button type="submit" className="btn-primary">Save Newsletter Settings</button>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
