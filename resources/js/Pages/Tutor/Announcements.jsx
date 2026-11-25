import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import { formatLocalTime } from '../../utils/formatLocalTime';

export default function Announcements({ courses = [], published = [], scheduled = [], welcomePosts = [] }) {
    const { csrfToken, flash } = usePage().props;
    const [publishOption, setPublishOption] = useState('now');
    const [activeTab, setActiveTab] = useState('published');

    const tabs = {
        published,
        scheduled,
        welcome: welcomePosts,
    };

    return (
        <DashboardLayout title="Announcements">
            <Head title="Announcements" />
            <div className="staff-page">
                <PageHeader variant="staff" title="Announcements & Templated Posts" description="Publish updates, schedule announcements, and manage welcome messages." />
                {flash?.success ? <div className="p-3 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">
                    <PortalCard title="Create Announcement">
                        <form method="POST" action="/tutor/announcements" className="space-y-4 min-w-0">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <select name="course_id" required className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm">
                                <option value="">Select Class Section</option>
                                {courses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.title}
                                    </option>
                                ))}
                            </select>
                            <input type="text" name="title" required placeholder="Title" className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm" />
                            <textarea name="content" required rows={4} placeholder="Announcement body" className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm" />
                            <input type="url" name="video_url" placeholder="Video URL (optional)" className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm" />
                            <div className="flex flex-wrap gap-4 text-sm font-bold">
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="publish_option" value="now" checked={publishOption === 'now'} onChange={() => setPublishOption('now')} /> Publish now
                                </label>
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="publish_option" value="schedule" checked={publishOption === 'schedule'} onChange={() => setPublishOption('schedule')} /> Schedule
                                </label>
                            </div>
                            {publishOption === 'schedule' ? <input type="datetime-local" name="scheduled_at" required className="w-full min-w-0 px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm" /> : null}
                            <label className="flex items-center gap-2 text-sm font-bold">
                                <input type="checkbox" name="is_welcome_post" value="1" /> Welcome post for new enrollments
                            </label>
                            <button type="submit" className="btn-enroll w-full py-3 text-sm font-bold">
                                Submit Post
                            </button>
                        </form>
                    </PortalCard>
                    <div className="md:col-span-2 min-w-0">
                        <PortalCard title="Your posts">
                            <div className="flex flex-wrap gap-2 mb-4">
                                {[
                                    ['published', 'Published'],
                                    ['scheduled', 'Scheduled'],
                                    ['welcome', 'Welcome Posts'],
                                ].map(([key, label]) => (
                                    <button key={key} type="button" onClick={() => setActiveTab(key)} className={`px-4 py-2 rounded-xl text-xs font-bold ${activeTab === key ? 'bg-primary text-white' : 'bg-surface-container-low'}`}>
                                        {label}
                                    </button>
                                ))}
                            </div>
                            <div className="space-y-3">
                                {(tabs[activeTab] || []).map((post) => (
                                    <div key={post.id} className="border border-outline-variant/30 rounded-2xl p-4 min-w-0">
                                        <div className="flex justify-between gap-2 min-w-0">
                                            <div className="min-w-0">
                                                <h3 className="font-bold break-words">{post.title}</h3>
                                                <p className="text-xs text-on-surface-variant break-words">{post.courseTitle}</p>
                                            </div>
                                            <form method="POST" action={`/tutor/announcements/${post.id}`} onSubmit={(e) => !confirm('Delete?') && e.preventDefault()} className="shrink-0">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="DELETE" />
                                                <button type="submit" className="text-xs text-error font-bold">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                        <p className="text-sm mt-2 whitespace-pre-wrap break-words">{post.content}</p>
                                        <p className="text-[10px] text-on-surface-variant mt-2">
                                            {post.scheduledAt ? `Scheduled: ${formatLocalTime(post.scheduledAt)}` : post.publishedAt ? `Published: ${formatLocalTime(post.publishedAt)}` : ''}
                                        </p>
                                    </div>
                                ))}
                                {!tabs[activeTab]?.length ? <p className="text-sm text-on-surface-variant">No posts in this tab.</p> : null}
                            </div>
                        </PortalCard>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
