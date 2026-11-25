const WHY = [
    {
        icon: '🎯',
        title: '1:1 Personalized Coaching',
        text: 'Certified expert tutors dedicated to your child — not group classes where kids get lost.',
    },
    {
        icon: '🎓',
        title: 'Harvard & MIT Expert Curriculum',
        text: 'Rigorous, Common Core-aligned programs built by world-class educators.',
    },
    {
        icon: '🗂️',
        title: 'Real Progress, Real Portfolio',
        text: 'Every course ends in a shareable project — tangible proof of your child’s skills.',
    },
    {
        icon: '🔄',
        title: 'Younimatiq Learning Guarantee',
        text: 'If a concept doesn’t click, your child gets a free class replay — no questions asked.',
    },
];

const FAQS = [
    {
        q: 'Why 1:1 instead of group classes?',
        a: 'Every Younimatiq class is one-on-one with a dedicated instructor, so the pace, project difficulty, and feedback are built entirely around your child — no waiting on slower classmates and no feeling rushed by faster ones.',
    },
    {
        q: 'Do I need to buy hardware separately?',
        a: 'Explorer and Builder tracks use simulators for most lessons, so no hardware purchase is required to start. The Innovator track uses physical kits, available to purchase or rent separately.',
    },
    {
        q: 'Can I switch tracks later?',
        a: 'Yes — you can move up (or down) a track as your child progresses, at the start of your next 24-Class Plan. Your instructor and personalized pacing stay the same.',
    },
    {
        q: 'What happens if we miss a class?',
        a: 'If you miss a class, a recording of the session will be sent to you so your child doesn’t fall behind. If anything in it isn’t clear, one of our Master Teachers will step in to walk your child through it.',
    },
];

const REVIEWS = [
    {
        quote: 'My son went from dreading math homework to asking for extra practice. The 1:1 pace made all the difference.',
        name: 'Priya M.',
        meta: 'Parent · Math',
    },
    {
        quote: 'She built her first working app in eight weeks. I didn’t expect that so soon — she’s genuinely proud of it.',
        name: 'James L.',
        meta: 'Parent · Coding',
    },
    {
        quote: 'My daughter loves her robotics sessions! Her coach explains complex engineering concepts in such a fun way.',
        name: 'Anita K.',
        meta: 'Parent · Robotics',
    },
    {
        quote: 'My son stopped just playing video games and started building his own levels. It transformed screen time completely!',
        name: 'Robert C.',
        meta: 'Parent · Minecraft',
    },
    {
        quote: 'The AI Mastery track finally gave us a way to talk about AI at home that felt safe and age-appropriate.',
        name: 'Sofia R.',
        meta: 'Parent · AI',
    },
    {
        quote: 'Her creative writing confidence skyrocketed. She went from struggling with essays to writing her own short stories.',
        name: 'Sarah T.',
        meta: 'Parent · English',
    },
];

const GLOBAL_PLANS = [
    { name: '12-Class Plan', priceUsd: 18, wasUsd: 22, save: 'SAVE 18%', classes: '12 classes total', popular: false },
    { name: '24-Class Plan', priceUsd: 16, wasUsd: 20, save: 'SAVE 20%', classes: '24 classes total', popular: true },
    { name: '48-Class Plan', priceUsd: 14, wasUsd: 18, save: 'SAVE 22%', classes: '48 classes total', popular: false },
];

const INR_PLANS = [
    {
        name: 'Group Program',
        price: '₹500',
        was: '₹1,000',
        save: 'SAVE 50%',
        blurb: 'Full access to your child’s track, taught by expert instructors.',
        features: ['Group class', '1–2 live classes / week', 'Track-matched curriculum & projects', 'Progress reports after every class', 'Flexible reschedule, easy make-ups'],
        popular: false,
    },
    {
        name: '1:1 Program',
        price: '₹600',
        was: '₹1,200',
        save: 'SAVE 50%',
        blurb: 'Full access to your child’s track, with a dedicated instructor.',
        features: ['1:1 personalized class', '1–2 live classes / week', 'Track-matched curriculum & projects', 'Progress reports after every class', 'Flexible reschedule, easy make-ups'],
        popular: true,
    },
];

