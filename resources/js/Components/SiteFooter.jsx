import { Link, usePage, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import Icon from './Icon';

export default function SiteFooter() {
    const { appName, appUrl, csrfToken } = usePage().props;
    const year = new Date().getFullYear();
    const logoRef = useRef(null);
    const { data, setData, post, processing } = useForm({ email: '' });
    const asset = (path) => `${(appUrl || '').replace(/\/$/, '')}${path}`;

    useEffect(() => {
        const v = logoRef.current;
        if (!v || v.dataset.ymBooted === '1') return;
        v.dataset.ymBooted = '1';
        const src = v.dataset.lazySrc;
        if (src && !v.getAttribute('src')) {
            v.src = src;
            v.load();
        }
        v.muted = true;
        const start = () => v.play()?.catch?.(() => {});
        start();
        v.addEventListener('ended', () => setTimeout(start, 5000));
    }, []);

    return (
        <footer className="w-full bg-inverse-surface text-white pt-24 pb-12 px-4 md:px-8 mt-auto">
            <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                <div className="space-y-6">
                    <Link href="/" aria-label="Younimatiq home" className="inline-block">
                        <video
                            ref={logoRef}
                            id="footerLogoVideoAnimation"
                            data-lazy-src={asset('/videos/younimatiq-logo-anim.mp4')}
                            className="h-28 w-auto bg-white p-3 rounded-2xl shadow-sm object-contain"
                            width={280}
                            height={112}
                            muted
                            playsInline
                            preload="none"
                            poster={asset('/images/younimatiq-logo-poster.webp')}
                            style={{ pointerEvents: 'none' }}
                        />
                    </Link>
                    <p className="text-surface-variant font-body-md" style={{ color: '#eff0fb' }}>
                        Empowering children to explore, create, and learn through a galaxy of interactive adventures. 100% online, 100% engaged.
                    </p>
                    <div className="flex flex-wrap gap-3">
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#1877F2] transition-colors" href="https://www.facebook.com/younimatiq" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on Facebook">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" /></svg>
                        </a>
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#DD2A7B] transition-colors" href="https://www.instagram.com/younimatiq/?hl=en" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on Instagram">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" /></svg>
                        </a>
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#FF0000] transition-colors" href="https://www.youtube.com/@younimatiq" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on YouTube">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.376.55 9.376.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" /></svg>
                        </a>
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#E60023] transition-colors" href="https://www.pinterest.com/younimatiq/" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on Pinterest">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 0a12 12 0 0 0-4.37 23.17c-.07-.65-.13-1.66.03-2.37.14-.63 1-4.22 1-4.22s-.26-.52-.26-1.28c0-1.2.7-2.1 1.56-2.1.73 0 1.08.55 1.08 1.2 0 .74-.47 1.84-.71 2.87-.2.86.43 1.56 1.27 1.56 1.53 0 2.7-1.61 2.7-3.93 0-2.05-1.47-3.48-3.57-3.48-2.4 0-3.8 1.8-3.8 3.65 0 .74.28 1.53.64 1.96.07.08.08.16.06.24-.07.29-.23.94-.26 1.05-.04.14-.14.17-.29.1-1.07-.5-1.74-2.07-1.74-3.33 0-2.7 1.96-5.18 5.66-5.18 2.97 0 5.28 2.11 5.28 4.93 0 2.95-1.86 5.33-4.44 5.33-.87 0-1.69-.45-1.97-.99l-.54 2.05c-.19.74-.71 1.66-1.06 2.22A12 12 0 1 0 12 0z" /></svg>
                        </a>
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-black transition-colors" href="https://www.tiktok.com/@younimatiq?_r=1&_t=ZP-97ghss4xXWp" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on TikTok">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.01.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93v7.2c0 1.63-.51 3.25-1.48 4.56-1.57 2.14-4.21 3.39-6.9 3.19-2.68-.2-5.06-1.84-6.27-4.25-1.16-2.28-1.07-5.09.23-7.29 1.34-2.25 3.86-3.62 6.48-3.61.12 0 .23 0 .35.01v4.06c-1.4-.24-2.88-.04-4.06.74-.95.63-1.61 1.66-1.73 2.78-.17 1.65.92 3.17 2.47 3.65 1.56.49 3.31.02 4.39-1.16 1.06-1.15 1.5-2.76 1.5-4.32V.02h.93z" /></svg>
                        </a>
                        <a className="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white hover:text-[#0A66C2] transition-colors" href="https://www.linkedin.com/company/younimatiq/" target="_blank" rel="noopener noreferrer" aria-label="Younimatiq on LinkedIn">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" /></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h2 className="font-bold text-xl mb-6 text-white">Quick Links</h2>
                    <ul className="space-y-4 text-surface-variant font-medium">
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/">Home</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/courses">Courses</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/about">About Us</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/faq">FAQ</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/contact">Contact</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/register/tutor">Become a Tutor</Link></li>
                        <li><Link className="hover:text-white transition-colors" style={{ color: '#eff0fb' }} href="/login/tutor">Tutor Login</Link></li>
                    </ul>
                </div>

                <div>
                    <h2 className="font-bold text-xl mb-6 text-white">Contact Us</h2>
                    <ul className="space-y-4 text-surface-variant font-medium">
                        <li className="flex items-center gap-3" style={{ color: '#eff0fb' }}>
                            <svg className="text-primary" fill="currentColor" height="18" viewBox="0 0 24 24" width="18" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
                            <a className="hover:text-white transition-colors" href="https://wa.me/12027944843" target="_blank" rel="noreferrer">USA +1 (202) 794 4843</a>
                        </li>
                        <li className="flex items-center gap-3" style={{ color: '#eff0fb' }}>
                            <svg className="text-primary" fill="currentColor" height="18" viewBox="0 0 24 24" width="18" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
                            <a className="hover:text-white transition-colors" href="https://wa.me/61470214835" target="_blank" rel="noreferrer">Australia +61 470 214 835</a>
                        </li>
                        <li className="flex items-center gap-3" style={{ color: '#eff0fb' }}>
                            <Icon name="call" className="text-primary text-[18px]" />
                            <a className="hover:text-white transition-colors" href="tel:+918981918383">India +91 89819 18383</a>
                        </li>
                        <li className="flex items-center gap-3" style={{ color: '#eff0fb' }}>
                            <Icon name="mail" className="text-primary text-[18px]" />
                            <a className="hover:text-white transition-colors" href="mailto:support@younimatiq.com">support@younimatiq.com</a>
                        </li>
                        <li className="flex items-start gap-3" style={{ color: '#eff0fb' }}>
                            <Icon name="near_me" className="text-primary text-[18px] mt-1" />
                            <span>Texas, USA</span>
                        </li>
                    </ul>
                    <a className="mt-6 w-full btn-enroll btn-block" href="https://wa.me/12027944843" target="_blank" rel="noreferrer">
                        <Icon name="chat" /> Chat on Whatsapp
                    </a>
                </div>

                <div className="space-y-6">
                    <h2 className="font-bold text-xl text-white">Join the Mission</h2>
                    <p className="text-surface-variant font-medium" style={{ color: '#eff0fb' }}>Subscribe for tips and early access to new courses.</p>
                    <form
                        className="relative flex items-center"
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/newsletter/subscribe');
                        }}
                    >
                        <input type="hidden" name="_token" value={csrfToken || ''} />
                        <input
                            className="w-full bg-white/10 border border-white/20 rounded-[var(--radius)] py-3 pl-4 pr-14 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-primary font-medium"
                            placeholder="Email address"
                            required
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <button className="absolute right-2 top-1/2 -translate-y-1/2 btn-primary btn-icon" type="submit" disabled={processing}>
                            <Icon name="send" className="text-sm" />
                        </button>
                    </form>
                    <a className="w-full btn-primary btn-block" href="mailto:support@younimatiq.com">
                        <Icon name="mail" /> Email Us
                    </a>
                </div>
            </div>
            <div className="max-w-7xl mx-auto pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-sm font-medium" style={{ color: 'rgba(255,255,255,0.6)' }}>
                <p>© {year} {appName || 'Younimatiq'} - Reach for the Stars!</p>
                <div className="flex gap-5">
                    <Link href="/page/privacy-policy" className="hover:text-white">Privacy</Link>
                    <Link href="/page/terms-of-service" className="hover:text-white">Terms</Link>
                </div>
            </div>
        </footer>
    );
}
