import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function loadTinyMce() {
    if (window.tinymce) return Promise.resolve(window.tinymce);
    return new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-ym-tinymce]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.tinymce));
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js';
        script.referrerPolicy = 'origin';
        script.dataset.ymTinymce = '1';
        script.onload = () => resolve(window.tinymce);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function initTinyMCE(selector) {
    const id = selector.replace('#', '');
    if (window.tinymce?.get(id)) {
        window.tinymce.get(id).remove();
    }
    window.tinymce.init({
        selector,
        menubar: false,
        plugins: 'lists link code',
        toolbar: 'undo redo | bold italic | bullist numlist | link code',
        height: 250,
        setup(editor) {
            editor.on('change', () => editor.save());
        },
    });
}

export default function Index({ faqs = [] }) {
    const { flash, csrfToken } = usePage().props;
    const [editingId, setEditingId] = useState(null);
    const addAnswerRef = useRef(null);

    useEffect(() => {
        let cancelled = false;
        loadTinyMce()
            .then(() => {
                if (!cancelled) initTinyMCE('#add-faq-answer');
            })
            .catch(() => {});
        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        if (!editingId) return;
        let cancelled = false;
        const t = window.setTimeout(() => {
            loadTinyMce()
                .then(() => {
                    if (!cancelled) initTinyMCE(`#edit-faq-answer-${editingId}`);
                })
                .catch(() => {});
        }, 50);
        return () => {
            cancelled = true;
            window.clearTimeout(t);
        };
    }, [editingId]);

    const destroy = (id) => {
        if (!confirm('Delete this FAQ?')) return;
        router.delete(`/admin/faqs/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="FAQs">
            <Head title="FAQs" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-tertiary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Frequently Asked Questions</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Manage FAQ entries shown on the public FAQ page.</p>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-tertiary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2">
                            <Icon name="help" className="text-tertiary" /> Add FAQ
                        </h2>
                        <form
                            method="POST"
                            action="/admin/faqs"
                            className="space-y-4"
                            onSubmit={() => window.tinymce?.triggerSave()}
                        >
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2">Question</label>
                                <input type="text" name="question" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Answer</label>
                                <textarea ref={addAnswerRef} id="add-faq-answer" name="answer" rows={4} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-bold mb-2">Category</label>
                                    <input type="text" name="category" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="General" />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">Sort Order</label>
                                    <input type="number" name="sort_order" defaultValue={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                </div>
                            </div>
                            <button type="submit" className="btn-primary btn-block">Add FAQ</button>
                        </form>
                    </PortalCard>

                    <div className="lg:col-span-2 space-y-4">
                        {faqs.length === 0 ? (
                            <PortalCard className="p-6 md:p-12 text-center text-on-surface-variant">No FAQs yet. Add your first question above.</PortalCard>
                        ) : (
                            faqs.map((faq) => (
                                <PortalCard key={faq.id} className="p-6">
                                    {editingId !== faq.id ? (
                                        <>
                                            <div className="flex justify-between items-start mb-3">
                                                <div>
                                                    {faq.category ? (
                                                        <span className="text-xs font-bold text-primary uppercase tracking-wider">{faq.category}</span>
                                                    ) : null}
                                                    <h3 className="font-bold text-lg mt-1">{faq.question}</h3>
                                                </div>
                                                <div className="flex gap-2">
                                                    <button type="button" onClick={() => setEditingId(faq.id)} className="p-2 text-on-surface-variant hover:text-primary">
                                                        <Icon name="edit" className="text-sm" />
                                                    </button>
                                                    <button type="button" onClick={() => destroy(faq.id)} className="p-2 text-on-surface-variant hover:text-error">
                                                        <Icon name="delete" className="text-sm" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="text-on-surface-variant leading-relaxed prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: faq.answer }} />
                                            <div className="mt-3 flex gap-4 text-xs text-on-surface-variant">
                                                <span>Order: {faq.sort_order}</span>
                                                <span>{faq.is_active ? 'Active' : 'Inactive'}</span>
                                            </div>
                                        </>
                                    ) : (
                                        <form
                                            method="POST"
                                            action={`/admin/faqs/${faq.id}`}
                                            className="space-y-4"
                                            onSubmit={() => window.tinymce?.triggerSave()}
                                        >
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="_method" value="PUT" />
                                            <div>
                                                <label className="block text-sm font-bold mb-2">Question</label>
                                                <input type="text" name="question" defaultValue={faq.question} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-bold mb-2">Answer</label>
                                                <textarea id={`edit-faq-answer-${faq.id}`} name="answer" rows={3} defaultValue={faq.answer} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <input type="text" name="category" defaultValue={faq.category || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" placeholder="Category" />
                                                <input type="number" name="sort_order" defaultValue={faq.sort_order} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                            </div>
                                            <div className="staff-form-actions">
                                                <label className="staff-check">
                                                    <input type="checkbox" name="is_active" value="1" defaultChecked={faq.is_active} />
                                                    <span>Active</span>
                                                </label>
                                                <button type="submit" className="btn-primary btn-sm">Save</button>
                                                <button type="button" onClick={() => setEditingId(null)} className="px-4 py-2 bg-surface-container font-bold rounded-lg text-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    )}
                                </PortalCard>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
