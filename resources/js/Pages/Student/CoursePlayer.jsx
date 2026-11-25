import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import Icon from '../../Components/Icon';

export default function CoursePlayer({ course, enrollment, progress = {} }) {
    const modules = course.modules || [];
    const [activeId, setActiveId] = useState(modules[0]?.id);

    const complete = (moduleId) => {
        router.post(`/student/courses/${course.id}/modules/${moduleId}/complete`, {}, { preserveScroll: true });
    };

    const active = modules.find((m) => m.id === activeId) || modules[0];

    return (
        <DashboardLayout title={course.title}>
            <Head title={course.title} />
            <div className="p-4 md:p-6 max-w-6xl mx-auto grid lg:grid-cols-12 gap-6">
                <aside className="lg:col-span-4 space-y-2">
                    <h1 className="font-bold text-lg mb-4">{course.title}</h1>
                    {modules.map((m) => (
                        <button
                            key={m.id}
                            type="button"
                            onClick={() => setActiveId(m.id)}
                            className={`w-full text-left p-3 rounded-xl border ${activeId === m.id ? 'border-primary bg-primary/5' : 'bg-white'}`}
                        >
                            <span className="font-bold text-sm">{m.title}</span>
                            {progress[m.id]?.completed ? (
                                <Icon name="check_circle" className="text-tertiary text-sm inline ml-2" />
                            ) : null}
                        </button>
                    ))}
                </aside>
                <main className="lg:col-span-8 clay-card p-6 bg-white min-h-[320px]">
                    {active ? (
                        <>
                            <h2 className="text-xl font-bold mb-4">{active.title}</h2>
                            <p className="text-on-surface-variant text-sm mb-6">{active.content_type || 'Lesson module'}</p>
                            {!progress[active.id]?.completed ? (
                                <button type="button" onClick={() => complete(active.id)} className="btn-enroll">
                                    Mark complete
                                </button>
                            ) : (
                                <p className="text-tertiary font-bold">Completed</p>
                            )}
                        </>
                    ) : (
                        <p className="text-on-surface-variant">No modules yet.</p>
                    )}
                    <Link href="/student/courses" className="inline-block mt-8 text-primary font-bold text-sm">
                        Back to my courses
                    </Link>
                </main>
            </div>
        </DashboardLayout>
    );
}
