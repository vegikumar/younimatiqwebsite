import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

const MODULES = [
    { slug: 'content_standards', title: 'Module 1: Content Standards', icon: 'menu_book', description: 'Learn class content guidelines, age-appropriate curriculum structuring, and Younimatiq quality standards.' },
    { slug: 'safety_privacy', title: 'Module 2: Safety & Privacy', icon: 'security', description: 'Understand learner privacy protection, anti-bypass communication policies, and child safety rules.' },
];

export default function PolicyTraining({ policyComplete = false, completedModules = [] }) {
    const { csrfToken } = usePage().props;
    const done = new Set(completedModules);

    return (
        <DashboardLayout title="Policy Training">
            <Head title="Mandatory Policy Training" />
            <div className="staff-page max-w-5xl">
                <PageHeader
                    variant="staff"
                    eyebrow="Mandatory Educator Onboarding"
                    title="Educator Policy & Safety Training"
                    description="Before teaching your first class, complete these policy training modules covering content standards, learner safety, and privacy."
                    actions={
                        policyComplete ? (
                            <span className="px-4 py-2 bg-tertiary-fixed text-tertiary rounded-xl font-bold text-xs flex items-center gap-1 shrink-0">
                                <span className="material-symbols-outlined text-sm">verified</span> Policy Training Complete!
                            </span>
                        ) : null
                    }
                />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                    {MODULES.map((m) => (
                        <PortalCard key={m.slug}>
                            <div className="space-y-4 min-w-0">
                                <div className="flex justify-between items-center gap-2 min-w-0">
                                    <h3 className="font-bold text-lg flex items-center gap-2 min-w-0 break-words">
                                        <span className="material-symbols-outlined text-primary shrink-0">{m.icon}</span> {m.title}
                                    </h3>
                                    {done.has(m.slug) ? <span className="px-2.5 py-0.5 bg-tertiary-fixed text-tertiary rounded-full text-[10px] font-bold shrink-0">COMPLETED</span> : null}
                                </div>
                                <div className="aspect-video bg-black rounded-2xl flex items-center justify-center text-white">
                                    <span className="material-symbols-outlined text-6xl text-white/80">play_circle</span>
                                </div>
                                <p className="text-xs text-on-surface-variant leading-relaxed break-words">{m.description}</p>
                                {!done.has(m.slug) ? (
                                    <form method="POST" action="/tutor/policy-training/complete">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="module_slug" value={m.slug} />
                                        <button type="submit" className="btn-enroll w-full py-2.5 text-xs font-bold">
                                            Mark {m.title.split(':')[0]} Completed
                                        </button>
                                    </form>
                                ) : null}
                            </div>
                        </PortalCard>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
