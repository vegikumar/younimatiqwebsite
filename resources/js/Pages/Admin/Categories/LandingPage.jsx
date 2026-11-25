import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function LandingPage({ category = {}, lpData: initial = {}, routes = {} }) {
    const { csrfToken, flash } = usePage().props;
    const [features, setFeatures] = useState(initial.features?.length ? initial.features : []);
    const [videos, setVideos] = useState(initial.videos?.length ? initial.videos : []);
    const [reviews, setReviews] = useState(initial.reviews?.length ? initial.reviews : []);
    const [curriculum, setCurriculum] = useState(initial.curriculum?.length ? initial.curriculum : []);
    const [faqs, setFaqs] = useState(initial.faqs?.length ? initial.faqs : []);

    const updateList = (setter, index, field, value) => {
        setter((list) => list.map((item, i) => (i === index ? { ...item, [field]: value } : item)));
    };

    return (
        <DashboardLayout title="Landing Page">
            <Head title={`Manage Landing Page - ${category.name}`} />
            <div className="staff-page">
                <div className="mb-6">
                    <Link href={routes.categoriesIndex} className="text-primary hover:underline">&larr; Back to Categories</Link>
                </div>

                <PortalCard className="p-5 md:p-8 lg:p-6 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Manage Landing Page: {category.name}</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Customize the content and layout for this category&apos;s public landing page.</p>
                </PortalCard>

                {flash?.success && <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>}

                <form method="POST" action={routes.update} encType="multipart/form-data" className="space-y-8">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="_method" value="PUT" />

                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-4">Hero Section</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-bold mb-2">Hero Title</label>
                                <input type="text" name="hero_title" defaultValue={initial.hero_title || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Theme Color</label>
                                <select name="theme_color" defaultValue={initial.theme_color || 'green'} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                    {['green', 'blue', 'purple', 'orange'].map((c) => <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>)}
                                </select>
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-sm font-bold mb-2">Hero Subtitle</label>
                                <textarea name="hero_subtitle" rows={2} defaultValue={initial.hero_subtitle || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" />
                            </div>
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-4">Section Headers & Text</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Courses Section</h3>
                                <input type="text" name="courses_title" defaultValue={initial.courses_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Videos (Maker Moments)</h3>
                                <input type="text" name="videos_badge" defaultValue={initial.videos_badge || ''} placeholder="Badge" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <input type="text" name="videos_title" defaultValue={initial.videos_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <input type="text" name="videos_subtitle" defaultValue={initial.videos_subtitle || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Features Section</h3>
                                <input type="text" name="features_badge" defaultValue={initial.features_badge || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <input type="text" name="features_title" defaultValue={initial.features_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Reviews Section</h3>
                                <input type="text" name="reviews_title" defaultValue={initial.reviews_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <input type="text" name="reviews_subtitle" defaultValue={initial.reviews_subtitle || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Curriculum Section</h3>
                                <input type="text" name="curriculum_badge" defaultValue={initial.curriculum_badge || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <input type="text" name="curriculum_title" defaultValue={initial.curriculum_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <textarea name="curriculum_subtitle" rows={2} defaultValue={initial.curriculum_subtitle || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                            <div className="space-y-2 p-4 border border-outline-variant rounded-xl">
                                <h3 className="font-bold text-primary">Bottom Banner Section</h3>
                                <input type="text" name="bottom_banner_title" defaultValue={initial.bottom_banner_title || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                <textarea name="bottom_banner_subtitle" rows={2} defaultValue={initial.bottom_banner_subtitle || ''} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                            </div>
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-headline-md font-bold">Features (Why Choose Younimatiq)</h2>
                            <button type="button" onClick={() => setFeatures((f) => [...f, { icon: '✨', title: '', description: '' }])} className="btn-secondary text-sm px-3 py-1">+ Add Feature</button>
                        </div>
                        <div className="space-y-4">
                            {features.map((feature, index) => (
                                <div key={index} className="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                                    <div className="w-16">
                                        <label className="block text-xs font-bold mb-1">Emoji</label>
                                        <input type="text" name={`features[${index}][icon]`} value={feature.icon || ''} onChange={(e) => updateList(setFeatures, index, 'icon', e.target.value)} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <input type="text" name={`features[${index}][title]`} value={feature.title || ''} onChange={(e) => updateList(setFeatures, index, 'title', e.target.value)} placeholder="Title" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                        <textarea name={`features[${index}][description]`} value={feature.description || ''} onChange={(e) => updateList(setFeatures, index, 'description', e.target.value)} rows={2} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    </div>
                                    <button type="button" onClick={() => setFeatures((f) => f.filter((_, i) => i !== index))} className="text-error absolute top-4 right-4"><Icon name="delete" /></button>
                                </div>
                            ))}
                            {!features.length && <p className="text-on-surface-variant italic text-sm">No features added.</p>}
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-headline-md font-bold">Maker Moments (Videos)</h2>
                            <button type="button" onClick={() => setVideos((v) => [...v, { url: '', title: '' }])} className="btn-secondary text-sm px-3 py-1">+ Add Video</button>
                        </div>
                        <div className="space-y-4">
                            {videos.map((video, index) => (
                                <div key={index} className="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                                    <div className="flex-1 space-y-2">
                                        <input type="file" name={`videos[${index}][file]`} accept="video/*,image/*" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                        <input type="hidden" name={`videos[${index}][url]`} value={video.url || ''} />
                                        {video.url ? <p className="text-xs text-on-tertiary-container">Currently: {video.url}</p> : null}
                                        <input type="text" name={`videos[${index}][title]`} value={video.title || ''} onChange={(e) => updateList(setVideos, index, 'title', e.target.value)} placeholder="Title/Caption" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    </div>
                                    <button type="button" onClick={() => setVideos((v) => v.filter((_, i) => i !== index))} className="text-error absolute top-4 right-4"><Icon name="delete" /></button>
                                </div>
                            ))}
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-headline-md font-bold">Parents Love Younimatiq (Reviews)</h2>
                            <button type="button" onClick={() => setReviews((r) => [...r, { text: '', name: '', initial: '', location: '' }])} className="btn-secondary text-sm px-3 py-1">+ Add Review</button>
                        </div>
                        <div className="space-y-4">
                            {reviews.map((review, index) => (
                                <div key={index} className="border border-outline-variant p-4 rounded-xl relative space-y-3">
                                    <textarea name={`reviews[${index}][text]`} value={review.text || ''} onChange={(e) => updateList(setReviews, index, 'text', e.target.value)} rows={2} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    <div className="grid grid-cols-4 gap-4">
                                        <input type="text" name={`reviews[${index}][name]`} value={review.name || ''} onChange={(e) => updateList(setReviews, index, 'name', e.target.value)} placeholder="Parent Name" className="col-span-2 w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                        <input type="text" name={`reviews[${index}][initial]`} value={review.initial || ''} onChange={(e) => updateList(setReviews, index, 'initial', e.target.value)} maxLength={1} placeholder="Initial" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                        <input type="text" name={`reviews[${index}][location]`} value={review.location || ''} onChange={(e) => updateList(setReviews, index, 'location', e.target.value)} placeholder="Location" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    </div>
                                    <button type="button" onClick={() => setReviews((r) => r.filter((_, i) => i !== index))} className="text-error absolute top-4 right-4"><Icon name="delete" /></button>
                                </div>
                            ))}
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-headline-md font-bold">Curriculum Path (Steps)</h2>
                            <button type="button" onClick={() => setCurriculum((c) => [...c, { title: '', description: '' }])} className="btn-secondary text-sm px-3 py-1">+ Add Step</button>
                        </div>
                        <div className="space-y-4">
                            {curriculum.map((step, index) => (
                                <div key={index} className="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                                    <div className="flex-1 space-y-2">
                                        <input type="text" name={`curriculum[${index}][title]`} value={step.title || ''} onChange={(e) => updateList(setCurriculum, index, 'title', e.target.value)} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                        <textarea name={`curriculum[${index}][description]`} value={step.description || ''} onChange={(e) => updateList(setCurriculum, index, 'description', e.target.value)} rows={2} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    </div>
                                    <button type="button" onClick={() => setCurriculum((c) => c.filter((_, i) => i !== index))} className="text-error absolute top-4 right-4"><Icon name="delete" /></button>
                                </div>
                            ))}
                        </div>
                    </PortalCard>

                    <PortalCard className="p-5 md:p-8">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-headline-md font-bold">FAQs</h2>
                            <button type="button" onClick={() => setFaqs((f) => [...f, { question: '', answer: '' }])} className="btn-secondary text-sm px-3 py-1">+ Add FAQ</button>
                        </div>
                        <input type="text" name="faq_title" defaultValue={initial.faq_title || ''} placeholder="FAQ Section Title" className="w-full max-w-md p-2 bg-surface-container-low border border-outline-variant rounded-lg mb-4" />
                        <div className="space-y-4">
                            {faqs.map((faq, index) => (
                                <div key={index} className="border border-outline-variant p-4 rounded-xl relative space-y-3">
                                    <input type="text" name={`faqs[${index}][question]`} value={faq.question || ''} onChange={(e) => updateList(setFaqs, index, 'question', e.target.value)} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    <textarea name={`faqs[${index}][answer]`} value={faq.answer || ''} onChange={(e) => updateList(setFaqs, index, 'answer', e.target.value)} rows={3} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" />
                                    <button type="button" onClick={() => setFaqs((f) => f.filter((_, i) => i !== index))} className="text-error absolute top-4 right-4"><Icon name="delete" /></button>
                                </div>
                            ))}
                        </div>
                    </PortalCard>

                    <div className="flex justify-end pb-12">
                        <button type="submit" className="btn-primary px-8 py-3 text-lg">Save Landing Page</button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
