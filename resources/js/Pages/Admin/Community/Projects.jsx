import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Projects({ projects = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [editOpen, setEditOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const openEdit = (p) => { setEditing({ ...p }); setEditOpen(true); };
    const destroy = (id) => { if (confirm('Delete this project?')) router.delete(`/admin/community/projects/${id}`, { preserveScroll: true }); };

    return (
        <DashboardLayout title="Community Projects">
            <Head title="Featured Projects" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Featured Projects</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Manage the projects featured on the Community page.</p>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>
                {flash?.success ? <div className="mb-6 p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6 flex items-center gap-2"><Icon name="add_circle" className="text-primary" /> Add Project</h2>
                        <form method="POST" action="/admin/community/projects" className="space-y-4" encType="multipart/form-data">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div><label className="block text-sm font-bold mb-2">Project Title</label><input type="text" name="title" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. My Cool Game" /></div>
                            <div><label className="block text-sm font-bold mb-2">Author Name</label><input type="text" name="author_name" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                            <div><label className="block text-sm font-bold mb-2">Category Badge</label>
                                <select name="category" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                    <option value="">None</option>{['Block','Bedrock','HTML/CSS','Python'].map(c=><option key={c} value={c}>{c}</option>)}
                                </select></div>
                            <div><label className="block text-sm font-bold mb-2">Project URL (Optional)</label><input type="url" name="project_url" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><label className="block text-sm font-bold mb-2">Initial Views</label><input type="number" name="views_count" defaultValue={0} min={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                                <div><label className="block text-sm font-bold mb-2">Initial Likes</label><input type="number" name="likes_count" defaultValue={0} min={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                            </div>
                            <div><label className="block text-sm font-bold mb-2">Thumbnail Video</label><input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" required className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                            <button type="submit" className="btn-primary w-full">Add Project</button>
                        </form>
                    </PortalCard>
                    <PortalCard className="lg:col-span-2 p-5 md:p-8">
                        <div className="overflow-x-auto">
                            <table className="staff-table w-full text-left border-collapse">
                                <thead><tr className="border-b-2 border-outline-variant"><th className="font-bold">Thumbnail</th><th className="font-bold">Title & Author</th><th className="font-bold">Category</th><th className="font-bold">Stats</th><th className="font-bold text-right">Actions</th></tr></thead>
                                <tbody>
                                    {projects.length === 0 ? <tr><td colSpan={5} className="py-8 text-center text-on-surface-variant">No projects added yet.</td></tr> : projects.map((project) => (
                                        <tr key={project.id} className="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                            <td>{project.isImage ? <img src={project.mediaUrl} alt="" className="w-24 h-16 object-cover rounded-xl shadow-sm" /> : <video src={project.mediaUrl} autoPlay loop muted playsInline className="w-24 h-16 object-cover rounded-xl shadow-sm" />}</td>
                                            <td><div className="font-bold">{project.title}</div><div className="text-sm text-on-surface-variant">by {project.author_name}</div></td>
                                            <td className="text-sm">{project.category || '-'}</td>
                                            <td className="text-xs"><div className="flex items-center gap-1"><Icon name="visibility" className="text-[14px]" /> {project.views_count}</div><div className="flex items-center gap-1"><Icon name="favorite" className="text-[14px]" /> {project.likes_count}</div></td>
                                            <td className="text-right"><div className="flex items-center justify-end gap-2"><button type="button" onClick={() => openEdit(project)} className="p-2 text-on-surface-variant hover:text-primary"><Icon name="edit" className="text-sm" /></button><button type="button" onClick={() => destroy(project.id)} className="p-2 text-on-surface-variant hover:text-error"><Icon name="delete" className="text-sm" /></button></div></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </PortalCard>
                </div>
                {editOpen && editing ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-on-surface-variant/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto shadow-2xl">
                            <h3 className="font-headline-md text-xl font-bold mb-6">Edit Project</h3>
                            <form action={`/admin/community/projects/${editing.id}`} method="POST" encType="multipart/form-data" className="space-y-4">
                                <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="_method" value="PUT" />
                                <input type="text" name="title" value={editing.title} onChange={(e)=>setEditing({...editing,title:e.target.value})} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <input type="text" name="author_name" value={editing.author_name} onChange={(e)=>setEditing({...editing,author_name:e.target.value})} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <select name="category" value={editing.category || ''} onChange={(e)=>setEditing({...editing,category:e.target.value})} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl"><option value="">None</option>{['Block','Bedrock','HTML/CSS','Python'].map(c=><option key={c} value={c}>{c}</option>)}</select>
                                <input type="url" name="project_url" value={editing.project_url || ''} onChange={(e)=>setEditing({...editing,project_url:e.target.value})} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <div className="grid grid-cols-2 gap-4">
                                    <input type="number" name="views_count" value={editing.views_count} onChange={(e)=>setEditing({...editing,views_count:e.target.value})} min={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                    <input type="number" name="likes_count" value={editing.likes_count} onChange={(e)=>setEditing({...editing,likes_count:e.target.value})} min={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" />
                                </div>
                                <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl" />
                                <div className="flex items-center gap-3 mt-6 pt-4 border-t border-outline-variant"><button type="button" onClick={() => setEditOpen(false)} className="btn-outline flex-1">Cancel</button><button type="submit" className="btn-primary flex-1">Save Changes</button></div>
                            </form>
                        </div>
                    </div>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
