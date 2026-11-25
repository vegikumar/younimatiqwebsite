import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import BrandHero from '../../../Components/BrandHero';

export default function LaunchingSoon({ category, seoTitle }) {
    const form = useForm({ email: '' });

    return (
        <PublicLayout title={seoTitle || 'Launching Soon'}>
            <Head title={seoTitle || 'Launching Soon'} />
            <BrandHero className="mt-8 mb-8" eyebrow="Coming soon">
                <h1 className="brand-title">{category.name} is launching soon</h1>
                <p className="brand-description">Join the waitlist and we&apos;ll notify you when classes go live.</p>
            </BrandHero>
            <div className="max-w-md mx-auto pb-20 px-4">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(`/courses/category/${category.slug}/waitlist`);
                    }}
                    className="clay-card p-6 space-y-4"
                >
                    <label className="block text-sm font-bold" htmlFor="email">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        required
                        className="w-full rounded-xl border px-4 py-3"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    <button type="submit" disabled={form.processing} className="btn-enroll w-full">
                        Notify me
                    </button>
                </form>
            </div>
        </PublicLayout>
    );
}
