import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import Icon from '../../Components/Icon';

export default function CareerExplorer({ pathways = [], gradeBandLabel = '', grade = null }) {
    return (
        <DashboardLayout title="Career Explorer">
            <Head title="Career Explorer" />
            <div className="p-5 md:p-8 max-w-5xl mx-auto space-y-6">
                <PageHeader
                    eyebrow={gradeBandLabel || 'Grades 6–12'}
                    title="Explore future pathways"
                    description="Age-appropriate career ideas mapped to skills you can practice on Younimatiq. Exploration only — not job placement."
                />
                {grade ? <p className="text-sm text-on-surface-variant">Showing pathways for grade {grade}.</p> : null}

                <div className="grid md:grid-cols-2 gap-4">
                    {pathways.map((p) => (
                        <article key={p.id} className="clay-card p-5 space-y-3">
                            <div className="flex items-start gap-3">
                                <span className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                    <Icon name={p.icon || 'rocket_launch'} className="text-xl" />
                                </span>
                                <div>
                                    <h2 className="font-bold text-lg">{p.title}</h2>
                                    <p className="text-sm text-primary font-bold">{p.tagline}</p>
                                </div>
                            </div>
                            <p className="text-sm text-on-surface-variant">{p.description}</p>
                            {(p.skill_tags || []).length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {p.skill_tags.map((tag) => (
                                        <span key={tag} className="px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface-container-high">
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            ) : null}
                            {(p.courses || []).length > 0 ? (
                                <ul className="text-sm space-y-1">
                                    {p.courses.map((c) => (
                                        <li key={c.id}>
                                            <Link href={c.url || '/student/courses'} className="font-bold text-primary">
                                                {c.title}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <Link href="/student/courses" className="text-sm font-bold text-primary">
                                    Browse related courses →
                                </Link>
                            )}
                        </article>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
