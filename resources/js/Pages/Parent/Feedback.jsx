import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import EmptyState from '../../Components/Portal/EmptyState';
import DashboardSection from '../../Components/Portal/DashboardSection';
import Icon from '../../Components/Icon';

export default function Feedback({
    children = [],
    selectedChildId = null,
    child = null,
    storeUrl = '/parent/feedback',
    reviewsUrl = '/parent/reviews',
}) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Class Feedback">
            <Head title="Class Feedback" />
            <div className="mx-auto max-w-[1400px] space-y-6 p-5 md:p-8">
                <PageHeader
                    variant="parent"
                    eyebrow="After class"
                    title="Class Feedback"
                    description="Tell us how a live class went — different from course star Reviews."
                    actions={
                        <Link href={reviewsUrl} className="inline-flex items-center gap-1.5 rounded-xl bg-white text-tertiary px-3.5 py-2 text-xs font-extrabold shadow-sm">
                            <Icon name="star" className="text-base" /> Course Reviews
                        </Link>
                    }
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold text-sm">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container text-on-error-container font-bold text-sm">{flash.error}</div> : null}

                <div className="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant">
                    <p><span className="font-bold text-on-surface">Feedback</span> = how today&apos;s class felt (tutor, experience, recommend).</p>
                    <p className="mt-1"><span className="font-bold text-on-surface">Reviews</span> = star rating + comment on a full course.</p>
                </div>

                {children.length === 0 ? (
                    <EmptyState icon="family_restroom" title="No children linked" description="Link a student profile first to leave class feedback." />
                ) : (
                    <>
                        <div className="flex flex-wrap gap-2">
                            {children.map((c) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => router.get('/parent/feedback', { child: c.id }, { preserveState: true })}
                                    className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-sm font-bold transition-all border ${
                                        selectedChildId === c.id
                                            ? 'bg-primary text-white border-primary shadow-md shadow-primary/25'
                                            : 'bg-white text-on-surface-variant border-outline-variant/50 hover:border-primary/40'
                                    }`}
                                >
                                    {c.name}
                                </button>
                            ))}
                        </div>

                        {child ? (
                            <div className="grid lg:grid-cols-2 gap-4">
                                <DashboardSection title={`Feedback for ${child.name}`} icon="forum">
                                    <form method="POST" action={storeUrl} className="space-y-3">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="student_id" value={child.id} />
                                        <div>
                                            <label className="block text-xs font-bold mb-1 text-on-surface-variant">Related class (optional)</label>
                                            <select name="booking_id" className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm">
                                                <option value="">General class feedback</option>
                                                {(child.recentClasses || []).map((c) => (
                                                    <option key={c.id} value={c.id}>{c.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid sm:grid-cols-2 gap-3">
                                            <div>
                                                <label className="block text-xs font-bold mb-1 text-on-surface-variant">Overall rating *</label>
                                                <select name="rating" required className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm">
                                                    <option value="">Select</option>
                                                    <option value="5">5 · Excellent</option>
                                                    <option value="4">4 · Good</option>
                                                    <option value="3">3 · Okay</option>
                                                    <option value="2">2 · Needs work</option>
                                                    <option value="1">1 · Poor</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-bold mb-1 text-on-surface-variant">Class experience *</label>
                                                <select name="class_experience" required className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm">
                                                    <option value="">Select</option>
                                                    <option value="Loved it">Loved it</option>
                                                    <option value="Good">Good</option>
                                                    <option value="Average">Average</option>
                                                    <option value="Disappointing">Disappointing</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-bold mb-1 text-on-surface-variant">Tutor communication *</label>
                                                <select name="tutor_communication" required className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm">
                                                    <option value="">Select</option>
                                                    <option value="Excellent">Excellent</option>
                                                    <option value="Clear">Clear</option>
                                                    <option value="Could improve">Could improve</option>
                                                    <option value="Unclear">Unclear</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-bold mb-1 text-on-surface-variant">Would recommend? *</label>
                                                <select name="would_recommend" required className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm">
                                                    <option value="">Select</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="Maybe">Maybe</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold mb-1 text-on-surface-variant">Anything else?</label>
                                            <textarea name="comments" rows={3} className="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm" placeholder="What went well, what we can improve…" />
                                        </div>
                                        <button type="submit" className="btn-primary text-sm px-4 py-2">Send class feedback</button>
                                    </form>
                                </DashboardSection>

                                <DashboardSection title="Your submitted feedback" icon="history">
                                    {(child.feedback || []).length === 0 ? (
                                        <EmptyState icon="forum" title="No class feedback yet" description="Submit feedback after a live class." />
                                    ) : (
                                        <div className="space-y-2">
                                            {child.feedback.map((f) => (
                                                <div key={f.id} className="p-3 rounded-xl bg-surface-container-low text-sm space-y-1">
                                                    <div className="flex justify-between gap-2">
                                                        <span className="font-bold">{f.rating}/5 · {f.class_experience}</span>
                                                        <span className="text-xs text-on-surface-variant">{f.created_at_label}</span>
                                                    </div>
                                                    {f.class_title ? <p className="text-xs text-on-surface-variant">{f.class_title}{f.tutor_name ? ` · ${f.tutor_name}` : ''}</p> : null}
                                                    <p className="text-xs">Tutor communication: {f.tutor_communication} · Recommend: {f.would_recommend}</p>
                                                    {f.comments ? <p className="text-on-surface-variant text-xs">{f.comments}</p> : null}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </DashboardSection>
                            </div>
                        ) : null}
                    </>
                )}
            </div>
        </DashboardLayout>
    );
}
