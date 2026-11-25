import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';

export default function TutorPending() {
    return (
        <PublicLayout>
            <Head title="Pending Approval" />
            <div className="min-h-[70vh] flex items-center justify-center px-4 py-16">
                <div className="max-w-lg w-full text-center bg-white rounded-[2rem] border shadow-xl p-8 clay-card">
                    <div className="w-16 h-16 bg-secondary mx-auto rounded-2xl flex items-center justify-center mb-4">
                        <Icon name="hourglass_top" className="text-white text-3xl" />
                    </div>
                    <h1 className="text-2xl font-extrabold mb-2">Application under review</h1>
                    <p className="text-on-surface-variant text-sm mb-6">
                        Thanks for registering as a tutor. Our team will review your profile and email you once you&apos;re approved.
                    </p>
                    <Link href="/" className="inline-flex px-5 py-3 rounded-xl bg-primary text-white font-bold text-sm">
                        Back to home
                    </Link>
                </div>
            </div>
        </PublicLayout>
    );
}
