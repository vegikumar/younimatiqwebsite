import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function loadCKEditor() {
    if (window.ClassicEditor) return Promise.resolve(window.ClassicEditor);
    return new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-ym-ckeditor]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.ClassicEditor));
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js';
        script.dataset.ymCkeditor = '1';
        script.onload = () => resolve(window.ClassicEditor);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

export default function Form({ blog = {}, isEdit = false }) {
    const { csrfToken, errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();

    const seo = blog.seo_metadata ?? {};
    const metaTitle = seo.meta_title ?? seo.title ?? '';
    const metaDescription = seo.meta_description ?? seo.description ?? '';

    useEffect(() => {
        let editor;
        let cancelled = false;
        loadCKEditor()
            .then((ClassicEditor) => {
                if (cancelled) return;
                const el = document.getElementById('blog-content');
                if (!el) return;
                return ClassicEditor.create(el).then((instance) => {
                    editor = instance;
                });
            })
            .catch(() => {});
        return () => {
            cancelled = true;
            editor?.destroy?.();
        };
    }, []);

    return (
        <DashboardLayout title={isEdit ? 'Edit Blog Post' : 'Create Blog Post'}>
            <Head title={isEdit ? 'Edit Blog Post' : 'Create Blog Post'}>
                <style>{`
                    .ck-editor__editable_inline {
                        min-height: 320px;
                        background-color: var(--color-surface-container-low) !important;
                        border-color: var(--color-outline-variant) !important;
                        border-radius: 0 0 12px 12px !important;
                        color: var(--color-on-surface) !important;
                    }
                    .ck.ck-toolbar {
                        background: var(--color-surface) !important;
                        border-color: var(--color-outline-variant) !important;
                        border-radius: 12px 12px 0 0 !important;
                    }
                `}</style>
            </Head>
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                            <Icon name="article" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-headline-md text-on-surface">{isEdit ? 'Edit Blog Post' : 'Create Blog Post'}</h2>
                            <p className="font-body-md text-on-surface-variant">Write and publish content for the public blog.</p>
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

                    <form
                        method="POST"
                        action={isEdit ? `/admin/blogs/${blog.id}` : '/admin/blogs'}
                        encType="multipart/form-data"
                        className="space-y-6"
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        {isEdit ? <input type="hidden" name="_method" value="PUT" /> : null}

                        <div>
                            <label className="block font-bold mb-2">Title</label>
                            <input
                                type="text"
                                name="title"
                                defaultValue={blog.title ?? ''}
                                required
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold mb-2">Slug (optional)</label>
                            <input
                                type="text"
                                name="slug"
                                defaultValue={blog.slug ?? ''}
                                placeholder="auto-generated from title if blank"
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold mb-2">Excerpt</label>
                            <textarea
                                name="excerpt"
                                rows={2}
                                defaultValue={blog.excerpt ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold mb-2">Content</label>
                            <textarea
                                id="blog-content"
                                name="content"
                                rows={16}
                                defaultValue={blog.content ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none rich-text"
                            />
                            <p className="text-sm text-on-surface-variant mt-2">Use the editor toolbar to format your post.</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block font-bold mb-2">Featured Image Upload</label>
                                {blog.featured_image ? (
                                    <div className="mb-2">
                                        <img src={blog.featured_image} alt="Current" className="w-24 h-24 object-cover rounded-xl border border-outline-variant" />
                                    </div>
                                ) : null}
                                <input
                                    type="file"
                                    name="featured_image"
                                    accept="image/*"
                                    className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label className="block font-bold mb-2">Author</label>
                                <input
                                    type="text"
                                    name="author"
                                    defaultValue={blog.author ?? 'Younimatiq'}
                                    className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block font-bold mb-2">Meta Title</label>
                                <input
                                    type="text"
                                    name="meta_title"
                                    defaultValue={metaTitle}
                                    className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label className="block font-bold mb-2">Meta Description</label>
                                <textarea
                                    name="meta_description"
                                    rows={2}
                                    defaultValue={metaDescription}
                                    className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block font-bold mb-2">Status</label>
                                <select name="status" defaultValue={blog.status ?? 'draft'} className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            <div>
                                <label className="block font-bold mb-2">Publish Date</label>
                                <input
                                    type="date"
                                    name="published_at"
                                    defaultValue={blog.published_at_date ?? ''}
                                    className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div className="flex gap-4 pt-4 border-t border-outline-variant">
                            <button type="submit" className="btn-primary">{isEdit ? 'Update Post' : 'Create Post'}</button>
                            <Link href="/admin/blogs" className="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
