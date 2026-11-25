import { useCallback, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePage } from '@inertiajs/react';
import '../../css/enroll-modal.css';

function enrollErrorList(errors) {
    if (!errors || typeof errors !== 'object') return [];
    const relevantKeys = ['course_id', 'classes', 'enroll', 'enrollment'];
    const list = [];
    for (const key of relevantKeys) {
        const val = errors[key];
        if (Array.isArray(val)) {
            list.push(...val);
        } else if (typeof val === 'string') {
            list.push(val);
        }
    }
    return list;
}

export default function EnrollModal() {
    const { csrfToken, leadCategories = [], errors } = usePage().props;
    const [isOpen, setIsOpen] = useState(false);
    const [categoryId, setCategoryId] = useState('');
    const [courseId, setCourseId] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const categories = useMemo(() => leadCategories || [], [leadCategories]);

    const courses = useMemo(() => {
        if (!categoryId) return [];
        const cat = categories.find((c) => String(c.id) === String(categoryId));
        return cat?.courses || [];
    }, [categories, categoryId]);

    const errorList = useMemo(() => enrollErrorList(errors), [errors]);

    const openModal = useCallback((e) => {
        if (e) e.preventDefault();
        setIsOpen(true);
        document.body.classList.add('modal-open');
        document.documentElement.classList.add('modal-open');
    }, []);

    const closeModal = useCallback(() => {
        setIsOpen(false);
        document.body.classList.remove('modal-open');
        document.documentElement.classList.remove('modal-open');
    }, []);

    useEffect(() => {
        window.openEnrollModal = openModal;
        return () => {
            delete window.openEnrollModal;
        };
    }, [openModal]);

    useEffect(() => {
        if (errorList.length > 0) {
            setSubmitting(false);
            setIsOpen(true);
            document.body.classList.add('modal-open');
            document.documentElement.classList.add('modal-open');
        }
    }, [errorList.length]);

    const onCategoryChange = (value) => {
        setCategoryId(value);
        setCourseId('');
    };

    if (typeof document === 'undefined') return null;

    return createPortal(
        <div id="enrollModal" className={`lead-modal-overlay${isOpen ? ' open' : ''}`}>
            <div className="lead-modal-panel">
                <button type="button" id="enrollModalClose" className="mclose" aria-label="Close" onClick={closeModal}>
                    <span className="material-symbols-outlined">close</span>
                </button>
                <div className="lead-modal-header">
                    <h3>Enroll Now</h3>
                    <p>Select your course and the number of classes you would like to purchase.</p>
                </div>

                {errorList.length > 0 && (
                    <div
                        style={{
                            background: '#ffebee',
                            color: '#c62828',
                            padding: 12,
                            borderRadius: 8,
                            marginBottom: 16,
                        }}
                    >
                        <ul style={{ margin: 0, paddingLeft: 20 }}>
                            {errorList.map((err) => (
                                <li key={String(err)}>{err}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <form
                    id="enrollForm"
                    action="/enroll"
                    method="POST"
                    autoComplete="on"
                    onSubmit={(e) => {
                        if (submitting) {
                            e.preventDefault();
                            return;
                        }
                        if (!courseId) {
                            e.preventDefault();
                            return;
                        }
                        setSubmitting(true);
                    }}
                >
                    <input type="hidden" name="_token" value={csrfToken || ''} />

                    <div className="form-group">
                        <label>Select Category</label>
                        <select
                            id="enroll_category"
                            className="form-control"
                            value={categoryId}
                            onChange={(e) => onCategoryChange(e.target.value)}
                        >
                            <option value="" disabled hidden>
                                Select a category
                            </option>
                            {categories.map((cat) => (
                                <option key={cat.id} value={String(cat.id)}>
                                    {cat.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Select Course</label>
                        <select
                            name="course_id"
                            id="enroll_course"
                            className="form-control"
                            required
                            value={courseId}
                            onChange={(e) => setCourseId(e.target.value)}
                        >
                            <option value="" disabled hidden>
                                {categoryId ? 'Select a course' : 'Please select a category first'}
                            </option>
                            {courses.map((c) => (
                                <option key={c.id} value={String(c.id)}>
                                    {c.title}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Number of Classes</label>
                        <input
                            type="number"
                            name="classes"
                            id="enroll_classes"
                            className="form-control"
                            min={1}
                            max={100}
                            defaultValue={24}
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        className="btn btn-primary btn-block"
                        disabled={submitting || !courseId}
                        style={{ fontSize: 16, padding: 14, marginTop: 16, opacity: submitting || !courseId ? 0.7 : 1 }}
                    >
                        {submitting ? 'Redirecting…' : 'Proceed to Checkout'}
                    </button>
                </form>
            </div>
        </div>,
        document.body
    );
}
