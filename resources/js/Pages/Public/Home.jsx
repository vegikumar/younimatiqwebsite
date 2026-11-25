import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import CourseCard from '../../Components/CourseCard';
import Icon from '../../Components/Icon';
import HeroClassFinder from '../../Components/Home/HeroClassFinder';
import FeaturedProjects from '../../Components/Home/FeaturedProjects';
import { ACADEMIC_TRACKS, TECH_TRACKS } from '../../Components/Home/subjectTracks';

/** Hero video — poster first, then stream+play as soon as a few frames are ready (not full download). */
const HERO_VIDEO_VERSION = '20261114c';

function DeferredHeroVideo() {
    const videoRef = useRef(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const el = videoRef.current;
        if (!el) return undefined;

        let played = false;
        const tryPlay = () => {
            if (played || !el) return;
            const p = el.play();
            if (p && typeof p.then === 'function') {
                p.then(() => {
                    played = true;
                    setReady(true);
                }).catch(() => {
                    // Autoplay blocked — keep poster; retry on first tap.
                });
            } else {
                played = true;
                setReady(true);
            }
        };

        // Start as soon as the browser can render a frame / begin playback.
        // Do NOT wait for canplaythrough (full buffer).
        el.addEventListener('loadeddata', tryPlay);
        el.addEventListener('canplay', tryPlay);
        el.addEventListener('playing', () => setReady(true));

        const onTap = () => tryPlay();
        document.addEventListener('pointerdown', onTap, { once: true, passive: true });

        if (el.readyState >= 2) tryPlay();

        return () => {
            el.removeEventListener('loadeddata', tryPlay);
            el.removeEventListener('canplay', tryPlay);
            document.removeEventListener('pointerdown', onTap);
        };
    }, []);

    return (
        <video
            ref={videoRef}
            className={`hero-media-video relative z-10 w-full h-auto aspect-video object-cover rounded-[1.5rem] lg:rounded-[1.75rem] shadow-[0_20px_50px_-18px_rgba(113,48,216,0.35)] ring-1 ring-white/80 bg-[#1a1030] transition-opacity duration-500 ${ready ? 'opacity-100' : 'opacity-95'}`}
            poster={`/images/younimatiq-hero-poster-800.webp?v=${HERO_VIDEO_VERSION}`}
            autoPlay
            muted
            loop
            playsInline
            preload="auto"
            aria-label="Younimatiq profile video — kids learning and creating together"
        >
            <source
                src={`/videos/younimatiq-hero-mobile.mp4?v=${HERO_VIDEO_VERSION}`}
                type="video/mp4"
                media="(max-width: 1023px)"
            />
            <source src={`/videos/younimatiq-hero.mp4?v=${HERO_VIDEO_VERSION}`} type="video/mp4" />
        </video>
    );
}

const STAT_ITEMS = [
    { icon: 'workspace_premium', label: 'Background-Checked Tutors', value: '100%' },
    { icon: 'groups', label: 'Students Learning Live', value: '170+' },
    { icon: 'alt_route', label: 'Subject Tracks', value: '8+' },
    { icon: 'menu_book', label: 'Live Online Courses', value: '40+' },
];

const HOW_STEPS = [
    { n: 1, title: 'Get Started', text: 'Tell us a bit about your child — takes two minutes.' },
    { n: 2, title: 'Get Matched to the Right Tutor & Track', text: 'We pair your child with a tutor suited to their pace and goals.' },
    { n: 3, title: 'Track Real Progress Every Week', text: "Follow milestones and projects from your child's dashboard." },
];

const REAL_PROGRESS_CARDS = [
    {
        icon: 'person_check',
        iconBg: 'bg-primary/10 text-primary',
        title: '1:1 Personalized Coaching',
        text: 'Never a one-size-fits-all class — every session adapts to your child.',
    },
    {
        icon: 'folder_open',
        iconBg: 'bg-secondary/10 text-secondary',
        title: 'Real Projects & Portfolios',
        text: "Kids build things they're proud of — not just worksheets.",
    },
        {
            icon: 'fact_check',
            iconBg: 'bg-warning/10 text-warning',
            title: 'Common Core-Aligned',
            text: 'Built to fit what your child is already learning in school — mapped to Common Core State Standards.',
        },
    {
        icon: 'trending_up',
        iconBg: 'bg-primary/10 text-primary',
        title: 'Progress You Can See',
        text: 'A personalized dashboard with real, weekly updates — no guessing.',
    },
];

const REVIEW_ITEMS = [
    {
        quote: '"My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference."',
        initials: 'JR',
        name: 'Jenna R.',
        meta: 'Math · Grade 5',
    },
    {
        quote: '"She built her first working app in eight weeks. I didn\'t expect that so soon — she\'s genuinely proud of it."',
        initials: 'MK',
        name: 'Marcus K.',
        meta: 'Coding · Grade 8',
    },
    {
        quote: '"The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate."',
        initials: 'PS',
        name: 'Priya S.',
        meta: 'AI Mastery · Grade 7',
    },
    {
        quote: '"My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way."',
        initials: 'DL',
        name: 'David L.',
        meta: 'Robotics · Grade 6',
    },
    {
        quote: '"Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories."',
        initials: 'ST',
        name: 'Sarah T.',
        meta: 'English · Grade 4',
    },
    {
        quote: '"Fascinating science experiments every week! My 8-year-old is constantly asking questions about how things work."',
        initials: 'AH',
        name: 'Ahmed H.',
        meta: 'Science · Grade 3',
    },
    {
        quote: '"From drawing paper sketches to animating digital characters — the progress in just 2 months is mind-blowing!"',
        initials: 'EM',
        name: 'Elena M.',
        meta: 'Arts & Animation · Grade 5',
    },
    {
        quote: '"My son stopped just playing video games and started building his own levels. It transformed screen time completely!"',
        initials: 'RC',
        name: 'Robert C.',
        meta: 'Game Design · Grade 7',
    },
];

