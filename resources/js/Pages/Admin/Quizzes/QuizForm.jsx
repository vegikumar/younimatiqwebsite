import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import QuizBuilder from '../../../Components/QuizBuilder';

function courseIdsOf(student) {
    const raw = student.enrolledCourseIds ?? student.enrolled_course_ids ?? [];
    const list = Array.isArray(raw) ? raw : Object.values(raw || {});
    return list.map((id) => Number(id)).filter((id) => Number.isFinite(id));
}

export default function QuizForm({ mode = 'create', quiz = null, courses = [], students = [], assignedCourseId = null, assignedStudentIds = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [courseId, setCourseId] = useState(assignedCourseId ?? '');
    const [studentSearch, setStudentSearch] = useState('');
    const [pickedIds, setPickedIds] = useState(() => new Set((assignedStudentIds || []).map((id) => Number(id))));
    const formId = mode === 'create' ? 'admin-quiz-create-form' : 'admin-quiz-edit-form';
    const action = mode === 'create' ? '/admin/quizzes' : `/admin/quizzes/${quiz.id}`;

    const query = studentSearch.trim().toLowerCase();
    const selectedCourse = courseId ? Number(courseId) : null;

    const visibleStudents = useMemo(() => {
        return students.filter((s) => {
            const hay = `${s.name || ''} ${s.email || ''}`.toLowerCase();
            const matchesSearch = !query || hay.includes(query);
            if (query) return matchesSearch;
            if (!selectedCourse) return true;
            return courseIdsOf(s).includes(selectedCourse);
        });
    }, [students, query, selectedCourse]);

    const visibleIds = useMemo(() => new Set(visibleStudents.map((s) => Number(s.id))), [visibleStudents]);
    const hiddenPicked = [...pickedIds].filter((id) => !visibleIds.has(id));

    const toggleStudent = (id, checked) => {
        setPickedIds((prev) => {
            const next = new Set(prev);
            if (checked) next.add(id);
            else next.delete(id);
            return next;
        });
    };

    return (
        <DashboardLayout title={mode === 'create' ? 'Create Quiz' : 'Edit Quiz'}>
            <Head title={mode === 'create' ? 'Create Quiz' : 'Edit Quiz'} />
            <div className="staff-page">
                <div className="flex items-center justify-between">
                    <h1 className="font-headline-lg text-2xl font-bold">{mode === 'create' ? 'Create New Quiz' : 'Edit Quiz'}</h1>
                    <Link href="/admin/quizzes" className="text-on-surface-variant hover:text-primary">
                        &larr; Back to Quizzes
                    </Link>
                </div>
                {flash?.error ? <div className="bg-error-container p-4 rounded-xl font-bold">{flash.error}</div> : null}
                {flash?.success ? <div className="bg-success-container p-4 rounded-xl font-bold">{flash.success}</div> : null}

                <form id={formId} method="POST" action={action} encType="multipart/form-data" className="space-y-8">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {mode === 'edit' ? <input type="hidden" name="_method" value="PUT" /> : null}

                    <PortalCard className="p-6 border-l-4 border-secondary space-y-4">
                        <h2 className="text-xl font-bold">Assign To (Optional)</h2>
                        <div className="grid md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-bold mb-1">Assign to Course</label>
                                <select name="course_id" value={courseId} onChange={(e) => setCourseId(e.target.value)} className="w-full px-4 py-3 border rounded-xl bg-surface-container-low">
                                    <option value="">-- No Course --</option>
                                    {courses.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-1" htmlFor="quiz-student-search">
                                    Assign to Specific Students
                                </label>
                                <input
                                    id="quiz-student-search"
                                    type="search"
                                    value={studentSearch}
                                    onChange={(e) => setStudentSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') e.preventDefault();
                                    }}
                                    placeholder="Search students by name or email..."
                                    autoComplete="off"
                                    className="w-full mb-2 px-4 py-2 bg-white border border-outline-variant/50 rounded-xl text-sm"
                                />
                                <p className="mb-2 text-xs text-on-surface-variant">
                                    {query
                                        ? `${visibleStudents.length} match${visibleStudents.length === 1 ? '' : 'es'}`
                                        : selectedCourse
                                          ? `${visibleStudents.length} student${visibleStudents.length === 1 ? '' : 's'} in this course`
                                          : `${students.length} students`}
                                    {pickedIds.size ? ` · ${pickedIds.size} selected` : ''}
                                </p>
                                {hiddenPicked.map((id) => (
                                    <input key={`picked-${id}`} type="hidden" name="student_ids[]" value={id} />
                                ))}
                                <div className="max-h-48 overflow-y-auto border rounded-xl p-2 bg-surface-container-low space-y-1">
                                    {visibleStudents.length === 0 ? (
                                        <p className="p-2 text-sm text-on-surface-variant">
                                            {query
                                                ? 'No students match that search.'
                                                : 'No students are enrolled in this course. Search by name to assign anyone.'}
                                        </p>
                                    ) : (
                                        visibleStudents.map((s) => {
                                            const id = Number(s.id);
                                            const inCourse = selectedCourse ? courseIdsOf(s).includes(selectedCourse) : false;
                                            return (
                                                <label key={s.id} className="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        name="student_ids[]"
                                                        value={s.id}
                                                        checked={pickedIds.has(id)}
                                                        onChange={(e) => toggleStudent(id, e.target.checked)}
                                                        className="rounded"
                                                    />
                                                    <span className="min-w-0">
                                                        {s.name}{' '}
                                                        {s.email ? <span className="text-xs text-on-surface-variant">({s.email})</span> : null}
                                                        {query && inCourse ? (
                                                            <span className="ml-2 text-[10px] font-bold uppercase tracking-wide text-primary">In course</span>
                                                        ) : null}
                                                    </span>
                                                </label>
                                            );
                                        })
                                    )}
                                </div>
                            </div>
                        </div>
                    </PortalCard>

                    <QuizBuilder initialQuiz={quiz} formId={formId} />
                </form>
            </div>
        </DashboardLayout>
    );
}
