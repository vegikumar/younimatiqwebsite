import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';
import SyllabusModulesEditor from './components/SyllabusModulesEditor';
import { attachRichTextEditors } from '../../../utils/loadCKEditor';

const DRAFT_KEY = 'admin_create_course_draft';

const defaultRows = () => Array.from({ length: 8 }, (_, i) => ({
    id: Date.now() + i,
    class_name: '',
    description: '',
    outcome: '',
    practical_video_url: '',
}));

export default function Create({ categories = [], ageGroups = [], routes = {} }) {
    const { csrfToken, errors } = usePage().props;
    const errList = errors ? Object.values(errors).flat() : [];
    const [ageGroupIds, setAgeGroupIds] = useState([]);
    const [courseType, setCourseType] = useState('live');
    const [selfPacedEnabled, setSelfPacedEnabled] = useState(false);
    const [units, setUnits] = useState([]);
    const [hasSavedDraft, setHasSavedDraft] = useState(false);
    const [savedDraftTime, setSavedDraftTime] = useState('');
    const [imagePreview, setImagePreview] = useState('');

    useEffect(() => {
        attachRichTextEditors('.rich-text').catch(() => {});
    }, []);

    useEffect(() => {
        const draft = localStorage.getItem(DRAFT_KEY);
        if (draft) {
            try {
                const parsed = JSON.parse(draft);
                setHasSavedDraft(true);
                setSavedDraftTime(parsed._savedAt ? new Date(parsed._savedAt).toLocaleTimeString() : 'earlier');
            } catch {
                /* ignore */
            }
        }
    }, []);

    const toggleAge = (id) => {
        setAgeGroupIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const newModule = () => ({ id: Date.now() + Math.random(), title: '', content_type: 'video', duration_minutes: '', description: '' });
    const addUnit = () => setUnits((u) => [...u, { id: Date.now(), title: '', description: '', modules: [newModule()] }]);

    const clearDraft = () => localStorage.removeItem(DRAFT_KEY);

    return (
        <DashboardLayout title="Create Course">
            <Head title="Create Class / Course" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="mb-8 border-b border-outline-variant pb-6 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 className="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                                <Icon name="add_circle" className="text-primary" /> Create Class / Tutoring Listing
                            </h2>
                            <p className="font-body-md text-on-surface-variant mt-2">Set up a new listing for students to discover. You can save as draft anytime.</p>
                        </div>
                        <a href={routes.sampleCsvBulk} className="px-3.5 py-2 bg-surface-container-high border border-outline-variant text-on-surface text-xs font-bold rounded-xl inline-flex items-center gap-1.5">
                            <Icon name="download" className="text-sm text-primary" /> Bulk Sample CSV
                        </a>
                    </div>

                    {hasSavedDraft && (
                        <div className="mb-6 p-4 bg-warning-container border-2 border-warning-container rounded-2xl flex flex-wrap items-center justify-between gap-3 text-warning shadow-sm">
                            <div className="flex items-center gap-3">
                                <Icon name="history_edu" className="text-2xl text-warning" />
                                <div>
                                    <p className="font-bold text-sm">Unsaved Draft Recovered!</p>
                                    <p className="text-xs text-warning">We detected an unsaved course draft from your previous browser session ({savedDraftTime}).</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <button type="button" onClick={() => setHasSavedDraft(false)} className="px-4 py-2 bg-warning-container text-warning rounded-xl text-xs font-bold">Discard</button>
                            </div>
                        </div>
                    )}

                    {errList.length > 0 && (
                        <div className="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
                            <ul className="list-disc pl-5">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                        </div>
                    )}

                    <form method="POST" action={routes.store} encType="multipart/form-data" className="space-y-6" onSubmit={clearDraft}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Class Title *</label>
                            <input type="text" name="title" required className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary outline-none" placeholder="e.g. AI for Young Innovators" />
                        </div>
                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Slug (optional)</label>
                            <input type="text" name="slug" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary outline-none" />
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Listing Format *</label>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                {['1to1', 'group', 'tutoring', 'club'].map((f) => (
                                    <label key={f} className="flex items-center gap-2 p-3.5 border border-outline-variant rounded-xl cursor-pointer font-bold bg-surface-container-low text-sm">
                                        <input type="radio" name="format" value={f} defaultChecked={f === 'group'} className="text-primary w-4 h-4" />
                                        {f === '1to1' ? '1 to 1 (1:1)' : f.charAt(0).toUpperCase() + f.slice(1)}
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Course Type *</label>
                            <select name="course_type" value={courseType} onChange={(e) => setCourseType(e.target.value)} required className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none">
                                <option value="live">Live Sessions (Zoom)</option>
                                <option value="self-paced">Self-Paced Content</option>
                            </select>
                            <label className="flex items-center gap-2 cursor-pointer text-xs text-on-surface-variant">
                                <input type="checkbox" name="self_paced_enabled" value="1" checked={selfPacedEnabled} onChange={(e) => setSelfPacedEnabled(e.target.checked)} className="w-4 h-4 text-primary rounded" />
                                Offer self-paced option at checkout
                            </label>
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Description *</label>
                            <textarea id="course-description-input" name="description" rows={4} className="rich-text w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none resize-none" />
                        </div>

                        <SyllabusModulesEditor initialRows={defaultRows()} sampleCsvUrl={routes.sampleCsvSyllabus} />

                        {(courseType === 'self-paced' || selfPacedEnabled) && (
                            <div className="space-y-4 pt-6 border-t border-outline-variant">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <label className="block font-bold text-lg text-on-surface">Syllabus Units &amp; Modules</label>
                                    <button type="button" onClick={addUnit} className="text-secondary font-bold flex items-center gap-1 bg-secondary/10 px-3 py-1.5 rounded-lg text-xs">
                                        <Icon name="add" className="text-sm" /> Add Unit
                                    </button>
                                </div>
                                {units.map((unit, ui) => (
                                    <div key={unit.id} className="p-4 border border-outline-variant rounded-2xl bg-white space-y-3">
                                        <input type="text" name={`units[${ui}][title]`} required placeholder="Unit title" className="w-full p-2.5 border rounded-lg text-sm font-bold" />
                                        <textarea name={`units[${ui}][description]`} rows={2} placeholder="Unit description" className="w-full p-2.5 border rounded-lg text-sm resize-none" />
                                        {unit.modules.map((mod, mi) => (
                                            <div key={mod.id} className="p-3 rounded-xl bg-surface-container-low space-y-2">
                                                <input type="text" name={`units[${ui}][modules][${mi}][title]`} required placeholder="Module title" className="w-full p-2 border rounded-lg text-sm" />
                                                <select name={`units[${ui}][modules][${mi}][content_type]`} defaultValue="video" className="w-full p-2 border rounded-lg text-sm">
                                                    {['video', 'pdf', 'quiz', 'assignment', 'image', 'text'].map((t) => <option key={t} value={t}>{t}</option>)}
                                                </select>
                                                <input type="number" name={`units[${ui}][modules][${mi}][duration_minutes]`} placeholder="min" className="w-full p-2 border rounded-lg text-sm" />
                                                <textarea name={`units[${ui}][modules][${mi}][description]`} rows={2} className="w-full p-2 border rounded-lg text-sm resize-none" />
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4 border-t border-outline-variant">
                            {[
                                { name: 'price_usd', label: 'Per Class Price ($) *', def: '18.00' },
                                { name: 'price_aed', label: 'Per Class Price (AED) *', def: '30.00' },
                                { name: 'price_inr', label: 'Per Class Price (INR) *', def: '600.00' },
                            ].map((p) => (
                                <div key={p.name} className="space-y-2">
                                    <label className="block font-bold text-on-surface">{p.label}</label>
                                    <input type="number" step="0.01" name={p.name} required defaultValue={p.def} className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                                </div>
                            ))}
                            <div className="space-y-2">
                                <label className="block font-bold text-on-surface">Category</label>
                                <select name="category_ids[]" multiple size={8} className="staff-multiselect">
                                    {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                            </div>
                        </div>

                        <div className="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                            <label className="block font-bold text-on-surface flex items-center gap-2"><Icon name="image" className="text-primary" /> Course Thumbnail</label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="thumbnail_url" placeholder="https://example.com/image.jpg" className="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none" />
                                <input type="file" name="thumbnail_file" accept="image/*" onChange={(e) => { const f = e.target.files?.[0]; if (f) setImagePreview(URL.createObjectURL(f)); }} className="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm" />
                            </div>
                            {imagePreview ? <img src={imagePreview} alt="" className="h-14 w-24 object-cover rounded-lg border" /> : null}
                        </div>

                        <div className="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                            <label className="block font-bold text-on-surface flex items-center gap-2"><Icon name="smart_display" className="text-primary" /> Promo Video</label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="video_url" placeholder="YouTube / MP4 link" className="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none" />
                                <input type="file" name="video_file" accept="video/*" className="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm" />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Grade Band</label>
                            <input type="hidden" name="age_groups_present" value="1" />
                            <div className="flex flex-wrap gap-2">
                                {ageGroups.length ? ageGroups.map((ag) => {
                                    const on = ageGroupIds.includes(ag.id);
                                    return (
                                        <label key={ag.id} className="cursor-pointer">
                                            <input type="checkbox" name="age_groups[]" value={ag.id} checked={on} onChange={() => toggleAge(ag.id)} className="peer sr-only" />
                                            <span className={`inline-flex items-center px-4 py-2 rounded-full border text-sm font-medium transition-colors ${on ? 'bg-primary text-on-primary border-primary' : 'border-outline-variant bg-surface-container-low text-on-surface-variant'}`}>{ag.name}</span>
                                        </label>
                                    );
                                }) : (
                                    <span className="text-sm text-on-surface-variant">No age bands yet. <Link href={routes.ageGroupsIndex} className="text-primary underline">Add age bands</Link>.</span>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Welcome Message for New Families</label>
                            <textarea name="welcome_message" rows={2} className="rich-text w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none resize-none" />
                        </div>

                        <div className="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                            <label className="block font-bold text-on-surface flex items-center gap-2"><Icon name="waving_hand" className="text-primary" /> Welcome Video</label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="welcome_video_url" className="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none" />
                                <input type="file" name="welcome_video_file" accept="video/*" className="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm" />
                            </div>
                        </div>

                        <div className="pt-6 border-t border-outline-variant mb-6">
                            <h3 className="font-bold text-lg mb-4 text-on-surface">SEO & Publishing</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <input type="text" name="seo_title" placeholder="Meta Title" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                                <textarea name="seo_description" rows={2} placeholder="Meta Description" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none resize-none" />
                            </div>
                            <input type="datetime-local" name="published_at" className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                            <label className="flex items-center gap-2 cursor-pointer mt-6">
                                <input type="checkbox" name="is_top_course" value="1" className="w-5 h-5 text-primary rounded" />
                                <span className="text-sm font-bold text-on-surface">Show in &quot;Top Courses&quot; section on homepage</span>
                            </label>
                        </div>

                        <div className="pt-6 border-t border-outline-variant flex flex-wrap items-center gap-4">
                            <button type="submit" name="status" value="published" className="btn-primary flex-1 py-4 text-base font-bold shadow-md">Create &amp; Publish Course</button>
                            <button type="submit" name="status" value="draft" className="px-8 py-4 bg-warning text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md">
                                <Icon name="draft" className="text-lg" /> Save as Draft
                            </button>
                            <Link href={routes.index} className="px-8 py-4 bg-surface-container-high rounded-xl font-bold">Cancel</Link>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
