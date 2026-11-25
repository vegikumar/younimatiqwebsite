import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';
import Icon from '../../../Components/Icon';

export default function Index({ blogs }) {
    const items = blogs?.data ?? [];
    const links = blogs?.links ?? [];

    return (
        <PublicLayout title="Blog | Younimatiq">
            <Head title="Blog | Younimatiq">
                <meta head-key="description" name="description" content="Tips, stories, and insights from the Younimatiq learning community." />
            </Head>

            <BrandHero className="mt-8 mb-8" eyebrow="Learning Hub">
                <h1 className="brand-title">
                    Younimatiq <span className="text-brand-accent">Blog</span>
                </h1>
                <p className="brand-description">Tips, stories, and insights from our learning community.</p>
            </BrandHero>

            <div className="pb-20 px-4 md:px-8 max-w-7xl mx-auto">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:p-8">
                    {items.length === 0 ? (
                        <div className="col-span-full clay-card p-6 md:p-16 text-center text-on-surface-variant">
                            <Icon name="article" className="text-6xl mb-4 block mx-auto" />
                            <p className="font-bold text-lg">No blog posts published yet.</p>
                        </div>
                    ) : (
                        items.map((blog) => (
                            <article key={blog.id} className="clay-card overflow-hidden flex flex-col">
                                {blog.featured_image ? (
                                    <img
                                        src={blog.featured_image}
                                        alt={blog.title}
                                        className="w-full aspect-video object-cover bg-surface-container-low"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full aspect-video bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                                        <Icon name="article" className="text-6xl text-primary/40" />
                                    </div>
                                )}
                                <div className="p-6 flex-1 flex flex-col">
                                    <p className="text-xs font-bold text-secondary uppercase tracking-wider mb-2">{blog.published_at_label}</p>
                                    <h2 className="font-headline-md font-bold text-on-surface mb-3 line-clamp-2">{blog.title}</h2>
                                    <p className="text-on-surface-variant text-sm mb-4 flex-1 line-clamp-3">{blog.excerpt_preview || blog.excerpt}</p>
                                    <Link href={`/blog/${blog.slug}`} className="link-primary inline-flex items-center gap-2">
                                        Read More <Icon name="arrow_forward" className="text-sm" />
                                    </Link>
                                </div>
                            </article>
                        ))
                    )}
                </div>

                {links.length > 3 ? (
                    <div className="mt-12 flex flex-wrap gap-2 justify-center">
                        {links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    className={`px-3 py-1 rounded-lg text-sm font-bold ${link.active ? 'bg-primary text-white' : 'bg-surface-container-low'}`}
                                    preserveState
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span key={i} className="px-3 py-1 text-on-surface-variant text-sm" dangerouslySetInnerHTML={{ __html: link.label }} />
                            ),
                        )}
                    </div>
                ) : null}
            </div>
        </PublicLayout>
    );
}
