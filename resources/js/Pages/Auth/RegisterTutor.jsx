import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

const VERTICALS = ['Coding', 'Robotics/Arduino', 'AI', 'Game Design', 'Art', 'Math', 'English/ELA', 'Science'];
const AGE_BANDS = ['5-7', '8-10', '11-13', '14-16'];
const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const emptyDays = () =>
    DAYS.map((day) => ({ day, enabled: false, from: '09:00', to: '17:00' }));

/** Normalize browser time values (e.g. "9:00", "09:00:00") to HH:MM. */
function normalizeTime(value) {
    const raw = String(value || '').trim();
    const match = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
    if (!match) return '';
    const hour = Math.min(23, Number(match[1]));
    const minute = Math.min(59, Number(match[2]));
    if (Number.isNaN(hour) || Number.isNaN(minute)) return '';
    return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
}

function buildScheduleSlots(rows) {
    return rows
        .filter((row) => row.enabled)
        .map((row) => {
            const from = normalizeTime(row.from);
            const to = normalizeTime(row.to);
            if (!from || !to || from >= to) return null;
            return `${row.day} ${from}-${to}`;
        })
        .filter(Boolean);
}

const MAX_VIDEO_BYTES = 50 * 1024 * 1024;
const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

function fileTooLarge(file, maxBytes, label) {
    if (file && file.size > maxBytes) {
        const mb = Math.round(maxBytes / (1024 * 1024));
        alert(`${label} must be under ${mb}MB. Your file is ${(file.size / (1024 * 1024)).toFixed(1)}MB.`);
        return true;
    }
    return false;
}

