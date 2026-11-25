import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

function LinkCard({ item, badge = null }) {
    const [copied, setCopied] = useState(false);
    const shareUrl = item.share_url || item.url || item.preview_url;

    const copyLink = async () => {
        if (!shareUrl) return;
        try {
            await navigator.clipboard.writeText(shareUrl);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1600);
        } catch {
            window.prompt('Copy this link:', shareUrl);
        }
    };

    return (
        <PortalCard className="p-5 space-y-3">
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                    <h3 className="font-extrabold text-lg break-words">{item.title}</h3>
                    {badge ? <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-secondary/15 text-secondary">{badge}</span> : null}
                </div>
                <p className="text-sm text-on-surface-variant break-words">{item.summary}</p>
                {item.subject || item.grade ? (
                    <p className="mt-1 text-xs text-on-surface-variant">
                        {[item.subject, item.grade].filter(Boolean).join(' · ')}
                    </p>
                ) : null}
                {shareUrl ? <p className="mt-1 text-xs text-on-surface-variant break-all">{shareUrl}</p> : null}
            </div>
            <div className="flex flex-wrap gap-2">
                <button type="button" onClick={copyLink} className="px-3 py-2 border rounded-lg text-sm font-bold">
                    {copied ? 'Copied!' : 'Copy link'}
                </button>
                <a href={item.preview_url || shareUrl} target="_blank" rel="noreferrer" className="px-3 py-2 border rounded-lg text-sm font-bold">
                    Open / Preview
                </a>
            </div>
        </PortalCard>
    );
}

export default function Index({ assigned = [], games = [], activityLinks = [] }) {
    const { flash } = usePage().props;

    return (
        <DashboardLayout title="Activity Links">
            <Head title="Activity Links & Games" />
            <div className="staff-page space-y-6">
                <PageHeader
                    variant="staff"
                    eyebrow="Resources"
                    title="Activity links & games"
                    description="Open activities assigned to you, or use any share link while signed in as a tutor. Only admin assigns these."
                />

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium">{flash.success}</div> : null}
                {flash?.error ? <div className="p-4 bg-error-container rounded-xl font-medium">{flash.error}</div> : null}

                <section className="space-y-4">
                    <h2 className="text-lg font-extrabold flex items-center gap-2">
                        <Icon name="assignment_ind" className="text-primary" /> Assigned to you
                    </h2>
                    {assigned.length ? (
                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            {assigned.map((item) => (
                                <LinkCard key={`a-${item.key}`} item={item} badge="Assigned" />
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-on-surface-variant">
                            Nothing assigned yet. You can still open activities via a share link from admin.
                        </p>
                    )}
                </section>

                <section className="space-y-4">
                    <h2 className="text-lg font-extrabold flex items-center gap-2">
                        <Icon name="link" className="text-primary" /> All activity links (open by link)
                    </h2>
                    <p className="text-sm text-on-surface-variant">
                        Any published link works when you are signed in as a tutor — assignment is optional.
                    </p>
                    {activityLinks.length ? (
                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            {activityLinks.map((item) => (
                                <LinkCard key={`l-${item.key}`} item={item} />
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-on-surface-variant">No published activity links yet.</p>
                    )}
                </section>

                <section className="space-y-4">
                    <h2 className="text-lg font-extrabold flex items-center gap-2">
                        <Icon name="sports_esports" className="text-primary" /> Classroom games
                    </h2>
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        {games.map((game) => (
                            <LinkCard key={`g-${game.key}`} item={game} />
                        ))}
                    </div>
                </section>
            </div>
        </DashboardLayout>
    );
}
