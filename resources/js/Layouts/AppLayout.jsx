import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Flash from '../Components/Flash';
import Icon from '../Components/Icon';
import LeadModal from '../Components/LeadModal';

export default function AppLayout({ children, title }) {
    const { appName, auth } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="min-h-screen bg-surface text-on-surface flex flex-col overflow-x-clip">
            <Flash />
            <header className="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-outline-variant/40">
                <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                    <Link href="/" className="font-extrabold text-lg text-primary" style={{ fontFamily: "'Baloo 2', Nunito, sans-serif" }}>
                        {appName || 'Younimatiq'}
                    </Link>
                    <nav className="hidden md:flex items-center gap-4 text-sm font-bold text-on-surface-variant">
                        <Link href="/courses" className="hover:text-primary">Courses</Link>
                        <Link href="/about" className="hover:text-primary">About</Link>
                        <Link href="/pricing" className="hover:text-primary">Pricing</Link>
                        <Link href="/blog" className="hover:text-primary">Blog</Link>
                        <Link href="/contact" className="hover:text-primary">Contact</Link>
                    </nav>
                    <div className="flex items-center gap-2">
                        {auth?.user ? (
                            <Link
                                href={auth.guard === 'admin' ? '/admin/dashboard' : auth.guard === 'tutor' ? '/tutor/dashboard' : '/student/dashboard'}
                                className="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-primary text-white text-xs font-bold"
                            >
                                <Icon name="dashboard" className="text-base" /> Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href="/login/student" className="text-xs font-bold text-primary px-2 py-2 hidden sm:inline">Login</Link>
                                <Link href="/register/tutor" className="inline-flex px-3 py-2 rounded-xl bg-primary text-white text-xs font-bold">
                                    Teach with us
                                </Link>
                            </>
                        )}
                        <button type="button" className="md:hidden p-2 rounded-lg hover:bg-surface-container-low" onClick={() => setMobileOpen((v) => !v)} aria-label="Menu">
                            <Icon name={mobileOpen ? 'close' : 'menu'} />
                        </button>
                    </div>
                </div>
                {mobileOpen && (
                    <nav className="md:hidden border-t border-outline-variant/40 px-4 py-3 flex flex-col gap-2 text-sm font-bold">
                        <Link href="/courses" onClick={() => setMobileOpen(false)}>Courses</Link>
                        <Link href="/about" onClick={() => setMobileOpen(false)}>About</Link>
                        <Link href="/pricing" onClick={() => setMobileOpen(false)}>Pricing</Link>
                        <Link href="/blog" onClick={() => setMobileOpen(false)}>Blog</Link>
                        <Link href="/contact" onClick={() => setMobileOpen(false)}>Contact</Link>
                        {!auth?.user && <Link href="/login/student" onClick={() => setMobileOpen(false)}>Student login</Link>}
                    </nav>
                )}
            </header>
            <main className="flex-1 w-full min-w-0">{children}</main>
            <LeadModal />
            <footer className="border-t border-outline-variant/40 bg-white mt-auto">
                <div className="max-w-7xl mx-auto px-4 py-8 grid sm:grid-cols-2 md:grid-cols-4 gap-6 text-sm">
                    <div>
                        <div className="font-extrabold text-primary mb-2">{appName || 'Younimatiq'}</div>
                        <p className="text-on-surface-variant text-xs">AI & STEM learning for kids — 1:1 live classes.</p>
                    </div>
                    <div className="space-y-1">
                        <div className="font-bold mb-1">Explore</div>
                        <Link href="/courses" className="block text-on-surface-variant hover:text-primary">Courses</Link>
                        <Link href="/faq" className="block text-on-surface-variant hover:text-primary">FAQ</Link>
                        <Link href="/community" className="block text-on-surface-variant hover:text-primary">Community</Link>
                    </div>
                    <div className="space-y-1">
                        <div className="font-bold mb-1">Account</div>
                        <Link href="/login/student" className="block text-on-surface-variant hover:text-primary">Student login</Link>
                        <Link href="/login/tutor" className="block text-on-surface-variant hover:text-primary">Tutor login</Link>
                        <Link href="/register/tutor" className="block text-on-surface-variant hover:text-primary">Become a tutor</Link>
                    </div>
                    <div className="space-y-1">
                        <div className="font-bold mb-1">Support</div>
                        <a href="mailto:support@younimatiq.com" className="block text-on-surface-variant hover:text-primary">support@younimatiq.com</a>
                        <Link href="/contact" className="block text-on-surface-variant hover:text-primary">Contact</Link>
                    </div>
                </div>
                <div className="text-center text-xs text-on-surface-variant py-4 border-t border-outline-variant/30">
                    © {new Date().getFullYear()} {appName || 'Younimatiq'}. All rights reserved.
                </div>
            </footer>
        </div>
    );
}
