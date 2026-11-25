import { useState } from 'react';
import Icon from '../../../../Components/Icon';
import PortalCard from '../../../../Components/Portal/PortalCard';
import { usePage } from '@inertiajs/react';

function UnitBlock({ unit, csrfToken }) {
    const [editingUnit, setEditingUnit] = useState(false);

    return (
        <div className="p-5 border border-outline-variant/30 rounded-2xl bg-surface-container-low/50">
            <div className="flex items-center justify-between mb-1">
                <div className="font-bold text-base text-on-surface flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">{unit.order}</span>
                    {unit.title}
                </div>
                <div className="flex items-center gap-3">
                    <button type="button" onClick={() => setEditingUnit(!editingUnit)} className="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                        <Icon name="edit" className="text-sm" /> Edit
                    </button>
                    <form method="POST" action={unit.destroyUrl} onSubmit={(e) => { if (!window.confirm(`Delete unit '${unit.title}' and all its lessons?`)) e.preventDefault(); }}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button type="submit" className="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                            <Icon name="delete" className="text-sm" /> Delete
                        </button>
                    </form>
                </div>
            </div>
            {editingUnit && (
                <div className="mt-3 p-4 bg-white border border-outline-variant/30 rounded-xl space-y-3 mb-3">
                    <h6 className="text-xs font-bold text-on-surface uppercase">Edit Unit Details</h6>
                    <form method="POST" action={unit.updateUrl} className="flex flex-col sm:flex-row gap-3">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PUT" />
                        <input type="text" name="title" defaultValue={unit.title} required className="flex-1 px-3 py-2 border rounded-xl text-sm" />
                        <input type="text" name="description" defaultValue={unit.description || ''} placeholder="Description" className="flex-1 px-3 py-2 border rounded-xl text-sm" />
                        <button type="submit" className="btn-primary py-2 px-4 text-xs font-bold whitespace-nowrap">Save</button>
                        <button type="button" onClick={() => setEditingUnit(false)} className="px-3 py-2 bg-surface-container-low text-on-surface text-xs font-bold rounded-xl">Cancel</button>
                    </form>
                </div>
            )}
            {unit.description ? <p className="text-xs text-on-surface-variant mb-3">{unit.description}</p> : null}
            <div className="space-y-2 pl-4 border-l-2 border-primary/20 mt-3">
                {unit.modules?.length ? unit.modules.map((mod) => (
                    <ModuleRow key={mod.id} mod={mod} csrfToken={csrfToken} />
                )) : <p className="text-xs text-on-surface-variant italic">No lessons in this unit yet.</p>}
            </div>
        </div>
    );
}

