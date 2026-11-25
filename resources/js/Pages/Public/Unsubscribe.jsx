import { Head, useForm, usePage } from '@inertiajs/react';

export default function Unsubscribe({ email = '', signed = false, already = false }) {
    const { flash, errors } = usePage().props;
    const form = useForm({ email: email || '', signed: signed ? '1' : '', sig_email: signed ? email : '' });
    const status = flash?.status || flash?.success;

    return (
        <div className="min-h-screen bg-[#f3f4f6] text-[#111827] font-sans">
            <Head title="Unsubscribe · Younimatiq" />
            <div className="max-w-[480px] mx-auto mt-12 px-4">
                <div className="bg-white border border-[#e5e7eb] rounded-xl p-7">
                    <h1 className="text-[22px] font-bold mb-2">Unsubscribe</h1>
                    <p className="text-sm text-[#4b5563] mb-4 leading-relaxed">
                        Stop receiving Younimatiq class digests and marketing emails. Class reminders for enrolled students are not affected.
                    </p>
                    {(status || already) && (
                        <div className="bg-[#ecfdf5] text-[#065f46] border border-[#a7f3d0] p-3 rounded-lg mb-4 text-sm">
                            {status || 'This email is already unsubscribed from marketing emails.'}
                        </div>
                    )}
                    <form
                        method="post"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post('/unsubscribe');
                        }}
                    >
                        <label htmlFor="email" className="block text-[13px] font-bold mb-1.5">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            readOnly={signed && !!email}
                            className="w-full box-border p-3 border border-[#d1d5db] rounded-lg text-sm"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                        />
                        {errors?.email ? <p className="text-[#b91c1c] text-sm mt-2">{errors.email}</p> : null}
                        <button type="submit" className="mt-3.5 w-full border-0 bg-[#2563eb] text-white font-bold py-3 rounded-lg" disabled={form.processing}>
                            Unsubscribe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
