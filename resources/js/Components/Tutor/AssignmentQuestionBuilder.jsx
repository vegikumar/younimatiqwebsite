import { useState } from 'react';

function generateId() {
    return Math.random().toString(36).substring(2, 9);
}

function normalizeQuestions(initial) {
    return (initial || []).map((q) => ({
        id: q.id || generateId(),
        type: q.type || 'short_answer',
        question: q.question || '',
        xp: q.xp ?? 10,
        options: q.options?.length ? q.options : [{ text: '' }, { text: '' }],
        correctOption: q.correctOption ?? null,
        correctText: q.correctText ?? '',
        image_url: q.image_url ?? null,
        imagePreview: null,
        removeImage: false,
    }));
}

export default function AssignmentQuestionBuilder({ formId = 'tutor-assignment-form' }) {
    const [questions, setQuestions] = useState(() => normalizeQuestions([]));

    const updateQuestion = (index, patch) => {
        setQuestions((prev) => {
            const next = [...prev];
            next[index] = { ...next[index], ...patch };
            return next;
        });
    };

    const addQuestion = () => {
        setQuestions((prev) => [
            ...prev,
            {
                id: generateId(),
                type: 'short_answer',
                question: '',
                xp: 10,
                options: [{ text: '' }, { text: '' }],
                correctOption: null,
                correctText: '',
                image_url: null,
                imagePreview: null,
                removeImage: false,
            },
        ]);
    };

    const removeQuestion = (index) => {
        if (!confirm('Delete this question?')) return;
        setQuestions((prev) => prev.filter((_, i) => i !== index));
    };

    const questionsForJson = questions.map(({ imagePreview, ...rest }) => rest);

    return (
        <div className="space-y-6">
            <input type="hidden" name="questions_json" value={JSON.stringify(questionsForJson)} form={formId} />
            {questions.map((q, qIndex) => (
                <div key={q.id} className="clay-card p-6 space-y-4 border-l-4 border-tertiary relative bg-white">
                    <div className="flex justify-between items-start gap-4">
                        <div className="flex-1 space-y-4">
                            <div className="flex flex-wrap items-center gap-4">
                                <span className="font-bold text-lg">Question {qIndex + 1}</span>
                                <select
                                    value={q.type}
                                    onChange={(e) => {
                                        const type = e.target.value;
                                        updateQuestion(qIndex, {
                                            type,
                                            options: type === 'mcq' && (!q.options || !q.options.length) ? [{ text: '' }, { text: '' }] : q.options,
                                        });
                                    }}
                                    className="px-3 py-1.5 border rounded-lg text-sm bg-surface-container-low font-bold"
                                >
                                    <option value="short_answer">Short Answer</option>
                                    <option value="mcq">Multiple Choice</option>
                                    <option value="draw">Draw Answer</option>
                                    <option value="image_upload">Upload Image Answer</option>
                                </select>
                            </div>
                            <div className="flex gap-2">
                                <input type="text" required={questions.length > 0} value={q.question} onChange={(e) => updateQuestion(qIndex, { question: e.target.value })} className="flex-1 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="Type your question here..." />
                                <input type="number" min={0} value={q.xp} onChange={(e) => updateQuestion(qIndex, { xp: e.target.value })} className="w-24 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="XP" />
                            </div>
                            <div>
                                <label className="flex items-center gap-2 text-sm font-bold text-primary cursor-pointer w-fit">
                                    <span className="material-symbols-outlined text-sm">image</span> Add prompt image
                                    <input
                                        type="file"
                                        name={`questions[${qIndex}][image_file]`}
                                        accept="image/*"
                                        form={formId}
                                        className="hidden"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (!file) return;
                                            const reader = new FileReader();
                                            reader.onload = (ev) => updateQuestion(qIndex, { imagePreview: ev.target.result, removeImage: false });
                                            reader.readAsDataURL(file);
                                        }}
                                    />
                                </label>
                                {q.image_url && !q.imagePreview && !q.removeImage ? (
                                    <div className="mt-2 relative inline-block">
                                        <img src={q.image_url} alt="" className="h-32 rounded-lg border object-contain" />
                                        <button type="button" onClick={() => updateQuestion(qIndex, { removeImage: true })} className="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 shadow-sm">
                                            <span className="material-symbols-outlined text-[14px]">close</span>
                                        </button>
                                    </div>
                                ) : null}
                                {q.imagePreview ? (
                                    <div className="mt-2 relative inline-block">
                                        <img src={q.imagePreview} alt="" className="h-32 rounded-lg border object-contain border-primary" />
                                    </div>
                                ) : null}
                                <input type="hidden" name={`questions[${qIndex}][remove_image]`} value={q.removeImage ? '1' : '0'} form={formId} />
                            </div>
                        </div>
                        <button type="button" onClick={() => removeQuestion(qIndex)} className="p-2 text-on-surface-variant hover:text-error transition-colors bg-surface-container-low rounded-xl" title="Delete Question">
                            <span className="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                    <hr className="border-outline-variant/30" />
                    <div className="pl-4 border-l-2 border-outline-variant/30 space-y-4">
                        {q.type === 'mcq' ? (
                            <div className="space-y-3">
                                <p className="text-xs text-on-surface-variant font-medium">Optional: mark the correct option to enable auto-check on submission.</p>
                                {q.options.map((opt, oIndex) => (
                                    <div key={oIndex} className="flex items-center gap-3">
                                        <input type="radio" name={`q_${q.id}_correct`} checked={Number(q.correctOption) === oIndex} onChange={() => updateQuestion(qIndex, { correctOption: oIndex })} className="w-5 h-5 text-tertiary" title="Mark as correct (optional)" />
                                        <input
                                            type="text"
                                            value={opt.text}
                                            onChange={(e) => {
                                                const options = [...q.options];
                                                options[oIndex] = { text: e.target.value };
                                                updateQuestion(qIndex, { options });
                                            }}
                                            className="flex-1 px-3 py-2 border rounded-lg text-sm"
                                            placeholder="Option text..."
                                        />
                                        {q.options.length > 2 ? (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    const options = q.options.filter((_, i) => i !== oIndex);
                                                    let correctOption = q.correctOption;
                                                    if (correctOption === oIndex) correctOption = null;
                                                    else if (correctOption > oIndex) correctOption--;
                                                    updateQuestion(qIndex, { options, correctOption });
                                                }}
                                                className="p-1.5 text-on-surface-variant hover:text-error"
                                            >
                                                <span className="material-symbols-outlined text-sm">close</span>
                                            </button>
                                        ) : null}
                                    </div>
                                ))}
                                <button type="button" onClick={() => updateQuestion(qIndex, { correctOption: null })} className="text-xs font-bold text-on-surface-variant hover:underline">
                                    Clear correct answer
                                </button>
                                <button type="button" onClick={() => updateQuestion(qIndex, { options: [...q.options, { text: '' }] })} className="text-sm font-bold text-tertiary flex items-center gap-1 hover:underline">
                                    <span className="material-symbols-outlined text-sm">add</span> Add Option
                                </button>
                            </div>
                        ) : null}
                        {q.type === 'short_answer' ? (
                            <div>
                                <label className="block text-sm font-bold text-on-surface-variant mb-1">Correct answer (optional)</label>
                                <input type="text" value={q.correctText} onChange={(e) => updateQuestion(qIndex, { correctText: e.target.value })} className="w-full px-4 py-3 border border-outline-variant rounded-xl font-mono text-sm" placeholder="e.g. Washington" />
                            </div>
                        ) : null}
                        {q.type === 'draw' ? (
                            <div className="p-4 bg-warning-container border border-warning-container rounded-xl text-sm text-warning">
                                <span className="font-bold">Draw answer:</span> Students will get a drawing canvas. Grade manually after submission.
                            </div>
                        ) : null}
                        {q.type === 'image_upload' ? (
                            <div className="p-4 bg-secondary-container border border-secondary-container rounded-xl text-sm text-on-secondary-container">
                                <span className="font-bold">Image upload:</span> Students will upload an image. Grade manually after submission.
                            </div>
                        ) : null}
                    </div>
                </div>
            ))}
            {questions.length === 0 ? (
                <p className="text-sm text-on-surface-variant">No questions yet. Skip this if you are only attaching a PDF — put graded questions in Quizzes instead.</p>
            ) : null}
            <div className="flex flex-wrap items-center gap-3">
                <button type="button" onClick={addQuestion} className="flex items-center gap-2 px-5 py-3 bg-tertiary text-white font-bold rounded-full hover:scale-105 transition-transform shadow-md">
                    <span className="material-symbols-outlined">add</span> Add Question
                </button>
            </div>
        </div>
    );
}
