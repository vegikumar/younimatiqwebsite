import { Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';

export default function BecomeTutor() {
    return (
        <PublicLayout title="Become a Tutor">
            <Head title="Become a Tutor | Younimatiq" />
            <BrandHero className="mt-8 mb-8" eyebrow="Join the Team">
                <h1 className="brand-title">
                    Become a <span className="text-brand-accent">Tutor</span>
                </h1>
            </BrandHero>
            <div className="pb-20 px-4 max-w-4xl mx-auto">
                <p>Join our amazing team of educators.</p>
            </div>
        </PublicLayout>
    );
}
