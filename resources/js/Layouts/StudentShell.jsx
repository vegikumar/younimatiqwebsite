import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Flash from '../Components/Flash';
import Icon from '../Components/Icon';
import NotificationBell from '../Components/NotificationBell';

const RAIL_KEY = 'ym-student-rail';

const studentNav = [
    { label: 'Main', type: 'section' },
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
    { href: '/student/reviews', icon: 'rate_review', label: 'Reviews' },
    { label: 'Account', type: 'section' },
    { href: '/student/profile', icon: 'person', label: 'Profile' },
    { href: '/student/wishlist', icon: 'favorite', label: 'Wishlist' },
    { href: '/student/payment-history', icon: 'payments', label: 'Payment History' },
    { href: '/student/referrals', icon: 'share', label: 'Referrals' },
    { href: '/student/settings', icon: 'settings', label: 'Settings' },
];

function groupNav(items) {
    const groups = [];
    let current = null;
    items.forEach((item) => {
        if (item.type === 'section') {
            current = { label: item.label, items: [] };
            groups.push(current);
        } else if (current) {
            current.items.push(item);
        }
    });
    return groups;
}

export default function StudentShell({
    children,
    title,
    navItems = null,
    storageKey = RAIL_KEY,
    home = '/student/dashboard',
    logoutHref = '/logout/student',
    profileHref = '/student/profile',
    accent = '#7130D8',
    roleLabel = 'Student',
    searchable = false,
    headerAction = null,
    student = true,
}) {
    const page = usePage();
    const { auth, appName } = page.props;
    const pageUrl = typeof page.url === 'string' ? page.url : '';
    const path = pageUrl.split('?')[0];
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [expanded, setExpanded] = useState(false);
    const [query, setQuery] = useState('');

    useEffect(() => {
        try {
            setExpanded(window.localStorage.getItem(storageKey) === 'open');
        } catch {
            /* ignore */
        }
    }, [storageKey]);

    const band = auth?.user?.grade_band || 'middle';
    const source = navItems || studentNav;
    const nav = useMemo(
        () => source.filter((item) => !item.bands || item.bands.includes(band)),
        [source, band],
    );
    const groups = useMemo(() => {
        const q = query.trim().toLowerCase();
        return groupNav(nav)
            .map((group) => ({
                ...group,
                items: q ? group.items.filter((item) => item.label.toLowerCase().includes(q)) : group.items,
            }))
            .filter((group) => group.items.length > 0);
    }, [nav, query]);

    useEffect(() => {
        const html = document.documentElement;
        const body = document.body;
        html.classList.add('portal-scroll-lock');
        body.classList.add('portal-scroll-lock');
        return () => {
            html.classList.remove('portal-scroll-lock');
            body.classList.remove('portal-scroll-lock');
        };
    }, []);

    const toggleRail = () => {
        setExpanded((current) => {
            const next = !current;
            try {
                window.localStorage.setItem(storageKey, next ? 'open' : 'closed');
            } catch {
                /* ignore */
            }
            return next;
        });
    };

    const isActive = (href) => path === href || path.startsWith(`${href}/`);
    const initial = (auth?.user?.name || 'U').charAt(0).toUpperCase();

    return (
        <div
            className={`chakra-portal panel-shell student-shell ${expanded ? 'is-expanded' : 'is-collapsed'}`}
            data-staff={student ? 'false' : 'true'}
            data-role={auth?.guard || 'web'}
            data-student={student ? 'true' : 'false'}
            style={{ '--portal-accent': accent }}
        >
            <Flash />
            <div className="student-shell-body">
                {drawerOpen ? (
                    <button
                        type="button"
                        className="student-drawer-backdrop"
                        aria-label="Close menu"
                        onClick={() => setDrawerOpen(false)}
                    />
                ) : null}

                <aside
                    className={`student-rail ${drawerOpen ? 'is-open' : ''}`}
                    aria-label={roleLabel}
                >
                    <div className="student-rail-brand">
                        <Link href={home} className="student-rail-logo" onClick={() => setDrawerOpen(false)} aria-label={appName || 'Younimatiq'}>
                            <img className="student-rail-mark" src="/images/logo-mark.webp?v=2" alt="" />
                            <img className="student-rail-word" src="/logo.webp" alt="" />
                        </Link>
                        <button type="button" className="student-rail-toggle" onClick={toggleRail} aria-pressed={expanded} aria-label={expanded ? 'Collapse menu' : 'Expand menu'}>
                            <Icon name={expanded ? 'chevron_left' : 'chevron_right'} />
                            <span className="student-rail-tip" role="tooltip">{expanded ? 'Collapse menu' : 'Expand menu'}</span>
                        </button>
                        <button type="button" className="student-rail-close" aria-label="Close menu" onClick={() => setDrawerOpen(false)}>
                            <Icon name="close" />
                        </button>
                    </div>

                    <span className="student-rail-role">{roleLabel}</span>
                    {searchable ? (
                        <div className="student-rail-search">
                            <input
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Search menu"
                                aria-label="Search menu"
                            />
                        </div>
                    ) : null}
                    <nav className="student-rail-nav">
                        {groups.map((group) => (
                            <div key={group.label} className="student-rail-section">
                                <p className="student-rail-group">{group.label}</p>
                                {group.items.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`student-rail-link ${isActive(item.href) ? 'is-active' : ''}`}
                                        aria-current={isActive(item.href) ? 'page' : undefined}
                                        aria-label={item.label}
                                        onClick={() => setDrawerOpen(false)}
                                    >
                                        <Icon name={item.icon} />
                                        <span className="student-rail-label">{item.label}</span>
                                        <span className="student-rail-tip" role="tooltip">{item.label}</span>
                                    </Link>
                                ))}
                            </div>
                        ))}
                        {groups.length === 0 ? <p className="student-rail-empty">No matches</p> : null}
                        <a href="/" className="student-rail-link" aria-label="Website" onClick={() => setDrawerOpen(false)}>
                            <Icon name="language" />
                            <span className="student-rail-label">Website</span>
                            <span className="student-rail-tip" role="tooltip">Website</span>
                        </a>
                    </nav>

                    <div className="student-rail-footer">
                        <Link href={logoutHref} method="post" as="button" className="student-rail-link student-rail-signout" aria-label="Sign out">
                            <Icon name="logout" />
                            <span className="student-rail-label">Sign out</span>
                            <span className="student-rail-tip" role="tooltip">Sign out</span>
                        </Link>
                    </div>
                </aside>

                <div className="student-main">
                    {auth?.impersonating ? (
                        <div className="student-impersonate-bar">
                            <span>
                                Viewing as {auth?.user?.name}{' '}
                                {auth?.user?.role === 'tutor' || auth?.guard === 'tutor'
                                    ? 'Trainee'
                                    : auth?.user?.role === 'parent' || auth?.guard === 'parent'
                                      ? 'Parent'
                                      : 'Student'}
                                {auth?.user?.id ? ` no ${auth.user.id}` : ''}
                            </span>
                            <a href="/impersonate/leave" className="underline">
                                Return to Admin
                            </a>
                        </div>
                    ) : null}
                    <header className={`student-topbar ${student ? 'is-student' : 'is-staff'}`}>
                        <div className="student-topbar-left">
                            <button type="button" className="student-topbar-menu" aria-label="Open menu" onClick={() => setDrawerOpen(true)}>
                                <Icon name="menu" />
                            </button>
                            <Link href={home} className="student-topbar-logo" aria-label={appName || 'Younimatiq'}>
                                <img src="/logo.webp" alt={appName || 'Younimatiq'} />
                            </Link>
                            <p className="student-topbar-title">{title || roleLabel}</p>
                        </div>
                        <div className="student-topbar-right">
                            {headerAction ? (
                                <Link href={headerAction.href} className="student-header-action">
                                    {headerAction.label}
                                </Link>
                            ) : null}
                            <NotificationBell />
                            <Link href={profileHref} className="portal-userchip">
                                <span style={{ background: accent }}>{initial}</span>
                                <strong>{auth?.user?.name}</strong>
                            </Link>
                        </div>
                    </header>
                    <div className="portal-canvas student-workspace">{children}</div>
                </div>
            </div>
        </div>
    );
}
