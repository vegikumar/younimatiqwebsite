import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import SoftStatCard from '../../Components/Portal/SoftStatCard';
import FormField from '../../Components/Portal/FormField';

const LABELS = {
    pending: 'Pending',
    active_trial: 'Active (on trial)',
    active_enrolled: 'Active (enrolled/paid)',
    trial_expiring: 'Trial expiring (7d)',
    trial_expired: 'Trial expired',
    suspended: 'Suspended',
};

const DROPPABLE = {
    pending: 'pending',
    active_trial: 'active',
    active_enrolled: 'active',
    suspended: 'suspended',
};

function detectBrowserTimezone() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    } catch {
        return 'UTC';
    }
}

function isUnsetTimezone(tz) {
    return !tz || ['UTC', 'GMT', 'Etc/UTC', 'Etc/GMT'].includes(String(tz).trim());
}

function mergeTimezoneOptions(base = [], ...extras) {
    const seen = new Set();
    const out = [];
    for (const opt of [...base, ...extras.filter(Boolean)]) {
        const id = opt?.id || opt;
        const label = opt?.label || String(id).replace(/_/g, ' ');
        if (!id || seen.has(id)) continue;
        seen.add(id);
        out.push({ id, label });
    }
    return out;
}

async function postJson(url, body, csrfToken) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.error || Object.values(data.errors || {}).flat()[0] || 'Request failed');
    }
    return data;
}

