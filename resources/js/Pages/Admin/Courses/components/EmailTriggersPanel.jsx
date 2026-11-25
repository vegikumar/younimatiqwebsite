import { useState } from 'react';
import Icon from '../../../../Components/Icon';
import PortalCard from '../../../../Components/Portal/PortalCard';
import { usePage } from '@inertiajs/react';

export default function EmailTriggersPanel({ course, emailAutomations = [], defaultAutomationId }) {
    const { csrfToken, flash } = usePage().props;
    const [tab, setTab] = useState('new_course');
    const [automationId, setAutomationId] = useState(String(defaultAutomationId ?? ''));

    const previewNewCourse = () => {
        const url = `${course.emailTriggerPreviewBaseUrl}?type=new_course&email_automation_id=${automationId || ''}`;
        window.open(url, '_blank');
    };

    const previewOffer = (form) => {
        const params = new URLSearchParams({
            type: 'offer',
            email_automation_id: automationId || '',
            subject: form.subject.value || '',
            heading: form.heading.value || '',
            intro_text: form.intro_text.value || '',
            cta_text: form.cta_text.value || '',
            original_price: form.original_price.value || '',
            offer_price: form.offer_price.value || '',
            offer_note: form.offer_note.value || '',
        });
        window.open(`${course.emailTriggerPreviewBaseUrl}?${params.toString()}`, '_blank');
    };

    const confirmAudience = (e) => {
        if (e.nativeEvent.submitter?.value === 'audience') {
            const msg = tab === 'offer'
                ? 'Queue this offer email to the selected automation audiences?'
                : 'Queue this new-course email to the selected automation audiences?';
            if (!window.confirm(msg)) e.preventDefault();
        }
    };

    return (
        <PortalCard className="p-5 md:p-8 mt-8">
            <h2 className="font-headline-md font-bold mb-2 flex items-center gap-2">
                <Icon name="campaign" className="text-primary" /> Email triggers
            </h2>
            <p className="text-sm text-on-surface-variant mb-5">
                Announce this course or send an offer using one of your Email Automations’ audiences, include/exclude lists, and branding.
            </p>

            {flash?.success && <div className="mb-4 p-3 rounded-xl bg-secondary-container text-on-secondary-container text-sm font-medium">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-3 rounded-xl bg-error-container text-on-error-container text-sm font-medium">{flash.error}</div>}

            <div className="mb-5">
                <label className="block text-sm font-bold mb-2">Use audiences from</label>
                <select value={automationId} onChange={(e) => setAutomationId(e.target.value)} className="w-full md:w-96 p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    {emailAutomations.length ? emailAutomations.map((a) => (
                        <option key={a.id} value={a.id}>{a.name}{a.is_enabled ? '' : ' (off)'}</option>
                    )) : <option value="">No automations — create one first</option>}
                </select>
            </div>

            <div className="flex flex-wrap gap-2 mb-5">
                <button type="button" onClick={() => setTab('new_course')} className={`px-4 py-2 rounded-xl text-sm font-bold ${tab === 'new_course' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface'}`}>New course announcement</button>
                <button type="button" onClick={() => setTab('offer')} className={`px-4 py-2 rounded-xl text-sm font-bold ${tab === 'offer' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface'}`}>Offer / special price</button>
            </div>

            {tab === 'new_course' && (
                <form method="POST" action={course.emailTriggerSendUrl} className="space-y-4" onSubmit={confirmAudience}>
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="type" value="new_course" />
                    <input type="hidden" name="email_automation_id" value={automationId} />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-bold mb-2">Subject</label>
                            <input type="text" name="subject" defaultValue={`New class: ${course.title}`} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Headline</label>
                            <input type="text" name="heading" defaultValue="New class just launched" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold mb-2">Intro</label>
                            <textarea name="intro_text" rows={2} defaultValue="We just added a new class families are going to love:" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Button text</label>
                            <input type="text" name="cta_text" defaultValue="View this class" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Test email</label>
                            <input type="email" name="test_email" defaultValue="younimatiq@gmail.com" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <button type="button" onClick={previewNewCourse} className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Preview</button>
                        <button type="submit" name="mode" value="test" className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                        <button type="submit" name="mode" value="audience" className="btn-primary">Send to audiences</button>
                    </div>
                </form>
            )}

            {tab === 'offer' && (
                <form method="POST" action={course.emailTriggerSendUrl} className="space-y-4" onSubmit={confirmAudience} id="offer-trigger-form">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="type" value="offer" />
                    <input type="hidden" name="email_automation_id" value={automationId} />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-bold mb-2">Subject</label>
                            <input type="text" name="subject" defaultValue={`Special offer: ${course.title}`} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Headline</label>
                            <input type="text" name="heading" defaultValue="A special offer for your family" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold mb-2">Intro</label>
                            <textarea name="intro_text" rows={2} defaultValue="For a limited time, this class is available at a special price:" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Original price (USD / class)</label>
                            <input type="number" step="0.01" min={0} name="original_price" defaultValue={course.price_usd ?? 18} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Offer price (USD / class)</label>
                            <input type="number" step="0.01" min={0} name="offer_price" required placeholder="e.g. 9.00" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-bold mb-2">Offer note</label>
                            <input type="text" name="offer_note" defaultValue="Limited seats · Book this week" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Button text</label>
                            <input type="text" name="cta_text" defaultValue="Claim this offer" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Test email</label>
                            <input type="email" name="test_email" defaultValue="younimatiq@gmail.com" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <button type="button" onClick={() => previewOffer(document.getElementById('offer-trigger-form'))} className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Preview</button>
                        <button type="submit" name="mode" value="test" className="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                        <button type="submit" name="mode" value="audience" className="btn-primary">Send offer to audiences</button>
                    </div>
                </form>
            )}
        </PortalCard>
    );
}
