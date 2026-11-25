import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import CourseCard from '../../Components/CourseCard';
import Icon from '../../Components/Icon';

export default function Home({ courses = [], topCourses = [], blogs = [], faqs = [] }) {
    const featured = topCourses?.length ? topCourses : courses?.slice(0, 3) || [];

    return (
        <AppLayout>
            <Head title="Home" />
            <section className="relative overflow-hidden bg-gradient-to-br from-[#EFE6FF] via-white to-[#F7F4FC]">
                <div className="max-w-7xl mx-auto px-4 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
                    <div>
                        <p className="text-primary font-bold text-sm mb-3 tracking-wide uppercase">Younimatiq</p>
                        <h1 className="text-4xl md:text-5xl font-extrabold leading-tight text-on-surface" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>
                            AI & STEM labs for curious kids
                        </h1>
                        <p className="mt-4 text-on-surface-variant text-lg max-w-lg">
                            1:1 live classes with expert tutors. Book a free consultation and find the right path for your child.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <a href="#leadModal" className="px-6 py-3 rounded-full bg-primary text-white font-bold shadow-lg">
                                Book a free consultation
                            </a>
                            <Link href="/courses" className="px-6 py-3 rounded-full border-2 border-primary text-primary font-bold">
                                Explore courses
                            </Link>
                        </div>
                    </div>
                    <div className="relative">
                        <img
                            src="/images/younimatiq-hero-poster-800.webp?v=20261114c"
                            alt="Younimatiq learning"
                            className="w-full rounded-[2rem] shadow-2xl object-cover aspect-[4/3]"
                        />
                    </div>
                </div>
            </section>

            <section className="max-w-7xl mx-auto px-4 py-16">
                <div className="flex items-end justify-between gap-4 mb-8">
                    <div>
                        <h2 className="text-3xl font-extrabold" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>Popular courses</h2>
                        <p className="text-on-surface-variant mt-1">Starts-from pricing — pick a plan that fits your child.</p>
                    </div>
                    <Link href="/courses" className="text-primary font-bold text-sm hidden sm:inline-flex items-center gap-1">
                        View all <Icon name="arrow_forward" className="text-base" />
                    </Link>
                </div>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {featured.map((c) => (
                        <CourseCard key={c.id} course={c} />
                    ))}
                </div>
            </section>

            {faqs?.length > 0 && (
                <section className="bg-white border-y border-outline-variant/30 py-16">
                    <div className="max-w-3xl mx-auto px-4">
                        <h2 className="text-3xl font-extrabold mb-8 text-center" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>FAQ</h2>
                        <div className="space-y-3">
                            {faqs.slice(0, 6).map((f) => (
                                <details key={f.id} className="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/40">
                                    <summary className="font-bold cursor-pointer">{f.question}</summary>
                                    <div className="mt-2 text-sm text-on-surface-variant prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: f.answer }} />
                                </details>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {blogs?.length > 0 && (
                <section className="max-w-7xl mx-auto px-4 py-16">
                    <h2 className="text-3xl font-extrabold mb-8" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>From the blog</h2>
                    <div className="grid md:grid-cols-3 gap-6">
                        {blogs.map((b) => (
                            <Link key={b.id} href={`/blog/${b.slug}`} className="bg-white rounded-2xl border p-5 hover:shadow-md transition">
                                <h3 className="font-bold text-lg mb-2 line-clamp-2">{b.title}</h3>
                                <p className="text-sm text-on-surface-variant line-clamp-3">{b.excerpt}</p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
