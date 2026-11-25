import { useState } from 'react';
import Icon from '../../../../Components/Icon';
import { parseSyllabusCsv } from '../../../../utils/parseSyllabusCsv';

export default function SyllabusModulesEditor({ initialRows = [], sampleCsvUrl }) {
    const [rows, setRows] = useState(initialRows);
    const [csvUploadMsg, setCsvUploadMsg] = useState('');

    const addRow = () => setRows((r) => [...r, { id: Date.now(), class_name: '', description: '', outcome: '', practical_video_url: '' }]);
    const add16 = () => {
        const extra = Array.from({ length: 16 }, (_, i) => ({
            id: Date.now() + i + Math.random(),
            class_name: '',
            description: '',
            outcome: '',
            practical_video_url: '',
        }));
        setRows((r) => [...r, ...extra]);
    };

    const handleCsv = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            const parsed = parseSyllabusCsv(ev.target.result);
            if (parsed.length) {
                setRows(parsed);
                setCsvUploadMsg(`✅ Successfully loaded ${parsed.length} classes from your CSV into the syllabus!`);
            } else {
                window.alert('Could not find class records in the CSV. Please ensure your CSV has "class_title" or "class_name" columns.');
            }
        };
        reader.readAsText(file);
        e.target.value = '';
    };

    const updateRow = (index, field, value) => {
        setRows((prev) => prev.map((row, i) => (i === index ? { ...row, [field]: value } : row)));
    };

    return (
        <div className="mb-6 space-y-4">
            <div className="flex flex-wrap items-center justify-between border-t border-outline-variant pt-6 gap-3">
                <div>
                    <div className="flex items-center gap-2">
                        <label className="block font-bold text-lg text-on-surface">Syllabus / Class Topics</label>
                        <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary">{rows.length} Classes</span>
                    </div>
                    <p className="text-xs text-on-surface-variant mt-0.5">Upload a CSV to instantly populate 24 to 100+ classes, or manage them below.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <label className="cursor-pointer text-secondary font-bold flex items-center gap-1 bg-secondary/10 hover:bg-secondary/20 px-3 py-1.5 rounded-lg transition-colors text-xs">
                        <Icon name="upload_file" className="text-sm" /> Upload Syllabus CSV
                        <input type="file" accept=".csv,text/csv,text/plain" className="hidden" onChange={handleCsv} />
                    </label>
                    {sampleCsvUrl ? (
                        <a href={sampleCsvUrl} className="text-on-surface-variant font-bold flex items-center gap-1 hover:text-primary bg-surface-container px-3 py-1.5 rounded-lg transition-colors text-xs">
                            <Icon name="download" className="text-sm" /> Sample CSV
                        </a>
                    ) : null}
                    <button type="button" onClick={addRow} className="text-primary font-bold flex items-center gap-1 hover:underline bg-primary/10 px-3 py-1.5 rounded-lg transition-colors text-xs">
                        <Icon name="add" className="text-sm" /> Add Class
                    </button>
                    <button type="button" onClick={() => { if (window.confirm('Clear all classes?')) setRows([]); }} className="text-error font-bold flex items-center gap-1 hover:bg-error/10 px-2 py-1.5 rounded-lg transition-colors text-xs">
                        <Icon name="delete_sweep" className="text-sm" />
                    </button>
                </div>
            </div>

            {csvUploadMsg ? (
                <div className="p-3 bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary rounded-xl text-xs font-bold flex items-center justify-between">
                    <span>{csvUploadMsg}</span>
                    <button type="button" onClick={() => setCsvUploadMsg('')} className="text-on-tertiary-container hover:text-tertiary">&times;</button>
                </div>
            ) : null}

            <div className="overflow-x-auto border border-outline-variant rounded-xl bg-white shadow-sm max-h-[600px] overflow-y-auto">
                <table className="staff-table w-full text-left text-sm">
                    <thead className="bg-surface-container-low text-on-surface border-b border-outline-variant sticky top-0 z-10">
                        <tr>
                            <th className="font-bold text-center w-12">#</th>
                            <th className="font-bold w-1/4">Class Name</th>
                            <th className="font-bold w-1/3">Description</th>
                            <th className="font-bold w-1/3">Learning Outcome</th>
                            <th className="font-bold w-1/4">Practical Video</th>
                            <th className="font-bold text-center w-16">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={row.id ?? index} className="border-b border-outline-variant last:border-0 hover:bg-surface-container-low transition-colors">
                                <td className="font-medium text-on-surface-variant text-center">{index + 1}</td>
                                <td>
                                    <input type="text" name={`modules[${index}][title]`} value={row.class_name} onChange={(e) => updateRow(index, 'class_name', e.target.value)} className="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none text-sm" placeholder="e.g. Intro to AI" />
                                </td>
                                <td>
                                    <textarea name={`modules[${index}][description]`} value={row.description} onChange={(e) => updateRow(index, 'description', e.target.value)} rows={2} className="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none resize-none text-sm" />
                                </td>
                                <td>
                                    <input type="text" name={`modules[${index}][learning_outcome]`} value={row.outcome} onChange={(e) => updateRow(index, 'outcome', e.target.value)} className="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none text-sm" />
                                </td>
                                <td>
                                    <input type="text" name={`modules[${index}][practical_video_url]`} value={row.practical_video_url} onChange={(e) => updateRow(index, 'practical_video_url', e.target.value)} className="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none text-sm" placeholder="https://... (optional)" />
                                </td>
                                <td className="text-center">
                                    <button type="button" onClick={() => setRows((r) => r.filter((_, i) => i !== index))} className="text-outline hover:text-error transition-colors p-1.5 rounded-lg hover:bg-error/10">
                                        <Icon name="delete" className="text-[20px]" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap justify-between items-center gap-3 pt-1 px-1">
                <div className="flex items-center gap-3 text-xs text-on-surface-variant">
                    <span>Showing <strong>{rows.length}</strong> classes in syllabus</span>
                    <span className="text-outline-variant">•</span>
                    <button type="button" onClick={add16} className="text-primary font-bold hover:underline">+ Add 16 more rows</button>
                </div>
                <button type="button" onClick={addRow} className="text-primary font-bold flex items-center gap-1.5 hover:bg-primary/20 bg-primary/10 px-4 py-2 rounded-xl transition-all shadow-sm text-sm">
                    <Icon name="add" className="text-base" /> Add Class
                </button>
            </div>
        </div>
    );
}
