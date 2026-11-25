import { Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { formatMoney } from '../../utils/formatLocalTime';

export default function TutorStorefront({ tutor, averageRating = 5, courses = [] }) {
    return (
        <PublicLayout title={`${tutor.name} - Teacher Storefront`}>
            <Head title={`${tutor.name} - Teacher Storefront`} />
            <div className="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
                <div className="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
                    <div className="flex flex-col md:flex-row items-center md:items-start gap-5 md:p-8">
                        <div className="w-32 h-32 rounded-full bg-primary text-white font-extrabold flex items-center justify-center text-4xl shadow-md shrink-0">{tutor.initial}</div>
                        <div className="flex-1 text-center md:text-left space-y-3">
                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-3">
                                <h1 className="font-headline-xl text-3xl font-extrabold text-on-surface">{tutor.name}</h1>
                                <span className="px-3 py-1 bg-warning-container text-warning rounded-full text-xs font-bold flex items-center gap-1">
                                    ★ {averageRating}
                                </span>
                                <span className="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold">Verified Educator</span>
                            </div>
                            <p className="text-sm text-on-surface-variant max-w-2xl">{tutor.bio || 'Passionate educator inspiring young explorers in technology, coding, and creative problem solving.'}</p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 md:p-10">
                    <div className="space-y-6">
                        <div className="clay-card p-6 bg-white space-y-3">
                            <h3 className="font-bold text-base text-on-surface flex items-center gap-2">
                                <span className="material-symbols-outlined text-primary">psychology</span> Teaching Philosophy & Style
                            </h3>
                            <p className="text-xs text-on-surface-variant leading-relaxed">{tutor.teachingStyle || 'Interactive, project-based learning with hands-on exercises and supportive guidance.'}</p>
                        </div>
                        {tutor.introVideoUrl ? (
                            <div className="clay-card p-6 bg-white space-y-3">
                                <h3 className="font-bold text-base text-on-surface flex items-center gap-2">
                                    <span className="material-symbols-outlined text-primary">movie</span> Teacher Video Intro
                                </h3>
                                <a href={tutor.introVideoUrl} target="_blank" rel="noreferrer" className="btn-primary w-full py-3 text-xs font-bold flex items-center justify-center gap-1">
                                    <span className="material-symbols-outlined text-sm">play_circle</span> Watch Teacher Intro Video
                                </a>
                            </div>
                        ) : null}
                    </div>

                    <div className="md:col-span-2 space-y-8">
                        <div className="clay-card p-6 md:p-8 bg-white space-y-6">
                            <h3 className="font-bold text-xl text-on-surface flex items-center gap-2">
                                <span className="material-symbols-outlined text-primary">school</span> Classes Offered by {tutor.name}
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {courses.length ? (
                                    courses.map((course) => (
                                        <div key={course.id} className="p-5 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 flex flex-col justify-between space-y-4">
                                            <div>
                                                <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-primary-container text-on-primary-container inline-block mb-2">{course.format === 'tutoring' ? '1:1 Tutoring' : 'Group Class'}</span>
                                                <h4 className="font-bold text-base text-on-surface mb-1">{course.title}</h4>
                                                <p className="text-xs text-on-surface-variant line-clamp-2">{course.description}</p>
                                            </div>
                                            <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20">
                                                <span className="font-extrabold text-primary text-base">${formatMoney(course.price)}</span>
                                                <a href={course.showUrl} className="btn-enroll py-2 px-4 text-xs font-bold">
                                                    Explore & Book
                                                </a>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="col-span-2 text-xs text-on-surface-variant py-6 text-center">No active class listings currently available.</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
