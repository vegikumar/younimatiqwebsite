import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function Wishlist({ items = [] }) {
    const remove = (courseId) => {
        if (!courseId) return;
        router.delete(`/student/wishlist/${courseId}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Wishlist">
            <Head title="Wishlist" />
            <div className="p-5 md:p-8 space-y-8">
                <div>
                    <h1 className="font-headline-lg text-headline-lg font-bold mb-2">My Wishlist</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">
                        Courses you&apos;ve saved for your future adventures.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {items.length ? (
                        items.map((item) => {
                            const course = item.course || {};
                            const thumb = course.thumbnail_url || null;
                            return (
                                <div
                                    key={item.id}
                                    className="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden group flex flex-col"
                                >
                                    <div className="aspect-square bg-surface-container-low relative flex items-center justify-center overflow-hidden">
                                        {thumb ? (
                                            <img src={thumb} alt={course.title || ''} className="w-full h-full object-contain" loading="lazy" />
                                        ) : (
                                            <div className="absolute inset-0 bg-gradient-to-br from-tertiary/20 to-primary/20 flex items-center justify-center">
                                                <span className="material-symbols-outlined text-6xl text-on-surface-variant opacity-50">
                                                    menu_book
                                                </span>
                                            </div>
                                        )}
                                        <button
                                            type="button"
                                            aria-label="Remove from wishlist"
                                            onClick={() => remove(course.id)}
                                            className="absolute top-4 right-4 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-error hover:scale-110 transition-transform"
                                        >
                                            <span
                                                className="material-symbols-outlined"
                                                style={{ fontVariationSettings: "'FILL' 1" }}
                                            >
                                                favorite
                                            </span>
                                        </button>
                                    </div>
                                    <div className="p-6 flex flex-col flex-1">
                                        <div className="text-xs font-bold text-tertiary uppercase tracking-wider mb-2">
                                            Course
                                        </div>
                                        <h3 className="font-headline-md text-headline-md font-bold mb-2 group-hover:text-primary transition-colors">
                                            <Link href={course.slug ? `/courses/${course.slug}` : `/courses/${course.id}`}>
                                                {course.title}
                                            </Link>
                                        </h3>
                                        <p className="text-sm text-on-surface-variant mb-4 line-clamp-2">
                                            {course.description}
                                        </p>
                                        <div className="mt-auto flex justify-between items-center gap-3">
                                            <span className="font-bold text-lg">
                                                ${Number(course.price ?? 0).toFixed(2)}
                                            </span>
                                            <a href={`/checkout/${course.id}`} className="btn-primary btn-sm shrink-0">
                                                Enroll / Checkout
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="col-span-full bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
                            <span className="material-symbols-outlined text-4xl mb-2">search</span>
                            <p className="font-bold mb-2">Looking for more?</p>
                            <a href="/courses" className="text-primary font-bold hover:underline">
                                Browse Courses
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
