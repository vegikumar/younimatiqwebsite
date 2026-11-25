import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import CourseCard from '../../Components/CourseCard';
import Icon from '../../Components/Icon';

export default function Courses({ courses = [], categories = [], filters = {} }) {
    const [q, setQ] = useState(filters.q || '');
    const list = courses?.data || courses || [];

    const search = (e) => {
        e.preventDefault();
        router.get('/courses', { q }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Courses" />
            <section className="bg-gradient-to-br from-[#EFE6FF] via-white to-[#F7F4FC] border-b border-outline-variant/30">
                <div className="max-w-7xl mx-auto px-4 py-10 md:py-14">
                    <h1 className="text-3xl md:text-4xl font-extrabold" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>
                        Explore courses
                    </h1>
                    <p className="text-on-surface-variant mt-2 max-w-xl">
                        Live 1:1 classes with starts-from pricing. Filter by interest and book a free consultation.
                    </p>
                    <form onSubmit={search} className="mt-6 flex flex-col sm:flex-row gap-2 max-w-xl">
                        <input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search courses…"
                            className="flex-1 px-4 py-3 rounded-xl border bg-white outline-none focus:ring-2 focus:ring-primary"
                        />
                        <button type="submit" className="px-5 py-3 rounded-xl bg-primary text-white font-bold inline-flex items-center justify-center gap-1">
                            <Icon name="search" className="text-base" /> Search
                        </button>
                    </form>
                    {categories?.length > 0 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            <Link href="/courses" className="px-3 py-1.5 rounded-full text-xs font-bold border bg-white">All</Link>
                            {categories.slice(0, 12).map((c) => (
                                <Link
                                    key={c.id}
                                    href={`/courses?category=${c.slug}`}
                                    className="px-3 py-1.5 rounded-full text-xs font-bold border bg-white hover:border-primary hover:text-primary"
                                >
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <section className="max-w-7xl mx-auto px-4 py-10">
                {list.length === 0 ? (
                    <p className="text-center text-on-surface-variant py-16">No courses found.</p>
                ) : (
                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {list.map((course) => (
                            <CourseCard key={course.id} course={course} />
                        ))}
                    </div>
                )}
                {courses?.links && (
                    <div className="mt-10 flex flex-wrap justify-center gap-2">
                        {courses.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-3 py-1.5 rounded-lg text-sm font-bold border ${link.active ? 'bg-primary text-white border-primary' : 'bg-white'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
