import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePage } from '@inertiajs/react';
import { watchModalScrollLock } from '../lib/modalScrollLock';
import { ensurePhoneInputs, getItiInstances, normalizePhoneOnSubmit } from '../lib/leadModalPhone';
import '../../css/lead-modal.css';

const GRADES = Array.from({ length: 12 }, (_, i) => String(i + 1));

const TZ_ALIASES = {
    'Asia/Calcutta': 'Asia/Kolkata',
    'Asia/Katmandu': 'Asia/Kathmandu',
    'Asia/Saigon': 'Asia/Ho_Chi_Minh',
    'Asia/Rangoon': 'Asia/Yangon',
    'Asia/Macao': 'Asia/Macau',
    'America/Buenos_Aires': 'America/Argentina/Buenos_Aires',
};

function normalizeTimezone(tz) {
    return TZ_ALIASES[tz] || tz || 'UTC';
}

function curriculumOptions(displayCurrency) {
    const inrFirst = (displayCurrency || 'USD').toUpperCase() === 'INR';
    const inr = ['CBSE', 'ICSE', 'IB', 'IGCSE/Cambridge', 'State/Provincial Curriculum', 'AP', 'Homeschool'];
    const usd = ['State/Provincial Curriculum', 'AP', 'Homeschool', 'IB', 'IGCSE/Cambridge', 'CBSE', 'ICSE'];
    const list = inrFirst ? inr : usd;
    return [...list, 'Other'];
}

function formatSlotLabel(utcIso, tz) {
    const start = new Date(utcIso);
    if (Number.isNaN(start.getTime())) return '';
    const end = new Date(start.getTime() + 60 * 60000);
    const fmt = { hour: 'numeric', minute: '2-digit', timeZone: tz };
    try {
        return (
            new Intl.DateTimeFormat(undefined, fmt).format(start) +
            ' – ' +
            new Intl.DateTimeFormat(undefined, fmt).format(end)
        );
    } catch {
        return (
            start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) +
            ' – ' +
            end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
        );
    }
}

function localISODateMin(hoursAhead = 0) {
    const d = new Date(Date.now() + Math.max(0, hoursAhead) * 3600 * 1000);
    const offset = d.getTimezoneOffset() * 60000;
    return new Date(d.getTime() - offset).toISOString().split('T')[0];
}

function consultationErrorList(errors) {
    if (!errors || typeof errors !== 'object') return [];
    const raw = errors.consultation;
    if (Array.isArray(raw)) return raw;
    if (raw && typeof raw === 'object') return Object.values(raw).flat();
    return [];
}

function GradeGrid({ name, requiredFirst, defaultValue }) {
    return (
        <div className="grade-grid">
            {GRADES.map((g, idx) => (
                <label key={g} className="grade-btn">
                    <input
                        type="radio"
                        name={name}
                        value={g}
                        required={requiredFirst && idx === 0}
                        defaultChecked={defaultValue === g}
                    />
                    <span>{g}</span>
                </label>
            ))}
        </div>
    );
}

function CurriculumSelect({ name, required, displayCurrency, defaultValue }) {
    return (
        <select name={name} className="form-control" required={required} defaultValue={defaultValue || ''}>
            <option value="" disabled>
                Select curriculum
            </option>
            {curriculumOptions(displayCurrency).map((opt) => (
                <option key={opt} value={opt}>
                    {opt}
                </option>
            ))}
        </select>
    );
}

