import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortalCard from '../../../Components/Portal/PortalCard';
import Icon from '../../../Components/Icon';

export default function Form({ template }) {
    const { csrfToken, errors = {}, flash } = usePage().props;
    const errorList = Object.values(errors).flat();

    return (
        <DashboardLayout title="Edit Email Template">
            <Head title={`Edit: ${template.name}`} />
            <div className="staff-page min-w-0">
                <PortalCard className="p-5 md:p-8 lg:p-10">
                    <div className="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
                        <div className="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center"><Icon name="mail" className="text-2xl" /></div>
                        <div><h2 className="font-headline-md text-headline-md text-on-surface">Edit: {template.name}</h2><p className="font-body-md text-on-surface-variant">Slug: <code className="text-sm bg-surface-container-low px-2 py-1 rounded">{template.slug}</code></p></div>
                    </div>
                    {flash?.success ? <div className="mb-6 p-4 bg-secondary-container rounded-xl font-bold">{flash.success}</div> : null}
                    {errorList.length ? <div className="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm"><ul className="list-disc list-inside">{errorList.map((e,i)=><li key={i}>{e}</li>)}</ul></div> : null}
                    <form method="POST" action={template.updateUrl} className="space-y-6">
                        <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="_method" value="PUT" />
                        <div><label className="block font-bold mb-2">Template Name</label><input type="text" name="name" defaultValue={template.name} required className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                        <div><label className="block font-bold mb-2">Email Subject</label><input type="text" name="subject" defaultValue={template.subject} required className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /></div>
                        <div><label className="block font-bold mb-2">Email Body</label><textarea name="body" rows={14} required defaultValue={template.body} className="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none font-mono text-sm" /><p className="text-xs text-on-surface-variant mt-2">Use placeholders like {'{{student_name}}'}, {'{{course_title}}'}, {'{{amount}}'}, {'{{reset_url}}'}</p></div>
                        <label className="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="is_active" value="1" defaultChecked={template.is_active} className="w-5 h-5 rounded text-primary focus:ring-primary" /><span className="font-bold">Template is active</span></label>
                        <div className="flex gap-4 pt-4 border-t border-outline-variant"><button type="submit" className="btn-primary">Save Template</button><Link href="/admin/email" className="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">Back</Link></div>
                    </form>
                    <div className="mt-8 pt-8 border-t border-outline-variant">
                        <h3 className="font-bold mb-4">Send Test Email</h3>
                        <form method="POST" action={template.testUrl} className="flex gap-4"><input type="hidden" name="_token" value={csrfToken} /><input type="email" name="email" required placeholder="test@example.com" className="flex-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" /><button type="submit" className="btn-enroll btn-sm">Send Test</button></form>
                    </div>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
