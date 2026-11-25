import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import CourseCard from '../../../Components/CourseCard';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function BackToSchool({ courses = {}, campCategories = [] }) {
    const list = courses.data || [];

    return (
        <PublicLayout title="Back To School Camps">
            <Head title="Back To School Camps" />
            <BrandHero className="mt-8 mb-8" eyebrow="Summer & camps">
                <h1 className="brand-title">Back to school camps</h1>
                <p className="brand-description">Live camp-style classes to keep learning fun.</p>
            </BrandHero>
            {campCategories?.length ? (
                <div className="max-w-7xl mx-auto px-4 flex flex-wrap gap-2 mb-8">
                    {campCategories.map((c) => (
                        <Link key={c.id} href={`/courses/category/${c.slug}`} className="px-3 py-1.5 rounded-full border bg-white text-sm font-bold">
                            {c.name} ({c.courses_count})
                        </Link>
                    ))}
                </div>
            ) : null}
            <div className="pb-20 px-4 max-w-7xl mx-auto grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                {list.map((c) => (
                    <CourseCard key={c.id} course={c} />
                ))}
            </div>
            <PaginationLinks links={courses.links} />
        </PublicLayout>
    );
}
