import { useEffect, useRef, useState } from 'react';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export default function HtmlGamePlayer({ game, xp = null }) {
    const [playUrl, setPlayUrl] = useState('');
    const [sessionId, setSessionId] = useState('');
    const [status, setStatus] = useState('loading');
    const [note, setNote] = useState('');
    const [elapsed, setElapsed] = useState(0);
    const startedAt = useRef(null);
    const finishing = useRef(false);
    const minSeconds = Number(xp?.min_seconds || 20);
    const alreadyToday = Boolean(xp?.awarded);

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const response = await fetch(`/student/games/${game.key}/play-token`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: '{}',
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (!cancelled) {
                        setStatus('error');
                        setNote(data.message || 'Could not start this game.');
                    }
                    return;
                }
                if (!cancelled) {
                    startedAt.current = Date.now();
                    setSessionId(data.session_id);
                    setPlayUrl(data.play_url);
                    setStatus('playing');
                }
            } catch {
                if (!cancelled) {
                    setStatus('error');
                    setNote('Could not start this game. Check your connection.');
                }
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [game.key]);

    useEffect(() => {
        if (status !== 'playing') return undefined;
        const id = window.setInterval(() => {
            if (!startedAt.current) return;
            setElapsed(Math.floor((Date.now() - startedAt.current) / 1000));
        }, 500);
        return () => window.clearInterval(id);
    }, [status]);

    const finish = async () => {
        if (!sessionId || finishing.current) return;
        finishing.current = true;
        setStatus('claiming');
        try {
            const response = await fetch(`/student/games/${game.key}/complete`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ session_id: sessionId }),
            });
            const data = await response.json().catch(() => ({}));
            setNote(data.message || (data.points_awarded ? `+${data.points_awarded} XP added.` : 'Finished.'));
            setStatus('done');
        } catch {
            setNote('XP could not be claimed. Try Finish again.');
            setStatus('playing');
            finishing.current = false;
        }
    };

    useEffect(() => {
        const onMessage = (event) => {
            if (event.origin !== window.location.origin) return;
            const payload = event.data;
            if (!payload || typeof payload !== 'object') return;
            const type = String(payload.type || '');
            if (type === 'chotipencil:complete' || type === 'younimatiq:complete') {
                finish();
            }
        };
        window.addEventListener('message', onMessage);
        return () => window.removeEventListener('message', onMessage);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sessionId, game.key]);

    return (
        <div className="space-y-4">
            {xp?.mode === 'daily' ? (
                <p className="text-sm font-bold text-on-surface-variant">
                    {alreadyToday
                        ? `You already earned ${xp.points || 0} XP from this game today.`
                        : `Play at least ${minSeconds}s, then finish. Up to ${xp.max || 0} XP once per day.`}
                </p>
            ) : null}

            {status === 'loading' ? <p className="p-6 font-bold">Opening the game…</p> : null}
            {status === 'error' ? <p className="p-4 clay-card text-sm font-bold text-error">{note}</p> : null}

            {playUrl ? (
                <iframe
                    title={game.title}
                    src={playUrl}
                    className="w-full min-h-[calc(100vh-12rem)] rounded-xl border border-outline-variant bg-white"
                    allow="autoplay; fullscreen"
                />
            ) : null}

            {status === 'playing' || status === 'claiming' || status === 'done' ? (
                <div className="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        className="btn-primary"
                        disabled={status === 'claiming' || status === 'done' || alreadyToday || elapsed < minSeconds}
                        onClick={finish}
                    >
                        {status === 'claiming'
                            ? 'Saving…'
                            : status === 'done'
                              ? 'Finished'
                              : elapsed < minSeconds
                                ? `Play ${Math.max(0, minSeconds - elapsed)}s more`
                                : 'Finish for XP'}
                    </button>
                    {elapsed >= minSeconds && !alreadyToday && status === 'playing' ? (
                        <span className="text-sm font-bold text-tertiary">Ready to claim today’s XP</span>
                    ) : null}
                </div>
            ) : null}

            {note ? <p className="text-sm font-bold text-primary">{note}</p> : null}
        </div>
    );
}
