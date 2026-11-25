import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function ParentShow({ feedback }) {
    const { flash, csrfToken } = usePage().props;
    const submitterLabel = feedback.submitterLabel || 'Parent';
    const backUrl = feedback.backUrl || '/admin/feedback?type=parent';
    const submitter = feedback.submitter || feedback.parent || { name: '—', email: '' };

    return (
        <DashboardLayout title={`${submitterLabel} Feedback`}>
            <Head title={`${submitterLabel} Feedback Detail`} />
            <div className="staff-page">
                <div className="mb-8 flex items-center gap-4">
                    <Link href={backUrl} className="text-primary hover:underline font-bold text-sm flex items-center gap-1">
                        <Icon name="arrow_back" className="text-sm" /> Back to {submitterLabel} Feedback
                    </Link>
                </div>
                {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:p-8">
                    <div className="lg:col-span-2 space-y-6">
                        <PortalCard className="p-5 md:p-8 border-t-8 border-tertiary">
                            <div className="mb-6 border-b border-outline-variant/30 pb-4 flex flex-wrap items-center justify-between gap-3">
                                <h2 className="font-headline-md text-2xl text-on-surface">{submitterLabel} Class Feedback</h2>
                                <span className="px-3 py-1 rounded-lg text-sm font-bold bg-primary-fixed text-primary">{feedback.rating}/5</span>
                            </div>
                            <div className="space-y-4">
                                <div className="bg-surface-container-low p-4 rounded-xl">
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Class experience</h4>
                                    <p className="text-lg">{feedback.classExperience}</p>
                                </div>
                                <div className="bg-surface-container-low p-4 rounded-xl">
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Tutor communication</h4>
                                    <p className="text-lg">{feedback.tutorCommunication}</p>
                                </div>
                                <div className="bg-surface-container-low p-4 rounded-xl">
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Would recommend</h4>
                                    <p className="text-lg">{feedback.wouldRecommend}</p>
                                </div>
                                {feedback.comments ? (
                                    <div className="bg-surface-container-low p-4 rounded-xl">
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Comments</h4>
                                        <p className="text-lg whitespace-pre-wrap">{feedback.comments}</p>
                                    </div>
                                ) : null}
                                {(feedback.classTitle || feedback.classAtLabel) ? (
                                    <div className="bg-surface-container-low p-4 rounded-xl">
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Related class</h4>
                                        <p className="font-bold">{feedback.classTitle || 'Class'}</p>
                                        {feedback.classAtLabel ? <p className="text-sm text-on-surface-variant mt-1">{feedback.classAtLabel}</p> : null}
                                    </div>
                                ) : null}
                            </div>
                        </PortalCard>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <PortalCard className="p-5">
                                <h3 className="font-bold mb-2 flex items-center gap-2"><Icon name="person" className="text-primary" /> {submitterLabel}</h3>
                                <p className="font-bold">{submitter.name}</p>
                                <p className="text-sm text-on-surface-variant">{submitter.email || '—'}</p>
                            </PortalCard>
                            <PortalCard className="p-5">
                                <h3 className="font-bold mb-2 flex items-center gap-2"><Icon name="school" className="text-primary" /> Student</h3>
                                <p className="font-bold">{feedback.student.name}</p>
                                <p className="text-sm text-on-surface-variant">{feedback.student.email}</p>
                            </PortalCard>
                            <PortalCard className="p-5">
                                <h3 className="font-bold mb-2 flex items-center gap-2"><Icon name="badge" className="text-primary" /> Tutor</h3>
                                <p className="font-bold">{feedback.tutor.name}</p>
                                <p className="text-sm text-on-surface-variant">{feedback.tutor.email || '—'}</p>
                            </PortalCard>
                        </div>
                    </div>

                    <div className="lg:col-span-1">
                        <PortalCard className="p-6 bg-primary-container text-on-primary-container">
                            <h3 className="font-headline-md text-xl font-bold mb-2">Admin notes</h3>
                            <p className="text-sm mb-4 opacity-90">Submitted {feedback.createdAtLabel}{feedback.reviewedAtLabel ? ` · Reviewed ${feedback.reviewedAtLabel}` : ''}</p>
                            <form action={feedback.updateUrl} method="POST" className="space-y-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PUT" />
                                <textarea
                                    name="admin_notes"
                                    rows={6}
                                    defaultValue={feedback.adminNotes || ''}
                                    className="w-full bg-white text-on-surface border-2 border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary"
                                    placeholder="Follow-up notes for ops / tutor coaching…"
                                />
                                <button type="submit" className="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90">Save notes</button>
                            </form>
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
