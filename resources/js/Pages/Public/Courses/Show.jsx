import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PublicLayout from '../../../Layouts/PublicLayout';
import CourseCard from '../../../Components/CourseCard';
import Icon from '../../../Components/Icon';
import { formatPrice } from '../../../lib/currency';

const CURRICULUM_PREVIEW = 5;

function extractYouTubeId(url) {
    if (!url) return '';
    const match = String(url).match(
        /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i
    );
    return match?.[1] ?? '';
}

function StarRow({ rating, size = 'text-sm' }) {
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((i) => (
                <span
                    key={i}
                    className={`material-symbols-outlined text-tertiary ${size}`}
                    style={{ fontVariationSettings: `'FILL' ${i <= rating ? 1 : 0}` }}
                    aria-hidden="true"
                >
                    star
                </span>
            ))}
        </div>
    );
}

function ModuleTopicCard({ module, index }) {
    return (
        <div className="clay-card p-5 bg-white flex items-start gap-4">
            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                {index + 1}
            </div>
            <div className="min-w-0">
                <h3 className="font-bold text-lg text-on-surface">{module.title}</h3>
            </div>
        </div>
    );
}

export default function Show({
    course,
    seoTitle,
    isEnrolled = false,
    inWishlist = false,
    userReview = null,
    reviewStats = {},
    relatedCourses = [],
    faqs = [],
}) {
    const { displayCurrency, auth, csrfToken } = usePage().props;
    const [showAllTopics, setShowAllTopics] = useState(false);
    const [classesCount, setClassesCount] = useState(1);
    const [mediaSlide, setMediaSlide] = useState(0);
    const [openFaq, setOpenFaq] = useState(0);
    const [reviewRating, setReviewRating] = useState(userReview?.rating ?? 5);

    const courseKey = course.slug || course.id;
    const category = course.categories?.[0] ?? null;
    const ageLabel = course.age_label ?? null;
    const reviewCount = reviewStats.count ?? 0;
    const reviewAverage = reviewStats.average ?? 0;

    const { symbol, amount: pricePerClass } = formatPrice(course, displayCurrency);
    const currency = String(displayCurrency || 'USD').toUpperCase();
    const totalAmount = pricePerClass * classesCount;
    const checkoutHref = `/checkout/${courseKey}?classes=${classesCount}&currency=${currency}&amount=${totalAmount}`;
    const playerHref = `/student/courses/${courseKey}/player`;

    const sortedModules = useMemo(
        () => [...(course.modules ?? [])].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
        [course.modules]
    );
    const sortedUnits = useMemo(
        () =>
            [...(course.units ?? [])].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)).map((unit) => ({
                ...unit,
                modules: [...(unit.modules ?? [])].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
            })),
        [course.units]
    );

    const hasUnits = sortedUnits.length > 0;
    const visibleTopics = showAllTopics ? sortedModules : sortedModules.slice(0, CURRICULUM_PREVIEW);
    const hasMedia = Boolean(course.thumbnail_url || course.video_url);
    const hasImage = Boolean(course.thumbnail_url);
    const hasVideo = Boolean(course.video_url);
    const slideCount = hasImage && hasVideo ? 2 : hasImage || hasVideo ? 1 : 0;
    const videoSlideIndex = hasImage ? 1 : 0;
    const ytPreviewId = extractYouTubeId(course.video_url);
    const ytWelcomeId = extractYouTubeId(course.welcome_video_url);
    const isLoggedIn = auth?.guard === 'web' && auth?.user;

    const clampClasses = (value) => {
        const n = Number(value);
        if (Number.isNaN(n) || n < 1) return 1;
        if (n > 48) return 48;
        return Math.floor(n);
    };

    return (
        <PublicLayout title={seoTitle || course.title}>
            <Head title={seoTitle || course.title} />
            <div className="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto mt-8">
                <Link href="/courses" className="inline-flex items-center gap-2 text-sm font-bold text-primary mb-6 hover:underline">
                    <Icon name="arrow_back" className="text-base" /> Back to Catalog
                </Link>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 xl:gap-5 items-start">
                    <div className="lg:col-span-8 space-y-8 order-2 lg:order-1">
                        <section className="clay-card p-6 md:p-8 bg-white" id="about">
                            <h2 className="section-heading mb-4">
                                About this <span className="accent-green">Course</span>
                            </h2>
                            <div
                                className="text-on-surface-variant leading-relaxed prose max-w-none"
                                dangerouslySetInnerHTML={{ __html: course.description || '' }}
                            />
                        </section>

                        <section className="clay-card p-6 md:p-8 bg-white" id="who-for">
                            <h2 className="section-heading mb-4">
                                Who this course is <span className="accent-green">for</span>
                            </h2>
                            <ul className="space-y-3 text-on-surface">
                                <li className="flex items-start gap-3">
                                    <Icon name="child_care" className="text-primary shrink-0" />
                                    <span>
                                        {ageLabel
                                            ? `Learners in the ${ageLabel} range`
                                            : 'Kids and teens ready for guided live learning'}{' '}
                                        who enjoy creative, hands-on projects.
                                    </span>
                                </li>
                                <li className="flex items-start gap-3">
                                    <Icon name="psychology" className="text-primary shrink-0" />
                                    <span>
                                        Beginners and intermediate students interested in{' '}
                                        {category?.name ?? 'this subject'} — no prior expert knowledge required.
                                    </span>
                                </li>
                                <li className="flex items-start gap-3">
                                    <Icon name="diversity_3" className="text-primary shrink-0" />
                                    <span>
                                        Parents looking for structured, live online classes with clear progress and a
                                        final showcase.
                                    </span>
                                </li>
                            </ul>
                        </section>

                        {course.learning_outcomes?.length ? (
                            <section className="clay-card p-6 md:p-8 bg-white" id="outcomes">
                                <h2 className="section-heading mb-6">
                                    Learning <span className="accent-green">Outcomes</span>
                                </h2>
                                <p className="text-on-surface-variant mb-5 text-sm">By the end of this course, students will:</p>
                                <ul className="space-y-3">
                                    {course.learning_outcomes.map((outcome, i) => (
                                        <li key={i} className="flex items-start gap-3">
                                            <Icon name="check_circle" className="text-primary shrink-0 mt-0.5" />
                                            <span className="text-on-surface">{outcome}</span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        <section className="clay-card p-6 md:p-8 bg-white" id="requirements">
                            <h2 className="section-heading mb-6">
                                What you&apos;ll <span className="accent-green">need</span>
                            </h2>
                            <ul className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {[
                                    { icon: 'laptop_mac', text: 'A laptop or desktop with a stable internet connection' },
                                    { icon: 'headphones', text: 'Headphones or a quiet space for live sessions' },
                                    { icon: 'mic', text: 'Working microphone and camera for interaction' },
                                    { icon: 'schedule', text: 'Consistent attendance for the live class schedule' },
                                ].map((item) => (
                                    <li key={item.icon} className="flex items-start gap-3 p-3 rounded-2xl bg-surface-container-low">
                                        <Icon name={item.icon} className="text-primary shrink-0" />
                                        <span className="text-on-surface">{item.text}</span>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {!hasUnits && sortedModules.length > 0 ? (
                            <section id="curriculum">
                                <h2 className="section-heading mb-6">
                                    Syllabus / <span className="accent-green">Class Topics</span>
                                </h2>
                                <div className="space-y-4">
                                    {visibleTopics.map((module, index) => (
                                        <ModuleTopicCard key={module.id} module={module} index={index} />
                                    ))}
                                </div>
                                {sortedModules.length > CURRICULUM_PREVIEW ? (
                                    <div className="mt-6 text-center">
                                        <button
                                            type="button"
                                            onClick={() => setShowAllTopics((v) => !v)}
                                            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full border border-primary text-primary hover:bg-primary/5 transition-colors font-semibold text-sm"
                                        >
                                            {showAllTopics ? 'Show Less' : 'Show More'}
                                            <Icon name={showAllTopics ? 'expand_less' : 'expand_more'} className="text-sm" />
                                        </button>
                                    </div>
                                ) : null}
                            </section>
                        ) : null}

                        {hasUnits ? (
                            <section id="syllabus">
                                <h2 className="section-heading mb-6">
                                    Syllabus <span className="accent-green">Units &amp; Modules</span>
                                </h2>
                                <div className="space-y-6">
                                    {sortedUnits.map((unit) => (
                                        <div key={unit.id} className="clay-card p-5 md:p-6 bg-white">
                                            <div className="flex items-start gap-4 mb-4">
                                                <div className="flex-shrink-0 w-10 h-10 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold">
                                                    {unit.order}
                                                </div>
                                                <div>
                                                    <h3 className="font-bold text-lg text-on-surface">{unit.title}</h3>
                                                    {unit.description ? (
                                                        <p className="text-on-surface-variant text-sm mt-1">{unit.description}</p>
                                                    ) : null}
                                                </div>
                                            </div>
                                            <div className="space-y-3 pl-4 border-l-2 border-primary/20">
                                                {unit.modules?.length ? (
                                                    unit.modules.map((module, index) => (
                                                        <div
                                                            key={module.id}
                                                            className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low"
                                                        >
                                                            <div className="flex-shrink-0 w-7 h-7 rounded-full bg-white text-primary flex items-center justify-center font-bold text-xs border border-outline-variant">
                                                                {index + 1}
                                                            </div>
                                                            <div className="min-w-0">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <h4 className="font-bold text-on-surface">{module.title}</h4>
                                                                    {module.content_type ? (
                                                                        <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                                                                            {module.content_type}
                                                                        </span>
                                                                    ) : null}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))
                                                ) : (
                                                    <p className="text-on-surface-variant text-sm italic">
                                                        Lessons for this unit are being added.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        ) : null}

                        {!hasUnits && sortedModules.length === 0 ? (
                            <section id="curriculum">
                                <h2 className="section-heading mb-6">
                                    Course <span className="accent-green">Curriculum</span>
                                </h2>
                                <div className="bg-surface-container-low p-5 rounded-2xl">
                                    <p className="text-on-surface-variant text-sm">Curriculum details are being updated.</p>
                                </div>
                            </section>
                        ) : null}

                        <section id="our-guarantee" className="mt-12">
                            <h2 className="section-heading mb-6">
                                Our <span className="accent-green">Guarantee</span>
                            </h2>
                            <div className="space-y-4">
                                <div className="clay-card p-5 bg-white flex items-start gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                                        1
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-lg text-on-surface">Expert designed Curriculum</h3>
                                        <p className="text-on-surface-variant text-sm mt-1">
                                            Designed by MIT, Harvard Experts keeping kids in mind
                                        </p>
                                    </div>
                                </div>
                                <details className="group clay-card bg-white overflow-hidden cursor-pointer">
                                    <summary className="flex items-center justify-between p-5 list-none">
                                        <div className="flex items-center gap-4">
                                            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                                                2
                                            </div>
                                            <h3 className="font-bold text-lg text-on-surface">100% Satisfaction Guarantee</h3>
                                        </div>
                                        <Icon name="expand_more" className="text-primary transition-transform group-open:rotate-180 shrink-0" />
                                    </summary>
                                    <div className="px-5 pb-5 pl-4 sm:pl-[72px] text-on-surface-variant text-sm">
                                        We&apos;ll help you find the right class always, else we follow the{' '}
                                        <strong className="text-primary">Younimatiq Learning Guarantee</strong>.
                                    </div>
                                </details>
                                <div className="clay-card p-5 bg-white flex items-start gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                                        3
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-lg text-on-surface">Expert teachers</h3>
                                        <p className="text-on-surface-variant text-sm mt-1">
                                            World class, background checked, certified and highly trained teachers with 5+
                                            years of experience in their fields.
                                        </p>
                                    </div>
                                </div>
                                <div className="clay-card p-5 bg-white flex items-start gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center font-bold">
                                        4
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-lg text-on-surface">Downloadable certificate</h3>
                                        <p className="text-on-surface-variant text-sm mt-1">
                                            Show the world you did it !! You really did it!!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {course.welcome_message || course.welcome_video_url ? (
                            <div className="clay-card p-6 bg-surface-container-low">
                                <h2 className="font-headline-md font-bold mb-4">Welcome Message</h2>
                                {course.welcome_video_url ? (
                                    <div className="mb-4 aspect-video rounded-xl overflow-hidden bg-black">
                                        {ytWelcomeId ? (
                                            <iframe
                                                className="w-full h-full"
                                                src={`https://www.youtube.com/embed/${ytWelcomeId}?enablejsapi=1`}
                                                title="Welcome video"
                                                allowFullScreen
                                            />
                                        ) : (
                                            <video
                                                className="w-full h-full object-contain"
                                                controls
                                                preload="none"
                                                poster={course.thumbnail_url || undefined}
                                            >
                                                <source src={course.welcome_video_url} type="video/mp4" />
                                            </video>
                                        )}
                                    </div>
                                ) : null}
                                {course.welcome_message ? (
                                    <div
                                        className="prose prose-on-surface max-w-none leading-relaxed"
                                        dangerouslySetInnerHTML={{ __html: course.welcome_message }}
                                    />
                                ) : null}
                            </div>
                        ) : null}

                        <section className="clay-card p-6 md:p-8 bg-white" id="certificate">
                            <div className="flex flex-col sm:flex-row gap-6 items-start">
                                <div className="w-16 h-16 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                                    <Icon name="military_tech" className="text-4xl" />
                                </div>
                                <div>
                                    <h2 className="section-heading mb-3">
                                        Certificate of <span className="accent-green">Completion</span>
                                    </h2>
                                    <p className="text-on-surface-variant leading-relaxed">
                                        Students who complete the modules and final project receive a Younimatiq certificate
                                        they can share with family, teachers, and future programs — a real milestone for their
                                        learning journey.
                                    </p>
                                </div>
                            </div>
                        </section>

                        {faqs?.length ? (
                            <section className="clay-card p-6 md:p-8 bg-white" id="faq">
                                <h2 className="section-heading mb-2">
                                    Frequently Asked <span className="accent-green">Questions</span>
                                </h2>
                                <p className="text-on-surface-variant text-sm mb-6">
                                    Common questions about live classes and enrollment.
                                </p>
                                <div className="space-y-3">
                                    {faqs.map((f, index) => (
                                        <div key={f.id} className="rounded-2xl border border-outline-variant/20 overflow-hidden">
                                            <button
                                                type="button"
                                                onClick={() => setOpenFaq(openFaq === index ? -1 : index)}
                                                className="w-full p-4 md:p-5 text-left flex justify-between items-center gap-4 hover:bg-surface-container-low transition-colors"
                                            >
                                                <span className="font-bold text-on-surface">{f.question}</span>
                                                <Icon
                                                    name="expand_more"
                                                    className={`text-primary transition-transform shrink-0 ${openFaq === index ? 'rotate-180' : ''}`}
                                                />
                                            </button>
                                            {openFaq === index ? (
                                                <div
                                                    className="px-4 md:px-5 pb-5 text-on-surface-variant leading-relaxed prose prose-sm max-w-none"
                                                    dangerouslySetInnerHTML={{ __html: f.answer }}
                                                />
                                            ) : null}
                                        </div>
                                    ))}
                                </div>
                                <Link href="/faq" className="inline-flex items-center gap-1 mt-6 font-bold text-primary hover:underline">
                                    View all FAQs
                                    <Icon name="arrow_forward" className="text-base" />
                                </Link>
                            </section>
                        ) : null}

                        {relatedCourses?.length ? (
                            <section id="related">
                                <h2 className="section-heading mb-6">
                                    Related <span className="accent-green">Courses</span>
                                </h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                                    {relatedCourses.map((c) => (
                                        <CourseCard key={c.id} course={c} hideBookFreeClass />
                                    ))}
                                </div>
                            </section>
                        ) : null}
                    </div>

                    <aside className="lg:col-span-4 space-y-5 order-1 lg:order-2 lg:sticky lg:top-24 h-fit">
                        <div className="clay-card overflow-hidden bg-white">
                            {hasMedia ? (
                                <div className="bg-surface-container-low aspect-square relative flex items-center justify-center overflow-hidden group">
                                    {hasImage ? (
                                        <div
                                            className={`absolute inset-0 transition-opacity duration-500 ${mediaSlide === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'}`}
                                        >
                                            <img
                                                src={course.thumbnail_url}
                                                alt={course.title}
                                                className="w-full h-full object-contain"
                                                loading="lazy"
                                            />
                                        </div>
                                    ) : null}
                                    {hasVideo ? (
                                        <div
                                            className={`absolute inset-0 transition-opacity duration-500 bg-black flex items-center justify-center ${mediaSlide === videoSlideIndex ? 'opacity-100 z-10' : 'opacity-0 z-0'}`}
                                        >
                                            {ytPreviewId ? (
                                                <iframe
                                                    className="w-full h-full"
                                                    src={`https://www.youtube.com/embed/${ytPreviewId}?autoplay=0&mute=0&controls=1&rel=0&enablejsapi=1`}
                                                    title={`${course.title} preview`}
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowFullScreen
                                                />
                                            ) : (
                                                <video
                                                    className="w-full h-full object-contain"
                                                    controls
                                                    preload="none"
                                                    poster={course.thumbnail_url || undefined}
                                                    controlsList="nodownload"
                                                >
                                                    <source src={course.video_url} type="video/mp4" />
                                                </video>
                                            )}
                                        </div>
                                    ) : null}
                                    {slideCount > 1 ? (
                                        <>
                                            <div className="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-20">
                                                <button
                                                    type="button"
                                                    onClick={() => setMediaSlide(0)}
                                                    className={`w-2.5 h-2.5 rounded-full transition-colors ${mediaSlide === 0 ? 'bg-primary' : 'bg-white/50 hover:bg-white/75'}`}
                                                    aria-label="Show image"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setMediaSlide(1)}
                                                    className={`w-2.5 h-2.5 rounded-full transition-colors ${mediaSlide === 1 ? 'bg-primary' : 'bg-white/50 hover:bg-white/75'}`}
                                                    aria-label="Show video"
                                                />
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => setMediaSlide((s) => (s === 0 ? 1 : 0))}
                                                className="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/75 hover:bg-white text-on-surface flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 shadow-sm"
                                                aria-label="Toggle preview"
                                            >
                                                <Icon name="chevron_left" className="text-sm" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setMediaSlide((s) => (s === 0 ? 1 : 0))}
                                                className="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/75 hover:bg-white text-on-surface flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 shadow-sm"
                                                aria-label="Toggle preview"
                                            >
                                                <Icon name="chevron_right" className="text-sm" />
                                            </button>
                                        </>
                                    ) : null}
                                </div>
                            ) : null}

                            <div className="p-5 md:p-6">
                                <div className="flex flex-wrap gap-2 mb-3">
                                    {category ? (
                                        <span className="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">
                                            {category.name}
                                        </span>
                                    ) : null}
                                    {ageLabel ? (
                                        <span className="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">
                                            {ageLabel}
                                        </span>
                                    ) : null}
                                    {course.course_type ? (
                                        <span className="px-3 py-1 bg-surface-container-high text-on-surface text-xs font-bold rounded-full uppercase">
                                            {course.course_type === 'live' ? 'Live Class' : course.course_type}
                                        </span>
                                    ) : null}
                                </div>

                                <h1 className="text-xl font-bold text-on-surface mb-2 leading-snug">{course.title}</h1>

                                {reviewCount > 0 ? (
                                    <a href="#reviews" className="inline-flex items-center gap-1.5 text-primary font-bold text-sm hover:underline">
                                        <span
                                            className="material-symbols-outlined text-tertiary text-lg"
                                            style={{ fontVariationSettings: "'FILL' 1" }}
                                            aria-hidden="true"
                                        >
                                            star
                                        </span>
                                        <span>{Number(reviewAverage).toFixed(1)}</span>
                                        <span className="text-on-surface-variant font-medium">
                                            ({reviewCount} {reviewCount === 1 ? 'review' : 'reviews'})
                                        </span>
                                    </a>
                                ) : null}

                                <div className="mt-5 pt-5 border-t border-outline-variant/20 space-y-3">
                                    <a
                                        href="#leadModal"
                                        data-category-id={category?.id ?? ''}
                                        data-course-title={course.title ?? ''}
                                        className="w-full py-2.5 text-[15px] font-bold text-center border-[2.5px] border-[#7e22ce] text-[#7e22ce] bg-white rounded-full shadow-[0_4px_0_#581c87] active:translate-y-[4px] active:shadow-none transition-all flex items-center justify-center hover:bg-primary-fixed"
                                    >
                                        Book my free class
                                    </a>

                                    {isEnrolled ? (
                                        <a href={playerHref} className="w-full btn-primary py-3.5 flex items-center justify-center">
                                            Continue Learning
                                        </a>
                                    ) : (
                                        <div className="p-4 bg-surface-container-low rounded-xl border border-outline-variant/30">
                                            <h3 className="font-bold text-on-surface mb-3 text-sm">Enroll Now</h3>
                                            <label className="block text-xs font-bold text-on-surface-variant mb-1">
                                                Number of classes (max 48)
                                            </label>
                                            <input
                                                type="number"
                                                min={1}
                                                max={48}
                                                value={classesCount}
                                                onChange={(e) => setClassesCount(clampClasses(e.target.value))}
                                                className="w-full p-2.5 rounded-lg border border-outline-variant/40 mb-3 bg-white text-sm"
                                            />
                                            <div className="flex justify-between items-center mb-4">
                                                <span className="text-sm text-on-surface-variant font-medium">Total Amount:</span>
                                                <span className="font-bold text-lg text-primary">
                                                    {symbol}
                                                    {totalAmount}
                                                </span>
                                            </div>
                                            <a
                                                href={checkoutHref}
                                                className="w-full py-3 flex items-center justify-center gap-2 text-sm font-bold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors"
                                            >
                                                Generate Payment Link
                                            </a>
                                        </div>
                                    )}

                                    {inWishlist ? (
                                        <p className="text-xs text-primary font-bold text-center">In your wishlist</p>
                                    ) : null}
                                </div>
                            </div>
                        </div>

                        <section className="clay-card p-5 md:p-6 bg-white" id="instructor">
                            <h2 className="text-base font-bold text-on-surface mb-4">
                                Meet your <span className="accent-green">Instructor</span>
                            </h2>
                            <div className="flex items-start gap-3">
                                <div className="w-14 h-14 rounded-full bg-primary/15 text-primary flex items-center justify-center text-xl font-bold shrink-0">
                                    {(course.tutor?.name ?? 'Y').charAt(0).toUpperCase()}
                                </div>
                                <div className="min-w-0">
                                    <h3 className="font-bold text-on-surface mb-0.5">
                                        {course.tutor?.name ?? 'Younimatiq Educator'}
                                    </h3>
                                    <p className="text-xs font-bold text-primary mb-2">
                                        Live {category?.name ?? 'STEM'} Educator
                                    </p>
                                    <p className="text-sm text-on-surface-variant leading-relaxed">
                                        {course.tutor?.bio ||
                                            `Experienced Younimatiq tutor specializing in engaging, project-based live classes. Students get personal feedback and a safe space to explore ${(category?.name ?? 'new skills').toLowerCase()} with confidence.`}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section className="clay-card p-5 md:p-6 bg-white" id="write-review">
                            <h2 className="text-base font-bold text-on-surface mb-1">
                                {userReview ? 'Update your review' : 'Write a Review'}
                            </h2>
                            <p className="text-xs text-on-surface-variant mb-4">Share your experience with this course.</p>
                            {isLoggedIn ? (
                                <form method="POST" action="/student/reviews" className="space-y-4">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="course_id" value={course.id} />
                                    <input type="hidden" name="rating" value={reviewRating} />
                                    <div>
                                        <label className="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-2">
                                            Your rating
                                        </label>
                                        <div className="flex items-center gap-1" role="radiogroup" aria-label="Rating">
                                            {[1, 2, 3, 4, 5].map((i) => (
                                                <button
                                                    key={i}
                                                    type="button"
                                                    onClick={() => setReviewRating(i)}
                                                    className="p-0.5 transition-transform hover:scale-110"
                                                    aria-pressed={reviewRating >= i}
                                                >
                                                    <span
                                                        className={`material-symbols-outlined text-2xl ${reviewRating >= i ? 'text-tertiary' : 'text-outline-variant'}`}
                                                        style={{
                                                            fontVariationSettings: `'FILL' ${reviewRating >= i ? 1 : 0}`,
                                                        }}
                                                        aria-hidden="true"
                                                    >
                                                        star
                                                    </span>
                                                </button>
                                            ))}
                                            <span className="ml-2 text-sm font-bold text-on-surface">{reviewRating} / 5</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            htmlFor="review-comment"
                                            className="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-2"
                                        >
                                            Your comment
                                        </label>
                                        <textarea
                                            id="review-comment"
                                            name="comment"
                                            rows={3}
                                            defaultValue={userReview?.comment ?? ''}
                                            className="w-full rounded-xl border border-outline-variant/40 bg-surface-container-low px-3 py-2.5 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-y"
                                            placeholder="What did you or your child enjoy most?"
                                        />
                                    </div>
                                    <button type="submit" className="w-full btn-primary py-3 text-sm flex items-center justify-center gap-2">
                                        <Icon name="rate_review" className="text-lg" />
                                        {userReview ? 'Update Review' : 'Submit Review'}
                                    </button>
                                </form>
                            ) : (
                                <div className="rounded-xl bg-surface-container-low p-4 text-center">
                                    <p className="text-sm text-on-surface-variant mb-3">Log in to leave a review for this course.</p>
                                    <Link href="/login/student" className="btn-primary btn-sm inline-flex items-center justify-center px-5 py-2.5">
                                        Login to Write a Review
                                    </Link>
                                </div>
                            )}
                        </section>

                        <section className="clay-card p-5 md:p-6 bg-white" id="reviews">
                            <div className="flex items-center justify-between gap-3 mb-4">
                                <h2 className="text-base font-bold text-on-surface">Reviews</h2>
                                {reviewCount > 0 ? (
                                    <div className="flex flex-col items-end shrink-0">
                                        <p className="text-xl font-bold text-primary leading-none">
                                            {Number(reviewAverage).toFixed(1)}
                                        </p>
                                        <StarRow rating={Math.round(reviewAverage)} />
                                        <p className="text-[11px] text-on-surface-variant mt-0.5">
                                            {reviewCount} {reviewCount === 1 ? 'review' : 'reviews'}
                                        </p>
                                    </div>
                                ) : null}
                            </div>
                            {reviewCount > 0 && course.reviews?.length ? (
                                <div className="space-y-3 max-h-[24rem] overflow-y-auto pr-1">
                                    {course.reviews.slice(0, 6).map((review) => (
                                        <article key={review.id} className="p-3 rounded-xl bg-surface-container-low">
                                            <div className="flex items-center justify-between gap-2 mb-2">
                                                <div className="flex items-center gap-2 min-w-0 flex-1">
                                                    <div className="w-8 h-8 rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                                        {(review.user_name ?? 'S').charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="font-bold text-sm text-on-surface truncate">{review.user_name}</p>
                                                        {review.created_at_label ? (
                                                            <p className="text-[11px] text-on-surface-variant">{review.created_at_label}</p>
                                                        ) : null}
                                                    </div>
                                                </div>
                                                <StarRow rating={review.rating} size="text-sm" />
                                            </div>
                                            {review.comment ? (
                                                <p className="text-sm text-on-surface-variant leading-relaxed pl-10">{review.comment}</p>
                                            ) : null}
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed border-outline-variant/40 py-8 px-4 text-center text-on-surface-variant">
                                    <Icon name="reviews" className="text-3xl text-primary/40 mb-2 block mx-auto" />
                                    <p className="font-bold text-on-surface text-sm mb-1">No reviews yet</p>
                                    <p className="text-xs">Be the first to write one above.</p>
                                </div>
                            )}
                        </section>
                    </aside>
                </div>
            </div>
        </PublicLayout>
    );
}
