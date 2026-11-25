import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import PageHeader from '../../Components/Portal/PageHeader';
import PortalCard from '../../Components/Portal/PortalCard';
import FormErrors from '../../Components/Portal/FormErrors';
import SubmitButton from '../../Components/Portal/SubmitButton';
import FormField from '../../Components/Portal/FormField';

const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const HOURS = Array.from({ length: 24 }, (_, i) => i);

function gridToSlots(grid = []) {
    const slots = {};
    grid.forEach((row) => {
        slots[String(row.day)] = (row.hours || []).map(Number);
    });
    return slots;
}

export default function Availability({ availabilityCommitment = 'flexible', grid = [] }) {
    const { flash } = usePage().props;
    const [slots, setSlots] = useState(() => gridToSlots(grid));

    const form = useForm({
        availability_commitment: availabilityCommitment,
        slots: gridToSlots(grid),
    });

    const selectedCount = useMemo(
        () => Object.values(slots).reduce((n, hours) => n + (hours?.length || 0), 0),
        [slots]
    );

    const toggleHour = (day, hour) => {
        const key = String(day);
        const current = new Set(slots[key] || []);
        if (current.has(hour)) current.delete(hour);
        else current.add(hour);
        const next = { ...slots, [key]: Array.from(current).sort((a, b) => a - b) };
        setSlots(next);
        form.setData('slots', next);
    };

    const submit = (e) => {
        e.preventDefault();
        form.transform((data) => ({
            availability_commitment: data.availability_commitment,
            slots,
        })).post('/tutor/availability');
    };

    return (
        <DashboardLayout title="Availability">
            <Head title="Availability" />
            <div className="staff-page max-w-5xl">
                <PageHeader
                    variant="staff"
                    title="My availability"
                    description={`Select weekly hours (0–23). Currently selected: ${selectedCount} hour slot${selectedCount === 1 ? '' : 's'}.`}
                />
                <PortalCard>
                    {flash?.success ? (
                        <div className="mb-4 p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold text-sm">
                            {flash.success}
                        </div>
                    ) : null}
                    {flash?.error ? (
                        <div className="mb-4 p-3 rounded-xl bg-error-container text-on-error-container font-bold text-sm">
                            {flash.error}
                        </div>
                    ) : null}
                    <FormErrors errors={form.errors} className="mb-4" />

                    <form onSubmit={submit} className="space-y-6">
                        <FormField
                            label="Availability commitment"
                            name="availability_commitment"
                            required
                            error={form.errors.availability_commitment}
                            className="max-w-md"
                        >
                            <select
                                id="availability_commitment"
                                required
                                value={form.data.availability_commitment}
                                onChange={(e) => form.setData('availability_commitment', e.target.value)}
                                className="form-input"
                            >
                                <option value="flexible">Flexible</option>
                                <option value="part_time">Part-time</option>
                                <option value="full_time">Full-time</option>
                            </select>
                        </FormField>

                        <div className="overflow-x-auto -mx-1 px-1">
                            <div className="min-w-[640px] space-y-3">
                                {DAY_LABELS.map((label, day) => (
                                    <div key={day} className="border border-outline-variant/30 rounded-xl p-3 bg-surface-container-low/40">
                                        <div className="font-bold text-sm mb-2">{label}</div>
                                        <div className="flex flex-wrap gap-1.5">
                                            {HOURS.map((hour) => {
                                                const on = (slots[String(day)] || []).includes(hour);
                                                return (
                                                    <button
                                                        key={hour}
                                                        type="button"
                                                        onClick={() => toggleHour(day, hour)}
                                                        className={`w-10 h-9 rounded-lg text-xs font-bold border transition-colors ${
                                                            on
                                                                ? 'bg-primary text-white border-primary'
                                                                : 'bg-white text-on-surface-variant border-outline-variant/40 hover:border-primary'
                                                        }`}
                                                        aria-pressed={on}
                                                    >
                                                        {String(hour).padStart(2, '0')}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <SubmitButton processing={form.processing} className="btn-primary py-2.5 px-5 text-sm font-bold">
                            Save availability
                        </SubmitButton>
                    </form>
                </PortalCard>
            </div>
        </DashboardLayout>
    );
}
