import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import CourseCard from '../../../Components/CourseCard';

const TOPIC_COPY = {
    math: { title: 'Math that clicks', subtitle: 'Live 1:1 math coaching tailored to your child.' },
    minecraft: { title: 'Minecraft reimagined', subtitle: 'Turn play into coding, design, and STEM skills.' },
    robotics: { title: 'Robotics & AI', subtitle: 'Build, code, and engineer with expert coaches.' },
    robotic: { title: 'Robotics & AI', subtitle: 'Build, code, and engineer with expert coaches.' },
};

export default function Topic({ topic, courses = [] }) {
    const copy = TOPIC_COPY[topic] || { title: 'Explore courses', subtitle: 'Personalized live classes for kids.' };

    return (
        <PublicLayout title={copy.title}>
            <Head title={copy.title} />
            <section className="max-w-7xl mx-auto px-4 py-14">
                <h1 className="text-4xl font-extrabold">{copy.title}</h1>
                <p className="text-lg text-on-surface-variant mt-2 max-w-2xl">{copy.subtitle}</p>
                <div className="mt-6 flex gap-3">
                    <a href="#leadModal" data-lead-type={topic || 'universal'} className="btn-enroll">
                        Book free class
                    </a>
                    <Link href="/courses" className="btn-primary">
                        Browse catalog
                    </Link>
                </div>
            </section>
            <section className="max-w-7xl mx-auto px-4 pb-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {courses.map((c) => (
                    <CourseCard key={c.id} course={c} />
                ))}
            </section>
        </PublicLayout>
    );
}
