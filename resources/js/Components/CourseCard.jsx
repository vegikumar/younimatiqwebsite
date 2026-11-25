import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { formatPrice } from '../lib/currency';

function youtubeId(url) {
    if (!url) return '';
    const m = String(url).match(
        /(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i
    );
    return m?.[1] ?? '';
}

export default function CourseCard({
    course,
    badge = null,
    hideButtons = false,
    hideBookFreeClass = false,
    className = '',
}) {
    const { displayCurrency } = usePage().props;
    const { symbol, amount } = formatPrice(course, displayCurrency);
    const href = course.slug ? `/courses/${course.slug}` : `/courses/${course.id}`;
    const thumb = course.thumbnail_url || '';
    const videoUrl = course.video_url || '';
    const yt = youtubeId(videoUrl);
    const hasVideo = Boolean(videoUrl);

    const rootRef = useRef(null);
    const [isOnScreen, setIsOnScreen] = useState(false);
    const [isHovered, setIsHovered] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const isPlaying = hasVideo && (isHovered ? false : isOnScreen);

    useEffect(() => {
        if (!hasVideo || !rootRef.current) return undefined;
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => setIsOnScreen(entry.isIntersecting));
            },
            { threshold: 0.1 }
        );
        observer.observe(rootRef.current);
        return () => observer.disconnect();
    }, [hasVideo]);

    useEffect(() => {
        if (isPlaying) setLoaded(true);
    }, [isPlaying]);

    const description = (course.description || '').trim();
    const categories = course.categories || [];

    return (
        <div
            ref={rootRef}
            className={`clay-card overflow-hidden group bg-white flex flex-col h-full ${className}`.trim()}
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
        >
            <Link
                href={href}
                className="relative block bg-surface-container-low aspect-square flex items-center justify-center overflow-hidden"
            >
                {hasVideo && loaded && isPlaying ? (
                    <div className="absolute inset-0 w-full h-full z-10 pointer-events-none overflow-hidden flex items-center justify-center bg-black">
                        {yt ? (
                            <iframe
                                title={course.title}
                                src={`https://www.youtube.com/embed/${yt}?autoplay=1&mute=1&controls=0&loop=1&playlist=${yt}&playsinline=1`}
                                className="w-full h-full border-0"
                                allow="autoplay; encrypted-media"
                                allowFullScreen
                            />
                        ) : (
                            <video
                                key={videoUrl}
                                src={videoUrl}
                                className="w-full h-full object-contain bg-black"
                                muted
                                autoPlay
                                loop
                                playsInline
                                onContextMenu={(e) => e.preventDefault()}
                            />
                        )}
                    </div>
                ) : null}

                <div className={`w-full h-full relative z-0 ${isPlaying ? 'opacity-0' : 'opacity-100'}`}>
                    {thumb ? (
                        <img
                            src={thumb}
                            alt={course.title}
                            className="w-full h-full object-contain group-hover:scale-[1.02] transition-transform duration-300"
                            loading="lazy"
                            width={512}
                            height={512}
                        />
                    ) : (
                        <div className="w-full h-full bg-primary/10 flex items-center justify-center">
                            <span className="material-symbols-outlined text-primary text-5xl">menu_book</span>
                        </div>
                    )}
                </div>

                <div className="absolute top-3 left-3 z-20 flex flex-col gap-2 items-start">
                    {badge ? (
                        <span className="bg-secondary-container text-on-secondary-container text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            {badge}
                        </span>
                    ) : null}
                    {course.self_paced_enabled ? (
                        <span className="bg-tertiary-container text-on-tertiary-container text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            Self-Paced Available
                        </span>
                    ) : null}
                </div>
            </Link>

            <Link href={href} className="p-5 flex-1 flex flex-col block cursor-pointer">
                <div className="flex flex-wrap items-center gap-2 mb-3">
                    {course.course_type ? (
                        <span className="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full uppercase">
                            {course.course_type === 'live' ? 'Live Class' : 'Self-Paced'}
                        </span>
                    ) : null}
                    {categories.map((category) => (
                        <span
                            key={category.id || category.slug || category.name}
                            className="px-2 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full"
                        >
                            {category.name}
                        </span>
                    ))}
                </div>

                <h3 className="font-headline-md font-bold mb-2 text-on-surface group-hover:text-primary transition-colors line-clamp-2">
                    {course.title}
                </h3>
                {description ? (
                    <p className="text-on-surface-variant text-sm flex-1 line-clamp-3">{description}</p>
                ) : (
                    <div className="flex-1" />
                )}
                {course.tutor?.name ? (
                    <p className="text-xs text-on-surface-variant mt-2">with {course.tutor.name}</p>
                ) : null}
            </Link>

            {!hideButtons ? (
                <div className="px-5 pb-5">
                    {!hideBookFreeClass ? (
                        <a
                            href="#leadModal"
                            data-category-id={categories[0]?.id || ''}
                            data-course-title={course.title || ''}
                            onClick={(e) => {
                                e.preventDefault();
                                if (typeof window.openLeadModal === 'function') {
                                    window.openLeadModal(
                                        e,
                                        'universal',
                                        categories[0]?.id || '',
                                        course.title || ''
                                    );
                                }
                            }}
                            className="w-full py-2.5 mb-3 text-[15px] font-bold text-center border-[2.5px] border-[#7e22ce] text-[#7e22ce] bg-white rounded-full shadow-[0_4px_0_#581c87] active:translate-y-[4px] active:shadow-none transition-all flex items-center justify-center hover:bg-primary-fixed"
                        >
                            Book my free class
                        </a>
                    ) : null}
                    <Link
                        href={href}
                        className="w-full py-2.5 text-[15px] font-bold text-center bg-[#7e22ce] text-white rounded-full shadow-[0_4px_0_#581c87] active:translate-y-[4px] active:shadow-none transition-all flex items-center justify-center hover:bg-[#6b21a8]"
                    >
                        Enroll now
                    </Link>
                    <div className="text-right mt-2 flex justify-end items-baseline gap-1">
                        <span className="font-normal text-on-surface-variant text-xs">{symbol}</span>
                        <span className="font-extrabold text-on-surface text-sm">{amount}</span>
                        <span className="font-normal text-on-surface-variant text-xs">/ class</span>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
