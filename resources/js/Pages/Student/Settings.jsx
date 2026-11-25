import { Head, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import FormField from '../../Components/Portal/FormField';
import FormErrors from '../../Components/Portal/FormErrors';
import SubmitButton from '../../Components/Portal/SubmitButton';

export default function Settings({ user }) {
    const { flash } = usePage().props;
    const prefs = user.notification_preferences ?? {};
    const form = useForm({
        password: '',
        password_confirmation: '',
        email_reminders: prefs.email_reminders !== false,
        email_marketing: !!prefs.email_marketing,
    });

    return (
        <DashboardLayout title="Settings">
            <Head title="Settings" />
            <div className="p-5 md:p-8 space-y-6">
                <PageHeader title="Settings" description="Manage your account preferences and security." />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold">{flash.success}</div> : null}

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/student/settings');
                    }}
                >
                    <div className="clay-card overflow-hidden">
                        <div className="p-5 md:p-8 border-b border-surface-container-high space-y-4">
                            <h2 className="font-headline-md font-bold">Password</h2>
                            <FormErrors errors={form.errors} />
                            <div className="space-y-4 max-w-md">
                                <FormField label="New password" name="password" error={form.errors.password} hint="Leave blank to keep your current password.">
                                    <input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Confirm new password" name="password_confirmation" error={form.errors.password_confirmation}>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        className="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                        value={form.data.password_confirmation}
                                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    />
                                </FormField>
                            </div>
                        </div>

                        <div className="p-5 md:p-8 space-y-4">
                            <h2 className="font-headline-md font-bold">Notification preferences</h2>
                            <label className="flex items-center gap-3 cursor-pointer text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.email_reminders}
                                    onChange={(e) => form.setData('email_reminders', e.target.checked)}
                                    className="w-5 h-5 rounded border-surface-container-high text-primary focus:ring-primary"
                                />
                                Email me reminders for upcoming sessions
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.email_marketing}
                                    onChange={(e) => form.setData('email_marketing', e.target.checked)}
                                    className="w-5 h-5 rounded border-surface-container-high text-primary focus:ring-primary"
                                />
                                Email me about new courses and promotions
                            </label>
                            <SubmitButton processing={form.processing} className="mt-2 btn-primary">
                                Save settings
                            </SubmitButton>
                        </div>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