function ModuleRow({ mod, csrfToken }) {
    const [editingMod, setEditingMod] = useState(false);

    return (
        <div className="p-3 bg-white rounded-xl border border-outline-variant/20 text-sm space-y-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-on-surface">{mod.title}</span>
                    <span className="text-xs px-2 py-0.5 rounded bg-surface-container text-on-surface-variant uppercase font-bold">{mod.content_type}</span>
                    {mod.duration_minutes ? <span className="text-xs text-on-surface-variant font-medium">⏱️ {mod.duration_minutes} min</span> : null}
                    {mod.content_url ? (
                        <a href={mod.content_url} target="_blank" rel="noreferrer" className="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                            <Icon name="file_present" className="text-xs" /> Attached File
                        </a>
                    ) : null}
                </div>
                <div className="flex items-center gap-3">
                    <button type="button" onClick={() => setEditingMod(!editingMod)} className="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                        <Icon name="edit" className="text-xs" /> Edit
                    </button>
                    <form method="POST" action={mod.destroyUrl} onSubmit={(e) => { if (!window.confirm(`Delete lesson '${mod.title}'?`)) e.preventDefault(); }}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button type="submit" className="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                            <Icon name="delete" className="text-xs" /> Delete
                        </button>
                    </form>
                </div>
            </div>
            {editingMod && (
                <div className="mt-2 pt-3 border-t border-outline-variant">
                    <form method="POST" action={mod.updateUrl} encType="multipart/form-data" className="space-y-3">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="PUT" />
                        <input type="hidden" name="course_unit_id" value={mod.unit_id} />
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="title" defaultValue={mod.title} required className="px-3 py-1.5 border rounded-lg text-xs" />
                            <select name="module_type" defaultValue={mod.content_type} required className="px-3 py-1.5 border rounded-lg text-xs">
                                {['video', 'image', 'pdf', 'text', 'assignment'].map((t) => <option key={t} value={t}>{t}</option>)}
                            </select>
                            <input type="number" name="duration_minutes" defaultValue={mod.duration_minutes || ''} placeholder="Duration (min)" className="px-3 py-1.5 border rounded-lg text-xs" />
                        </div>
                        <div className="flex items-center gap-2">
                            <input type="file" name="content_file" accept="video/*,image/*,application/pdf" className="flex-1 text-xs file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-surface-container-low" />
                            <button type="submit" className="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg">Save</button>
                            <button type="button" onClick={() => setEditingMod(false)} className="px-3 py-1.5 bg-surface-container-low text-on-surface text-xs font-bold rounded-lg">Cancel</button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}

export default function SyllabusUnitsPanel({ course }) {
    const { csrfToken } = usePage().props;
    const [moduleType, setModuleType] = useState('video');
    const units = course.units || [];

    const showFile = moduleType !== 'text';
    const showText = moduleType === 'text' || moduleType === 'assignment';

    return (
        <PortalCard className="p-5 md:p-8 mt-10">
            <h2 className="font-headline-xl text-headline-sm text-on-surface mb-6 flex items-center gap-2">
                <Icon name="view_list" className="text-primary" /> Syllabus Units &amp; Modules
            </h2>

            {units.length > 0 && (
                <div className="space-y-4 mb-8">
                    {units.map((unit) => (
                        <UnitBlock key={unit.id} unit={unit} csrfToken={csrfToken} />
                    ))}
                </div>
            )}

            <div className="p-4 bg-surface-container-low border border-outline-variant/20 rounded-2xl space-y-3 mb-6">
                <h5 className="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                    <Icon name="add" className="text-sm text-primary" /> Add Syllabus Unit
                </h5>
                <form method="POST" action={course.unitsStoreUrl} className="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="text" name="title" placeholder="Unit Title (e.g. Unit 1: Foundations)" required className="flex-1 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" />
                    <input type="text" name="description" placeholder="Description (Optional)" className="flex-1 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" />
                    <button type="submit" className="btn-primary py-2 px-4 text-xs font-bold whitespace-nowrap">Add Unit</button>
                </form>
            </div>

            <div className="p-4 bg-primary-container/20 border border-primary/20 rounded-2xl space-y-3">
                <h5 className="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                    <Icon name="post_add" className="text-sm text-primary" /> Add Lesson / Content Block
                </h5>
                <form method="POST" action={course.modulesStoreUrl} encType="multipart/form-data" className="space-y-3">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" name="title" placeholder="Lesson Title" required className="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" />
                        <select name="course_unit_id" required className="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                            <option value="">Select Unit *</option>
                            {units.map((u) => <option key={u.id} value={u.id}>{u.title}</option>)}
                        </select>
                        <select name="module_type" value={moduleType} onChange={(e) => setModuleType(e.target.value)} required className="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                            <option value="video">Video</option>
                            <option value="image">Image</option>
                            <option value="text">Text / Reading</option>
                            <option value="pdf">PDF Document</option>
                            <option value="assignment">Post class Activity</option>
                        </select>
                    </div>
                    <div className="flex flex-col sm:flex-row gap-3 items-center">
                        {showFile && (
                            <div className="flex-1 w-full">
                                <label className="block text-xs font-bold text-on-surface mb-1">Upload Video / Image / PDF File</label>
                                <input type="file" name="content_file" className="w-full px-3 py-1.5 bg-white border border-outline-variant/30 rounded-xl text-sm" />
                            </div>
                        )}
                        {showText && (
                            <div className="flex-1 w-full">
                                <label className="block text-xs font-bold text-on-surface mb-1">Text Content</label>
                                <textarea name="content_text" rows={2} className="w-full px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" placeholder="Enter text content here..." />
                            </div>
                        )}
                        <div className="w-full sm:w-32">
                            <label className="block text-xs font-bold text-on-surface mb-1">Duration (min)</label>
                            <input type="number" name="duration_minutes" placeholder="e.g. 15" className="w-full px-4 py-1.5 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" />
                        </div>
                        <div className="w-full sm:w-auto self-end">
                            <button type="submit" className="btn-primary w-full py-1.5 px-6 font-bold">Add Lesson</button>
                        </div>
                    </div>
                </form>
            </div>
        </PortalCard>
    );
}
