import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';
import LoginCharacterStage from '../../Components/LoginCharacterStage';

export default function RegisterStudent({ referralCode = '' }) {
    const { errors } = usePage().props;
    const [show, setShow] = useState(false);
    const [passwordFocused, setPasswordFocused] = useState(false);
    const { data, setData, post, processing } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        referral_code: referralCode || '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
    });
    const errList = Object.values(errors || {}).flat();

    return (
        <PublicLayout title="Student Registration">
            <Head title="Student Registration" />
            <div className="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <div className="absolute top-[-10%] left-[-5%] w-96 h-96 bg-primary/10 rounded-full blur-3xl" />
                    <div className="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-tertiary/10 rounded-full blur-3xl" />
                </div>

                <div className="max-w-4xl w-full flex flex-col md:flex-row bg-white rounded-[2rem] border border-surface-container-high shadow-xl relative z-10 clay-card overflow-hidden">
                    <LoginCharacterStage passwordFocused={passwordFocused} passwordVisible={show} />

                    <div className="w-full md:w-1/2 p-5 md:p-8 lg:p-12 flex flex-col justify-center relative">
                        <div className="text-center mb-8">
                            <div className="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4 transform -rotate-6 hover:rotate-0 transition-transform">
                                <Icon name="school" className="text-white text-3xl" />
                            </div>
                            <h2 className="font-headline-lg text-headline-lg font-bold text-on-surface">Become an Explorer</h2>
                            <p className="text-on-surface-variant font-body-md mt-2">
                                Join Younimatiq and start your learning adventure!
                            </p>
                        </div>

                        <form
                            className="space-y-6"
                            autoComplete="on"
                            onSubmit={(e) => {
                                e.preventDefault();
                                post('/register/student');
                            }}
                        >
                            <input type="hidden" name="referral_code" value={data.referral_code} readOnly />
                            <input type="hidden" name="timezone" value={data.timezone} readOnly />

                            {errList.length > 0 ? (
                                <div className="bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold">
                                    <ul>
                                        {errList.map((e, i) => (
                                            <li key={i}>{e}</li>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}

                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="name" className="block text-sm font-bold text-on-surface mb-2">
                                        Explorer Name
                                    </label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                            <Icon name="person" className="text-xl" />
                                        </span>
                                        <input
                                            id="name"
                                            type="text"
                                            required
                                            autoComplete="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="appearance-none rounded-xl relative block w-full pl-12 pr-4 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm font-medium"
                                            placeholder="Alex"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="email" className="block text-sm font-bold text-on-surface mb-2">
                                        Email Address
                                    </label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                            <Icon name="mail" className="text-xl" />
                                        </span>
                                        <input
                                            id="email"
                                            type="email"
                                            required
                                            autoComplete="email"
                                            inputMode="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            onFocus={() => setPasswordFocused(false)}
                                            className="appearance-none rounded-xl relative block w-full pl-12 pr-4 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm font-medium"
                                            placeholder="you@example.com"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="password" className="block text-sm font-bold text-on-surface mb-2">
                                        Secret Password
                                    </label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                            <Icon name="lock" className="text-xl" />
                                        </span>
                                        <input
                                            id="password"
                                            type={show ? 'text' : 'password'}
                                            required
                                            minLength={8}
                                            autoComplete="new-password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            onFocus={() => setPasswordFocused(true)}
                                            onBlur={() => setPasswordFocused(false)}
                                            className="appearance-none rounded-xl relative block w-full pl-12 pr-12 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm font-medium"
                                            placeholder="••••••••"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShow((v) => !v)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-4 text-on-surface-variant hover:text-primary transition-colors focus:outline-none"
                                            aria-label="Toggle password visibility"
                                        >
                                            <Icon name={show ? 'visibility_off' : 'visibility'} className="text-xl" />
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-bold text-on-surface mb-2">
                                        Confirm Password
                                    </label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                            <Icon name="check_circle" className="text-xl" />
                                        </span>
                                        <input
                                            id="password_confirmation"
                                            type={show ? 'text' : 'password'}
                                            required
                                            autoComplete="new-password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            onFocus={() => setPasswordFocused(true)}
                                            onBlur={() => setPasswordFocused(false)}
                                            className="appearance-none rounded-xl relative block w-full pl-12 pr-4 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm font-medium"
                                            placeholder="••••••••"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" disabled={processing} className="btn-primary btn-block disabled:opacity-60">
                                    {processing ? 'Creating…' : 'Start your adventure'}
                                </button>
                            </div>

                            <div className="text-center mt-4">
                                <p className="text-sm text-on-surface-variant font-medium">
                                    Already an Explorer?{' '}
                                    <Link href="/login/student" className="link-primary">
                                        Sign In Here
                                    </Link>
                                </p>
                                <p className="text-sm text-on-surface-variant font-medium mt-4 pt-4 border-t border-surface-container-high">
                                    Are you a Teacher?{' '}
                                    <Link href="/register/tutor" className="font-bold text-secondary hover:underline transition-colors">
                                        Join as Tutor
                                    </Link>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
