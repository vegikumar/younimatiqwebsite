import { Head, Link, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import PublicLayout from '../../../Layouts/PublicLayout';
import CourseMarquee from '../../../Components/CourseMarquee';
import { getMarketingLanding } from '../../../data/marketingLandings';

function planMoney(usd, currency) {
    const c = String(currency || 'USD').toUpperCase();
    if (c === 'AED') return { symbol: 'د.إ', amount: Math.round(Number(usd) * 3.67) };
    if (c === 'OMR') return { symbol: 'ر.ع.', amount: Math.round(Number(usd) * 0.39 * 100) / 100 };
    return { symbol: '$', amount: Number(usd) };
}

function openLead(event, leadType, courseTitle = '') {
    event?.preventDefault?.();
    if (typeof window.openLeadModal === 'function') {
        window.openLeadModal(event || { preventDefault() {} }, leadType || 'universal', '', courseTitle || '');
    }
}

function CourseCard({ course, leadType }) {
    const href = course.href || (course.slug ? `/courses/${course.slug}` : '#');
    const title = course.landing_title || course.title;
    const description = (course.description || '').replace(/<[^>]+>/g, '');

    return (
        <div
            className="shrink-0 w-72 md:w-80 rounded-3xl overflow-hidden bg-white flex flex-col"
            style={{ boxShadow: 'rgba(30, 58, 138, 0.08) 0px 2px 20px' }}
        >
            <Link href={href} className="relative block w-full overflow-hidden aspect-square" style={{ background: 'rgb(226, 232, 240)' }}>
                {course.thumbnail_url ? (
                    <img
                        loading="lazy"
                        alt={title}
                        src={course.thumbnail_url}
                        className="absolute inset-0 w-full h-full object-cover"
                    />
                ) : (
                    <div className="h-full flex items-center justify-center text-primary font-bold">Course</div>
                )}
                {course.age_group ? (
                    <div className="absolute top-3 left-3 z-[2] px-3 py-1 rounded-full text-xs font-bold" style={{ background: 'rgb(239, 246, 255)', color: 'rgb(37, 99, 235)' }}>
                        {course.age_group}
                    </div>
                ) : null}
            </Link>
            <div className="p-6 flex flex-col flex-1">
                <h3 className="font-bold text-on-surface text-base mb-2" style={{ fontFamily: 'Sora, sans-serif' }}>
                    {title}
                </h3>
                <p className="text-sm text-on-surface leading-relaxed flex-1 line-clamp-3">{description}</p>
                <div className="mt-5 space-y-2">
                    <a
                        href="#leadModal"
                        onClick={(e) => openLead(e, leadType, course.title || title)}
                        className="block text-center py-2.5 rounded-full text-sm font-bold border-2 border-[#7e22ce] text-[#7e22ce] hover:bg-primary-fixed"
                    >
                        Book free class
                    </a>
                    <Link href={href} className="block text-center py-3 rounded-full text-sm font-bold text-white hover:opacity-90" style={{ background: 'rgb(34, 197, 94)' }}>
                        View Course
                    </Link>
                </div>
            </div>
        </div>
    );
}

function VideoShowcase({ videos }) {
    const [current, setCurrent] = useState(0);
    const videoRef = useRef(null);
    if (!videos?.length) return null;

    return (
        <section className="py-20 md:py-24 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center mb-12">
                    <div className="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style={{ background: 'rgb(254, 243, 199)', color: 'rgb(217, 119, 6)' }}>
                        Maker Moments
                    </div>
                    <h2 className="text-3xl md:text-4xl font-extrabold text-on-surface" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                        See Younimatiq Creators in Action.
                    </h2>
                </div>
                <div className="relative w-full rounded-3xl overflow-hidden mb-6" style={{ aspectRatio: '16 / 9', background: 'rgb(15, 23, 42)' }}>
                    <video
                        ref={videoRef}
                        key={videos[current]}
                        src={videos[current]}
                        className="w-full h-full object-contain bg-black"
                        controls
                        muted
                        autoPlay
                        playsInline
                        onEnded={() => setCurrent((i) => (i + 1) % videos.length)}
                    />
                    <div className="absolute bottom-4 left-4 px-3 py-1.5 rounded-lg text-sm font-semibold text-white" style={{ background: 'rgba(0,0,0,0.5)' }}>
                        Younimatiq Student Showcase
                    </div>
                </div>
                <div className="flex gap-4 overflow-x-auto pb-4" style={{ scrollbarWidth: 'none' }}>
                    {videos.map((src, index) =>
                        index === current ? null : (
                            <button
                                key={src}
                                type="button"
                                onClick={() => setCurrent(index)}
                                className="relative rounded-2xl overflow-hidden shrink-0 snap-start w-32 sm:w-40 md:w-56 lg:w-64"
                                style={{ aspectRatio: '3 / 2', background: 'rgb(226, 232, 240)' }}
                            >
                                <video src={`${src}#t=0.001`} className="w-full h-full object-cover" muted playsInline preload="metadata" />
                                <div className="absolute inset-0 flex items-center justify-center bg-black/30">
                                    <span className="material-symbols-outlined text-white text-3xl">play_arrow</span>
                                </div>
                            </button>
                        ),
                    )}
                </div>
            </div>
        </section>
    );
}

function PricingSection({ copy, leadType }) {
    const { displayCurrency } = usePage().props;
    const currency = String(displayCurrency || 'USD').toUpperCase();
    const isInr = currency === 'INR';

    return (
        <section
            className="py-20 md:py-24 rounded-[2rem] lg:rounded-[3rem] mx-4 lg:mx-8 my-12 overflow-hidden shadow-2xl"
            id="pricing"
            style={{ background: 'linear-gradient(135deg, #10b981 0%, #047857 100%)' }}
        >
            <div className="w-full px-6 md:px-12">
                <div className="text-center mb-16">
                    <div className="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4 text-white" style={{ background: 'rgba(255,255,255,0.2)', border: '1px solid rgba(255,255,255,0.3)' }}>
                        {copy.pricingBadge}
                    </div>
                    <h2 className="text-4xl md:text-5xl font-extrabold text-white mb-6" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                        {copy.pricingTitle}
                    </h2>
                    <p className="text-lg text-secondary-container max-w-3xl mx-auto">{copy.pricingSubtitle}</p>
                </div>

                {isInr ? (
                    <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        {copy.inrPlans.map((plan) => (
                            <div
                                key={plan.name}
                                className={`bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative ${plan.popular ? 'border-2 border-secondary' : 'border border-outline-variant'}`}
                            >
                                {plan.popular ? (
                                    <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">1:1 POPULAR</div>
                                ) : null}
                                <div className="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                                    <span className="text-xs font-semibold text-error line-through">
                                        {plan.was}
                                        <span className="font-normal">/class</span>
                                    </span>
                                    <span className="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">{plan.save}</span>
                                </div>
                                <div className="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">{plan.name}</div>
                                <div className="flex items-baseline gap-2 mb-1">
                                    <span className="text-5xl font-extrabold text-on-surface">{plan.price}</span>
                                    <span className="text-on-surface-variant font-medium">/ class</span>
                                </div>
                                <p className="text-on-surface text-sm mb-6 mt-6">{plan.blurb}</p>
                                <ul className="space-y-4 mb-8 flex-1">
                                    {plan.features.map((f) => (
                                        <li key={f} className="flex items-start gap-3 text-sm text-on-surface">
                                            <span className="material-symbols-outlined text-tertiary text-base mt-0.5">check_circle</span>
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                                <button type="button" onClick={(e) => openLead(e, leadType)} className="block w-full text-center py-3.5 rounded-xl font-bold text-white bg-secondary">
                                    Get Started
                                </button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="grid md:grid-cols-3 gap-8">
                        {copy.globalPlans.map((plan) => {
                            const price = planMoney(plan.priceUsd, currency);
                            const was = planMoney(plan.wasUsd, currency);
                            return (
                                <div key={plan.name} className={`bg-white rounded-3xl p-8 flex flex-col shadow-2xl relative ${plan.popular ? 'border-2 border-secondary' : ''}`}>
                                    {plan.popular ? (
                                        <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">MOST POPULAR</div>
                                    ) : null}
                                    <div className="absolute top-4 right-4 flex flex-col items-end gap-0.5">
                                        <span className="text-xs font-semibold text-error line-through">
                                            {was.symbol}
                                            {was.amount}
                                            <span className="font-normal">/class</span>
                                        </span>
                                        <span className="text-[10px] font-bold text-white bg-error rounded-full px-2 py-0.5 leading-none">{plan.save}</span>
                                    </div>
                                    <div className="text-sm font-bold tracking-wider text-on-secondary-container mb-2 uppercase">{plan.name}</div>
                                    <div className="flex items-baseline gap-2 mb-1">
                                        <span className="text-5xl font-extrabold text-on-surface">
                                            {price.symbol}
                                            {price.amount}
                                        </span>
                                        <span className="text-on-surface-variant font-medium">/ class</span>
                                    </div>
                                    <div className="inline-block px-3 py-1 rounded-md text-xs font-semibold mb-6 bg-tertiary-fixed text-on-tertiary-container w-fit">{plan.classes}</div>
                                    <p className="text-on-surface text-sm mb-6">Full access to your child&apos;s track, taught by expert instructors.</p>
                                    <ul className="space-y-4 mb-8 flex-1">
                                        {copy.planFeatures.map((f) => (
                                            <li key={f} className="flex items-start gap-3 text-sm text-on-surface">
                                                <span className="material-symbols-outlined text-tertiary text-base mt-0.5">check_circle</span>
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                    <button type="button" onClick={(e) => openLead(e, leadType)} className="block w-full text-center py-3.5 rounded-xl font-bold text-white bg-secondary">
                                        Get Started
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </section>
    );
}

function FaqSection({ faqs }) {
    const [open, setOpen] = useState(null);
    return (
        <section className="pb-20 md:pb-24 pt-12" style={{ background: '#fafbfc' }}>
            <div className="max-w-7xl mx-auto px-6">
                <h2 className="text-3xl md:text-4xl font-extrabold text-on-surface mb-10" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                    Frequently asked questions
                </h2>
                <div className="space-y-5">
                    {faqs.map((item, index) => {
                        const isOpen = open === index;
                        return (
                            <div key={item.q} className="bg-white border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                                <button type="button" className="w-full px-6 py-5 flex items-center justify-between text-left" onClick={() => setOpen(isOpen ? null : index)}>
                                    <span className="font-bold text-on-surface text-lg">{item.q}</span>
                                    <span className="text-on-secondary-container text-2xl font-bold ml-4 leading-none">{isOpen ? '×' : '+'}</span>
                                </button>
                                {isOpen ? <div className="px-6 pb-6 text-on-surface leading-relaxed">{item.a}</div> : null}
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export default function MarketingTopic({ topic = 'minecraft', courses = [], showPricing = true }) {
    const copy = getMarketingLanding(topic);
    const pricingEnabled = showPricing && copy.showPricing !== false;

    const heroBg = copy.heroBackground
        ? { background: `url('${copy.heroBackground}') center/cover no-repeat` }
        : { background: copy.heroGradient || 'linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%)' };

    return (
        <PublicLayout title={copy.title}>
            <Head title={copy.title}>
                <meta name="description" content={copy.description} />
                <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&display=swap" rel="stylesheet" />
            </Head>

            <div id="home" />

            <section className="relative min-h-[88vh] flex items-center overflow-hidden" style={{ ...heroBg, paddingTop: 24 }}>
                <div className="absolute inset-0 pointer-events-none" style={{ backgroundColor: copy.heroBackground ? 'rgba(0,0,0,0.8)' : 'transparent' }} />
                <div className="relative max-w-7xl mx-auto px-4 py-10 grid lg:grid-cols-2 gap-12 items-center z-10 w-full">
                    <div>
                        <div
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-6"
                            style={{ background: 'rgba(34, 197, 94, 0.25)', color: 'rgb(134, 239, 172)', border: '1px solid rgba(34, 197, 94, 0.4)' }}
                        >
                            <span>{copy.badge}</span>
                        </div>
                        <h1 className="text-4xl md:text-5xl xl:text-6xl font-extrabold text-white leading-tight mb-6" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                            {copy.heroTitle.map((part, i) =>
                                typeof part === 'string' ? (
                                    <span key={i}>{part}</span>
                                ) : (
                                    <span key={i} style={{ color: 'rgb(74, 222, 128)' }}>
                                        {part.accent}
                                    </span>
                                ),
                            )}
                        </h1>
                        <p className="text-lg text-white leading-relaxed mb-8 max-w-lg" style={{ opacity: 0.9 }}>
                            {copy.heroText}
                        </p>
                        <div className="flex flex-wrap gap-4">
                            <a href="#courses" className="px-7 py-4 rounded-full font-bold text-white text-base" style={{ background: 'rgb(34, 197, 94)' }}>
                                Explore Courses
                            </a>
                            <button type="button" onClick={(e) => openLead(e, copy.leadType)} className="px-7 py-4 rounded-full font-bold text-white border-2 border-white/40 hover:bg-white/10 text-base">
                                Book free class
                            </button>
                        </div>
                        <div className="mt-10 inline-flex items-center gap-3 px-5 py-3 rounded-2xl" style={{ background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(255,255,255,0.2)' }}>
                            <span className="text-[#f59e0b] tracking-wider">★★★★★</span>
                            <span className="text-white font-semibold">4.9</span>
                            <span className="text-white text-sm" style={{ opacity: 0.8 }}>
                                from 1,200+ parent reviews
                            </span>
                        </div>
                    </div>
                    <div className="flex justify-center lg:justify-end">
                        {copy.heroImage ? <img loading="lazy" src={copy.heroImage} alt="" className={`w-full ${copy.heroImageClass || 'object-contain'}`} /> : null}
                    </div>
                </div>
            </section>

            <section className="py-20 md:py-24" style={{ background: 'rgb(248, 250, 252)' }} id="how-it-works">
                <div className="w-full px-6 md:px-12">
                    <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12">
                        <div>
                            <div className="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style={{ background: 'rgb(239, 246, 255)', color: 'rgb(37, 99, 235)' }}>
                                Course Catalog
                            </div>
                            <h2 id="courses" className="text-3xl md:text-4xl font-extrabold text-on-surface" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                                {copy.coursesHeading}
                            </h2>
                        </div>
                        {copy.coursesCta ? (
                            <Link href={copy.coursesCta.href} className="shrink-0 text-sm font-semibold text-on-secondary-container underline underline-offset-2">
                                {copy.coursesCta.label}
                            </Link>
                        ) : null}
                    </div>
                    {courses.length ? (
                        <CourseMarquee>
                            {courses.map((course) => (
                                <CourseCard key={course.id || course.slug} course={course} leadType={copy.leadType} />
                            ))}
                        </CourseMarquee>
                    ) : (
                        <p className="text-on-surface-variant">Courses coming soon.</p>
                    )}
                </div>
            </section>

            <VideoShowcase videos={copy.videos} />

            <section className="py-20 md:py-24" style={{ background: 'linear-gradient(135deg, rgb(240, 249, 255) 0%, rgb(240, 253, 244) 100%)' }}>
                <div className="max-w-7xl mx-auto px-6">
                    <div className="text-center mb-14">
                        <div className="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-4" style={{ background: 'rgb(220, 252, 231)', color: 'rgb(22, 163, 74)' }}>
                            Why Choose Younimatiq
                        </div>
                        <h2 className="text-3xl md:text-4xl font-extrabold text-on-surface" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                            Built Different. On Purpose.
                        </h2>
                    </div>
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10">
                        {copy.why.map((item) => (
                            <div key={item.title} className="bg-white rounded-3xl p-7 flex flex-col gap-3" style={{ boxShadow: 'rgba(30, 58, 138, 0.07) 0px 2px 20px' }}>
                                <div className="text-4xl">{item.icon}</div>
                                <h3 className="font-bold text-on-surface text-base" style={{ fontFamily: 'Sora, sans-serif' }}>
                                    {item.title}
                                </h3>
                                <p className="text-sm text-on-surface leading-relaxed">{item.text}</p>
                            </div>
                        ))}
                    </div>
                    <div className="flex items-center justify-center gap-2 py-4 rounded-2xl" style={{ background: 'rgb(220, 252, 231)' }}>
                        <span className="text-xl">🌳</span>
                        <p className="text-sm font-semibold text-tertiary">For every 24 classes completed, Younimatiq plants a tree — because the next generation deserves a planet worth building for.</p>
                    </div>
                </div>
            </section>

            <section className="py-20 md:py-24 bg-white">
                <div className="w-full px-6 md:px-12">
                    <div className="text-center mb-12">
                        <h2 id="reviews" className="text-3xl md:text-4xl font-extrabold text-on-surface" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                            Parents Love Younimatiq
                        </h2>
                        <p className="text-on-surface-variant mt-3">Real stories from real families around the world.</p>
                    </div>
                    <CourseMarquee>
                        {copy.reviews.map((review) => (
                            <div
                                key={review.name + review.quote.slice(0, 24)}
                                className="shrink-0 rounded-3xl p-7 flex flex-col gap-4"
                                style={{ width: 340, background: 'rgb(248, 250, 252)', boxShadow: 'rgba(30, 58, 138, 0.06) 0px 2px 20px' }}
                            >
                                <div className="text-[#f59e0b] tracking-wider">★★★★★</div>
                                <p className="text-sm text-on-surface leading-relaxed flex-1">&ldquo;{review.quote}&rdquo;</p>
                                <div>
                                    <div className="font-bold text-on-surface">{review.name}</div>
                                    <div className="text-xs text-on-surface-variant">{review.meta}</div>
                                </div>
                            </div>
                        ))}
                    </CourseMarquee>
                    <div className="mt-16 text-center">
                        <p className="text-xs font-semibold uppercase tracking-widest text-outline">Trusted by families in 30+ countries</p>
                    </div>
                </div>
            </section>

            {pricingEnabled ? <PricingSection copy={copy} leadType={copy.leadType} /> : null}

            <FaqSection faqs={copy.faqs} />

            <section className="py-20 md:py-24" style={{ background: 'linear-gradient(135deg, rgb(30, 58, 138) 0%, rgb(29, 78, 216) 100%)' }}>
                <div className="relative max-w-3xl mx-auto px-6 text-center">
                    <div className="text-5xl mb-6">🚀</div>
                    <h2 id="consult" className="text-3xl md:text-5xl font-extrabold text-white mb-5" style={{ fontFamily: 'Sora, sans-serif', letterSpacing: '-0.02em' }}>
                        Ready to Turn Screen Time Into Skill Time?
                    </h2>
                    <p className="text-secondary-container text-lg mb-8">{copy.consultText}</p>
                    <button type="button" onClick={(e) => openLead(e, copy.leadType)} className="inline-block px-10 py-4 rounded-full font-bold text-white text-lg shadow-2xl hover:scale-105 mb-10" style={{ background: 'rgb(34, 197, 94)' }}>
                        Book Free Consultation
                    </button>
                </div>
            </section>
        </PublicLayout>
    );
}
