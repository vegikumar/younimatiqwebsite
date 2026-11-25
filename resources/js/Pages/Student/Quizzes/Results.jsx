import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';

export default function Results({ quiz, attempt, review = [] }) {
    return (
        <DashboardLayout title={`Results: ${quiz.title}`}>
            <Head title={`Results: ${quiz.title}`} />
            <div className="p-6 max-w-3xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold">{quiz.title}</h1>
                <div className="clay-card p-6 text-center">
                    <p className="text-4xl font-extrabold text-primary">{attempt.score}%</p>
                    <p className="font-bold mt-2">{attempt.passed ? 'Passed' : 'Keep practicing'}</p>
                </div>
                <div className="space-y-4">
                    {review.map((row) => (
                        <div key={row.number} className={`p-4 rounded-xl border ${row.is_manual ? 'border-outline-variant' : row.is_correct ? 'border-tertiary' : 'border-error/30'}`}>
                            <p className="font-bold">
                                Q{row.number}. {row.question}
                            </p>
                            {row.answer_image_url ? <img src={row.answer_image_url} alt="" className="mt-3 max-h-64 rounded-xl border object-contain" /> : <p className="text-sm mt-2">Your answer: {row.student_answer}</p>}
                            {!row.is_manual && !row.is_correct ? <p className="text-sm text-primary">Correct: {row.correct_answer}</p> : null}
                            {row.is_manual ? <p className="text-sm mt-2 text-on-surface-variant">Submitted</p> : <Icon name={row.is_correct ? 'check_circle' : 'cancel'} className={row.is_correct ? 'text-tertiary' : 'text-error'} />}
                        </div>
                    ))}
                </div>
                <div className="flex flex-wrap gap-3">
                    {!attempt.passed ? (
                        <Link href={quiz.take_url} className="btn-primary btn-sm">
                            Try again
                        </Link>
                    ) : null}
                    <Link href="/student/quizzes" className="text-primary font-bold self-center">
                        Back to quizzes
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
