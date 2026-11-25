import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import { formatLocalTime } from '../../utils/formatLocalTime';

function avatarUrl(name) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Tutor')}&background=4CAF50&color=fff`;
}

export default function Qa({ questions = [], enrolledCourses = [], storeUrl = '/student/qa' }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Q & A">
            <Head title="Q & A" />
            <div className="p-5 md:p-8 space-y-8">
                <div>
                    <h1 className="font-headline-lg text-headline-lg font-bold mb-2">Q & A 💬</h1>
                    <p className="font-body-lg text-body-lg text-on-surface-variant">Questions you&apos;ve asked and answers from tutors.</p>
                </div>

                <div className="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                    <h2 className="font-headline-md font-bold mb-4">Ask a Question</h2>
                    <form method="POST" action={storeUrl} className="space-y-4 max-w-2xl">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div>
                            <label className="block text-sm font-bold mb-2">Course</label>
                            <select name="course_id" required className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                                <option value="">Select a course</option>
                                {enrolledCourses.map((course) => (
                                    <option key={course.id} value={course.id}>
                                        {course.title}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Question Title</label>
                            <input type="text" name="title" required maxLength={255} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Brief summary of your question" />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-2">Details</label>
                            <textarea name="body" rows={4} required maxLength={2000} className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Describe your question in detail..." />
                        </div>
                        <button type="submit" className="btn-primary flex items-center gap-2">
                            <span className="material-symbols-outlined">send</span> Post Question
                        </button>
                    </form>
                </div>

                <div className="space-y-6">
                    {questions.length ? (
                        questions.map((question) => (
                            <div key={question.id} className="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                                <div className="flex gap-4">
                                    <div className="w-12 h-12 bg-primary-fixed rounded-full flex items-center justify-center flex-shrink-0 text-primary font-bold">Q</div>
                                    <div className="w-full">
                                        <h3 className="font-bold text-lg mb-2">{question.title}</h3>
                                        <p className="text-sm text-on-surface-variant mb-2">
                                            Course: {question.courseTitle} · Asked on {formatLocalTime(question.createdAt, 'date')}
                                        </p>
                                        <p className="text-on-surface mb-4">{question.body}</p>
                                        {question.answers.map((answer) => (
                                            <div key={answer.id} className="bg-surface-container-low p-4 rounded-xl border-l-4 border-secondary mt-4">
                                                <div className="flex gap-3 mb-2 items-center">
                                                    <img src={avatarUrl(answer.authorName)} className="w-6 h-6 rounded-full" alt="" />
                                                    <span className="font-bold text-sm">{answer.authorName}</span>
                                                    <span className="text-xs text-on-surface-variant">{formatLocalTime(answer.createdAt, 'date')}</span>
                                                </div>
                                                <p className="text-on-surface">{answer.body}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
                            <span className="material-symbols-outlined text-4xl mb-2">forum</span>
                            <p className="font-bold mb-2">No questions asked yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
