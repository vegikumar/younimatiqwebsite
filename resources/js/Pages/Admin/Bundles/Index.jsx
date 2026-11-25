import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import FormField from '../../../Components/Portal/FormField';

export default function Index({ bundles = [], courses = [] }) {
    const { csrfToken, flash } = usePage().props;

    return (
        <DashboardLayout title="Course Bundles">
            <Head title="Course Bundles" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Growth"
                    title="Course bundles"
                    description="Package 2+ courses with a shared discount. Bundles surface on checkout for included courses."
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold break-words">{flash.success}</div> : null}

                <PortalCard title="New bundle">
                    <form method="POST" action="/admin/bundles" className="staff-form">
                        <input type="hidden" name="_token" value={csrfToken} />

                        <FormField label="Bundle name" name="name" required>
                            <input id="name" name="name" required placeholder="e.g. AI Starter Pack" className="form-input" />
                        </FormField>

                        <FormField label="Description" name="description" hint="Short pitch shown with the bundle.">
                            <textarea id="description" name="description" rows={2} placeholder="Short description" className="form-input break-words" />
                        </FormField>

                        <FormField label="Discount percent" name="discount_percent" required hint="Between 1 and 50.">
                            <input
                                id="discount_percent"
                                name="discount_percent"
                                type="number"
                                min={1}
                                max={50}
                                defaultValue={10}
                                required
                                className="form-input"
                            />
                        </FormField>

                        <FormField
                            label="Courses"
                            name="course_ids"
                            required
                            hint="Hold Ctrl/Cmd to multi-select. Pick at least 2."
                        >
                            <select
                                id="course_ids"
                                name="course_ids[]"
                                multiple
                                required
                                size={Math.min(10, Math.max(6, courses.length || 6))}
                                className="staff-multiselect"
                            >
                                {courses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.title}
                                    </option>
                                ))}
                            </select>
                        </FormField>

                        <div className="staff-form-actions">
                            <label className="staff-check">
                                <input type="checkbox" name="is_active" value="1" defaultChecked />
                                <span>Active</span>
                            </label>
                            <button type="submit" className="btn-primary">
                                Create bundle
                            </button>
                        </div>
                    </form>
                </PortalCard>

                <div className="space-y-4 min-w-0">
                    {bundles.map((b) => (
                        <PortalCard key={b.id}>
                            <div className="space-y-3 min-w-0">
                                <div className="flex flex-wrap justify-between gap-2 min-w-0">
                                    <h3 className="font-bold text-lg break-words min-w-0">
                                        {b.name}{' '}
                                        <span className="text-primary">{b.discount_percent}% off</span>
                                    </h3>
                                    <span className="text-xs font-bold uppercase shrink-0 self-start px-2 py-1 rounded-lg bg-surface-container-low">
                                        {b.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                {b.description ? <p className="text-sm text-on-surface-variant break-words">{b.description}</p> : null}
                                <p className="text-sm break-words">{(b.courses || []).map((c) => c.title).join(' · ') || 'No courses linked'}</p>
                                <form
                                    method="POST"
                                    action={`/admin/bundles/${b.id}`}
                                    className="pt-1"
                                    onSubmit={(e) => {
                                        if (!confirm('Delete this bundle?')) e.preventDefault();
                                    }}
                                >
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input type="hidden" name="_method" value="DELETE" />
                                    <button type="submit" className="text-sm font-bold text-error hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </PortalCard>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
