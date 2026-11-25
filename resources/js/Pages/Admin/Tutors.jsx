import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import SoftStatCard from '../../Components/Portal/SoftStatCard';
import FilterBar from '../../Components/Portal/FilterBar';
import FormField from '../../Components/Portal/FormField';
import PortalCard from '../../Components/Portal/PortalCard';

const LABELS = {
    NEW: 'New',
    UNDER_REVIEW: 'Under Review',
    VIDEO_REVIEWED: 'Video Reviewed',
    INTERVIEW: 'Interview',
    APPROVED: 'Approved',
    REJECTED: 'Rejected',
};

export default function Tutors({ columns = {}, search = '', stats = {}, modules = [], skills = [], timezones = [] }) {
    const { flash, csrfToken, errors = {} } = usePage().props;
    const [dragId, setDragId] = useState(null);
    const dragIdRef = useRef(null);
    const didDragRef = useRef(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [detail, setDetail] = useState(null);
    const [detailPipeline, setDetailPipeline] = useState('NEW');
    const [editTutor, setEditTutor] = useState(null);
    const [showAddTutor, setShowAddTutor] = useState(false);
    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        timezone: 'UTC',
        status: 'active',
        send_email: true,
    });
    const editForm = useForm({
        name: '',
        email: '',
        status: 'active',
        password: '',
        password_confirmation: '',
    });

    const sendTutorWelcome = (tutor) => {
        if (!tutor) return;
        const defaultPw = 'YQ' + Math.floor(100000 + Math.random() * 900000);
        const pw = window.prompt(`Send welcome email with login credentials to tutor ${tutor.name} (${tutor.email})?\n\nEnter or edit login password to assign and send in email:`, defaultPw);
        if (pw === null) return;
        router.post(`/admin/users/${tutor.id}/send-welcome`, { tutor_password: pw.trim() || defaultPw }, { preserveScroll: true });
    };

    const deleteTutor = (tutor) => {
        if (!tutor?.id) return;
        const ok = window.confirm(
            `Delete tutor "${tutor.name}" (${tutor.email})?\n\nTheir account will be removed permanently. Courses they created are kept and unassigned. This cannot be undone.`
        );
        if (!ok) return;
        router.delete(`/admin/users/${tutor.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editTutor?.id === tutor.id) setEditTutor(null);
                if (detail?.id === tutor.id) {
                    setModalOpen(false);
                    setDetail(null);
                }
            },
        });
    };

    const openEdit = (tutor) => {
        setEditTutor(tutor);
        editForm.setData({
            name: tutor.name || '',
            email: tutor.email || '',
            status: tutor.status === 'suspended' || tutor.status === 'pending' ? tutor.status : 'active',
            password: '',
            password_confirmation: '',
        });
        editForm.clearErrors();
    };

    const saveEdit = (e) => {
        e.preventDefault();
        if (!editTutor) return;
        const { name, email, status, password, password_confirmation } = editForm.data;
        router.patch(
            `/admin/users/${editTutor.id}`,
            { name, email, status },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (password) {
                        router.post(
                            `/admin/users/${editTutor.id}/reset-password`,
                            { password, password_confirmation },
                            {
                                preserveScroll: true,
                                onFinish: () => {
                                    setEditTutor(null);
                                    editForm.reset('password', 'password_confirmation');
                                },
                            }
                        );
                    } else {
                        setEditTutor(null);
                    }
                },
            }
        );
    };

    const onDropColumn = async (e, status) => {
        e.preventDefault();
        const id = Number(e.dataTransfer.getData('text/plain') || dragIdRef.current || dragId || 0);
        dragIdRef.current = null;
        setDragId(null);
        if (!id) return;
        try {
            const res = await fetch(`/admin/tutors/${id}/pipeline`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ pipeline_status: status }),
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || data.error || 'Could not update tutor pipeline.');
                return;
            }
            router.reload({ preserveScroll: true });
        } catch {
            alert('Could not update tutor pipeline. Please try again.');
        }
    };

    const openTutor = async (id) => {
        if (didDragRef.current) {
            didDragRef.current = false;
            return;
        }
        try {
            const res = await fetch(`/admin/tutors/${id}/json`);
            if (!res.ok) throw new Error('load failed');
            const json = await res.json();
            setDetail(json);
            setDetailPipeline(json?.profile?.pipeline_status || 'NEW');
            setModalOpen(true);
        } catch {
            alert('Could not load tutor details.');
        }
    };

    return (
        <DashboardLayout title="Tutors">
            <Head title="Tutors Pipeline" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="People"
                    title="Tutors"
                    description="Register → review on kanban → approve → assign training & skills"
                    actions={
                        <FilterBar>
                            <button
                                type="button"
                                className="px-4 py-2 bg-secondary text-white rounded-lg font-bold shrink-0"
                                onClick={() => setShowAddTutor((open) => !open)}
                            >
                                {showAddTutor ? 'Hide form' : 'Add tutor'}
                            </button>
                            <form
                                method="GET"
                                className="flex gap-2 min-w-0"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const q = new FormData(e.currentTarget).get('search') || '';
                                    router.get('/admin/tutors', { search: q }, { preserveState: true });
                                }}
                            >
                                <input type="search" name="search" defaultValue={search || ''} placeholder="Search tutors..." className="p-2 rounded-lg border border-outline-variant bg-white min-w-0 max-w-[220px]" />
                                <button type="submit" className="px-4 py-2 bg-primary text-white rounded-lg font-bold shrink-0">Search</button>
                            </form>
                        </FilterBar>
                    }
                />

                {showAddTutor ? (
                    <PortalCard className="p-5 md:p-6">
                        <h2 className="text-lg font-extrabold mb-1">Add tutor</h2>
                        <p className="text-sm text-on-surface-variant mb-4">Create a tutor login. They can sign in at /login/tutor. Active tutors go straight to Approved.</p>
                        <form
                            className="grid md:grid-cols-2 gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                createForm.post('/admin/tutors', {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        createForm.reset();
                                        createForm.setData({
                                            name: '',
                                            email: '',
                                            password: '',
                                            password_confirmation: '',
                                            timezone: 'UTC',
                                            status: 'active',
                                            send_email: true,
                                        });
                                        setShowAddTutor(false);
                                    },
                                });
                            }}
                        >
                            <FormField label="Name" name="tutor_create_name" required error={createForm.errors.name || errors.name}>
                                <input id="tutor_create_name" required className="form-input" value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)} />
                            </FormField>
                            <FormField label="Email" name="tutor_create_email" required error={createForm.errors.email || errors.email}>
                                <input id="tutor_create_email" type="email" required className="form-input" value={createForm.data.email} onChange={(e) => createForm.setData('email', e.target.value)} />
                            </FormField>
                            <FormField label="Password" name="tutor_create_password" required error={createForm.errors.password || errors.password}>
                                <input id="tutor_create_password" type="password" required minLength={6} className="form-input" value={createForm.data.password} onChange={(e) => createForm.setData('password', e.target.value)} />
                            </FormField>
                            <FormField label="Confirm password" name="tutor_create_password_confirmation" required error={createForm.errors.password_confirmation || errors.password_confirmation}>
                                <input id="tutor_create_password_confirmation" type="password" required minLength={6} className="form-input" value={createForm.data.password_confirmation} onChange={(e) => createForm.setData('password_confirmation', e.target.value)} />
                            </FormField>
                            <FormField label="Timezone" name="tutor_create_timezone" error={createForm.errors.timezone || errors.timezone}>
                                <select id="tutor_create_timezone" className="form-input" value={createForm.data.timezone} onChange={(e) => createForm.setData('timezone', e.target.value)}>
                                    {(timezones.length ? timezones : [{ id: 'UTC', label: 'UTC' }]).map((tz) => (
                                        <option key={tz.id} value={tz.id}>{tz.label}</option>
                                    ))}
                                </select>
                            </FormField>
                            <FormField label="Account status" name="tutor_create_status" error={createForm.errors.status || errors.status}>
                                <select id="tutor_create_status" className="form-input" value={createForm.data.status} onChange={(e) => createForm.setData('status', e.target.value)}>
                                    <option value="active">Active (can log in · Approved)</option>
                                    <option value="pending">Pending (cannot teach yet · New)</option>
                                </select>
                            </FormField>
                            <label className="md:col-span-2 flex items-center gap-2 text-sm font-bold">
                                <input
                                    type="checkbox"
                                    checked={Boolean(createForm.data.send_email)}
                                    onChange={(e) => createForm.setData('send_email', e.target.checked)}
                                />
                                Send login credentials by email
                            </label>
                            <div className="md:col-span-2">
                                <button type="submit" className="btn-primary" disabled={createForm.processing}>
                                    {createForm.processing ? 'Creating…' : 'Create tutor'}
                                </button>
                            </div>
                        </form>
                    </PortalCard>
                ) : null}

                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <SoftStatCard label="Total" value={stats.total ?? 0} icon="badge" tone="lilac" accentBar />
                    <SoftStatCard label="Active" value={stats.active ?? 0} icon="check_circle" tone="mint" accentBar />
                    <SoftStatCard label="Pending" value={stats.pending ?? 0} icon="schedule" tone="peach" accentBar />
                    <SoftStatCard label="Suspended" value={stats.suspended ?? 0} icon="do_not_disturb_on" tone="sky" accentBar />
                </div>

                {flash?.success && <div className="p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{flash.success}</div>}
                {flash?.error && <div className="p-3 rounded-xl bg-rose-100 text-rose-900 border border-rose-200 font-bold">{flash.error}</div>}

                <div className="overflow-x-auto overscroll-x-contain pb-4 -mx-1 px-1">
                    <div className="flex gap-4 min-w-max items-start">
                        {Object.entries(LABELS).map(([key, label]) => (
                            <div
                                key={key}
                                className="w-72 shrink-0 bg-surface-container-low rounded-lg border border-outline-variant/40 flex flex-col min-w-0"
                                onDragOver={(e) => {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                }}
                                onDrop={(e) => onDropColumn(e, key)}
                            >
                                <div className="p-3 font-bold border-b border-outline-variant/40 sticky top-0 bg-surface-container-low rounded-t-2xl z-[1] flex justify-between">
                                    <span>{label}</span>
                                    <span className="text-xs bg-white px-2 py-0.5 rounded-full">{(columns[key] || []).length}</span>
                                </div>
                                <div className="p-2 space-y-2">
                                    {(columns[key] || []).map((tutor) => (
                                        <div
                                            key={tutor.id}
                                            className="bg-white rounded-xl border border-outline-variant/50 p-3 shadow-sm cursor-grab active:cursor-grabbing"
                                            draggable
                                            onDragStart={(e) => {
                                                didDragRef.current = false;
                                                dragIdRef.current = tutor.id;
                                                setDragId(tutor.id);
                                                e.dataTransfer.setData('text/plain', String(tutor.id));
                                                e.dataTransfer.effectAllowed = 'move';
                                            }}
                                            onDrag={() => {
                                                didDragRef.current = true;
                                            }}
                                            onDragEnd={() => {
                                                dragIdRef.current = null;
                                                setDragId(null);
                                            }}
                                            onClick={() => openTutor(tutor.id)}
                                            onKeyDown={(e) => e.key === 'Enter' && openTutor(tutor.id)}
                                            role="button"
                                            tabIndex={0}
                                        >
                                            <div className="flex items-center gap-2">
                                                <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary overflow-hidden">
                                                    {tutor.photo_url ? (
                                                        <img src={tutor.photo_url} className="w-full h-full object-cover" alt="" />
                                                    ) : (
                                                        (tutor.name || '?').charAt(0).toUpperCase()
                                                    )}
                                                </div>
                                                <div className="min-w-0">
                                                    <div className="font-bold text-sm truncate">{tutor.name}</div>
                                                    <div className="text-[11px] text-on-surface-variant truncate">{tutor.email}</div>
                                                </div>
                                            </div>
                                            {tutor.subject_vertical && (
                                                <div className="mt-2 flex flex-wrap gap-1">
                                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-tertiary-fixed">{tutor.subject_vertical}</span>
                                                </div>
                                            )}
                                            <div className="mt-2 text-[10px] text-on-surface-variant">
                                                {tutor.location || tutor.timezone} · {tutor.created_label}
                                            </div>
                                            <div className="mt-2 flex flex-wrap gap-3 items-center" onClick={(e) => e.stopPropagation()} onKeyDown={(e) => e.stopPropagation()}>
                                                <button type="button" className="text-[11px] font-bold text-on-surface" onClick={() => openEdit(tutor)}>
                                                    Edit
                                                </button>
                                                <Link href={`/admin/impersonate/${tutor.id}`} className="text-[11px] font-bold text-primary">
                                                    Login as
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-0.5"
                                                    title="Send welcome email with credentials to tutor"
                                                    onClick={() => sendTutorWelcome(tutor)}
                                                >
                                                    <span className="material-symbols-outlined text-[13px]">mail</span>
                                                    <span>Welcome Mail</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    className="text-[11px] font-bold text-rose-700 hover:text-rose-800"
                                                    onClick={() => deleteTutor(tutor)}
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {modalOpen && (
                    <div className="fixed inset-0 z-50 flex justify-end bg-black/40" onClick={() => setModalOpen(false)} role="presentation">
                        <div className="w-full max-w-lg h-full bg-white shadow-2xl overflow-y-auto p-6 space-y-4" onClick={(e) => e.stopPropagation()}>
                            <div className="flex justify-between items-start">
                                <div>
                                    <h2 className="text-xl font-bold">{detail?.name}</h2>
                                    <p className="text-sm text-on-surface-variant">{detail?.email}</p>
                                </div>
                                <button type="button" onClick={() => setModalOpen(false)} className="material-symbols-outlined">
                                    close
                                </button>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <button type="button" className="flex-1 min-w-[5.5rem] py-2 rounded-xl border border-outline-variant font-bold text-sm" onClick={() => openEdit(detail)}>
                                    Edit
                                </button>
                                <a href={`/admin/impersonate/${detail?.id}`} className="flex-1 min-w-[5.5rem] py-2 rounded-xl bg-primary text-white text-center font-bold text-sm">
                                    Login as
                                </a>
                                <button
                                    type="button"
                                    className="flex-1 min-w-[5.5rem] py-2 rounded-xl bg-emerald-600 text-white text-center font-bold text-sm hover:bg-emerald-700 transition-colors flex items-center justify-center gap-1"
                                    onClick={() => sendTutorWelcome(detail)}
                                >
                                    <span className="material-symbols-outlined text-[16px]">mail</span>
                                    <span>Welcome</span>
                                </button>
                                <button
                                    type="button"
                                    className="flex-1 min-w-[5.5rem] py-2 rounded-xl border border-rose-300 text-rose-800 bg-rose-50 hover:bg-rose-100 font-bold text-sm"
                                    onClick={() => deleteTutor(detail)}
                                >
                                    Delete
                                </button>
                            </div>
                            {detail?.video_url && <video src={detail.video_url} controls className="w-full rounded-xl bg-black" />}
                            {detail?.profile && (
                                <div className="text-sm space-y-1">
                                    <p>
                                        <strong>Phone:</strong> {detail.profile.phone}
                                    </p>
                                    <p>
                                        <strong>Location:</strong> {detail.profile.city_country}
                                    </p>
                                    <p>
                                        <strong>Experience:</strong> {detail.profile.teaching_experience}
                                    </p>
                                    <p>
                                        <strong>Education:</strong> {detail.profile.higher_education} — {detail.profile.higher_institution}
                                    </p>
                                    <p>
                                        <strong>Rate:</strong> {detail.profile.expected_hourly_rate} ({detail.profile.currency})
                                    </p>
                                </div>
                            )}

                            <form method="POST" action={`/admin/tutors/${detail?.id}/pipeline`} className="space-y-3 border-t pt-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <FormField label="Pipeline status" name="pipeline_status">
                                    <select id="pipeline_status" name="pipeline_status" className="form-input" value={detailPipeline} onChange={(e) => setDetailPipeline(e.target.value)}>
                                        {Object.entries(LABELS).map(([k, l]) => (
                                            <option key={k} value={k}>
                                                {l}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                                <FormField label="Reviewer notes" name="reviewer_notes">
                                    <textarea id="reviewer_notes" name="reviewer_notes" rows={3} className="form-input" defaultValue={detail?.profile?.reviewer_notes || ''} />
                                </FormField>
                                <button type="submit" className="w-full py-2 bg-primary text-white rounded-xl font-bold">Save pipeline</button>
                            </form>

                            <form method="POST" action={`/admin/tutors/${detail?.id}/assign-training`} className="space-y-3 border-t pt-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <FormField label="Assign training modules" name="assign_training_modules">
                                    {modules.length === 0 ? (
                                        <p className="text-xs text-on-surface-variant">No modules yet — create them under Tutor Training.</p>
                                    ) : (
                                        <div className="space-y-2">
                                            {modules.map((m) => (
                                                <label key={m.id} className="flex gap-2 text-sm">
                                                    <input type="checkbox" name="module_ids[]" value={m.id} /> {m.title}
                                                </label>
                                            ))}
                                        </div>
                                    )}
                                </FormField>
                                <button type="submit" className="w-full py-2 bg-secondary text-white rounded-xl font-bold" disabled={!modules.length}>
                                    Assign training
                                </button>
                            </form>

                            <form method="POST" action={`/admin/tutors/${detail?.id}/assign-skills`} className="space-y-3 border-t pt-4">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input type="hidden" name="verified" value="1" />
                                <FormField label="Assign skills (verified)" name="assign_skills">
                                    <div className="space-y-2">
                                        {skills.map((skill) => (
                                            <label key={skill.id} className="flex gap-2 text-sm">
                                                <input type="checkbox" name="skill_ids[]" value={skill.id} /> {skill.name}
                                            </label>
                                        ))}
                                    </div>
                                </FormField>
                                <button type="submit" className="w-full py-2 bg-tertiary text-white rounded-xl font-bold">Assign skills</button>
                            </form>

                        </div>
                    </div>
                )}

            {editTutor ? (
                <div className="fixed inset-0 z-[120] bg-black/40 flex items-center justify-center p-4" onClick={() => setEditTutor(null)}>
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between">
                            <h2 className="font-bold text-lg">Edit tutor</h2>
                            <button type="button" className="text-sm font-bold" onClick={() => setEditTutor(null)}>
                                Close
                            </button>
                        </div>
                        <form className="space-y-3" onSubmit={saveEdit}>
                            <FormField label="Name" name="tutor_edit_name" required error={editForm.errors.name}>
                                <input id="tutor_edit_name" className="form-input" value={editForm.data.name} onChange={(e) => editForm.setData('name', e.target.value)} required />
                            </FormField>
                            <FormField label="Email" name="tutor_edit_email" required error={editForm.errors.email}>
                                <input id="tutor_edit_email" type="email" className="form-input" value={editForm.data.email} onChange={(e) => editForm.setData('email', e.target.value)} required />
                            </FormField>
                            <FormField label="Account status" name="tutor_edit_status" error={editForm.errors.status}>
                                <select id="tutor_edit_status" className="form-input" value={editForm.data.status} onChange={(e) => editForm.setData('status', e.target.value)}>
                                    <option value="active">Active (can log in)</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended (cannot log in)</option>
                                </select>
                            </FormField>
                            <div className="pt-2 border-t space-y-3">
                                <FormField label="New password" name="tutor_edit_password" error={editForm.errors.password} hint="Optional — leave blank to keep current password">
                                    <input id="tutor_edit_password" type="password" className="form-input" value={editForm.data.password} onChange={(e) => editForm.setData('password', e.target.value)} />
                                </FormField>
                                <FormField label="Confirm password" name="tutor_edit_password_confirmation" error={editForm.errors.password_confirmation}>
                                    <input id="tutor_edit_password_confirmation" type="password" className="form-input" value={editForm.data.password_confirmation} onChange={(e) => editForm.setData('password_confirmation', e.target.value)} />
                                </FormField>
                            </div>
                            <button type="submit" className="btn-primary w-full" disabled={editForm.processing}>
                                Save tutor
                            </button>
                            <button
                                type="button"
                                className="w-full py-2 px-3 border border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                                onClick={() => {
                                    const t = editTutor;
                                    setEditTutor(null);
                                    sendTutorWelcome(t);
                                }}
                            >
                                <span className="material-symbols-outlined text-[15px]">mail</span>
                                <span>Send Welcome Email</span>
                            </button>
                            <button
                                type="button"
                                className="w-full py-2 px-3 border border-rose-300 text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-xl font-bold text-xs"
                                onClick={() => deleteTutor(editTutor)}
                            >
                                Delete tutor
                            </button>
                        </form>
                    </div>
                </div>
            ) : null}
            </div>
        </DashboardLayout>
    );
}
