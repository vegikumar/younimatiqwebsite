import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function TrialExpired() {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Trial Expired">
            <Head title="Trial Expired - Younimatiq" />
            <div className="min-h-[70vh] bg-surface-container-lowest flex items-center justify-center p-4">
                <div className="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden text-center border border-surface-container">
                    <div className="bg-primary py-8 text-white">
                        <span className="material-symbols-outlined text-6xl mb-2">hourglass_empty</span>
                        <h1 className="text-3xl font-headline-lg font-bold">Trial Expired</h1>
                    </div>
                    <div className="p-5 md:p-8">
                        <p className="text-on-surface-variant text-lg mb-6">
                            Your 9-day free trial period has ended. We hope you and your child enjoyed the free trial class!
                        </p>
                        <p className="text-on-surface text-md font-medium mb-8">
                            To unlock full access to the student dashboard, interactive learning paths, and more live classes, please purchase a course.
                        </p>

                        <div className="flex flex-col gap-3">
                            <a href="/courses" className="btn-primary w-full text-center text-lg py-3 rounded-xl font-bold">
                                Browse Courses
                            </a>
                            <a href="/" className="btn-secondary w-full text-center text-lg py-3 rounded-xl font-bold">
                                Back to Home
                            </a>
                            <form action="/logout/student" method="POST" className="w-full">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-full text-center text-lg py-3 rounded-xl font-bold border border-outline text-on-surface hover:bg-surface-container transition-colors">
                                    Log Out
                                </button>
                            </form>
                        </div>

                        <p className="text-sm text-on-surface-variant mt-8">
                            If you believe this is a mistake, please{' '}
                            <a href="mailto:support@younimatiq.com" className="text-primary hover:underline">
                                contact support
                            </a>
                            .
                        </p>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
