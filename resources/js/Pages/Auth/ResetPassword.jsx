import { Head, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';

export default function ResetPassword({ token = '', email: initialEmail = '' }) {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        token,
        email: initialEmail || '',
        password: '',
        password_confirmation: '',
    });
    const errList = Object.values(errors || {}).flat();

    return (
        <PublicLayout>
            <Head title="Reset Password" />
            <div className="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-xl clay-card">
                    <div className="text-center mb-8">
                        <div className="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                            <Icon name="password" className="text-white text-3xl" />
                        </div>
                        <h2 className="font-headline-lg font-bold text-on-surface">Reset Password</h2>
                        <p className="text-on-surface-variant mt-2">Choose a new password for your account.</p>
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
                            post('/reset-password');
                        }}
                    >
                        <input type="hidden" name="token" value={data.token} readOnly />
                        <div>
                            <label htmlFor="email" className="block text-sm font-bold text-on-surface mb-2">
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
                            />
                        </div>
                        <div>
                            <label htmlFor="password" className="block text-sm font-bold text-on-surface mb-2">
                                New Password
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                minLength={8}
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full px-4 py-3 rounded-xl bg-surface-container-low border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-bold text-on-surface mb-2">
                                Confirm Password
                            </label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="w-full px-4 py-3 rounded-xl bg-surface-container-low border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <button type="submit" disabled={processing} className="btn-primary btn-block">
                            {processing ? 'Saving…' : 'Reset Password'}
                        </button>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}
