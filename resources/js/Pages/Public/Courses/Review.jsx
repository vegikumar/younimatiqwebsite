import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

export default function Review({ course }) {
    const form = useForm({ rating: 5, comment: '' });

    return (
        <PublicLayout title={`Review ${course.title}`}>
            <Head title={`Review ${course.title}`} />
            <div className="max-w-lg mx-auto px-4 py-12">
                <h1 className="text-2xl font-bold mb-2">Review {course.title}</h1>
                <form
                    className="clay-card p-6 space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(`/courses/${course.slug}/review`);
                    }}
                >
                    <label className="block text-sm font-bold">
                        Rating
                        <select className="w-full mt-1 rounded-xl border px-3 py-2" value={form.data.rating} onChange={(e) => form.setData('rating', Number(e.target.value))}>
                            {[5, 4, 3, 2, 1].map((n) => (
                                <option key={n} value={n}>
                                    {n} stars
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-sm font-bold">
                        Comment
                        <textarea
                            rows={5}
                            className="w-full mt-1 rounded-xl border px-3 py-2"
                            value={form.data.comment}
                            onChange={(e) => form.setData('comment', e.target.value)}
                            required
                        />
                    </label>
                    <button type="submit" className="btn-enroll w-full" disabled={form.processing}>
                        Submit review
                    </button>
                </form>
            </div>
        </PublicLayout>
    );
}
