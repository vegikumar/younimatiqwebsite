import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';

export default function AdminOtp({ email = '' }) {
    const { errors, flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ otp: '' });
    const errList = Object.values(errors || {}).flat();
    const statusMessage = flash?.success || flash?.status;

    const resend = (e) => {
        e.preventDefault();
        router.post('/login/admin/otp/resend');
    };

    return (
        <PublicLayout>
            <Head title="Verify Admin Login" />
            <div className="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <div className="absolute top-[-10%] right-[-5%] w-96 h-96 bg-primary/10 rounded-full blur-3xl" />
                </div>

                <div className="max-w-md w-full bg-white rounded-[2rem] border border-surface-container-high shadow-xl relative z-10 clay-card overflow-hidden p-5 md:p-10">
                    <div className="text-center mb-8">
                        <div className="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                            <Icon name="mark_email_read" className="text-white text-3xl" />
                        </div>
                        <h2 className="font-headline-lg text-headline-lg font-bold text-on-surface">Verify Admin Login</h2>
                        <p className="text-on-surface-variant font-body-md mt-2">
                            Enter the 6-digit code sent to <span className="font-bold text-on-surface">{email}</span>
                        </p>
                    </div>
                    {statusMessage ? (
                        <div className="mb-4 bg-primary/10 text-primary p-4 rounded-xl text-sm font-bold">{statusMessage}</div>
                    ) : null}
                    {errList.length > 0 && (
                        <div className="mb-4 bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold">
                            <ul>{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                        </div>
                    )}
                    <form
                        className="space-y-6"
                        autoComplete="one-time-code"
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/login/admin/otp');
                        }}
                    >
                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2" htmlFor="otp">
                                Verification Code
                            </label>
                            <input
                                id="otp"
                                name="otp"
                                type="text"
                                inputMode="numeric"
                                pattern="[0-9]{6}"
                                maxLength={6}
                                required
                                autoFocus
                                value={data.otp}
                                onChange={(e) => setData('otp', e.target.value.replace(/\D/g, '').slice(0, 6))}
                                className="appearance-none rounded-xl relative block w-full px-4 py-3 bg-surface-container-low border border-surface-container-high text-on-surface text-center text-2xl tracking-[0.4em] font-bold focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="••••••"
                            />
                        </div>
                        <button type="submit" disabled={processing} className="btn-primary btn-block">
                            {processing ? 'Verifying…' : 'Verify & Continue'}
                        </button>
                    </form>
                    <form className="mt-4" onSubmit={resend}>
                        <button type="submit" className="w-full text-sm font-bold text-primary hover:underline">
                            Resend code
                        </button>
                    </form>
                    <div className="mt-6 text-center">
                        <Link href="/login/admin" className="text-sm font-medium text-on-surface-variant hover:text-primary">
                            Back to login
                        </Link>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
