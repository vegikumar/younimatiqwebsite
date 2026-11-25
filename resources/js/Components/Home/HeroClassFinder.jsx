import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function SelectField({ label, id, value, display, openKey, open, setOpen, options, onPick }) {
    const ref = useRef(null);

    useEffect(() => {
        if (open !== openKey) return;
        const onDoc = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(null);
        };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, [open, openKey, setOpen]);

    return (
        <div className="hero-select flex flex-col w-full md:w-[28%]" ref={ref}>
            <span className="text-[11px] font-[800] text-[#7130D8] uppercase tracking-wider mb-2" id={`${id}-label`}>
                {label}
            </span>
            <div className="relative">
                <button
                    type="button"
                    id={id}
                    className={`hero-select-trigger ${open === openKey ? 'is-open' : ''}`}
                    aria-haspopup="listbox"
                    aria-expanded={open === openKey}
                    aria-labelledby={`${id}-label`}
                    onClick={() => setOpen(open === openKey ? null : openKey)}
                >
                    <span className="hero-select-value">{display}</span>
                    <svg className="hero-select-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                {open === openKey ? (
                    <div className="hero-select-panel" role="listbox" aria-labelledby={`${id}-label`}>
                        <div className="hero-select-panel-inner" data-scroll-native>
                            {options.map((opt) => (
                                <button
                                    key={opt.value || 'all'}
                                    type="button"
                                    role="option"
                                    className={`hero-select-option ${value === opt.value ? 'is-active' : ''}`}
                                    onClick={() => {
                                        onPick(opt);
                                        setOpen(null);
                                    }}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>
                    </div>
                ) : null}
            </div>
        </div>
    );
}

export default function HeroClassFinder({ categories = [], gradeBands = [], initialFilters = {} }) {
    const [open, setOpen] = useState(null);
    const [grade, setGrade] = useState(initialFilters.grade || '');
    const [gradeLabel, setGradeLabel] = useState(initialFilters.gradeLabel || 'All Grades (K-12)');
    const [interest, setInterest] = useState(initialFilters.interest || '');
    const [interestLabel, setInterestLabel] = useState(initialFilters.interestLabel || 'Any Interest');
    const [level, setLevel] = useState(initialFilters.level || '');
    const [levelLabel, setLevelLabel] = useState(initialFilters.levelLabel || 'Any Level');

    useEffect(() => {
        const on = !!open;
        document.documentElement.classList.toggle('hero-dd-open', on);
        document.body.classList.toggle('hero-dd-open', on);
        return () => {
            document.documentElement.classList.remove('hero-dd-open');
            document.body.classList.remove('hero-dd-open');
        };
    }, [open]);

    useEffect(() => {
        const onEsc = (e) => {
            if (e.key === 'Escape') setOpen(null);
        };
        window.addEventListener('keydown', onEsc);
        return () => window.removeEventListener('keydown', onEsc);
    }, []);

    const gradeOptions = [
        { value: '', label: 'All Grades (K-12)' },
        ...gradeBands.map((g) => ({ value: String(g.value), label: g.label || `Grade ${g.value}` })),
    ];
    const interestOptions = [
        { value: '', label: 'Any Interest' },
        ...categories.map((c) => ({ value: c.slug, label: c.name })),
    ];
    const levelOptions = [
        { value: '', label: 'Any Level' },
        { value: 'beginner', label: 'Beginner' },
        { value: 'intermediate', label: 'Intermediate' },
        { value: 'advanced', label: 'Advanced' },
    ];

    const submit = (e) => {
        e.preventDefault();
        const params = {};
        if (grade) params.grade = grade;
        if (interest) params.categories = [interest];
        if (level) params.search = level;
        router.get('/courses', params);
    };

    return (
        <form
            onSubmit={submit}
            className="clay-card rounded-[1.5rem] p-4 sm:p-6 w-full flex flex-col md:flex-row items-end gap-6 justify-between overflow-visible"
        >
            <SelectField
                label="Grade"
                id="hero-filter-grade"
                openKey="grade"
                open={open}
                setOpen={setOpen}
                value={grade}
                display={gradeLabel}
                options={gradeOptions}
                onPick={(opt) => {
                    setGrade(opt.value);
                    setGradeLabel(opt.label);
                }}
            />
            <SelectField
                label="Interest"
                id="hero-filter-interest"
                openKey="interest"
                open={open}
                setOpen={setOpen}
                value={interest}
                display={interestLabel}
                options={interestOptions}
                onPick={(opt) => {
                    setInterest(opt.value);
                    setInterestLabel(opt.label);
                }}
            />
            <SelectField
                label="Experience Level"
                id="hero-filter-level"
                openKey="level"
                open={open}
                setOpen={setOpen}
                value={level}
                display={levelLabel}
                options={levelOptions}
                onPick={(opt) => {
                    setLevel(opt.value);
                    setLevelLabel(opt.label);
                }}
            />
            <div className="w-full md:w-auto flex-shrink-0 mt-4 md:mt-0">
                <button
                    type="submit"
                    className="block w-full text-center bg-[#7130D8] text-white rounded-[1.25rem] px-8 py-3 text-[15px] font-[700] hover:bg-[#5b24b3] transition-colors shadow-md hover:shadow-lg whitespace-nowrap"
                >
                    Find Classes
                </button>
            </div>
        </form>
    );
}
