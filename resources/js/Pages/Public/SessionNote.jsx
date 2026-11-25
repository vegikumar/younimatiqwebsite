import { Head, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function SessionNote({ context = {}, old: oldInput = {} }) {
    const { flash, errors } = usePage().props;
    const errList = Object.values(errors || {}).flat();
    const kindLabel = context.kind === 'trial' ? 'trial class' : 'class';

    const { data, setData, post, processing } = useForm({
        name: oldInput.name || '',
        email: oldInput.email || '',
        note: oldInput.note || '',
        kind: context.kind || 'class',
        ref_type: context.ref_type || '',
        ref_id: context.ref_id || '',
        session_name: context.session_name || '',
        when_label: context.when_label || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/session-note', { preserveScroll: true });
    };

    return (
        <PublicLayout title="Can't join — leave a note">
            <Head title="Can't join — leave a note" />
            <div className="max-w-lg mx-auto px-4 py-14">
                <div className="clay-card p-6 md:p-8">
                    <h1 className="text-2xl font-extrabold text-primary mb-2">Can&apos;t join this {kindLabel}?</h1>
                    <p className="text-sm text-on-surface-variant mb-6">
                        Send a short note to <strong>support@younimatiq.com</strong> and our team will help reschedule or follow up.
                    </p>

                    {(context.session_name || context.when_label) && (
                        <div className="mb-6 rounded-xl bg-primary/5 border border-primary/15 p-4 text-sm">
                            {context.session_name ? (
                                <p className="font-bold text-on-surface">{context.session_name}</p>
                            ) : null}
                            {context.when_label ? (
                                <p className="text-on-surface-variant mt-1">{context.when_label}</p>
                            ) : null}
                        </div>
                    )}

                    {flash?.success ? (
                        <div className="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 p-4 text-sm font-semibold">
                            {flash.success}
                        </div>
                    ) : null}

                    {errList.length > 0 && (
                        <div className="mb-4 rounded-xl bg-error-container text-on-error-container p-4 text-sm font-bold">
                            <ul className="list-disc pl-5">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                        </div>
                    )}

                    {!flash?.success ? (
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-bold mb-1">Your name *</label>
                                <input
                                    required
                                    className="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-1">Email *</label>
                                <input
                                    type="email"
                                    required
                                    className="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-1">Your note *</label>
                                <textarea
                                    required
                                    minLength={10}
                                    rows={5}
                                    placeholder="e.g. We can't make the class at this time — please reschedule to next week evening IST."
                                    className="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant"
                                    value={data.note}
                                    onChange={(e) => setData('note', e.target.value)}
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full btn-enroll disabled:opacity-70"
                            >
                                {processing ? 'Sending…' : 'Send note to support'}
                            </button>
                        </form>
                    ) : (
                        <a href="/" className="btn-enroll inline-block mt-2">Back to home</a>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
