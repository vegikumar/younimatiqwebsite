import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';
import LoginCharacterStage from '../../Components/LoginCharacterStage';

const configs = {
    student: {
        title: 'Welcome Back, Explorer!',
        subtitle: 'Sign in to continue your learning journey.',
        icon: 'school',
        action: '/login/student',
        accentClass: 'bg-primary',
        rotateClass: 'rotate-6',
        focusRing: 'focus:ring-primary focus:border-primary',
        rememberClass: 'text-primary focus:ring-primary',
        submitLabel: 'Start your adventure',
        registerHref: '/register/student',
        registerPrompt: 'Not an Explorer yet?',
        registerLabel: 'Join Here',
        switchHref: '/login/tutor',
        switchPrompt: 'Teaching with us?',
        switchLabel: 'Teacher / Tutor sign-in',
        switchClass: 'font-bold text-secondary hover:underline transition-colors',
        blurClass: 'bg-primary/10',
        blurSide: 'left-[-5%]',
    },
    tutor: {
        title: 'Welcome Back, Tutor',
        subtitle: 'Sign in to manage your courses and students.',
        icon: 'co_present',
        action: '/login/tutor',
        accentClass: 'bg-secondary',
        rotateClass: '-rotate-6',
        focusRing: 'focus:ring-secondary focus:border-secondary',
        rememberClass: 'text-secondary focus:ring-secondary',
        submitLabel: 'Sign In',
        registerHref: '/register/tutor',
        registerPrompt: 'New Tutor?',
        registerLabel: 'Apply Here',
        switchHref: '/login/student',
        switchPrompt: 'Are you a Student?',
        switchLabel: 'Sign In Here',
        switchClass: 'font-bold text-primary hover:underline transition-colors',
        blurClass: 'bg-secondary/10',
        blurSide: 'right-[-5%]',
    },
    admin: {
        title: 'Admin Sign In',
        subtitle: 'Secure access to the control panel.',
        icon: 'admin_panel_settings',
        action: '/login/admin',
        accentClass: 'bg-tertiary',
        rotateClass: 'rotate-3',
        focusRing: 'focus:ring-tertiary focus:border-tertiary',
        rememberClass: 'text-tertiary focus:ring-tertiary',
        submitLabel: 'Secure Login',
        registerHref: null,
        registerPrompt: null,
        registerLabel: null,
        switchHref: null,
        switchPrompt: null,
        switchLabel: null,
        switchClass: '',
        blurClass: 'bg-tertiary/10',
        blurSide: 'left-[-5%]',
    },
    parent: {
        title: 'Welcome, Parent',
        subtitle: 'Sign in to follow your child’s learning journey.',
        icon: 'family_restroom',
        action: '/login/parent',
        accentClass: 'bg-secondary',
        rotateClass: '-rotate-3',
        focusRing: 'focus:ring-secondary focus:border-secondary',
        rememberClass: 'text-secondary focus:ring-secondary',
        submitLabel: 'Sign In',
        registerHref: '/register/parent',
        registerPrompt: 'New parent?',
        registerLabel: 'Create account',
        switchHref: '/login/student',
        switchPrompt: 'Student explorer?',
        switchLabel: 'Student sign in',
        switchClass: 'font-bold text-primary hover:underline transition-colors',
        blurClass: 'bg-secondary/10',
        blurSide: 'right-[-5%]',
    },
};

