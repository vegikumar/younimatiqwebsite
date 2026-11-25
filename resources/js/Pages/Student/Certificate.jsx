import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function Certificate({ course, enrollment, user }) {
    return (
        <DashboardLayout title="Certificate">
            <Head title="Certificate" />
            <div className="p-8 max-w-3xl mx-auto text-center">
                <div className="clay-card p-10 bg-gradient-to-br from-primary/5 to-white">
                    <h1 className="text-3xl font-extrabold mb-2">Certificate of Completion</h1>
                    <p className="text-lg">{user.name}</p>
                    <p className="text-on-surface-variant mt-4">{course.title}</p>
                    {enrollment.certificate_url ? (
                        <a href={enrollment.certificate_url} target="_blank" rel="noopener noreferrer" className="btn-enroll inline-block mt-8">
                            Download certificate
                        </a>
                    ) : (
                        <p className="mt-6 text-sm text-on-surface-variant">Your certificate will appear here once issued.</p>
                    )}
                    <Link href="/student/courses" className="block mt-4 text-primary font-bold">
                        Back to courses
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
