import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePage } from '@inertiajs/react';

const storageKey = (id) => `yq_popup_banner_dismissed_${id}`;

export default function PopupBanner() {
    const { popupBanner } = usePage().props;
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (!popupBanner?.id || !popupBanner?.image_url) {
            setOpen(false);
            return undefined;
        }

        try {
            if (popupBanner.show_once && window.localStorage.getItem(storageKey(popupBanner.id))) {
                setOpen(false);
                return undefined;
            }
        } catch {
            // ignore storage errors
        }

        const delay = Math.max(0, Number(popupBanner.delay_seconds ?? 2)) * 1000;
        const timer = window.setTimeout(() => setOpen(true), delay);
        return () => window.clearTimeout(timer);
    }, [popupBanner]);

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (event) => {
            if (event.key === 'Escape') dismiss();
        };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    if (!popupBanner?.image_url || !open || typeof document === 'undefined') {
        return null;
    }

    const dismiss = () => {
        setOpen(false);
        if (popupBanner.show_once) {
            try {
                window.localStorage.setItem(storageKey(popupBanner.id), '1');
            } catch {
                // ignore
            }
        }
    };

    const copyCoupon = async () => {
        if (!popupBanner.coupon_code) return;
        try {
            await navigator.clipboard.writeText(popupBanner.coupon_code);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1600);
        } catch {
            window.prompt('Copy coupon code:', popupBanner.coupon_code);
        }
    };

    const image = (
        <img
            src={popupBanner.image_url}
            alt={popupBanner.title || 'Special offer'}
            className="w-full max-h-[min(78vh,900px)] object-contain block"
            draggable={false}
        />
    );

    return createPortal(
        <div className="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-label={popupBanner.title || 'Offer'}>
            <button type="button" className="absolute inset-0 bg-black/55" aria-label="Close offer" onClick={dismiss} />
            <div className="relative z-[1] w-full max-w-[min(92vw,420px)] sm:max-w-[480px] rounded-2xl overflow-hidden bg-white shadow-2xl">
                <button
                    type="button"
                    onClick={dismiss}
                    className="absolute top-2 right-2 z-[2] w-9 h-9 rounded-full bg-black/55 text-white font-bold text-lg leading-none"
                    aria-label="Close"
                >
                    ×
                </button>

                {popupBanner.link_url ? (
                    <a href={popupBanner.link_url} onClick={dismiss} className="block">
                        {image}
                    </a>
                ) : (
                    <div>{image}</div>
                )}

                {popupBanner.coupon_code ? (
                    <div className="px-4 py-3 border-t border-outline-variant flex flex-wrap items-center justify-between gap-2 bg-surface-container-lowest">
                        <p className="text-sm font-bold">
                            Use code <span className="font-mono text-primary">{popupBanner.coupon_code}</span>
                        </p>
                        <button type="button" onClick={copyCoupon} className="px-3 py-1.5 rounded-lg border text-xs font-bold">
                            {copied ? 'Copied' : 'Copy code'}
                        </button>
                    </div>
                ) : null}
            </div>
        </div>,
        document.body,
    );
}
