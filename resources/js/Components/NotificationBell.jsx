import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Icon from './Icon';

export default function NotificationBell() {
    const { csrfToken } = usePage().props;
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [unread, setUnread] = useState(0);
    const rootRef = useRef(null);

    const load = async () => {
        try {
            const res = await fetch('/notifications', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            setItems(data.notifications || data.items || []);
            setUnread(Number(data.unread_count ?? data.unread ?? 0));
        } catch {
            /* ignore */
        }
    };

    useEffect(() => {
        load();
        const t = setInterval(load, 60000);
        return () => clearInterval(t);
    }, []);

    useEffect(() => {
        if (!open) return undefined;
        const onDoc = (e) => {
            if (rootRef.current && !rootRef.current.contains(e.target)) setOpen(false);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('click', onDoc);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onDoc);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const markRead = async (id) => {
        try {
            await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            load();
        } catch {
            /* ignore */
        }
    };

    const markAllRead = async () => {
        try {
            await fetch('/notifications/read-all', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            load();
        } catch {
            /* ignore */
        }
    };

    return (
        <div className="relative" ref={rootRef}>
            <button
                type="button"
                className="student-topbar-bell relative"
                aria-label="Notifications"
                aria-expanded={open}
                onClick={() => {
                    setOpen((v) => !v);
                    if (!open) load();
                }}
            >
                <Icon name="notifications" />
                {unread > 0 ? (
                    <span className="absolute -top-0.5 -right-0.5 min-w-[1.05rem] h-[1.05rem] px-1 flex items-center justify-center rounded-full bg-error text-[9px] font-bold text-white leading-none shadow ring-2 ring-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div className="absolute right-0 top-full mt-2 z-[80] w-[min(20rem,calc(100vw-1rem))] bg-white rounded-xl shadow-xl border border-outline-variant overflow-hidden" style={{ height: 320 }}>
                    <div className="h-full flex flex-col">
                        <div className="flex items-center justify-between gap-2 px-3 py-2 border-b border-outline-variant bg-surface-container-low shrink-0">
                            <h3 className="font-semibold text-xs text-on-surface">Notifications</h3>
                            {unread > 0 ? (
                                <button type="button" onClick={markAllRead} className="text-[11px] text-primary font-semibold hover:underline whitespace-nowrap">
                                    Mark all read
                                </button>
                            ) : null}
                        </div>
                        <div className="flex-1 min-h-0 overflow-y-auto">
                            {items.length === 0 ? (
                                <div className="px-3 py-10 text-center text-xs text-on-surface-variant">No new notifications.</div>
                            ) : (
                                items.map((item) => (
                                    <a
                                        key={item.id}
                                        href={item.action_url || '#'}
                                        onClick={() => markRead(item.id)}
                                        className="block px-3 py-2.5 border-b border-outline-variant hover:bg-surface-container-low text-left bg-primary/[0.04]"
                                    >
                                        <p className="text-xs font-semibold text-on-surface break-words">{item.title}</p>
                                        {item.body ? <p className="text-[11px] text-on-surface mt-0.5 line-clamp-2 break-words">{item.body}</p> : null}
                                    </a>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