export default function Login({ role = 'student', email: initialEmail = '' }) {
    const cfg = configs[role] || configs.student;
    const { errors } = usePage().props;
    const [show, setShow] = useState(false);
    const [passwordFocused, setPasswordFocused] = useState(false);
    const { data, setData, post, processing } = useForm({
        email: initialEmail || '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(cfg.action);
    };

    const errList = Object.values(errors || {}).flat();
    const pageTitle =
        role === 'student'
            ? 'Student Login'
            : role === 'tutor'
              ? 'Tutor Login'
              : role === 'parent'
                ? 'Parent Login'
                : 'Admin Login';

    return (
        <PublicLayout title={pageTitle}>
            <Head title={pageTitle} />
            <div className="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <div className={`absolute top-[-10%] ${cfg.blurSide} w-96 h-96 ${cfg.blurClass} rounded-full blur-3xl`} />
                </div>

                <div className="max-w-4xl w-full flex flex-col md:flex-row bg-white rounded-[2rem] border border-surface-container-high shadow-xl relative z-10 clay-card overflow-hidden">
                    <LoginCharacterStage passwordFocused={passwordFocused} passwordVisible={show} />

                    <div className="w-full md:w-1/2 p-5 md:p-8 lg:p-12 flex flex-col justify-center relative">
                        <div className="text-center mb-8">
                            <div
                                className={`w-16 h-16 ${cfg.accentClass} mx-auto rounded-2xl flex items-center justify-center mb-4 transform ${cfg.rotateClass} hover:rotate-0 transition-transform`}
                            >
                                <Icon name={cfg.icon} className="text-white text-3xl" />
                            </div>
                            <h2 className="font-headline-lg text-headline-lg font-bold text-on-surface">{cfg.title}</h2>
                            <p className="text-on-surface-variant font-body-md mt-2">{cfg.subtitle}</p>
                        </div>

                        <form className="space-y-6" onSubmit={submit} autoComplete="on">
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
                                            className={`appearance-none rounded-xl relative block w-full px-12 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 ${cfg.focusRing} transition-all sm:text-sm font-medium`}
                                            placeholder="you@example.com"
                                            aria-invalid={Boolean(errors?.email)}
                                        />
                                    </div>
                                    {errors?.email ? <p className="mt-1.5 text-xs font-medium text-error">{errors.email}</p> : null}
                                </div>

                                <div>
                                    <label htmlFor="password" className="block text-sm font-bold text-on-surface mb-2">
                                        {role === 'parent' ? 'Password' : role === 'student' ? 'Secret Password' : 'Password'}
                                    </label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                            <Icon name="lock" className="text-xl" />
                                        </span>
                                        <input
                                            id="password"
                                            type={show ? 'text' : 'password'}
                                            required
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            onFocus={() => setPasswordFocused(true)}
                                            onBlur={() => setPasswordFocused(false)}
                                            className={`appearance-none rounded-xl relative block w-full px-12 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 ${cfg.focusRing} transition-all sm:text-sm font-medium`}
                                            placeholder="••••••••"
                                            aria-invalid={Boolean(errors?.password)}
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
                                    {errors?.password ? <p className="mt-1.5 text-xs font-medium text-error">{errors.password}</p> : null}
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <input
                                        id="remember-me"
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className={`h-4 w-4 ${cfg.rememberClass} border-surface-container-high rounded cursor-pointer`}
                                    />
                                    <label htmlFor="remember-me" className="ml-2 block text-sm text-on-surface font-medium cursor-pointer">
                                        Remember me
                                    </label>
                                </div>
                                <div className="text-sm">
                                    <Link
                                        href="/forgot-password"
                                        className={role === 'tutor' ? 'font-bold text-secondary hover:underline transition-colors' : 'link-primary'}
                                    >
                                        Forgot your password?
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <button type="submit" disabled={processing} className="btn-enroll btn-block disabled:opacity-60">
                                    {processing ? 'Signing in…' : cfg.submitLabel}
                                </button>
                            </div>

                            <div className="text-center mt-4">
                                {cfg.registerHref ? (
                                    <p className="text-sm text-on-surface-variant font-medium">
                                        {cfg.registerPrompt}{' '}
                                        <Link
                                            href={cfg.registerHref}
                                            className={
                                                role === 'tutor'
                                                    ? 'font-bold text-secondary hover:underline transition-colors'
                                                    : 'link-primary'
                                            }
                                        >
                                            {cfg.registerLabel}
                                        </Link>
                                    </p>
                                ) : null}
                                {cfg.switchHref ? (
                                    <p className="text-sm text-on-surface-variant font-medium mt-4 pt-4 border-t border-surface-container-high">
                                        {cfg.switchPrompt}{' '}
                                        <Link href={cfg.switchHref} className={cfg.switchClass}>
                                            {cfg.switchLabel}
                                        </Link>
                                    </p>
                                ) : null}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
