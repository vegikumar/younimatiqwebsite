import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';

export default function Create({ bookingId, type, studentName, storeUrl, cancelUrl }) {
    const { csrfToken } = usePage().props;
    const isDemo = type === 'demo';

    return (
        <DashboardLayout title="Submit Feedback">
            <Head title="Submit Class Feedback" />
            <div className="staff-page max-w-3xl">
                <PageHeader
                    variant="staff"
                    title={isDemo ? 'Demo Class Feedback' : 'Class Session Feedback'}
                    description={`Submit your feedback for ${studentName}.`}
                />

                <PortalCard>
                    <form action={storeUrl} method="POST" className="space-y-6 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="type" value={type} />
                        <input type="hidden" name="booking_id" value={bookingId} />

                        {isDemo ? (
                            <>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">Did the student enjoy the demo class? *</label>
                                    <select name="enjoyed_class" required className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                                        <option value="">Select an option</option>
                                        <option value="Very much">Very much</option>
                                        <option value="Somewhat">Somewhat</option>
                                        <option value="Not really">Not really</option>
                                    </select>
                                </div>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">What is the student&apos;s current skill level? *</label>
                                    <select name="skill_level" required className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                                        <option value="">Select an option</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                    </select>
                                </div>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">Recommended Follow-up (Customer Type) *</label>
                                    <select name="customer_type_recommendation" required className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                                        <option value="">Select an option</option>
                                        <option value="Hot Lead">Hot Lead - Very likely to enroll</option>
                                        <option value="Warm Lead">Warm Lead - Might enroll with some follow-up</option>
                                        <option value="Follow Up Required">Follow Up Required - Has specific questions/concerns</option>
                                        <option value="Not Interested">Not Interested - Unlikely to enroll</option>
                                    </select>
                                    <p className="text-xs text-on-surface-variant mt-1 break-words">This helps admins determine how to properly follow up with this parent.</p>
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">Student&apos;s progress today? *</label>
                                    <select name="progress" required className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                                        <option value="">Select an option</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Needs Work">Needs Work</option>
                                    </select>
                                </div>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">Areas of improvement? *</label>
                                    <textarea name="improvement_areas" required rows={3} className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors" />
                                </div>
                                <div className="min-w-0">
                                    <label className="block font-bold mb-2 text-on-surface">General behavior *</label>
                                    <select name="behavior" required className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                                        <option value="">Select an option</option>
                                        <option value="Attentive">Attentive and Engaged</option>
                                        <option value="Distracted">Somewhat Distracted</option>
                                        <option value="Disruptive">Disruptive</option>
                                    </select>
                                </div>
                            </>
                        )}

                        <div className="min-w-0">
                            <label className="block font-bold mb-2 text-on-surface">Additional Notes (Optional)</label>
                            <textarea name="notes" rows={4} className="w-full min-w-0 bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors" placeholder="Any other observations..." />
                        </div>

                        <div className="flex flex-wrap justify-end gap-3 mt-8 border-t border-outline-variant pt-6">
                            <Link href={cancelUrl} className="px-6 py-3 bg-surface-container-low hover:bg-surface-container text-on-surface font-bold rounded-xl transition-colors">
                                Cancel
                            </Link>
                            <button type="submit" className="px-6 py-3 bg-primary hover:bg-primary-container text-white font-bold rounded-xl transition-colors shadow-sm">
                                Submit Feedback
                            </button>
                        </div>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
