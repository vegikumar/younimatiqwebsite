import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import CourseCard from '../../../Components/CourseCard';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function Category({ category, courses = {}, seoTitle }) {
    const list = courses.data || [];

    return (
        <PublicLayout title={seoTitle || category.name}>
            <Head title={seoTitle || category.name} />
            <BrandHero className="mt-8 mb-8" eyebrow={category.name}>
                <h1 className="brand-title">{category.name}</h1>
                {category.description ? <p className="brand-description">{category.description}</p> : null}
            </BrandHero>
            <div className="pb-20 px-4 max-w-7xl mx-auto">
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {list.map((c) => (
                        <CourseCard key={c.id} course={c} />
                    ))}
                </div>
                <PaginationLinks links={courses.links} />
                <div className="mt-8 text-center">
                    <Link href="/courses" className="text-primary font-bold">
                        All courses
                    </Link>
                </div>
            </div>
        </PublicLayout>
    );
}
