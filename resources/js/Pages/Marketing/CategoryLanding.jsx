import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import CourseCard from '../../Components/CourseCard';

export default function CategoryLanding({ category, courses = [] }) {
    const title = category?.name || 'Category';

    return (
        <AppLayout>
            <Head title={title} />
            <section className="bg-gradient-to-br from-[#EFE6FF] via-white to-[#F7F4FC]">
                <div className="max-w-7xl mx-auto px-4 py-12 md:py-16">
                    <p className="text-primary font-bold text-sm uppercase tracking-wide mb-2">Younimatiq</p>
                    <h1 className="text-3xl md:text-5xl font-extrabold" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>
                        {title}
                    </h1>
                    {category?.description && (
                        <p className="mt-3 text-on-surface-variant max-w-2xl text-lg">{category.description}</p>
                    )}
                    <div className="mt-6 flex flex-wrap gap-3">
                        <a href="#leadModal" className="px-6 py-3 rounded-full bg-primary text-white font-bold shadow-lg">
                            Book a free consultation
                        </a>
                        <Link href="/courses" className="px-6 py-3 rounded-full border-2 border-primary text-primary font-bold">
                            All courses
                        </Link>
                    </div>
                </div>
            </section>
            <section className="max-w-7xl mx-auto px-4 py-12">
                <h2 className="text-2xl font-extrabold mb-6" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>
                    Courses in {title}
                </h2>
                {courses.length === 0 ? (
                    <p className="text-on-surface-variant">No published courses in this category yet.</p>
                ) : (
                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {courses.map((c) => (
                            <CourseCard key={c.id} course={c} />
                        ))}
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
