import { Head, Link } from '@inertiajs/react';
import BrandHero from '../../Components/BrandHero';
import Icon from '../../Components/Icon';
import PublicLayout from '../../Layouts/PublicLayout';

const whyUsBadgeClass = {
    1: 'bg-primary/10 text-primary',
    2: 'bg-secondary/10 text-secondary',
    3: 'bg-tertiary/10 text-tertiary',
    4: 'bg-error/10 text-error',
    5: 'bg-primary/10 text-primary',
    6: 'bg-secondary/10 text-secondary',
};

const whyUs = [
    { n: 1, title: 'Experienced Educators', text: 'Our educators bring real classroom and online teaching experience.' },
    { n: 2, title: 'Trained for Kids', text: 'Interactive methods, storytelling, and fun activities that make learning enjoyable.' },
    { n: 3, title: 'Carefully Screened', text: 'We carefully select our educators through a rigorous interview and vetting process.' },
    { n: 4, title: 'Subject Expertise', text: 'Specialized teachers in robotics, communication, creative arts, and more.' },
    { n: 5, title: 'Live 1:1', text: 'Real-time interaction and personalized attention for every student.' },
    { n: 6, title: 'Project Based', text: 'Active learning by creating, experimenting, and solving real-world problems.' },
];

const safety = [
    { icon: 'how_to_reg', title: 'Background-checked tutors', text: 'Every tutor passes an identity and criminal background check before their first class, renewed every year.' },
    { icon: 'security', title: 'Trained to protect', text: 'All tutors complete child-safeguarding training.' },
    { icon: 'lock', title: 'Secure classes only', text: 'Classes run on our secure platform. Tutors never contact students by phone, text or social media.' },
    { icon: 'video_camera_front', title: 'Every class recorded', text: 'Recordings are available to you for 30 days, then deleted.' },
    { icon: 'waving_hand', title: "You're always welcome", text: 'Sit in on any class. All messages from tutors come to you.' },
    { icon: 'policy', title: 'Privacy you control', text: "We follow COPPA, collect only what's needed, never sell data, and delete it on request." },
];

const offerIconClass = {
    primary: 'bg-primary/10 text-primary',
    secondary: 'bg-secondary/10 text-secondary',
    tertiary: 'bg-tertiary/10 text-tertiary',
    error: 'bg-error/10 text-error',
};

const offers = [
    { emoji: '👥', color: 'primary', title: 'Live Small-Group & One-on-One Classes', text: 'At Younimatiq, all sessions are conducted live by experienced educators, enabling real-time interaction, meaningful discussions, and personalized attention.' },
    { emoji: '🎨', color: 'secondary', title: 'Age-Appropriate, Skill-Focused', text: 'Our lessons are designed to match each child’s age, learning level, and goals—ensuring steady progress and meaningful development.' },
    { emoji: '🎓', color: 'tertiary', title: 'Interdisciplinary Learning', text: 'Our programs connect multiple subjects like academics, coding, STEM, creativity, and communication—helping students build a well-rounded understanding.' },
    { emoji: '📘', color: 'primary', title: 'Hands-On, Project-Based', text: 'Students actively learn by creating, experimenting, and solving real-world problems through engaging, activity-based lessons.' },
    { emoji: '💻', color: 'secondary', title: 'Creative & Game-Based Platforms', text: 'We use platforms like Scratch, Minecraft, Python, and robotics tools to make learning interactive, fun, and highly engaging.' },
    { emoji: '❤️', color: 'error', title: 'Supportive Learning Environment', text: 'We foster a positive and inclusive space where students feel confident, encouraged, and comfortable expressing their ideas.' },
];

