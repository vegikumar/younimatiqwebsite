import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

export default function Create({ categories = [], storeUrl = '/tutor/courses', backUrl = '/tutor/assigned-classes' }) {
    const { csrfToken, errors } = usePage().props;
    const fieldErrors = errors || {};

    return (
        <DashboardLayout title="Create Course">
            <Head title="Create Course" />
            <div className="staff-page max-w-3xl">
                <PageHeader
                    variant="staff"
                    title="Create New Course"
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
                    <form action={storeUrl} method="POST" encType="multipart/form-data" className="space-y-4 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Title</label>
                            <input type="text" name="title" required className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Description</label>
                            <textarea name="description" required rows={4} className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-1">Course Type</label>
                                <select name="type" required className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" defaultValue="1-on-1">
                                    <option value="1-on-1">1-on-1</option>
                                    <option value="camp">Group Camp</option>
                                    <option value="recording">Pre-recorded</option>
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label className="block text-sm font-bold mb-1">Price ($)</label>
                                <input type="number" step="0.01" name="price" className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                            </div>
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Categories</label>
                            <select name="category_ids[]" multiple className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2 h-32">
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="min-w-0">
                            <label className="block text-sm font-bold mb-1">Thumbnail Image</label>
                            <input type="file" name="thumbnail" accept="image/*" className="w-full min-w-0 border border-outline-variant/30 rounded-lg px-4 py-2" />
                        </div>
                        <div className="pt-4 text-right">
                            <button type="submit" className="btn-primary">
                                Create Course
                            </button>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
