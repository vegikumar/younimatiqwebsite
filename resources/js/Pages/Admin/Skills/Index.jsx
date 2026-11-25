import { Head, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';

export default function Index({ skills = [], courses = [], categories = [], matchMode = 'suggest', hrEmails = '' }) {
    const { flash, csrfToken } = usePage().props;

    return (
        <DashboardLayout title="Skills">
            <Head title="Skills & Matching" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Tutor matching"
                    title="Skills catalog & requirements"
                    description="Define skills, map them to courses/categories, and choose how matching runs on enroll."
                />
                {flash?.success ? <div className="p-3 rounded-xl bg-secondary-container font-bold break-words">{flash.success}</div> : null}
                {flash?.error ? <div className="p-3 rounded-xl bg-error-container font-bold break-words">{flash.error}</div> : null}

                <PortalCard title="Match mode & HR emails" className="space-y-4">
                    <form method="POST" action="/admin/skills/match-settings" className="space-y-4 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div className="grid sm:grid-cols-3 gap-3">
                            {[
                                { value: 'suggest', label: 'Suggest', desc: 'Rank matches; keep Auto-assign button' },
                                { value: 'auto', label: 'Auto', desc: 'Assign best match when no tutor set' },
                                { value: 'manual', label: 'Manual', desc: 'No auto suggestions on enroll/verify' },
                            ].map((opt) => (
                                <label key={opt.value} className={`p-4 rounded-xl border cursor-pointer min-w-0 ${matchMode === opt.value ? 'border-primary bg-primary/5' : 'border-outline-variant'}`}>
                                    <input type="radio" name="tutor_match_mode" value={opt.value} defaultChecked={matchMode === opt.value} className="mr-2" />
                                    <span className="font-bold">{opt.label}</span>
                                    <p className="text-xs text-on-surface-variant mt-1 break-words">{opt.desc}</p>
                                </label>
                            ))}
                        </div>
                        <input
                            name="hr_review_emails"
                            defaultValue={hrEmails}
                            placeholder="HR review emails (comma-separated)"
                            className="w-full p-3 rounded-xl border bg-surface-container-low min-w-0"
                        />
                        <button type="submit" className="btn-primary">
                            Save matching settings
                        </button>
                    </form>
                </PortalCard>

                <PortalCard>
                    <form method="POST" action="/admin/skills" className="flex flex-wrap gap-3 min-w-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input name="name" required placeholder="New skill name" className="flex-1 min-w-[140px] p-3 rounded-xl border" />
                        <input name="description" placeholder="Description" className="flex-1 min-w-[140px] p-3 rounded-xl border" />
                        <button type="submit" className="px-4 py-2 bg-primary text-white rounded-xl font-bold shrink-0">
                            Add skill
                        </button>
                    </form>
                </PortalCard>

                <PortalCard padding={false}>
                    <DataTableShell
                        className="p-1"
                        minWidth="720px"
                        mobileCards={skills.map((skill) => (
                            <div key={skill.id} className="staff-mobile-card min-w-0 space-y-2">
                                <p className="font-bold break-words">{skill.name}</p>
                                <p className="text-xs text-on-surface-variant">Tutors: {skill.tutors_count} · System: {skill.is_system ? 'Yes' : 'No'}</p>
                            </div>
                        ))}
                    >
                        <table className="staff-table w-full text-left border-collapse">
                            <thead className="bg-surface-container-low">
                                <tr>
                                    <th>Skill</th>
                                    <th>Tutors</th>
                                    <th>System</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {skills.map((skill) => (
                                    <tr key={skill.id} className="border-t border-outline-variant/40">
                                        <td className="min-w-0">
                                            <form method="POST" action={`/admin/skills/${skill.id}`} className="flex gap-2 min-w-0">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="PUT" />
                                                <input name="name" defaultValue={skill.name} className="p-2 rounded-lg border flex-1 min-w-0" />
                                                <button type="submit" className="text-sm font-bold text-primary shrink-0">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                        <td>{skill.tutors_count}</td>
                                        <td>{skill.is_system ? 'Yes' : 'No'}</td>
                                        <td>
                                            {!skill.is_system ? (
                                                <form method="POST" action={`/admin/skills/${skill.id}`} onSubmit={(e) => !confirm('Delete?') && e.preventDefault()}>
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input type="hidden" name="_method" value="DELETE" />
                                                    <button type="submit" className="text-error text-sm font-bold">
                                                        Delete
                                                    </button>
                                                </form>
                                            ) : null}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </DataTableShell>
                </PortalCard>

                <PortalCard title="Map required skills on a course">
                    <form
                        method="POST"
                        action=""
                        className="space-y-3 min-w-0"
                        onSubmit={(e) => {
                            const select = e.currentTarget.querySelector('[name=course_id]');
                            if (!select?.value) {
                                e.preventDefault();
                                return;
                            }
                            e.currentTarget.action = `/admin/skills/courses/${select.value}/sync`;
                        }}
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <select name="course_id" required className="w-full p-3 rounded-xl border min-w-0" defaultValue="">
                            <option value="">Select course…</option>
                            {courses.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.title}
                                </option>
                            ))}
                        </select>
                        <div className="flex flex-wrap gap-2">
                            {skills.map((skill) => (
                                <label key={skill.id} className="text-sm flex gap-1 break-words">
                                    <input type="checkbox" name="skill_ids[]" value={skill.id} /> {skill.name}
                                </label>
                            ))}
                        </div>
                        <button type="submit" className="px-4 py-2 bg-secondary text-white rounded-xl font-bold">
                            Save course requirements
                        </button>
                    </form>
                </PortalCard>

                <PortalCard title="Map required skills on a category">
                    <form
                        method="POST"
                        action=""
                        className="space-y-3 min-w-0"
                        onSubmit={(e) => {
                            const select = e.currentTarget.querySelector('[name=category_id]');
                            if (!select?.value) {
                                e.preventDefault();
                                return;
                            }
                            e.currentTarget.action = `/admin/skills/categories/${select.value}/sync`;
                        }}
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <select name="category_id" required className="w-full p-3 rounded-xl border min-w-0" defaultValue="">
                            <option value="">Select category…</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                        <div className="flex flex-wrap gap-2">
                            {skills.map((skill) => (
                                <label key={skill.id} className="text-sm flex gap-1 break-words">
                                    <input type="checkbox" name="skill_ids[]" value={skill.id} /> {skill.name}
                                </label>
                            ))}
                        </div>
                        <button type="submit" className="px-4 py-2 bg-tertiary text-white rounded-xl font-bold">
                            Save category requirements
                        </button>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
