export function formatLocalTime(iso, format = 'datetime') {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    // Never append timezone names on student-facing surfaces.
    const opts = {
        date: { year: 'numeric', month: 'short', day: 'numeric', timeZoneName: undefined },
        time: { hour: 'numeric', minute: '2-digit', timeZoneName: undefined },
        datetime: { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZoneName: undefined },
        'datetime-short': { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZoneName: undefined },
    };
    const base = opts[format] || opts.datetime;
    const { timeZoneName: _ignored, ...safe } = base;
    return d.toLocaleString(undefined, safe);
}

export function formatMoney(n) {
    const v = Number(n) || 0;
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function bookingHostLinkAvailable(scheduledAt) {
    if (!scheduledAt) return true;
    const scheduled = new Date(scheduledAt);
    const fourHoursBefore = scheduled.getTime() - 4 * 60 * 60 * 1000;
    return fourHoursBefore <= Date.now();
}

export function sessionEnded(iso, durationMinutes = 60) {
    if (!iso) return false;
    return new Date(iso).getTime() + durationMinutes * 60 * 1000 < Date.now();
}
