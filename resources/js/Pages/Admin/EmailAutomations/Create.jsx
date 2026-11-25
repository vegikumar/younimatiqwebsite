import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import { Link } from '@inertiajs/react';

export default function Create({ defaults = {}, routes = {} }) {
    const { errors, csrfToken } = usePage().props;
    const errList = errors ? Object.values(errors).flat() : [];

    return (
        <DashboardLayout title="New Email Automation">
            <Head title="New Email Automation" />
            <div className="staff-page">
                <div className="mb-6">
                    <Link href={routes.index} className="text-sm font-bold text-primary hover:underline">
                        ← All automations
                    </Link>
                </div>

                <PortalCard className="p-5 md:p-8 max-w-xl border-t-8 border-primary">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">New email automation</h1>
                    <p className="text-sm text-on-surface-variant mb-6">Give it a name. You’ll set schedule, audiences, and copy on the next screen.</p>

                    {errList.length > 0 && (
                        <div className="mb-4 p-4 rounded-xl bg-error-container text-on-error-container">
                            <ul className="list-disc pl-5 space-y-1">
                                {errList.map((e, i) => (
                                    <li key={i}>{e}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <form method="POST" action={routes.store} className="space-y-4">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div>
                            <label className="block text-sm font-bold mb-2">Campaign name</label>
                            <input
                                type="text"
                                name="name"
                                required
                                maxLength={120}
                                className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                                placeholder="e.g. Weekend camp promo"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">
                                Email subject <span className="font-normal text-on-surface-variant">(optional for now)</span>
                            </label>
                            <input
                                type="text"
                                name="subject"
                                maxLength={180}
                                defaultValue={defaults.subject || ''}
                                className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                                placeholder="Trending classes this week"
                            />
                        </div>
                        <div className="flex flex-wrap gap-3 pt-2">
                            <button type="submit" className="btn-primary">
                                Create &amp; configure
                            </button>
                            <Link href={routes.index} className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
