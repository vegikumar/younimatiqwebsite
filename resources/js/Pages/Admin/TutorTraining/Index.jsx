import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

const DOC_ACCEPT =
    '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.odt,.ods,.odp,.txt,.rtf,.csv,.png,.jpg,.jpeg,.gif,.webp,.zip,.mp4,.webm,.mov,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation';

function AddItemForm({ module, csrfToken }) {
    const [type, setType] = useState('document');
    const [docUrls, setDocUrls] = useState(['']);
    const [fileRows, setFileRows] = useState([0]);
    const [pickedNames, setPickedNames] = useState([]);
    const [questions, setQuestions] = useState([
        { q: '', options: ['', '', '', ''], answer: '0' },
    ]);

    const quizJson = useMemo(() => JSON.stringify(questions), [questions]);

    const updateQuestion = (index, patch) => {
        setQuestions((prev) => prev.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    };

    const updateOption = (qIndex, optIndex, value) => {
        setQuestions((prev) =>
            prev.map((row, i) => {
                if (i !== qIndex) return row;
                const options = [...(row.options || [])];
                options[optIndex] = value;
                return { ...row, options };
            })
        );
    };

    const onFilesPicked = (event) => {
        const names = Array.from(event.target.files || []).map((f) => f.name);
        setPickedNames((prev) => {
            const merged = [...prev];
            names.forEach((n) => {
                if (!merged.includes(n)) merged.push(n);
            });
            return merged;
        });
    };

    return (
        <form method="POST" action={module.addItemUrl} encType="multipart/form-data" className="grid md:grid-cols-2 gap-3 border-t border-outline-variant/40 pt-4">
            <input type="hidden" name="_token" value={csrfToken} />
            {type === 'quiz' ? <input type="hidden" name="quiz_json" value={quizJson} /> : null}

            <label className="text-xs font-bold text-on-surface-variant">
                Type
                <select
                    name="type"
                    required
                    value={type}
                    onChange={(e) => setType(e.target.value)}
                    className="mt-1 w-full p-3 rounded-xl border border-outline-variant/50 bg-white"
                >
                    <option value="document">Document / PDF / PPT / files</option>
                    <option value="video">Video</option>
                    <option value="assignment">Assignment</option>
                    <option value="quiz">Quiz</option>
                </select>
            </label>

            <label className="text-xs font-bold text-on-surface-variant">
                Skill title
                <input name="title" required placeholder="e.g. Classroom facilitation" className="mt-1 w-full p-3 rounded-xl border border-outline-variant/50 bg-white" />
            </label>

            <label className="md:col-span-2 text-xs font-bold text-on-surface-variant">
                Instructions
                <textarea name="instructions" placeholder="What should the tutor review or complete?" className="mt-1 w-full p-3 rounded-xl border border-outline-variant/50 bg-white" rows={2} />
            </label>

            {type === 'document' ? (
                <div className="md:col-span-2 space-y-3 rounded-2xl border border-outline-variant/40 bg-surface-container-low/60 p-4">
                    <div>
                        <p className="text-sm font-extrabold text-on-surface">Documents & links</p>
                        <p className="text-xs text-on-surface-variant">
                            Upload PDFs, Word, PowerPoint, Excel, images, ZIP, or paste Drive/Slides links. You can add several files.
                        </p>
                    </div>
                    <div className="space-y-2">
                        {docUrls.map((url, i) => (
                            <div key={i} className="flex gap-2 items-center">
                                <input
                                    type="url"
                                    name="document_urls[]"
                                    value={url}
                                    onChange={(e) => setDocUrls((prev) => prev.map((v, idx) => (idx === i ? e.target.value : v)))}
                                    placeholder="Document URL (Google Docs/Slides, Notion, PDF link)"
                                    className="flex-1 p-3 rounded-xl border border-outline-variant/50 bg-white"
                                />
                                {docUrls.length > 1 ? (
                                    <button
                                        type="button"
                                        className="text-xs font-bold text-error shrink-0"
                                        onClick={() => setDocUrls((prev) => prev.filter((_, idx) => idx !== i))}
                                    >
                                        Remove
                                    </button>
                                ) : null}
                            </div>
                        ))}
                        <button
                            type="button"
                            className="text-xs font-extrabold text-primary"
                            onClick={() => setDocUrls((prev) => [...prev, ''])}
                        >
                            + Add another link
                        </button>
                    </div>
                    <div className="space-y-2">
                        <p className="text-xs font-bold text-on-surface-variant">Upload files</p>
                        {fileRows.map((rowKey) => (
                            <input
                                key={rowKey}
                                type="file"
                                name="document_files[]"
                                multiple
                                accept={DOC_ACCEPT}
                                onChange={onFilesPicked}
                                className="block w-full p-3 rounded-xl border border-outline-variant/50 bg-white text-sm"
                            />
                        ))}
                        <button
                            type="button"
                            className="text-xs font-extrabold text-primary"
                            onClick={() => setFileRows((prev) => [...prev, prev.length ? prev[prev.length - 1] + 1 : 1])}
                        >
                            + Add more files
                        </button>
                        {pickedNames.length ? (
                            <ul className="text-xs text-on-surface-variant space-y-0.5">
                                {pickedNames.map((name) => (
                                    <li key={name}>📎 {name}</li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-[11px] text-on-surface-variant">
                                Select multiple files in one picker (Ctrl/Cmd+click), or use “Add more files”. PDF, DOC, PPT, XLS, images, ZIP, and more.
                            </p>
                        )}
                    </div>
                </div>
            ) : null}

            {type === 'video' ? (
                <>
                    <input type="url" name="video_url" placeholder="Video URL" className="p-3 rounded-xl border border-outline-variant/50 bg-white" />
                    <input type="file" name="video_file" accept="video/*" className="p-3 rounded-xl border border-outline-variant/50 bg-white text-sm" />
                </>
            ) : null}

            {type === 'quiz' ? (
                <div className="md:col-span-2 space-y-3 rounded-2xl border border-outline-variant/40 bg-surface-container-low/60 p-4">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p className="text-sm font-extrabold text-on-surface">Quiz</p>
                            <p className="text-xs text-on-surface-variant">Build questions for tutors — students never see JSON.</p>
                        </div>
                        <label className="text-xs font-bold text-on-surface-variant">
                            Pass score (%)
                            <input type="number" name="pass_score" defaultValue={70} min={1} max={100} className="mt-1 block w-28 p-2.5 rounded-xl border border-outline-variant/50 bg-white" />
                        </label>
                    </div>

                    {questions.map((row, qIndex) => (
                        <div key={qIndex} className="rounded-xl border border-outline-variant/40 bg-white p-3 space-y-2">
                            <div className="flex items-center justify-between gap-2">
                                <p className="text-xs font-extrabold">Question {qIndex + 1}</p>
                                {questions.length > 1 ? (
                                    <button
                                        type="button"
                                        className="text-xs font-bold text-error"
                                        onClick={() => setQuestions((prev) => prev.filter((_, i) => i !== qIndex))}
                                    >
                                        Remove
                                    </button>
                                ) : null}
                            </div>
                            <input
                                value={row.q}
                                onChange={(e) => updateQuestion(qIndex, { q: e.target.value })}
                                placeholder="Question text"
                                className="w-full p-2.5 rounded-xl border border-outline-variant/50"
                                required={type === 'quiz'}
                            />
                            <div className="grid sm:grid-cols-2 gap-2">
                                {(row.options || []).map((opt, optIndex) => (
                                    <label key={optIndex} className="text-[11px] font-bold text-on-surface-variant flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name={`correct_${qIndex}`}
                                            checked={String(row.answer) === String(optIndex)}
                                            onChange={() => updateQuestion(qIndex, { answer: String(optIndex) })}
                                        />
                                        <input
                                            value={opt}
                                            onChange={(e) => updateOption(qIndex, optIndex, e.target.value)}
                                            placeholder={`Option ${optIndex + 1}`}
                                            className="flex-1 p-2 rounded-lg border border-outline-variant/50 font-normal text-sm"
                                            required={type === 'quiz'}
                                        />
                                    </label>
                                ))}
                            </div>
                        </div>
                    ))}

                    <button
                        type="button"
                        className="text-xs font-extrabold text-primary"
                        onClick={() => setQuestions((prev) => [...prev, { q: '', options: ['', '', '', ''], answer: '0' }])}
                    >
                        + Add question
                    </button>
                </div>
            ) : (
                <input type="number" name="pass_score" defaultValue={70} min={1} max={100} className="p-3 rounded-xl border border-outline-variant/50 bg-white" placeholder="Pass score %" />
            )}

            <button className="md:col-span-2 px-4 py-2.5 bg-secondary text-white rounded-xl font-bold">Add skill item</button>
        </form>
    );
}

function AssignTutorsForm({ module, tutors, csrfToken }) {
    const [query, setQuery] = useState('');
    const [picked, setPicked] = useState(() => new Set((module.assignedTutorIds || []).map((id) => Number(id))));
    const needle = query.trim().toLowerCase();
    const matches = !needle
        ? tutors
        : tutors.filter((t) => t.name.toLowerCase().includes(needle) || (t.email || '').toLowerCase().includes(needle));
    const chosen = tutors.filter((t) => picked.has(Number(t.id)));

    const toggle = (id) => {
        const numId = Number(id);
        setPicked((current) => {
            const next = new Set(current);
            if (next.has(numId)) next.delete(numId);
            else next.add(numId);
            return next;
        });
    };

    const selectAll = () => setPicked(new Set(tutors.map((t) => Number(t.id))));
    const clearAll = () => setPicked(new Set());

    return (
        <form method="POST" action={module.assignUrl} className="space-y-3 rounded-2xl border border-primary/20 bg-primary/5 p-4">
            <input type="hidden" name="_token" value={csrfToken} />
            {[...picked].map((id) => (
                <input key={id} type="hidden" name="tutor_ids[]" value={Number(id)} />
            ))}
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="text-sm font-extrabold text-on-surface">Assign to tutors</p>
                    <p className="text-xs text-on-surface-variant">
                        All approved tutors ({tutors.length}). Select who should see this module in Tutor → Training.
                    </p>
                </div>
                <div className="flex gap-2 shrink-0">
                    <button type="button" className="text-xs font-extrabold text-primary" onClick={selectAll} disabled={!tutors.length}>
                        Select all
                    </button>
                    <button type="button" className="text-xs font-extrabold text-on-surface-variant" onClick={clearAll}>
                        Clear
                    </button>
                </div>
            </div>
            <input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Filter by name or email"
                className="w-full p-3 rounded-xl border border-outline-variant/50 bg-white text-sm"
            />
            <div className="flex flex-wrap gap-2">
                {chosen.length === 0 ? (
                    <span className="text-xs text-on-surface-variant">No tutors assigned yet.</span>
                ) : (
                    chosen.map((t) => (
                        <button key={t.id} type="button" className="px-2 py-1 rounded-full bg-primary/15 text-xs font-bold" onClick={() => toggle(t.id)}>
                            {t.name} ×
                        </button>
                    ))
                )}
            </div>
            <ul className="max-h-72 overflow-y-auto border border-outline-variant/40 rounded-xl divide-y bg-white">
                {!tutors.length ? (
                    <li className="px-3 py-3 text-sm text-on-surface-variant">No approved tutors yet. Approve tutors on the Tutors page first.</li>
                ) : matches.length ? (
                    matches.map((t) => (
                        <li key={t.id}>
                            <button type="button" className="w-full text-left px-3 py-2 text-sm hover:bg-surface-container-low flex items-center justify-between gap-2" onClick={() => toggle(t.id)}>
                                <span className="min-w-0">
                                    <span className="font-bold">{t.name}</span>
                                    <span className="text-on-surface-variant"> · {t.email}</span>
                                </span>
                                <span className={`shrink-0 text-xs font-extrabold ${picked.has(Number(t.id)) ? 'text-error' : 'text-primary'}`}>
                                    {picked.has(Number(t.id)) ? 'Remove' : 'Add'}
                                </span>
                            </button>
                        </li>
                    ))
                ) : (
                    <li className="px-3 py-2 text-sm text-on-surface-variant">No tutors match that filter.</li>
                )}
            </ul>
            <button type="submit" className="px-4 py-2.5 bg-primary text-white rounded-xl font-bold text-sm">
                Save tutor assignments ({picked.size})
            </button>
        </form>
    );
}

export default function Index({ modules = [], skills = [], tutors = [] }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Tutor Training">
            <Head title="Tutor Training Modules" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Tutor skills"
                    title="Tutor training modules"
                    description="Create modules with docs, videos, and quizzes — then assign them to tutors right here."
                    actions={
                        <Link href="/admin/tutor-training/reviews" className="btn-primary btn-sm rounded-lg">
                            HR review queue
                        </Link>
                    }
                />
                {flash?.success ? <div className="p-3 rounded-lg bg-secondary-container font-bold">{flash.success}</div> : null}
                <PortalCard title="New module">
                <form method="POST" action="/admin/tutor-training/modules" className="space-y-3">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input name="title" required placeholder="Module title" className="w-full p-3 rounded-lg border bg-surface-container-low" />
                    <div className="grid gap-3 sm:grid-cols-3">
                        <input name="course_name" placeholder="Course name" className="w-full p-3 rounded-lg border bg-surface-container-low" />
                        <input name="class_name" placeholder="Class name" className="w-full p-3 rounded-lg border bg-surface-container-low" />
                        <input name="class_number" placeholder="Class number" className="w-full p-3 rounded-lg border bg-surface-container-low" />
                    </div>
                    <textarea name="description" placeholder="Description" className="w-full p-3 rounded-xl border bg-surface-container-low" />
                    <div className="flex flex-wrap gap-2">
                        {skills.map((s) => (
                            <label key={s.id} className="text-sm flex gap-1 items-center">
                                <input type="checkbox" name="skill_ids[]" value={s.id} /> {s.name}
                            </label>
                        ))}
                    </div>
                    <button className="px-4 py-2 bg-primary text-white rounded-lg font-bold">Create module</button>
                </form>
                </PortalCard>
                {modules.map((module) => (
                    <PortalCard
                        key={module.id}
                        title={
                            module.courseName || module.className
                                ? `${module.courseName || module.title}${module.className ? `, ${module.className}` : ''}${module.classNumber ? ` - Class ${module.classNumber}` : ''}`
                                : module.title
                        }
                        actions={
                            <button
                                type="button"
                                onClick={() => confirm('Delete module?') && router.delete(module.destroyUrl, { preserveScroll: true })}
                                className="text-error text-sm font-bold"
                            >
                                Delete
                            </button>
                        }
                    >
                        <div className="min-w-0 space-y-3">
                                {module.courseName || module.className ? (
                                    <p className="text-xs text-on-surface-variant">Module: {module.title}</p>
                                ) : null}
                                <p className="text-sm text-on-surface-variant break-words">{module.description}</p>
                                <div className="flex flex-wrap gap-1">
                                    {module.skillRewards.map((n, i) => (
                                        <span key={i} className="text-xs px-2 py-0.5 rounded-full bg-tertiary-fixed">
                                            {n}
                                        </span>
                                    ))}
                                </div>
                        </div>
                        <ul className="space-y-2">
                            {module.items.map((item, i) => (
                                <li key={i} className="p-3 rounded-xl bg-surface-container-low text-sm flex flex-wrap justify-between items-center gap-3">
                                    <span className="flex items-center gap-2 flex-wrap">
                                        <strong className="uppercase text-xs tracking-wide text-primary px-2 py-0.5 rounded bg-primary-container/40">
                                            {item.type === 'quiz' ? 'Quiz' : item.type === 'document' ? 'Document 📄' : item.type}
                                        </strong>
                                        <span className="font-bold">{item.title}</span>
                                        {(item.documents || []).map((doc, di) => (
                                            <a
                                                key={`${item.id}-doc-${di}`}
                                                href={doc.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-xs text-primary underline font-bold"
                                            >
                                                {doc.kind === 'url' ? `${doc.label || 'Open link'} ↗` : `${doc.label || 'Attachment'} 📎`}
                                            </a>
                                        ))}
                                    </span>
                                    {item.type === 'quiz' ? <span className="text-xs shrink-0">Pass ≥ {item.passScore}%</span> : null}
                                </li>
                            ))}
                        </ul>
                        <AssignTutorsForm key={`assign-${module.id}`} module={module} tutors={tutors} csrfToken={csrfToken} />
                        <AddItemForm module={module} csrfToken={csrfToken} />
                    </PortalCard>
                ))}
            </div>
        </DashboardLayout>
    );
}
