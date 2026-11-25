import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import StudentShell from './StudentShell';

const adminNav = [
    { label: 'Main Menu', type: 'section' },
    { href: '/admin/dashboard', icon: 'dashboard', label: 'Dashboard' },
    { href: '/admin/reports', icon: 'bar_chart', label: 'Reports & Analysis' },
    { href: '/admin/orders', icon: 'payments', label: 'Orders & Payments' },
    { href: '/admin/bundles', icon: 'package_2', label: 'Course Bundles' },
    { label: 'People', type: 'section' },
    { href: '/admin/students', icon: 'group', label: 'Students' },
    { href: '/admin/tutors', icon: 'badge', label: 'Tutors' },
    { href: '/admin/tutor-training', icon: 'school', label: 'Tutor Training' },
    { href: '/admin/tutor-training/reviews', icon: 'fact_check', label: 'HR Training Reviews' },
    { href: '/admin/skills', icon: 'psychology', label: 'Skills' },
    { href: '/admin/consultations', icon: 'contact_page', label: 'Consultations' },
    { href: '/admin/enrollments', icon: 'how_to_reg', label: 'Enrollments' },
    { label: 'Learning', type: 'section' },
    { href: '/admin/courses', icon: 'menu_book', label: 'Courses' },
    { href: '/admin/assignments', icon: 'assignment', label: 'Post class Activity' },
    { href: '/admin/quizzes', icon: 'quiz', label: 'Quizzes' },
    { href: '/admin/activity-links', icon: 'link', label: 'Activity Links' },
    { href: '/admin/tips', icon: 'smart_display', label: 'Tips & Tricks' },
    { href: '/admin/recordings', icon: 'videocam', label: 'Recordings' },
    { href: '/admin/meet-alerts', icon: 'shield_person', label: 'Meet Alerts' },
    { href: '/admin/feedback', icon: 'forum', label: 'Class Feedback' },
    { href: '/admin/reviews', icon: 'star', label: 'Course Reviews' },
    { label: 'Content', type: 'section' },
    { href: '/admin/categories', icon: 'category', label: 'Categories' },
    { href: '/admin/age-groups', icon: 'group_work', label: 'Grade Bands' },
    { href: '/admin/community/projects', icon: 'stars', label: 'Community Projects' },
    { href: '/admin/community/testimonials', icon: 'reviews', label: 'Testimonials' },
    { href: '/admin/blogs', icon: 'article', label: 'Blogs' },
    { href: '/admin/pages', icon: 'web', label: 'Pages' },
    { href: '/admin/faqs', icon: 'help_center', label: 'FAQs' },
    { href: '/admin/coupons', icon: 'local_offer', label: 'Coupons' },
    { href: '/admin/popup-banners', icon: 'campaign', label: 'Popup Banners' },
    { label: 'Operations', type: 'section' },
    { href: '/admin/ops/roster', icon: 'dashboard_customize', label: 'Ops Roster' },
    { href: '/admin/ops/nps', icon: 'sentiment_satisfied', label: 'NPS' },
    { href: '/admin/contact-messages', icon: 'mail', label: 'Contact Forms' },
    { href: '/admin/login-history', icon: 'history', label: 'Login History' },
    { href: '/admin/chatbot', icon: 'smart_toy', label: 'Chatbot Training' },
    { label: 'Settings', type: 'section' },
    { href: '/admin/platform-settings', icon: 'settings', label: 'Platform Settings' },
    { href: '/admin/integrations', icon: 'hub', label: 'Integrations' },
    { href: '/admin/settings/seo', icon: 'search', label: 'SEO Section' },
    { href: '/admin/settings/newsletter', icon: 'mail', label: 'Newsletter' },
    { href: '/admin/email', icon: 'email', label: 'Email Templates' },
    { href: '/admin/email-automations', icon: 'campaign', label: 'Email Automation' },
];

const tutorNav = [
    { label: 'Main Menu', type: 'section' },
    { href: '/tutor/dashboard', icon: 'dashboard', label: 'Dashboard' },
    { href: '/tutor/profile', icon: 'person', label: 'My Profile' },
    { href: '/tutor/classes', icon: 'event', label: 'Upcoming Classes' },
    { href: '/tutor/students', icon: 'group', label: 'My Students' },
    { label: 'Teaching', type: 'section' },
    { href: '/tutor/assigned-classes', icon: 'menu_book', label: 'Assigned Classes' },
    { href: '/tutor/availability', icon: 'schedule', label: 'Availability' },
    { href: '/tutor/assignments', icon: 'assignment', label: 'Post class Activity' },
    { href: '/tutor/quizzes', icon: 'quiz', label: 'Quizzes' },
    { href: '/tutor/games', icon: 'sports_esports', label: 'Activity Links' },
    { href: '/tutor/rewards', icon: 'celebration', label: 'Class Rewards' },
    { href: '/tutor/qa', icon: 'forum', label: 'Q & A' },
    { href: '/tutor/ai-assistant', icon: 'smart_toy', label: 'AI Assistant' },
    { label: 'Communication', type: 'section' },
    { href: '/tutor/messages', icon: 'chat', label: 'Messages' },
    { href: '/tutor/announcements', icon: 'campaign', label: 'Announcements' },
    { label: 'Growth', type: 'section' },
    { href: '/tutor/growth', icon: 'trending_up', label: 'Growth Leads' },
    { href: '/tutor/earnings', icon: 'payments', label: 'Earnings' },
    { label: 'Resources', type: 'section' },
    { href: '/tutor/resources', icon: 'folder', label: 'Resource Library' },
    { href: '/tutor/training', icon: 'workspace_premium', label: 'Training' },
    { href: '/tutor/policy-training', icon: 'verified_user', label: 'Policy Training' },
];

