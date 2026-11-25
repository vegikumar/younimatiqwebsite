import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import CourseCard from '../../../Components/CourseCard';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function Index({ courses = {}, categories = [] }) {
    const list = courses?.data || [];

    return (
        <PublicLayout title="Online Courses for Kids">
            <Head title="Online Courses for Kids" />
            <BrandHero className="mt-8 mb-8" eyebrow="Course Catalog">
                <h1 className="brand-title">
                    Explore <span className="text-brand-accent">All Courses</span>
                </h1>
                <p className="brand-description">Find the perfect online class for your child&apos;s age and interests.</p>
            </BrandHero>
            <div className="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto">
                <div className="flex flex-col md:flex-row gap-8">
                    <aside className="w-full md:w-1/4">
                        <h3 className="font-bold text-lg mb-4 text-on-surface">Filter By Category</h3>
                        <ul className="space-y-2">
                            {categories.map((category) => (
                                <li key={category.id}>
                                    <Link href={`/courses/category/${category.slug}`} className="text-primary hover:underline">
                                        {category.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </aside>
                    <div className="w-full md:w-3/4">
                        <p className="text-on-surface-variant font-medium mb-6">
                            Showing <span className="font-bold text-primary">{courses.meta?.from ?? 0}–{courses.meta?.to ?? 0}</span> of{' '}
                            <span className="font-bold text-primary">{courses.meta?.total ?? list.length}</span>
                        </p>
                        {list.length === 0 ? (
                            <div className="clay-card p-12 text-center text-on-surface-variant">
                                <p className="font-bold text-lg">No courses found matching your criteria.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                {list.map((course) => (
                                    <CourseCard key={course.id} course={course} />
                                ))}
                            </div>
                        )}
                        <PaginationLinks links={courses.links} />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
