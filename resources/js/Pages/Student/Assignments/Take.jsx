import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

function optionText(option) {
    if (option && typeof option === 'object') return option.text || '';
    return option ?? '';
}

function DrawPad({ value, onChange }) {
    const ref = useRef(null);
    const drawing = useRef(false);

    useEffect(() => {
        const canvas = ref.current;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#1a1a1a';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        if (value) {
            const image = new Image();
            image.onload = () => ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
            image.src = value;
        }
    }, [value]);

    const point = (event) => {
        const canvas = ref.current;
        const bounds = canvas.getBoundingClientRect();
        const source = event.touches ? event.touches[0] : event;
        return {
            x: (source.clientX - bounds.left) * (canvas.width / bounds.width),
            y: (source.clientY - bounds.top) * (canvas.height / bounds.height),
        };
    };

    const start = (event) => {
        drawing.current = true;
        const spot = point(event);
        const ctx = ref.current.getContext('2d');
        ctx.beginPath();
        ctx.moveTo(spot.x, spot.y);
    };

    const move = (event) => {
        if (!drawing.current) return;
        event.preventDefault();
        const spot = point(event);
        const ctx = ref.current.getContext('2d');
        ctx.lineTo(spot.x, spot.y);
        ctx.stroke();
    };

    const finish = () => {
        if (!drawing.current) return;
        drawing.current = false;
        onChange(ref.current.toDataURL('image/png'));
    };

    return (
        <canvas
            ref={ref}
            width={720}
            height={420}
            className="w-full max-w-3xl rounded-xl border bg-white touch-none"
            onPointerDown={start}
            onPointerMove={move}
            onPointerUp={finish}
            onPointerLeave={finish}
        />
    );
}

export default function Take({ assignment }) {
    const { errors = {} } = usePage().props;
    const errorList = Object.values(errors).flat();
    const questions = Array.isArray(assignment.questions) ? assignment.questions : [];
    const previous = assignment.submission?.answers || {};
    const startingAnswers = {};
    Object.entries(previous).forEach(([id, answer]) => {
        if (answer && typeof answer === 'object' && (answer.type === 'short_answer' || answer.type === 'mcq')) {
            startingAnswers[id] = answer.value ?? '';
        }
    });

    const form = useForm({
        answers: startingAnswers,
        answer_drawings: {},
        answer_files: {},
        content: assignment.submission?.content || '',
        submission_file: null,
    });

    const setAnswer = (id, value) => form.setData('answers', { ...form.data.answers, [id]: value });

    const submit = (event) => {
        event.preventDefault();
        const missing = questions.some((question) => {
            if (question.type === 'draw') return !form.data.answer_drawings[question.id];
            if (question.type === 'image_upload') return !form.data.answer_files[question.id];
            const value = form.data.answers[question.id];
            if (question.type === 'mcq') return value === undefined || value === null || value === '';
            return !String(value ?? '').trim();
        });
        if (questions.length && missing) {
            alert('Answer every question before submitting.');
            return;
        }
        if (!questions.length && !String(form.data.content || '').trim() && !form.data.submission_file) {
            alert(assignment.file_url
                ? 'Upload your completed work, or add a short note that you finished the worksheet.'
                : 'Write your answer or upload a file before submitting.');
            return;
        }
        form.post(assignment.submit_url, { forceFormData: true });
    };

    return (
        <DashboardLayout title={assignment.title}>
            <Head title={assignment.title} />
            <div className="p-6 max-w-3xl mx-auto space-y-6">
                <Link href="/student/assignments" className="text-sm font-bold text-primary">← Back to assignments</Link>
                <h1 className="text-2xl font-bold">{assignment.title}</h1>
                {assignment.due_at_label ? <p className="text-sm text-on-surface-variant">Due {assignment.due_at_label}</p> : null}
                {assignment.description ? <p className="text-on-surface-variant">{assignment.description}</p> : null}
                {assignment.file_url ? (
                    <a href={assignment.file_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-primary/10 text-primary font-bold hover:bg-primary/15">
                        Open worksheet (PDF / file)
                    </a>
                ) : null}
                {errorList.length ? (
                    <div className="bg-error-container p-4 rounded-xl font-bold">
                        <ul className="list-disc pl-5">{errorList.map((error) => <li key={error}>{error}</li>)}</ul>
                    </div>
                ) : null}
                {assignment.submission?.grade != null ? (
                    <div className="p-4 rounded-xl bg-tertiary-fixed text-tertiary font-bold">
                        Grade: {assignment.submission.grade}%
                        {assignment.submission.feedback ? <p className="font-medium mt-1">{assignment.submission.feedback}</p> : null}
                    </div>
                ) : null}

                <form onSubmit={submit} className="space-y-5">
                    {questions.map((question, index) => (
                        <div key={question.id || index} className="clay-card p-5 space-y-3">
                            <p className="font-bold">{index + 1}. {question.question}</p>
                            {question.image_url ? <img src={question.image_url} alt="" className="max-h-56 rounded-xl border object-contain" /> : null}
                            {question.type === 'mcq' ? (
                                (question.options || []).map((option, optionIndex) => (
                                    <label key={optionIndex} className="flex gap-2 items-start">
                                        <input
                                            type="radio"
                                            name={`assignment-${question.id}`}
                                            checked={String(form.data.answers[question.id] ?? '') === String(optionIndex)}
                                            onChange={() => setAnswer(question.id, optionIndex)}
                                        />
                                        <span>{optionText(option)}</span>
                                    </label>
                                ))
                            ) : null}
                            {question.type === 'short_answer' || !question.type ? (
                                <input
                                    type="text"
                                    value={form.data.answers[question.id] ?? ''}
                                    onChange={(event) => setAnswer(question.id, event.target.value)}
                                    className="w-full px-4 py-3 border rounded-xl"
                                    placeholder="Type your answer"
                                />
                            ) : null}
                            {question.type === 'draw' ? (
                                <DrawPad value={form.data.answer_drawings[question.id]} onChange={(image) => form.setData('answer_drawings', { ...form.data.answer_drawings, [question.id]: image })} />
                            ) : null}
                            {question.type === 'image_upload' ? (
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => form.setData('answer_files', { ...form.data.answer_files, [question.id]: event.target.files?.[0] || null })}
                                />
                            ) : null}
                        </div>
                    ))}

                    {questions.length === 0 ? (
                        <div className="clay-card p-5 space-y-4">
                            <div>
                                <p className="font-bold mb-1">
                                    {assignment.file_url ? 'Upload completed work' : 'Your answer'}
                                </p>
                                <p className="text-sm text-on-surface-variant mb-3">
                                    {assignment.file_url
                                        ? 'Download the worksheet above, then upload your completed PDF/photo here. You can also leave a short note.'
                                        : 'Type your answer or upload a file.'}
                                </p>
                                <input
                                    type="file"
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"
                                    onChange={(event) => form.setData('submission_file', event.target.files?.[0] || null)}
                                    className="w-full px-4 py-3 border rounded-xl bg-surface-container-low file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-bold mb-1">Note (optional)</label>
                                <input
                                    type="text"
                                    value={form.data.content}
                                    onChange={(event) => form.setData('content', event.target.value)}
                                    className="w-full px-4 py-3 border rounded-xl"
                                    placeholder={assignment.file_url ? 'e.g. Finished page 1–2' : 'Type your answer'}
                                />
                            </div>
                        </div>
                    ) : null}

                    <button type="submit" className="btn-enroll" disabled={form.processing}>
                        {form.processing ? 'Submitting…' : 'Submit assignment'}
                    </button>
                </form>
            </div>
        </DashboardLayout>
    );
}
