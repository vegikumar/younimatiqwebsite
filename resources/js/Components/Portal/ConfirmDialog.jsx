import { useEffect, useId } from 'react';
import { createPortal } from 'react-dom';

export default function ConfirmDialog({
    open,
    title = 'Confirm',
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    tone = 'danger',
    busy = false,
    onConfirm,
    onCancel,
}) {
    const titleId = useId();

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape' && !busy) onCancel?.();
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, busy, onCancel]);

    if (!open || typeof document === 'undefined') return null;

    const confirmClass =
        tone === 'danger'
            ? 'bg-error text-white hover:opacity-90'
            : 'bg-primary text-white hover:opacity-90';

    return createPortal(
        <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="presentation">
            <button type="button" className="absolute inset-0 bg-black/40" aria-label="Dismiss" disabled={busy} onClick={onCancel} />
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className="relative w-full max-w-md clay-card p-6 space-y-4 shadow-xl"
            >
                <h2 id={titleId} className="text-lg font-bold">
                    {title}
                </h2>
                {message ? <p className="text-sm text-on-surface-variant">{message}</p> : null}
                <div className="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        disabled={busy}
                        onClick={onCancel}
                        className="px-4 py-2 rounded-xl border border-outline-variant text-sm font-bold disabled:opacity-60"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        disabled={busy}
                        onClick={onConfirm}
                        className={`px-4 py-2 rounded-xl text-sm font-bold disabled:opacity-60 ${confirmClass}`}
                    >
                        {busy ? 'Working…' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>,
        document.body
    );
}
