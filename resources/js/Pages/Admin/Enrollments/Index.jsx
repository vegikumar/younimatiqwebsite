import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import Icon from '../../../Components/Icon';
import ConfirmDialog from '../../../Components/Portal/ConfirmDialog';
import EmptyState from '../../../Components/Portal/EmptyState';
import PageHeader from '../../../Components/Portal/PageHeader';
import PortalCard from '../../../Components/Portal/PortalCard';
import DataTableShell from '../../../Components/Portal/DataTableShell';
import { formatLocalTime } from '../../../utils/formatLocalTime';
import {
    detectBrowserTimezone,
    previewAdminTime,
    previewFromDateTimeLocal,
    shortTzLabel,
} from '../../../utils/timezonePreview';

function mergeTimezoneOptions(base = [], ...extras) {
    const seen = new Set();
    const out = [];
    for (const opt of [...base, ...extras.filter(Boolean)]) {
        const id = typeof opt === 'string' ? opt : opt?.id;
        if (!id || seen.has(id)) continue;
        const label =
            (typeof opt === 'object' && opt.label) ||
            String(id).split('/').pop().replace(/_/g, ' ');
        seen.add(id);
        out.push({ id, label });
    }
    return out;
}

function PaginationLinks({ links = [] }) {
    if (!links || links.length <= 3) return null;
    return (
        <div className="p-4 flex flex-wrap gap-2">
            {links.map((link, i) => (
                link.url ? (
                    <Link key={i} href={link.url} className={`px-3 py-1 rounded-lg text-sm font-bold ${link.active ? 'bg-primary text-white' : 'bg-surface-container-low'}`} dangerouslySetInnerHTML={{ __html: link.label }} />
                ) : (
                    <span key={i} className="px-3 py-1 text-sm text-on-surface-variant" dangerouslySetInnerHTML={{ __html: link.label }} />
                )
            ))}
        </div>
    );
}