export default function RegisterTutor() {
    const { errors } = usePage().props;
    const errList = Object.values(errors || {}).flat();
    const [step, setStep] = useState(1);
    const [dayRows, setDayRows] = useState(emptyDays);
    const [submitting, setSubmitting] = useState(false);
    const [uploadPct, setUploadPct] = useState(null);

    const { data, setData, post, processing, transform, clearErrors } = useForm({
        name: '',
        email: '',
        phone: '',
        city_country: '',
        password: '',
        password_confirmation: '',
        linkedin_url: '',
        source: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
        profile_photo: null,
        is_18_plus: false,
        background_check_consent: false,
        child_safety_consent: false,
        intro_video: null,
        certifications: [],
        currency: 'USD',
        expected_hourly_rate: '',
        references: '',
        subject_verticals: [],
        preferred_age_bands: [],
        teaching_experience: '',
        countries_taught: '',
        platforms_taught: '',
        higher_education: '',
        higher_institution: '',
        english_fluency: '3',
        additional_languages: '',
        about_yourself: '',
        availability_commitment: '10-20 hrs/wk',
        preferred_schedule: [],
        start_date: new Date().toISOString().slice(0, 10),
        has_laptop_camera: false,
        has_stable_internet: false,
        has_quiet_workspace: false,
        comfort_with_zoom: false,
        internet_speed_screenshot: null,
    });

    const selectedSlots = useMemo(() => buildScheduleSlots(dayRows), [dayRows]);

    const toggleArray = (key, value) => {
        const cur = data[key] || [];
        setData(key, cur.includes(value) ? cur.filter((v) => v !== value) : [...cur, value]);
    };

    const validateAvailability = () => {
        const enabled = dayRows.filter((r) => r.enabled);
        if (enabled.length < 1) {
            alert('Please enable at least one day and set From / To times.');
            return false;
        }
        for (const row of enabled) {
            const from = normalizeTime(row.from);
            const to = normalizeTime(row.to);
            if (!from || !to) {
                alert(`Please set From and To times for ${row.day}.`);
                return false;
            }
            if (from >= to) {
                alert(`For ${row.day}, From time must be earlier than To time.`);
                return false;
            }
        }
        if (buildScheduleSlots(dayRows).length < 1) {
            alert('Please enable at least one day and set From / To times.');
            return false;
        }
        return true;
    };

    const nextStep = () => {
        if (step === 3 && !validateAvailability()) return;
        setStep((s) => Math.min(3, s + 1));
    };

    const submit = (e) => {
        e.preventDefault();
        if (processing || submitting) return;
        if (!validateAvailability()) return;

        const slots = buildScheduleSlots(dayRows);
        if (slots.length < 1) {
            alert('Please enable at least one day and set From / To times.');
            return;
        }

        if (!data.profile_photo || !data.intro_video || !data.internet_speed_screenshot || !(data.certifications || []).length) {
            alert('Some required files are missing. Please go back to step 1 and re-attach your photo, intro video, and certifications.');
            setStep(1);
            return;
        }
        if (fileTooLarge(data.intro_video, MAX_VIDEO_BYTES, 'Intro video')) {
            setStep(1);
            return;
        }
        if (fileTooLarge(data.profile_photo, MAX_IMAGE_BYTES, 'Profile photo')) {
            setStep(1);
            return;
        }
        if (fileTooLarge(data.internet_speed_screenshot, MAX_IMAGE_BYTES, 'Internet speed screenshot')) {
            setStep(3);
            return;
        }
        for (const cert of data.certifications || []) {
            if (fileTooLarge(cert, MAX_IMAGE_BYTES, 'Certification file')) {
                setStep(1);
                return;
            }
        }

        // Inertia useForm ignores visit-option `transform`; use form.transform() + sync data.
        setData('preferred_schedule', slots);
        transform((form) => ({
            ...form,
            preferred_schedule: slots,
            is_18_plus: form.is_18_plus ? 1 : 0,
            background_check_consent: form.background_check_consent ? 1 : 0,
            child_safety_consent: form.child_safety_consent ? 1 : 0,
            has_laptop_camera: form.has_laptop_camera ? 1 : 0,
            has_stable_internet: form.has_stable_internet ? 1 : 0,
            has_quiet_workspace: form.has_quiet_workspace ? 1 : 0,
            comfort_with_zoom: form.comfort_with_zoom ? 1 : 0,
        }));

        clearErrors();
        setSubmitting(true);
        setUploadPct(0);
        post('/register/tutor', {
            forceFormData: true,
            onProgress: (progress) => {
                if (progress?.percentage != null) {
                    setUploadPct(Math.round(progress.percentage));
                }
            },
            onFinish: () => {
                setSubmitting(false);
                setUploadPct(null);
            },
            onError: (errs) => {
                setSubmitting(false);
                setUploadPct(null);
                const keys = Object.keys(errs || {});
                if (keys.some((k) => ['name', 'email', 'password', 'phone', 'city_country', 'profile_photo', 'intro_video', 'certifications', 'is_18_plus', 'background_check_consent', 'child_safety_consent', 'currency', 'expected_hourly_rate'].includes(k) || k.startsWith('certifications'))) {
                    setStep(1);
                } else if (keys.some((k) => ['subject_verticals', 'preferred_age_bands', 'teaching_experience', 'higher_education', 'higher_institution', 'about_yourself', 'english_fluency'].includes(k))) {
                    setStep(2);
                } else {
                    setStep(3);
                }
            },
            onCancel: () => {
                setSubmitting(false);
                setUploadPct(null);
                alert('Upload was cancelled or timed out. Please try again with a smaller intro video (under 50MB).');
            },
        });
    };

    const updateDay = (index, patch) => {
        setDayRows((rows) => {
            const next = rows.map((r, i) => {
                if (i !== index) return r;
                const merged = { ...r, ...patch };
                if (Object.prototype.hasOwnProperty.call(patch, 'from')) {
                    merged.from = normalizeTime(patch.from) || patch.from || r.from;
                }
                if (Object.prototype.hasOwnProperty.call(patch, 'to')) {
                    merged.to = normalizeTime(patch.to) || patch.to || r.to;
                }
                return merged;
            });
            // Defer form sync so we don't setState during another setState.
            queueMicrotask(() => setData('preferred_schedule', buildScheduleSlots(next)));
            return next;
        });
    };

    return (
        <PublicLayout title="Tutor Registration">
            <Head title="Tutor Registration" />
            <div className="min-h-[80vh] bg-surface py-10 px-4">
                <div className="max-w-4xl mx-auto bg-white rounded-[2rem] border border-outline-variant/30 shadow-xl p-5 md:p-10">
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-extrabold text-on-surface">Tutor Registration</h1>
                        <p className="text-on-surface-variant mt-2">Complete all steps so we can review and approve your profile.</p>
                        <div className="flex justify-center gap-2 mt-6">
                            {[1, 2, 3].map((s) => (
                                <div key={s} className={`h-2 w-16 rounded-full ${step >= s ? 'bg-primary' : 'bg-surface-container-high'}`} />
                            ))}
                        </div>
                        <p className="text-xs text-on-surface-variant mt-2">Step {step} of 3</p>
                    </div>

                    {errList.length > 0 && (
                        <div className="bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold mb-6">
                            <ul className="list-disc pl-5">{errList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6" encType="multipart/form-data">
                        {step === 1 && (
                            <div className="space-y-6">
                                <h2 className="font-bold text-xl">Basic info, credentials & compensation</h2>
                                <div className="grid md:grid-cols-2 gap-4">
                                    <Field label="Full name *"><input required className={inputCls} value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                                    <Field label="Email *"><input type="email" required className={inputCls} value={data.email} onChange={(e) => setData('email', e.target.value)} /></Field>
                                    <Field label="Phone (with country code) *"><input required placeholder="+1..." className={inputCls} value={data.phone} onChange={(e) => setData('phone', e.target.value)} /></Field>
                                    <Field label="City / Country *"><input required className={inputCls} value={data.city_country} onChange={(e) => setData('city_country', e.target.value)} /></Field>
                                    <Field label="Password *"><input type="password" required minLength={8} className={inputCls} value={data.password} onChange={(e) => setData('password', e.target.value)} /></Field>
                                    <Field label="Confirm password *"><input type="password" required className={inputCls} value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} /></Field>
                                    <Field label="Profile photo *"><input type="file" accept="image/*" required className="w-full text-sm" onChange={(e) => setData('profile_photo', e.target.files?.[0] || null)} /></Field>
                                    <Field label="LinkedIn (optional)"><input type="url" className={inputCls} value={data.linkedin_url} onChange={(e) => setData('linkedin_url', e.target.value)} /></Field>
                                    <div className="md:col-span-2"><Field label="How did you hear about us?"><input className={inputCls} value={data.source} onChange={(e) => setData('source', e.target.value)} /></Field></div>
                                </div>
                                <div className="space-y-2 text-sm">
                                    <label className="flex gap-2 items-start"><input type="checkbox" checked={data.is_18_plus} onChange={(e) => setData('is_18_plus', e.target.checked)} required /> I am 18 years or older *</label>
                                    <label className="flex gap-2 items-start"><input type="checkbox" checked={data.background_check_consent} onChange={(e) => setData('background_check_consent', e.target.checked)} required /> I consent to a background check *</label>
                                    <label className="flex gap-2 items-start"><input type="checkbox" checked={data.child_safety_consent} onChange={(e) => setData('child_safety_consent', e.target.checked)} required /> I agree to child safety policies *</label>
                                </div>
                                <hr className="border-outline-variant/40" />
                                <div className="space-y-4">
                                    <h3 className="font-bold text-lg">Video, credentials & compensation</h3>
                                    <Field label="Intro video (max 90s, MP4/WebM/MOV, 50MB) *"><input type="file" accept="video/mp4,video/webm,video/quicktime" required className="w-full text-sm" onChange={(e) => setData('intro_video', e.target.files?.[0] || null)} /></Field>
                                    <Field label="Certifications / degree (at least 1) *"><input type="file" accept=".pdf,image/*" multiple required className="w-full text-sm" onChange={(e) => setData('certifications', Array.from(e.target.files || []))} /></Field>
                                    <div className="grid md:grid-cols-2 gap-4">
                                        <Field label="Currency *">
                                            <select className={inputCls} value={data.currency} onChange={(e) => setData('currency', e.target.value)}>
                                                {['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR'].map((c) => <option key={c}>{c}</option>)}
                                            </select>
                                        </Field>
                                        <Field label="Expected hourly rate *"><input required placeholder="15-20/hour" className={inputCls} value={data.expected_hourly_rate} onChange={(e) => setData('expected_hourly_rate', e.target.value)} /></Field>
                                        <div className="md:col-span-2"><Field label="References (optional)"><textarea rows={3} className={inputCls} value={data.references} onChange={(e) => setData('references', e.target.value)} /></Field></div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {step === 2 && (
                            <div className="space-y-4">
                                <h2 className="font-bold text-xl">Teaching profile & communication</h2>
                                <div>
                                    <p className="text-sm font-bold mb-2">Subject verticals * (select at least 1)</p>
                                    <div className="flex flex-wrap gap-2">
                                        {VERTICALS.map((v) => (
                                            <button type="button" key={v} onClick={() => toggleArray('subject_verticals', v)}
                                                className={`px-3 py-2 rounded-full border text-sm ${data.subject_verticals.includes(v) ? 'bg-primary text-white border-primary' : 'border-outline-variant'}`}>{v}</button>
                                        ))}
                                    </div>
                                </div>
                                <div>
                                    <p className="text-sm font-bold mb-2">Preferred age bands *</p>
                                    <div className="flex flex-wrap gap-2">
                                        {AGE_BANDS.map((b) => (
                                            <button type="button" key={b} onClick={() => toggleArray('preferred_age_bands', b)}
                                                className={`px-3 py-2 rounded-full border text-sm ${data.preferred_age_bands.includes(b) ? 'bg-secondary text-white border-secondary' : 'border-outline-variant'}`}>{b}</button>
                                        ))}
                                    </div>
                                </div>
                                <div className="grid md:grid-cols-2 gap-4">
                                    <div className="md:col-span-2"><Field label="Teaching experience *"><input required className={inputCls} value={data.teaching_experience} onChange={(e) => setData('teaching_experience', e.target.value)} /></Field></div>
                                    <Field label="Countries taught"><input className={inputCls} value={data.countries_taught} onChange={(e) => setData('countries_taught', e.target.value)} /></Field>
                                    <Field label="Platforms taught"><input className={inputCls} value={data.platforms_taught} onChange={(e) => setData('platforms_taught', e.target.value)} /></Field>
                                    <Field label="Higher education *"><input required className={inputCls} value={data.higher_education} onChange={(e) => setData('higher_education', e.target.value)} /></Field>
                                    <Field label="Institution *"><input required className={inputCls} value={data.higher_institution} onChange={(e) => setData('higher_institution', e.target.value)} /></Field>
                                    <Field label="English fluency *">
                                        <select className={inputCls} value={data.english_fluency} onChange={(e) => setData('english_fluency', e.target.value)}>
                                            <option value="5">5 — Native/Bilingual</option>
                                            <option value="4">4 — Full Professional</option>
                                            <option value="3">3 — Professional Working</option>
                                            <option value="2">2 — Limited Working</option>
                                            <option value="1">1 — Elementary</option>
                                        </select>
                                    </Field>
                                    <Field label="Additional languages"><input className={inputCls} value={data.additional_languages} onChange={(e) => setData('additional_languages', e.target.value)} /></Field>
                                    <div className="md:col-span-2"><Field label="About yourself *"><textarea required minLength={10} rows={4} className={inputCls} value={data.about_yourself} onChange={(e) => setData('about_yourself', e.target.value)} /></Field></div>
                                </div>
                            </div>
                        )}

                        {step === 3 && (
                            <div className="space-y-4">
                                <h2 className="font-bold text-xl">Availability & tech setup</h2>
                                <Field label="Weekly commitment *">
                                    <select className={inputCls} value={data.availability_commitment} onChange={(e) => setData('availability_commitment', e.target.value)}>
                                        <option>Under 10 hrs/wk</option>
                                        <option>10-20 hrs/wk</option>
                                        <option>20-40 hrs/wk</option>
                                    </select>
                                </Field>
                                <div>
                                    <p className="text-sm font-bold mb-2">Weekly availability * (From → To for each day)</p>
                                    <p className="text-xs text-on-surface-variant mb-3">
                                        Timezone: {data.timezone}. {selectedSlots.length} day(s) selected
                                        {selectedSlots.length ? ` — ${selectedSlots.join(', ')}` : ' — check at least one day'}
                                    </p>
                                    <div className="space-y-3">
                                        {dayRows.map((row, index) => (
                                            <div key={row.day} className={`grid grid-cols-1 sm:grid-cols-[110px_1fr_1fr] gap-3 items-center p-3 rounded-xl border ${row.enabled ? 'border-primary/40 bg-primary/5' : 'border-outline-variant bg-surface-container-low/40'}`}>
                                                <label className="inline-flex items-center gap-2 font-bold text-sm">
                                                    <input type="checkbox" className="accent-primary h-4 w-4" checked={row.enabled} onChange={(e) => updateDay(index, { enabled: e.target.checked })} />
                                                    {row.day}
                                                </label>
                                                <div>
                                                    <label className="block text-xs font-bold mb-1 text-on-surface-variant">From</label>
                                                    <input type="time" required={row.enabled} disabled={!row.enabled} className="w-full p-2.5 rounded-xl bg-white border border-outline-variant disabled:opacity-50" value={normalizeTime(row.from) || row.from} onChange={(e) => updateDay(index, { from: e.target.value })} />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-bold mb-1 text-on-surface-variant">To</label>
                                                    <input type="time" required={row.enabled} disabled={!row.enabled} className="w-full p-2.5 rounded-xl bg-white border border-outline-variant disabled:opacity-50" value={normalizeTime(row.to) || row.to} onChange={(e) => updateDay(index, { to: e.target.value })} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    {(errors.preferred_schedule || errors['preferred_schedule.0']) ? (
                                        <p className="mt-2 text-sm font-bold text-error">{errors.preferred_schedule || errors['preferred_schedule.0']}</p>
                                    ) : null}
                                </div>
                                <Field label="Earliest start date *"><input type="date" required className={inputCls} value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} /></Field>
                                <div className="space-y-2 text-sm">
                                    <label className="flex gap-2"><input type="checkbox" checked={data.has_laptop_camera} onChange={(e) => setData('has_laptop_camera', e.target.checked)} required /> I have a laptop with camera *</label>
                                    <label className="flex gap-2"><input type="checkbox" checked={data.has_stable_internet} onChange={(e) => setData('has_stable_internet', e.target.checked)} required /> I have stable internet *</label>
                                    <label className="flex gap-2"><input type="checkbox" checked={data.has_quiet_workspace} onChange={(e) => setData('has_quiet_workspace', e.target.checked)} required /> Quiet, well-lit workspace *</label>
                                    <label className="flex gap-2"><input type="checkbox" checked={data.comfort_with_zoom} onChange={(e) => setData('comfort_with_zoom', e.target.checked)} required /> Comfortable with Zoom/Meet screen-share *</label>
                                </div>
                                <Field label="Internet speed screenshot *"><input type="file" accept="image/*,application/pdf" required className="w-full text-sm" onChange={(e) => setData('internet_speed_screenshot', e.target.files?.[0] || null)} /></Field>
                            </div>
                        )}

                        <div className="flex justify-between pt-4 border-t border-outline-variant/30">
                            {step > 1 ? (
                                <button type="button" className="btn-secondary px-5 py-2 rounded-xl font-bold" onClick={() => setStep((s) => s - 1)} disabled={processing || submitting}>Back</button>
                            ) : <span />}
                            <div className="ml-auto flex gap-3">
                                {step < 3 ? (
                                    <button type="button" className="btn-primary px-5 py-2 rounded-xl font-bold text-white bg-primary" onClick={nextStep}>Continue</button>
                                ) : (
                                    <button type="submit" className="btn-primary px-5 py-2 rounded-xl font-bold text-white bg-primary disabled:opacity-70" disabled={processing || submitting}>
                                        {processing || submitting
                                            ? (uploadPct != null ? `Uploading… ${uploadPct}%` : 'Uploading… Please wait')
                                            : 'Submit registration'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </form>

                    <div className="text-center mt-8 pt-6 border-t border-outline-variant/30 space-y-3">
                        <p className="text-sm text-on-surface-variant font-medium">
                            Already a Tutor?{' '}
                            <Link href="/login/tutor" className="font-bold text-secondary hover:underline">
                                Sign In Here
                            </Link>
                        </p>
                        <p className="text-sm text-on-surface-variant font-medium">
                            Are you a Student?{' '}
                            <Link href="/login/student" className="font-bold text-primary hover:underline">
                                Sign In Here
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

const inputCls = 'w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant';

function Field({ label, children }) {
    return (
        <div>
            <label className="block text-sm font-bold mb-1">{label}</label>
            {children}
        </div>
    );
}
