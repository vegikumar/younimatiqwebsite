import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Testimonials({ testimonials = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [editOpen, setEditOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    return (
        <DashboardLayout title="Testimonials">
            <Head title="Community Testimonials" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Testimonials</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Manage the testimonials shown on the Community page.</p>
                </PortalCard>
                {flash?.success ? <div className="mb-6 p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2"><Icon name="add_circle" className="text-primary" /> Add Testimonial</h2>
                        <form method="POST" action="/admin/community/testimonials" className="space-y-4" encType="multipart/form-data">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="text" name="author_name" required placeholder="Author name" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            <textarea name="text" required rows={4} placeholder="What did they say?" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            <select name="rating" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{[5,4,3,2,1].map(n=><option key={n} value={n}>{n} Stars</option>)}</select>
                            <input type="file" name="avatar_file" accept="image/*" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                            <button type="submit" className="btn-primary w-full">Add Testimonial</button>
                        </form>
                    </PortalCard>
                    <PortalCard className="lg:col-span-2 p-5 md:p-8">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead><tr className="border-b-2 border-outline-variant"><th className="font-bold">Author</th><th className="font-bold">Review</th><th className="font-bold">Rating</th><th className="font-bold text-right">Actions</th></tr></thead>
                            <tbody>
                                {testimonials.length === 0 ? <tr><td colSpan={4} className="py-8 text-center text-on-surface-variant">No testimonials added yet.</td></tr> : testimonials.map((t) => (
                                    <tr key={t.id} className="border-b border-outline-variant hover:bg-surface-container-low">
                                        <td><div className="flex items-center gap-3">{t.avatarUrl ? <img src={t.avatarUrl} alt="" className="w-10 h-10 rounded-full object-cover" /> : <div className="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">{t.author_name.charAt(0)}</div>}<span className="font-bold">{t.author_name}</span></div></td>
                                        <td className="text-sm max-w-xs truncate">{t.text}</td>
                                        <td><div className="flex text-warning">{Array.from({ length: t.rating }).map((_, i) => <Icon key={i} name="star" className="text-[16px]" style={{ fontVariationSettings: "'FILL' 1" }} />)}</div></td>
                                        <td className="text-right"><button type="button" onClick={() => { setEditing({ ...t }); setEditOpen(true); }} className="p-2 hover:text-primary"><Icon name="edit" className="text-sm" /></button><button type="button" onClick={() => confirm('Delete?') && router.delete(`/admin/community/testimonials/${t.id}`, { preserveScroll: true })} className="p-2 hover:text-error"><Icon name="delete" className="text-sm" /></button></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </PortalCard>
                </div>
                {editOpen && editing ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-on-surface-variant/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 shadow-2xl">
                            <h3 className="font-headline-md text-xl font-bold mb-6">Edit Testimonial</h3>
                            <form action={`/admin/community/testimonials/${editing.id}`} method="POST" encType="multipart/form-data" className="space-y-4">
                                <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="_method" value="PUT" />
                                <input type="text" name="author_name" value={editing.author_name} onChange={(e)=>setEditing({...editing,author_name:e.target.value})} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <textarea name="text" value={editing.text} onChange={(e)=>setEditing({...editing,text:e.target.value})} required rows={4} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <select name="rating" value={editing.rating} onChange={(e)=>setEditing({...editing,rating:e.target.value})} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl">{[5,4,3,2,1].map(n=><option key={n} value={n}>{n} Stars</option>)}</select>
                                <input type="file" name="avatar_file" accept="image/*" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <div className="flex gap-3"><button type="button" onClick={() => setEditOpen(false)} className="btn-outline flex-1">Cancel</button><button type="submit" className="btn-primary flex-1">Save</button></div>
                            </form>
                        </div>
                    </div>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