export default function LeadModal() {
    const { csrfToken, displayCurrency, leadCategories = [], timezones = {}, countryCode, errors } = usePage().props;

    const [isOpen, setIsOpen] = useState(false);
    const [headerTitle, setHeaderTitle] = useState('Book a Free Class');
    const [bookingType, setBookingType] = useState('consultation');
    const [showChild2, setShowChild2] = useState(false);
    const [showCategoryGroups, setShowCategoryGroups] = useState(false);
    const [sectionVisibility, setSectionVisibility] = useState({
        gradeC1: true,
        boardC1: true,
        courseC1: true,
        gradeC2: true,
        boardC2: true,
        gradeC1Required: true,
        boardC1Required: true,
    });

    const [categoryC1, setCategoryC1] = useState('');
    const [categoryC2, setCategoryC2] = useState('');
    const [courseC1, setCourseC1] = useState('');
    const [courseC2, setCourseC2] = useState('');
    const [timezone, setTimezone] = useState(() => {
        try {
            return normalizeTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone);
        } catch {
            return 'UTC';
        }
    });
    const [timezoneOptions, setTimezoneOptions] = useState([]);
    const [timezoneUserModified, setTimezoneUserModified] = useState(false);
    const [consultDate, setConsultDate] = useState('');
    const [consultTime, setConsultTime] = useState('');
    const [timeSlotOptions, setTimeSlotOptions] = useState([
        { value: '', label: 'Select a date first', disabled: true },
    ]);
    const [showCustomTime, setShowCustomTime] = useState(false);
    const [customSlot, setCustomSlot] = useState('0');
    const [captureGuarded, setCaptureGuarded] = useState(false);
    const [formResetKey, setFormResetKey] = useState(0);
    const [defaultGradeC1, setDefaultGradeC1] = useState('');

    const overlayRef = useRef(null);
    const panelRef = useRef(null);
    const formRef = useRef(null);
    const phoneRef = useRef(null);
    const whatsappRef = useRef(null);
    const consultDateRef = useRef(null);

    const categories = useMemo(() => leadCategories || [], [leadCategories]);
    const consultationErrors = useMemo(() => consultationErrorList(errors), [errors]);

    const coursesForCategory = useCallback(
        (catId) => {
            if (!catId) return [];
            const category = categories.find((c) => String(c.id) === String(catId));
            return category?.courses || [];
        },
        [categories]
    );

    const applyDetectedTimezone = useCallback(() => {
        if (timezoneUserModified) return;
        const browserTz = normalizeTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone);
        setTimezoneOptions((prev) => {
            const exists = prev.some((o) => o.value === browserTz);
            if (!exists && browserTz) {
                return [...prev, { value: browserTz, label: browserTz.replace(/_/g, ' ') }];
            }
            return prev;
        });
        if (browserTz) setTimezone(browserTz);
    }, [timezoneUserModified]);

    const fetchSlots = useCallback(
        async (dateVal, tzOverride, bookingTypeOverride) => {
            if (!dateVal) {
                setTimeSlotOptions([{ value: '', label: 'Select a date first', disabled: true, selected: true }]);
                setConsultTime('');
                setShowCustomTime(false);
                setCustomSlot('0');
                return;
            }

            const tz = normalizeTimezone(tzOverride || timezone);
            const type = bookingTypeOverride || bookingType || 'consultation';
            setTimeSlotOptions([{ value: '', label: 'Loading slots...', disabled: true, selected: true }]);

            try {
                const res = await fetch(
                    `/api/consultation-slots?date=${encodeURIComponent(dateVal)}&timezone=${encodeURIComponent(tz)}&booking_type=${encodeURIComponent(type)}`
                );
                if (!res.ok) throw new Error(`Network error: ${res.status}`);
                const data = await res.json();
                const opts = [{ value: '', label: 'Select a recommended time', disabled: true, selected: true }];

                if (data.slots?.length) {
                    data.slots.forEach((slot) => {
                        const val = slot.utc_iso || slot.time;
                        const localLabel = slot.utc_iso ? formatSlotLabel(slot.utc_iso, tz) : slot.label;
                        let suffix = '';
                        if (!slot.available) {
                            if (slot.too_soon) suffix = ' (Available after 48 hours)';
                            else if (slot.past) suffix = ' (Past)';
                            else suffix = ' (Booked)';
                        }
                        opts.push({
                            value: val,
                            label: localLabel + suffix,
                            disabled: !slot.available,
                        });
                    });
                }

                opts.push({ value: 'custom', label: 'Choose a different time' });
                setTimeSlotOptions(opts);
                setConsultTime('');
                setShowCustomTime(false);
                setCustomSlot('0');
            } catch (err) {
                console.error('Slot fetch error:', err);
                setTimeSlotOptions([
                    { value: '', label: `Error: ${err.message}`, disabled: true, selected: true },
                    { value: 'custom', label: 'Choose a different time' },
                ]);
            }
        },
        [timezone, bookingType]
    );

    const openModal = useCallback(
        (type = 'universal', categoryId = null, courseTitle = null) => {
            let catGroupsVisible = false;
            const nextSection = {
                gradeC1: true,
                boardC1: true,
                courseC1: true,
                gradeC2: true,
                boardC2: true,
                gradeC1Required: true,
                boardC1Required: true,
            };
            let nextBooking = 'consultation';
            let title = 'Book a free consultation';
            let cat1 = '';
            let cat2 = '';
            let co1 = '';
            let co2 = '';

            if (type === 'trial' || type === 'free_class') {
                // Dedicated free trial registration (Children's Day + trial CTAs)
                nextBooking = 'free_class';
                title = 'Book a free trial class';
                catGroupsVisible = !categoryId;
                if (categoryId) {
                    cat1 = String(categoryId);
                    cat2 = String(categoryId);
                }
                if (courseTitle) {
                    co1 = courseTitle;
                    co2 = courseTitle;
                    nextSection.courseC1 = false;
                    nextSection.courseC2 = false;
                }
                nextSection.gradeC1 = true;
                nextSection.boardC1 = true;
                nextSection.gradeC2 = true;
                nextSection.boardC2 = true;
                nextSection.gradeC1Required = true;
                nextSection.boardC1Required = true;
            } else if (type === 'robotics') {
                catGroupsVisible = false;
                const roboticsCat = categories.find((c) => c.slug?.includes('robotic'));
                if (roboticsCat) {
                    cat1 = String(roboticsCat.id);
                    cat2 = String(roboticsCat.id);
                }
            } else if (type === 'minecraft') {
                catGroupsVisible = false;
                const mcCat = categories.find((c) => c.slug?.includes('minecraft'));
                if (mcCat) {
                    cat1 = String(mcCat.id);
                    cat2 = String(mcCat.id);
                }
            } else {
                catGroupsVisible = true;
                if (categoryId) {
                    catGroupsVisible = false;
                    cat1 = String(categoryId);
                    cat2 = String(categoryId);
                    if (courseTitle) {
                        co1 = courseTitle;
                        co2 = courseTitle;
                    }
                    nextSection.gradeC1 = false;
                    nextSection.boardC1 = false;
                    nextSection.courseC1 = false;
                    nextSection.gradeC2 = false;
                    nextSection.boardC2 = false;
                    nextSection.gradeC1Required = false;
                    nextSection.boardC1Required = false;
                    nextBooking = 'free_class';
                    title = 'Book a free trial class';
                } else {
                    nextBooking = 'consultation';
                    title = 'Book a free consultation';
                }
            }

            setShowCategoryGroups(catGroupsVisible);
            setSectionVisibility(nextSection);
            setBookingType(nextBooking);
            setHeaderTitle(title);
            setCategoryC1(cat1);
            setCategoryC2(cat2);
            setCourseC1(co1);
            setCourseC2(co2);
            setShowChild2(false);
            setDefaultGradeC1(nextBooking === 'free_class' ? '1' : '');
            setConsultDate('');
            setConsultTime('');
            setFormResetKey((k) => k + 1);
            setIsOpen(true);

            requestAnimationFrame(() => {
                applyDetectedTimezone();
                if (phoneRef.current && whatsappRef.current) {
                    ensurePhoneInputs(phoneRef.current, whatsappRef.current, (countryCode || '').toLowerCase());
                }
                if (formRef.current) formRef.current.scrollTop = 0;
                if (overlayRef.current) overlayRef.current.scrollTop = 0;
            });
        },
        [categories, countryCode, applyDetectedTimezone]
    );

    const closeModal = useCallback(() => {
        setIsOpen(false);
        setCaptureGuarded(false);
        document.body.classList.remove('modal-open');
        document.documentElement.classList.remove('modal-open');
    }, []);

    useEffect(() => {
        const opts = Object.entries(timezones || {}).map(([value, label]) => ({ value, label }));
        setTimezoneOptions(opts.length ? opts : [{ value: 'UTC', label: 'UTC' }]);
    }, [timezones]);

    useEffect(() => {
        if (consultationErrors.length > 0) {
            setSubmitting(false);
            setIsOpen(true);
        }
    }, [consultationErrors.length]);

    useEffect(() => {
        window.openLeadModal = (e, type = 'universal', categoryId = null, courseTitle = null) => {
            if (e) e.preventDefault();
            openModal(type, categoryId, courseTitle);
        };
        window.closeLeadModal = closeModal;

        // Capture plain href="#leadModal" clicks (Home CTAs, landings, etc.)
        const onDocClick = (e) => {
            const a = e.target?.closest?.('a[href="#leadModal"], a[href="#enrollModal"]');
            if (!a) return;
            e.preventDefault();
            if (a.getAttribute('href') === '#enrollModal') {
                if (typeof window.openEnrollModal === 'function') window.openEnrollModal(e);
                return;
            }
            const type = a.dataset.leadType || 'universal';
            const categoryId = a.dataset.categoryId || null;
            const courseTitle = a.dataset.courseTitle || null;
            openModal(type, categoryId, courseTitle);
        };
        document.addEventListener('click', onDocClick, true);

        return () => {
            delete window.openLeadModal;
            delete window.closeLeadModal;
            document.removeEventListener('click', onDocClick, true);
        };
    }, [openModal, closeModal]);

    useEffect(() => {
        if (!isOpen) return;
        document.body.classList.add('modal-open');
        document.documentElement.classList.add('modal-open');
        ensurePhoneInputs(phoneRef.current, whatsappRef.current, (countryCode || '').toLowerCase());
    }, [isOpen, countryCode]);

    useEffect(() => {
        const stopWatch = watchModalScrollLock(['#leadModal']);
        return () => {
            if (typeof stopWatch === 'function') stopWatch();
        };
    }, []);

    useEffect(() => {
        const panel = panelRef.current;
        if (!panel || !isOpen) return;

        const prevent = (e) => e.preventDefault();
        ['contextmenu', 'dragstart', 'copy', 'cut'].forEach((evt) => {
            panel.addEventListener(evt, prevent);
        });

        const guard = () => {
            if (!overlayRef.current?.classList.contains('open')) return;
            setCaptureGuarded(true);
        };
        const release = () => setCaptureGuarded(false);

        window.addEventListener('blur', guard);
        window.addEventListener('focus', release);
        const onVis = () => {
            if (document.hidden) guard();
            else release();
        };
        document.addEventListener('visibilitychange', onVis);

        const onKey = (e) => {
            if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
                guard();
                try {
                    navigator.clipboard?.writeText?.('Screenshots of this booking form are not permitted.').catch(() => {});
                } catch {
                    /* noop */
                }
                setTimeout(release, 1800);
            }
        };
        window.addEventListener('keydown', onKey);

        return () => {
            ['contextmenu', 'dragstart', 'copy', 'cut'].forEach((evt) => {
                panel.removeEventListener(evt, prevent);
            });
            window.removeEventListener('blur', guard);
            window.removeEventListener('focus', release);
            document.removeEventListener('visibilitychange', onVis);
            window.removeEventListener('keydown', onKey);
        };
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen) setCaptureGuarded(false);
    }, [isOpen]);

    const onCategoryChange = (prefix, value) => {
        if (prefix === 'c1') {
            setCategoryC1(value);
            setCourseC1('');
        } else {
            setCategoryC2(value);
            setCourseC2('');
        }
    };

    const [submitting, setSubmitting] = useState(false);

    const onFormSubmit = (e) => {
        if (submitting) {
            e.preventDefault();
            return;
        }
        const { itiPhoneModal, itiWhatsappModal } = getItiInstances();
        const phoneOk = normalizePhoneOnSubmit(itiPhoneModal, phoneRef.current);
        const waOk = normalizePhoneOnSubmit(itiWhatsappModal, whatsappRef.current);
        if (!phoneOk || !waOk) {
            e.preventDefault();
            return;
        }
        setSubmitting(true);
    };

    const onConsultTimeChange = (val) => {
        setConsultTime(val);
        const isCustom = val === 'custom';
        setShowCustomTime(isCustom);
        setCustomSlot(isCustom ? '1' : '0');
    };

    if (typeof document === 'undefined') return null;

    const coursesC1 = coursesForCategory(categoryC1);
    const coursesC2 = coursesForCategory(categoryC2);

    return createPortal(
        <div
            id="leadModal"
            ref={overlayRef}
            className={`lead-modal-overlay${isOpen ? ' open' : ''}`}
            data-scroll-native="true"
        >
            <div
                ref={panelRef}
                className={`lead-modal-panel${captureGuarded ? ' is-capture-guarded' : ''}`}
            >
                <button type="button" id="leadModalClose" className="mclose" aria-label="Close" onClick={closeModal}>
                    <span className="material-symbols-outlined">close</span>
                </button>
                <div className="lead-modal-header">
                    <h3>{headerTitle}</h3>
                    <p>
                        {bookingType === 'free_class'
                            ? 'Tell us a bit about your child to book a free trial class. Slots open 48 hours ahead.'
                            : 'Tell us a bit about your child to book a free consultation. Consultation slots open 48 hours ahead.'}
                    </p>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 6,
                            marginTop: 12,
                            color: '#38B91F',
                            fontSize: 13,
                            fontWeight: 700,
                        }}
                    >
                        <span className="material-symbols-outlined" style={{ fontSize: 16 }}>
                            how_to_reg
                        </span>
                        <span>All tutors are background-checked</span>
                    </div>
                </div>

                {consultationErrors.length > 0 && (
                    <div
                        style={{
                            background: '#ffebee',
                            color: '#c62828',
                            padding: 12,
                            borderRadius: 8,
                            margin: '0 30px 16px',
                        }}
                    >
                        <ul style={{ margin: 0, paddingLeft: 20 }}>
                            {consultationErrors.map((err) => (
                                <li key={err}>{err}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <form
                    key={formResetKey}
                    id="leadForm"
                    ref={formRef}
                    action="/book-consultation"
                    method="POST"
                    autoComplete="on"
                    onSubmit={onFormSubmit}
                >
                    <input type="hidden" name="_token" value={csrfToken || ''} />
                    <input type="hidden" name="booking_type" id="booking_type" value={bookingType} />
                    <div className="lead-form-scroll" data-scroll-native>
                        <div id="child1-section">
                            <div
                                className="form-group"
                                id="grade-group-c1"
                                style={{ display: sectionVisibility.gradeC1 ? 'block' : 'none' }}
                            >
                                <label>Which grade is your child in?</label>
                                <GradeGrid
                                    name="grade_c1"
                                    requiredFirst={sectionVisibility.gradeC1Required}
                                    defaultValue={defaultGradeC1}
                                />
                            </div>

                            <div
                                className="form-group"
                                id="board-group-c1"
                                style={{ display: sectionVisibility.boardC1 ? 'block' : 'none' }}
                            >
                                <label>What curriculum does your child follow?</label>
                                <CurriculumSelect
                                    name="board_c1"
                                    required={sectionVisibility.boardC1Required}
                                    displayCurrency={displayCurrency}
                                    defaultValue={!sectionVisibility.boardC1Required ? 'Other' : ''}
                                />
                            </div>

                            <div className="form-row">
                                <div className="form-group" style={{ flex: 2 }}>
                                    <label>What&apos;s your child&apos;s name?</label>
                                    <input
                                        type="text"
                                        name="name_c1"
                                        id="leadChildName1"
                                        className="form-control"
                                        placeholder="Child's Name"
                                        autoComplete="given-name"
                                        required
                                    />
                                </div>
                                <div className="form-group" style={{ flex: 1 }}>
                                    <label>Age</label>
                                    <input
                                        type="number"
                                        name="age_c1"
                                        id="leadChildAge1"
                                        className="form-control"
                                        placeholder="Age"
                                        min={4}
                                        max={19}
                                        autoComplete="off"
                                        required
                                    />
                                </div>
                            </div>

                            <div
                                className="form-group category-group"
                                style={{ display: showCategoryGroups ? 'block' : 'none' }}
                            >
                                <label>What category are you interested in?</label>
                                <select
                                    name="category_c1"
                                    className="form-control"
                                    value={categoryC1}
                                    onChange={(e) => onCategoryChange('c1', e.target.value)}
                                >
                                    <option value="" disabled hidden>
                                        Select category
                                    </option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div
                                className="form-group"
                                id="course-group-c1"
                                style={{ display: sectionVisibility.courseC1 ? 'block' : 'none' }}
                            >
                                <label>Course you are interested in</label>
                                <select
                                    name="courses_c1"
                                    className="form-control"
                                    required
                                    value={courseC1}
                                    onChange={(e) => setCourseC1(e.target.value)}
                                >
                                    <option value="" disabled hidden>
                                        Select course
                                    </option>
                                    {coursesC1.map((course) => (
                                        <option key={course.id} value={course.title}>
                                            {course.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="form-group add-child-toggle" style={{ marginTop: 16 }}>
                            <label className="course-check" style={{ fontWeight: 700, color: 'var(--blue)' }}>
                                <input
                                    type="checkbox"
                                    id="child2Toggle"
                                    checked={showChild2}
                                    onChange={(e) => setShowChild2(e.target.checked)}
                                />
                                <span>Add 2nd Child (Optional)</span>
                            </label>
                        </div>

                        <div
                            id="child2-section"
                            style={{
                                display: showChild2 ? 'block' : 'none',
                                paddingTop: 20,
                                borderTop: '1px solid var(--color-outline-variant)',
                                marginTop: 16,
                            }}
                        >
                            <h4>Child 2 Details</h4>
                            {sectionVisibility.gradeC2 && (
                                <div className="form-group" id="grade-group-c2">
                                    <label>Which grade is your child in?</label>
                                    <GradeGrid name="grade_c2" requiredFirst={false} />
                                </div>
                            )}

                            {sectionVisibility.boardC2 && (
                                <div className="form-group" id="board-group-c2">
                                    <label>What curriculum does your child follow?</label>
                                    <CurriculumSelect
                                        name="board_c2"
                                        required={showChild2}
                                        displayCurrency={displayCurrency}
                                    />
                                </div>
                            )}

                            <div className="form-row">
                                <div className="form-group" style={{ flex: 2 }}>
                                    <label>What&apos;s your child&apos;s name?</label>
                                    <input
                                        type="text"
                                        name="name_c2"
                                        id="leadChildName2"
                                        className="form-control"
                                        placeholder="Child's Name"
                                        autoComplete="off"
                                        required={showChild2}
                                    />
                                </div>
                                <div className="form-group" style={{ flex: 1 }}>
                                    <label>Age</label>
                                    <input
                                        type="number"
                                        name="age_c2"
                                        className="form-control"
                                        placeholder="Age"
                                        min={4}
                                        max={19}
                                        required={showChild2}
                                    />
                                </div>
                            </div>

                            <div
                                className="form-group category-group"
                                style={{ display: showCategoryGroups ? 'block' : 'none' }}
                            >
                                <label>What category are you interested in?</label>
                                <select
                                    name="category_c2"
                                    className="form-control"
                                    value={categoryC2}
                                    onChange={(e) => onCategoryChange('c2', e.target.value)}
                                >
                                    <option value="" disabled hidden>
                                        Select category
                                    </option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="form-group">
                                <label>Course you are interested in</label>
                                <select
                                    name="courses_c2"
                                    className="form-control"
                                    value={courseC2}
                                    onChange={(e) => setCourseC2(e.target.value)}
                                >
                                    <option value="" disabled hidden>
                                        Select course
                                    </option>
                                    {coursesC2.map((course) => (
                                        <option key={course.id} value={course.title}>
                                            {course.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group" style={{ flex: 1 }}>
                                <label>Parent/Guardian Phone Number</label>
                                <input
                                    type="tel"
                                    name="phone"
                                    id="leadModalPhone"
                                    ref={phoneRef}
                                    className="form-control"
                                    placeholder="Phone Number"
                                    autoComplete="tel"
                                    inputMode="tel"
                                />
                            </div>
                            <div className="form-group" style={{ flex: 1 }}>
                                <label>WhatsApp Number</label>
                                <input
                                    type="tel"
                                    name="whatsapp_number"
                                    id="leadModalWhatsapp"
                                    ref={whatsappRef}
                                    className="form-control"
                                    placeholder="WhatsApp Number"
                                    autoComplete="tel"
                                    inputMode="tel"
                                />
                            </div>
                        </div>
                        <div className="form-group">
                            <label>Parent/Guardian Email ID</label>
                            <input
                                type="email"
                                name="email"
                                id="leadModalEmail"
                                className="form-control"
                                placeholder="Email Address"
                                autoComplete="email"
                                inputMode="email"
                                required
                                pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$"
                                title="Please enter a valid email address (e.g., user@example.com)"
                            />
                        </div>

                        <div style={{ margin: '24px 0', borderTop: '1px solid var(--color-outline-variant)' }} />

                        <div className="form-group">
                            <label>Your timezone</label>
                            <select
                                name="timezone"
                                id="parentTimezoneInput"
                                className="form-control"
                                required
                                value={timezone}
                                onChange={(e) => {
                                    setTimezoneUserModified(true);
                                    setTimezone(e.target.value);
                                    if (consultDate) fetchSlots(consultDate, e.target.value, bookingType);
                                }}
                            >
                                {timezoneOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="form-row">
                            <div className="form-group" style={{ flex: 1 }}>
                                <label>Preferred Date</label>
                                <input
                                    type="date"
                                    name="scheduled_date"
                                    id="consultDate"
                                    ref={consultDateRef}
                                    className="form-control"
                                    autoComplete="off"
                                    required
                                    min={localISODateMin(48)}
                                    value={consultDate}
                                    onChange={(e) => {
                                        setConsultDate(e.target.value);
                                        fetchSlots(e.target.value, undefined, bookingType);
                                    }}
                                />
                            </div>
                            <div className="form-group" style={{ flex: 1 }}>
                                <label>Preferred Time</label>
                                <select
                                    name="scheduled_time"
                                    id="consultTime"
                                    className="form-control"
                                    required
                                    value={consultTime}
                                    onChange={(e) => onConsultTimeChange(e.target.value)}
                                >
                                    {timeSlotOptions.map((opt, i) => (
                                        <option
                                            key={`${opt.value}-${i}`}
                                            value={opt.value}
                                            disabled={opt.disabled}
                                        >
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="custom_slot" id="consultCustomSlot" value={customSlot} />
                        <div className="form-group" id="consultCustomWrap" style={{ display: showCustomTime ? 'block' : 'none' }}>
                            <label>Your preferred time</label>
                            <input
                                type="time"
                                name="custom_scheduled_time"
                                id="consultCustomTime"
                                className="form-control"
                                step={300}
                                required={showCustomTime}
                            />
                        </div>
                    </div>
                    <div
                        className="lead-form-actions"
                        style={{
                            flex: '0 0 auto',
                            display: 'block',
                            visibility: 'visible',
                            background: '#fff',
                        }}
                    >
                        <button
                            type="submit"
                            className="btn btn-primary btn-block"
                            id="leadFormSubmit"
                            disabled={submitting}
                            style={{ display: 'block', width: '100%', minHeight: 48, opacity: submitting ? 0.7 : 1 }}
                        >
                            {submitting ? 'Submitting…' : 'Submit Details'}
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    );
}
