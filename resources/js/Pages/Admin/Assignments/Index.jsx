import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import AssignmentQuestionBuilder from '../../../Components/AssignmentQuestionBuilder';
import Icon from '../../../Components/Icon';

export default function Index({ courses = [], students = [], assignments = [] }) {
    const { csrfToken, flash, errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();
    const [courseId, setCourseId] = useState('');
    const [studentSearch, setStudentSearch] = useState('');

    const filteredStudents = useMemo(() => {
        const q = studentSearch.toLowerCase().trim();
        return students.filter((s) => {
            const hay = `${s.name} ${s.email}`.toLowerCase();
            if (q) return hay.includes(q);
            if (courseId) return s.courseIds.includes(Number(courseId));
            return true;
        });
    }, [students, courseId, studentSearch]);

    return (
        <DashboardLayout title="Post class Activity">
            <Head title="Manage Post class Activity" />
            <div className="staff-page">
                <PageHeader variant="staff" eyebrow="Learning" title="Post class activity" description="Attach a PDF worksheet, add optional questions, or both. Use Quizzes when you only need graded questions." />
                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium">{flash.success}</div> : null}
                {errorList.length ? <div className="p-4 bg-error-container text-on-error-container rounded-xl"><ul className="list-disc pl-5">{errorList.map((e,i)=><li key={i}>{e}</li>)}</ul></div> : null}
                <PortalCard className="space-y-4 max-w-4xl min-w-0">
                <form method="POST" action="/admin/assignments" encType="multipart/form-data" className="space-y-4 min-w-0">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <h3 className="font-bold text-lg mb-2">Create New Post class Activity</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label className="block text-sm font-medium mb-1">Course (Optional)</label><select name="course_id" value={courseId} onChange={(e)=>setCourseId(e.target.value)} className="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none"><option value="">Select a Course</option>{courses.map(c=><option key={c.id} value={c.id}>{c.title}</option>)}</select></div>
                        <div><label className="block text-sm font-medium mb-1">Due Date</label><input type="datetime-local" name="due_at" className="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none" /></div>
                    </div>
                    <div><label className="block text-sm font-medium mb-1">Title</label><input type="text" name="title" required placeholder="Post class Activity title" className="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none" /></div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <textarea name="description" placeholder="Instructions for the student" className="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none" rows={4} />
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">XP Reward</label>
                                <input type="number" name="xp_reward" placeholder="XP Reward" className="w-full px-4 py-3 border rounded-xl" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">File Attachment (PDF worksheet)</label>
                                <input
                                    type="file"
                                    name="assignment_file"
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"
                                    className="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none bg-surface-container-low file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white"
                                />
                                <p className="text-xs text-on-surface-variant mt-1">PDF-only activities are fine — no questions required. PDF, Word, text, or image · max 10MB.</p>
                            </div>
                        </div>
                    </div>
                    <div className="border-t border-outline-variant/30 pt-6">
                        <h4 className="font-bold text-lg mb-1">Questions <span className="font-normal text-on-surface-variant text-sm">(optional)</span></h4>
                        <p className="text-sm text-on-surface-variant mb-4">Optional. Leave empty when the worksheet is a PDF and questions live in Quizzes. If you add questions here, each starts as short answer.</p>
                        <AssignmentQuestionBuilder />
                    </div>
                    <div className="border border-outline-variant/50 rounded-xl overflow-hidden mt-4">
                        <div className="bg-surface-container-low p-3 border-b font-bold text-sm">Assign to Students (Select at least one)</div>
                        <div className="p-3 border-b"><input type="text" value={studentSearch} onChange={(e)=>setStudentSearch(e.target.value)} placeholder="Search students..." className="w-full pl-9 pr-4 py-2 border rounded-xl text-sm" /></div>
                        <div className="max-h-48 overflow-y-auto p-3 space-y-2">{filteredStudents.map(s=><label key={s.id} className="flex items-center gap-2 p-2 hover:bg-surface-container-lowest rounded-lg cursor-pointer"><input type="checkbox" name="student_ids[]" value={s.id} className="w-4 h-4 text-primary rounded" /><span className="text-sm font-medium">{s.name} <span className="text-xs text-on-surface-variant">({s.email})</span></span></label>)}</div>
                    </div>
                    <button type="submit" className="btn-primary btn-sm mt-4">Create & Assign</button>
                </form>
                </PortalCard>
                <div className="space-y-4 min-w-0"><h3 className="font-bold text-xl mt-8">Past Post class Activity</h3>
                    {assignments.length === 0 ? <p className="text-on-surface-variant">No assignments created yet.</p> : assignments.map(a=>(
                        <div key={a.id} className="bg-white rounded-2xl border p-6">
                            <div className="flex justify-between items-start mb-2"><div><h3 className="font-bold text-lg flex items-center gap-2">{a.title}{a.courseTitle ? <span className="px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full">{a.courseTitle}</span> : null}</h3><p className="text-sm text-on-surface-variant mt-1">Assigned to: {a.students.map((n,i)=><span key={i} className="inline-block bg-surface-container px-2 py-0.5 rounded-full text-xs font-medium mr-1">{n}</span>)}</p></div><span className="text-sm text-on-surface-variant">{a.createdAtLabel}</span></div>
                            {a.fileUrl ? <a href={a.fileUrl} target="_blank" rel="noreferrer" className="text-primary text-sm hover:underline flex items-center gap-1 mt-1"><Icon name="attachment" className="text-[16px]" /> View Attached Prompt</a> : null}
                            {a.hasQuestions ? <p className="text-sm text-on-surface-variant mt-1">{a.questionCount} question(s)</p> : null}
                            <div className="mt-4 border-t pt-4"><h4 className="font-bold text-sm mb-3">Submissions ({a.submissions.length})</h4>
                                {a.submissions.length === 0 ? <p className="text-sm text-on-surface-variant italic">No submissions yet.</p> : a.submissions.map(s=>(
                                    <div key={s.id} className="mt-2 p-4 bg-surface-container-low rounded-xl">
                                        <p className="text-sm flex justify-between gap-3"><strong>{s.studentName}</strong><span className="text-xs text-on-surface-variant">Submitted: {s.createdAtLabel}</span></p>
                                        {s.answersSummary ? <pre className="text-xs mt-2 whitespace-pre-wrap overflow-x-auto">{s.answersSummary}</pre> : null}
                                        <form method="POST" action={s.gradeUrl} className="mt-4 flex flex-wrap gap-2"><input type="hidden" name="_token" value={csrfToken} /><input type="number" name="grade" min={0} max={100} placeholder="Grade" defaultValue={s.grade ?? ''} className="w-28 px-3 py-2 border rounded-lg text-sm" /><input type="text" name="feedback" placeholder="Feedback" defaultValue={s.feedback ?? ''} className="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm" /><button type="submit" className="btn-secondary btn-sm px-4">{s.status === 'graded' ? 'Update Grade' : 'Grade'}</button></form>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
