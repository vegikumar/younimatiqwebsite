import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';

export default function Copilot({ course, coPilotData = [] }) {
    const { csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Co-Pilot">
            <Head title={`Live Session Co-Pilot - ${course.title}`} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Live Session Co-Pilot"
                    title={course.title}
                    description="Pre-class prep drawer: child avatars, parent learning style notes, and last session sticking points."
                    actions={
                        <Link href={course.classroomUrl} className="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
                            <span className="material-symbols-outlined text-sm">arrow_back</span> Classroom Hub
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {coPilotData.length ? (
                        coPilotData.map((child) => (
                            <PortalCard key={child.student_id}>
                                <div className="space-y-4 min-w-0">
                                    <div className="flex items-center gap-4 border-b border-outline-variant/20 pb-4 min-w-0">
                                        <div className="w-12 h-12 rounded-full bg-primary text-white font-bold flex items-center justify-center text-base shrink-0">{child.name?.charAt(0)}</div>
                                        <div className="min-w-0">
                                            <h3 className="font-bold text-base text-on-surface truncate">{child.name}</h3>
                                            <span className="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-[10px] font-bold">Enrolled Learner</span>
                                        </div>
                                    </div>
                                    <div className="space-y-3 text-xs min-w-0">
                                        <div className="p-3 bg-warning-container rounded-xl text-warning border border-warning-container break-words">
                                            <strong className="block mb-1 font-bold text-warning flex items-center gap-1">
                                                <span className="material-symbols-outlined text-xs text-warning">psychology</span> Parent Notes & Learning Style:
                                            </strong>
                                            {child.parent_notes}
                                        </div>
                                        <div className="p-3 bg-surface-container-low rounded-xl text-on-surface break-words">
                                            <strong className="block mb-1 font-bold flex items-center gap-1 text-primary">
                                                <span className="material-symbols-outlined text-xs">warning</span> Last Session Sticking Point:
                                            </strong>
                                            {child.last_sticking_point}
                                        </div>
                                    </div>
                                    <form method="POST" action={course.updateUrl} className="space-y-2 pt-2 border-t border-outline-variant/20 min-w-0">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input type="hidden" name="student_id" value={child.student_id} />
                                        <input type="text" name="last_sticking_point" placeholder="Update sticking point..." className="w-full min-w-0 px-3 py-1.5 bg-surface-container-low border border-outline-variant/30 rounded-lg text-xs outline-none" />
                                        <button type="submit" className="btn-primary w-full py-1.5 text-xs font-bold">
                                            Save Prep Note
                                        </button>
                                    </form>
                                </div>
                            </PortalCard>
                        ))
                    ) : (
                        <div className="col-span-full py-12 text-center text-xs text-on-surface-variant">No enrolled students found in section for Co-Pilot prep.</div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