export default function About() {
    return (
        <PublicLayout title="About Younimatiq">
            <Head title="About Younimatiq">
                <meta
                    head-key="description"
                    name="description"
                    content="Discover Younimatiq – a personalized online learning platform for kids. We offer interactive 1:1 classes in Coding, Math, STEM, and more for K-12 students."
                />
            </Head>
            <BrandHero className="mt-8 mb-4" eyebrow="Get to Know Us">
                <h1 className="brand-title">
                    About <span className="text-brand-accent">Younimatiq</span>
                </h1>
                <p className="brand-description">
                    The world our children are stepping into is constantly evolving, and success now requires more than just academic knowledge. Skills like critical thinking, creativity, and problem-solving are becoming essential.
                </p>
            </BrandHero>

            <section className="py-24 px-4 md:px-8 max-w-7xl mx-auto">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div className="clay-card p-10 bg-gradient-to-br from-surface to-primary/5 border border-primary/20">
                        <div className="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white mb-6 transform -rotate-6">
                            <Icon name="target" className="text-4xl" />
                        </div>
                        <h2 className="section-heading mb-4"><span className="accent-green">Our</span> Mission</h2>
                        <p className="font-body-md text-on-surface-variant leading-relaxed mb-4">
                            At <strong>Younimatiq</strong>, our mission is to turn screen time into skill-building time. Through coding, creativity, and real-world problem-solving, children don’t just learn—they build, create, and think independently.
                        </p>
                        <p className="font-body-md text-on-surface-variant leading-relaxed">
                            Our expert educators use a multidisciplinary, polymath-inspired approach—connecting science, art, math, and technology—to help children think across subjects, not in silos.
                        </p>
                    </div>
                    <div className="clay-card p-10 bg-gradient-to-br from-surface to-secondary/5 border border-secondary/20">
                        <div className="w-16 h-16 bg-secondary rounded-2xl flex items-center justify-center text-white mb-6 transform rotate-6">
                            <Icon name="rocket_launch" className="text-4xl" />
                        </div>
                        <h2 className="section-heading mb-4"><span className="accent-green">Our</span> Vision</h2>
                        <p className="font-body-md text-on-surface-variant leading-relaxed mb-4">
                            Our vision is to build a generation of thinkers, creators, and innovators. Children who don’t just consume technology—but create it.
                        </p>
                        <p className="font-body-md text-on-surface-variant leading-relaxed">
                            By blending STEM, creativity, and real-world application, we help learners grow into confident problem-solvers ready for the future.
                        </p>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-surface-container-low rounded-[3rem] mx-gutter-mobile md:mx-gutter-desktop mb-24 px-8 md:px-16">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 className="section-heading mb-6">The Younimatiq <span className="accent-green">Story</span></h2>
                        <div className="space-y-4 text-on-surface-variant font-body-md leading-relaxed">
                            <p>Every child is naturally curious — always asking questions, exploring ideas, and trying to understand the world around them. But somewhere along the way, learning often becomes limited to memorization.</p>
                            <p>At <strong>Younimatiq</strong>, we saw this gap and knew something needed to change.</p>
                            <p>We started with a simple belief — that learning should inspire, not pressure. Education should build confidence, not just report cards.</p>
                            <p>Today, Younimatiq is more than just a platform. It is a growing community of learners who are curious, confident, and ready to take on the future.</p>
                        </div>
                    </div>
                    <div className="relative group">
                        <div className="absolute inset-0 bg-primary/20 rounded-[2rem] transform translate-x-4 translate-y-4 group-hover:translate-x-6 group-hover:translate-y-6 transition-transform" />
                        <img src="/wp_assets/about_files/children-learning.webp" alt="Children Learning" className="relative rounded-[2rem] shadow-xl w-full h-auto object-cover border-4 border-white" />
                    </div>
                </div>
            </section>

            <section className="py-24 px-4 md:px-8 max-w-7xl mx-auto mb-12">
                <div className="text-center mb-16">
                    <h2 className="section-heading">Why <span className="accent-green">Choose Us</span></h2>
                    <p className="font-body-md text-on-surface-variant mt-4">What makes Younimatiq a great place to learn and grow.</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {whyUs.map((item) => (
                        <div key={item.n} className="clay-card p-8 flex flex-col gap-4">
                            <div className={`w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl ${whyUsBadgeClass[item.n]}`}>{item.n}</div>
                            <h3 className="font-headline-sm font-bold text-on-surface">{item.title}</h3>
                            <p className="text-sm text-on-surface-variant">{item.text}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section id="safety" className="py-4 px-4 md:px-8 max-w-7xl mx-auto mb-16 scroll-mt-24">
                <div className="clay-card p-8 md:p-12 bg-white rounded-[2rem] shadow-md border-t-8 border-[#38B91F] relative overflow-hidden">
                    <div className="absolute top-0 right-0 -mr-16 -mt-16 text-[#38B91F]/5 pointer-events-none">
                        <Icon name="shield" className="text-[240px]" />
                    </div>
                    <div className="relative z-10">
                        <div className="flex flex-col md:flex-row items-center md:items-start gap-4 mb-10 text-center md:text-left">
                            <div className="w-14 h-14 shrink-0 rounded-full bg-[#38B91F]/10 flex items-center justify-center text-[#38B91F]">
                                <Icon name="shield" className="text-3xl" />
                            </div>
                            <div>
                                <h2 className="text-3xl md:text-4xl font-extrabold text-on-surface mb-2" style={{ fontFamily: "'Baloo 2', sans-serif" }}>Your child&apos;s safety comes first</h2>
                                <p className="text-on-surface-variant font-medium">We provide a secure, trusted learning environment for every student.</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10 mb-10">
                            {safety.map((item) => (
                                <div key={item.title} className="flex items-start gap-3">
                                    <Icon name={item.icon} className="text-[#38B91F] mt-0.5" />
                                    <div>
                                        <h3 className="font-bold text-on-surface text-lg mb-1">{item.title}</h3>
                                        <p className="text-sm text-on-surface-variant leading-relaxed">{item.text}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="bg-surface-container-low border border-surface-container-high p-5 rounded-2xl flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                            <div className="w-10 h-10 shrink-0 rounded-full bg-white flex items-center justify-center text-primary shadow-sm">
                                <Icon name="mail" />
                            </div>
                            <p className="text-sm font-medium text-on-surface-variant m-0">
                                Questions or concerns? Email <a href="mailto:safety@younimatiq.com" className="text-primary hover:underline font-bold">safety@younimatiq.com</a>; we reply within 24 hours.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 px-4 md:px-8 max-w-7xl mx-auto mb-12 bg-surface-container-low rounded-[3rem] shadow-sm">
                <div className="text-center mb-16">
                    <h2 className="section-heading">What We Offer At <span className="accent-green">Younimatiq</span></h2>
                    <p className="font-body-md text-on-surface-variant mt-4">A holistic approach to education that prepares children for tomorrow.</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-4 md:px-12">
                    {offers.map((item) => (
                        <div key={item.title} className="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
                            <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform text-3xl ${offerIconClass[item.color]}`}>
                                {item.emoji}
                            </div>
                            <h3 className="font-headline-sm font-bold text-on-surface mb-3">{item.title}</h3>
                            <p className="text-sm text-on-surface-variant">{item.text}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="py-16 px-4 md:px-8 max-w-7xl mx-auto mb-24">
                <div className="brand-panel p-12 md:p-16 text-center">
                    <p className="brand-eyebrow">Get Started</p>
                    <h2 className="brand-title">Ready to join the <span className="text-brand-accent">adventure?</span></h2>
                    <p className="brand-description mb-8">Explore our courses or reach out — we&apos;re here to help your child start learning.</p>
                    <div className="flex flex-wrap justify-center gap-4">
                        <Link href="/courses" className="btn-enroll">View Our Courses</Link>
                        <Link href="/contact" className="btn-whatsapp-light">Contact Us</Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
