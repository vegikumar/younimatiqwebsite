import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';

export default function ForgotPassword() {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({ email: '' });
    const errList = Object.values(errors || {}).flat();

    return (
        <PublicLayout>
            <Head title="Forgot Password" />
            <div className="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-xl clay-card">
                    <div className="text-center mb-8">
                        <div className="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                            <Icon name="lock_reset" className="text-white text-3xl" />
                        </div>
                        <h2 className="font-headline-lg font-bold text-on-surface">Forgot Password?</h2>
                        <p className="text-on-surface-variant mt-2">Enter your email and we&apos;ll send you a reset link.</p>
                    </div>
                    {errList.length > 0 && (
                        <div className="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                            <ul className="list-disc list-inside">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                        </div>
                    )}
                    <form
                        className="space-y-6"
                        autoComplete="on"
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/forgot-password');
                        }}
                    >
                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2" htmlFor="email">
                                Email Address
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autoComplete="email"
                                inputMode="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full px-4 py-3 rounded-xl bg-surface-container-low border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="you@example.com"
                            />
                        </div>
                        <button type="submit" disabled={processing} className="btn-primary btn-block">
                            {processing ? 'Sending…' : 'Send Reset Link'}
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-on-surface-variant">
                        Remember your password?{' '}
                        <Link href="/login/student" className="text-primary font-bold hover:underline">
                            Sign in
                        </Link>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
