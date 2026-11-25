import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Index({ quizzes = [] }) {
    return (
        <DashboardLayout title="Quizzes">
            <Head title="Quizzes" />
            <div className="p-5 md:p-8 max-w-4xl mx-auto space-y-4">
                <h1 className="text-2xl font-bold">Quizzes</h1>
                {quizzes.map((q) => (
                    <div key={q.id} className="clay-card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">{q.title}</h2>
                            {q.course_title ? <p className="text-sm text-on-surface-variant">{q.course_title}</p> : null}
                            {q.latest_attempt ? (
                                <p className="text-xs text-primary mt-1">
                                    Last score: {q.latest_attempt.score}% {q.latest_attempt.passed ? '· Passed' : ''}
                                </p>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {q.latest_attempt?.passed ? (
                                <Link href={q.results_url} className="btn-primary btn-sm">
                                    View results
                                </Link>
                            ) : (
                                <Link href={q.take_url} className="btn-primary btn-sm">
                                    {q.latest_attempt ? 'Retry quiz' : 'Take quiz'}
                                </Link>
                            )}
                            {q.latest_attempt && !q.latest_attempt.passed ? (
                                <Link href={q.results_url} className="btn-secondary btn-sm">
                                    Last results
                                </Link>
                            ) : null}
                        </div>
                    </div>
                ))}
            </div>
        </DashboardLayout>
    );
}