export default function Students({ columns = {}, search = '', stats = {}, timezones = [] }) {
    const { flash, csrfToken } = usePage().props;
    const dragIdRef = useRef(null);
    const [dragId, setDragId] = useState(null);
    const [editStudent, setEditStudent] = useState(null);
    const [welcomeStudent, setWelcomeStudent] = useState(null);
    const [browserTz, setBrowserTz] = useState('UTC');
    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        timezone: 'UTC',
        send_email: true,
        parent_name: '',
        parent_email: '',
        parent_password: '',
        parent_password_confirmation: '',
        relationship: 'parent',
    });
    const editForm = useForm({
        name: '',
        email: '',
        status: 'active',
        timezone: 'UTC',
        password: '',
        password_confirmation: '',
    });
    const welcomeForm = useForm({
        student_password: '',
        parent_password: '',
        send_to_student: true,
        send_to_parent: true,
    });

    useEffect(() => {
        const detected = detectBrowserTimezone();
        setBrowserTz(detected);
        if (isUnsetTimezone(createForm.data.timezone)) {
            createForm.setData('timezone', detected);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- detect once on mount for Add form default
    }, []);

    const createTimezoneOptions = useMemo(
        () => mergeTimezoneOptions(timezones, { id: browserTz, label: `${browserTz.replace(/_/g, ' ')} (detected)` }),
        [timezones, browserTz]
    );

    const editTimezoneOptions = useMemo(
        () =>
            mergeTimezoneOptions(
                timezones,
                editStudent?.timezone ? { id: editStudent.timezone, label: editStudent.timezone_label || editStudent.timezone } : null,
                { id: browserTz, label: `${browserTz.replace(/_/g, ' ')} (detected)` }
            ),
        [timezones, editStudent, browserTz]
    );

    const openWelcome = (student) => {
        setWelcomeStudent(student);
        const suggestedPass = 'YQ' + Math.floor(100000 + Math.random() * 900000);
        welcomeForm.setData({
            student_password: suggestedPass,
            parent_password: suggestedPass,
            send_to_student: true,
            send_to_parent: !!(student.parents && student.parents.length > 0),
        });
    };

    const submitWelcome = (e) => {
        e.preventDefault();
        if (!welcomeStudent) return;
        welcomeForm.post(`/admin/users/${welcomeStudent.id}/send-welcome`, {
            preserveScroll: true,
            onSuccess: () => {
                setWelcomeStudent(null);
                welcomeForm.reset();
            },
        });
    };

    const startDrag = (e, id) => {
        dragIdRef.current = id;
        setDragId(id);
        e.dataTransfer.setData('text/plain', String(id));
        e.dataTransfer.effectAllowed = 'move';
    };

    const onDropColumn = async (e, column) => {
        e.preventDefault();
        const id = Number(e.dataTransfer.getData('text/plain') || dragIdRef.current || dragId || 0);
        const status = DROPPABLE[column];
        dragIdRef.current = null;
        setDragId(null);
        if (!id || !status) {
            if (!status) alert('That column is computed from trial/payment data and cannot be dropped into. Drop into Pending, Active (trial/enrolled), or Suspended.');
            return;
        }
        try {
            await postJson(`/admin/students/${id}/status`, { status }, csrfToken);
            router.reload({ preserveScroll: true });
        } catch (err) {
            alert(err.message || 'Could not update student status.');
        }
    };

    const openEdit = (student) => {
        setEditStudent(student);
        const tz = !isUnsetTimezone(student.timezone) ? student.timezone : detectBrowserTimezone();
        editForm.setData({
            name: student.name || '',
            email: student.email || '',
            status: student.status === 'suspended' || student.status === 'pending' ? student.status : 'active',
            timezone: tz || 'UTC',
            password: '',
            password_confirmation: '',
        });
        editForm.clearErrors();
    };

    const applyDetectedTimezone = (form) => {
        const detected = detectBrowserTimezone();
        setBrowserTz(detected);
        form.setData('timezone', detected);
    };

    const saveEdit = (e) => {
        e.preventDefault();
        if (!editStudent) return;
        const { name, email, status, timezone, password, password_confirmation } = editForm.data;
        router.patch(
            `/admin/users/${editStudent.id}`,
            { name, email, status, timezone },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (password) {
                        router.post(
                            `/admin/users/${editStudent.id}/reset-password`,
                            { password, password_confirmation },
                            {
                                preserveScroll: true,
                                onFinish: () => {
                                    setEditStudent(null);
                                    editForm.reset('password', 'password_confirmation');
                                },
                            }
                        );
                    } else {
                        setEditStudent(null);
                    }
                },
            }
        );
    };

    const activate = async (student) => {
        try {
            await postJson(`/admin/students/${student.id}/status`, { status: 'active' }, csrfToken);
            router.reload({ preserveScroll: true });
        } catch (err) {
            alert(err.message || 'Could not activate student.');
        }
    };

    const deleteStudent = (student) => {
        if (!student?.id) return;
        const ok = window.confirm(
            `Delete student "${student.name}" (${student.email})?\n\nThis permanently removes their account, enrollments, and related data. This cannot be undone.`
        );
        if (!ok) return;
        router.delete(`/admin/users/${student.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editStudent?.id === student.id) setEditStudent(null);
                if (welcomeStudent?.id === student.id) setWelcomeStudent(null);
            },
        });
    };

    return (
        <DashboardLayout title="Students">
            <Head title="Students" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="People"
                    title="Students"
                    description="Pipeline, activation, and account controls. Kanban by account & trial status."
                    actions={
                        <form
                            method="GET"
                            className="flex gap-2 min-w-0"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const q = new FormData(e.currentTarget).get('search') || '';
                                router.get('/admin/students', { search: q }, { preserveState: true });
                            }}
                        >
                            <input type="search" name="search" defaultValue={search || ''} placeholder="Search students..." className="p-2 rounded-lg border border-outline-variant bg-white min-w-0 max-w-[220px]" />
                            <button type="submit" className="px-4 py-2 bg-primary text-white rounded-lg font-bold shrink-0">Search</button>
                        </form>
                    }
                />

                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <SoftStatCard label="Total" value={Number(stats.total || 0).toLocaleString()} tone="lilac" icon="group" accentBar />
                    <SoftStatCard label="Active" value={Number(stats.active || 0).toLocaleString()} tone="mint" icon="check_circle" accentBar />
                    <SoftStatCard label="Pending" value={Number(stats.pending || 0).toLocaleString()} tone="peach" icon="schedule" accentBar />
                    <SoftStatCard label="Trial expiring" value={Number(stats.trial_expiring || 0).toLocaleString()} tone="sky" icon="hourglass_top" accentBar />
                    <SoftStatCard label="Suspended" value={Number(stats.suspended || 0).toLocaleString()} tone="rose" icon="do_not_disturb_on" accentBar />
                </div>

                <PortalCard padding={false} className="p-4">
                <details className="min-w-0">
                    <summary className="font-bold cursor-pointer">Link parent to student</summary>
                    <form method="POST" action="/admin/parents/link" className="grid md:grid-cols-2 gap-3 mt-4">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <FormField label="Parent email" name="parent_email" required>
                            <input type="email" id="parent_email" name="parent_email" required className="form-input" />
                        </FormField>
                        <FormField label="Parent name" name="parent_name" hint="If new">
                            <input id="parent_name" name="parent_name" className="form-input" />
                        </FormField>
                        <FormField label="Student email (or ID)" name="student_id" required hint="Enter student's email address">
                            <input
                                type="text"
                                id="student_id"
                                name="student_id"
                                required
                                placeholder="student@example.com (or user ID)"
                                className="form-input"
                            />
                        </FormField>
                        <FormField label="Relationship" name="relationship">
                            <select id="relationship" name="relationship" className="form-input" defaultValue="parent">
                                <option value="parent">Parent</option>
                                <option value="guardian">Guardian</option>
                            </select>
                        </FormField>
                        <FormField label="Parent password" name="parent_password" hint="Optional — create/set parent's password">
                            <input
                                type="password"
                                id="link_parent_password"
                                name="parent_password"
                                className="form-input"
                                placeholder="Enter parent's password"
                            />
                        </FormField>
                        <div className="md:col-span-2 flex items-center gap-2">
                            <label className="flex items-center gap-2 text-sm text-on-surface cursor-pointer font-medium">
                                <input type="checkbox" name="send_email" value="1" defaultChecked />
                                <span>Send welcome email with login credentials to parent now</span>
                            </label>
                        </div>
                        <button type="submit" className="md:col-span-2 px-4 py-2 bg-secondary text-white rounded-xl font-bold">
                            Link parent account
                        </button>
                    </form>
                    <p className="text-xs text-on-surface-variant mt-2">Creates a parent user if the email is new, or automatically updates it if previously registered as a student. Share a password reset with them after linking.</p>
                </details>
                </PortalCard>

                <PortalCard padding={false} className="p-4">
                <details className="min-w-0">
                    <summary className="font-bold cursor-pointer">Add student</summary>
                    <form
                        className="grid md:grid-cols-2 gap-3 mt-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            createForm.post('/admin/students', {
                                onSuccess: () => {
                                    createForm.reset();
                                    createForm.setData('timezone', detectBrowserTimezone());
                                },
                            });
                        }}
                    >
                        <FormField label="Name" name="create_name" required error={createForm.errors.name}>
                            <input id="create_name" required className="form-input" value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)} />
                        </FormField>
                        <FormField label="Email" name="create_email" required error={createForm.errors.email}>
                            <input id="create_email" type="email" required className="form-input" value={createForm.data.email} onChange={(e) => createForm.setData('email', e.target.value)} />
                        </FormField>
                        <FormField label="Password" name="create_password" required error={createForm.errors.password}>
                            <input id="create_password" type="password" required className="form-input" value={createForm.data.password} onChange={(e) => createForm.setData('password', e.target.value)} />
                        </FormField>
                        <FormField label="Confirm password" name="create_password_confirmation" required error={createForm.errors.password_confirmation}>
                            <input id="create_password_confirmation" type="password" required className="form-input" value={createForm.data.password_confirmation} onChange={(e) => createForm.setData('password_confirmation', e.target.value)} />
                        </FormField>
                        <FormField
                            label="Timezone"
                            name="create_timezone"
                            error={createForm.errors.timezone}
                            hint="Child's local timezone. Auto-detected from this browser — change if needed."
                        >
                            <div className="flex gap-2">
                                <select
                                    id="create_timezone"
                                    className="form-input flex-1"
                                    value={createForm.data.timezone}
                                    onChange={(e) => createForm.setData('timezone', e.target.value)}
                                >
                                    {createTimezoneOptions.map((tz) => (
                                        <option key={tz.id} value={tz.id}>{tz.label}</option>
                                    ))}
                                </select>
                                <button
                                    type="button"
                                    className="px-3 py-2 border border-outline-variant rounded-lg text-xs font-bold shrink-0"
                                    onClick={() => applyDetectedTimezone(createForm)}
                                >
                                    Detect
                                </button>
                            </div>
                        </FormField>

                        <div className="md:col-span-2 border-t border-outline-variant/30 pt-3 mt-1">
                            <div className="flex items-center gap-2 mb-2 text-xs font-bold uppercase tracking-wider text-secondary">
                                <span className="material-symbols-outlined text-[16px]">escalator_warning</span>
                                <span>Optional: Parent Information & Credentials</span>
                            </div>
                            <div className="grid md:grid-cols-2 gap-3 bg-secondary/5 p-3.5 rounded-xl border border-secondary/15">
                                <FormField label="Parent name" name="parent_name" hint="Optional">
                                    <input
                                        id="create_parent_name"
                                        className="form-input"
                                        placeholder="e.g. Jane Doe"
                                        value={createForm.data.parent_name}
                                        onChange={(e) => createForm.setData('parent_name', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Parent email" name="parent_email" hint="Optional" error={createForm.errors.parent_email}>
                                    <input
                                        id="create_parent_email"
                                        type="email"
                                        className="form-input"
                                        placeholder="parent@example.com"
                                        value={createForm.data.parent_email}
                                        onChange={(e) => createForm.setData('parent_email', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Parent password" name="parent_password" hint="Optional — set login password for parent" error={createForm.errors.parent_password}>
                                    <input
                                        id="create_parent_password"
                                        type="password"
                                        className="form-input"
                                        placeholder="Min 6 characters"
                                        value={createForm.data.parent_password}
                                        onChange={(e) => createForm.setData('parent_password', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Confirm parent password" name="parent_password_confirmation" error={createForm.errors.parent_password_confirmation}>
                                    <input
                                        id="create_parent_password_confirmation"
                                        type="password"
                                        className="form-input"
                                        placeholder="Repeat parent password"
                                        value={createForm.data.parent_password_confirmation}
                                        onChange={(e) => createForm.setData('parent_password_confirmation', e.target.value)}
                                    />
                                </FormField>
                                <FormField label="Relationship" name="relationship">
                                    <select
                                        id="create_relationship"
                                        className="form-input"
                                        value={createForm.data.relationship}
                                        onChange={(e) => createForm.setData('relationship', e.target.value)}
                                    >
                                        <option value="parent">Parent</option>
                                        <option value="guardian">Guardian</option>
                                    </select>
                                </FormField>
                            </div>
                        </div>

                        <div className="md:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={createForm.data.send_email} onChange={(e) => createForm.setData('send_email', e.target.checked)} /> Send welcome & login credentials email
                            </label>
                            <button type="submit" className="w-full sm:w-auto px-4 py-2 bg-primary text-white rounded-lg font-bold shrink-0" disabled={createForm.processing}>
                                Create student {createForm.data.parent_email ? '+ parent' : ''}
                            </button>
                        </div>
                    </form>
                </details>
                </PortalCard>

                {flash?.success && (
                    <div className="p-3 rounded-xl bg-emerald-100 text-emerald-900 border border-emerald-200 font-bold">{flash.success}</div>
                )}
                {flash?.error && (
                    <div className="p-3 rounded-xl bg-rose-100 text-rose-900 border border-rose-200 font-bold">{flash.error}</div>
                )}

                <div className="overflow-x-auto overscroll-x-contain pb-4 -mx-1 px-1">
                    <div className="flex gap-4 min-w-max items-start">
                        {Object.keys(LABELS).map((key) => (
                            <div
                                key={key}
                                className={`w-72 shrink-0 bg-surface-container-low rounded-lg border flex flex-col min-w-0 ${
                                    dragId && DROPPABLE[key] ? 'border-primary border-2' : 'border-outline-variant/40'
                                }`}
                                onDragOver={(e) => {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                }}
                                onDrop={(e) => onDropColumn(e, key)}
                            >
                                <div className="p-3 font-bold border-b border-outline-variant/40 flex justify-between sticky top-0 bg-surface-container-low rounded-t-2xl z-[1]">
                                    <span className="text-sm">{LABELS[key]}</span>
                                    <span className="text-xs bg-white px-2 py-0.5 rounded-full">{(columns[key] || []).length}</span>
                                </div>
                                <div className="p-2 space-y-2">
                                    {(columns[key] || []).map((student) => (
                                        <div
                                            key={student.id}
                                            className="bg-white rounded-xl border p-3 shadow-sm cursor-grab active:cursor-grabbing"
                                            draggable
                                            onDragStart={(e) => startDrag(e, student.id)}
                                            onDragEnd={() => {
                                                dragIdRef.current = null;
                                                setDragId(null);
                                            }}
                                        >
                                            <div className="font-bold text-sm truncate">{student.name}</div>
                                            <div className="text-[11px] text-on-surface-variant truncate">{student.email}</div>
                                            <div className="text-[10px] mt-1 uppercase tracking-wide text-on-surface-variant">Status: {student.status}</div>
                                            <div className={`text-[10px] mt-1 ${isUnsetTimezone(student.timezone) ? 'text-amber-700 font-semibold' : 'text-on-surface-variant'}`}>
                                                TZ: {student.timezone_label || student.timezone || 'Not set'}
                                            </div>
                                            {student.trial_ends_at && (
                                                <div className="text-[10px] mt-1 text-on-surface-variant">Trial: {student.trial_label || student.trial_ends_at}</div>
                                            )}
                                            {student.parents && student.parents.length > 0 && (
                                                <div className="mt-1.5 flex items-center gap-1 text-[11px] text-secondary font-medium bg-secondary/10 px-2 py-0.5 rounded-md truncate" title={student.parents.map((p) => `${p.name} (${p.email})`).join(', ')}>
                                                    <span className="material-symbols-outlined text-[13px]">escalator_warning</span>
                                                    <span className="truncate">Parent: {student.parents.map((p) => p.name || p.email).join(', ')}</span>
                                                </div>
                                            )}
                                            <div className="mt-2 flex flex-wrap gap-2">
                                                <button type="button" className="text-[11px] font-bold text-on-surface" onClick={() => openEdit(student)}>
                                                    Edit
                                                </button>
                                                {student.status === 'suspended' || student.status === 'pending' ? (
                                                    <button type="button" className="text-[11px] font-bold text-primary" onClick={() => activate(student)}>
                                                        Activate
                                                    </button>
                                                ) : null}
                                                <Link href={`/admin/impersonate/${student.id}`} className="text-[11px] font-bold text-primary">
                                                    Login as
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-[11px] font-bold text-secondary"
                                                    onClick={() => {
                                                        router.post(`/admin/users/${student.id}/extend-trial`, {
                                                            days: 7,
                                                            reactivate: 1,
                                                        });
                                                    }}
                                                >
                                                    +7d trial
                                                </button>
                                                <button
                                                    type="button"
                                                    className="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-0.5"
                                                    title={student.parents && student.parents.length > 0 ? 'Send welcome email to student and linked parent(s)' : 'Send welcome email to student'}
                                                    onClick={() => openWelcome(student)}
                                                >
                                                    <span className="material-symbols-outlined text-[13px]">mail</span>
                                                    <span>Welcome Mail</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    className="text-[11px] font-bold text-rose-700 hover:text-rose-800"
                                                    onClick={() => deleteStudent(student)}
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
                <p className="text-xs text-on-surface-variant">
                    Drag into Pending / Active columns / Suspended to change account status. Use <strong>Activate</strong> or <strong>Edit</strong> to fix suspended logins. Trial columns are computed from trial dates & payments.
                </p>
            </div>

            {editStudent ? (
                <div className="fixed inset-0 z-[120] bg-black/40 flex items-center justify-center p-4" onClick={() => setEditStudent(null)}>
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between">
                            <h2 className="font-bold text-lg">Edit student</h2>
                            <button type="button" className="text-sm font-bold" onClick={() => setEditStudent(null)}>
                                Close
                            </button>
                        </div>
                        <form className="space-y-3" onSubmit={saveEdit}>
                            {editStudent?.parents && editStudent.parents.length > 0 && (
                                <div className="p-3 bg-secondary/10 border border-secondary/20 rounded-xl text-xs space-y-1">
                                    <div className="font-bold flex items-center gap-1 text-secondary">
                                        <span className="material-symbols-outlined text-[15px]">escalator_warning</span>
                                        <span>Linked Parent Account</span>
                                    </div>
                                    {editStudent.parents.map((p) => (
                                        <div key={p.id} className="text-on-surface font-medium">
                                            {p.name} — <span className="text-on-surface-variant">{p.email}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <FormField label="Name" name="edit_name" required error={editForm.errors.name}>
                                <input id="edit_name" className="form-input" value={editForm.data.name} onChange={(e) => editForm.setData('name', e.target.value)} required />
                            </FormField>
                            <FormField label="Email" name="edit_email" required error={editForm.errors.email}>
                                <input id="edit_email" type="email" className="form-input" value={editForm.data.email} onChange={(e) => editForm.setData('email', e.target.value)} required />
                            </FormField>
                            <FormField label="Account status" name="edit_status" error={editForm.errors.status}>
                                <select id="edit_status" className="form-input" value={editForm.data.status} onChange={(e) => editForm.setData('status', e.target.value)}>
                                    <option value="active">Active (can log in)</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended (cannot log in)</option>
                                </select>
                            </FormField>
                            <FormField
                                label="Timezone"
                                name="edit_timezone"
                                error={editForm.errors.timezone}
                                hint="Child's local timezone. Saving also updates upcoming class display times."
                            >
                                <div className="flex gap-2">
                                    <select
                                        id="edit_timezone"
                                        className="form-input flex-1"
                                        value={editForm.data.timezone}
                                        onChange={(e) => editForm.setData('timezone', e.target.value)}
                                    >
                                        {editTimezoneOptions.map((tz) => (
                                            <option key={tz.id} value={tz.id}>{tz.label}</option>
                                        ))}
                                    </select>
                                    <button
                                        type="button"
                                        className="px-3 py-2 border border-outline-variant rounded-lg text-xs font-bold shrink-0"
                                        onClick={() => applyDetectedTimezone(editForm)}
                                    >
                                        Detect
                                    </button>
                                </div>
                            </FormField>
                            <div className="pt-2 border-t space-y-3">
                                <FormField label="New password" name="edit_password" error={editForm.errors.password} hint="Optional — leave blank to keep current password">
                                    <input id="edit_password" type="password" className="form-input" value={editForm.data.password} onChange={(e) => editForm.setData('password', e.target.value)} />
                                </FormField>
                                <FormField label="Confirm password" name="edit_password_confirmation" error={editForm.errors.password_confirmation}>
                                    <input id="edit_password_confirmation" type="password" className="form-input" value={editForm.data.password_confirmation} onChange={(e) => editForm.setData('password_confirmation', e.target.value)} />
                                </FormField>
                            </div>
                            <button type="submit" className="btn-primary w-full" disabled={editForm.processing}>
                                Save student
                            </button>
                            <button
                                type="button"
                                className="w-full py-2 px-3 border border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                                onClick={() => {
                                    const s = editStudent;
                                    setEditStudent(null);
                                    openWelcome(s);
                                }}
                            >
                                <span className="material-symbols-outlined text-[15px]">mail</span>
                                <span>Send Welcome Email {editStudent?.parents && editStudent.parents.length > 0 ? '(Student + Linked Parent)' : ''}</span>
                            </button>
                            <button
                                type="button"
                                className="w-full py-2 px-3 border border-rose-300 text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-xl font-bold text-xs"
                                onClick={() => deleteStudent(editStudent)}
                            >
                                Delete student
                            </button>
                        </form>
                    </div>
                </div>
            ) : null}

            {welcomeStudent ? (
                <div className="fixed inset-0 z-[130] bg-black/40 flex items-center justify-center p-4" onClick={() => setWelcomeStudent(null)}>
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between border-b pb-3">
                            <div className="flex items-center gap-2">
                                <span className="material-symbols-outlined text-emerald-600">mail</span>
                                <h2 className="font-bold text-lg">Send Welcome Email</h2>
                            </div>
                            <button type="button" className="text-sm font-bold text-on-surface-variant hover:text-on-surface" onClick={() => setWelcomeStudent(null)}>
                                Close
                            </button>
                        </div>

                        <form className="space-y-4" onSubmit={submitWelcome}>
                            <div className="p-3 bg-surface-container-low rounded-xl text-xs space-y-1">
                                <div className="font-bold text-sm text-on-surface">{welcomeStudent.name}</div>
                                <div className="text-on-surface-variant">{welcomeStudent.email}</div>
                            </div>

                            {welcomeStudent.parents && welcomeStudent.parents.length > 0 ? (
                                <div className="p-3.5 bg-secondary/10 border border-secondary/20 rounded-xl text-xs space-y-3">
                                    <div className="font-bold flex items-center gap-1.5 text-secondary">
                                        <span className="material-symbols-outlined text-[16px]">escalator_warning</span>
                                        <span>Linked Parent Account</span>
                                    </div>
                                    {welcomeStudent.parents.map((p) => (
                                        <div key={p.id} className="text-on-surface font-medium">
                                            {p.name} — <span className="text-on-surface-variant">{p.email}</span>
                                        </div>
                                    ))}
                                    <FormField label="Parent password" name="welcome_parent_password" hint="Set or update parent's login password to send in welcome email">
                                        <input
                                            id="welcome_parent_password"
                                            type="text"
                                            className="form-input"
                                            placeholder="Enter parent password (e.g. parent123)"
                                            value={welcomeForm.data.parent_password}
                                            onChange={(e) => welcomeForm.setData('parent_password', e.target.value)}
                                        />
                                    </FormField>
                                    <label className="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={welcomeForm.data.send_to_parent}
                                            onChange={(e) => welcomeForm.setData('send_to_parent', e.target.checked)}
                                        />
                                        <span>Send welcome email with credentials to parent</span>
                                    </label>
                                </div>
                            ) : (
                                <div className="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-1">
                                    <div className="font-bold flex items-center gap-1">
                                        <span className="material-symbols-outlined text-[15px]">info</span>
                                        <span>No Parent Linked</span>
                                    </div>
                                    <p>Link a parent in the section above to send login credentials to both student and parent.</p>
                                </div>
                            )}

                            <div className="p-3.5 bg-primary/5 border border-primary/15 rounded-xl space-y-3 text-xs">
                                <div className="font-bold text-primary flex items-center gap-1.5">
                                    <span className="material-symbols-outlined text-[16px]">school</span>
                                    <span>Student Login Credentials</span>
                                </div>
                                <FormField label="Student password" name="welcome_student_password" hint="Login password sent to student in welcome email">
                                    <input
                                        id="welcome_student_password"
                                        type="text"
                                        className="form-input"
                                        placeholder="Enter or customize student password"
                                        value={welcomeForm.data.student_password}
                                        onChange={(e) => welcomeForm.setData('student_password', e.target.value)}
                                    />
                                </FormField>
                                <label className="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={welcomeForm.data.send_to_student}
                                        onChange={(e) => welcomeForm.setData('send_to_student', e.target.checked)}
                                    />
                                    <span>Send welcome email with credentials to student</span>
                                </label>
                            </div>

                            <div className="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    className="flex-1 py-2 rounded-xl border border-outline-variant font-bold text-xs"
                                    onClick={() => setWelcomeStudent(null)}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5"
                                    disabled={welcomeForm.processing || (!welcomeForm.data.send_to_parent && !welcomeForm.data.send_to_student)}
                                >
                                    <span className="material-symbols-outlined text-[16px]">send</span>
                                    <span>{welcomeForm.processing ? 'Sending...' : 'Send Welcome Email'}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            ) : null}
        </DashboardLayout>
    );
}
