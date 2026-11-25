import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';
import Icon from '../../Components/Icon';

export default function Faq({ faqsByCategory = [], schemaFaqs = [] }) {
    const faqSchema = useMemo(() => {
        if (!schemaFaqs?.length) return null;
        return {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            mainEntity: schemaFaqs.map(({ question, answer }) => ({
                '@type': 'Question',
                name: question,
                acceptedAnswer: { '@type': 'Answer', text: answer },
            })),
        };
    }, [schemaFaqs]);

    return (
        <PublicLayout title="Younimatiq Faq">
            <Head title="Younimatiq Faq">
                <meta head-key="description" name="description" content="Find answers to common questions about Younimatiq online courses, classes, age groups, learning programs, and enrollment. Explore our FAQ for parents and students." />
                {faqSchema ? <script type="application/ld+json">{JSON.stringify(faqSchema)}</script> : null}
            </Head>

            <BrandHero className="mt-8 mb-8" eyebrow="FAQ">
                <h1 className="brand-title">
                    Frequently <span className="text-brand-accent">Asked Questions</span>
                </h1>
                <p className="brand-description">Everything you need to know about learning with us.</p>
            </BrandHero>

            <div className="pb-20 px-4 md:px-6 xl:px-8 max-w-4xl mx-auto space-y-12">
                {faqsByCategory.map(({ category, items }) => (
                    <section key={category}>
                        <h2 className="text-2xl font-bold text-on-surface mb-6">{category}</h2>
                        <div className="space-y-4">
                            {items.map((faq) => (
                                <details key={faq.id} className="group clay-card bg-white rounded-2xl overflow-hidden">
                                    <summary className="flex justify-between items-center p-6 cursor-pointer font-bold text-lg list-none gap-4 text-on-surface">
                                        {faq.question}
                                        <Icon name="expand_more" className="text-primary transition-transform group-open:rotate-180 shrink-0" />
                                    </summary>
                                    <div
                                        className="px-6 pb-6 text-on-surface-variant font-body-md border-t border-outline-variant/10 prose prose-sm max-w-none"
                                        dangerouslySetInnerHTML={{ __html: faq.answer }}
                                    />
                                </details>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </PublicLayout>
    );
}
