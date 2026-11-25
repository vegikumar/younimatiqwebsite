import { Head, usePage } from '@inertiajs/react';
import { lazy, Suspense, useRef, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const Editor = lazy(() => import('@monaco-editor/react'));

const STARTER = `name = input("Enter your name: ")
print("Hello", name)
`;

export default function PythonLab({ available = false }) {
    const { csrfToken } = usePage().props;
    const [code, setCode] = useState(STARTER);
    const [stdin, setStdin] = useState('');
    const [stdout, setStdout] = useState('');
    const [stderr, setStderr] = useState('');
    const [status, setStatus] = useState(available ? 'Ready' : 'Unavailable');
    const [running, setRunning] = useState(false);
    const [duration, setDuration] = useState(null);
    const abortRef = useRef(null);

    const run = async () => {
        if (running) return;
        setRunning(true);
        setStatus('Running');
        setStdout('');
        setStderr('');
        setDuration(null);
        const controller = new AbortController();
        abortRef.current = controller;
        try {
            const res = await fetch('/student/python/run', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ code, stdin }),
                signal: controller.signal,
            });
            const data = await res.json().catch(() => ({}));
            setStdout(data.stdout || '');
            setStderr(data.stderr || (res.ok ? '' : 'Python Lab could not run this code.'));
            setDuration(typeof data.duration_ms === 'number' ? data.duration_ms : null);
            if (data.available === false) {
                setStatus('Unavailable');
            } else if (data.exit_code === 0) {
                setStatus('Finished');
            } else if (controller.signal.aborted) {
                setStatus('Stopped');
            } else {
                setStatus(data.exit_code === 124 ? 'Time limit' : 'Error');
            }
        } catch (error) {
            if (error?.name === 'AbortError') {
                setStatus('Stopped');
            } else {
                setStatus('Error');
                setStderr('Python Lab could not run this code.');
            }
        } finally {
            setRunning(false);
            abortRef.current = null;
        }
    };

    const stop = () => {
        abortRef.current?.abort();
    };

    return (
        <DashboardLayout title="Python Lab">
            <Head title="Python Lab" />
            <div className="p-5 md:p-8 space-y-4">
                <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-primary">Learning</p>
                        <h1 className="text-2xl font-bold">Python Lab</h1>
                        <p className="text-sm text-on-surface-variant mt-1">Write Python, run it, and read the real output.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button type="button" className="btn-primary btn-sm" onClick={run} disabled={running || !available}>
                            {running ? 'Running…' : 'Run code'}
                        </button>
                        <button type="button" className="px-3 py-1.5 rounded-xl border border-outline-variant text-sm font-bold" onClick={stop} disabled={!running}>
                            Stop
                        </button>
                        <button type="button" className="px-3 py-1.5 rounded-xl border border-outline-variant text-sm font-bold" onClick={() => { setStdout(''); setStderr(''); setDuration(null); setStatus(available ? 'Ready' : 'Unavailable'); }} disabled={running}>
                            Clear output
                        </button>
                        <button type="button" className="px-3 py-1.5 rounded-xl border border-outline-variant text-sm font-bold" onClick={() => setCode(STARTER)} disabled={running}>
                            Reset code
                        </button>
                    </div>
                </div>

                {!available ? (
                    <p className="clay-card p-4 text-sm font-bold text-error">Python Lab is unavailable on this server. Code will not be run.</p>
                ) : null}

                <div className="grid lg:grid-cols-[minmax(0,1fr)_280px] gap-4">
                    <div className="clay-card overflow-hidden min-h-[420px]">
                        <Suspense fallback={<p className="p-4 text-sm font-bold">Opening the editor…</p>}>
                            <Editor
                                height="420px"
                                defaultLanguage="python"
                                language="python"
                                theme="vs-dark"
                                value={code}
                                onChange={(value) => setCode(value ?? '')}
                                options={{
                                    fontSize: 14,
                                    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
                                    minimap: { enabled: false },
                                    wordWrap: 'on',
                                    tabSize: 4,
                                    automaticLayout: true,
                                    scrollBeyondLastLine: false,
                                }}
                            />
                        </Suspense>
                    </div>
                    <div className="clay-card p-4 space-y-3">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status</p>
                            <p className="font-bold">{status}</p>
                            {duration != null ? <p className="text-xs text-on-surface-variant">{duration} ms</p> : null}
                        </div>
                        <label className="block text-sm font-bold" htmlFor="python-stdin">
                            Program input
                            <textarea
                                id="python-stdin"
                                className="mt-1 w-full font-mono text-sm"
                                rows={6}
                                value={stdin}
                                onChange={(event) => setStdin(event.target.value)}
                                placeholder={'One line per input()\nAda'}
                                disabled={running}
                            />
                        </label>
                        <p className="text-xs text-on-surface-variant">Each line is sent when your program calls input().</p>
                    </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                    <section className="clay-card p-4">
                        <h2 className="text-sm font-bold mb-2">Output</h2>
                        <pre className="text-sm font-mono whitespace-pre-wrap break-words min-h-24 text-on-surface">{stdout || ' '}</pre>
                    </section>
                    <section className="clay-card p-4">
                        <h2 className="text-sm font-bold mb-2">Errors</h2>
                        <pre className="text-sm font-mono whitespace-pre-wrap break-words min-h-24 text-error">{stderr || ' '}</pre>
                    </section>
                </div>
            </div>
        </DashboardLayout>
    );
}