const FAQ_ITEMS = [
    {
        question: 'How often are classes, and how long do they run?',
        answer: 'Most students attend 2–3 sessions per week, 45–55 minutes each. Schedules flex around school, sports, and family commitments.',
    },
    {
        question: 'What devices does my child need?',
        answer: 'A laptop or tablet with a webcam and stable internet connection. Coding and AI Mastery tracks work best on a laptop.',
    },
    {
        question: "Is the curriculum aligned with my child's school?",
        answer: "Yes — coaches customize sessions to complement your child's grade-level curriculum, aligned to Common Core standards.",
    },
    {
        question: 'Can my child try a class before enrolling?',
        answer: 'Absolutely — every family starts with one free trial class, no commitment required.',
    },
    {
        question: 'Can my child combine two subjects, like Coding and AI Mastery?',
        answer: "Yes, many families mix and match tracks. We'll help you build a schedule that fits.",
    },
    {
        question: 'Are tutors background-checked?',
        answer: 'Yes. Younimatiq tutors are background-checked and teach on a COPPA-conscious platform designed for K–12 families.',
    },
];

const FAQ_JSON_LD = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: FAQ_ITEMS.map((item) => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: {
            '@type': 'Answer',
            text: item.answer,
        },
    })),
};

const HOWTO_JSON_LD = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: 'How to start live online classes for kids on Younimatiq',
    description: 'Three steps to enroll your child in live 1:1 or small-group coaching at Younimatiq.',
    totalTime: 'PT10M',
    step: HOW_STEPS.map((step) => ({
        '@type': 'HowToStep',
        position: step.n,
        name: step.title,
        text: step.text,
        url: 'https://younimatiq.com/#how',
    })),
};

function ReviewCard({ quote, initials, name, meta }) {
    return (
        <div className="review-card">
            <div>
                <div className="ym-stars text-base mb-3 tracking-wide" style={{ color: '#FFC400' }} aria-label="5 out of 5 stars">
                    ★★★★★
                </div>
                <p className="text-on-surface text-sm leading-relaxed italic mb-6">{quote}</p>
            </div>
            <div className="flex items-center gap-3 pt-3 border-t border-outline-variant">
                <div className="w-10 h-10 rounded-full bg-[#F0EAFF] text-[#7130D8] font-bold flex items-center justify-center text-sm">{initials}</div>
                <div>
                    <div className="font-bold text-brand-ink text-sm">{name}</div>
                    <div className="text-xs text-on-surface-variant">{meta}</div>
                </div>
            </div>
        </div>
    );
}

