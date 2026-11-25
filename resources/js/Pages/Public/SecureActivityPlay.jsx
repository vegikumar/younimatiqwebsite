import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export default function SecureActivityPlay({ title, slug, tokenUrl, description = '' }) {
    const [playUrl, setPlayUrl] = useState('');
    const [status, setStatus] = useState('loading');
    const [note, setNote] = useState('');

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const response = await fetch(tokenUrl, {
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
                        setNote(data.message || 'Could not open this protected activity.');
                    }
                    return;
                }
                if (!cancelled) {
                    setPlayUrl(data.play_url);
                    setStatus('playing');
                }
            } catch {
                if (!cancelled) {
                    setStatus('error');
                    setNote('Could not open this activity. Check your connection and try again.');
                }
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [tokenUrl]);

    useEffect(() => {
        const block = (event) => {
            event.preventDefault();
            return false;
        };
        document.addEventListener('contextmenu', block, true);
        document.addEventListener('copy', block, true);
        document.addEventListener('cut', block, true);
        document.addEventListener('dragstart', block, true);
        return () => {
            document.removeEventListener('contextmenu', block, true);
            document.removeEventListener('copy', block, true);
            document.removeEventListener('cut', block, true);
            document.removeEventListener('dragstart', block, true);
        };
    }, []);

    return (
        <PublicLayout>
            <Head title={title}>
                <meta name="robots" content="noindex,nofollow,noarchive,nosnippet" />
            </Head>
            <div className="mx-auto max-w-[1200px] space-y-4 p-4 md:p-6 select-none">
                <div>
                    <h1 className="font-headline-md text-2xl font-extrabold text-on-surface">{title}</h1>
                    {description ? <p className="mt-1 text-sm text-on-surface-variant">{description}</p> : null}
                    <p className="mt-2 text-xs font-bold text-on-surface-variant">Younimatiq protected activity · copying and saving are blocked</p>
                </div>

                {status === 'loading' ? <p className="p-6 font-bold">Opening secure player…</p> : null}
                {status === 'error' ? <p className="p-4 rounded-xl bg-error-container text-on-error-container font-bold text-sm">{note}</p> : null}

                {playUrl ? (
                    <iframe
                        title={title}
                        src={playUrl}
                        className="w-full min-h-[calc(100vh-10rem)] rounded-2xl border border-outline-variant bg-white"
                        allow="autoplay; fullscreen"
                        referrerPolicy="no-referrer"
                        sandbox="allow-scripts allow-same-origin allow-pointer-lock"
                    />
                ) : null}
            </div>
        </PublicLayout>
    );
}
