import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PublicLayout from '../../../Layouts/PublicLayout';
import CourseMarquee from '../../../Components/CourseMarquee';

const HIGHLIGHTS = [
    { title: 'Real skills. Real projects. Real confidence.', text: null },
    { title: 'From playing games to building them.', text: null },
    { title: 'Problem-solving', text: 'Thinks in logical steps, which helps at school too.' },
    { title: 'Real projects', text: 'A game, robot or 3D model every class.' },
    { title: 'Confidence', text: 'A 1:1 coach, so your child is never lost in a crowd.' },
];

function openTrial(event, course = null) {
    event?.preventDefault?.();
    if (typeof window.openLeadModal !== 'function') return;
    const categoryId = course?.category_id || '';
    const courseTitle = course?.title || course?.landing_title || '';
    window.openLeadModal(event || { preventDefault() {} }, 'trial', categoryId, courseTitle);
}

function gradeLabel(course) {
    if (course.grade_min != null && course.grade_max != null) {
        return course.grade_min === course.grade_max
            ? `Grade ${course.grade_min}`
            : `Grades ${course.grade_min}–${course.grade_max}`;
    }
    return course.age_group || null;
}

export default function ChildrensDay({ courses = [] }) {
    const [ready, setReady] = useState(false);
    useEffect(() => {
        const id = window.requestAnimationFrame(() => setReady(true));
        return () => window.cancelAnimationFrame(id);
    }, []);

    return (
        <PublicLayout title="Children's Day Special Courses | Younimatiq">
            <Head title="Children's Day Special Courses | Younimatiq">
                <meta
                    name="description"
                    content="Happy Children’s Day from Younimatiq — every child is a creator. Live 1:1 classes in Minecraft, Roblox, AI, robotics and 3D Modelling. Book a free trial."
                />
                <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Nunito:wght@500;700;800&display=swap" rel="stylesheet" />
            </Head>

            <section className="relative overflow-hidden min-h-[88vh] flex items-end md:items-center">
                <img
                    src="/images/childrens-day/hero.webp"
                    alt=""
                    className="absolute inset-0 w-full h-full object-cover"
                    fetchPriority="high"
                />
                <div
                    className="absolute inset-0"
                    style={{
                        background:
                            'linear-gradient(105deg, rgba(12, 24, 48, 0.88) 0%, rgba(12, 24, 48, 0.55) 48%, rgba(255, 176, 32, 0.25) 100%)',
                    }}
                />
                <div
                    className={`relative z-10 max-w-7xl mx-auto px-4 py-16 md:py-24 w-full transition-all duration-700 ${ready ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`}
                >
                    <p className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-extrabold mb-5 bg-amber-400/20 text-amber-200 border border-amber-300/40">
                        🎈 Happy Children’s Day!
                    </p>
                    <h1
                        className="text-4xl sm:text-5xl md:text-6xl font-extrabold max-w-3xl leading-tight"
                        style={{ fontFamily: 'Sora, Nunito, sans-serif', letterSpacing: '-0.02em' }}
                    >
                        <span className="text-white">Every child is a creator.</span>
                        <span className="block mt-2 text-amber-300">Let’s help them build.</span>
                    </h1>
                    <p className="mt-5 text-lg md:text-xl text-white/90 max-w-2xl leading-relaxed">
                        This Children’s Day, give your child more than a gift: a skill they’ll keep for life. Live 1:1 classes in Minecraft, Roblox, AI, robotics and 3D Modelling.
                    </p>
                    <div className="mt-8">
                        <button
                            type="button"
                            onClick={(e) => openTrial(e)}
                            className="px-7 py-3.5 rounded-full font-bold text-slate-900 bg-amber-300 hover:bg-amber-200 transition"
                        >
                            Book a free trial
                        </button>
                        <p className="mt-4 text-sm md:text-base font-semibold text-white/90">
                            ⭐ 100% live · 1:1 expert coaches · Real projects every class
                        </p>
                    </div>
                </div>
            </section>

            <section className="py-16 md:py-20" style={{ background: 'linear-gradient(180deg, #fff8e8 0%, #ffffff 55%, #eef9ff 100%)' }}>
                <div className="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p className="text-sm font-extrabold uppercase tracking-[0.16em] text-amber-700 mb-3">What your child walks away with</p>
                        <div className="mt-6 space-y-5">
                            {HIGHLIGHTS.map((item, index) => (
                                <div key={`${index}-${item.title}`} className="flex gap-4">
                                    <div className="w-8 h-8 rounded-full bg-amber-400 text-slate-900 text-sm font-extrabold flex items-center justify-center shrink-0">
                                        {index + 1}
                                    </div>
                                    <div>
                                        <h3 className="font-extrabold text-slate-900 text-lg leading-snug" style={{ fontFamily: 'Sora, sans-serif' }}>
                                            {item.title}
                                        </h3>
                                        {item.text ? <p className="text-slate-600 text-sm mt-1 leading-relaxed">{item.text}</p> : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="relative">
                        <img
                            src="/images/childrens-day/kids.webp"
                            alt="Children celebrating learning"
                            className="w-full rounded-[2rem] object-cover shadow-xl border border-amber-100"
                            loading="lazy"
                        />
                        <div className="absolute -bottom-4 -left-2 md:-left-6 bg-white rounded-2xl px-4 py-3 shadow-lg border border-amber-100">
                            <p className="text-xs font-bold text-amber-700 uppercase">Children’s Day focus</p>
                            <p className="font-extrabold text-slate-900">Learn. Build. Grow.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="courses" className="py-16 md:py-20 bg-white">
                <div className="w-full px-4 md:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-10">
                        <h2 className="text-3xl md:text-4xl font-extrabold text-slate-900" style={{ fontFamily: 'Sora, sans-serif' }}>
                            Popular courses to start this season
                        </h2>
                        <p className="mt-3 text-slate-600">Start with what your child already loves. We’ll turn it into real skills.</p>
                    </div>

                    {courses.length ? (
                        <CourseMarquee>
                            {courses.map((course) => {
                                const title = course.landing_title || course.title;
                                const meta = gradeLabel(course);
                                return (
                                    <article
                                        key={course.id || course.slug}
                                        className="shrink-0 w-72 md:w-80 rounded-3xl overflow-hidden bg-[#fffdf8] border border-amber-100 flex flex-col shadow-[0_10px_30px_rgba(180,120,20,0.08)]"
                                    >
                                        <div className="relative block w-full overflow-hidden aspect-square bg-amber-50">
                                            {course.thumbnail_url ? (
                                                <img
                                                    src={course.thumbnail_url}
                                                    alt={title}
                                                    className="absolute inset-0 w-full h-full object-cover"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-amber-700 font-bold">Younimatiq</div>
                                            )}
                                        </div>
                                        <div className="p-5 flex flex-col flex-1">
                                            {meta ? <p className="text-xs font-bold uppercase tracking-wide text-sky-700 mb-2">{meta}</p> : null}
                                            <h3 className="text-lg font-extrabold text-slate-900 mb-2 line-clamp-2">{title}</h3>
                                            {course.description ? <p className="text-sm text-slate-600 line-clamp-3 flex-1 mb-4">{course.description}</p> : <div className="flex-1 mb-4" />}
                                            <button
                                                type="button"
                                                onClick={(e) => openTrial(e, course)}
                                                className="w-full py-2.5 rounded-full font-bold text-white text-center mt-auto"
                                                style={{ background: 'linear-gradient(135deg, #f59e0b, #ea580c)' }}
                                            >
                                                Book a trial class
                                            </button>
                                        </div>
                                    </article>
                                );
                            })}
                        </CourseMarquee>
                    ) : (
                        <p className="text-center text-slate-500">Courses coming soon.</p>
                    )}
                </div>
            </section>

            <section className="py-16 md:py-20" style={{ background: 'linear-gradient(135deg, #0ea5e9 0%, #0369a1 55%, #0f172a 100%)' }}>
                <div className="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p className="text-sm font-extrabold uppercase tracking-[0.16em] text-sky-200 mb-3">This Children’s Day</p>
                        <h2 className="text-3xl md:text-4xl font-extrabold text-white" style={{ fontFamily: 'Sora, sans-serif' }}>
                            Gift a skill, not just a toy.
                        </h2>
                        <p className="mt-4 text-sky-100 text-lg leading-relaxed">
                            Turn your child’s love for games into skills they’ll keep for life. Live 1:1 classes with expert coaches, and a real project every class.
                        </p>
                        <button
                            type="button"
                            onClick={(e) => openTrial(e)}
                            className="mt-8 px-8 py-3.5 rounded-full font-bold text-slate-900 bg-amber-300 hover:bg-amber-200 transition"
                        >
                            Gift a free class
                        </button>
                    </div>
                    <img
                        src="/images/childrens-day/gift.webp"
                        alt="Child in a live 1:1 class with a parent"
                        className="w-full rounded-[2rem] object-cover shadow-2xl border border-white/20"
                        loading="lazy"
                    />
                </div>
            </section>

            <section className="py-16 md:py-20 bg-[#fff8e8]">
                <div className="max-w-3xl mx-auto px-4 text-center">
                    <p className="text-4xl mb-4">🚀📚🤖</p>
                    <h2 className="text-3xl md:text-4xl font-extrabold text-slate-900" style={{ fontFamily: 'Sora, sans-serif' }}>
                        Ready for a trial class?
                    </h2>
                    <p className="mt-4 text-slate-600 text-lg">
                        Share your child’s age and interests — we’ll match the right coach and course for the trial.
                    </p>
                    <div className="mt-8 flex justify-center">
                        <button type="button" onClick={(e) => openTrial(e)} className="px-8 py-3.5 rounded-full font-bold text-white" style={{ background: 'linear-gradient(135deg, #f59e0b, #ea580c)' }}>
                            Book a trial class
                        </button>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
