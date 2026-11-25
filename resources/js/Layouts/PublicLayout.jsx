import { Head } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import Flash from '../Components/Flash';
import SiteHeader from '../Components/SiteHeader';
import SiteFooter from '../Components/SiteFooter';

const LeadModal = lazy(() => import('../Components/LeadModal'));
const EnrollModal = lazy(() => import('../Components/EnrollModal'));
const FloatingActions = lazy(() => import('../Components/FloatingActions'));
const PopupBanner = lazy(() => import('../Components/PopupBanner'));

export default function PublicLayout({ children, title }) {
    return (
        <div className="min-h-screen flex flex-col text-on-surface bg-surface-container-low overflow-x-hidden">
            <Head title={title || undefined} />
            <Flash />
            <SiteHeader />
            <main className="flex-grow min-w-0 max-w-full w-full overflow-x-clip">{children}</main>
            <SiteFooter />
            <Suspense fallback={null}>
                <LeadModal />
                <EnrollModal />
                <FloatingActions />
                <PopupBanner />
            </Suspense>
        </div>
    );
}