function ReviewsMarquee() {
    return (
        <section className="py-12 md:py-16 bg-surface-container-high/30 overflow-hidden" id="reviews">
            <div className="max-w-7xl mx-auto px-4 md:px-8 mb-10 text-center">
                <h2 className="section-heading">
                    Loved by <span className="accent-green">Families Everywhere</span>
                </h2>
                <p className="text-on-surface-variant mt-2 text-base md:text-lg">Real reviews from parents around the world</p>
            </div>
            <style>{`
                #reviews .marquee-wrapper {
                    display: flex;
                    overflow: hidden;
                    width: 100%;
                    max-width: 100%;
                    position: relative;
                    mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
                    -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
                }
                #reviews .marquee-track {
                    display: flex;
                    gap: 1.5rem;
                    animation: home-reviews-marquee 60s linear infinite;
                    padding: 1rem 0;
                    width: max-content;
                    will-change: transform;
                }
                #reviews .marquee-group {
                    display: flex;
                    gap: 1.5rem;
                    flex-shrink: 0;
                }
                #reviews .marquee-wrapper:hover .marquee-track {
                    animation-play-state: paused;
                }
                @keyframes home-reviews-marquee {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(calc(-25% - 0.375rem)); }
                }
                #reviews .review-card {
                    width: 85vw;
                    max-width: 360px;
                    flex-shrink: 0;
                    background: #ffffff;
                    border-radius: 1.25rem;
                    padding: 1.75rem;
                    border: 1px solid #e2e8f0;
                    box-shadow: 0 4px 20px rgba(0, 61, 134, 0.06);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                }
                #reviews .review-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 12px 30px rgba(0, 61, 134, 0.12);
                }
            `}</style>
            <div className="marquee-wrapper">
                <div className="marquee-track">
                    {[0, 1, 2, 3].map((groupIndex) => (
                        <div key={groupIndex} className="marquee-group" aria-hidden={groupIndex > 0 ? true : undefined}>
                            {REVIEW_ITEMS.map((item) => (
                                <ReviewCard key={`${groupIndex}-${item.initials}`} {...item} />
                            ))}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export default function Home({
    topCourses = [],
    featuredProjects = [],
    likedProjectIds = [],
    faqs = [],
    categories = [],
    gradeBands = [],
    heroFilters = {},
    demoClassPrice = 0,
    blogs = [],
}) {
    const displayFaqs = useMemo(() => faqs.filter((f) => f.category !== 'Pricing'), [faqs]);
    const [faqExpanded, setFaqExpanded] = useState(false);
    const [showPopup, setShowPopup] = useState(false);

    useEffect(() => {
        if (sessionStorage.getItem('bannerTriggered')) return;
        const handler = () => {
            if (window.scrollY > 10) {
                setShowPopup(true);
                sessionStorage.setItem('bannerTriggered', '1');
                window.removeEventListener('scroll', handler);
            }
        };
        window.addEventListener('scroll', handler, { passive: true });
        return () => window.removeEventListener('scroll', handler);
    }, []);

    const handlePopupBookNow = (e) => {
        setShowPopup(false);
        if (typeof window.openLeadModal === 'function') {
            window.openLeadModal(e);
        }
    };

    return (
        <PublicLayout title="Online Classes for Kids: Coding, AI & Math | Younimatiq">
            <Head>
                <title>Online Classes for Kids: Coding, AI & Math | Younimatiq</title>
                <meta
                    head-key="description"
                    name="description"
                    content="Younimatiq offers live 1:1 and small-group online classes for K–12 kids in Coding, AI, Robotics, Math, and more. Free trial with background-checked tutors."
                />
                <link head-key="canonical" rel="canonical" href="https://younimatiq.com/" />
                <meta head-key="og:type" property="og:type" content="website" />
                <meta head-key="og:url" property="og:url" content="https://younimatiq.com/" />
                <meta head-key="og:title" property="og:title" content="Online Classes for Kids: Coding, AI & Math | Younimatiq" />
                <meta
                    head-key="og:description"
                    property="og:description"
                    content="Younimatiq offers live 1:1 and small-group online classes for K–12 kids in Coding, AI, Robotics, Math, and more. Free trial with background-checked tutors."
                />
                <meta head-key="og:image" property="og:image" content={`https://younimatiq.com/images/younimatiq-hero-poster-800.webp?v=${HERO_VIDEO_VERSION}`} />
                <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
                <meta head-key="twitter:title" name="twitter:title" content="Online Classes for Kids: Coding, AI & Math | Younimatiq" />
                <meta
                    head-key="twitter:description"
                    name="twitter:description"
                    content="Younimatiq offers live 1:1 and small-group online classes for K–12 kids in Coding, AI, Robotics, Math, and more. Free trial with background-checked tutors."
                />
                <link
                    rel="preload"
                    as="image"
                    type="image/webp"
                    href={`/images/younimatiq-hero-poster-800.webp?v=${HERO_VIDEO_VERSION}`}
                    fetchPriority="high"
                />
                <script type="application/ld+json">{JSON.stringify(FAQ_JSON_LD)}</script>
                <script type="application/ld+json">{JSON.stringify(HOWTO_JSON_LD)}</script>
            </Head>

            <section
                className="relative py-8 sm:py-12 lg:py-14 min-h-fit flex items-center px-3 sm:px-6 md:px-8 mx-auto w-full md:w-[98%] max-w-[1600px] mt-0 md:mt-6 rounded-none md:rounded-[2.5rem] hero-panel hero-panel--playful z-20"
                style={{ background: 'var(--brand-gradient)' }}
            >
                <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxjaXJjbGUgY3g9IjIiIGN5PSIyIiByPSIxLjIiIGZpbGw9InJnYmEoMTEzLCA0OCwgMjE2LCAwLjA0KSIvPgo8L3N2Zz4=')] opacity-70 pointer-events-none" />
                <div className="hero-deco hero-deco--with-media pointer-events-none absolute inset-0 z-[1] overflow-hidden" aria-hidden="true">
                    <span className="hero-blob hero-blob--purple" />
                    <span className="hero-blob hero-blob--blue" />
                    <span className="hero-blob hero-blob--orange" />
                    <span className="hero-blob hero-blob--green" />
                    <span className="hero-float hero-float--star" style={{ top: '12%', left: '6%', animationDelay: '0s' }}>
                        <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
                            <path fill="#FFC400" d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z" />
                        </svg>
                    </span>
                    <span className="hero-float hero-float--star" style={{ bottom: '16%', left: '12%', animationDelay: '0.6s' }}>
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                            <path fill="#087FF5" d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z" />
                        </svg>
                    </span>
                    <span className="hero-float hero-float--icon" style={{ top: '55%', left: '4%', animationDelay: '0.9s', color: '#38B91F' }}>
                        &lt;/&gt;
                    </span>
                    <span className="hero-float hero-float--rocket" style={{ bottom: '8%', left: '28%', animationDelay: '1.1s', fontSize: '1.15rem' }}>
                        🤖
                    </span>
                    <span className="hero-ring hero-ring--1" />
                </div>
                <div className="w-full flex flex-col z-10 relative">
                    <div className="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 md:p-8 lg:p-10 items-center w-full px-2 sm:px-8 md:px-10 lg:px-12">
                        <div className="lg:col-span-5 max-w-3xl mx-auto lg:mx-0 space-y-3 sm:space-y-5 relative z-10 flex flex-col items-center sm:items-start text-center sm:text-left w-full order-2 lg:order-1">
                            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-[2.75rem] xl:text-5xl font-extrabold leading-tight tracking-tight hero-title text-center sm:text-left m-0">
                                Turn Screen Time <br />
                                <span className="hero-title-accent drop-shadow-sm">Into Skill Time</span>
                            </h1>
                            <p className="font-bold text-brand-ink/90 text-lg sm:text-xl md:text-2xl mt-2 text-center sm:text-left">
                                — Younimatiq helps your child become a creator, not just a consumer.
                            </p>
                            <p className="font-body-lg text-body-lg text-on-surface-variant max-w-xl text-center sm:text-left mb-4 md:mb-6 text-sm sm:text-base md:text-lg">
                                <strong>Younimatiq</strong> is an online learning platform for K–12 students in the United States and worldwide —
                                live 1:1 and small-group coaching that turns screen time into skill time across Math, Science, Art, Coding, AI Mastery,
                                and Robotics, with a free trial class, background-checked tutors, and weekly progress parents can see.
                                <span className="block mt-3 font-bold text-on-surface">One child. Many interests. Limitless possibilities.</span>
                            </p>
                            <div className="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-center sm:justify-start gap-2 sm:gap-4 pt-1 sm:pt-2 w-full min-h-[7.5rem] sm:min-h-[3.25rem]">
                                <Link href="/courses" className="btn-enroll text-base sm:text-lg group w-full sm:w-auto text-center justify-center">
                                    Get Started
                                    <svg className="w-5 h-5 shrink-0 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                        <path d="M5 12h14" />
                                        <path d="m13 5 7 7-7 7" />
                                    </svg>
                                </Link>
                                <a href="#how" className="btn see-how text-base sm:text-lg w-full sm:w-auto text-center justify-center">
                                    <span>See How Younimatiq Works</span>
                                </a>
                            </div>
                            <div className="trust-row pt-1 sm:pt-3 flex items-center justify-center sm:justify-start gap-3 mt-1 sm:mt-2 w-full bg-white/65 border border-[#0057bf]/25 px-4 py-2.5 rounded-2xl backdrop-blur-md max-w-max hover:bg-white/80 transition-colors">
                                <div className="stars flex items-center text-[14px] tracking-[1px]">
                                    <span className="text-[#f59e0b]">★★★★</span>
                                    <div className="relative inline-block">
                                        <span className="text-white">★</span>
                                        <span className="absolute left-0 top-0 text-[#f59e0b] overflow-hidden whitespace-nowrap" style={{ width: '60%' }}>
                                            ★
                                        </span>
                                    </div>
                                </div>
                                <div className="rating-num font-[800] text-[15px] text-[#003d86]">4.9* Trustpilot</div>
                            </div>
                        </div>
                        <div className="lg:col-span-7 relative z-10 order-1 lg:order-2 flex flex-col items-center lg:items-end w-full">
                            <div className="hero-media relative w-full max-w-[420px] sm:max-w-[480px] lg:max-w-none xl:max-w-[760px]">
                                <div className="hero-media-frame absolute -inset-2 sm:-inset-3 lg:-inset-4 rounded-[1.75rem] lg:rounded-[2rem] bg-gradient-to-br from-[#7130D8]/20 via-[#FFC400]/15 to-[#087FF5]/20" aria-hidden="true" />
                                <DeferredHeroVideo />
                                <span className="hero-media-badge absolute -bottom-3 left-4 sm:left-6 z-20 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs sm:text-sm font-extrabold text-[#7130D8] shadow-md ring-1 ring-black/5">
                                    <span aria-hidden="true">✨</span> Learn · Create · Grow
                                </span>
                            </div>
                            <div className="mt-6 sm:mt-8 text-[12px] sm:text-[13px] font-semibold text-on-surface-variant flex items-center justify-center lg:justify-start gap-2 sm:gap-3 flex-wrap w-full max-w-[420px] sm:max-w-[480px] lg:max-w-none xl:max-w-[760px]">
                                <span className="flex items-center gap-1">
                                    <Icon name="how_to_reg" className="text-[16px] text-primary" /> Background-checked tutors
                                </span>
                                <span className="text-outline hidden sm:inline">&bull;</span>
                                <span className="flex items-center gap-1">
                                    <Icon name="video_camera_front" className="text-[16px] text-primary" /> Every class recorded
                                </span>
                                <span className="text-outline hidden md:inline">&bull;</span>
                                <span className="flex items-center gap-1">
                                    <Icon name="policy" className="text-[16px] text-primary" /> COPPA-compliant
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="w-full px-4 sm:px-8 md:px-12 max-w-[1100px] mx-auto pb-4 sm:pb-8 relative z-30">
                        <HeroClassFinder categories={categories} gradeBands={gradeBands} initialFilters={heroFilters} />
                    </div>
                </div>
            </section>

            <section className="py-6 md:py-8 max-w-7xl mx-auto px-4 md:px-8 relative z-10 overflow-x-clip">
                <div className="flex md:grid md:grid-cols-4 gap-3 sm:gap-4 overflow-x-auto snap-x snap-mandatory hide-scrollbar py-2 max-w-full">
                    {STAT_ITEMS.map((item) => (
                        <div key={item.label} className="clay-card p-4 sm:p-6 text-center flex flex-col items-center justify-center space-y-1 flex-none w-[min(60vw,16rem)] md:w-auto snap-start">
                            <Icon name={item.icon} className="text-primary text-4xl" />
                            <div className="font-extrabold text-brand-ink text-2xl sm:text-3xl tracking-tight">{item.value}</div>
                            <div className="font-bold text-on-surface-variant text-sm sm:text-base">{item.label}</div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="py-12 md:py-14 px-4 md:px-8 max-w-7xl mx-auto" id="experts">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-center">
                    <div className="lg:col-span-8 order-2 lg:order-1 space-y-5 text-center lg:text-left">
                        <p className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-primary">
                            <Icon name="verified" className="text-[16px]" /> World-class curriculum &amp; mentorship
                        </p>
                        <h2 className="section-heading text-center lg:text-left max-w-2xl mx-auto lg:mx-0">
                            Programs designed by experts from <span className="accent-green">Harvard, MIT, and University of Glasgow</span>
                        </h2>
                        <p className="text-on-surface-variant text-base sm:text-[1.05rem] leading-relaxed max-w-2xl mx-auto lg:mx-0">
                            Younimatiq curriculum and learning frameworks are crafted with mentorship connected to{' '}
                            <a href="https://www.harvard.edu/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">Harvard University</a>,{' '}
                            <a href="https://www.mit.edu/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">MIT</a>, and the{' '}
                            <a href="https://www.gla.ac.uk/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">University of Glasgow</a>
                            {' '}— combining academic rigor with hands-on, 1:1 coaching that adapts to every child.
                        </p>
                        <div className="pt-2">
                            <p className="text-[11px] font-bold uppercase tracking-[0.12em] text-on-surface-variant mb-3">Academic pedigree</p>
                            <div className="flex items-center justify-center lg:justify-start gap-6 sm:gap-8">
                                <img loading="lazy" src="/images/logos/harvard.svg" alt="Harvard University" width="110" height="28" className="h-6 sm:h-7 w-auto object-contain opacity-90" />
                                <span className="w-px h-6 bg-surface-container" aria-hidden="true" />
                                <img loading="lazy" src="/images/logos/mit.webp" alt="MIT" width="72" height="24" className="h-5 sm:h-6 w-auto object-contain opacity-90" />
                                <span className="w-px h-6 bg-surface-container" aria-hidden="true" />
                                <img loading="lazy" src="/images/logos/glasgow.webp" alt="University of Glasgow" width="218" height="41" className="h-6 sm:h-7 w-auto object-contain opacity-90" />
                            </div>
                        </div>
                    </div>
                    <div className="lg:col-span-4 order-1 lg:order-2 flex justify-center lg:justify-start">
                        <figure className="w-full max-w-[240px] sm:max-w-[260px]">
                            <div className="group block relative aspect-square overflow-hidden rounded-2xl bg-surface-container-high ring-1 ring-black/5 shadow-[0_8px_30px_rgba(15,23,42,0.08)]">
                                <img
                                    src="/images/experts/sharul.webp"
                                    alt="Sharul — Founder, MIT"
                                    className="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]"
                                    width="260"
                                    height="260"
                                    sizes="(max-width: 640px) 240px, 260px"
                                    srcSet="/images/experts/sharul-380.webp 380w, /images/experts/sharul-640.webp 640w, /images/experts/sharul.webp 1024w"
                                />
                            </div>
                            <figcaption className="mt-4 text-center">
                                <p className="text-xl font-bold text-brand-ink tracking-tight">Sharul</p>
                                <p className="mt-0.5 text-sm text-on-surface-variant"> MIT</p>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            <section className="py-12 md:py-10 px-4 md:px-8 bg-surface-container-high/20" id="projects">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-8">
                        <h2 className="section-heading">
                            Consumers to <span className="accent-green">Creators</span>
                        </h2>
                        <p className="text-on-surface-variant mt-2 text-base md:text-lg font-medium">(Look What Kids Create in Our Live Classes)</p>
                    </div>
                    <FeaturedProjects projects={featuredProjects} likedProjectIds={likedProjectIds} />
                </div>
            </section>

            <section className="py-12 md:py-10 px-4 md:px-8 max-w-7xl mx-auto" id="how">
                <div className="text-center mb-4 md:mb-8">
                    <h2 className="section-heading">
                        How does <span className="accent-green">Younimatiq</span> work?
                    </h2>
                    <p className="text-on-surface-variant mt-2 text-base md:text-lg font-medium max-w-2xl mx-auto">
                        Three clear steps take your child from a free trial to matched coaching and weekly progress you can track.
                    </p>
                </div>
                <ol className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto list-none p-0 m-0">
                    {HOW_STEPS.map((step) => (
                        <li key={step.n} className="text-center space-y-3">
                            <div className="w-16 h-16 mx-auto rounded-full bg-[#F0EAFF] text-[#7130D8] font-extrabold text-2xl flex items-center justify-center border-4 border-[#7130D8]">
                                {step.n}
                            </div>
                            <h3 className="text-xl font-bold text-brand-ink">{step.title}</h3>
                            <p className="text-sm text-on-surface-variant">{step.text}</p>
                        </li>
                    ))}
                </ol>
                <div className="text-center mt-10">
                    <Link href="/courses" className="btn-enroll text-lg inline-flex items-center gap-2">
                        Get Started <Icon name="arrow_forward" />
                    </Link>
                </div>
            </section>

            <section className="pt-4 pb-16 px-4 md:px-8 max-w-7xl mx-auto" id="courses">
                <div className="text-center mb-10">
                    <h2 className="section-heading">
                        What subjects can my child <span className="accent-green">learn online?</span>
                    </h2>
                    <p className="text-on-surface-variant mt-2 text-base md:text-lg font-medium max-w-2xl mx-auto">
                        Personalized K–12 coaching blended by grade and interest across tech and academic tracks.
                    </p>
                </div>
                <div className="mb-8">
                    <div className="flex items-center gap-2 mb-6 text-primary font-bold uppercase tracking-wider text-sm">
                        <span className="w-3 h-3 rounded-full bg-primary inline-block" /> Tech &amp; Innovation
                    </div>
                    <div className="flex overflow-x-auto gap-6 snap-x hide-scrollbar py-4">
                        {TECH_TRACKS.map((t) => (
                            <Link key={t.href} href={t.href} className="clay-card overflow-hidden flex flex-col !p-0 flex-none w-[280px] shrink-0 hover:scale-105 transition-all">
                                <img src={t.img} alt="" className="w-full aspect-video object-cover" loading="lazy" />
                                <div className="p-5">
                                    <h3 className="text-xl font-bold mb-1">{t.title}</h3>
                                    <p className="text-sm text-on-surface-variant">{t.desc}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
                <div>
                    <div className="flex items-center gap-2 mb-6 text-secondary font-bold uppercase tracking-wider text-sm">
                        <span className="w-3 h-3 rounded-full bg-secondary inline-block" /> Academics &amp; Core
                    </div>
                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {ACADEMIC_TRACKS.map((t) => (
                            <Link key={t.href} href={t.href} className="clay-card overflow-hidden flex flex-col !p-0 hover:scale-105 transition-all">
                                <img src={t.img} alt="" className="w-full aspect-video object-cover" loading="lazy" />
                                <div className="p-5">
                                    <h3 className="text-xl font-bold mb-1">{t.title}</h3>
                                    <p className="text-sm text-on-surface-variant">{t.desc}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-12 px-4 md:px-8 max-w-7xl mx-auto" id="story">
                <div className="clay-card p-5 md:p-8 lg:p-14 bg-gradient-to-br from-primary/10 via-white to-primary relative overflow-hidden rounded-3xl shadow-lg w-full flex flex-col md:flex-row gap-8 items-center">
                    <div className="space-y-6 w-full z-10">
                        <div className="flex items-center gap-3 text-primary font-bold uppercase tracking-wider text-sm">
                            <Icon name="favorite" className="text-primary" aria-hidden="true" />
                            Our Story
                        </div>
                        <h2 className="section-heading mb-4 max-w-none">Why did the rules for success change for kids?</h2>
                        <p className="text-on-surface font-bold text-lg md:text-xl max-w-3xl">Every parent wants their child to succeed. The rules just changed.</p>
                        <div className="space-y-4 font-body-lg text-body-lg text-on-surface-variant leading-relaxed w-full">
                            <p>
                                For a generation, success meant knowing the right answers. Today it means knowing how to think — how to question, adapt, and create in a world where AI can already do the recall for you. Founded with that shift in mind, Younimatiq in Texas, United States, is not another platform teaching kids what to memorize, but one built to teach them how to think critically, understand AI, and create boldly.
                            </p>
                            <p>
                                So we set out to build the &quot;Future Fit platform&quot; every parent wishes existed — spanning AI Robotics, Math, English, Science, and Quantum Computing, anchored by AI Mastery and 1:1 tutors who teach for understanding. Today Younimatiq supports 170+ students across 40+ live courses and 8+ subject tracks, with families citing a 4.9 Trustpilot signal and free trial access before they enroll.
                            </p>
                            <p>
                                Research from the{' '}
                                <a href="https://www.oecd.org/en/topics/education-and-skills.html" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">OECD Education and Skills</a>
                                {' '}program and{' '}
                                <a href="https://www.unesco.org/en/education" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">UNESCO Education</a>
                                {' '}underscores why adaptive problem-solving beats rote recall as AI reshapes work. Younimatiq academic tracks also complement{' '}
                                <a href="https://www.thecorestandards.org/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">Common Core State Standards</a>
                                , and our child-safety practices follow guidance in the{' '}
                                <a href="https://www.ftc.gov/legal-library/browse/rules/childrens-online-privacy-protection-rule-coppa" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">U.S. FTC COPPA Rule</a>.
                            </p>
                        </div>
                    </div>
                    <div className="w-full md:w-1/3 flex justify-center md:justify-end z-10">
                        <figure className="w-full max-w-[240px] sm:max-w-[260px]">
                            <div className="group block relative aspect-square overflow-hidden rounded-2xl bg-surface-container-high ring-1 ring-black/5 shadow-[0_8px_30px_rgba(15,23,42,0.08)]">
                                <img
                                    src="/images/nairrita-moha.webp"
                                    alt="Nairrita — Co-Founder"
                                    className="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]"
                                    width={260}
                                    height={260}
                                    loading="lazy"
                                />
                            </div>
                            <figcaption className="mt-4 text-center">
                                <p className="text-xl font-bold text-brand-ink tracking-tight">Nairrita</p>
                                <p className="mt-0.5 text-sm text-on-surface-variant">Co-Founder</p>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            <section className="py-12 px-4 md:px-8 max-w-7xl mx-auto" id="insights">
                <div className="text-center mb-8 max-w-3xl mx-auto">
                    <h2 className="section-heading">
                        Why Families Choose <span className="accent-green">Evidence-Backed Learning</span>
                    </h2>
                    <p className="text-on-surface-variant mt-3 text-base md:text-lg">
                        Younimatiq designs live K–12 coaching around published education research — not guesswork.
                    </p>
                </div>
                <div className="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto text-on-surface-variant leading-relaxed">
                    <p className="clay-card p-5 md:p-6 text-sm sm:text-base">
                        According to <a href="https://www.oecd.org/en/topics/education-and-skills.html" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">OECD</a> PISA analyses, roughly 65% of students across OECD countries meet baseline reading proficiency, while problem-solving gaps persist — so Younimatiq prioritizes projects over memorization.
                    </p>
                    <p className="clay-card p-5 md:p-6 text-sm sm:text-base">
                        <a href="https://nces.ed.gov/nationsreportcard/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">NAEP</a> results show fewer than 40% of U.S. Grade 4 and Grade 8 students reach NAEP Proficient in math in recent cycles, which informs how Younimatiq Math coaches diagnose and reteach.
                    </p>
                    <p className="clay-card p-5 md:p-6 text-sm sm:text-base">
                        <a href="https://www.worldbank.org/en/topic/education" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">World Bank</a> research places learning poverty near 70% in parts of the developing world — a reminder that personalized remediation and enrichment remain essential for equity.
                    </p>
                    <p className="clay-card p-5 md:p-6 text-sm sm:text-base">
                        <a href="https://www.pewresearch.org/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">Pew Research Center</a> finds a majority of U.S. teens already use generative AI for schoolwork; Younimatiq AI Mastery teaches responsible use and creation, not shortcuts.
                    </p>
                    <p className="clay-card p-5 md:p-6 text-sm sm:text-base md:col-span-2">
                        Younimatiq also maps practice to <a href="https://www.iste.org/standards/iste-standards-for-students" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">ISTE Standards for Students</a> and child-privacy guidance from the <a href="https://www.ftc.gov/business-guidance/resources/complying-coppa-frequently-asked-questions" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">FTC COPPA FAQ</a>, while keeping academic tracks aligned with <a href="https://www.thecorestandards.org/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">Common Core State Standards</a> and support from the <a href="https://www.ed.gov/" target="_blank" rel="noopener noreferrer" className="text-primary font-semibold underline-offset-2 hover:underline">U.S. Department of Education</a> ecosystem.
                    </p>
                </div>
            </section>

            <section className="py-12 px-4 md:px-8 bg-surface-container-high/40">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-10">
                        <h2 className="section-heading">
                            Not Tutoring. Not Screen Time. <br />
                            <span className="accent-green">Real Progress.</span>
                        </h2>
                    </div>
                    <div className="flex md:grid md:grid-cols-2 lg:grid-cols-4 gap-5 overflow-x-auto snap-x snap-mandatory py-16 -my-16 hide-scrollbar px-4 -mx-4 md:mx-0">
                        {REAL_PROGRESS_CARDS.map((card) => (
                            <div key={card.title} className="clay-card p-5 md:p-8 space-y-4 flex-none w-[65vw] md:w-auto snap-start">
                                <div className={`w-14 h-14 rounded-2xl ${card.iconBg} flex items-center justify-center`}>
                                    <Icon name={card.icon} className="text-3xl" />
                                </div>
                                <h3 className="text-xl font-bold text-on-surface">{card.title}</h3>
                                <p className="text-on-surface-variant text-base leading-relaxed">{card.text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <ReviewsMarquee />

            <section className="py-0 px-0 max-w-7xl mx-auto my-6">
                <div className="p-0 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-0 rounded-3xl shadow-lg" style={{ background: '#EFDECE', border: '1px solid #E4D0BD' }}>
                    <div className="space-y-4 max-w-xl z-10 p-5 md:p-8 lg:p-6 md:p-12">
                        <div className="inline-flex items-center gap-2 bg-warning text-white text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wide shadow-sm">
                            <Icon name="auto_awesome" className="text-sm" aria-hidden="true" /> New Track
                        </div>
                        <h3 className="text-2xl md:text-4xl font-extrabold text-brand-ink leading-tight">Give Your Child an Edge: AI Mastery for K–12</h3>
                        <p className="text-on-surface text-base md:text-lg leading-relaxed">
                            Learn to think with AI, build with it, and use it responsibly — skills every future workplace will expect.
                        </p>
                        <div className="pt-2">
                            <Link href="/courses?categories[]=ai-mastery" className="btn-primary text-base md:text-lg inline-flex items-center gap-2 px-7 py-3 rounded-full font-bold shadow-md hover:scale-105 transition-transform">
                                <span>Learn About AI Mastery</span>
                                <Icon name="arrow_forward" className="text-sm" />
                            </Link>
                        </div>
                    </div>
                    <div className="w-full md:w-1/2 flex justify-end items-center z-10 p-0 m-0 hidden md:flex">
                        <img src="/images/ai-mastery.webp" alt="Give Your Child an Edge: AI Mastery for K–12" className="w-full h-full max-h-[400px] object-cover object-right block" loading="lazy" />
                    </div>
                </div>
            </section>

            {topCourses?.length > 0 ? (
                <section className="py-10 px-4 md:px-8 max-w-7xl mx-auto">
                    <div className="text-center mb-8">
                        <h3 className="text-2xl font-bold">
                            Top Courses, <span className="text-primary">Loved by Parents and Learners</span>
                        </h3>
                        <p className="text-on-surface-variant mt-2 text-lg font-medium">Our most-loved courses, picked by families like yours</p>
                    </div>
                    <div className="flex md:grid md:grid-cols-3 gap-5 overflow-x-auto snap-x snap-mandatory hide-scrollbar py-16 -my-16 px-4 -mx-4 md:mx-0">
                        {topCourses.map((c) => (
                            <CourseCard key={c.id} course={c} badge="🏆 BEST SELLER" className="flex-none w-[65vw] md:w-[320px] lg:w-auto snap-start shadow-sm border border-outline-variant rounded-2xl relative" />
                        ))}
                    </div>
                </section>
            ) : null}

            <section className="py-10 px-4 md:px-8 max-w-7xl mx-auto" id="trial">
                <div className="brand-panel px-12 py-6 md:px-24 md:py-12 text-center relative overflow-hidden">
                    <div className="relative z-10">
                        <div className="hero-badge mb-6 inline-flex">
                            <Icon name="rocket_launch" className="text-[20px] text-white" style={{ fontVariationSettings: "'FILL' 1" }} />
                            Get Started
                        </div>
                        <h2 className="font-headline-xl text-headline-xl mb-6 hero-title">
                            Ready to See What <br className="hidden md:block" /> <span className="hero-title-accent drop-shadow-sm">Your Child Can Do?</span>
                        </h2>
                        <p className="font-body-lg text-body-lg mb-10 text-white/85 max-w-2xl mx-auto leading-relaxed px-4 md:px-8">
                            No commitment. Your first class is free. Give your child the opportunity to explore the future through 1:1 personalized coaching.
                        </p>
                        <div className="flex flex-col sm:flex-row justify-center gap-4">
                            <Link href="/courses" className="btn-enroll text-lg group w-full sm:w-auto">
                                Get Started Free
                                <Icon name="arrow_forward" className="group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <a href="https://wa.me/12027944843" target="_blank" rel="noopener noreferrer" className="btn-primary text-lg w-full sm:w-auto inline-flex items-center justify-center gap-2">
                                <Icon name="chat" />
                                Whatsapp Us
                            </a>
                        </div>
                    </div>
                    <div className="absolute -bottom-20 -left-20 w-80 h-80 bg-white/30 rounded-full blur-3xl" aria-hidden="true" />
                    <div className="absolute -top-20 -right-20 w-80 h-80 bg-secondary-container/30 rounded-full blur-3xl" aria-hidden="true" />
                </div>
            </section>

            <section className="py-16 bg-surface-container-low/50" id="faq">
                <div className="max-w-3xl mx-auto px-4 md:px-8">
                    <div className="text-center mb-10">
                        <h2 className="section-heading">
                            What do parents ask before <span className="accent-green">enrolling?</span>
                        </h2>
                        <p className="text-on-surface-variant mt-2 text-lg">Frequently asked questions about classes, devices, trials, and tutors</p>
                    </div>
                    <div className="space-y-4">
                        {displayFaqs.slice(0, faqExpanded ? undefined : 5).map((faq, index) => (
                            <details key={faq.id} className="group clay-card bg-white rounded-2xl overflow-hidden" open={index === 0}>
                                <summary className="flex justify-between items-center p-6 cursor-pointer font-bold text-lg list-none gap-4">
                                    {faq.question}
                                    <Icon name="expand_more" className="text-primary group-open:rotate-180 transition-transform shrink-0" />
                                </summary>
                                <div className="px-6 pb-6 text-on-surface-variant prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: faq.answer }} />
                            </details>
                        ))}
                    </div>
                    {displayFaqs.length > 5 ? (
                        <div className="text-center mt-10">
                            <button type="button" className="text-primary font-bold" onClick={() => setFaqExpanded((v) => !v)}>
                                {faqExpanded ? 'Show Less' : 'Show More'}
                            </button>
                        </div>
                    ) : null}
                    <div className="text-center mt-4">
                        <Link href="/faq" className="text-sm text-on-surface-variant hover:text-primary">
                            View all FAQs page
                        </Link>
                    </div>
                </div>
            </section>

            {blogs?.length > 0 ? (
                <section className="py-10 px-4 md:px-8 max-w-7xl mx-auto">
                    <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
                        <h2 className="section-heading mb-0">From the blog</h2>
                        <Link href="/blog" className="text-sm font-bold text-primary hover:underline">
                            View all posts
                        </Link>
                    </div>
                    <div className="grid md:grid-cols-3 gap-6">
                        {blogs.map((b) => (
                            <Link key={b.id} href={`/blog/${b.slug}`} className="clay-card overflow-hidden hover:shadow-md transition flex flex-col">
                                {b.featured_image ? (
                                    <img
                                        src={b.featured_image}
                                        alt={b.title}
                                        className="w-full aspect-video object-cover bg-surface-container-low"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full aspect-video bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                                        <Icon name="article" className="text-5xl text-primary/40" />
                                    </div>
                                )}
                                <div className="p-5 flex-1 flex flex-col">
                                    {b.published_at_label ? (
                                        <p className="text-xs font-bold text-secondary uppercase tracking-wider mb-2">{b.published_at_label}</p>
                                    ) : null}
                                    <h3 className="font-bold text-lg mb-2 line-clamp-2">{b.title}</h3>
                                    <p className="text-sm text-on-surface-variant line-clamp-3 flex-1">{b.excerpt || b.excerpt_preview}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            ) : null}

            {showPopup ? (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm">
                    <div className="relative bg-white rounded-[2rem] p-5 md:p-8 max-w-lg w-full shadow-2xl text-center border-t-8 border-primary">
                        <button type="button" className="absolute top-4 right-4 p-2 rounded-full bg-surface-container-low hover:bg-surface-container-high transition-colors" onClick={() => setShowPopup(false)} aria-label="Close">
                            <Icon name="close" />
                        </button>
                        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary/10 text-primary mb-6">
                            <Icon name="local_activity" className="text-4xl" />
                        </div>
                        <h3 className="font-headline-lg font-bold mb-2">Book Your Free Trial Class</h3>
                        <p className="text-on-surface-variant mb-8">
                            Experience Younimatiq&apos;s 1:1 interactive learning environment{' '}
                            {demoClassPrice <= 0 ? (
                                <span className="font-bold text-primary">completely FREE today!</span>
                            ) : (
                                <>
                                    for just <span className="font-bold text-primary">${demoClassPrice}</span>!
                                </>
                            )}
                        </p>
                        <a href="#leadModal" onClick={handlePopupBookNow} className="btn-enroll btn-lg w-full inline-flex items-center justify-center gap-2">
                            Book Now <Icon name="arrow_forward" className="text-sm" />
                        </a>
                        <button type="button" onClick={() => setShowPopup(false)} className="mt-4 text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
                            Maybe Later
                        </button>
                    </div>
                </div>
            ) : null}
        </PublicLayout>
    );
}