const studentNav = [
    { label: 'Main Menu', type: 'section' },
    { href: '/student/dashboard', icon: 'dashboard', label: 'Dashboard' },
    { href: '/student/courses', icon: 'school', label: 'My Courses' },
    { href: '/student/bookings', icon: 'calendar_month', label: 'Tutor Bookings' },
    { href: '/student/recordings', icon: 'video_library', label: 'Recordings' },
    { label: 'Learning', type: 'section' },
    { href: '/student/activities', icon: 'sports_esports', label: 'Activities' },
    { href: '/student/assignments', icon: 'assignment', label: 'Post class Activity' },
    { href: '/student/quizzes', icon: 'quiz', label: 'Quizzes' },
    { href: '/student/python', icon: 'code', label: 'Python Lab' },
    { href: '/student/rewards', icon: 'emoji_events', label: 'Rewards' },
    { href: '/student/career', icon: 'rocket_launch', label: 'Career Explorer', bands: ['junior', 'senior', 'middle'] },
    { href: '/student/qa', icon: 'forum', label: 'Q & A' },
    { href: '/student/feedback', icon: 'forum', label: 'Class Feedback' },
    { href: '/student/reviews', icon: 'star', label: 'Course Reviews' },
    { label: 'Account', type: 'section' },
    { href: '/student/profile', icon: 'person', label: 'Profile' },
    { href: '/student/wishlist', icon: 'favorite', label: 'Wishlist' },
    { href: '/student/payment-history', icon: 'payments', label: 'Payment History' },
    { href: '/student/referrals', icon: 'share', label: 'Referrals' },
    { href: '/student/settings', icon: 'settings', label: 'Settings' },
];

const parentNav = [
    { label: 'Family', type: 'section' },
    { href: '/parent/dashboard', icon: 'family_restroom', label: 'Dashboard' },
    { href: '/parent/feedback', icon: 'forum', label: 'Class Feedback' },
    { href: '/parent/reviews', icon: 'star', label: 'Course Reviews' },
    { href: '/parent/referrals', icon: 'share', label: 'Referrals' },
];

const ROLE_META = {
    admin: { label: 'Admin', home: '/admin/dashboard', palette: 'purple', accent: '#7130D8' },
    tutor: { label: 'Tutor', home: '/tutor/dashboard', palette: 'blue', accent: '#087FF5' },
    parent: { label: 'Parent', home: '/parent/dashboard', palette: 'green', accent: '#16803C' },
    web: { label: 'Student', home: '/student/dashboard', palette: 'purple', accent: '#7130D8' },
};

export default function DashboardLayout({ children, title }) {
    const page = usePage();
    const { auth } = page.props;
    const guard = auth?.guard || 'web';
    const role = ROLE_META[guard] || ROLE_META.web;
    const isStaff = guard === 'admin' || guard === 'tutor';

    const nav = useMemo(() => {
        if (guard === 'admin') return adminNav;
        if (guard === 'tutor') return tutorNav;
        if (guard === 'parent') return parentNav;
        const band = auth?.user?.grade_band || 'middle';
        return studentNav.filter((item) => !item.bands || item.bands.includes(band));
    }, [guard, auth?.user?.grade_band]);

    const logoutHref =
        guard === 'admin' ? '/logout/admin' : guard === 'tutor' ? '/logout/tutor' : guard === 'parent' ? '/logout/parent' : '/logout/student';
    const profileHref =
        guard === 'tutor' ? '/tutor/profile' : guard === 'web' ? '/student/profile' : guard === 'parent' ? '/parent/dashboard' : role.home;
    const headerAction =
        guard === 'admin'
            ? { href: '/admin/students', label: 'Students' }
            : guard === 'tutor'
              ? { href: '/tutor/classes', label: 'Classes' }
              : null;

    return (
        <StudentShell
            title={title}
            navItems={nav}
            storageKey={`ym-${guard}-rail`}
            home={role.home}
            logoutHref={logoutHref}
            profileHref={profileHref}
            accent={role.accent}
            roleLabel={role.label}
            searchable={isStaff}
            headerAction={headerAction}
            student={guard === 'web'}
        >
            {children}
        </StudentShell>
    );
}
