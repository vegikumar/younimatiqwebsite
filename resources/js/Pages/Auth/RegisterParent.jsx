import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function RegisterParent() {
    const { errors = {} } = usePage().props;
    const { data, setData, post, processing } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <PublicLayout title="Parent Register">
            <Head title="Parent Register" />
            <div className="max-w-md mx-auto px-4 py-16">
                <h1 className="font-headline-lg text-3xl font-bold mb-2">Parent account</h1>
                <p className="text-on-surface-variant mb-6">Create an account to follow your child’s learning. Admin will link student profiles to this email.</p>
                {Object.values(errors).flat().length ? (
                    <div className="mb-4 p-3 rounded-xl bg-error-container text-sm font-bold">
                        {Object.values(errors).flat().map((e, i) => (
                            <p key={i}>{e}</p>
                        ))}
                    </div>
                ) : null}
                <form
                    className="space-y-4 clay-card p-6"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/register/parent');
                    }}
                >
                    <input className="w-full rounded-xl border px-4 py-3" placeholder="Your name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    <input type="email" className="w-full rounded-xl border px-4 py-3" placeholder="Email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                    <input type="password" className="w-full rounded-xl border px-4 py-3" placeholder="Password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                    <input type="password" className="w-full rounded-xl border px-4 py-3" placeholder="Confirm password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} required />
                    <button type="submit" className="btn-primary w-full" disabled={processing}>
                        Create parent account
                    </button>
                </form>
                <p className="mt-4 text-sm text-center">
                    Already have an account?{' '}
                    <Link href="/login/parent" className="text-primary font-bold hover:underline">
                        Sign in
                    </Link>
                </p>
            </div>
        </PublicLayout>
    );
}
