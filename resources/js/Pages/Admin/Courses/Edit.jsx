import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';
import SyllabusModulesEditor from './components/SyllabusModulesEditor';
import EmailTriggersPanel from './components/EmailTriggersPanel';
import SyllabusUnitsPanel from './components/SyllabusUnitsPanel';
import { attachRichTextEditors } from '../../../utils/loadCKEditor';

export default function Edit({ course = {}, categories = [], ageGroups = [], emailAutomations = [], defaultAutomationId, routes = {} }) {
    const { csrfToken, errors } = usePage().props;
    const errList = errors ? Object.values(errors).flat() : [];
    const [imageUrl, setImageUrl] = useState(course.thumbnail_url || '');
    const selectedIds = course.selectedAgeGroupIds || [];

    useEffect(() => {
        attachRichTextEditors('.rich-text').catch(() => {});
    }, []);

    const isYoutube = (url) => url && (url.includes('youtube.com') || url.includes('youtu.be'));

    return (
        <DashboardLayout title="Edit Course">
            <Head title={`Edit · ${course.title}`} />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Edit Course: {course.title}</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Update the full details of this course.</p>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                {errList.length > 0 && (
                    <div className="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
                        <ul className="list-disc pl-5">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                    </div>
                )}

                <PortalCard className="p-5 md:p-8">
                    <form method="POST" action={course.updateUrl} encType="multipart/form-data">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PUT" />
                        <input type="hidden" name="age_groups_present" value="1" />

                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Category</label>
                            <select name="category_ids[]" multiple size={8} defaultValue={course.categoryIds?.map(String)} className="staff-multiselect">
                                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </div>

                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Title</label>
                            <input type="text" name="title" defaultValue={course.title} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </div>
                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Slug (optional)</label>
                            <input type="text" name="slug" defaultValue={course.slug || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </div>
                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Description</label>
                            <textarea name="description" rows={5} defaultValue={course.description || ''} className="rich-text w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </div>

                        <SyllabusModulesEditor initialRows={course.syllabusModules || []} sampleCsvUrl={routes.sampleCsvSyllabus} />

                        <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                            <div>
                                <label className="block text-sm font-bold mb-2">Per Class ($) *</label>
                                <input type="number" step="0.01" name="price_usd" defaultValue={course.price_usd} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Per Class (AED) *</label>
                                <input type="number" step="0.01" name="price_aed" defaultValue={course.price_aed} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Per Class (INR) *</label>
                                <input type="number" step="0.01" name="price_inr" defaultValue={course.price_inr} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Course Type</label>
                                <select name="course_type" defaultValue={course.course_type} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none">
                                    <option value="live">Live</option>
                                    <option value="self-paced">Self-paced</option>
                                </select>
                                <label className="mt-2 flex items-center gap-2 cursor-pointer text-xs text-on-surface-variant">
                                    <input type="checkbox" name="self_paced_enabled" value="1" defaultChecked={course.self_paced_enabled} className="w-4 h-4 text-primary rounded" />
                                    Offer self-paced option at checkout
                                </label>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Listing Format</label>
                                <select name="format" defaultValue={course.format} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none">
                                    <option value="1to1">1 to 1 (1:1)</option>
                                    <option value="group">Group (1:M)</option>
                                    <option value="tutoring">Tutoring</option>
                                    <option value="club">Club</option>
                                </select>
                            </div>
                        </div>

                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Grade Band</label>
                            <div className="flex flex-wrap gap-2">
                                {ageGroups.map((ag) => (
                                    <label key={ag.id} className="cursor-pointer">
                                        <input type="checkbox" name="age_groups[]" value={ag.id} defaultChecked={selectedIds.includes(ag.id)} className="peer sr-only" />
                                        <span className="inline-flex items-center px-4 py-2 rounded-full border border-outline-variant bg-surface-container-low text-sm font-medium peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary">{ag.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Learning Outcomes (One per line)</label>
                            <textarea name="learning_outcomes" rows={4} defaultValue={course.learning_outcomes || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </div>

                        <div className="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="p-5 bg-surface-container-low border border-outline-variant rounded-2xl">
                                <label className="block text-base font-bold mb-4 flex items-center gap-2"><Icon name="image" className="text-primary" /> Thumbnail</label>
                                {imageUrl ? (
                                    <div className="mb-4">
                                        <img src={imageUrl} alt="" className="max-h-64 rounded-xl border object-contain" />
                                        {course.thumbnail_url ? (
                                            <label className="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                                <input type="checkbox" name="remove_thumbnail" value="1" onChange={(e) => { if (e.target.checked) setImageUrl(''); }} className="rounded" />
                                                Remove existing thumbnail
                                            </label>
                                        ) : null}
                                    </div>
                                ) : null}
                                <input type="text" name="thumbnail_url" defaultValue={course.thumbnail_url || ''} onChange={(e) => setImageUrl(e.target.value)} className="w-full p-2.5 bg-white border rounded-xl text-sm mb-3" />
                                <input type="file" name="thumbnail_file" accept="image/*" onChange={(e) => { const f = e.target.files?.[0]; if (f) setImageUrl(URL.createObjectURL(f)); }} className="w-full p-2 bg-white border rounded-xl text-sm" />
                            </div>
                            <div className="p-5 bg-surface-container-low border border-outline-variant rounded-2xl">
                                <label className="block text-base font-bold mb-4 flex items-center gap-2"><Icon name="smart_display" className="text-primary" /> Promo Video</label>
                                {course.video_url ? (
                                    <div className="mb-4">
                                        {isYoutube(course.video_url) ? (
                                            <a href={course.video_url} target="_blank" rel="noreferrer" className="text-primary hover:underline">View YouTube Video</a>
                                        ) : (
                                            <video src={course.video_url} controls className="w-full max-h-64 rounded-xl border bg-black" />
                                        )}
                                        <label className="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                            <input type="checkbox" name="remove_video" value="1" className="rounded" /> Remove existing promo video
                                        </label>
                                    </div>
                                ) : null}
                                <input type="text" name="video_url" defaultValue={course.video_url || ''} className="w-full p-2.5 bg-white border rounded-xl text-sm mb-3" />
                                <input type="file" name="video_file" accept="video/*" className="w-full p-2 bg-white border rounded-xl text-sm" />
                            </div>
                        </div>

                        <div className="mb-6 p-5 bg-surface-container-low border border-outline-variant rounded-2xl">
                            <label className="block text-base font-bold mb-4 flex items-center gap-2"><Icon name="waving_hand" className="text-primary" /> Welcome Video</label>
                            {course.welcome_video_url ? (
                                <div className="mb-4">
                                    {isYoutube(course.welcome_video_url) ? (
                                        <a href={course.welcome_video_url} target="_blank" rel="noreferrer" className="text-primary hover:underline">View YouTube Video</a>
                                    ) : (
                                        <video src={course.welcome_video_url} controls className="w-full max-h-64 rounded-xl border bg-black" />
                                    )}
                                    <label className="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                        <input type="checkbox" name="remove_welcome_video" value="1" className="rounded" /> Remove existing welcome video
                                    </label>
                                </div>
                            ) : null}
                            <input type="text" name="welcome_video_url" defaultValue={course.welcome_video_url || ''} className="w-full p-2.5 bg-white border rounded-xl text-sm mb-3" />
                            <input type="file" name="welcome_video_file" accept="video/*" className="w-full p-2 bg-white border rounded-xl text-sm" />
                        </div>

                        <div className="mb-6">
                            <label className="block text-sm font-bold mb-2">Welcome Message</label>
                            <textarea name="welcome_message" rows={3} defaultValue={course.welcome_message || ''} className="rich-text w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </div>

                        <PortalCard className="p-6 mb-6 bg-surface-container">
                            <h3 className="font-bold text-lg mb-4 text-on-surface">Display Settings</h3>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_top_course" value="1" defaultChecked={course.is_top_course} className="w-5 h-5 text-primary rounded" />
                                <span className="text-sm font-bold text-on-surface">Show in &quot;Top Courses&quot; section on homepage</span>
                            </label>
                        </PortalCard>

                        <PortalCard className="p-6 mb-6 bg-surface-container">
                            <h3 className="font-bold text-lg mb-4 text-on-surface">SEO & Publishing</h3>
                            <input type="text" name="seo_title" defaultValue={course.seo_title || ''} placeholder="SEO Title" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none mb-4" />
                            <textarea name="seo_description" rows={2} defaultValue={course.seo_description || ''} placeholder="SEO Description" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none mb-4 resize-none" />
                            <input type="datetime-local" name="published_at" defaultValue={course.published_at || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none" />
                        </PortalCard>

                        <div className="flex flex-wrap items-center gap-4">
                            <button type="submit" name="status" value="published" className="btn-primary">Update Course</button>
                            <button type="submit" name="status" value="draft" className="px-6 py-3 bg-warning text-white font-bold rounded-xl inline-flex items-center gap-2">
                                <Icon name="save_as" className="text-sm" /> Save as Draft
                            </button>
                            <Link href={routes.index} className="px-6 py-3 bg-surface-container text-on-surface font-bold rounded-xl border border-outline">Cancel</Link>
                        </div>
                    </form>
                </PortalCard>

                <EmailTriggersPanel course={course} emailAutomations={emailAutomations} defaultAutomationId={defaultAutomationId} />
                <SyllabusUnitsPanel course={course} />
            </div>
        </DashboardLayout>
    );
}
