import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import { formatLocalTime } from '../../utils/formatLocalTime';

function Stars({ rating }) {
    return (
        <div className="flex gap-1 text-tertiary">
            {[0, 1, 2, 3, 4].map((i) => (
                <span key={i} className="material-symbols-outlined" style={{ fontVariationSettings: `'FILL' ${i < rating ? 1 : 0}` }}>
                    star
                </span>
            ))}
        </div>
    );
}

export default function Reviews({ reviews = [], unreviewedCourses = [], storeUrl = '/parent/reviews' }) {
    const { csrfToken, flash } = usePage().props;
    const [editingId, setEditingId] = useState(null);

    return (
        <DashboardLayout title="Reviews">
            <Head title="Parent Reviews" />
            <div className="mx-auto max-w-[1400px] space-y-8 p-5 md:p-8">
                <PageHeader
                    variant="parent"
                    eyebrow="Course ratings"
                    title="Course Reviews"
                    description="Star rating + comment on a full course. Class Feedback (how a live session went) is a separate page."
                    actions={
                        <Link href="/parent/feedback" className="inline-flex items-center gap-1.5 rounded-xl bg-white text-tertiary px-3.5 py-2 text-xs font-extrabold shadow-sm">
                            Class Feedback
                        </Link>
                    }
                />

                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold text-sm">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container text-on-error-container font-bold text-sm">{flash.error}</div> : null}

                {unreviewedCourses.length ? (
                    <div className="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                        <h2 className="font-headline-md font-bold mb-4">Write a Review</h2>
                        <form method="POST" action={storeUrl} className="space-y-4 max-w-xl">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2">Course</label>
                                <select name="course_id" required className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                                    <option value="">Select a course</option>
                                    {unreviewedCourses.map((course) => (
                                        <option key={course.id} value={course.id}>
                                            {course.title}
                                            {course.childNames?.length ? ` (${course.childNames.join(', ')})` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Rating</label>
                                <select name="rating" required defaultValue={5} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                                    {[5, 4, 3, 2, 1].map((i) => (
                                        <option key={i} value={i}>
                                            {i} Star{i > 1 ? 's' : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Comment</label>
                                <textarea name="comment" rows={3} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Share your experience as a parent..." />
                            </div>
                            <button type="submit" className="btn-primary btn-sm">
                                Submit Review
                            </button>
                        </form>
                    </div>
                ) : null}

                <div className="space-y-6">
                    {reviews.length ? (
                        reviews.map((review) => (
                            <div key={review.id} className="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                                {editingId !== review.id ? (
                                    <>
                                        <div className="flex justify-between items-start mb-4 gap-3">
                                            <div className="min-w-0">
                                                <h3 className="font-headline-md font-bold mb-1 break-words">{review.courseTitle}</h3>
                                                <p className="text-sm text-on-surface-variant">
                                                    Reviewed on {formatLocalTime(review.createdAt, 'date')}
                                                    {review.childNames?.length ? ` · ${review.childNames.join(', ')}` : ''}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-4 shrink-0">
                                                <Stars rating={review.rating} />
                                                <button type="button" onClick={() => setEditingId(review.id)} className="text-on-surface-variant hover:text-primary transition-colors" title="Edit Review">
                                                    <span className="material-symbols-outlined text-sm">edit</span>
                                                </button>
                                            </div>
                                        </div>
                                        {review.comment ? <p className="text-on-surface leading-relaxed whitespace-pre-wrap">{review.comment}</p> : null}
                                    </>
                                ) : (
                                    <>
                                        <h3 className="font-headline-md font-bold mb-4">Edit Review</h3>
                                        <form method="POST" action={storeUrl} className="space-y-4">
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <input type="hidden" name="course_id" value={review.courseId} />
                                            <div>
                                                <label className="block text-sm font-bold mb-2">Rating</label>
                                                <select name="rating" required defaultValue={review.rating} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                                                    {[5, 4, 3, 2, 1].map((i) => (
                                                        <option key={i} value={i}>
                                                            {i} Star{i > 1 ? 's' : ''}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-bold mb-2">Comment</label>
                                                <textarea name="comment" rows={3} defaultValue={review.comment ?? ''} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Share your experience as a parent..." />
                                            </div>
                                            <div className="flex gap-2">
                                                <button type="submit" className="btn-primary btn-sm">
                                                    Save Changes
                                                </button>
                                                <button type="button" onClick={() => setEditingId(null)} className="btn-outline btn-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </>
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
                            <span className="material-symbols-outlined text-4xl mb-2">star_rate</span>
                            <p className="font-bold mb-2">No reviews yet.</p>
                            <p className="text-sm">When your child is enrolled in a course, you can leave a review here.</p>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
