import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Form({ page = {}, isEdit = false }) {
    const { csrfToken, errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();
    const slugPreview = page.slug || 'your-slug';

    return (
        <DashboardLayout title={isEdit ? 'Edit Page' : 'Create Page'}>
            <Head title={isEdit ? 'Edit Page' : 'Create Page'} />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                            <Icon name="web" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-headline-md text-on-surface">{isEdit ? 'Edit Page' : 'Create Page'}</h2>
                            <p className="font-body-md text-on-surface-variant">Build static content pages for the public site.</p>
                        </div>
                    </div>

                    {errorList.length > 0 && (
                        <div className="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                            <ul className="list-disc list-inside">
                                {errorList.map((error, i) => (
                                    <li key={i}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <form method="POST" action={isEdit ? `/admin/pages/${page.id}` : '/admin/pages'} className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        {isEdit ? <input type="hidden" name="_method" value="PUT" /> : null}

                        <div>
                            <label className="block font-bold mb-2">Title</label>
                            <input
                                type="text"
                                name="title"
                                defaultValue={page.title ?? ''}
                                required
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold mb-2">Slug</label>
                            <input
                                type="text"
                                name="slug"
                                defaultValue={page.slug ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                placeholder="auto-generated-from-title"
                            />
                            <p className="text-xs text-on-surface-variant mt-1">
                                URL: /page/<span className="font-mono">{slugPreview}</span>
                            </p>
                        </div>

                        <div>
                            <label className="block font-bold mb-2">Content</label>
                            <textarea
                                name="content"
                                rows={14}
                                required
                                defaultValue={page.content ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none font-mono text-sm"
                            />
                        </div>

                        <label className="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_published" value="1" defaultChecked={!!page.is_published} className="w-5 h-5 rounded text-primary focus:ring-primary" />
                            <span className="font-bold">Publish this page</span>
                        </label>

                        <div className="flex gap-4 pt-4 border-t border-outline-variant">
                            <button type="submit" className="btn-primary">{isEdit ? 'Update Page' : 'Create Page'}</button>
                            <Link href="/admin/pages" className="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
