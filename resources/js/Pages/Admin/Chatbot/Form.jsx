import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Form({ training = null, isEdit = false }) {
    const { csrfToken, errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();

    return (
        <DashboardLayout title={isEdit ? 'Edit QA Pair' : 'Add QA Pair'}>
            <Head title={isEdit ? 'Edit QA Pair' : 'Add QA Pair'} />
            <div className="staff-page min-w-0">
                <div className="flex items-center gap-4 mb-8">
                    <Link href="/admin/chatbot" className="p-2 bg-surface-container-high hover:bg-outline-variant text-on-surface rounded-full transition-colors flex items-center justify-center">
                        <Icon name="arrow_back" />
                    </Link>
                    <h1 className="font-headline-xl text-2xl text-on-surface">{isEdit ? 'Edit QA Pair' : 'Add QA Pair'}</h1>
                </div>

                {errorList.length > 0 && (
                    <div className="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
                        <ul className="list-disc pl-5">
                            {errorList.map((error, i) => (
                                <li key={i}>{error}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <PortalCard className="p-5 md:p-8">
                    <form method="POST" action={isEdit ? `/admin/chatbot/${training.id}` : '/admin/chatbot'} className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} />
                        {isEdit ? <input type="hidden" name="_method" value="PUT" /> : null}

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Question</label>
                            <input
                                type="text"
                                name="question"
                                defaultValue={training?.question ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                                placeholder="e.g. How do I reset my password?"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Tags / Keywords</label>
                            <input
                                type="text"
                                name="tags"
                                defaultValue={training?.tags ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                                placeholder="e.g. password, reset, login"
                            />
                            <p className="text-xs text-on-surface-variant">Separate multiple tags with commas.</p>
                        </div>

                        <div className="space-y-2">
                            <label className="block font-bold text-on-surface">Bot Answer</label>
                            <textarea
                                name="answer"
                                rows={5}
                                defaultValue={training?.answer ?? ''}
                                className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                                placeholder="The exact response the bot will give."
                            />
                        </div>

                        <div className="pt-4 border-t border-outline-variant text-right">
                            <button type="submit" className="btn-primary">{isEdit ? 'Save Changes' : 'Add QA Pair'}</button>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
