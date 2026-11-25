import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import QuizBuilder from '../../../Components/QuizBuilder';

export default function QuizForm({ mode = 'create', quiz = null, assignedCourseId = null, assignedStudentIds = [] }) {
    const { csrfToken, flash } = usePage().props;
    const formId = mode === 'create' ? 'quiz-create-form' : 'quiz-edit-form';
    const action = mode === 'create' ? '/tutor/quizzes' : `/tutor/quizzes/${quiz.id}`;
    const method = mode === 'create' ? 'POST' : 'PUT';

    return (
        <DashboardLayout title={mode === 'create' ? 'Create Quiz' : 'Edit Quiz'}>
            <Head title={mode === 'create' ? 'Create Quiz' : 'Edit Quiz'} />
            <div className="staff-page max-w-4xl">
                <PageHeader
                    variant="staff"
                    title={mode === 'create' ? 'Create New Quiz' : 'Edit Quiz'}
                    actions={
                        <Link href="/tutor/quizzes" className="text-sm font-bold text-on-surface-variant hover:text-primary">
                            &larr; Back to Quizzes
                        </Link>
                    }
                />
                {flash?.error ? <div className="bg-error-container p-4 rounded-xl font-bold">{flash.error}</div> : null}

                <form id={formId} method="POST" action={action} encType="multipart/form-data" className="space-y-6">
                    <input type="hidden" name="_token" value={csrfToken} />
                    {mode === 'edit' ? <input type="hidden" name="_method" value={method} /> : null}

                    <PortalCard className="border-l-4 border-l-secondary">
                        <p className="text-sm text-on-surface-variant">
                            You can edit quiz content here. Assigning quizzes to students is admin-only.
                            {assignedCourseId || assignedStudentIds?.length
                                ? ' Current assignments are managed from the admin portal.'
                                : ''}
                        </p>
                    </PortalCard>

                    <QuizBuilder initialQuiz={quiz} formId={formId} />
                </form>
            </div>
        </DashboardLayout>
    );
}
