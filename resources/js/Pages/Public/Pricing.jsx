import { Head, Link, usePage } from '@inertiajs/react';
import Icon from '../../Components/Icon';
import PublicLayout from '../../Layouts/PublicLayout';

const FEATURES = [
    '1:1 personalized class',
    '1–2 live classes / week',
    'Track-matched curriculum & projects',
    'Progress reports after every class',
    'Flexible reschedule, easy make-ups',
];

const PLANS = {
    INR: { strike: '₹1,200', price: '₹600', save: 'SAVE 50%' },
    OMR: { strike: 'ر.ع. 6', price: 'ر.ع. 3', save: 'SAVE 50%' },
    AED: { strike: 'د.إ 60', price: 'د.إ 30', save: 'SAVE 50%' },
    DEFAULT: { strike: '$40', price: '$20', save: 'SAVE 50%' },
};

function PricingCard({ plan, onEnroll }) {
    return (
        <div className="bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative border-2 border-secondary max-w-md mx-auto w-full">
            <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
            <div className="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                <span className="text-xs font-semibold text-error line-through">{plan.strike}<span className="font-normal not-italic">/class</span></span>
                <span className="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">{plan.save}</span>
            </div>
            <div className="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">1:1 Program</div>
            <div className="flex items-baseline gap-2 mb-1">
                <span className="text-5xl font-extrabold text-on-surface">{plan.price}</span>
                <span className="text-on-surface-variant font-medium">/ class</span>
            </div>
            <p className="text-on-surface text-sm mb-6 mt-6">Full access to your child&apos;s track, with a dedicated instructor.</p>
            <ul className="space-y-4 mb-8 flex-1">
                {FEATURES.map((f) => (
                    <li key={f} className="flex items-start gap-3 text-sm text-on-surface">
                        <Icon name="check_circle" className="text-tertiary text-base mt-0.5 shrink-0" />
                        {f}
                    </li>
                ))}
            </ul>
            <button type="button" onClick={onEnroll} className="btn-primary w-full justify-center mt-6">Enroll Now</button>
        </div>
    );
}

export default function Pricing({ categories = [], faqs = [] }) {
    const { displayCurrency } = usePage().props;
    const currency = (displayCurrency || 'USD').toUpperCase();
    const plan = PLANS[currency] || PLANS.DEFAULT;

    const openEnroll = (e) => {
        if (typeof window.openEnrollModal === 'function') {
            window.openEnrollModal(e || { preventDefault() {} });
            return;
        }
        window.location.href = '/contact';
    };

    return (
        <PublicLayout title="Pricing & Plans">
            <Head title="Pricing & Plans | Younimatiq" />
            <div style={{ paddingTop: 80, minHeight: '100vh', background: '#f8fafc' }}>
                <section className="pt-6 pb-20 md:pt-8 md:pb-24 mx-4 lg:mx-8 overflow-hidden" id="pricing">
                    <div className="w-full px-6 md:px-12 max-w-7xl mx-auto">
                        <div className="text-center mb-10 md:mb-12">
                            <div className="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4 text-on-secondary-container bg-secondary-container border border-secondary-container">
                                Younimatiq • Pricing
                            </div>
                            <h2 className="text-4xl md:text-5xl font-extrabold text-on-surface mb-6" style={{ fontFamily: "var(--font-display, 'Baloo 2', sans-serif)", letterSpacing: '-0.02em' }}>
                                Invest in your child&apos;s future.
                            </h2>
                            <p className="text-lg text-on-surface max-w-3xl mx-auto">
                                1:1 Personalized classes, paced entirely to your child. Choose the plan that fits your goals.
                            </p>
                            <div className="flex items-center justify-center gap-1.5 mt-4 text-[#38B91F] text-sm font-bold">
                                <Icon name="how_to_reg" className="text-lg" />
                                <span>All tutors are background-checked</span>
                            </div>
                        </div>
                        <PricingCard plan={plan} onEnroll={openEnroll} />
                    </div>
                </section>

                <section className="py-16 md:py-20 bg-surface-container-low border-t border-outline-variant">
                    <div className="w-full px-6 md:px-12 max-w-7xl mx-auto text-center">
                        <h2 className="text-2xl md:text-3xl font-extrabold text-on-surface mb-10" style={{ fontFamily: "var(--font-display, 'Baloo 2', sans-serif)", letterSpacing: '-0.02em' }}>
                            Explore Courses by Category
                        </h2>
                        <div className="flex flex-wrap justify-center gap-4">
                            {categories.map((c) => (
                                <Link key={c.id || c.slug} href={`/courses?categories[]=${encodeURIComponent(c.slug)}`} className="bg-white border border-secondary-container hover:border-secondary hover:shadow-md text-on-secondary-container font-bold py-3 px-6 rounded-full transition-all duration-300">
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="py-20 md:py-24 bg-white">
                    <div className="w-full px-6 md:px-12 max-w-7xl mx-auto">
                        <div className="grid md:grid-cols-3 gap-12">
                            {[
                                { icon: 'workspace_premium', title: 'Expert Tutors', text: 'Our instructors are rigorously vetted and trained to provide the best learning experience for your child.' },
                                { icon: 'laptop_mac', title: 'Interactive Learning', text: 'Engaging, project-based curriculum designed to keep students motivated and excited to learn.' },
                                { icon: 'trending_up', title: 'Track Progress', text: 'Get detailed progress reports after every class to see exactly how your child is improving.', primary: true },
                            ].map((f) => (
                                <div key={f.title} className="text-center">
                                    <div className={`w-16 h-16 ${f.primary ? 'bg-primary-fixed text-primary' : 'bg-secondary-container text-on-secondary-container'} rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm`}>
                                        <Icon name={f.icon} className="text-3xl" />
                                    </div>
                                    <h3 className="text-xl font-bold text-on-surface mb-3">{f.title}</h3>
                                    <p className="text-on-surface">{f.text}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {faqs.length > 0 && (
                    <section className="py-20 md:py-24 bg-surface-container-low" id="faq">
                        <div className="w-full px-6 md:px-12 max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <h2 className="text-3xl md:text-4xl font-extrabold text-on-surface mb-6" style={{ fontFamily: "var(--font-display, 'Baloo 2', sans-serif)", letterSpacing: '-0.02em' }}>
                                    Frequently Asked Questions
                                </h2>
                                <p className="text-lg text-on-surface max-w-2xl mx-auto">Have questions? We&apos;re here to help you understand our pricing and programs better.</p>
                            </div>
                            <div className="max-w-3xl mx-auto space-y-6">
                                {faqs.map((faq) => (
                                    <div key={faq.id} className="bg-white rounded-2xl p-6 border border-outline-variant shadow-sm">
                                        <h3 className="text-lg font-bold text-on-surface mb-2">{faq.question}</h3>
                                        <div className="text-on-surface prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: faq.answer }} />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
