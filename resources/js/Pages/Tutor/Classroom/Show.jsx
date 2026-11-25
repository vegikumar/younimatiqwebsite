import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import RewardModal, { openRewardModal } from '../../../Components/Tutor/RewardModal';
import { formatLocalTime } from '../../../utils/formatLocalTime';

function formatMentionContent(text) {
    if (!text) return '';
    const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return escaped.replace(/@([A-Za-z0-9_\-\.]+)/g, '<span class="font-bold text-primary bg-primary/10 px-1 py-0.5 rounded">@$1</span>');
}

export default function ClassroomShow({ course, discussions = [], recordings = [], enrolledStudents = [], badgeCatalog = [], recentRewards = [], rewardPresets = [] }) {
    const { csrfToken } = usePage().props;
    const [activeTab, setActiveTab] = useState('feed');

    return (
        <DashboardLayout title={course.title}>
            <Head title={`${course.title} - Classroom Hub`} />
            <RewardModal presets={rewardPresets} badges={badgeCatalog} />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Dedicated Classroom Hub"
                    title={course.title}
                    description="Classroom announcements, student discussion feed with @mentions, and post-class recordings."
                    actions={
                        <Link href={course.manageCoursesUrl} className="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1 shrink-0">
                            <span className="material-symbols-outlined text-sm">arrow_back</span> Back to Classes
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">
                    <PortalCard title="Post to Classroom">
                        <form method="POST" action={course.discussionStoreUrl} encType="multipart/form-data" className="space-y-4 min-w-0">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <div className="min-w-0">
                                <label className="block text-sm font-bold text-on-surface mb-1">Message / Post *</label>
                                <textarea name="content" required rows={4} placeholder="Write message to class... Type @LearnerName to notify family!" className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm" />
                            </div>
                            <div className="min-w-0">
                                <label className="block text-sm font-bold text-on-surface mb-1">Attachment (Video up to 1GB, files up to 10MB)</label>
                                <input type="file" name="attachment" className="w-full min-w-0 text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20" />
                            </div>
                            <button type="submit" className="btn-enroll w-full py-3 mt-4">
                                <span className="material-symbols-outlined text-sm">send</span> Post Announcement / Discussion
                            </button>
                        </form>
                        <div className="mt-6 pt-6 border-t border-outline-variant/20">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Enrolled Students in Section</h3>
                            <div className="space-y-2 max-h-48 overflow-y-auto">
                                {enrolledStudents.length ? (
                                    enrolledStudents.map((student) => (
                                        <div key={student.id} className="flex items-center justify-between gap-2 text-xs p-2 bg-surface-container-low rounded-lg min-w-0">
                                            <span className="font-bold text-on-surface truncate">{student.name}</span>
                                            <form method="POST" action={student.messageStartUrl} className="inline shrink-0">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="student_id" value={student.id} />
                                                <input type="hidden" name="course_id" value={student.courseId} />
                                                <button type="submit" className="text-primary font-bold hover:underline flex items-center gap-0.5">
                                                    <span className="material-symbols-outlined text-xs">chat</span> DM
                                                </button>
                                            </form>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-xs text-on-surface-variant">No enrolled students yet.</p>
                                )}
                            </div>
                        </div>
                    </PortalCard>

                    <div className="md:col-span-2 min-w-0">
                        <PortalCard>
                            <div className="flex border-b border-outline-variant/20 mb-6 flex-wrap">
                                <button type="button" onClick={() => setActiveTab('feed')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${activeTab === 'feed' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                                    <span className="material-symbols-outlined text-sm">forum</span> Discussion Feed ({discussions.length})
                                </button>
                                <button type="button" onClick={() => setActiveTab('recordings')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${activeTab === 'recordings' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                                    <span className="material-symbols-outlined text-sm">video_library</span> Post-Class Recordings ({recordings.length})
                                </button>
                                <button type="button" onClick={() => setActiveTab('rewards')} className={`py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2 ${activeTab === 'rewards' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'}`}>
                                    <span className="material-symbols-outlined text-sm">celebration</span> Rewards
                                </button>
                            </div>

                            {activeTab === 'feed' ? (
                                <div className="space-y-6">
                                    {discussions.length ? (
                                        discussions.map((post) => (
                                            <div key={post.id} className="p-6 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-4 min-w-0">
                                                <div className="flex items-center gap-3 min-w-0">
                                                    <div className="w-10 h-10 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm shrink-0">{post.authorInitial}</div>
                                                    <div className="min-w-0">
                                                        <p className="font-bold text-sm text-on-surface truncate">{post.authorName}</p>
                                                        <p className="text-xs text-on-surface-variant">{formatLocalTime(post.createdAt)}</p>
                                                    </div>
                                                </div>
                                                <div className="text-sm text-on-surface leading-relaxed whitespace-pre-line break-words" dangerouslySetInnerHTML={{ __html: formatMentionContent(post.content) }} />
                                                {post.attachments?.length ? (
                                                    <div className="pt-2 flex flex-wrap gap-2">
                                                        {post.attachments.map((att, i) => (
                                                            <div key={i} className="p-3 bg-white rounded-xl border border-outline-variant/20 inline-flex items-center gap-3 text-xs min-w-0 max-w-full">
                                                                <span className="material-symbols-outlined text-primary shrink-0">{att.type === 'video' ? 'movie' : 'description'}</span>
                                                                <div className="min-w-0">
                                                                    <a href={att.url} target="_blank" rel="noreferrer" className="font-bold text-primary hover:underline truncate block">
                                                                        {att.name}
                                                                    </a>
                                                                    <p className="text-[10px] text-on-surface-variant">{((att.size ?? 0) / 1024 / 1024).toFixed(2)} MB</p>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : null}
                                                {post.replies?.length ? (
                                                    <div className="pl-6 border-l-2 border-primary/20 space-y-3 pt-3">
                                                        {post.replies.map((reply) => (
                                                            <div key={reply.id} className="p-3 bg-white rounded-xl border border-outline-variant/10 text-xs space-y-1 min-w-0">
                                                                <div className="flex justify-between gap-2 font-bold text-on-surface min-w-0">
                                                                    <span className="truncate">{reply.authorName}</span>
                                                                    <span className="text-on-surface-variant font-normal text-[10px] shrink-0">{formatLocalTime(reply.createdAt, 'datetime-short')}</span>
                                                                </div>
                                                                <p className="text-on-surface-variant break-words" dangerouslySetInnerHTML={{ __html: formatMentionContent(reply.content) }} />
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : null}
                                                <form method="POST" action={course.discussionStoreUrl} className="flex flex-wrap gap-2 pt-2 min-w-0">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input type="hidden" name="parent_id" value={post.id} />
                                                    <input type="text" name="content" required placeholder="Write a reply... Use @LearnerName" className="flex-1 min-w-[140px] px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none" />
                                                    <button type="submit" className="btn-primary py-2 px-4 text-xs font-bold shrink-0">
                                                        Reply
                                                    </button>
                                                </form>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="py-12 text-center text-on-surface-variant">No classroom posts yet. Share your first announcement or discussion above.</p>
                                    )}
                                </div>
                            ) : null}

                            {activeTab === 'recordings' ? (
                                <div className="space-y-6 min-w-0">
                                    <div className="p-5 bg-surface-container-low border border-outline-variant/20 rounded-2xl space-y-3">
                                        <h4 className="font-bold text-sm text-on-surface flex items-center gap-2">
                                            <span className="material-symbols-outlined text-primary">video_call</span> Publish Post-Class Replay Recording
                                        </h4>
                                        <form method="POST" action={course.recordingStoreUrl} className="space-y-3 min-w-0">
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <input type="text" name="title" required placeholder="Recording Title" className="min-w-0 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none" />
                                                <input type="url" name="recording_url" required placeholder="Video Recording URL" className="min-w-0 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none" />
                                            </div>
                                            <div className="flex justify-end">
                                                <button type="submit" className="btn-enroll py-2 px-5 text-xs font-bold">
                                                    Publish Recording
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {recordings.length ? (
                                            recordings.map((rec) => (
                                                <div key={rec.id} className="p-4 border border-outline-variant/20 rounded-2xl bg-surface-container-low space-y-3 min-w-0">
                                                    <div className="flex justify-between items-start gap-2 min-w-0">
                                                        <h4 className="font-bold text-sm text-on-surface break-words">{rec.title}</h4>
                                                        <form method="POST" action={rec.destroyUrl} onSubmit={(e) => !confirm('Remove recording?') && e.preventDefault()} className="shrink-0">
                                                            <input type="hidden" name="_token" value={csrfToken} />
                                                            <input type="hidden" name="_method" value="DELETE" />
                                                            <button type="submit" className="text-xs text-error font-bold hover:underline">
                                                                Remove
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <p className="text-xs text-on-surface-variant">Recorded: {formatLocalTime(rec.recordedAt, 'date')}</p>
                                                    <a href={rec.recordingUrl} target="_blank" rel="noreferrer" className="btn-primary w-full py-2 text-xs flex items-center justify-center gap-1">
                                                        <span className="material-symbols-outlined text-sm">play_circle</span> Watch Replay
                                                    </a>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="col-span-2 py-8 text-center text-on-surface-variant">No post-class recordings published for this section yet.</div>
                                        )}
                                    </div>
                                </div>
                            ) : null}

                            {activeTab === 'rewards' ? (
                                <div className="space-y-6 min-w-0">
                                    <div className="p-5 bg-gradient-to-r from-primary-container/40 to-tertiary-container/30 border border-outline-variant/20 rounded-2xl">
                                        <h4 className="font-bold text-sm text-on-surface flex items-center gap-2">
                                            <span className="material-symbols-outlined text-primary">celebration</span> Celebrate your learners
                                        </h4>
                                        <p className="text-xs text-on-surface-variant mt-1">Hand out XP compliments, one level-up tip and badges during or after class.</p>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        {enrolledStudents.length ? (
                                            enrolledStudents.map((student) => (
                                                <div key={student.id} className="flex items-center justify-between gap-2 p-3 bg-surface-container-low rounded-2xl border border-outline-variant/20 min-w-0">
                                                    <div className="flex items-center gap-3 min-w-0">
                                                        <div className="w-9 h-9 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm shrink-0">{student.name.charAt(0)}</div>
                                                        <span className="font-bold text-sm text-on-surface truncate">{student.name}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        className="btn-enroll py-2 px-4 text-xs font-bold flex items-center gap-1 shrink-0"
                                                        onClick={() => openRewardModal({ id: student.id, name: student.name, courseId: student.courseId })}
                                                    >
                                                        <span className="material-symbols-outlined text-sm">celebration</span> Celebrate
                                                    </button>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="col-span-2 text-center py-8 text-on-surface-variant">No enrolled students in this course yet.</p>
                                        )}
                                    </div>
                                    {recentRewards.length ? (
                                        <div className="pt-4 border-t border-outline-variant/20">
                                            <h4 className="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Recent rewards in this course</h4>
                                            <div className="space-y-2">
                                                {recentRewards.map((reward, i) => (
                                                    <div key={i} className="flex items-center justify-between gap-2 text-xs p-3 bg-surface-container-low rounded-xl border border-outline-variant/10 min-w-0">
                                                        <div className="min-w-0 break-words">
                                                            <span className="font-bold text-on-surface">{reward.studentName}</span>
                                                            {reward.badgeTitle ? <span className="text-primary font-bold"> · {reward.badgeTitle}</span> : null}
                                                            {reward.complimentLabels?.length ? <span className="text-on-surface-variant"> · {reward.complimentLabels.join(', ')}</span> : null}
                                                        </div>
                                                        <span className="font-bold text-tertiary shrink-0">+{reward.xpAmount} XP</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
