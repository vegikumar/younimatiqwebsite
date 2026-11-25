import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Email({ settings = {} }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="SMTP Settings">
            <Head title="SMTP Settings" />
            <div className="staff-page min-w-0 space-y-6">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                            <Icon name="outgoing_mail" className="text-2xl" />
                        </div>
                        <div>
                            <h2 className="font-headline-md text-on-surface">SMTP & Email Settings</h2>
                            <p className="text-on-surface-variant">
                                Admissions mailbox for student/parent ops. Support mailbox for helpdesk &amp; promotional mail.
                            </p>
                        </div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    {flash?.error ? <div className="mb-6 p-4 bg-error-container rounded-xl font-bold">{flash.error}</div> : null}

                    <form method="POST" action="/admin/settings" className="space-y-10">
                        <input type="hidden" name="_token" value={csrfToken} />

                        <section className="space-y-4">
                            <div>
                                <h3 className="font-bold text-lg">Admissions / student ops</h3>
                                <p className="text-sm text-on-surface-variant">
                                    Used for activity assignments, enrollments, schedules, and parent/student notes. From:{' '}
                                    <strong>admission@younimatiq.com</strong>
                                </p>
                            </div>
                            <div className="grid md:grid-cols-2 gap-4">
                                <input type="text" name="mail_host" defaultValue={settings.mail_host ?? 'smtp.gmail.com'} placeholder="SMTP Host" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="number" name="mail_port" defaultValue={settings.mail_port ?? '587'} placeholder="SMTP Port" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="text" name="mail_username" defaultValue={settings.mail_username ?? 'admission@younimatiq.com'} placeholder="SMTP Username" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="password" name="mail_password" defaultValue={settings.mail_password ?? ''} placeholder="SMTP App Password" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            </div>
                            <select name="mail_encryption" defaultValue={settings.mail_encryption ?? 'tls'} className="w-full p-4 bg-surface-container-low border rounded-xl">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                            <div className="grid md:grid-cols-2 gap-4">
                                <input type="email" name="mail_from_address" defaultValue={settings.mail_from_address ?? 'admission@younimatiq.com'} placeholder="From Email" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="text" name="mail_from_name" defaultValue={settings.mail_from_name ?? 'Younimatiq Admissions'} placeholder="From Name" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            </div>
                        </section>

                        <section className="space-y-4 border-t border-outline-variant pt-8">
                            <div>
                                <h3 className="font-bold text-lg">Support / promotional</h3>
                                <p className="text-sm text-on-surface-variant">
                                    Used for support replies and promotional mail. From: <strong>support@younimatiq.com</strong>
                                </p>
                            </div>
                            <div className="grid md:grid-cols-2 gap-4">
                                <input type="text" name="support_mail_host" defaultValue={settings.support_mail_host ?? settings.mail_host ?? 'smtp.gmail.com'} placeholder="SMTP Host" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="number" name="support_mail_port" defaultValue={settings.support_mail_port ?? settings.mail_port ?? '587'} placeholder="SMTP Port" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="text" name="support_mail_username" defaultValue={settings.support_mail_username ?? 'support@younimatiq.com'} placeholder="SMTP Username" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="password" name="support_mail_password" defaultValue={settings.support_mail_password ?? ''} placeholder="SMTP App Password" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            </div>
                            <select name="support_mail_encryption" defaultValue={settings.support_mail_encryption ?? settings.mail_encryption ?? 'tls'} className="w-full p-4 bg-surface-container-low border rounded-xl">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                            <div className="grid md:grid-cols-2 gap-4">
                                <input type="email" name="support_mail_from_address" defaultValue={settings.support_mail_from_address ?? 'support@younimatiq.com'} placeholder="From Email" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                                <input type="text" name="support_mail_from_name" defaultValue={settings.support_mail_from_name ?? 'Younimatiq Support'} placeholder="From Name" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                            </div>
                        </section>

                        <input type="email" name="admin_email" defaultValue={settings.admin_email ?? ''} placeholder="Admin Notification Email" className="w-full p-4 bg-surface-container-low border rounded-xl" />
                        <button type="submit" className="btn-primary">Save Email Settings</button>
                    </form>

                    <div className="mt-12 pt-8 border-t border-outline-variant">
                        <h3 className="font-bold text-lg text-primary mb-4">Send Test Email (Admissions SMTP)</h3>
                        <form method="POST" action="/admin/settings/email/test" className="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="email" name="test_email" required placeholder="Enter email to test..." className="flex-1 p-4 bg-surface-container-low border rounded-xl" />
                            <button type="submit" className="btn-enroll px-8 whitespace-nowrap">Send Test</button>
                        </form>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
