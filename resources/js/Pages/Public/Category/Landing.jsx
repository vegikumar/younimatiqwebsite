import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import CourseCard from '../../../Components/CourseCard';

const TEMPLATE_COPY = {
    'math-landing': {
        eyebrow: 'Math mastery',
        blurb: '1:1 math coaching that builds confidence, fluency, and real problem-solving skills.',
        leadType: 'math',
    },
    'minecraft-landing': {
        eyebrow: 'Minecraft & coding',
        blurb: 'Turn play into coding, design, and STEM skills with expert coaches.',
        leadType: 'minecraft',
    },
    'robotics-landing': {
        eyebrow: 'Robotics & AI',
        blurb: 'Build, code, and engineer with project-based robotics and AI pathways.',
        leadType: 'robotics',
    },
    'category-landing': {
        eyebrow: 'Learn with Younimatiq',
        blurb: 'Personalized live classes tailored to your child.',
        leadType: 'universal',
    },
    default: {
        eyebrow: 'Younimatiq',
        blurb: 'Personalized live classes tailored to your child.',
        leadType: 'universal',
    },
};

export default function Landing({ category, courses = [], template = 'default' }) {
    const copy = TEMPLATE_COPY[template] || TEMPLATE_COPY.default;
    const title = category?.name || 'Category';

    return (
        <PublicLayout title={title}>
            <Head title={title} />
            <section className="relative overflow-hidden bg-gradient-to-br from-[#EFE6FF] via-white to-[#F7F4FC]">
                <div className="max-w-7xl mx-auto px-4 py-12 md:py-16">
                    <p className="text-primary font-bold text-sm uppercase tracking-wide mb-2">{copy.eyebrow}</p>
                    <h1 className="text-3xl md:text-5xl font-extrabold max-w-3xl">{title}</h1>
                    <p className="mt-3 text-on-surface-variant max-w-2xl text-lg">
                        {category?.description || copy.blurb}
                    </p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <a
                            href="#leadModal"
                            data-lead-type={copy.leadType}
                            data-category-id={category?.id || ''}
                            className="px-6 py-3 rounded-full bg-primary text-white font-bold shadow-lg"
                        >
                            Book a free consultation
                        </a>
                        <Link href="/courses" className="px-6 py-3 rounded-full border-2 border-primary text-primary font-bold">
                            All courses
                        </Link>
                    </div>
                </div>
            </section>
            <section className="max-w-7xl mx-auto px-4 py-12">
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
                    <h2 className="text-2xl font-extrabold">Courses in {title}</h2>
                    <a href="#leadModal" data-lead-type={copy.leadType} data-category-id={category?.id || ''} className="text-primary font-bold text-sm hover:underline">
                        Not sure where to start? Talk to us
                    </a>
                </div>
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
            <section className="max-w-7xl mx-auto px-4 pb-16">
                <div className="rounded-[2rem] bg-primary text-white p-8 md:p-12 text-center">
                    <h2 className="text-2xl md:text-3xl font-extrabold mb-3">Ready for a free trial class?</h2>
                    <p className="text-white/85 max-w-xl mx-auto mb-6">
                        Book a consultation and we&apos;ll match your child with the right coach and pathway.
                    </p>
                    <a
                        href="#leadModal"
                        data-lead-type={copy.leadType}
                        data-category-id={category?.id || ''}
                        className="inline-flex px-8 py-3 rounded-full bg-white text-primary font-bold"
                    >
                        Book free class
                    </a>
                </div>
            </section>
        </PublicLayout>
    );
}
