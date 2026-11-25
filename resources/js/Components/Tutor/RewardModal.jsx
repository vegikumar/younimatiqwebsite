import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';

export default function RewardModal({ presets = [], badges = [] }) {
    const { csrfToken } = usePage().props;
    const [show, setShow] = useState(false);
    const [studentId, setStudentId] = useState(null);
    const [studentName, setStudentName] = useState('');
    const [courseId, setCourseId] = useState('');
    const [bookingId, setBookingId] = useState('');
    const [isTrial, setIsTrial] = useState(false);
    const [selected, setSelected] = useState({});
    const [customLabel, setCustomLabel] = useState('');
    const [customXp, setCustomXp] = useState(0);
    const [tip, setTip] = useState('');
    const [badgeId, setBadgeId] = useState('');
    const [showNewBadge, setShowNewBadge] = useState(false);

    const open = (detail) => {
        const d = detail || {};
        setStudentId(d.id);
        setStudentName(d.name || 'this student');
        setCourseId(d.courseId ?? '');
        setBookingId(d.bookingId ?? '');
        setIsTrial(!!d.isTrial);
        setSelected({});
        setCustomLabel('');
        setCustomXp(0);
        setTip('');
        setBadgeId('');
        setShowNewBadge(false);
        setShow(true);
    };

    useEffect(() => {
        const handler = (e) => open(e.detail);
        window.addEventListener('open-reward', handler);
        return () => window.removeEventListener('open-reward', handler);
    }, []);

    const chosen = useMemo(() => {
        const out = [];
        presets.forEach((p, i) => {
            if (selected[i]) out.push({ label: p.label, xp: p.xp });
        });
        if ((customLabel && customLabel.trim()) || Number(customXp) > 0) {
            out.push({ label: customLabel.trim() || 'Great work', xp: Number(customXp) || 0 });
        }
        return out;
    }, [presets, selected, customLabel, customXp]);

    const total = chosen.reduce((sum, c) => sum + (Number(c.xp) || 0), 0);

    if (!show) return null;

    return (
        <>
            <div className="fixed inset-0 z-[120] flex items-start justify-center overflow-y-auto bg-black/60 p-4 py-10">
                <div className="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <div className="p-6 border-b border-outline-variant/20 flex items-center justify-between bg-gradient-to-r from-primary-container/50 to-tertiary-container/40">
                        <div>
                            <h3 className="font-bold text-lg text-on-surface flex items-center gap-2">
                                <span className="material-symbols-outlined text-primary">celebration</span>
                                Celebrate <span className="text-primary">{studentName}</span>
                            </h3>
                            <p className="text-xs text-on-surface-variant mt-0.5">Give XP compliments, one level-up tip and award a badge.</p>
                        </div>
                        <button type="button" onClick={() => setShow(false)} className="text-on-surface-variant hover:text-error text-2xl leading-none">
                            &times;
                        </button>
                    </div>

                    <form method="POST" action="/tutor/rewards" className="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="student_id" value={studentId ?? ''} />
                        <input type="hidden" name="course_id" value={courseId ?? ''} />
                        <input type="hidden" name="booking_id" value={bookingId ?? ''} />
                        <input type="hidden" name="is_trial" value={isTrial ? 1 : 0} />
                        {chosen.map((c, i) => (
                            <div key={i} className="hidden">
                                <input type="hidden" name={`compliments[${i}][label]`} value={c.label} />
                                <input type="hidden" name={`compliments[${i}][xp]`} value={c.xp} />
                            </div>
                        ))}

                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2">XP Compliments</label>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                {presets.map((p, i) => (
                                    <label
                                        key={i}
                                        className={`flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-colors ${
                                            selected[i] ? 'border-primary bg-primary/10' : 'border-outline-variant/30 hover:bg-surface-container-low'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            className="rounded"
                                            checked={!!selected[i]}
                                            onChange={() => setSelected((s) => ({ ...s, [i]: !s[i] }))}
                                        />
                                        <span className="material-symbols-outlined text-base text-on-surface-variant">{p.icon}</span>
                                        <span className="flex-1 text-xs font-medium text-on-surface">{p.label}</span>
                                        <span className="text-xs font-bold text-primary">+{p.xp} XP</span>
                                    </label>
                                ))}
                            </div>
                            <div className="mt-3 flex gap-2 items-center">
                                <input
                                    type="text"
                                    value={customLabel}
                                    onChange={(e) => setCustomLabel(e.target.value)}
                                    placeholder="Custom compliment (optional)"
                                    className="flex-1 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary"
                                />
                                <input
                                    type="number"
                                    value={customXp}
                                    onChange={(e) => setCustomXp(e.target.value)}
                                    min={0}
                                    max={1000}
                                    placeholder="XP"
                                    className="w-20 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2">Level-Up Tip</label>
                            <textarea
                                name="level_up_tip"
                                value={tip}
                                onChange={(e) => setTip(e.target.value)}
                                rows={2}
                                maxLength={500}
                                placeholder="One specific thing they can do to level up next time..."
                                className="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-on-surface mb-2">Award a Badge</label>
                            <div className="flex gap-2">
                                <select
                                    name="badge_id"
                                    value={badgeId}
                                    onChange={(e) => setBadgeId(e.target.value)}
                                    className="flex-1 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary"
                                >
                                    <option value="">— No badge —</option>
                                    {badges.map((b) => (
                                        <option key={b.id} value={b.id}>
                                            {b.title}
                                            {!b.isSystem ? ' (custom)' : ''}
                                        </option>
                                    ))}
                                </select>
                                <button type="button" onClick={() => setShowNewBadge(!showNewBadge)} className="text-xs font-bold text-primary bg-primary/10 px-3 py-2 rounded-xl whitespace-nowrap">
                                    + New badge
                                </button>
                            </div>
                            {showNewBadge ? (
                                <div className="mt-3 p-3 rounded-2xl border border-outline-variant/30 bg-surface-container-low space-y-2">
                                    <p className="text-[11px] text-on-surface-variant font-bold uppercase tracking-wider">Create a custom badge</p>
                                    <input type="text" form="newBadgeForm" name="title" required maxLength={80} placeholder="Badge name" className="w-full px-3 py-2 rounded-xl border text-xs" />
                                    <input type="text" form="newBadgeForm" name="description" maxLength={240} placeholder="Short description" className="w-full px-3 py-2 rounded-xl border text-xs" />
                                    <div className="flex gap-2">
                                        <input type="text" form="newBadgeForm" name="badge_icon" defaultValue="military_tech" className="flex-1 px-3 py-2 rounded-xl border text-xs" />
                                        <input type="hidden" form="newBadgeForm" name="course_id" value={courseId ?? ''} />
                                        <button type="submit" form="newBadgeForm" className="text-xs font-bold text-white bg-primary px-4 py-2 rounded-xl">
                                            Create badge
                                        </button>
                                    </div>
                                </div>
                            ) : null}
                        </div>

                        <div className="flex items-center justify-between pt-3 border-t border-outline-variant/20">
                            <div className="text-sm font-bold text-on-surface">
                                Total XP: <span className="text-tertiary text-lg">{total}</span>
                            </div>
                            <div className="flex gap-2">
                                <button type="button" onClick={() => setShow(false)} className="px-4 py-2 rounded-xl border text-sm font-bold text-on-surface-variant">
                                    Cancel
                                </button>
                                <button type="submit" disabled={total <= 0 && !tip && !badgeId} className="btn-enroll px-6 py-2 text-sm disabled:opacity-50">
                                    Award
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <form id="newBadgeForm" method="POST" action="/tutor/rewards/badges">
                <input type="hidden" name="_token" value={csrfToken} />
            </form>
        </>
    );
}

/** Call from buttons: openRewardModal({ id, name, courseId, bookingId, isTrial }) */
export function openRewardModal(detail) {
    window.dispatchEvent(new CustomEvent('open-reward', { detail }));
}
