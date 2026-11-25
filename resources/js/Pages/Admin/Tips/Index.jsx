import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Index({
    currentFolderId = null,
    breadcrumbs = [],
    totalFolders = 0,
    totalTips = 0,
    rootFoldersCount = 0,
    rootTipsCount = 0,
    folders = [],
    tips = [],
    allFolders = [],
    students = [],
    assignments = [],
}) {
    const { csrfToken, flash, errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();
    const [folderModal, setFolderModal] = useState(false);
    const [tipModal, setTipModal] = useState(false);
    const [assignModal, setAssignModal] = useState(false);
    const [selectedTipIds, setSelectedTipIds] = useState([]);
    const [selectedFolderIds, setSelectedFolderIds] = useState([]);

    const toggleTip = (id) => setSelectedTipIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    const toggleFolder = (id) => setSelectedFolderIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    const selectionCount = selectedTipIds.length + selectedFolderIds.length;

    return (
        <DashboardLayout title="Tips & Tricks">
            <Head title="Tips & Tricks Management" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Content"
                    title="Tips & tricks"
                    description="Manage and assign tips to students."
                />

                <div className="flex gap-4 flex-wrap">
                    <div className="bg-white border rounded-xl p-4 min-w-[150px] shadow-sm"><p className="text-xs font-bold text-on-surface-variant uppercase">ALL FOLDERS</p><h2 className="text-2xl font-bold mt-1">{totalFolders}</h2></div>
                    <div className="bg-white border rounded-xl p-4 min-w-[150px] shadow-sm"><p className="text-xs font-bold text-on-surface-variant uppercase">ALL FILES</p><h2 className="text-2xl font-bold mt-1">{totalTips}</h2></div>
                    <div className="bg-white border rounded-xl p-4 min-w-[200px] shadow-sm"><p className="text-xs font-bold text-on-surface-variant uppercase">ROOT LEVEL</p><h2 className="text-lg font-bold mt-1">{rootFoldersCount} folders · {rootTipsCount} files</h2></div>
                </div>

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium">{flash.success}</div> : null}
                {errorList.length ? <div className="p-4 bg-error-container rounded-xl"><ul className="list-disc pl-5">{errorList.map((e, i) => <li key={i}>{e}</li>)}</ul></div> : null}

                <PortalCard padding={false} className="overflow-hidden min-w-0">
                    <div className="p-4 border-b bg-surface-container-lowest flex flex-wrap items-center gap-2">
                        <Icon name="folder" className="text-warning" />
                        <Link href="/admin/tips" className="text-sm font-medium hover:text-primary">Admin</Link>
                        {breadcrumbs.map((bc) => (
                            <span key={bc.id} className="flex items-center gap-1 text-sm">
                                <Icon name="chevron_right" className="text-[16px] text-outline" />
                                <Link href={`/admin/tips?folder_id=${bc.id}`} className="hover:text-primary">{bc.name}</Link>
                            </span>
                        ))}
                    </div>
                    <div className="p-4 border-b flex flex-wrap gap-2">
                        <button type="button" onClick={() => setFolderModal(true)} className="px-4 py-2 bg-success text-white font-bold rounded-lg text-sm">New Folder</button>
                        <button type="button" onClick={() => setTipModal(true)} className="px-4 py-2 bg-success text-white font-bold rounded-lg text-sm">Add File</button>
                        <button type="button" disabled={selectionCount === 0} onClick={() => setAssignModal(true)} className={`px-4 py-2 border rounded-lg text-sm font-bold ${selectionCount ? 'text-primary' : 'opacity-50'}`}>
                            Assign ({selectionCount})
                        </button>
                        <form method="POST" action="/admin/tips/bulk-delete" onSubmit={(e) => selectionCount && confirm('Delete selected?') ? null : e.preventDefault()}>
                            <input type="hidden" name="_token" value={csrfToken} />
                            {selectedFolderIds.map((id) => <input key={`f-${id}`} type="hidden" name="tip_folder_ids[]" value={id} />)}
                            {selectedTipIds.map((id) => <input key={`t-${id}`} type="hidden" name="tip_ids[]" value={id} />)}
                            <button type="submit" disabled={!selectionCount} className="px-4 py-2 border text-error rounded-lg text-sm font-bold disabled:opacity-50">Delete ({selectionCount})</button>
                        </form>
                    </div>
                    <div className="p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                        {folders.map((folder) => (
                            <div key={folder.id} className="relative flex flex-col items-center p-4 rounded-2xl hover:bg-surface-container-low border border-transparent hover:border-outline-variant/30">
                                <input type="checkbox" checked={selectedFolderIds.includes(folder.id)} onChange={() => toggleFolder(folder.id)} className="absolute top-2 left-2 w-5 h-5" />
                                <Link href={folder.url} className="flex flex-col items-center w-full">
                                    <Icon name="folder" className="text-5xl text-warning mb-2" />
                                    <h4 className="font-bold text-sm text-center truncate w-full">{folder.name}</h4>
                                    <p className="text-xs text-on-surface-variant mt-1">{folder.tipsCount} files · {folder.childrenCount} folders</p>
                                </Link>
                            </div>
                        ))}
                        {tips.map((tip) => (
                            <div key={tip.id} className="relative flex flex-col items-center p-4 rounded-2xl hover:bg-surface-container-low">
                                <input type="checkbox" checked={selectedTipIds.includes(tip.id)} onChange={() => toggleTip(tip.id)} className="absolute top-2 left-2 w-5 h-5" />
                                <a href={tip.streamUrl} target="_blank" rel="noopener noreferrer" className="flex flex-col items-center">
                                    <Icon name={tip.type === 'video' ? 'movie' : 'description'} className="text-5xl mb-2" />
                                    <h4 className="font-bold text-sm text-center truncate w-full">{tip.title}</h4>
                                </a>
                            </div>
                        ))}
                        {folders.length === 0 && tips.length === 0 ? <p className="col-span-full text-center text-on-surface-variant py-12">This folder is empty.</p> : null}
                    </div>
                </PortalCard>

                {assignments.length ? (
                    <PortalCard className="p-5">
                        <h3 className="font-bold mb-3">Recent assignments</h3>
                        <ul className="text-sm space-y-1">{assignments.slice(0, 20).map((a, i) => <li key={i}>{a.studentName} → {a.target}</li>)}</ul>
                    </PortalCard>
                ) : null}

                {folderModal ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div className="bg-white rounded-2xl p-6 max-w-md w-full">
                            <h3 className="font-bold mb-4">New folder</h3>
                            <form method="POST" action="/admin/tips/folder" className="space-y-3">
                                <input type="hidden" name="_token" value={csrfToken} />
                                {currentFolderId ? <input type="hidden" name="parent_id" value={currentFolderId} /> : null}
                                <input name="name" required className="w-full p-3 border rounded-xl" placeholder="Folder name" />
                                <div className="flex gap-2"><button type="button" onClick={() => setFolderModal(false)} className="btn-outline flex-1">Cancel</button><button type="submit" className="btn-primary flex-1">Create</button></div>
                            </form>
                        </div>
                    </div>
                ) : null}

                {tipModal ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div className="bg-white rounded-2xl p-6 max-w-md w-full">
                            <h3 className="font-bold mb-4">Add file</h3>
                            <form method="POST" action="/admin/tips/item" encType="multipart/form-data" className="space-y-3">
                                <input type="hidden" name="_token" value={csrfToken} />
                                {currentFolderId ? <input type="hidden" name="tip_folder_id" value={currentFolderId} /> : null}
                                <input name="title" required className="w-full p-3 border rounded-xl" placeholder="Title" />
                                <select name="type" className="w-full p-3 border rounded-xl"><option value="file">File</option><option value="video">Video</option></select>
                                <input type="file" name="content_files[]" required className="w-full p-2 border rounded-xl" />
                                <div className="flex gap-2"><button type="button" onClick={() => setTipModal(false)} className="btn-outline flex-1">Cancel</button><button type="submit" className="btn-primary flex-1">Upload</button></div>
                            </form>
                        </div>
                    </div>
                ) : null}

                {assignModal ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div className="bg-white rounded-2xl p-6 max-w-md w-full">
                            <h3 className="font-bold mb-4">Assign to student</h3>
                            <form method="POST" action="/admin/tips/assign" className="space-y-3">
                                <input type="hidden" name="_token" value={csrfToken} />
                                {selectedFolderIds.map((id) => <input key={id} type="hidden" name="tip_folder_ids[]" value={id} />)}
                                {selectedTipIds.map((id) => <input key={id} type="hidden" name="tip_ids[]" value={id} />)}
                                <select name="student_id" required className="w-full p-3 border rounded-xl"><option value="">Select student</option>{students.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
                                <div className="flex gap-2"><button type="button" onClick={() => setAssignModal(false)} className="btn-outline flex-1">Cancel</button><button type="submit" className="btn-primary flex-1">Assign</button></div>
                            </form>
                        </div>
                    </div>
                ) : null}
            </div>
        </DashboardLayout>
    );
}
