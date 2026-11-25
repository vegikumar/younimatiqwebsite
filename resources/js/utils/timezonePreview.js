/** Browser / admin timezone (fallback UTC). */
export function detectBrowserTimezone() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    } catch {
        return 'UTC';
    }
}

/** Short city-style label from an IANA id. */
export function shortTzLabel(tz, fallbackList = []) {
    if (!tz) return 'UTC';
    const hit = fallbackList.find((t) => t.id === tz);
    if (hit?.label) return hit.label;
    const parts = String(tz).split('/');
    return parts[parts.length - 1].replace(/_/g, ' ');
}

/**
 * Interpret YYYY-MM-DD + HH:MM as wall-clock in `fromTz`, return a Date (UTC instant).
 */
export function wallClockToDate(dateStr, timeStr, fromTz) {
    if (!dateStr || !timeStr || !fromTz) return null;
    const [y, mo, d] = dateStr.split('-').map(Number);
    const [hh, mm] = timeStr.split(':').map(Number);
    if (![y, mo, d, hh, mm].every((n) => Number.isFinite(n))) return null;

    const desiredAsUtcMs = Date.UTC(y, mo - 1, d, hh, mm, 0);
    let guess = new Date(desiredAsUtcMs);

    const fmt = new Intl.DateTimeFormat('en-US', {
        timeZone: fromTz,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    for (let i = 0; i < 4; i += 1) {
        const parts = Object.fromEntries(fmt.formatToParts(guess).filter((p) => p.type !== 'literal').map((p) => [p.type, p.value]));
        const asUtc = Date.UTC(
            Number(parts.year),
            Number(parts.month) - 1,
            Number(parts.day),
            Number(parts.hour) % 24,
            Number(parts.minute),
            Number(parts.second || 0)
        );
        const diff = desiredAsUtcMs - asUtc;
        if (diff === 0) break;
        guess = new Date(guess.getTime() + diff);
    }

    return guess;
}

/** Format a Date in a timezone as "7:00 PM". */
export function formatTimeInZone(date, timeZone) {
    if (!date || Number.isNaN(date.getTime())) return '';
    try {
        return new Intl.DateTimeFormat('en-US', {
            timeZone,
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(date);
    } catch {
        return '';
    }
}

/** Format weekday + date in a timezone, e.g. "Mon, Nov 22". */
export function formatDateInZone(date, timeZone) {
    if (!date || Number.isNaN(date.getTime())) return '';
    try {
        return new Intl.DateTimeFormat('en-US', {
            timeZone,
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        }).format(date);
    } catch {
        return '';
    }
}

/**
 * Convert student wall-clock time to admin timezone preview strings.
 * Uses refDate (YYYY-MM-DD) or today in the student zone.
 */
export function previewAdminTime(timeHHMM, studentTz, adminTz, refDate = null) {
    if (!timeHHMM || !studentTz || !adminTz) return null;
    const dateStr = refDate || new Date().toISOString().slice(0, 10);
    const instant = wallClockToDate(dateStr, timeHHMM, studentTz);
    if (!instant) return null;

    return {
        studentTime: formatTimeInZone(instant, studentTz),
        adminTime: formatTimeInZone(instant, adminTz),
        adminDate: formatDateInZone(instant, adminTz),
        studentDate: formatDateInZone(instant, studentTz),
        sameZone: studentTz === adminTz,
    };
}

/**
 * Preview for a datetime-local value interpreted in studentTz.
 */
export function previewFromDateTimeLocal(localValue, studentTz, adminTz) {
    if (!localValue || !studentTz || !adminTz) return null;
    const [dateStr, timePart] = localValue.split('T');
    if (!dateStr || !timePart) return null;
    const timeHHMM = timePart.slice(0, 5);
    return previewAdminTime(timeHHMM, studentTz, adminTz, dateStr);
}
