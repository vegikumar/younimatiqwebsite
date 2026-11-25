import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function AiAssistant({ courses = [], recentNotes = [], recaps = [], pendingRecapCount = 0, parentNoteUrl, projectReviewUrl }) {
    const { csrfToken, flash } = usePage().props;
    const [toolTab, setToolTab] = useState('note');
    const [drafting, setDrafting] = useState(false);

    const studentOptions = useMemo(() => {
        const out = [];
        courses.forEach((course) => {
            (course.students || []).forEach((s) => {
                out.push({ ...s, key: `${s.id}-${course.id}` });
            });
        });
        return out;
    }, [courses]);

    return (
        <DashboardLayout title="AI Assistant">
            <Head title="AI Teaching Assistant" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Draft assist"
                    title="AI Teaching Assistant"
                    description="Template-based draft assist for parent updates, Zoom recaps, and project notes. Review every draft before sending — this is not a live LLM chat."
                />

                <PortalCard>
                    {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container text-sm font-bold">{flash.success}</div> : null}
                    {flash?.error ? <div className="p-3 rounded-xl bg-error-container text-error text-sm font-bold">{flash.error}</div> : null}
                    <div className="flex border-b border-outline-variant/20 flex-wrap">
                        <button type="button" onClick={() => setToolTab('note')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${toolTab === 'note' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                            <span className="material-symbols-outlined text-sm">edit_note</span> AI Parent Note & HW Drafter
                        </button>
                        <button type="button" onClick={() => setToolTab('recap')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${toolTab === 'recap' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                            <span className="material-symbols-outlined text-sm">subtitles</span> 4-Hour Zoom Session Recaps ({pendingRecapCount})
                        </button>
                        <button type="button" onClick={() => setToolTab('project')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${toolTab === 'project' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                            <span className="material-symbols-outlined text-sm">code</span> AI Code & Project Reviewer
                        </button>
                    </div>

                    {toolTab === 'note' ? (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 pt-6">
                            <div className="space-y-4 min-w-0">
                                <h3 className="font-bold text-base text-on-surface flex items-center gap-2">
                                    <span className="material-symbols-outlined text-primary">auto_fix_high</span> Jot 2–3 Quick Bullets
                                </h3>
                                <form method="POST" action={parentNoteUrl} className="space-y-4 min-w-0" onSubmit={() => setDrafting(true)}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <div className="min-w-0">
                                        <label className="block text-xs font-bold text-on-surface mb-1">Class *</label>
                                        <select name="course_id" required className="w-full min-w-0 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                            <option value="">— Select Class —</option>
                                            {courses.map((course) => (
                                                <option key={course.id} value={course.id}>
                                                    {course.title}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="min-w-0">
                                        <label className="block text-xs font-bold text-on-surface mb-1">Student / Family *</label>
                                        <select name="student_id" required className="w-full min-w-0 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                            <option value="">— Select Student —</option>
                                            {studentOptions.map((s) => (
                                                <option key={s.key} value={s.id}>
                                                    {s.name} ({s.courseTitle})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="min-w-0">
                                        <label className="block text-xs font-bold text-on-surface mb-1">Quick Bullet Points *</label>
                                        <textarea name="raw_bullets" required rows={4} placeholder="e.g.&#10;• Completed Python loops" className="w-full min-w-0 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none resize-none" />
                                    </div>
                                    <button type="submit" disabled={drafting} className="btn-enroll w-full py-3 text-xs font-bold flex items-center justify-center gap-1 disabled:opacity-60">
                                        <span className="material-symbols-outlined text-sm">auto_awesome</span>
                                        {drafting ? 'Drafting…' : 'Draft polished update & homework'}
                                    </button>
                                </form>
                            </div>
                            <div className="space-y-4 min-w-0">
                                <h3 className="font-bold text-base text-on-surface">Recent Saved AI Notes</h3>
                                <div className="space-y-3 max-h-[400px] overflow-y-auto">
                                    {recentNotes.length ? (
                                        recentNotes.map((note) => (
                                            <div key={note.id} className="p-4 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-2 text-xs min-w-0">
                                                <div className="flex justify-between items-baseline gap-2 font-bold text-on-surface min-w-0">
                                                    <span className="break-words">
                                                        {note.studentName} ({note.courseTitle})
                                                    </span>
                                                    <span className="text-[10px] text-on-surface-variant shrink-0">{formatLocalTime(note.createdAt, 'datetime-short')}</span>
                                                </div>
                                                <div className="p-3 bg-white rounded-xl border border-outline-variant/10 text-on-surface leading-relaxed whitespace-pre-line break-words">{note.aiGeneratedNote}</div>
                                                {note.homeworkAssignment ? <div className="p-2 bg-warning-container text-warning rounded-lg font-bold break-words">{note.homeworkAssignment}</div> : null}
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-xs text-on-surface-variant py-6 text-center">No AI notes generated yet.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {toolTab === 'recap' ? (
                        <div className="space-y-6 pt-6 min-w-0">
                            <h3 className="font-bold text-base text-on-surface flex items-center gap-2">
                                <span className="material-symbols-outlined text-primary">schedule</span> Automatic Zoom Session Recaps (4-Hour Edit Window)
                            </h3>
                            <p className="text-xs text-on-surface-variant">Review, edit, skip, or approve recaps before they auto-send to families.</p>
                            <div className="space-y-4">
                                {recaps.length ? (
                                    recaps.map((recap) => (
                                        <div key={recap.id} className="p-5 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-3 min-w-0">
                                            <div className="flex justify-between items-center flex-wrap gap-2">
                                                <div className="min-w-0">
                                                    <h4 className="font-bold text-sm text-on-surface break-words">{recap.courseTitle}</h4>
                                                    <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${recap.status === 'sent' ? 'bg-tertiary-fixed text-tertiary' : recap.status === 'skipped' ? 'bg-surface-container-low text-on-surface' : 'bg-warning-container text-warning'}`}>{recap.status}</span>
                                                </div>
                                                {recap.status === 'pending_review' && recap.autoSendHuman ? (
                                                    <span className="text-xs text-error font-bold flex items-center gap-1 shrink-0">
                                                        <span className="material-symbols-outlined text-sm">timer</span> Auto-sends {recap.autoSendHuman}
                                                    </span>
                                                ) : null}
                                            </div>
                                            <form method="POST" action={recap.updateUrl} className="space-y-3 min-w-0">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <textarea name="edited_summary" rows={3} defaultValue={recap.editedSummary ?? ''} className="w-full min-w-0 px-4 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none" />
                                                {recap.status === 'pending_review' ? (
                                                    <div className="flex gap-2 justify-end flex-wrap">
                                                        <button type="submit" name="action" value="skip" className="btn-outline-primary py-1.5 px-4 text-xs font-bold">
                                                            Skip
                                                        </button>
                                                        <button type="submit" name="action" value="save" className="btn-primary py-1.5 px-4 text-xs font-bold">
                                                            Save Edits
                                                        </button>
                                                        <button type="submit" name="action" value="send" className="btn-enroll py-1.5 px-4 text-xs font-bold">
                                                            Approve & Send Now
                                                        </button>
                                                    </div>
                                                ) : null}
                                            </form>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-xs text-on-surface-variant py-8 text-center">No Zoom session recaps pending review.</p>
                                )}
                            </div>
                        </div>
                    ) : null}

                    {toolTab === 'project' ? (
                        <div className="space-y-6 pt-6 min-w-0">
                            <h3 className="font-bold text-base text-on-surface flex items-center gap-2">
                                <span className="material-symbols-outlined text-primary">developer_board</span> AI Code & Project Reviewer
                            </h3>
                            <form method="POST" action={projectReviewUrl} className="space-y-4 max-w-2xl min-w-0">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="min-w-0">
                                        <label className="block text-xs font-bold text-on-surface mb-1">Class *</label>
                                        <select name="course_id" required className="w-full min-w-0 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                            <option value="">— Select Class —</option>
                                            {courses.map((course) => (
                                                <option key={course.id} value={course.id}>
                                                    {course.title}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="min-w-0">
                                        <label className="block text-xs font-bold text-on-surface mb-1">Student *</label>
                                        <select name="student_id" required className="w-full min-w-0 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                            <option value="">— Select Student —</option>
                                            {studentOptions.map((s) => (
                                                <option key={s.key} value={s.id}>
                                                    {s.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="min-w-0">
                                    <label className="block text-xs font-bold text-on-surface mb-1">Project Title *</label>
                                    <input type="text" name="project_title" required placeholder="e.g. Scratch Maze Game" className="w-full min-w-0 px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none" />
                                </div>
                                <div className="min-w-0">
                                    <label className="block text-xs font-bold text-on-surface mb-1">Paste Code Snippet / Output Description</label>
                                    <textarea name="code_content" rows={4} className="w-full min-w-0 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs font-mono outline-none resize-none" />
                                </div>
                                <button type="submit" className="btn-enroll w-full py-3 text-xs font-bold">
                                    <span className="material-symbols-outlined text-sm">auto_awesome</span> Generate Parent Progress Note
                                </button>
                            </form>
                        </div>
                    ) : null}
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
