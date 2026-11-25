import { Link, usePage, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from './Icon';

function NavDropdown({ label, href, open, setOpen, width = 320, children, accent = '#7130D8' }) {
    const closeTimer = useRef(null);

    const openMenu = () => {
        if (closeTimer.current) {
            window.clearTimeout(closeTimer.current);
            closeTimer.current = null;
        }
        setOpen(true);
    };

    const scheduleClose = () => {
        if (closeTimer.current) window.clearTimeout(closeTimer.current);
        closeTimer.current = window.setTimeout(() => setOpen(false), 180);
    };

    useEffect(() => () => {
        if (closeTimer.current) window.clearTimeout(closeTimer.current);
    }, []);

    return (
        <div
            className="nav-dd relative"
            onMouseEnter={openMenu}
            onMouseLeave={scheduleClose}
        >
            <Link
                href={href}
                className="nav-dd-trigger"
                aria-expanded={open}
                aria-haspopup="menu"
                style={{ color: open ? accent : undefined, textDecoration: 'none' }}
                onClick={() => setOpen(false)}
            >
                {label}
                <Icon
                    name="expand_more"
                    className={`text-[14px] leading-none transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                />
            </Link>
            <div
                className={`nav-dd-panel ${open ? 'is-open' : ''}`}
                style={{ ['--nav-dd-width']: `${width}px` }}
                role="menu"
                onMouseEnter={openMenu}
                onMouseLeave={scheduleClose}
            >
                <div className="nav-dd-panel-inner" data-scroll-native style={{ width }}>
                    {children}
                </div>
            </div>
        </div>
    );
}

export default function SiteHeader() {
    const { auth, navigation, appUrl } = usePage().props;
    const navCategories = navigation?.categories || [];
    const gradeBands = navigation?.gradeBands || [];
    const [coursesOpen, setCoursesOpen] = useState(false);
    const [gradesOpen, setGradesOpen] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [mobileCourses, setMobileCourses] = useState(false);
    const [mobileGrades, setMobileGrades] = useState(false);
    const logoRef = useRef(null);

    // Backend now sends an ordered array (K→12). Keep a fallback for older cached object shapes.
    const gradeEntries = Array.isArray(gradeBands)
        ? gradeBands.map((band) => [String(band.key ?? band.value), band])
        : Object.entries(gradeBands || {}).sort(([a], [b]) => {
              const av = a === 'k' || a === 'K' ? -1 : Number(a);
              const bv = b === 'k' || b === 'K' ? -1 : Number(b);
              return av - bv;
          });

    useEffect(() => {
        const logoVid = logoRef.current;
        if (!logoVid || logoVid.dataset.ymBooted === '1') return;
        logoVid.dataset.ymBooted = '1';
        logoVid.muted = true;
        const start = () => logoVid.play()?.catch?.(() => {});
        setTimeout(start, 300);
        logoVid.addEventListener('ended', () => setTimeout(start, 5000));
    }, []);

    const openLead = (e) => {
        e?.preventDefault?.();
        if (typeof window.openLeadModal === 'function') {
            window.openLeadModal(e || { preventDefault() {} }, 'free_class');
        } else {
            document.getElementById('leadModal')?.classList.add('open');
            document.body.classList.add('modal-open');
            document.documentElement.classList.add('modal-open');
        }
    };

    const closeMobile = () => setMobileOpen(false);
    const asset = (path) => `${(appUrl || '').replace(/\/$/, '')}${path}`;

    return (
        <>
            <header className="custom-header">
                <div className="header-row">
                    <Link href="/" aria-label="Younimatiq home" style={{ display: 'flex', alignItems: 'center' }}>
                        <video
                            ref={logoRef}
                            id="logoVideoAnimation"
                            src={asset('/videos/younimatiq-logo-anim.mp4')}
                            className="logo-img object-contain"
                            width={200}
                            height={72}
                            muted
                            playsInline
                            preload="metadata"
                            poster={asset('/images/younimatiq-logo-poster.webp')}
                            style={{ pointerEvents: 'none' }}
                        />
                    </Link>

                    <nav className="desktop-nav">
                        <Link href="/">Home</Link>
                        <Link href="/community">Community</Link>
                        <Link href="/pricing">Pricing</Link>
                        <Link href="/blog">Blog</Link>

                        <NavDropdown
                            label="Courses"
                            href="/courses"
                            open={coursesOpen}
                            setOpen={setCoursesOpen}
                            width={520}
                            accent="#FF9800"
                        >
                            <div className="nav-dd-head">
                                <div>
                                    <p className="nav-dd-kicker">Catalog</p>
                                    <p className="nav-dd-title">Browse by subject</p>
                                </div>
                                <Link href="/courses" className="nav-dd-all" onClick={() => setCoursesOpen(false)}>
                                    View all <Icon name="arrow_forward" className="text-sm" />
                                </Link>
                            </div>
                            <div className="nav-dd-grid">
                                {navCategories.map((category) => (
                                    <Link
                                        key={category.id || category.slug}
                                        href={`/courses/category/${category.slug}`}
                                        className="nav-dd-item"
                                        role="menuitem"
                                        onClick={() => setCoursesOpen(false)}
                                    >
                                        <span className="nav-dd-item-label">{category.name}</span>
                                        <span className="nav-dd-count">{category.courses_count}</span>
                                    </Link>
                                ))}
                            </div>
                        </NavDropdown>

                        <NavDropdown
                            label="Grades"
                            href="/courses"
                            open={gradesOpen}
                            setOpen={setGradesOpen}
                            width={280}
                            accent="#FFC400"
                        >
                            <div className="nav-dd-head nav-dd-head--compact">
                                <div>
                                    <p className="nav-dd-kicker">School level</p>
                                    <p className="nav-dd-title">Browse by grade</p>
                                </div>
                                <Link href="/courses" className="nav-dd-all" onClick={() => setGradesOpen(false)}>
                                    View all <Icon name="arrow_forward" className="text-sm" />
                                </Link>
                            </div>
                            <div className="nav-dd-list nav-dd-list--grades">
                                {gradeEntries.map(([value, band]) => (
                                    <Link
                                        key={value}
                                        href={`/courses/grades/${band.key || value}`}
                                        className="nav-dd-grade"
                                        role="menuitem"
                                        onClick={() => setGradesOpen(false)}
                                    >
                                        <span className="nav-dd-item-label">{band.label}</span>
                                        <span className="nav-dd-count">{band.courses_count ?? 0}</span>
                                    </Link>
                                ))}
                            </div>
                        </NavDropdown>

                        <Link href="/about">About Us</Link>
                        <Link href="/contact">Contact</Link>
                    </nav>

                    <div className="header-actions">
                        {auth?.user && auth?.guard === 'web' ? (
                            <>
                                {/* Full page load: public JS bundle cannot resolve Student/* portal pages. */}
                                <a className="btn btn-primary btn-sm" href="/student/dashboard">Dashboard</a>
                                <button
                                    type="button"
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => router.post('/logout/student')}
                                >
                                    Logout
                                </button>
                            </>
                        ) : (
                            <>
                                <Link className="btn btn-outline-primary btn-sm header-login-btn" href="/login/student">Log In</Link>
                                <a className="btn btn-primary btn-sm book-cta" href="#leadModal" onClick={openLead} style={{ cursor: 'pointer' }}>
                                    <span className="book-cta-short">Book free</span>
                                    <span className="book-cta-full">Book a free trial class</span>
                                </a>
                            </>
                        )}
                        <button aria-label="Open menu" className="icon-btn" id="menuBtn" type="button" onClick={() => setMobileOpen(true)}>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                    </div>
                </div>
            </header>

            <div className={`menu-overlay ${mobileOpen ? 'open' : ''}`} id="menuOverlay" onClick={(e) => { if (e.target.id === 'menuOverlay') closeMobile(); }}>
                <nav className="menu-panel" style={{ overflowY: 'auto' }}>
                    <button className="mclose" type="button" aria-label="Close menu" onClick={closeMobile}>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                    <Link href="/" onClick={closeMobile}>Home <Icon name="chevron_right" className="text-sm" /></Link>
                    <Link href="/community" onClick={closeMobile}>Community <Icon name="chevron_right" className="text-sm" /></Link>
                    <Link href="/pricing" onClick={closeMobile}>Pricing <Icon name="chevron_right" className="text-sm" /></Link>
                    <Link href="/blog" onClick={closeMobile}>Blog <Icon name="chevron_right" className="text-sm" /></Link>

                    <div className="w-full">
                        <div className="mobile-acc-btn" style={{ gap: 8 }}>
                            <Link href="/courses" onClick={closeMobile} style={{ color: 'inherit', textDecoration: 'none', flex: 1 }}>
                                Courses
                            </Link>
                            <button type="button" aria-label="Toggle courses menu" onClick={() => setMobileCourses((v) => !v)} style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', color: 'inherit' }}>
                                <Icon name="expand_more" className={`text-sm transition-transform ${mobileCourses ? 'rotate-180' : ''}`} />
                            </button>
                        </div>
                        {mobileCourses && (
                            <div className="mobile-acc-body">
                                <Link href="/courses" onClick={closeMobile} className="mobile-acc-all">View All Courses</Link>
                                <div className="mobile-acc-grid">
                                    {navCategories.map((category) => (
                                        <Link key={category.slug} href={`/courses/category/${category.slug}`} onClick={closeMobile} className="mobile-acc-link">
                                            {category.name}
                                            <span className="nav-dd-count" style={{ marginLeft: 8 }}>{category.courses_count ?? 0}</span>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="w-full">
                        <div className="mobile-acc-btn" style={{ gap: 8 }}>
                            <Link href="/courses" onClick={closeMobile} style={{ color: 'inherit', textDecoration: 'none', flex: 1 }}>
                                Grades
                            </Link>
                            <button type="button" aria-label="Toggle grades menu" onClick={() => setMobileGrades((v) => !v)} style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', color: 'inherit' }}>
                                <Icon name="expand_more" className={`text-sm transition-transform ${mobileGrades ? 'rotate-180' : ''}`} />
                            </button>
                        </div>
                        {mobileGrades && (
                            <div className="mobile-acc-body">
                                <Link href="/courses" onClick={closeMobile} className="mobile-acc-all">View All Courses</Link>
                                <div className="mobile-acc-grid">
                                    {gradeEntries.map(([value, band]) => (
                                        <Link key={value} href={`/courses/grades/${band.key || value}`} onClick={closeMobile} className="mobile-acc-link">
                                            {band.label}
                                            <span className="nav-dd-count" style={{ marginLeft: 8 }}>{band.courses_count ?? 0}</span>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <Link href="/about" onClick={closeMobile}>About Us <Icon name="chevron_right" className="text-sm" /></Link>
                    <Link href="/contact" onClick={closeMobile}>Contact <Icon name="chevron_right" className="text-sm" /></Link>
                    {auth?.user && auth?.guard === 'web' ? (
                        <a href="/student/dashboard" onClick={closeMobile} style={{ marginTop: 12, color: 'var(--color-primary)' }}>Dashboard <Icon name="chevron_right" className="text-sm" /></a>
                    ) : (
                        <Link href="/login/student" onClick={closeMobile} style={{ marginTop: 12, color: 'var(--color-primary)' }}>Login <Icon name="chevron_right" className="text-sm" /></Link>
                    )}
                    <a className="btn btn-primary btn-block" href="#leadModal" onClick={(e) => { closeMobile(); openLead(e); }} style={{ marginTop: 12, textDecoration: 'none', justifyContent: 'center', padding: '12px 0' }}>
                        Book a free trial class
                    </a>
                </nav>
            </div>
        </>
    );
}
