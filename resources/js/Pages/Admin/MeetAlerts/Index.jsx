import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import PaginationLinks from '../../../Components/PaginationLinks';

export default function Index({ alerts = { data: [], links: [] }, unreviewedCount = 0, highlightId = null, filters = {} }) {
    const { csrfToken, flash } = usePage().props;
    const highlightRef = useRef(null);

    useEffect(() => {
        if (highlightRef.current) highlightRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, [highlightId]);

    const rows = alerts.data || [];

    return (
        <DashboardLayout title="Meet Contact Alerts">
            <Head title="Meet Contact Alerts" />
            <div className="staff-page">
                <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                    <div><h1 className="text-2xl font-extrabold text-on-surface tracking-tight" style={{ fontFamily: 'Nunito, system-ui, sans-serif' }}>Meet contact alerts</h1><p className="text-sm text-on-surface-variant mt-1">Instant alerts when tutors or students share contact info in class Meets.</p></div>
                    {unreviewedCount ? <span className="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-tertiary text-white text-xs font-bold">{unreviewedCount} unreviewed</span> : null}
                </div>
                {flash?.success ? <div className="rounded-xl bg-tertiary-fixed text-tertiary px-4 py-3 text-sm font-semibold">{flash.success}</div> : null}
                <form method="GET" className="flex flex-wrap gap-2 items-center">
                    <input type="search" name="q" defaultValue={filters.q || ''} placeholder="Search room, name, text…" className="flex-1 min-w-[180px] rounded-xl border border-outline-variant/40 px-3 py-2 text-sm" />
                    <select name="source" defaultValue={filters.source || ''} className="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm"><option value="">All sources</option><option value="chat">Chat</option><option value="speech">Speech</option><option value="captions">Captions</option></select>
                    <select name="type" defaultValue={filters.type || ''} className="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm"><option value="">All types</option><option value="email">Email</option><option value="phone">Phone</option><option value="messaging_link">Messaging link</option></select>
                    <label className="inline-flex items-center gap-2 text-sm px-2"><input type="checkbox" name="unreviewed" value="1" defaultChecked={filters.unreviewed} /> Unreviewed only</label>
                    <button type="submit" className="btn-primary btn-sm">Filter</button>
                </form>
                <PortalCard className="bg-white overflow-hidden"><div className="divide-y divide-outline-variant/20">
                    {rows.length === 0 ? <div className="p-8 text-center text-on-surface-variant text-sm">No contact alerts yet.</div> : rows.map((alert) => (
                        <div key={alert.id} id={`alert-${alert.id}`} ref={highlightId === alert.id ? highlightRef : null} className={`p-5 space-y-2 ${highlightId === alert.id ? 'bg-warning-container' : ''} ${alert.reviewedAt ? 'opacity-80' : ''}`}>
                            <div className="flex flex-col md:flex-row md:items-start justify-between gap-3">
                                <div className="space-y-1"><div className="flex flex-wrap items-center gap-2"><span className="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-secondary-container">{alert.typeLabel}</span><span className="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface-container-low">{alert.sourceLabel}</span>{!alert.reviewedAt ? <span className="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-warning-container text-warning">New</span> : null}</div>
                                    <p className="font-bold text-on-surface">{alert.actorName || 'Unknown'} <span className="font-normal text-on-surface-variant text-sm">({alert.actorRole || 'unknown'})</span></p>
                                    <p className="text-xs text-on-surface-variant">{alert.occurredAtLabel} · Room <code className="text-[11px]">{alert.roomId}</code></p></div>
                                {!alert.reviewedAt ? <form method="POST" action={alert.reviewUrl}><input type="hidden" name="_token" value={csrfToken} /><button className="btn btn-outline-primary btn-sm">Mark reviewed</button></form> : <p className="text-xs text-on-surface-variant">Reviewed {alert.reviewedAtHuman}</p>}
                            </div>
                            <div className="rounded-xl bg-surface-container-low/60 px-3 py-2 text-sm">{(alert.matches || []).length ? alert.matches.map((m,i)=><p key={i} className="font-semibold"><span className="text-on-surface-variant font-medium uppercase">{m.type}:</span> {m.value || '—'}</p>) : <p className="font-semibold">{alert.typeLabel} detected</p>}</div>
                            <p className="text-sm text-on-surface"><span className="text-on-surface-variant text-xs font-bold uppercase">Context</span><br />{alert.sharedText}</p>
                        </div>
                    ))}
                </div></PortalCard>
                <PaginationLinks links={alerts.links} />
            </div>
        </DashboardLayout>
    );
}
