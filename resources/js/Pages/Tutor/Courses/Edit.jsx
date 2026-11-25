import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

export default function Edit({ course, categories = [], backUrl = '/tutor/assigned-classes' }) {
    const { csrfToken, errors } = usePage().props;
    const fieldErrors = errors || {};

    return (
        <DashboardLayout title="Edit Course">
            <Head title="Edit Course" />
            <div className="staff-page max-w-3xl">
                <PageHeader
                    variant="staff"
                    title={`Edit Course: ${course.title}`}
                    actions={
                        <Link href={backUrl} className="btn-secondary btn-sm">
                            Back
                        </Link>
                    }
                />

                {Object.keys(fieldErrors).length ? (
                    <div className="p-4 bg-error-container text-on-error-container rounded-xl">
                        <ul className="list-disc pl-5">
                            {Object.values(fieldErrors).flat().map((err, i) => (
                                <li key={i}>{err}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                <PortalCard title="Course details">
                    <form action={course.updateUrl} method="POST" encType="multipart/form-data" className="space-y-4 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PUT" />
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Title</label>
                            <input type="text" name="title" required defaultValue={course.title} className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Description</label>
                            <textarea name="description" required rows={4} defaultValue={course.description ?? ''} className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-1">Course Type</label>
                                <select name="type" required defaultValue={course.type} className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2">
                                    <option value="1-on-1">1-on-1</option>
                                    <option value="camp">Group Camp</option>
                                    <option value="recording">Pre-recorded</option>
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-1">Price ($)</label>
                                <input type="number" step="0.01" name="price" defaultValue={course.price} className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                            </div>
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Categories</label>
                            <select name="category_ids[]" multiple className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2 h-32">
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id} selected={course.categoryIds.includes(category.id)}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Thumbnail Image</label>
                            {course.thumbnailUrl ? (
                                <div className="mb-2">
                                    <img src={course.thumbnailUrl} alt="Thumbnail" className="w-32 h-auto rounded-lg" />
                                </div>
                            ) : null}
                            <input type="file" name="thumbnail" accept="image/*" className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="pt-4 text-right">
                            <button type="submit" className="btn-primary">
                                Update Course
                            </button>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
