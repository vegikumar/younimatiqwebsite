import { usePage } from '@inertiajs/react';

export default function Flash() {
    const { flash } = usePage().props;
    if (!flash?.success && !flash?.error && !flash?.info) return null;

    return (
        <div className="fixed top-4 right-4 z-[100] space-y-2 max-w-sm w-[calc(100%-2rem)]">
            {flash.success && (
                <div className="rounded-xl bg-secondary-container text-on-secondary-container px-4 py-3 font-bold text-sm shadow-lg">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-xl bg-error-container text-on-error-container px-4 py-3 font-bold text-sm shadow-lg">
                    {flash.error}
                </div>
            )}
            {flash.info && (
                <div className="rounded-xl bg-primary-fixed text-on-primary-container px-4 py-3 font-bold text-sm shadow-lg">
                    {flash.info}
                </div>
            )}
        </div>
    );
}
