import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';
import FeaturedProjects from '../../Components/Home/FeaturedProjects';
import Icon from '../../Components/Icon';

function testimonialMediaUrl(path) {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
}

function TestimonialCard({ testimonial, duplicate }) {
    const text = testimonial.text ?? testimonial.content ?? '';
    const rating = Math.min(5, Math.max(0, Number(testimonial.rating) || 5));
    const avatarUrl = testimonialMediaUrl(testimonial.avatar_path);

    return (
        <div
            className="community-testimonial-card bg-white p-8 rounded-2xl shadow-sm border border-outline-variant flex flex-col relative h-full"
            {...(duplicate ? { 'data-clone': '1', 'aria-hidden': true } : {})}
        >
            <div className="flex text-warning mb-4 text-sm gap-0.5">
                {Array.from({ length: rating }).map((_, i) => (
                    <span key={`f-${i}`} className="material-symbols-outlined text-base" style={{ fontVariationSettings: "'FILL' 1" }}>
                        star
                    </span>
                ))}
                {Array.from({ length: 5 - rating }).map((_, i) => (
                    <span key={`e-${i}`} className="material-symbols-outlined text-base text-outline">
                        star
                    </span>
                ))}
            </div>
            <p className="text-on-surface leading-relaxed mb-6 italic flex-1">&ldquo;{text}&rdquo;</p>
            <div className="mt-auto flex items-center gap-4">
                {avatarUrl ? (
                    <img
                        src={avatarUrl}
                        alt={testimonial.author_name || ''}
                        className="w-12 h-12 rounded-full object-cover"
                        draggable={false}
                        data-clarity-mask="true"
                    />
                ) : (
                    <div className="w-12 h-12 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-lg">
                        {(testimonial.author_name || '?').charAt(0)}
                    </div>
                )}
                <div>
                    <h4 className="font-bold text-on-surface">{testimonial.author_name}</h4>
                    <p className="text-xs text-on-surface-variant">Verified Parent</p>
                </div>
            </div>
        </div>
    );
}

export default function Community({ featuredProjects = [], testimonials = [], likedProjectIds = [] }) {
    const { flash } = usePage().props;

    return (
        <PublicLayout title="Students Projects">
            <Head title="Students Projects">
                <meta
                    head-key="description"
                    name="description"
                    content="Explore innovative student projects in app development, game design, and robotics. Discover hands-on tech solutions, source code, and creative student builds on Younimatiq."
                />
            </Head>
            <style>{`
                .community-testimonials-marquee {
                    display: flex;
                    overflow: hidden;
                    width: 100vw;
                    position: relative;
                    left: 50%;
                    right: 50%;
                    margin-left: -50vw;
                    margin-right: -50vw;
                    mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                    -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
                }
                .community-testimonials-track {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 1.25rem;
                    width: max-content;
                    will-change: transform;
                    padding: 0.35rem 0;
                    animation: community-testimonials-scroll 85s linear infinite;
                }
                .community-testimonials-marquee:hover .community-testimonials-track,
                .community-testimonials-marquee:focus-within .community-testimonials-track {
                    animation-play-state: paused;
                }
                .community-testimonial-card {
                    width: min(85vw, 340px);
                    flex: 0 0 auto;
                }
                @keyframes community-testimonials-scroll {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                @media (prefers-reduced-motion: reduce) {
                    .community-testimonials-track {
                        animation: none !important;
                        overflow-x: auto;
                        width: 100%;
                        -webkit-overflow-scrolling: touch;
                        scrollbar-width: none;
                    }
                    .community-testimonials-track::-webkit-scrollbar { display: none; }
                    .community-testimonial-card[data-clone="1"] {
                        display: none;
                    }
                }
            `}</style>

            <BrandHero className="mt-8 mb-8" eyebrow="Younimatiq Community">
                <h1 className="brand-title">
                    Our <span className="text-brand-accent">Community</span>
                </h1>
            </BrandHero>

            <div className="pb-20 px-4 max-w-6xl mx-auto space-y-24 select-none" data-clarity-mask="true">
                {flash?.success ? (
                    <div className="rounded-2xl border border-tertiary-fixed-dim bg-tertiary-fixed text-tertiary px-5 py-4 font-medium">{flash.success}</div>
                ) : null}

                <section>
                    <div className="text-center mb-12">
                        <h2 className="text-3xl font-bold text-on-surface mb-4">Featured Student Projects</h2>
                        <p className="text-on-surface max-w-2xl mx-auto">
                            Discover the amazing games, apps, and robots built by Younimatiq students.
                        </p>
                    </div>
                    {featuredProjects.length > 0 ? (
                        <div id="projects">
                            <FeaturedProjects projects={featuredProjects} likedProjectIds={likedProjectIds} />
                        </div>
                    ) : (
                        <div className="text-center py-12 bg-surface-container-low rounded-2xl border border-outline-variant">
                            <Icon name="rocket_launch" className="text-4xl text-outline mb-3" />
                            <p className="text-on-surface-variant font-medium">New student projects coming soon!</p>
                        </div>
                    )}
                </section>

                <section>
                    <div className="text-center mb-12">
                        <h2 className="text-3xl font-bold text-on-surface mb-4">What Parents Are Saying</h2>
                        <p className="text-on-surface max-w-2xl mx-auto">
                            Hear from the families whose children are thriving at Younimatiq.
                        </p>
                    </div>
                    {testimonials.length > 0 ? (
                        <div className="community-testimonials-marquee" aria-label="Parent testimonials">
                            <div className="community-testimonials-track">
                                {[false, true].map((dup) =>
                                    testimonials.map((t) => (
                                        <TestimonialCard key={`${dup ? 'c' : 'o'}-${t.id}`} testimonial={t} duplicate={dup} />
                                    ))
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-12 bg-surface-container-low rounded-2xl border border-outline-variant">
                            <Icon name="reviews" className="text-4xl text-outline mb-3" />
                            <p className="text-on-surface-variant font-medium">Testimonials coming soon!</p>
                        </div>
                    )}
                </section>
            </div>
        </PublicLayout>
    );
}
