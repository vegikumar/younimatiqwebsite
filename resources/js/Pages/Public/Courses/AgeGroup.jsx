import { Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import CourseCard from '../../../Components/CourseCard';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function AgeGroup({ group, label, courses = {} }) {
    const list = courses.data || [];

    return (
        <PublicLayout title={label || 'Courses by grade'}>
            <Head title={label || 'Courses by grade'} />
            <BrandHero className="mt-8 mb-8" eyebrow="Grade band">
                <h1 className="brand-title">{label}</h1>
            </BrandHero>
            <div className="pb-20 px-4 max-w-7xl mx-auto grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                {list.map((c) => (
                    <CourseCard key={c.id} course={c} />
                ))}
            </div>
            <PaginationLinks links={courses.links} />
        </PublicLayout>
    );
}
