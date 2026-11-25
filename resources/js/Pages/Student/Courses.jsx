import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';
import PageHeader from '../../Components/Portal/PageHeader';
import EmptyState from '../../Components/Portal/EmptyState';

function stripHtml(html) {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, '').trim();
}

function CertificateButton({ enrollment, courseId }) {
    if (!enrollment) return null;
    if (enrollment.has_certificate) {
        return (
            <a href={`/student/courses/${courseId}/certificate/download`} className="inline-flex items-center gap-1.5 btn-secondary btn-sm !px-4 text-xs font-bold">
                <Icon name="download" className="text-[16px]" /> Download Certificate
            </a>
        );
    }
    if (enrollment.status === 'completed') {
        return (
            <a href={`/student/courses/${courseId}/certificate`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 btn-secondary btn-sm !px-4 text-xs font-bold">
                <Icon name="workspace_premium" className="text-[16px]" /> View Certificate
            </a>
        );
    }
    return null;
}

function JoinClassBlock({ booking }) {
    if (!booking?.zoom_join_url) return null;
    return (
        <a
            href={booking.zoom_join_url}
            target="_blank"
            rel="noreferrer"
            className="flex items-center justify-center gap-1.5 bg-success text-white w-full py-2 rounded-lg text-sm font-bold hover:bg-success transition-colors shadow-sm"
        >
            <Icon name="language" className="text-sm" /> Join Class
        </a>
    );
}

function OneToOneStatus({ booking }) {
    if (!booking) {
        return (
            <div className="bg-surface-container-low border border-outline-variant text-on-surface text-sm p-4 rounded-xl mb-4 font-medium">
                Processing your 1:1 booking...
            </div>
        );
    }
    if (!booking.tutor_id) {
        return (
            <div className="bg-warning-container border border-warning-container text-warning text-sm p-4 rounded-xl mb-4 font-medium">
                <div className="flex items-center gap-2 mb-1">
                    <Icon name="schedule" className="text-warning text-lg" />
                    <span className="font-bold">Not Yet Allotted</span>
                </div>
                <p className="text-xs text-warning/80">We are assigning the best expert for you.</p>
            </div>
        );
    }
    if (booking.status === 'completed' || booking.session_ended) {
        return (
            <div className="bg-surface-container border border-surface-container-high text-on-surface-variant text-sm p-4 rounded-xl mb-4 font-medium flex items-center gap-2">
                <Icon name="event_busy" className="text-[18px]" /> Session Ended
            </div>
        );
    }
    if (booking.zoom_join_url || booking.join_url) {
        return (
            <div className="bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary text-sm p-4 rounded-xl mb-4 font-medium flex flex-col gap-3">
                {booking.scheduled_at_label ? (
                    <p className="text-xs font-bold text-on-surface-variant">Next class: {booking.scheduled_at_label}</p>
                ) : null}
                <JoinClassBlock booking={booking} />
            </div>
        );
    }
    return (
        <div className="bg-secondary-container border border-secondary-container text-on-secondary-container text-sm p-4 rounded-xl mb-4 font-medium">
            <div className="flex items-center gap-2 mb-1">
                <Icon name="verified" className="text-secondary text-lg" />
                <span className="font-bold">Tutor Assigned</span>
            </div>
            <p className="text-xs text-on-secondary-container/80">
                {booking.scheduled_at_label
                    ? `Next class: ${booking.scheduled_at_label}. Your class link will be ready shortly.`
                    : 'Your class link will be ready shortly.'}
            </p>
        </div>
    );
}

export default function Courses({ courses = [], enrollmentsByCourse = {}, bookingsByCourse = {}, search = '' }) {
    const { flash } = usePage().props;

    return (
        <DashboardLayout title="Enrolled Courses">
            <Head title="Enrolled Courses" />
            <div className="p-5 md:p-8 space-y-8">
                <PageHeader
                    eyebrow="Learning"
                    title="My enrolled courses"
                    description="Continue learning from your active and completed courses."
                    actions={
                        <a href="/courses" className="btn-primary btn-sm">
                            Browse catalog
                        </a>
                    }
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container font-bold">{flash.error}</div> : null}

                <form action="/student/courses" method="GET" className="flex gap-4 max-w-lg">
                    <input
                        type="text"
                        name="search"
                        defaultValue={search}
                        placeholder="Search your courses..."
                        className="flex-1 px-4 py-3 bg-surface-container-low border border-surface-container-high rounded-xl focus:outline-none focus:border-primary"
                    />
                    <button type="submit" className="btn-primary btn-sm">
                        Search
                    </button>
                </form>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {courses.map((course) => {
                        const enrollment = enrollmentsByCourse[course.id];
                        const booking = bookingsByCourse[course.id];
                        const isLiveEnrollment = String(enrollment?.delivery_mode || '').toLowerCase() === 'live'
                            || booking?.session_type === 'scheduled_class'
                            || Boolean(booking?.tutor_id)
                            || Boolean(booking?.zoom_join_url);
                        const is1to1 = course.format === '1to1' || isLiveEnrollment;
                        const tutor = booking?.tutor;

                        return (
                            <div key={course.id} className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden flex flex-col hover:border-primary/30 transition-colors">
                                {is1to1 ? (
                                    <div className="h-32 bg-gradient-to-br from-tertiary/20 to-primary/20 flex flex-col items-center justify-center p-4 relative">
                                        <span className="absolute top-2 right-3 text-[10px] font-bold uppercase tracking-wider bg-white/60 text-on-tertiary-container px-2 py-0.5 rounded-full">
                                            1:1 Tutoring
                                        </span>
                                        {tutor ? (
                                            <>
                                                <div className="w-14 h-14 rounded-full bg-white border-2 border-tertiary shadow-sm flex items-center justify-center overflow-hidden mb-2 text-xl font-bold text-tertiary">
                                                    {(tutor.name || '?').charAt(0)}
                                                </div>
                                                <span className="text-xs font-bold text-on-surface">with {(tutor.name || '').split(' ')[0]}</span>
                                            </>
                                        ) : (
                                            <>
                                                <div className="w-12 h-12 rounded-full bg-white/50 border border-white/80 shadow-sm flex items-center justify-center mb-2 animate-pulse">
                                                    <Icon name="person_search" className="text-tertiary/50" />
                                                </div>
                                                <span className="text-xs font-bold text-on-surface-variant">Matching Tutor...</span>
                                            </>
                                        )}
                                    </div>
                                ) : (
                                    <div className="h-32 bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                                        <Icon name="school" className="text-5xl text-primary/40" />
                                    </div>
                                )}

                                <div className="p-6 flex-1 flex flex-col">
                                    <h3 className="font-headline-md font-bold mb-2">{course.title}</h3>
                                    <p className="text-sm text-on-surface-variant line-clamp-2 mb-4 flex-1">{stripHtml(course.description)}</p>

                                    {enrollment ? (
                                        is1to1 ? (
                                            <OneToOneStatus booking={booking} />
                                        ) : (
                                            <>
                                                <div className="mb-4">
                                                    <div className="flex justify-between text-xs text-on-surface-variant mb-1">
                                                        <span>Progress</span>
                                                        <span>{Math.round(enrollment.progress ?? 0)}%</span>
                                                    </div>
                                                    <div className="w-full bg-surface-container-high rounded-full h-2">
                                                        <div className="bg-primary h-2 rounded-full" style={{ width: `${enrollment.progress ?? 0}%` }} />
                                                    </div>
                                                </div>
                                                <span
                                                    className={`inline-block px-3 py-1 rounded-full text-[10px] uppercase tracking-wider font-bold mb-4 ${
                                                        enrollment.status === 'completed' ? 'bg-secondary-container text-on-secondary-container' : 'bg-primary-container text-on-primary-container'
                                                    }`}
                                                >
                                                    {enrollment.status}
                                                </span>
                                            </>
                                        )
                                    ) : null}

                                    <div className="flex flex-wrap items-center justify-between gap-2 pt-4 border-t border-surface-container-high mt-auto">
                                        {is1to1 ? (
                                            <>
                                                <span className="text-xs text-on-surface-variant font-medium">1:1 Coaching</span>
                                                <div className="flex flex-wrap items-center gap-2 justify-end">
                                                    <CertificateButton enrollment={enrollment} courseId={course.id} />
                                                    {booking?.tutor_id ? (
                                                        <a href="mailto:support@younimatiq.com" className="btn-secondary btn-sm !px-4 text-xs">
                                                            Contact Support
                                                        </a>
                                                    ) : (
                                                        <span className="text-xs text-on-surface-variant italic">Pending...</span>
                                                    )}
                                                </div>
                                            </>
                                        ) : (
                                            <>
                                                <span className="text-xs text-on-surface-variant font-medium">{course.modules_count ?? 0} modules</span>
                                                <div className="flex flex-wrap items-center gap-2 justify-end">
                                                    <CertificateButton enrollment={enrollment} courseId={course.id} />
                                                    <Link href={`/student/courses/${course.id}/player`} className="btn-primary btn-sm !px-6">
                                                        {(enrollment?.progress ?? 0) > 0 ? 'Continue' : 'Start'}
                                                    </Link>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {courses.length === 0 ? (
                    <EmptyState
                        icon="school"
                        title="No enrolled courses yet"
                        description="Browse our catalog and enroll in your first adventure."
                        action={
                            <a href="/courses" className="btn-primary btn-sm">
                                Explore Courses
                            </a>
                        }
                    />
                ) : null}
            </div>
        </DashboardLayout>
    );
}