function ScheduleEditor({ booking, scheduleWeekDays, timezones, csrfToken, adminTimezone }) {
    const [open, setOpen] = useState(false);
    const s = booking.scheduleSummary || {};
    const [studentTz, setStudentTz] = useState(s.timezone || 'UTC');
    const [startDate, setStartDate] = useState(s.start_date || '');
    const tzOptions = useMemo(
        () => mergeTimezoneOptions(timezones, studentTz ? { id: studentTz } : null),
        [timezones, studentTz]
    );

    useEffect(() => {
        if (open) {
            setStudentTz(s.timezone || 'UTC');
            setStartDate(s.start_date || '');
        }
    }, [open, s.timezone, s.start_date]);

    return (
        <td className="staff-ops-cell">
            {!open ? (
                <div className="staff-ops-stack">
                    {booking.scheduledAt ? (
                        <>
                            <div className="font-semibold text-on-surface whitespace-nowrap">{formatLocalTime(booking.scheduledAt, 'date')}</div>
                            <div className="text-xs font-medium text-primary whitespace-nowrap">{formatLocalTime(booking.scheduledAt, 'time')}</div>
                            {booking.studentOptedLabel ? <div className="text-[10px] text-outline mt-0.5">Student opted: {booking.studentOptedLabel}</div> : null}
                        </>
                    ) : (
                        <span className="text-outline">Not scheduled</span>
                    )}
                    <div className="mt-1 text-[11px] font-medium text-on-surface">
                        {s.classes} {s.classes === 1 ? 'class' : 'classes'}
                        {s.daysLabel ? <><span className="text-outline"> · </span>{s.daysLabel}</> : null}
                    </div>
                    <button type="button" onClick={() => setOpen(true)} className="staff-ops-link">
                        <Icon name="edit_calendar" /> {booking.scheduledAt ? 'Edit' : 'Schedule'}
                    </button>
                </div>
            ) : (
                <form method="POST" action={booking.scheduleUpdateUrl} className="flex flex-col gap-2 bg-surface-container-low p-3 rounded-xl border border-outline-variant mt-2">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <label className="text-[10px] font-bold uppercase text-on-surface-variant">Number of Classes</label>
                    <input type="number" name="number_of_classes" min={1} max={48} defaultValue={s.classes} className="w-full text-xs p-2 border rounded-lg" />
                    <label className="text-[10px] font-bold uppercase text-on-surface-variant">Schedule Start Date</label>
                    <input
                        type="date"
                        name="schedule_start_date"
                        value={startDate}
                        onChange={(e) => setStartDate(e.target.value)}
                        className="w-full text-xs p-2 border rounded-lg"
                    />
                    <label className="text-[10px] font-bold uppercase text-on-surface-variant">Student timezone</label>
                    <select
                        name="timezone"
                        required
                        value={studentTz}
                        onChange={(e) => setStudentTz(e.target.value)}
                        className="auto-timezone-select w-full text-xs p-2 border rounded-lg"
                        data-current={s.timezone}
                    >
                        {tzOptions.map((tz) => <option key={tz.id} value={tz.id}>{tz.label}</option>)}
                    </select>
                    <p className="text-[10px] text-on-surface-variant leading-snug">
                        Times below are in the <strong>student</strong> zone. Each row also shows your admin time ({shortTzLabel(adminTimezone, tzOptions)}).
                    </p>
                    <label className="text-[10px] font-bold uppercase text-on-surface-variant">Weekly Days &amp; Times</label>
                    <div className="flex flex-col gap-1">
                        {scheduleWeekDays.map((day) => (
                            <DayTimeRow
                                key={day.value}
                                day={day}
                                defaultOn={(s.days || []).includes(day.value)}
                                defaultTime={(s.times || {})[day.value] || '17:00'}
                                studentTimezone={studentTz}
                                adminTimezone={adminTimezone}
                                timezones={tzOptions}
                                refDate={startDate}
                            />
                        ))}
                    </div>
                    <div className="flex gap-1 mt-1">
                        <button type="submit" className="flex-1 bg-primary text-white text-[11px] py-1 rounded-lg font-bold">Save</button>
                        <button type="button" onClick={() => setOpen(false)} className="flex-1 bg-surface-container text-on-surface text-[11px] py-1 rounded-lg font-bold">Cancel</button>
                    </div>
                </form>
            )}
        </td>
    );
}

