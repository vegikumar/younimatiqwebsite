import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Icon from '../../Components/Icon';

export default function VerifyEmail() {
    const { post, processing } = useForm({});

    return (
        <PublicLayout>
            <Head title="Verify Email" />
            <div className="min-h-[70vh] flex items-center justify-center px-4 py-16">
                <div className="max-w-md w-full text-center bg-white rounded-[2rem] border shadow-xl p-8 clay-card">
                    <div className="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                        <Icon name="mark_email_unread" className="text-white text-3xl" />
                    </div>
                    <h1 className="text-2xl font-bold mb-2">Verify your email</h1>
                    <p className="text-sm text-on-surface-variant mb-6">
                        Please check your inbox for a verification link. You can request another email if needed.
                    </p>
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => post('/email/verification-notification')}
                        className="w-full py-3 rounded-xl bg-primary text-white font-bold mb-3"
                    >
                        {processing ? 'Sending…' : 'Resend verification email'}
                    </button>
                    <Link href="/login/student" className="text-sm text-primary font-bold">Back to login</Link>
                </div>
            </div>
        </PublicLayout>
    );
}
