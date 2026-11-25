import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ ageGroups = [] }) {
    const { flash, csrfToken } = usePage().props;
    const [editGroup, setEditGroup] = useState(null);

    const destroy = (id) => {
        if (!confirm('Delete this age group?')) return;
        router.delete(`/admin/age-groups/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Grade Bands">
            <Head title="Grade Bands" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Catalog"
                    title="Grade bands"
                    description="Manage age bands shown in the header dropdown and course filters."
                />

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
                    <PortalCard className="p-5 md:p-8">
                        <h2 className="font-headline-md font-bold mb-6">Add Grade Band</h2>
                        <form method="POST" action="/admin/age-groups" className="space-y-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div>
                                <label className="block text-sm font-bold mb-2">Grade / Name</label>
                                <input type="text" name="name" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. Grade 5" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Age Range</label>
                                <input type="text" name="age_range" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. 10-11" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Slug (optional)</label>
                                <input type="text" name="slug" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="auto from age range" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Sort Order</label>
                                <input type="number" name="sort_order" defaultValue={0} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Description</label>
                                <textarea name="description" rows={2} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <button type="submit" className="btn-primary btn-block">Create Grade Band</button>
                        </form>
                    </PortalCard>

                    <PortalCard className="lg:col-span-2 p-5 md:p-8">
                        <div className="overflow-x-auto">
                            <table className="staff-table w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b-2 border-outline-variant">
                                        <th className="font-bold">Grade</th>
                                        <th className="font-bold">Slug</th>
                                        <th className="font-bold">Order</th>
                                        <th className="font-bold">Status</th>
                                        <th className="font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {ageGroups.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="py-8 text-center text-on-surface-variant">
                                                No age groups yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        ageGroups.map((group) => (
                                            <tr key={group.id} className="border-b border-outline-variant">
                                                <td className="font-medium">
                                                    {group.name}
                                                    {group.age_range ? <span className="text-xs text-on-surface-variant"> ({group.age_range})</span> : null}
                                                </td>
                                                <td className="text-sm text-on-surface-variant">{group.slug}</td>
                                                <td>{group.sort_order}</td>
                                                <td>
                                                    <span
                                                        className={`px-3 py-1 rounded-full text-xs font-bold ${group.is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`}
                                                    >
                                                        {group.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="text-right">
                                                    <div className="inline-flex items-center justify-end gap-2">
                                                        <button type="button" onClick={() => setEditGroup(group)} className="p-2 text-on-surface-variant hover:text-primary" title="Edit">
                                                            <Icon name="edit" className="text-sm" />
                                                        </button>
                                                        <form method="POST" action={`/admin/age-groups/${group.id}`} className="m-0 p-0 flex items-center">
                                                            <input type="hidden" name="_token" value={csrfToken} />
                                                            <input type="hidden" name="_method" value="PUT" />
                                                            <input type="hidden" name="name" value={group.name} />
                                                            <input type="hidden" name="age_range" value={group.age_range || ''} />
                                                            <input type="hidden" name="sort_order" value={group.sort_order} />
                                                            <input type="hidden" name="slug" value={group.slug} />
                                                            <input type="hidden" name="is_active" value="0" />
                                                            <label className="relative inline-flex items-center cursor-pointer">
                                                                <input
                                                                    type="checkbox"
                                                                    name="is_active"
                                                                    value="1"
                                                                    className="sr-only peer"
                                                                    defaultChecked={group.is_active}
                                                                    onChange={(e) => e.currentTarget.form.requestSubmit()}
                                                                />
                                                                <div className="w-11 h-6 bg-surface-container rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full" />
                                                            </label>
                                                        </form>
                                                        <button type="button" onClick={() => destroy(group.id)} className="p-2 text-on-surface-variant hover:text-error" title="Delete">
                                                            <Icon name="delete" className="text-sm" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </PortalCard>
                </div>
            </div>

            {editGroup ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm text-left">
                    <div className="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative">
                        <button type="button" onClick={() => setEditGroup(null)} className="absolute top-4 right-4 p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full">
                            <Icon name="close" className="text-sm" />
                        </button>
                        <h3 className="font-bold text-xl mb-4">Edit Grade Band</h3>
                        <form method="POST" action={`/admin/age-groups/${editGroup.id}`} className="space-y-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="PUT" />
                            <div>
                                <label className="block text-sm font-bold mb-2">Grade / Name</label>
                                <input type="text" name="name" defaultValue={editGroup.name} required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Age Range</label>
                                <input type="text" name="age_range" defaultValue={editGroup.age_range || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Slug (optional)</label>
                                <input type="text" name="slug" defaultValue={editGroup.slug || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Sort Order</label>
                                <input type="number" name="sort_order" defaultValue={editGroup.sort_order} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-2">Description</label>
                                <textarea name="description" rows={2} defaultValue={editGroup.description || ''} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <button type="submit" className="btn-primary w-full">Save Changes</button>
                        </form>
                    </div>
                </div>
            ) : null}
        </DashboardLayout>
    );
}
