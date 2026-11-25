import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

export default function Profile({ user = {} }) {
    const { csrfToken, errors, flash } = usePage().props;
    const avatarSrc = user.avatarUrl || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'T')}&background=199C79&color=fff`;
    const fieldErrors = errors && typeof errors === 'object' ? errors : {};

    return (
        <DashboardLayout title="Tutor Profile">
            <Head title="Tutor Profile" />
            <div className="staff-page max-w-3xl">
                <PageHeader variant="staff" title="Tutor Profile" description="Update your account details and password." />
                <PortalCard>
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant/25 pb-6 min-w-0">
                        <div className="w-16 h-16 shrink-0 bg-tertiary text-white rounded-full overflow-hidden">
                            <img src={avatarSrc} alt="" className="w-full h-full object-cover" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-extrabold text-on-surface truncate">{user.name}</p>
                            <p className="text-sm text-on-surface-variant truncate">{user.email}</p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-medium">{flash.success}</div> : null}
                    {Object.keys(fieldErrors).length ? (
                        <div className="mb-6 p-4 bg-error-container text-error rounded-xl">
                            <ul className="list-disc pl-5">
                                {Object.values(fieldErrors).flat().map((e, i) => (
                                    <li key={i}>{e}</li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    <form method="POST" action="/tutor/profile" encType="multipart/form-data" className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div>
                            <label className="block font-bold mb-2">Profile Photo</label>
                            <input type="file" name="avatar" accept="image/*" className="w-full min-w-0 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl" />
                        </div>
                        <div>
                            <label className="block font-bold mb-2">Full Name</label>
                            <input type="text" name="name" defaultValue={user.name} className="w-full min-w-0 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl" />
                        </div>
                        <div>
                            <label className="block font-bold mb-2">Email Address</label>
                            <input type="email" name="email" defaultValue={user.email} className="w-full min-w-0 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl" />
                        </div>
                        <div className="border-t border-outline-variant/25 pt-6">
                            <h3 className="font-bold text-lg mb-4">Change Password</h3>
                            <div className="space-y-4">
                                <input type="password" name="password" placeholder="New password" className="w-full min-w-0 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl" />
                                <input type="password" name="password_confirmation" placeholder="Confirm password" className="w-full min-w-0 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl" />
                            </div>
                        </div>
                        <button type="submit" className="btn-primary">
                            Update Profile
                        </button>
                    </form>
                    <div className="mt-12 pt-10 border-t border-outline-variant/25">
                        <h3 className="text-base font-extrabold mb-4">My Availability</h3>
                        <p className="text-on-surface-variant mb-4">Manage weekly availability on the dedicated availability page.</p>
                        <Link href="/tutor/availability" className="btn-outline-primary">
                            Update Availability
                        </Link>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
