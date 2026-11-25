@extends('layouts.app')

@section('seo_title', 'About Younimatiq')
@section('seo_description', 'Discover Younimatiq – a personalized online learning platform for kids. We offer interactive 1:1 classes in Coding, Math, STEM, and more for K-12 students.')

@section('content')
<x-brand-hero class="mt-8 mb-4" eyebrow="Get to Know Us">
    <h1 class="brand-title">About <span class="text-brand-accent">Younimatiq</span></h1>
    <p class="brand-description">
        The world our children are stepping into is constantly evolving, and success now requires more than just academic knowledge. Skills like critical thinking, creativity, and problem-solving are becoming essential.
    </p>
</x-brand-hero>

<!-- Mission & Vision -->
<section class="py-24 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Mission -->
        <div class="clay-card p-10 bg-gradient-to-br from-surface to-primary/5 border border-primary/20">
            <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white mb-6 transform -rotate-6">
                <span class="material-symbols-outlined text-4xl">target</span>
            </div>
            <h2 class="section-heading mb-4"><span class="accent-green">Our</span> Mission</h2>
            <p class="font-body-md text-on-surface-variant leading-relaxed mb-4">
                At <strong>Younimatiq</strong>, our mission is to turn screen time into skill-building time. Through coding, creativity, and real-world problem-solving, children don’t just learn—they build, create, and think independently.
            </p>
            <p class="font-body-md text-on-surface-variant leading-relaxed">
                Our expert educators use a multidisciplinary, polymath-inspired approach—connecting science, art, math, and technology—to help children think across subjects, not in silos.
            </p>
        </div>

        <!-- Vision -->
        <div class="clay-card p-10 bg-gradient-to-br from-surface to-secondary/5 border border-secondary/20">
            <div class="w-16 h-16 bg-secondary rounded-2xl flex items-center justify-center text-white mb-6 transform rotate-6">
                <span class="material-symbols-outlined text-4xl">rocket_launch</span>
            </div>
            <h2 class="section-heading mb-4"><span class="accent-green">Our</span> Vision</h2>
            <p class="font-body-md text-on-surface-variant leading-relaxed mb-4">
                Our vision is to build a generation of thinkers, creators, and innovators. Children who don’t just consume technology—but create it.
            </p>
            <p class="font-body-md text-on-surface-variant leading-relaxed">
                By blending STEM, creativity, and real-world application, we help learners grow into confident problem-solvers ready for the future.
            </p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="py-24 bg-surface-container-low rounded-[3rem] mx-gutter-mobile md:mx-gutter-desktop mb-24 px-8 md:px-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
            <h2 class="section-heading mb-6">The Younimatiq <span class="accent-green">Story</span></h2>
            <div class="space-y-4 text-on-surface-variant font-body-md leading-relaxed">
                <p>Every child is naturally curious — always asking questions, exploring ideas, and trying to understand the world around them. But somewhere along the way, learning often becomes limited to memorization.</p>
                <p>At <strong>Younimatiq</strong>, we saw this gap and knew something needed to change.</p>
                <p>We started with a simple belief — that learning should inspire, not pressure. Education should build confidence, not just report cards.</p>
                <p>Today, Younimatiq is more than just a platform. It is a growing community of learners who are curious, confident, and ready to take on the future.</p>
            </div>
        </div>
        <div class="relative group">
            <div class="absolute inset-0 bg-primary/20 rounded-[2rem] transform translate-x-4 translate-y-4 group-hover:translate-x-6 group-hover:translate-y-6 transition-transform"></div>
            <img src="{{ asset('wp_assets/about_files/children-learning.webp') }}" alt="Children Learning" class="relative rounded-[2rem] shadow-xl w-full h-auto object-cover border-4 border-white">
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-24 px-4 md:px-8 max-w-7xl mx-auto mb-12">
    <div class="text-center mb-16">
        <h2 class="section-heading">Why <span class="accent-green">Choose Us</span></h2>
        <p class="font-body-md text-on-surface-variant mt-4">What makes Younimatiq a great place to learn and grow.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">1</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Experienced Educators</h3>
            <p class="text-sm text-on-surface-variant">Our educators bring real classroom and online teaching experience.</p>
        </div>
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-secondary font-bold text-xl">2</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Trained for Kids</h3>
            <p class="text-sm text-on-surface-variant">Interactive methods, storytelling, and fun activities that make learning enjoyable.</p>
        </div>
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-tertiary/10 flex items-center justify-center text-tertiary font-bold text-xl">3</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Carefully Screened</h3>
            <p class="text-sm text-on-surface-variant">We carefully select our educators through a rigorous interview and vetting process.</p>
        </div>
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center text-error font-bold text-xl">4</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Subject Expertise</h3>
            <p class="text-sm text-on-surface-variant">Specialized teachers in robotics, communication, creative arts, and more.</p>
        </div>
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">5</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Live 1:1</h3>
            <p class="text-sm text-on-surface-variant">Real-time interaction and personalized attention for every student.</p>
        </div>
        <div class="clay-card p-8 flex flex-col gap-4">
            <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-secondary font-bold text-xl">6</div>
            <h3 class="font-headline-sm font-bold text-on-surface">Project Based</h3>
            <p class="text-sm text-on-surface-variant">Active learning by creating, experimenting, and solving real-world problems.</p>
        </div>
    </div>
