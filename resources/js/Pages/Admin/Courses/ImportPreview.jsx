import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import Icon from '../../../Components/Icon';

export default function ImportPreview({ analysis = {}, token, options = {}, routes = {} }) {
    const { csrfToken } = usePage().props;
    const summary = analysis.summary || { new: 0, updated: 0, classes: 0 };
    const warnings = analysis.warnings || [];
    const courses = analysis.courses || [];

    return (
        <DashboardLayout title="Import Preview">
            <Head title="Import Preview" />
            <div className="staff-page">
                <PortalCard className="p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
                    <div className="flex items-center gap-3 mb-2">
                        <Icon name="fact_check" className="text-primary text-3xl" />
                        <h1 className="font-headline-xl text-headline-xl text-on-surface">Review Import</h1>
                    </div>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">
                        Nothing has been saved yet. Check the courses below, then confirm to import.
                    </p>
                    <div className="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none" />
                </PortalCard>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    {[
                        { icon: 'add_circle', value: summary.new, label: 'New Courses', cls: 'bg-tertiary-fixed text-on-tertiary-container' },
                        { icon: 'sync', value: summary.updated, label: 'Updates', cls: 'bg-secondary-container text-on-secondary-container' },
                        { icon: 'menu_book', value: summary.classes, label: 'Classes', cls: 'bg-primary-fixed text-primary' },
                        { icon: 'warning', value: warnings.length, label: 'Warnings', cls: warnings.length ? 'bg-warning-container text-warning' : 'bg-surface-container text-on-surface-variant' },
                    ].map((card) => (
                        <div key={card.label} className="staff-card rounded-xl border border-outline-variant/25 bg-white shadow-[0_14px_36px_-30px_rgba(20,12,40,0.5)] p-5 flex items-center gap-4">
                            <div className={`w-12 h-12 rounded-2xl flex items-center justify-center ${card.cls}`}>
                                <Icon name={card.icon} />
                            </div>
                            <div>
                                <div className="text-2xl font-bold text-on-surface">{card.value}</div>
                                <div className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{card.label}</div>
                            </div>
                        </div>
                    ))}
                </div>

                {warnings.length > 0 && (
                    <div className="mb-6 p-4 bg-warning-container border border-warning-container text-warning rounded-2xl">
                        <div className="flex flex-wrap items-center justify-between gap-3 mb-2">
                            <div className="flex items-center gap-3">
                                <Icon name="warning" className="text-warning" />
                                <div className="font-bold text-sm">These are non-blocking warnings. The import will still proceed.</div>
                            </div>
                            <a href={routes.warningsCsv} className="text-warning font-bold text-xs hover:underline inline-flex items-center gap-1">
                                <Icon name="download" className="text-xs" /> Download warnings CSV
                            </a>
                        </div>
                        <ul className="list-disc pl-10 space-y-1 text-sm max-h-56 overflow-y-auto">
                            {warnings.map((w, i) => (
                                <li key={i}>{w}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <PortalCard className="p-5 md:p-8 mb-6">
                    <h2 className="text-lg font-bold text-on-surface mb-4">Courses to import</h2>
                    <div className="overflow-x-auto">
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b-2 border-outline-variant">
                                    {['Course', 'Action', 'Category', 'Age / Grades', 'Tutor', 'Classes', 'Price (USD)', 'Details', 'Status'].map((h) => (
                                        <th key={h} className={`py-3 px-4 font-bold text-on-surface text-sm ${h === 'Classes' || h === 'Price (USD)' ? 'text-right' : ''}`}>
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {courses.map((c, idx) => (
                                    <tr key={idx} className="border-b border-outline-variant">
                                        <td>
                                            <div className="font-medium text-on-surface">{c.title}</div>
                                            <div className="text-xs text-on-surface-variant font-mono">{c.slug}</div>
                                        </td>
                                        <td>
                                            {c.action === 'create' ? (
                                                <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-tertiary-fixed text-on-tertiary-container">New</span>
                                            ) : (
                                                <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">Update</span>
                                            )}
                                        </td>
                                        <td className="text-sm text-on-surface-variant">{c.category || '—'}</td>
                                        <td className="text-sm text-on-surface-variant">
                                            {c.age_group || '—'}
                                            {c.grade_range ? <span className="text-xs"> ({c.grade_range})</span> : null}
                                        </td>
                                        <td className="text-sm text-on-surface-variant">{c.tutor || '—'}</td>
                                        <td className="text-sm text-on-surface text-right">{c.classes}</td>
                                        <td className="text-sm text-on-surface text-right">${Number(c.price_usd).toFixed(2)}</td>
                                        <td>
                                            <div className="flex flex-wrap gap-1">
                                                {c.is_camp ? <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-fixed text-primary">CAMP</span> : null}
                                                {c.is_top_course ? <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-warning-container text-warning">TOP</span> : null}
                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container text-on-surface-variant">{c.outcomes} outcomes</span>
                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container text-on-surface-variant">{c.highlights} highlights</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{c.status}</span>
                                        </td>
                                    </tr>
                                ))}
                                {!courses.length ? (
                                    <tr>
                                        <td colSpan={9} className="py-6 px-4 text-center text-on-surface-variant">
                                            No courses found in this file.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </PortalCard>

                <PortalCard className="p-6 flex flex-wrap items-center justify-between gap-4">
                    <div className="text-sm text-on-surface-variant">
                        Default status: <strong className="text-on-surface">{options.default_status ? options.default_status.charAt(0).toUpperCase() + options.default_status.slice(1) : 'Draft'}</strong>
                        {' · '}
                        Update existing: <strong className="text-on-surface">{options.update_existing ? 'Yes' : 'No'}</strong>
                    </div>
                    <div className="flex items-center gap-3">
                        <a href={routes.index} className="px-5 py-2.5 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors text-sm">
                            Cancel
                        </a>
                        <form action={routes.bulkImport} method="POST">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="import_token" value={token} />
                            <button type="submit" className="btn-primary px-6 py-2.5 text-sm font-bold shadow-md inline-flex items-center gap-2">
                                <Icon name="check" className="text-base" /> Confirm &amp; Import
                            </button>
                        </form>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
