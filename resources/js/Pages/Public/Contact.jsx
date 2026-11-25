import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';
import Icon from '../../Components/Icon';

const SUBJECT_OPTIONS = ['General Inquiry'];

export default function Contact({ categories = [], detectedCountry = 'us', old: oldInput = {} }) {
    const { flash, errors } = usePage().props;
    const phoneRef = useRef(null);
    const itiRef = useRef(null);
    const errList = Object.values(errors || {}).flat();

    const form = useForm({
        name: oldInput.name || '',
        email: oldInput.email || '',
        phone: oldInput.phone || '',
        subject: oldInput.subject || '',
        message: oldInput.message || '',
    });
    const { data, setData, processing } = form;

    const subjectOptions = [...SUBJECT_OPTIONS, ...categories.map((c) => c.name)];

    useEffect(() => {
        const phoneInput = phoneRef.current;
        if (!phoneInput) return;

        let cancelled = false;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css';
        document.head.appendChild(link);

        const style = document.createElement('style');
        style.textContent = `
            .iti { width: 100%; }
            .iti__flag-container { z-index: 5; }
            .iti__country-list { color: #1f2937; text-align: left; z-index: 40; }
            #phone.iti-invalid {
                border-color: #dc2626 !important;
                box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.15);
            }
        `;
        document.head.appendChild(style);

        const loadScript = () =>
            new Promise((resolve, reject) => {
                if (window.intlTelInput) {
                    resolve(window.intlTelInput);
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js';
                script.onload = () => resolve(window.intlTelInput);
                script.onerror = reject;
                document.body.appendChild(script);
            });

        loadScript()
            .then((intlTelInput) => {
                if (cancelled || !phoneInput) return;
                const sessionCountry = (detectedCountry || 'us').toLowerCase();
                const iti = intlTelInput(phoneInput, {
                    initialCountry: 'auto',
                    preferredCountries: ['us', 'in', 'au', 'gb', 'ae', 'sg'],
                    nationalMode: false,
                    formatOnDisplay: true,
                    autoPlaceholder: 'aggressive',
                    geoIpLookup(callback) {
                        if (sessionCountry && sessionCountry.length === 2) {
                            callback(sessionCountry);
                            return;
                        }
                        fetch('https://ipapi.co/json')
                            .then((res) => res.json())
                            .then((d) => callback((d.country_code || 'us').toLowerCase()))
                            .catch(() => callback('us'));
                    },
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js',
                });
                itiRef.current = iti;
                if (data.phone) {
                    try {
                        iti.setNumber(data.phone);
                    } catch {
                        phoneInput.value = data.phone;
                    }
                }
            })
            .catch(() => {});

        return () => {
            cancelled = true;
            itiRef.current?.destroy?.();
            itiRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const showPhoneError = (show) => {
        const phoneInput = phoneRef.current;
        const phoneError = document.getElementById('phone-error');
        if (!phoneInput) return;
        if (show) {
            phoneInput.classList.add('iti-invalid');
            phoneError?.classList.remove('hidden');
        } else {
            phoneInput.classList.remove('iti-invalid');
            phoneError?.classList.add('hidden');
        }
    };

    const submit = (e) => {
        e.preventDefault();
        const phoneInput = phoneRef.current;
        const iti = itiRef.current;
        let phone = data.phone;
        if (phoneInput?.value.trim() && iti) {
            if (window.intlTelInputUtils && !iti.isValidNumber()) {
                showPhoneError(true);
                phoneInput.focus();
                return;
            }
            showPhoneError(false);
            phone = iti.getNumber() || phoneInput.value;
        } else {
            showPhoneError(false);
        }
        form.transform((payload) => ({ ...payload, phone })).post('/contact', { preserveScroll: true });
    };

    return (
        <PublicLayout title="Contact Younimatiq">
            <Head title="Contact Younimatiq">
                <meta
                    head-key="description"
                    name="description"
                    content="Contact Younimatiq to learn more about online classes and courses for kids. Get in touch with our team for course details, enrollment, and support."
                />
            </Head>

            <BrandHero className="mt-8 mb-8" eyebrow="Get in Touch">
                <h1 className="brand-title">
                    Contact <span className="text-brand-accent">Us</span>
                </h1>
                <p className="brand-description">Questions about courses, demos, or enrollment? Send us a message and our team will help.</p>
            </BrandHero>

            <div className="pb-20 px-4 md:px-6 max-w-6xl mx-auto">
                {flash?.success ? (
                    <div className="mb-8 rounded-2xl border border-tertiary-fixed-dim bg-tertiary-fixed text-tertiary px-5 py-4 font-medium">{flash.success}</div>
                ) : null}

                {errList.length > 0 ? (
                    <div className="mb-8 rounded-2xl border border-error-container bg-error-container text-error px-5 py-4">
                        <p className="font-bold mb-2">Please fix the following:</p>
                        <ul className="list-disc list-inside text-sm space-y-1">
                            {errList.map((error, i) => (
                                <li key={i}>{error}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                <div className="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-10">
                    <aside className="lg:col-span-2 space-y-6">
                        <div className="bg-white rounded-3xl border border-outline-variant shadow-sm p-6 md:p-8 space-y-5">
                            <h2 className="text-xl font-bold text-on-surface">Talk to our team</h2>
                            <p className="text-on-surface text-sm leading-relaxed">Prefer to reach out directly? Use the options below — we are happy to help families worldwide.</p>
                            <ul className="space-y-4">
                                <li className="flex items-start gap-3">
                                    <svg className="text-primary mt-0.5" fill="currentColor" height="24" viewBox="0 0 24 24" width="24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">USA</p>
                                        <a href="https://wa.me/12027944843" target="_blank" rel="noreferrer" className="font-semibold text-on-surface hover:text-primary">
                                            +1 (202) 794 4843
                                        </a>
                                    </div>
                                </li>
                                <li className="flex items-start gap-3">
                                    <svg className="text-primary mt-0.5" fill="currentColor" height="24" viewBox="0 0 24 24" width="24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Australia</p>
                                        <a href="https://wa.me/61470214835" target="_blank" rel="noreferrer" className="font-semibold text-on-surface hover:text-primary">
                                            +61 470 214 835
                                        </a>
                                    </div>
                                </li>
                                <li className="flex items-start gap-3">
                                    <Icon name="call" className="text-primary mt-0.5" />
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">India</p>
                                        <a href="tel:+918981918383" className="font-semibold text-on-surface hover:text-primary">
                                            +91 89819 18383
                                        </a>
                                    </div>
                                </li>
                                <li className="flex items-start gap-3">
                                    <Icon name="mail" className="text-primary mt-0.5" />
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Email</p>
                                        <a href="mailto:support@younimatiq.com" className="font-semibold text-on-surface hover:text-primary">
                                            support@younimatiq.com
                                        </a>
                                    </div>
                                </li>
                                <li className="flex items-start gap-3">
                                    <Icon name="near_me" className="text-primary mt-0.5" />
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Location</p>
                                        <p className="font-semibold text-on-surface">Texas, USA</p>
                                    </div>
                                </li>
                            </ul>
                            <div className="pt-2 flex flex-col gap-3">
                                <a href="https://wa.me/12027944843" target="_blank" rel="noopener noreferrer" className="btn-primary w-full inline-flex justify-center items-center gap-2">
                                    <Icon name="chat" className="text-base" />
                                    Chat on WhatsApp
                                </a>
                                <a href="/book-free-session" className="w-full inline-flex justify-center items-center gap-2 rounded-xl border border-outline-variant px-4 py-3 font-bold text-on-surface hover:bg-surface-container-low transition-colors">
                                    <Icon name="event" className="text-base" />
                                    Book a Free Class
                                </a>
                            </div>
                        </div>
                    </aside>

                    <div className="lg:col-span-3">
                        <div className="bg-white rounded-3xl border border-outline-variant shadow-sm p-6 md:p-8">
                            <h2 className="text-xl font-bold text-on-surface mb-1">Send a message</h2>
                            <p className="text-on-surface text-sm mb-6">Fill out the form and we will get back to you soon.</p>

                            <form onSubmit={submit} className="space-y-5" autoComplete="on">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-bold text-on-surface mb-2">
                                            Full name
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                            maxLength={255}
                                            autoComplete="name"
                                            className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            placeholder="Your name"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-bold text-on-surface mb-2">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            maxLength={255}
                                            autoComplete="email"
                                            inputMode="email"
                                            className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            placeholder="you@email.com"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label htmlFor="phone" className="block text-sm font-bold text-on-surface mb-2">
                                            Phone / WhatsApp
                                        </label>
                                        <input
                                            ref={phoneRef}
                                            type="tel"
                                            id="phone"
                                            name="phone"
                                            defaultValue={data.phone}
                                            onInput={() => showPhoneError(false)}
                                            autoComplete="tel"
                                            inputMode="tel"
                                            className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            placeholder="Phone number"
                                        />
                                        <p id="phone-error" className="hidden mt-2 text-sm text-error font-medium">
                                            Please enter a valid phone number with country code.
                                        </p>
                                    </div>
                                    <div>
                                        <label htmlFor="subject" className="block text-sm font-bold text-on-surface mb-2">
                                            Subject
                                        </label>
                                        <select
                                            id="subject"
                                            name="subject"
                                            value={data.subject}
                                            onChange={(e) => setData('subject', e.target.value)}
                                            required
                                            className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                        >
                                            <option value="">Select a topic</option>
                                            {subjectOptions.map((opt) => (
                                                <option key={opt} value={opt}>
                                                    {opt}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="message" className="block text-sm font-bold text-on-surface mb-2">
                                        Message
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        required
                                        maxLength={5000}
                                        rows={6}
                                        className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 resize-y"
                                        placeholder="How can we help you and your child?"
                                    />
                                </div>

                                <button type="submit" disabled={processing} className="btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3">
                                    <Icon name="send" className="text-base" />
                                    Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
