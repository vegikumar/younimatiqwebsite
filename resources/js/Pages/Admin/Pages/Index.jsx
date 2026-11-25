import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function Index({ pages = [] }) {
    const { flash } = usePage().props;

    const destroy = (id) => {
        if (!confirm('Delete this page?')) return;
        router.delete(`/admin/pages/${id}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Pages">
            <Head title="Pages" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden flex justify-between items-center">
                    <div>
                        <h1 className="font-headline-xl text-headline-xl text-on-surface mb-2">Static Pages</h1>
                        <p className="font-body-lg text-body-lg text-on-surface-variant">Manage custom content pages like Privacy Policy and Terms.</p>
                    </div>
                    <Link href="/admin/pages/create" className="btn-primary btn-sm flex items-center gap-2">
                        <Icon name="add" /> New Page
                    </Link>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>
                )}

                <PortalCard className="p-5 md:p-8">
                    <div className="overflow-x-auto">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b-2 border-outline-variant">
                                    <th className="font-bold">Title</th>
                                    <th className="font-bold">Slug</th>
                                    <th className="font-bold">Published</th>
                                    <th className="font-bold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pages.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-8 text-center text-on-surface-variant">
                                            No pages created yet.
                                        </td>
                                    </tr>
                                ) : (
                                    pages.map((page) => (
                                        <tr key={page.id} className="border-b border-outline-variant hover:bg-surface-container-low">
                                            <td className="font-medium">{page.title}</td>
                                            <td className="text-on-surface-variant text-sm">
                                                <a href={`/page/${page.slug}`} target="_blank" rel="noreferrer" className="hover:text-primary">
                                                    {page.slug}
                                                </a>
                                            </td>
                                            <td>
                                                <span
                                                    className={`px-3 py-1 rounded-full text-xs font-bold ${
                                                        page.is_published ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'
                                                    }`}
                                                >
                                                    {page.is_published ? 'Published' : 'Draft'}
                                                </span>
                                            </td>
                                            <td className="text-right">
                                                <Link href={`/admin/pages/${page.id}/edit`} className="p-2 text-on-surface-variant hover:text-primary inline-block">
                                                    <Icon name="edit" className="text-sm" />
                                                </Link>
                                                <button type="button" onClick={() => destroy(page.id)} className="p-2 text-on-surface-variant hover:text-error">
                                                    <Icon name="delete" className="text-sm" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
