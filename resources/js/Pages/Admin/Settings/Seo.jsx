import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

const defaults = {
    seo_meta_title: 'Younimatiq — Personalized K–12 Online Learning',
    seo_meta_description:
        'Personalized K–12 online learning with expert mentors. Coding, AI, robotics, math, science, arts and more — built around your child’s interests.',
    seo_meta_keywords:
        'Younimatiq, personalized learning, online classes for kids, K-12 tutoring, coding for kids, STEM courses, AI for kids, robotics for kids',
};

export default function Seo({ settings = {} }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="SEO Settings">
            <Head title="SEO Settings" />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                            <Icon name="search" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-on-surface">Global SEO Settings</h2>
                            <p className="text-on-surface-variant">Manage meta tags for better search engine visibility.</p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    <form method="POST" action="/admin/settings" className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="seo_meta_title" defaultValue={settings.seo_meta_title ?? defaults.seo_meta_title} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="Global Meta Title" />
                        <textarea name="seo_meta_description" rows={3} defaultValue={settings.seo_meta_description ?? defaults.seo_meta_description} className="w-full p-4 bg-surface-container-low border rounded-xl" />
                        <input type="text" name="seo_meta_keywords" defaultValue={settings.seo_meta_keywords ?? defaults.seo_meta_keywords} className="w-full p-4 bg-surface-container-low border rounded-xl" placeholder="Keywords" />
                        <button type="submit" className="btn-enroll">Save SEO Data</button>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
