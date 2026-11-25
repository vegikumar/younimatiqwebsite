import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Show({ feedback }) {
    const { flash, csrfToken } = usePage().props;
    const isDemo = feedback.sessionType === 'demo';
    const parentEmails = feedback.parentEmails || [];

    return (
        <DashboardLayout title="Feedback Detail">
            <Head title="Feedback Review" />
            <div className="staff-page">
                <div className="mb-8 flex items-center gap-4">
                    <Link href="/admin/feedback" className="text-primary hover:underline font-bold text-sm flex items-center gap-1">
                        <Icon name="arrow_back" className="text-sm" /> Back to Feedback
                    </Link>
                </div>
                {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                {flash?.error ? <div className="mb-6 p-4 bg-error-container text-on-error-container rounded-xl font-bold">{flash.error}</div> : null}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 md:p-8">
                    <div className="lg:col-span-2 space-y-6">
                        <PortalCard className={`p-5 md:p-8 border-t-8 ${isDemo ? 'border-warning' : 'border-primary'}`}>
                            <div className="mb-6 border-b border-outline-variant/30 pb-4 flex flex-wrap items-center justify-between gap-3">
                                <h2 className="font-headline-md text-2xl text-on-surface">{isDemo ? 'Demo Class Feedback' : 'Regular Class Feedback'}</h2>
                                {feedback.sharedWithParentAt ? (
                                    <span className="px-3 py-1 rounded-lg text-xs font-bold bg-secondary-container text-on-secondary-container">
                                        Sent to parent · {feedback.sharedWithParentAt}
                                    </span>
                                ) : null}
                            </div>
                            <div className="space-y-6">
                                {feedback.feedbackItems.map((item, i) => (
                                    <div key={i} className="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20">
                                        <h4 className="text-sm font-bold text-on-surface-variant uppercase tracking-wider mb-2">{item.question}</h4>
                                        <p className="text-on-surface text-lg">{item.answer}</p>
                                    </div>
                                ))}
                            </div>
                        </PortalCard>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <PortalCard className="p-6">
                                <h3 className="font-bold text-lg mb-4 text-on-surface flex items-center gap-2"><Icon name="person" className="text-primary" /> Student Info</h3>
                                <p className="text-sm text-on-surface-variant mb-1">Name: <span className="font-bold text-on-surface">{feedback.student.name}</span></p>
                                <p className="text-sm text-on-surface-variant mb-1">Email: <span className="font-bold text-on-surface">{feedback.student.email}</span></p>
                                {feedback.student.profileUrl ? <Link href={feedback.student.profileUrl} className="text-primary text-xs font-bold hover:underline">View Profile →</Link> : null}
                            </PortalCard>
                            <PortalCard className="p-6">
                                <h3 className="font-bold text-lg mb-4 text-on-surface flex items-center gap-2"><Icon name="school" className="text-primary" /> Tutor Info</h3>
                                <p className="text-sm text-on-surface-variant mb-1">Name: <span className="font-bold text-on-surface">{feedback.tutor.name}</span></p>
                                <p className="text-sm text-on-surface-variant mb-1">Email: <span className="font-bold text-on-surface">{feedback.tutor.email}</span></p>
                            </PortalCard>
                        </div>
                    </div>
                    <div className="lg:col-span-1 space-y-6">
                        <PortalCard className="p-6 bg-primary-container text-on-primary-container">
                            <h3 className="font-headline-md text-xl font-bold mb-4 flex items-center gap-2"><Icon name="gavel" /> Admin Decision</h3>
                            <p className="text-sm mb-6 opacity-90">Determine the customer type and leave internal notes for follow-up actions.</p>
                            <form action={feedback.updateUrl} method="POST" className="space-y-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="_method" value="PUT" />
                                <div>
                                    <label className="block font-bold mb-2 text-sm">Customer Type / Lead Status</label>
                                    <select name="customer_type" defaultValue={feedback.customerType || ''} className="w-full bg-white text-on-surface border-2 border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary transition-colors">
                                        <option value="">-- Select Status --</option>
                                        <option value="Hot Lead">🔥 Hot Lead</option>
                                        <option value="Warm Lead">☀️ Warm Lead</option>
                                        <option value="Follow Up Required">📅 Follow Up Required</option>
                                        <option value="Not Interested">🧊 Not Interested</option>
                                        <option value="Enrolled">✅ Enrolled</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block font-bold mb-2 text-sm">Internal Admin Notes</label>
                                    <textarea name="admin_notes" rows={4} defaultValue={feedback.adminNotes || ''} className="w-full bg-white text-on-surface border-2 border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Notes for sales team or follow-up plan..." />
                                </div>
                                <button type="submit" className="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-sm">Save Decision</button>
                            </form>
                        </PortalCard>

                        {feedback.canSendToParent ? (
                            <PortalCard className="p-6">
                                <h3 className="font-bold text-lg mb-2 text-on-surface flex items-center gap-2">
                                    <Icon name="mail" className="text-primary" /> Send to parent
                                </h3>
                                <p className="text-sm text-on-surface-variant mb-4">
                                    Email this class update to linked parents and show it on their Parent Dashboard. Recent post-class activities for the student are included automatically.
                                </p>
                                {parentEmails.length ? (
                                    <p className="text-xs text-on-surface-variant mb-3 break-words">
                                        Will send to: {parentEmails.join(', ')}
                                    </p>
                                ) : (
                                    <p className="text-xs text-error mb-3 font-bold">
                                        No parent linked yet. Link a parent on the student profile first.
                                    </p>
                                )}
                                <form action={feedback.sendToParentUrl} method="POST" className="space-y-4">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <div>
                                        <label className="block font-bold mb-2 text-sm">Optional note to parent</label>
                                        <textarea
                                            name="parent_message"
                                            rows={4}
                                            defaultValue={feedback.parentMessage || ''}
                                            className="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary transition-colors"
                                            placeholder="What happened in class, homework reminders, encouragement…"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={!parentEmails.length}
                                        className="w-full py-3 bg-tertiary text-white font-bold rounded-xl hover:opacity-90 transition-opacity shadow-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        {feedback.sharedWithParentAt ? 'Resend to parent' : 'Send to parent'}
                                    </button>
                                </form>
                            </PortalCard>
                        ) : (
                            <PortalCard className="p-6">
                                <h3 className="font-bold text-lg mb-2 text-on-surface">Send to parent</h3>
                                <p className="text-sm text-on-surface-variant">
                                    Demo feedback stays internal for sales follow-up. Use regular class feedback (with a linked student) to send parent updates.
                                </p>
                            </PortalCard>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
