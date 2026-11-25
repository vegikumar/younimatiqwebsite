import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import BrandHero from '../../Components/BrandHero';

export default function TrialClass() {
    useEffect(() => {
        if (typeof window.openLeadModal === 'function') {
            window.openLeadModal();
        }
    }, []);

    const openModal = (e) => {
        e.preventDefault();
        if (typeof window.openLeadModal === 'function') {
            window.openLeadModal(e);
        }
    };

    return (
        <PublicLayout title="Trial Class">
            <Head title="Free Trial Class | Younimatiq" />
            <BrandHero className="mt-8 mb-8" eyebrow="Try For Free">
                <h1 className="brand-title">
                    Book a <span className="text-brand-accent">Trial Class</span>
                </h1>
            </BrandHero>
            <div className="pb-20 px-4 max-w-4xl mx-auto flex justify-center mt-8">
                <button type="button" className="btn btn-primary btn-lg px-8 py-4 text-lg font-bold" onClick={openModal}>
                    Book Now
                </button>
            </div>
        </PublicLayout>
    );
}
