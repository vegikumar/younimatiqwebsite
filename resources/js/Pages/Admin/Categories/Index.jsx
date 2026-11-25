import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ categories = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [editCat, setEditCat] = useState(null);

    const toggleActive = (id) => router.patch(`/admin/categories/${id}/toggle-active`, {}, { preserveScroll: true });

    return (
        <DashboardLayout title="Categories">
            <Head title="Course Categories" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Course Categories</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Organize courses into browsable categories.</p>
                </PortalCard>
                {flash?.success ? <div className="mb-6 p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2"><Icon name="add_circle" className="text-primary" /> Add Category</h2>
                        <form method="POST" action="/admin/categories" className="space-y-4" encType="multipart/form-data">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label className="block text-sm font-bold mb-2">Name</label><input type="text" name="name" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                                <div><label className="block text-sm font-bold mb-2">Slug (optional)</label><input type="text" name="slug" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                            </div>
                            <textarea name="description" rows={3} placeholder="Description" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="file" name="image" accept="image/*" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <input type="number" name="sort_order" defaultValue={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                            </div>
                            <input type="text" name="seo_title" placeholder="SEO Meta Title" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <textarea name="seo_description" rows={2} placeholder="SEO Meta Description" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <button type="submit" className="btn-primary btn-block">Create Category</button>
                        </form>
                    </PortalCard>
                    <PortalCard className="lg:col-span-2 p-5 md:p-8">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead><tr className="border-b-2 border-outline-variant"><th className="font-bold">Name</th><th className="font-bold">Slug</th><th className="font-bold">Order</th><th className="font-bold">Status</th><th className="font-bold text-right">Actions</th></tr></thead>
                            <tbody>
                                {categories.length === 0 ? <tr><td colSpan={5} className="py-8 text-center text-on-surface-variant">No categories yet.</td></tr> : categories.map((cat) => (
                                    <tr key={cat.id} className="border-b border-outline-variant hover:bg-surface-container-low">
                                        <td><div className="flex items-center gap-3">{cat.imageUrl ? <img src={cat.imageUrl} alt="" className="w-8 h-8 rounded object-cover" /> : <div className="w-8 h-8 rounded bg-surface-container flex items-center justify-center text-xs">Img</div>}<span className="font-medium">{cat.name}</span></div></td>
                                        <td className="text-on-surface-variant text-sm">{cat.slug}</td>
                                        <td className="text-on-surface-variant">{cat.sort_order}</td>
                                        <td><label className="relative inline-flex items-center cursor-pointer gap-3"><input type="checkbox" className="sr-only peer" checked={!!cat.is_active} onChange={() => toggleActive(cat.id)} /><div className="w-11 h-6 bg-surface-container-high rounded-full peer-checked:bg-success relative after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 peer-checked:after:translate-x-full transition-all" /><span className="text-xs font-bold">{cat.is_active ? 'Active' : 'Inactive'}</span></label></td>
                                        <td className="text-right"><div className="inline-flex gap-2"><Link href={cat.landingPageUrl} className="p-2 hover:text-secondary"><Icon name="web" className="text-sm" /></Link><button type="button" onClick={() => setEditCat(cat)} className="p-2 hover:text-primary"><Icon name="edit" className="text-sm" /></button><button type="button" onClick={() => confirm('Delete?') && router.delete(`/admin/categories/${cat.id}`, { preserveScroll: true })} className="p-2 hover:text-error"><Icon name="delete" className="text-sm" /></button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </PortalCard>
                </div>
                {editCat ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm">
                        <div className="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative">
                            <button type="button" onClick={() => setEditCat(null)} className="absolute top-4 right-4 p-2 rounded-full hover:bg-surface-container-low"><Icon name="close" className="text-sm" /></button>
                            <h3 className="font-bold text-xl mb-4">Edit Category</h3>
                            <form method="POST" action={`/admin/categories/${editCat.id}`} className="space-y-4" encType="multipart/form-data">
                                <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="_method" value="PUT" />
                                <input type="text" name="name" defaultValue={editCat.name} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <input type="text" name="slug" defaultValue={editCat.slug || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <textarea name="description" defaultValue={editCat.description || ''} rows={3} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <input type="file" name="image" accept="image/*" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <input type="number" name="sort_order" defaultValue={editCat.sort_order} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <input type="text" name="seo_title" defaultValue={editCat.seo_metadata?.meta_title || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <textarea name="seo_description" defaultValue={editCat.seo_metadata?.meta_description || ''} rows={2} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)]" />
                                <input type="hidden" name="is_active" value="0" />
                                <div className="staff-form-actions flex-col items-stretch sm:flex-row sm:items-center">
                                    <label className="staff-check">
                                        <input type="checkbox" name="is_active" value="1" defaultChecked={!!editCat.is_active} />
                                        <span>Active on website</span>
                                    </label>
                                    <button type="submit" className="btn-primary w-full sm:w-auto">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