function DayTimeRow({ day, defaultOn, defaultTime, studentTimezone, adminTimezone, timezones = [], refDate = null }) {
    const [on, setOn] = useState(defaultOn);
    const [time, setTime] = useState(defaultTime || '17:00');

    const preview = useMemo(() => {
        if (!on || !time || !studentTimezone || !adminTimezone) return null;
        return previewAdminTime(time, studentTimezone, adminTimezone, refDate || null);
    }, [on, time, studentTimezone, adminTimezone, refDate]);

    return (
        <div className="text-xs bg-surface-container-lowest p-2 rounded-lg border border-outline-variant/50 space-y-1">
            <label className="flex items-center gap-2">
                <input type="checkbox" name="schedule_days[]" value={day.value} checked={on} onChange={(e) => setOn(e.target.checked)} className="w-4 h-4 rounded text-primary" />
                <span className="w-20 font-bold text-on-surface truncate">{day.label}</span>
                <input
                    type="time"
                    name={`schedule_times[${day.value}]`}
                    value={time}
                    onChange={(e) => setTime(e.target.value)}
                    disabled={!on}
                    className="text-xs p-1.5 border rounded-md disabled:opacity-40"
                />
            </label>
            {on && preview ? (
                <div className="pl-6 text-[10px] leading-snug space-y-0.5">
                    <div className="text-on-surface font-medium">
                        Student: <span className="font-bold">{preview.studentTime}</span>
                        <span className="text-on-surface-variant"> ({shortTzLabel(studentTimezone, timezones)})</span>
                    </div>
                    <div className={preview.sameZone ? 'text-on-surface-variant' : 'text-primary font-semibold'}>
                        Admin: <span className="font-bold">{preview.adminTime}</span>
                        <span className="font-normal text-on-surface-variant"> ({shortTzLabel(adminTimezone, timezones)})</span>
                        {preview.adminDate && preview.adminDate !== preview.studentDate ? (
                            <span className="font-normal text-on-surface-variant"> · {preview.adminDate}</span>
                        ) : null}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

function TutorAssignForm({ assignUrl, autoAssignUrl, tutorId, tutorName, tutorsAvailability, csrfToken, returnTab }) {
    const [busy, setBusy] = useState('');
    const [selectedTutorId, setSelectedTutorId] = useState(tutorId ? String(tutorId) : '');

    useEffect(() => {
        setSelectedTutorId(tutorId ? String(tutorId) : '');
    }, [tutorId]);

    return (
        <td className="staff-ops-cell staff-ops-cell--assign">
            <div className="staff-ops-stack">
                {autoAssignUrl ? (
                    <form method="POST" action={autoAssignUrl} onSubmit={() => setBusy('auto')}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="return_tab" value={returnTab} />
                        <button type="submit" disabled={Boolean(busy)} className="staff-ops-btn staff-ops-btn--soft">
                            {busy === 'auto' ? 'Assigning…' : 'Auto-assign'}
                        </button>
                    </form>
                ) : null}
                <form
                    method="POST"
                    action={assignUrl}
                    className="staff-assign-row"
                    onSubmit={(e) => {
                        if (!selectedTutorId) {
                            e.preventDefault();
                            return;
                        }
                        setBusy('manual');
                    }}
                >
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="return_tab" value={returnTab} />
                    <input type="hidden" name="timezone" className="user-timezone-input" defaultValue="UTC" />
                    <input type="hidden" name="tutor_id" value={selectedTutorId} />
                    <select
                        value={selectedTutorId}
                        required
                        onChange={(e) => setSelectedTutorId(e.target.value)}
                        className="staff-assign-select"
                    >
                        <option value="">Select tutor</option>
                        {(tutorsAvailability || []).map((item) => (
                            <option key={item.tutorId} value={String(item.tutorId)}>
                                {item.isOccupied
                                    ? `[Busy] ${item.tutorName}${item.tutorEmail ? ` (${item.tutorEmail})` : ''}`
                                    : `${item.tutorName}${item.tutorEmail ? ` (${item.tutorEmail})` : ''}`}
                            </option>
                        ))}
                    </select>
                    <button
                        type="submit"
                        disabled={Boolean(busy) || !selectedTutorId || !(tutorsAvailability || []).length}
                        className="staff-ops-btn"
                    >
                        {busy === 'manual' ? 'Saving…' : tutorId ? 'Reassign' : 'Assign'}
                    </button>
                </form>
                {tutorId ? (
                    <div className="staff-ops-meta staff-ops-meta--ok">
                        <Icon name="check_circle" />
                        <span>
                            Assigned to {tutorName || 'tutor'}
                            {(() => {
                                const match = (tutorsAvailability || []).find((t) => String(t.tutorId) === String(tutorId));
                                return match?.tutorEmail ? ` (${match.tutorEmail})` : '';
                            })()}
                        </span>
                    </div>
                ) : (
                    <div className="staff-ops-meta staff-ops-meta--warn">No tutor assigned yet</div>
                )}
                {!(tutorsAvailability || []).length ? (
                    <div className="staff-ops-meta">No tutors available for this slot</div>
                ) : null}
            </div>
        </td>
    );
}

function ZoomLinks({ joinUrl, startUrl }) {
    return (
        <td className="staff-ops-cell staff-ops-cell--zoom">
            <div className="staff-ops-stack">
                {joinUrl ? (
                    <a href={joinUrl} target="_blank" rel="noreferrer" className="staff-zoom-link staff-zoom-link--join">
                        <Icon name="videocam" /> Student join
                    </a>
                ) : null}
                {startUrl ? (
                    <a href={startUrl} target="_blank" rel="noreferrer" className="staff-zoom-link staff-zoom-link--host">
                        <Icon name="play_circle" /> Teacher host
                    </a>
                ) : null}
                {!joinUrl && !startUrl ? <span className="staff-ops-meta">Creates on assign</span> : null}
            </div>
        </td>
    );
}

export default function Index(props) {
    const {
        tab: initialTab = 'enrollments',
        filters = {},
        students = [],
        courses = [],
        timezones = [],
        weekDays = [],
        scheduleWeekDays = [],
        enrollments = { data: [], links: [] },
        bookings = { data: [], links: [] },
        campBookings = { data: [], links: [], total: 0 },
        routes = {},
    } = props;
    const { csrfToken, flash } = usePage().props;
    const [tab, setTab] = useState(initialTab);
    const [cancelTarget, setCancelTarget] = useState(null);
    const [cancelBusy, setCancelBusy] = useState(false);
    const [selectedStudentId, setSelectedStudentId] = useState(students[0]?.id ? String(students[0].id) : '');
    const [selectedTimezone, setSelectedTimezone] = useState(students[0]?.timezone || 'America/New_York');
    const [scheduleStartDate, setScheduleStartDate] = useState('');
    const [adminTimezone, setAdminTimezone] = useState('UTC');

    const enrollTimezoneOptions = useMemo(
        () => mergeTimezoneOptions(timezones, selectedTimezone ? { id: selectedTimezone } : null),
        [timezones, selectedTimezone]
    );

    const handleStudentChange = (e) => {
        const sId = e.target.value;
        setSelectedStudentId(sId);
        const found = students.find((s) => String(s.id) === String(sId));
        if (found?.timezone) {
            setSelectedTimezone(found.timezone);
        }
    };

    useEffect(() => {
        setTab(initialTab);
    }, [initialTab]);

    useEffect(() => {
        const adminTz = detectBrowserTimezone();
        setAdminTimezone(adminTz);
        document.querySelectorAll('.user-timezone-input').forEach((el) => { el.value = adminTz; });
        document.querySelectorAll('.auto-timezone-select').forEach((el) => {
            const current = el.getAttribute('data-current');
            if (!current || current === 'UTC') {
                const match = Array.from(el.options).find((o) => o.value === adminTz);
                if (match) el.value = adminTz;
            }
        });
    }, [tab, bookings.data, campBookings.data]);

    const switchTab = (next) => {
        setTab(next);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', next);
        window.history.replaceState({}, '', url.toString());
    };

    return (
        <DashboardLayout title="Enrollments">
            <Head title="Enrollments & Demos" />
            <div className="staff-page">
                <PageHeader
                    variant="staff"
                    eyebrow="Operations"
                    title="Enrollment management"
                    description="Manual enroll, demos, and tutor auto-assign."
                />

                {flash?.success ? <div className="p-4 bg-tertiary-fixed text-tertiary rounded-xl font-medium border border-tertiary-fixed-dim">{flash.success}</div> : null}
                {flash?.error ? <div className="p-4 bg-error-container text-on-error-container rounded-xl font-medium">{flash.error}</div> : null}

                <PortalCard title="Manual enrollment" className="max-w-4xl">
                <form method="POST" action={routes.store} className="space-y-5">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-bold mb-1">Student</label>
                            <select
                                name="user_id"
                                required
                                value={selectedStudentId}
                                onChange={handleStudentChange}
                                className="w-full px-4 py-3 border rounded-xl"
                            >
                                {students.map((s) => <option key={s.id} value={s.id}>{s.name} ({s.email})</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-1">Course</label>
                            <select name="course_id" required className="w-full px-4 py-3 border rounded-xl">
                                {courses.map((c) => <option key={c.id} value={c.id}>{c.title}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-1">Number of Classes</label>
                            <input type="number" name="number_of_classes" min={1} max={48} placeholder="Default: course classes" className="w-full px-4 py-3 border rounded-xl" />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-bold mb-1">Learning Mode</label>
                            <select name="delivery_mode" className="w-full px-4 py-3 border rounded-xl">
                                <option value="live">Live</option>
                                <option value="self-paced">Self-paced</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-1">Schedule Start Date</label>
                            <input
                                type="date"
                                name="schedule_start_date"
                                value={scheduleStartDate}
                                onChange={(e) => setScheduleStartDate(e.target.value)}
                                className="w-full px-4 py-3 border rounded-xl"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-bold mb-1">Student / Child Timezone</label>
                            <select
                                name="timezone"
                                value={selectedTimezone}
                                onChange={(e) => setSelectedTimezone(e.target.value)}
                                className="w-full px-4 py-3 border rounded-xl"
                            >
                                {enrollTimezoneOptions.map((tz) => <option key={tz.id} value={tz.id}>{tz.label}</option>)}
                            </select>
                            <span className="text-xs text-on-surface-variant/80 mt-1 block">
                                Child zone. Your admin zone: <strong>{shortTzLabel(adminTimezone, enrollTimezoneOptions)}</strong>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-sm font-bold">Weekly Class Schedule (in Student&apos;s Timezone)</label>
                        </div>
                        <p className="text-xs text-on-surface-variant mb-2">
                            Enter the time in the <strong>child&apos;s</strong> timezone. Each selected day shows the matching time in <strong>your admin timezone</strong> so you can confirm before saving.
                        </p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            {weekDays.map((day) => (
                                <DayTimeRow
                                    key={day.value}
                                    day={day}
                                    defaultOn={false}
                                    defaultTime="17:00"
                                    studentTimezone={selectedTimezone}
                                    adminTimezone={adminTimezone}
                                    timezones={enrollTimezoneOptions}
                                    refDate={scheduleStartDate}
                                />
                            ))}
                        </div>
                    </div>
                    <div className="flex justify-end">
                        <button type="submit" className="btn-primary btn-sm">Manual Enroll</button>
                    </div>
                </form>
                </PortalCard>

                <PortalCard title="Filters">
                    <form method="GET" action={routes.index} className="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="tab" value={tab} />
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-sm font-bold mb-1">Search Student</label>
                            <input type="text" name="search" defaultValue={filters.search} placeholder="Name or email..." className="w-full px-4 py-2 border rounded-xl" />
                        </div>
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-sm font-bold mb-1">Filter by Course</label>
                            <select name="course_id" defaultValue={filters.courseId} className="w-full px-4 py-2 border rounded-xl">
                                <option value="">All Courses</option>
                                {courses.map((c) => <option key={c.id} value={c.id}>{c.title}</option>)}
                            </select>
                        </div>
                        <div className="flex-1 min-w-[150px]">
                            <label className="block text-sm font-bold mb-1">Status</label>
                            <select name="status" defaultValue={filters.status} className="w-full px-4 py-2 border rounded-xl">
                                <option value="">All Statuses</option>
                                {['active', 'completed', 'pending', 'cancelled'].map((s) => <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>)}
                            </select>
                        </div>
                        <button type="submit" className="btn-primary py-2 px-4">Apply Filters</button>
                        <Link href={routes.index} className="bg-surface-container-low py-2 px-4 rounded-xl font-bold">Clear</Link>
                    </form>
                </PortalCard>

                <div className="flex gap-2 border-b border-outline-variant overflow-x-auto">
                    {[
                        { id: 'enrollments', label: 'Full Course Enrollments' },
                        { id: 'bookings', label: 'Course Bookings & 1:1 Classes' },
                        { id: 'demos', label: 'Free Consultations & Trials', badge: campBookings.total },
                    ].map((t) => (
                        <button key={t.id} type="button" onClick={() => switchTab(t.id)} className={`px-6 py-3 font-bold border-b-2 transition-colors flex items-center gap-2 ${tab === t.id ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'}`}>
                            {t.label}
                            {t.badge != null ? <span className="px-2 py-0.5 rounded-full text-xs font-extrabold bg-warning-container text-warning">{t.badge}</span> : null}
                        </button>
                    ))}
                </div>

                {tab === 'enrollments' && (
                    <PortalCard padding={false}>
                        <DataTableShell
                            className="p-1"
                            minWidth="1100px"
                            mobileCards={enrollments.data.map((e) => (
                                <div key={e.id} className="staff-mobile-card min-w-0 space-y-1">
                                    <p className="font-bold break-words">{e.studentName}</p>
                                    <p className="text-xs text-on-surface-variant truncate">{e.studentEmail}</p>
                                    <p className="text-sm break-words">{e.courseTitle}</p>
                                    <p className="text-xs">{e.status} · {e.progress}%</p>
                                </div>
                            ))}
                        >
                            <table className="staff-table staff-table--ops w-full text-left">
                                <thead>
                                    <tr>
                                        {['Student', 'Course', 'Progress', 'Status', 'Date', 'Action'].map((h) => (
                                            <th key={h} className={h === 'Action' ? 'text-right' : ''}>{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {enrollments.data.map((e) => (
                                        <tr key={e.id}>
                                            <td className="staff-ops-cell" style={{ minWidth: '14rem' }}>
                                                <div className="font-semibold text-on-surface">{e.studentName}</div>
                                                <div className="text-xs text-on-surface-variant truncate">{e.studentEmail}</div>
                                            </td>
                                            <td className="staff-ops-cell min-w-0 break-words">{e.courseTitle}</td>
                                            <td className="staff-ops-cell">{e.progress}%</td>
                                            <td className="staff-ops-cell"><span className="staff-status-pill">{e.status}</span></td>
                                            <td className="staff-ops-cell text-sm whitespace-nowrap">{formatLocalTime(e.createdAt, 'date')}</td>
                                            <td className="staff-ops-cell text-right">
                                                <div className="staff-ops-stack" style={{ alignItems: 'flex-end' }}>
                                                    <form method="POST" action={e.certificateUploadUrl} encType="multipart/form-data" className="staff-assign-row">
                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                        <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required className="text-xs max-w-[140px]" />
                                                        <button type="submit" className="staff-ops-btn">Upload cert</button>
                                                    </form>
                                                    {e.hasCertificate ? <span className="staff-ops-meta staff-ops-meta--ok">Certificate uploaded</span> : null}
                                                    {e.sendEmailUrl && e.status !== 'cancelled' && (
                                                        <form method="POST" action={e.sendEmailUrl} style={{ display: 'inline', marginTop: '4px' }}>
                                                            <input type="hidden" name="_token" value={csrfToken} />
                                                            <button
                                                                type="submit"
                                                                className="staff-ops-link"
                                                                style={{ color: '#046bd2', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '4px' }}
                                                                title="Send enrollment confirmation & class details email to student and linked parents"
                                                            >
                                                                ✉️ Send Details Email
                                                            </button>
                                                        </form>
                                                    )}
                                                    {e.status !== 'cancelled' && (
                                                        <button
                                                            type="button"
                                                            className="staff-ops-link"
                                                            style={{ color: 'var(--color-error, #b3261e)' }}
                                                            onClick={() => setCancelTarget(e)}
                                                        >
                                                            Cancel
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {!enrollments.data.length && (
                                        <tr>
                                            <td colSpan={6} className="p-4">
                                                <EmptyState title="No enrollments" description="No enrollments match your filters." />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </DataTableShell>
                        <PaginationLinks links={enrollments.links} />
                    </PortalCard>
                )}

                {tab === 'bookings' && (
                    <PortalCard padding={false}>
                        <DataTableShell className="p-1" minWidth="1680px">
                            <table className="staff-table staff-table--ops w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        {['Student', 'Course / type', 'Schedule', 'Assign tutor', 'Zoom links', 'Status', 'Date booked'].map((h) => (
                                            <th key={h}>{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {bookings.data.map((b) => (
                                        <tr key={b.id}>
                                            <td className="staff-ops-cell" style={{ minWidth: '14rem' }}>
                                                <div className="font-semibold text-on-surface">{b.studentName}</div>
                                                <div className="text-xs text-on-surface-variant whitespace-nowrap">{b.studentEmail}</div>
                                            </td>
                                            <td className="staff-ops-cell" style={{ minWidth: '12rem' }}>
                                                <span className="font-medium text-on-surface">{b.courseTitle}</span>
                                                <div className="text-[11px] text-on-surface-variant mt-0.5 font-medium">{b.sessionTypeLabel}</div>
                                            </td>
                                            <ScheduleEditor booking={b} scheduleWeekDays={scheduleWeekDays} timezones={timezones} csrfToken={csrfToken} adminTimezone={adminTimezone} />
                                            <TutorAssignForm assignUrl={b.assignTutorUrl} autoAssignUrl={b.autoAssignUrl} tutorId={b.tutorId} tutorName={b.tutorName} tutorsAvailability={b.tutorsAvailability} csrfToken={csrfToken} returnTab="bookings" />
                                            <ZoomLinks joinUrl={b.zoomJoinUrl} startUrl={b.zoomStartUrl} />
                                            <td className="staff-ops-cell">
                                                <span className="staff-status-pill">{b.status}</span>
                                            </td>
                                            <td className="staff-ops-cell text-xs text-on-surface-variant whitespace-nowrap">{formatLocalTime(b.createdAt, 'date')}</td>
                                        </tr>
                                    ))}
                                    {!bookings.data.length && <tr><td colSpan={7} className="p-8 text-center text-on-surface-variant font-medium">No bookings found matching your filters.</td></tr>}
                                </tbody>
                            </table>
                        </DataTableShell>
                        <PaginationLinks links={bookings.links} />
                    </PortalCard>
                )}

                {tab === 'demos' && (
                    <PortalCard padding={false}>
                        <DataTableShell className="p-1" minWidth="1680px">
                            <table className="staff-table staff-table--ops w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        {['Parent & student', 'Consultation / trial', 'Schedule', 'Assign tutor', 'Zoom links', 'Status', 'Date applied'].map((h) => (
                                            <th key={h}>{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {campBookings.data.map((cb) => (
                                        <tr key={cb.id}>
                                            <td className="staff-ops-cell" style={{ minWidth: '15rem' }}>
                                                <div className="font-semibold text-on-surface">{cb.childName ? `${cb.childName} (Student)` : 'Applied Student'}</div>
                                                <div className="text-xs text-on-surface font-medium">Parent: {cb.parentName}</div>
                                                <div className="text-xs text-on-surface-variant whitespace-nowrap">{cb.email}</div>
                                                {cb.phone ? <div className="text-xs text-on-surface-variant whitespace-nowrap">{cb.phone}</div> : null}
                                            </td>
                                            <td className="staff-ops-cell" style={{ minWidth: '13rem' }}>
                                                <span className="font-semibold text-on-surface">{cb.campName}</span>
                                                <div className="text-xs text-on-surface-variant mt-0.5">Age: {cb.age ?? 'N/A'} · {cb.country ?? 'Global'}</div>
                                            </td>
                                            <CampScheduleCell camp={cb} timezones={timezones} csrfToken={csrfToken} adminTimezone={adminTimezone} />
                                            <TutorAssignForm assignUrl={cb.assignTutorUrl} autoAssignUrl={null} tutorId={cb.tutorId} tutorName={cb.tutorName} tutorsAvailability={cb.tutorsAvailability} csrfToken={csrfToken} returnTab="demos" />
                                            <ZoomLinks joinUrl={cb.zoomJoinUrl} startUrl={cb.zoomStartUrl} />
                                            <td className="staff-ops-cell">
                                                <span className={`staff-status-pill ${cb.statusClass === 'assigned' ? 'is-ok' : 'is-warn'}`}>{cb.statusLabel}</span>
                                            </td>
                                            <td className="staff-ops-cell text-xs text-on-surface-variant whitespace-nowrap">{formatLocalTime(cb.createdAt, 'date')}</td>
                                        </tr>
                                    ))}
                                    {!campBookings.data.length && <tr><td colSpan={7} className="p-8 text-center text-on-surface-variant font-medium">No demo bookings found.</td></tr>}
                                </tbody>
                            </table>
                        </DataTableShell>
                        <PaginationLinks links={campBookings.links} />
                    </PortalCard>
                )}
            </div>
            <ConfirmDialog
                open={Boolean(cancelTarget)}
                title="Cancel enrollment?"
                message={
                    cancelTarget
                        ? `Cancel enrollment for ${cancelTarget.studentName} in ${cancelTarget.courseTitle}? This cannot be undone from here.`
                        : ''
                }
                confirmLabel="Cancel enrollment"
                cancelLabel="Keep enrollment"
                busy={cancelBusy}
                onCancel={() => (!cancelBusy ? setCancelTarget(null) : null)}
                onConfirm={() => {
                    if (!cancelTarget?.cancelUrl || cancelBusy) return;
                    setCancelBusy(true);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = cancelTarget.cancelUrl;
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = csrfToken;
                    form.appendChild(token);
                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);
                    document.body.appendChild(form);
                    form.submit();
                }}
            />
        </DashboardLayout>
    );
}

function CampScheduleCell({ camp, timezones, csrfToken, adminTimezone }) {
    const [open, setOpen] = useState(false);
    const [studentTz, setStudentTz] = useState(camp.timezone || 'UTC');
    const [localAt, setLocalAt] = useState(camp.scheduledLocal || '');
    const tzOptions = useMemo(
        () => mergeTimezoneOptions(timezones, studentTz ? { id: studentTz } : null),
        [timezones, studentTz]
    );

    useEffect(() => {
        if (open) {
            setStudentTz(camp.timezone || 'UTC');
            setLocalAt(camp.scheduledLocal || '');
        }
    }, [open, camp.timezone, camp.scheduledLocal]);

    const preview = useMemo(
        () => (open ? previewFromDateTimeLocal(localAt, studentTz, adminTimezone) : null),
        [open, localAt, studentTz, adminTimezone]
    );

    return (
        <td className="staff-ops-cell">
            {!open ? (
                <div className="staff-ops-stack">
                    {camp.scheduledAt ? (
                        <>
                            <div className="font-semibold text-on-surface whitespace-nowrap">{formatLocalTime(camp.scheduledAt, 'date')}</div>
                            <div className="text-xs font-medium text-primary whitespace-nowrap">{formatLocalTime(camp.scheduledAt, 'time')}</div>
                            {camp.studentOptedLabel ? <div className="text-[10px] text-outline mt-0.5">Student opted: {camp.studentOptedLabel}</div> : null}
                        </>
                    ) : (
                        <span className="text-outline">Not scheduled</span>
                    )}
                    <button type="button" onClick={() => setOpen(true)} className="staff-ops-link">
                        <Icon name="edit_calendar" /> {camp.scheduledAt ? 'Edit' : 'Schedule'}
                    </button>
                </div>
            ) : (
                <form method="POST" action={camp.scheduleUpdateUrl} className="flex flex-col gap-2 bg-surface-container-low p-2 rounded-xl border border-outline-variant mt-2">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <select
                        name="timezone"
                        required
                        value={studentTz}
                        onChange={(e) => setStudentTz(e.target.value)}
                        className="auto-timezone-select w-full text-xs p-2 border rounded-lg"
                        data-current={camp.timezone}
                    >
                        {tzOptions.map((tz) => <option key={tz.id} value={tz.id}>{tz.label}</option>)}
                    </select>
                    <input
                        type="datetime-local"
                        name="scheduled_at"
                        required
                        value={localAt}
                        onChange={(e) => setLocalAt(e.target.value)}
                        className="w-full text-xs p-2 border rounded-lg"
                    />
                    {preview ? (
                        <div className="text-[10px] leading-snug space-y-0.5 px-0.5">
                            <div>
                                Student: <strong>{preview.studentTime}</strong> ({shortTzLabel(studentTz, tzOptions)})
                            </div>
                            <div className="text-primary font-semibold">
                                Admin: <strong>{preview.adminTime}</strong> ({shortTzLabel(adminTimezone, tzOptions)})
                                {preview.adminDate ? ` · ${preview.adminDate}` : ''}
                            </div>
                        </div>
                    ) : null}
                    <div className="flex gap-1">
                        <button type="submit" className="flex-1 bg-primary text-white text-[11px] py-1 rounded-lg font-bold">Save</button>
                        <button type="button" onClick={() => setOpen(false)} className="flex-1 bg-surface-container text-on-surface text-[11px] py-1 rounded-lg font-bold">Cancel</button>
                    </div>
                </form>
            )}
        </td>
    );
}
