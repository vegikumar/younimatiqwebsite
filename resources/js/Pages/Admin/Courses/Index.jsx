import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import { formatLocalTime } from '../../../utils/formatLocalTime';

function CopyToast({ message, show }) {
    if (!show) return null;
    return (
        <div className="fixed bottom-7 left-1/2 -translate-x-1/2 z-[9999] bg-[#0f172a] text-white px-6 py-3 rounded-full text-sm font-semibold shadow-lg">
            {message}
        </div>
    );
}

export default function Index({ courses = [], filters = {}, importErrors = [], importWarnings = [], importOpenModal = false, routes = {} }) {
    const { flash, csrfToken } = usePage().props;
    const [toast, setToast] = useState('');
    const [bulkOpen, setBulkOpen] = useState(importOpenModal);
    const [customOpen, setCustomOpen] = useState(false);
    const [customCourseId, setCustomCourseId] = useState('');
    const [customUrl, setCustomUrl] = useState('');
    const [categories, setCategories] = useState([]);
    const [selectedCatIds, setSelectedCatIds] = useState([]);
    const [catLoading, setCatLoading] = useState(false);

    useEffect(() => {
        if (importOpenModal) setBulkOpen(true);
    }, [importOpenModal]);

    const loadCategories = useCallback(() => {
        setCatLoading(true);
        fetch(routes.categoriesJson, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((data) => {
                setCategories(data);
                setCatLoading(false);
            })
            .catch(() => setCatLoading(false));
    }, [routes.categoriesJson]);

    useEffect(() => {
        if (bulkOpen) loadCategories();
    }, [bulkOpen, loadCategories]);

    const copyReviewLink = (url) => {
        navigator.clipboard.writeText(url).then(() => {
            setToast('✅ Review link copied to clipboard!');
            setTimeout(() => setToast(''), 2800);
        });
    };

    const toggleChip = (id) => {
        setSelectedCatIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const openCustom = (courseId) => {
        setCustomCourseId(courseId);
        setCustomUrl('');
        setCustomOpen(true);
    };

    const generateCustomLink = async (e) => {
        e.preventDefault();
        const form = e.target;
        const price = form.price.value;
        const currency = form.currency.value;
        const classes = form.classes.value;
        const res = await fetch(`/admin/courses/${customCourseId}/custom-link`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: JSON.stringify({ price, currency, classes }),
        });
        if (!res.ok) {
            window.alert('Error generating link');
            return;
        }
        const data = await res.json();
        if (data.url) setCustomUrl(data.url);
        else window.alert('Failed to generate link');
    };

    const copyCustom = () => {
        if (!customUrl) return;
        navigator.clipboard.writeText(customUrl).then(() => {
            setToast('✅ Custom link copied to clipboard!');
            setTimeout(() => setToast(''), 2800);
        });
    };

    const patchStatus = (url, status) => {
        router.patch(url, { status }, { preserveScroll: true });
    };

    const destroyCourse = (url) => {
        if (!window.confirm('Are you sure you want to delete this course?')) return;
        router.delete(url, { preserveScroll: true });
    };

    const statusClass = (s) => {
        if (s === 'published') return 'text-on-tertiary-container';
        if (s === 'pending') return 'text-warning';
        if (s === 'rejected') return 'text-error';
        return 'text-on-surface-variant';
    };

    return (
        <DashboardLayout title="Course Management">
            <Head title="Course Approval" />
            <CopyToast message={toast} show={!!toast} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Catalog"
                    title="Course management"
                    description="Create courses and manage the syllabus."
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <a href={routes.sampleCsv} className="px-3 py-2 bg-surface-container-high border border-outline-variant/40 text-on-surface text-xs font-bold rounded-lg inline-flex items-center gap-2">
                                <Icon name="download" className="text-base text-primary" /> CSV template
                            </a>
                            <button type="button" onClick={() => setBulkOpen(true)} className="px-3 py-2 bg-secondary text-on-secondary font-bold rounded-lg inline-flex items-center gap-2 text-xs">
                                <Icon name="upload_file" /> Bulk upload
                            </button>
                            <Link href={routes.create} className="btn-primary btn-sm rounded-lg inline-flex items-center gap-2">
                                <Icon name="add" /> Create course
                            </Link>
                        </div>
                    }
                />

                {flash?.success && (
                    <div className="mb-6 p-4 bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary rounded-2xl flex items-center gap-3">
                        <Icon name="check_circle" className="text-on-tertiary-container" />
                        <div className="font-bold text-sm">{flash.success}</div>
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 p-4 bg-error-container border border-error-container text-error rounded-2xl flex items-center gap-3">
                        <Icon name="error" />
                        <div className="font-bold text-sm">{flash.error}</div>
                    </div>
                )}
                {importErrors?.length > 0 && (
                    <div className="mb-6 p-4 bg-error-container border border-error-container text-error rounded-2xl">
                        <div className="flex items-center gap-3 mb-2">
                            <Icon name="error" />
                            <div className="font-bold text-sm">Import blocked — please fix the following and upload again:</div>
                        </div>
                        <ul className="list-disc pl-10 space-y-1 text-sm">
                            {importErrors.map((err, i) => <li key={i}>{err}</li>)}
                        </ul>
                    </div>
                )}
                {importWarnings?.length > 0 && (
                    <div className="mb-6 p-4 bg-warning-container border border-warning-container text-warning rounded-2xl">
                        <div className="flex items-center gap-3 mb-2">
                            <Icon name="warning" />
                            <div className="font-bold text-sm">Import completed with {importWarnings.length} warning(s):</div>
                        </div>
                        <ul className="list-disc pl-10 space-y-1 text-sm">
                            {importWarnings.map((w, i) => <li key={i}>{w}</li>)}
                        </ul>
                    </div>
                )}

                <PortalCard title="Search & filter" className="mb-0">
                    <form method="GET" action={routes.index} className="flex flex-wrap gap-4 items-end">
                        <div className="flex-1 min-w-[220px]">
                            <label className="block text-sm font-bold mb-2" htmlFor="admin-course-search">Search</label>
                            <div className="relative">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none" />
                                <input id="admin-course-search" type="search" name="search" defaultValue={filters.search} placeholder="Search by title, category, or tutor…" className="w-full pl-10 pr-3 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Filter by Status</label>
                            <select name="status" defaultValue={filters.status} className="p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                <option value="">All Statuses</option>
                                {['draft', 'pending', 'published', 'rejected'].map((s) => (
                                    <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
                                ))}
                            </select>
                        </div>
                        <button type="submit" className="btn-primary btn-sm">Search</button>
                        {(filters.status || filters.search) && (
                            <Link href={routes.index} className="px-6 py-3 bg-surface-container text-on-surface font-bold rounded-xl">Clear</Link>
                        )}
                    </form>
                </PortalCard>

                <PortalCard title="Courses">
                    <DataTableShell
                        minWidth="1400px"
                        mobileCards={courses.map((c) => (
                            <div key={c.id} className="staff-mobile-card min-w-0 space-y-1">
                                <p className="font-medium break-words">{c.title}</p>
                                <p className="text-xs text-on-surface-variant">{c.categoryLabel} · {c.status}</p>
                                <p className="text-sm font-bold">${c.priceUsdLabel} / class</p>
                                <Link href={c.editUrl} className="text-xs font-bold text-primary">Edit</Link>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b-2 border-outline-variant">
                                    {['Course', 'Category', 'Price', 'Created By', 'Status', 'Active', 'Submitted', 'Actions'].map((h) => (
                                        <th key={h} className={`py-4 px-4 font-bold text-on-surface ${h === 'Actions' ? 'text-right' : ''}`}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {courses.map((c) => (
                                    <tr key={c.id} className="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                        <td className="font-medium min-w-0"><span className="break-words line-clamp-2">{c.title}</span></td>
                                        <td className="text-on-surface-variant">{c.categoryLabel}</td>
                                        <td className="font-bold">${c.priceUsdLabel} / class</td>
                                        <td>
                                            <span className={`font-bold text-sm ${c.createdByType === 'tutor' ? 'text-tertiary' : 'text-primary'}`}>{c.createdByLabel}</span>
                                        </td>
                                        <td>
                                            <select
                                                defaultValue={c.status}
                                                onChange={(e) => patchStatus(c.statusUrl, e.target.value)}
                                                className={`p-2 text-sm font-bold rounded-lg border border-outline-variant bg-surface-container-low ${statusClass(c.status)}`}
                                            >
                                                {['draft', 'pending', 'published', 'rejected'].map((s) => (
                                                    <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="text-center">
                                            <form method="POST" action={c.toggleActiveUrl}>
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="PATCH" />
                                                <label className="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" defaultChecked={c.isActive} onChange={(e) => e.currentTarget.form?.requestSubmit()} className="sr-only peer" />
                                                    <div className="w-11 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-success" />
                                                </label>
                                            </form>
                                        </td>
                                        <td className="text-on-surface-variant text-sm">{formatLocalTime(c.createdAt, 'date')}</td>
                                        <td className="text-right">
                                            <div className="staff-table-actions">
                                                <button type="button" onClick={() => copyReviewLink(c.reviewUrl)} className="text-on-tertiary-container font-bold hover:underline text-sm">Review link</button>
                                                <button type="button" onClick={() => openCustom(c.id)} className="text-on-secondary-container font-bold hover:underline text-sm">Custom link</button>
                                                <Link href={c.editUrl} className="text-primary font-bold hover:underline">Edit</Link>
                                                <button type="button" onClick={() => destroyCourse(c.destroyUrl)} className="text-error font-bold hover:underline">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {!courses.length && (
                                    <tr><td colSpan={8} className="py-8 text-center text-on-surface-variant">No courses found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>
            </div>

            {customOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="bg-surface p-6 rounded-2xl shadow-xl w-full max-w-md">
                        <h2 className="text-xl font-bold mb-4 text-on-surface">Generate Custom Link</h2>
                        <form onSubmit={generateCustomLink}>
                            <div className="mb-4">
                                <label className="block text-sm font-bold mb-2">Price</label>
                                <input type="number" step="0.01" name="price" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div className="mb-4">
                                <label className="block text-sm font-bold mb-2">Currency</label>
                                <select name="currency" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                                    {['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'INR'].map((cur) => <option key={cur} value={cur}>{cur}</option>)}
                                </select>
                            </div>
                            <div className="mb-4">
                                <label className="block text-sm font-bold mb-2">Number of Classes</label>
                                <input type="number" name="classes" min={1} defaultValue={1} className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" />
                            </div>
                            <div className="flex justify-end gap-3">
                                <button type="button" onClick={() => setCustomOpen(false)} className="px-4 py-2 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl">Done</button>
                                <button type="submit" className="btn-primary">Generate</button>
                            </div>
                        </form>
                        {customUrl ? (
                            <div className="mt-4">
                                <p className="text-sm font-bold mb-2 text-on-surface">Generated Link:</p>
                                <div className="flex gap-2">
                                    <input type="text" readOnly value={customUrl} className="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg text-sm" />
                                    <button type="button" onClick={copyCustom} className="btn-primary btn-sm whitespace-nowrap">Copy</button>
                                </div>
                            </div>
                        ) : null}
                    </div>
                </div>
            )}

            {bulkOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-surface border border-outline-variant rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden">
                        <div className="p-6 bg-gradient-to-r from-secondary-container/50 to-primary-container/30 border-b border-outline-variant flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-secondary/20 flex items-center justify-center text-secondary">
                                    <Icon name="cloud_upload" className="text-2xl" />
                                </div>
                                <div>
                                    <h2 className="text-xl font-bold text-on-surface">Bulk Upload Courses (CSV)</h2>
                                    <p className="text-xs text-on-surface-variant">Upload single or multiple AI courses with 24 to 100 classes in one go.</p>
                                </div>
                            </div>
                            <button type="button" onClick={() => setBulkOpen(false)} className="text-on-surface-variant hover:text-on-surface p-1.5 rounded-lg hover:bg-black/5">
                                <Icon name="close" />
                            </button>
                        </div>
                        <form action={routes.bulkPreview} method="POST" encType="multipart/form-data" className="p-6 space-y-5">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div className="p-4 bg-primary/5 border border-primary/20 rounded-2xl text-xs space-y-2 text-on-surface-variant">
                                <div className="flex items-center gap-2 font-bold text-primary text-sm">
                                    <Icon name="info" className="text-base" /> Supported CSV Features
                                </div>
                                <ul className="list-disc pl-5 space-y-1">
                                    <li>One unified template — <strong>course info + all classes</strong> in a single CSV file.</li>
                                    <li>You can <strong>override the category below</strong> for every course in this upload.</li>
                                    <li>You&apos;ll see a <strong>validation preview</strong> before anything is saved.</li>
                                </ul>
                                <div className="pt-2 border-t border-primary/10 flex items-center justify-between">
                                    <span className="font-bold text-on-surface">Need the exact CSV format?</span>
                                    <a href={routes.sampleCsv} className="text-primary font-bold hover:underline inline-flex items-center gap-1">
                                        <Icon name="download" className="text-xs" /> Download Course Template
                                    </a>
                                </div>
                            </div>
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <label className="block text-sm font-bold text-on-surface">Override Category <span className="text-xs font-normal text-on-surface-variant ml-1">(optional)</span></label>
                                    <button type="button" onClick={loadCategories} className="text-xs text-primary font-bold hover:underline inline-flex items-center gap-1">
                                        <Icon name="refresh" className="text-xs" /> Refresh
                                    </button>
                                </div>
                                {catLoading ? (
                                    <div className="text-xs text-on-surface-variant py-3 flex items-center gap-2">
                                        <Icon name="progress_activity" className="text-sm animate-spin" /> Loading categories…
                                    </div>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {categories.map((cat) => {
                                            const on = selectedCatIds.includes(cat.id);
                                            return (
                                                <button
                                                    key={cat.id}
                                                    type="button"
                                                    onClick={() => toggleChip(cat.id)}
                                                    className={`px-3 py-1.5 rounded-full border text-xs font-bold transition-all ${on ? 'bg-primary text-white border-primary' : 'border-outline-variant bg-surface-container text-on-surface hover:border-primary hover:text-primary'}`}
                                                >
                                                    {cat.name}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                                {selectedCatIds.map((id) => (
                                    <input key={id} type="hidden" name="category_ids[]" value={id} />
                                ))}
                                <p className="text-xs text-on-surface-variant mt-2">
                                    Categories are loaded live. If you just <a href={routes.categoriesIndex} target="_blank" rel="noreferrer" className="text-primary hover:underline">added a new category</a>, click Refresh.
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-bold text-on-surface mb-2">Select CSV File *</label>
                                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Default Status</label>
                                    <select name="default_status" defaultValue="draft" className="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold outline-none focus:border-primary">
                                        <option value="draft">Draft (Recommended)</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                                <div className="flex items-center pt-6">
                                    <label className="flex items-center gap-2 cursor-pointer text-sm font-bold text-on-surface">
                                        <input type="checkbox" name="update_existing" value="1" defaultChecked className="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary" />
                                        Update existing course if slug matches
                                    </label>
                                </div>
                            </div>
                            <div className="pt-4 border-t border-outline-variant flex justify-end gap-3">
                                <button type="button" onClick={() => setBulkOpen(false)} className="px-5 py-2.5 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl text-sm">Cancel</button>
                                <button type="submit" className="btn-primary px-6 py-2.5 text-sm font-bold shadow-md inline-flex items-center gap-2">
                                    <Icon name="fact_check" className="text-base" /> Validate &amp; Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}
