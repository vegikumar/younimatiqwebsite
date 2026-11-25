import { Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

export default function Success({ booking }) {
    return (
        <PublicLayout title="Booking confirmed">
            <Head title="Booking confirmed" />
            <div className="max-w-lg mx-auto px-4 py-16 text-center">
                <div className="clay-card p-8">
                    <h1 className="text-2xl font-bold text-primary mb-2">You&apos;re booked!</h1>
                    <p className="text-on-surface-variant mb-4">{booking.camp_name || 'Trial class'} is confirmed.</p>
                    {booking.scheduled_at_label ? (
                        <p className="font-bold">
                            {booking.scheduled_at_label}
                            {booking.timezone_label ? (
                                <span className="block text-sm font-semibold text-on-surface-variant mt-1">
                                    {booking.timezone_label}
                                </span>
                            ) : null}
                        </p>
                    ) : null}
                    <a href="/student/dashboard" className="btn-enroll inline-block mt-8">
                        Go to dashboard
                    </a>
                </div>
            </div>
        </PublicLayout>
    );
}