</section>

<!-- Safety Section -->
<section id="safety" class="py-4 px-4 md:px-8 max-w-7xl mx-auto mb-16 scroll-mt-24">
    <div class="clay-card p-8 md:p-12 bg-white rounded-[2rem] shadow-md border-t-8 border-[#38B91F] relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 text-[#38B91F]/5 pointer-events-none">
            <span class="material-symbols-outlined" style="font-size: 240px;">shield</span>
        </div>
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-4 mb-10 text-center md:text-left">
                <div class="w-14 h-14 shrink-0 rounded-full bg-[#38B91F]/10 flex items-center justify-center text-[#38B91F]">
                    <span class="material-symbols-outlined text-3xl">shield</span>
                </div>
                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-on-surface mb-2" style="font-family: 'Baloo 2', sans-serif;">Your child's safety comes first</h2>
                    <p class="text-on-surface-variant font-medium">We provide a secure, trusted learning environment for every student.</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10 mb-10">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">how_to_reg</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">Background-checked tutors</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">Every tutor passes an identity and criminal background check before their first class, renewed every year.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">security</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">Trained to protect</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">All tutors complete child-safeguarding training.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">lock</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">Secure classes only</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">Classes run on our secure platform. Tutors never contact students by phone, text or social media.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">video_camera_front</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">Every class recorded</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">Recordings are available to you for 30 days, then deleted.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">waving_hand</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">You're always welcome</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">Sit in on any class. All messages from tutors come to you.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#38B91F] mt-0.5">policy</span>
                    <div>
                        <h3 class="font-bold text-on-surface text-lg mb-1">Privacy you control</h3>
                        <p class="text-sm text-on-surface-variant leading-relaxed">We follow COPPA, collect only what's needed, never sell data, and delete it on request.</p>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-low border border-surface-container-high p-5 rounded-2xl flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                <div class="w-10 h-10 shrink-0 rounded-full bg-white flex items-center justify-center text-primary shadow-sm">
                    <span class="material-symbols-outlined">mail</span>
                </div>
                <p class="text-sm font-medium text-on-surface-variant m-0">Questions or concerns? Email <a href="mailto:safety@younimatiq.com" class="text-primary hover:underline font-bold">safety@younimatiq.com</a>; we reply within 24 hours.</p>
            </div>
        </div>
    </div>
</section>

<!-- What We Offer -->
<section class="py-24 px-4 md:px-8 max-w-7xl mx-auto mb-12 bg-surface-container-low rounded-[3rem] shadow-sm">
    <div class="text-center mb-16">
        <h2 class="section-heading">What We Offer At <span class="accent-green">Younimatiq</span></h2>
        <p class="font-body-md text-on-surface-variant mt-4">A holistic approach to education that prepares children for tomorrow.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-4 md:px-12">
        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">👥</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Live Small-Group &amp; One-on-One Classes</h3>
            <p class="text-sm text-on-surface-variant">At Younimatiq, all sessions are conducted live by experienced educators, enabling real-time interaction, meaningful discussions, and personalized attention.</p>
        </div>

        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">🎨</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Age-Appropriate, Skill-Focused</h3>
            <p class="text-sm text-on-surface-variant">Our lessons are designed to match each child’s age, learning level, and goals—ensuring steady progress and meaningful development.</p>
        </div>

        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-tertiary/10 text-tertiary flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">🎓</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Interdisciplinary Learning</h3>
            <p class="text-sm text-on-surface-variant">Our programs connect multiple subjects like academics, coding, STEM, creativity, and communication—helping students build a well-rounded understanding.</p>
        </div>

        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">📘</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Hands-On, Project-Based</h3>
            <p class="text-sm text-on-surface-variant">Students actively learn by creating, experimenting, and solving real-world problems through engaging, activity-based lessons.</p>
        </div>

        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">💻</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Creative &amp; Game-Based Platforms</h3>
            <p class="text-sm text-on-surface-variant">We use platforms like Scratch, Minecraft, Python, and robotics tools to make learning interactive, fun, and highly engaging.</p>
        </div>

        <div class="clay-card p-8 bg-white flex flex-col items-center text-center group hover:scale-[1.02] transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-error/10 text-error flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                <span class="text-3xl">❤️</span>
            </div>
            <h3 class="font-headline-sm font-bold text-on-surface mb-3">Supportive Learning Environment</h3>
            <p class="text-sm text-on-surface-variant">We foster a positive and inclusive space where students feel confident, encouraged, and comfortable expressing their ideas.</p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 px-4 md:px-8 max-w-7xl mx-auto mb-24">
    <div class="brand-panel p-12 md:p-16 text-center">
        <p class="brand-eyebrow">Get Started</p>
        <h2 class="brand-title">Ready to join the <span class="text-brand-accent">adventure?</span></h2>
        <p class="brand-description mb-8">Explore our courses or reach out — we're here to help your child start learning.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('courses.index') }}" class="btn-enroll">View Our Courses</a>
            <a href="{{ route('contact') }}" class="btn-whatsapp-light">Contact Us</a>
        </div>
    </div>
</section>
@endsection
