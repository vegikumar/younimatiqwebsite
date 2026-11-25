import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

export default function ProfileStorefront({ profile }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Profile Storefront">
            <Head title="My Brand Storefront Settings" />
            <div className="staff-page max-w-3xl">
                <PageHeader
                    variant="staff"
                    title="Teacher Brand & Storefront Profile"
                    description="Customize your public storefront bio, teaching style, and video intro that families browse before booking."
                    actions={
                        <a href={profile.previewUrl} target="_blank" rel="noreferrer" className="btn-primary py-2.5 px-5 text-xs font-bold flex items-center gap-1">
                            <span className="material-symbols-outlined text-sm">open_in_new</span> Preview Public Storefront
                        </a>
                    }
                />

                <PortalCard title="Storefront settings">
                    <form method="POST" action={profile.updateUrl} className="space-y-6 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div className="min-w-0">
                            <label className="block text-sm font-bold text-on-surface mb-1">Teacher Bio (Storefront Headline)</label>
                            <textarea name="bio" rows={4} defaultValue={profile.bio ?? ''} placeholder="Introduce yourself to families..." className="w-full min-w-0 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary" />
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold text-on-surface mb-1">Teaching Philosophy & Style</label>
                            <textarea name="teaching_style" rows={4} defaultValue={profile.teachingStyle ?? ''} placeholder="Describe your classroom approach..." className="w-full min-w-0 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary" />
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold text-on-surface mb-1">Intro Video URL (YouTube / Vimeo / Loom)</label>
                            <input type="url" name="intro_video_url" defaultValue={profile.introVideoUrl ?? ''} placeholder="https://youtube.com/watch?v=..." className="w-full min-w-0 px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none focus:border-primary" />
                        </div>
                        <button type="submit" className="btn-enroll py-3 px-8 text-xs font-bold">
                            Save Storefront Profile
                        </button>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
