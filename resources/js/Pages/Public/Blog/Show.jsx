import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import Icon from '../../../Components/Icon';

export default function Show({ blog, seoTitle, seoDescription, seoImage }) {
    const eyebrow = [blog.published_at_label, blog.author ?? 'Younimatiq'].filter(Boolean).join(' · ');

    return (
        <PublicLayout title={seoTitle}>
            <Head title={seoTitle}>
                {seoDescription ? <meta head-key="description" name="description" content={seoDescription} /> : null}
                {seoImage ? <meta head-key="og:image" property="og:image" content={seoImage} /> : null}
            </Head>

            <BrandHero className="mt-8 mb-8" eyebrow={eyebrow}>
                <h1 className="brand-title">{blog.title}</h1>
                {blog.excerpt ? <p className="brand-description">{blog.excerpt}</p> : null}
            </BrandHero>

            <article className="pb-20 px-4 md:px-8 max-w-4xl mx-auto">
                {blog.featured_image ? (
                    <img src={blog.featured_image} alt={blog.title} className="w-full rounded-[2rem] shadow-xl mb-8" />
                ) : null}

                <div className="clay-card p-5 md:p-8 lg:p-6 md:p-12 bg-white">
                    <div
                        className="prose prose-lg max-w-none text-on-surface leading-relaxed space-y-4"
                        dangerouslySetInnerHTML={{ __html: blog.content }}
                    />
                </div>

                <div className="mt-16 pt-8 border-t border-outline-variant/20 flex justify-between items-center flex-wrap gap-4">
                    <Link href="/blog" className="link-primary inline-flex items-center gap-2">
                        <Icon name="arrow_back" /> Back to Blog
                    </Link>
                    <Link href="/register/student" className="btn-primary">
                        Join Younimatiq
                    </Link>
                </div>
            </article>
        </PublicLayout>
    );
}