const PLAN_FEATURES = [
    '1:1 with a dedicated instructor',
    '1–2 live classes / week',
    'Track-matched curriculum & projects',
    'Progress reports after every class',
    'Flexible reschedule, easy make-ups',
];

export const MARKETING_LANDINGS = {
    math: {
        key: 'math',
        leadType: 'math',
        title: 'Math Classes for Kids | Younimatiq',
        description: 'Live 1:1 math coaching that builds confidence, fluency, and real problem-solving skills.',
        badge: 'See Math Everywhere',
        heroTitle: ['Where Every Child Becomes a ', { accent: 'Math Expert' }],
        heroText:
            'Younimatiq’s Math Lab is a live, 1:1 online studio where students build number sense, problem-solving, and confidence — turning math anxiety into curiosity.',
        heroImage: '/math_assets/math-hero.jpg',
        heroImageClass: 'object-cover rounded-3xl shadow-2xl',
        coursesHeading: 'Pick Your Path in Math',
        coursesCta: { label: 'Explore Math Courses →', href: '/courses/category/math-camp' },
        videos: [
            '/math_assets/math-video-1.mp4',
            '/math_assets/math-video-2.mp4',
            '/math_assets/math-video-3.mp4',
            '/math_assets/math-video-4.mp4',
            '/math_assets/math-video-5.mp4',
        ],
        pricingBadge: 'Younimatiq • Math Program',
        pricingTitle: 'Live, instructor-led Math classes for ages 6–14.',
        pricingSubtitle: '1:1 Personalized classes, paced entirely to your child — grow confidence with a dedicated instructor.',
        showPricing: true,
        why: WHY,
        faqs: FAQS,
        reviews: REVIEWS,
        inrPlans: INR_PLANS.map((p) => ({ ...p, name: p.name.replace('Program', 'Math Program') })),
        globalPlans: GLOBAL_PLANS,
        planFeatures: PLAN_FEATURES,
        consultText: 'Book a free consultation and get a personalized Math Lab plan for your child — no commitment required.',
    },
    minecraft: {
        key: 'minecraft',
        leadType: 'minecraft',
        title: 'Minecraft Coding & Game Design Classes for Kids | Younimatiq',
        description: 'Learn coding and game design through Minecraft at Younimatiq. Interactive, live online classes teaching real programming skills to kids.',
        badge: '⛏️ Turn Screen Time Into Skill Time',
        heroTitle: ['Build, Code, and Create in ', { accent: 'Minecraft' }, '.'],
        heroText:
            "Younimatiq's Minecraft Lab is a live, 1:1 online studio where students learn logic, redstone engineering, and Java modding — turning their favorite game into a powerful learning engine.",
        heroImage: '/images/minecraft_character.webp',
        heroImageClass: 'object-contain drop-shadow-2xl max-w-lg mx-auto',
        heroBackground: '/images/minecraft_hero_1787217370958.webp',
        coursesHeading: 'Pick Your Path in Minecraft',
        coursesCta: { label: 'Explore Minecraft Courses →', href: '/courses/category/minecraft-camp' },
        videos: [
            '/minecraft_videos/video1.mp4',
            '/minecraft_videos/video2.mp4',
            '/minecraft_videos/video3.mp4',
            '/minecraft_videos/video4.mp4',
            '/minecraft_videos/video5.mp4',
        ],
        pricingBadge: 'Younimatiq • Minecraft Program',
        pricingTitle: 'Live, instructor-led Minecraft classes for ages 6–14.',
        pricingSubtitle: '1:1 Personalized classes, paced entirely to your child — build redstone circuits, write real code, and ship a mod with a dedicated instructor.',
        showPricing: true,
        why: WHY,
        faqs: FAQS,
        reviews: REVIEWS,
        inrPlans: INR_PLANS.map((p) => ({ ...p, name: p.name.replace('Program', 'Minecraft Program') })),
        globalPlans: GLOBAL_PLANS,
        planFeatures: PLAN_FEATURES,
        consultText: 'Book a free consultation and get a personalized Minecraft Lab plan for your child — no commitment required.',
    },
    robotics: {
        key: 'robotics',
        leadType: 'robotics',
        title: 'AI & Robotics Classes for Kids | Younimatiq',
        description: 'Build, code, and engineer with project-based robotics and AI pathways at Younimatiq.',
        badge: '🌱 Turn Screen Time Into Skill Time',
        heroTitle: ['Where Kids Become ', { accent: 'AI & STEM Creators' }, ' — Not Just Consumers.'],
        heroText:
            "Younimatiq's AI & STEM Lab is a live, 1:1 online studio for AI-Robotics, Coding, and 3D Design — where students turn screen time into real, portfolio-worthy skills, guided by expert coaches.",
        heroImage: '/images/robot_ai_generated.webp',
        heroImageClass: 'object-cover rounded-3xl shadow-2xl',
        heroGradient: 'linear-gradient(135deg, rgb(30, 58, 138) 0%, rgb(29, 78, 216) 55%, rgb(37, 99, 235) 100%)',
        coursesHeading: 'Pick Your Path in AI-Robotics',
        coursesCta: { label: 'Explore AI-Robotics Courses →', href: '/courses/category/ai-robotics' },
        videos: [
            '/community_videos/video1.mp4',
            '/community_videos/video2.mp4',
            '/community_videos/video3.mp4',
            '/community_videos/video4.mp4',
            '/community_videos/video5.mp4',
        ],
        pricingBadge: 'Younimatiq • AI & STEM Program',
        pricingTitle: 'Live, instructor-led AI & Robotics classes for ages 6–14.',
        pricingSubtitle: '1:1 Personalized classes, paced entirely to your child — build robots, train models, and ship projects with a dedicated instructor.',
        showPricing: true,
        why: WHY,
        faqs: FAQS,
        reviews: REVIEWS,
        inrPlans: INR_PLANS.map((p) => ({ ...p, name: p.name.replace('Program', 'AI & STEM Program') })),
        globalPlans: GLOBAL_PLANS,
        planFeatures: PLAN_FEATURES,
        consultText: 'Book a free consultation and get a personalized AI & STEM Lab plan for your child — no commitment required.',
    },
    'childrens-day': {
        key: 'childrens-day',
        leadType: 'universal',
        title: "Children's Day Special Courses | Younimatiq",
        description: "Celebrate Children's Day with live Minecraft, Roblox, AI, robotics, and 3D design courses for kids.",
        badge: "🎉 Children's Day Special",
        heroTitle: ['Celebrate ', { accent: "Children's Day" }, ' with Courses Kids Love.'],
        heroText:
            'Gift a skill that sticks — Minecraft, Roblox, AI, robotics, and 3D design in live 1:1 classes with expert coaches. Explore this Children’s Day special collection.',
        heroImage: '/images/minecraft_character.webp',
        heroImageClass: 'object-contain drop-shadow-2xl max-w-lg mx-auto',
        heroBackground: '/images/minecraft_hero_1787217370958.webp',
        coursesHeading: "Children's Day Course Collection",
        coursesCta: { label: 'Explore All Courses →', href: '/courses' },
        videos: [
            '/minecraft_videos/video1.mp4',
            '/minecraft_videos/video2.mp4',
            '/minecraft_videos/video3.mp4',
            '/minecraft_videos/video4.mp4',
            '/minecraft_videos/video5.mp4',
        ],
        showPricing: false,
        why: WHY,
        faqs: FAQS,
        reviews: REVIEWS,
        consultText: "Book a free consultation and get a personalized Children's Day learning plan for your child — no commitment required.",
    },
};

export function getMarketingLanding(topic) {
    return MARKETING_LANDINGS[topic] || MARKETING_LANDINGS.minecraft;
}
