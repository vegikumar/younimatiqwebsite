import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

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

function optionText(opt) {
    if (opt && typeof opt === 'object') return opt.text || '';
    return opt ?? '';
}

export default function Take({ quiz }) {
    const { flash } = usePage().props;
    const form = useForm({ answers: {}, answer_drawings: {}, answer_files: {} });
    const submitUrl = `/student/quizzes/${quiz.id}/submit`;
    const questions = Array.isArray(quiz.questions) ? quiz.questions : [];

    const setAnswer = (qId, value) => {
        form.setData('answers', { ...form.data.answers, [qId]: value });
    };

    const submit = (e) => {
        e.preventDefault();
        const missing = questions.some((q) => {
            if (q.type === 'draw') return !form.data.answer_drawings[q.id];
            if (q.type === 'image_upload') return !form.data.answer_files[q.id];
            const value = form.data.answers[q.id];
            if (q.type === 'short_answer') return !String(value ?? '').trim();
            return value === undefined || value === null || value === '';
        });
        if (missing) {
            alert('Answer every question before submitting.');
            return;
        }
        form.post(submitUrl, { forceFormData: true });
    };

    return (
        <DashboardLayout title={quiz.title}>
            <Head title={quiz.title} />
            <div className="p-6 max-w-3xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold">{quiz.title}</h1>
                {quiz.description ? <p className="text-on-surface-variant">{quiz.description}</p> : null}
                {flash?.error ? <div className="bg-error-container p-4 rounded-xl font-bold">{flash.error}</div> : null}
                {questions.length === 0 ? (
                    <p className="text-on-surface-variant">This quiz has no questions yet.</p>
                ) : (
                    <form onSubmit={submit} className="space-y-5">
                        {questions.map((q, i) => (
                            <div key={q.id || i} className="clay-card p-5 space-y-3">
                                <p className="font-bold">
                                    {i + 1}. {q.question}
                                </p>
                                {q.image_url ? (
                                    <img src={q.image_url} alt="" className="max-h-56 rounded-xl border object-contain" />
                                ) : null}
                                {q.type === 'short_answer' ? (
                                    <input
                                        type="text"
                                        value={form.data.answers[q.id] ?? ''}
                                        onChange={(e) => setAnswer(q.id, e.target.value)}
                                        className="w-full px-4 py-3 border rounded-xl"
                                        placeholder="Type your answer"
                                    />
                                ) : null}
                                {q.type === 'draw' ? (
                                    <DrawPad value={form.data.answer_drawings[q.id]} onChange={(image) => form.setData('answer_drawings', { ...form.data.answer_drawings, [q.id]: image })} />
                                ) : null}
                                {q.type === 'image_upload' ? (
                                    <input type="file" accept="image/*" onChange={(e) => form.setData('answer_files', { ...form.data.answer_files, [q.id]: e.target.files?.[0] || null })} />
                                ) : null}
                                {q.type !== 'short_answer' && q.type !== 'draw' && q.type !== 'image_upload' ? (
                                    (q.options || []).map((opt, idx) => (
                                        <label key={idx} className="flex gap-2 items-start">
                                            <input
                                                type="radio"
                                                name={`quiz-${q.id}`}
                                                checked={form.data.answers[q.id] === idx}
                                                onChange={() => setAnswer(q.id, idx)}
                                            />
                                            <span>{optionText(opt)}</span>
                                        </label>
                                    ))
                                ) : null}
                            </div>
                        ))}
                        <button type="submit" className="btn-enroll" disabled={form.processing}>
                            {form.processing ? 'Submitting…' : 'Submit quiz'}
                        </button>
                    </form>
                )}
            </div>
        </DashboardLayout>
    );
}
