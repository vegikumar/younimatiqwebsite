import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';

export default function Index({ contactMessages = { data: [], links: [] } }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedMessage, setSelectedMessage] = useState(null);
    const rows = contactMessages.data || [];

    const openMessage = (message) => {
        setSelectedMessage(message);
        setModalOpen(true);
    };

    return (
        <DashboardLayout title="Contact Forms">
            <Head title="Contact Forms" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Inbox"
                    title="Contact form submissions"
                    description="Messages submitted through the public contact form."
                />

                <PortalCard padding={false}>
                    <DataTableShell
                        className="p-1"
                        minWidth="1000px"
                        mobileCards={rows.map((message) => (
                            <div key={message.id} className="staff-mobile-card min-w-0 space-y-1">
                                <p className="font-bold break-words">{message.name}</p>
                                <p className="text-xs text-on-surface-variant truncate">{message.email}</p>
                                <p className="text-sm font-medium break-words">{message.subject || 'N/A'}</p>
                                <button type="button" onClick={() => openMessage(message)} className="text-xs font-bold text-primary">View</button>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead className="bg-surface-container-low">
                                <tr>
                                    <th className="font-bold">Name</th>
                                    <th className="font-bold">Email</th>
                                    <th className="font-bold">Subject</th>
                                    <th className="font-bold max-w-sm">Message</th>
                                    <th className="font-bold">Date Submitted</th>
                                    <th className="font-bold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="p-5 md:p-8 text-center text-on-surface-variant font-medium">
                                            No contact messages found.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((message) => (
                                        <tr key={message.id} className="border-t hover:bg-surface-container-low/60 transition-colors">
                                            <td className="font-bold text-on-surface min-w-0 break-words">{message.name}</td>
                                            <td className="text-on-surface min-w-0"><span className="truncate block max-w-[14rem]">{message.email}</span></td>
                                            <td className="font-medium min-w-0 break-words">{message.subject || 'N/A'}</td>
                                            <td className="text-sm text-on-surface max-w-sm truncate">
                                                {message.message_preview}
                                            </td>
                                            <td className="text-xs text-on-surface-variant">{message.created_at_label}</td>
                                            <td className="text-right">
                                                <button type="button" onClick={() => openMessage(message)} className="btn-primary btn-sm px-3 py-1.5 rounded-lg text-xs">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </DataTableShell>
                    {contactMessages.links?.length > 3 ? (
                        <div className="p-4 border-t flex flex-wrap gap-2">
                            {contactMessages.links.map((link, i) =>
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

            {modalOpen && selectedMessage ? (
                <div className="fixed inset-0 z-[999] flex items-center justify-center p-4 overflow-y-auto overscroll-contain" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="fixed inset-0 bg-on-surface-variant/75 backdrop-blur-sm" onClick={() => setModalOpen(false)} aria-hidden="true" />
                    <div className="relative w-full max-w-2xl my-auto bg-white rounded-2xl text-left overflow-hidden shadow-2xl border border-outline-variant">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <div className="flex justify-between items-center mb-4">
                                            <h3 className="text-xl leading-6 font-bold text-on-surface" id="modal-title">
                                                Contact Form Submission
                                            </h3>
                                            <button type="button" onClick={() => setModalOpen(false)} className="text-outline hover:text-on-surface transition-colors">
                                                <Icon name="close" />
                                            </button>
                                        </div>
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                                                <div>
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Name</p>
                                                    <p className="font-medium text-on-surface">{selectedMessage.name}</p>
                                                </div>
                                                <div>
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Email</p>
                                                    <p className="font-medium text-on-surface">
                                                        <a href={`mailto:${selectedMessage.email}`} className="text-primary hover:underline">
                                                            {selectedMessage.email}
                                                        </a>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Phone</p>
                                                    <p className="font-medium text-on-surface">
                                                        <a href={`tel:${selectedMessage.phone}`} className="text-primary hover:underline">
                                                            {selectedMessage.phone}
                                                        </a>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Location</p>
                                                    <p className="font-medium text-on-surface">{selectedMessage.location}</p>
                                                </div>
                                                <div className="col-span-2">
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Subject</p>
                                                    <p className="font-medium text-on-surface">{selectedMessage.subject}</p>
                                                </div>
                                                <div className="col-span-2">
                                                    <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Date Submitted</p>
                                                    <p className="font-medium text-on-surface">{selectedMessage.date}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Message</p>
                                                <div className="bg-white border border-outline-variant rounded-xl p-4 text-on-surface whitespace-pre-wrap text-sm leading-relaxed">
                                                    {selectedMessage.message}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <div className="bg-surface-container-low px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-outline-variant">
                            <button
                                type="button"
                                className="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-on-surface-variant text-base font-bold text-white hover:bg-on-surface-variant focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                                onClick={() => setModalOpen(false)}
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
