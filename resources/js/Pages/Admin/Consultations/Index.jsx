import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import { formatLocalTime } from '../../../utils/formatLocalTime';

function DetailField({ label, children }) {
    if (children === null || children === undefined || children === '') {
        return (
            <div>
                <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">{label}</p>
                <p className="font-medium text-on-surface-variant">—</p>
            </div>
        );
    }

    return (
        <div>
            <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">{label}</p>
            <div className="font-medium text-on-surface break-words">{children}</div>
        </div>
    );
}

export default function ConsultationsIndex({ consultations = { data: [] } }) {
    const rows = consultations.data ?? consultations;
    const [selected, setSelected] = useState(null);

    const openDetails = (row) => setSelected(row);
    const closeDetails = () => setSelected(null);

    return (
        <DashboardLayout title="Consultations">
            <Head title="Consultation Forms" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Operations"
                    title="Consultation forms"
                    description="Parent consultation submissions and scheduled sessions."
                />
                <PortalCard padding={false}>
                    <DataTableShell
                        className="p-1"
                        minWidth="1100px"
                        mobileCards={rows.map((c) => (
                            <div key={c.id} className="staff-mobile-card min-w-0 space-y-2">
                                <p className="font-bold break-words">{c.parentName}</p>
                                <p className="text-xs text-on-surface-variant truncate">{c.email}</p>
                                <p className="text-sm">{c.childName} · {c.programLabel}</p>
                                <p className="text-xs text-on-surface-variant">{c.scheduledAt ? formatLocalTime(c.scheduledAt) : 'Not scheduled'}</p>
                                <div className="flex flex-wrap gap-3 pt-1">
                                    <button type="button" onClick={() => openDetails(c)} className="text-xs font-bold text-primary">
                                        View details
                                    </button>
                                    {c.zoomJoinUrl ? (
                                        <a href={c.zoomJoinUrl} target="_blank" rel="noreferrer" className="text-xs font-bold text-primary">
                                            Join Zoom
                                        </a>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left">
                            <thead className="bg-surface-container-low">
                                <tr>
                                    <th className="font-bold">Parent & Contact</th>
                                    <th className="font-bold">Child</th>
                                    <th className="font-bold">Program</th>
                                    <th className="font-bold">Scheduled</th>
                                    <th className="font-bold">Zoom</th>
                                    <th className="font-bold">Submitted</th>
                                    <th className="font-bold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((c) => (
                                    <tr key={c.id} className="border-t hover:bg-surface-container-low/60 transition-colors">
                                        <td className="min-w-0">
                                            <span className="font-bold break-words">{c.parentName}</span>
                                            <br />
                                            <span className="text-xs text-on-surface-variant break-words">
                                                {c.email}
                                                <br />
                                                {c.phone || '—'}
                                            </span>
                                        </td>
                                        <td className="min-w-0 break-words">
                                            {c.childName}
                                            <br />
                                            <span className="text-xs">{c.age}</span>
                                        </td>
                                        <td className="font-bold min-w-0 break-words">{c.programLabel}</td>
                                        <td className="text-sm">{c.scheduledAt ? formatLocalTime(c.scheduledAt) : '—'}</td>
                                        <td>
                                            {c.zoomJoinUrl ? (
                                                <a href={c.zoomJoinUrl} target="_blank" rel="noreferrer" className="text-xs font-bold text-white px-3 py-1.5 rounded-lg bg-[#2D8CFF]">
                                                    Join Zoom
                                                </a>
                                            ) : (
                                                <span className="text-xs">Pending</span>
                                            )}
                                        </td>
                                        <td className="text-sm">{formatLocalTime(c.createdAt, 'date')}</td>
                                        <td className="text-right">
                                            <button
                                                type="button"
                                                onClick={() => openDetails(c)}
                                                className="btn-primary btn-sm px-3 py-1.5 rounded-lg text-xs"
                                            >
                                                View details
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {!rows.length ? (
                                    <tr>
                                        <td colSpan={7} className="p-8 text-center text-on-surface-variant">
                                            No consultation submissions yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </DataTableShell>
                    {consultations.links?.length > 3 ? (
                        <div className="p-4 border-t flex flex-wrap gap-2">
                            {consultations.links.map((link, i) =>
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`px-3 py-1 rounded-lg text-sm ${link.active ? 'bg-primary text-white font-bold' : 'border hover:bg-surface-container-low'}`}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span key={i} className="px-3 py-1 text-sm text-on-surface-variant" dangerouslySetInnerHTML={{ __html: link.label }} />
                                ),
                            )}
                        </div>
                    ) : null}
                </PortalCard>
            </div>

            {selected ? (
                <div className="fixed inset-0 z-[999] flex items-center justify-center p-4 overflow-y-auto overscroll-contain" aria-labelledby="consultation-details-title" role="dialog" aria-modal="true">
                    <div className="fixed inset-0 bg-on-surface-variant/75 backdrop-blur-sm" onClick={closeDetails} aria-hidden="true" />
                    <div className="relative w-full max-w-2xl my-auto bg-white rounded-2xl text-left overflow-hidden shadow-2xl border border-outline-variant">
                        <div className="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div className="flex justify-between items-center mb-4 gap-3">
                                <h3 className="text-xl leading-6 font-bold text-on-surface" id="consultation-details-title">
                                    Consultation details
                                </h3>
                                <button type="button" onClick={closeDetails} className="text-outline hover:text-on-surface transition-colors">
                                    <Icon name="close" />
                                </button>
                            </div>

                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                                    <DetailField label="Parent / Contact">{selected.parentName}</DetailField>
                                    <DetailField label="Parent email">
                                        {selected.email ? (
                                            <a href={`mailto:${selected.email}`} className="text-primary hover:underline">
                                                {selected.email}
                                            </a>
                                        ) : null}
                                    </DetailField>
                                    <DetailField label="Phone">
                                        {selected.phone ? (
                                            <a href={`tel:${selected.phone}`} className="text-primary hover:underline">
                                                {selected.phone}
                                            </a>
                                        ) : null}
                                    </DetailField>
                                    <DetailField label="WhatsApp">
                                        {selected.whatsappNumber ? (
                                            <a href={`https://wa.me/${String(selected.whatsappNumber).replace(/\D/g, '')}`} target="_blank" rel="noreferrer" className="text-primary hover:underline">
                                                {selected.whatsappNumber}
                                            </a>
                                        ) : null}
                                    </DetailField>
                                    <DetailField label="Child name">{selected.childName}</DetailField>
                                    <DetailField label="Age & details">{selected.age}</DetailField>
                                    <DetailField label="Program / course">{selected.programLabel}</DetailField>
                                    <DetailField label="Location">
                                        {[selected.state, selected.country].filter(Boolean).join(', ') || null}
                                    </DetailField>
                                    <DetailField label="Timezone">{selected.timezoneLabel || selected.timezone}</DetailField>
                                    <DetailField label="Submitted">{selected.createdAt ? formatLocalTime(selected.createdAt) : null}</DetailField>
                                    <DetailField label="Scheduled (parent timezone)">{selected.scheduledParentLabel}</DetailField>
                                    <DetailField label="Scheduled (IST)">{selected.scheduledIstLabel}</DetailField>
                                    <DetailField label="Zoom passcode">{selected.zoomPasscode}</DetailField>
                                    <DetailField label="Your local time">{selected.scheduledAt ? formatLocalTime(selected.scheduledAt) : null}</DetailField>
                                </div>

                                {(selected.zoomJoinUrl || selected.zoomStartUrl) ? (
                                    <div className="flex flex-wrap gap-2">
                                        {selected.zoomStartUrl ? (
                                            <a
                                                href={selected.zoomStartUrl}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-bold text-white bg-[#0B5CFF]"
                                            >
                                                Start Zoom as Host
                                            </a>
                                        ) : null}
                                        {selected.zoomJoinUrl ? (
                                            <a
                                                href={selected.zoomJoinUrl}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-bold text-white bg-[#2D8CFF]"
                                            >
                                                Join Zoom Meeting
                                            </a>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>
                        </div>
                        <div className="bg-surface-container-low px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-outline-variant">
                            <button
                                type="button"
                                className="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-on-surface-variant text-base font-bold text-white hover:bg-on-surface focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                                onClick={closeDetails}
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}
        </DashboardLayout>
    );
}
