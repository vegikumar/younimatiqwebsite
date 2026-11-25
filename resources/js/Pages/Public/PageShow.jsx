import { Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';

export default function PageShow({ page, seoTitle, seoDescription }) {
    return (
        <PublicLayout title={seoTitle || page.title}>
            <Head title={seoTitle || page.title}>
                {seoDescription ? <meta head-key="description" name="description" content={seoDescription} /> : null}
            </Head>
            <BrandHero className="mt-8 mb-8">
                <h1 className="brand-title">{page.title}</h1>
            </BrandHero>
            <article className="max-w-4xl mx-auto px-4 pb-20 prose prose-lg" dangerouslySetInnerHTML={{ __html: page.content || '' }} />
        </PublicLayout>
    );
}
