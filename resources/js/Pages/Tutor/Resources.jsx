import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

const CARDS = [
    {
        icon: 'menu_book',
        title: 'Educator Handbook',
        tone: 'bg-primary-container text-on-primary-container',
        text: 'Comprehensive guide on parent communication best practices, classroom engagement strategies, and teaching guidelines.',
        href: '/tutor/training',
        cta: 'Open Handbook',
    },
    {
        icon: 'campaign',
        title: 'Marketing & Growth Guide',
        tone: 'bg-secondary-container text-on-secondary-container',
        text: 'Learn how to build your teacher brand, optimize your public storefront bio, and maximize student re-enrollments.',
        href: '/tutor/profile',
        cta: 'View Marketing Tips',
    },
    {
        icon: 'extension',
        title: 'Business & Lesson Toolkit',
        tone: 'bg-tertiary-container text-on-tertiary-container',
        text: 'Slide deck templates, class setup checklists, and syllabus planning worksheets.',
        href: '/tutor/policy-training',
        cta: 'Open Toolkit',
    },
];

export default function Resources() {
    return (
        <DashboardLayout title="Resource Library">
            <Head title="Resource Library" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    title="Educator Resource Library & Toolkit"
                    description="Access the Educator Handbook, marketing templates, curriculum structuring guides, and business toolkits to scale your teaching brand."
                />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">
                    {CARDS.map((card) => (
                        <PortalCard key={card.title}>
                            <div className="flex flex-col gap-3 h-full min-w-0">
                                <div className={`w-12 h-12 rounded-2xl ${card.tone} font-bold flex items-center justify-center shrink-0`}>
                                    <span className="material-symbols-outlined text-2xl">{card.icon}</span>
                                </div>
                                <h3 className="font-bold text-lg text-on-surface break-words">{card.title}</h3>
                                <p className="text-xs text-on-surface-variant leading-relaxed flex-1 break-words">{card.text}</p>
                                <Link href={card.href} className="btn-outline-primary w-full py-2 text-xs font-bold mt-2 text-center">
                                    {card.cta}
                                </Link>
                            </div>
                        </PortalCard>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
