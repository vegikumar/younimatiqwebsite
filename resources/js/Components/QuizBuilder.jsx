import { useState } from 'react';

function generateId() {
    return Math.random().toString(36).substring(2, 9);
}

function normalizeQuestions(initial) {
    const qs = (initial || []).map((q) => ({
        id: q.id || generateId(),
        type: q.type || 'mcq',
        question: q.question || '',
        xp: q.xp ?? 10,
        options: q.options?.length ? q.options : [{ text: '' }, { text: '' }],
        correctOption: q.correctOption ?? q.correct_option ?? 0,
        correctText: q.correctText ?? q.correct_text ?? '',
        image_url: q.image_url ?? null,
        imagePreview: null,
        removeImage: false,
    }));
    if (qs.length === 0) {
        qs.push({
            id: generateId(),
            type: 'mcq',
            question: '',
            xp: 10,
            options: [{ text: '' }, { text: '' }],
            correctOption: 0,
            correctText: '',
            image_url: null,
            imagePreview: null,
            removeImage: false,
        });
    }
    return qs;
}

export default function QuizBuilder({ initialQuiz = null, formId = 'quiz-form' }) {
    const [quiz, setQuiz] = useState({
        title: initialQuiz?.title ?? '',
        description: initialQuiz?.description ?? '',
        passing_score: initialQuiz?.passingScore ?? initialQuiz?.passing_score ?? 70,
        xp_reward: initialQuiz?.xpReward ?? initialQuiz?.xp_reward ?? 100,
        questions: normalizeQuestions(initialQuiz?.questions),
    });
    const setField = (key, val) => setQuiz((q) => ({ ...q, [key]: val }));

    const addQuestion = () => {
        setQuiz((q) => ({
            ...q,
            questions: [
                ...q.questions,
                {
                    id: generateId(),
                    type: 'mcq',
                    question: '',
                    xp: 10,
                    options: [{ text: '' }, { text: '' }],
                    correctOption: 0,
                    correctText: '',
                    image_url: null,
                    imagePreview: null,
                    removeImage: false,
                },
            ],
        }));
    };

    const updateQuestion = (index, patch) => {
        setQuiz((q) => {
            const questions = [...q.questions];
            questions[index] = { ...questions[index], ...patch };
            return { ...q, questions };
        });
    };

    const removeQuestion = (index) => {
        if (!confirm('Delete this question?')) return;
        setQuiz((q) => ({ ...q, questions: q.questions.filter((_, i) => i !== index) }));
    };

    const submitQuiz = () => {
        if (!quiz.title.trim()) {
            alert('Please enter a quiz title.');
            return;
        }
        for (let i = 0; i < quiz.questions.length; i++) {
            const q = quiz.questions[i];
            if (!q.question.trim()) {
                alert(`Question ${i + 1} is missing text.`);
                return;
            }
            if (q.type === 'mcq') {
                for (const o of q.options) {
                    if (!o.text.trim()) {
                        alert(`Question ${i + 1} has empty options.`);
                        return;
                    }
                }
            } else if (q.type === 'short_answer' && !q.correctText.trim()) {
                alert(`Question ${i + 1} requires an acceptable correct answer.`);
                return;
            }
        }
        document.getElementById(formId)?.requestSubmit();
    };

    const questionsForJson = quiz.questions.map(({ imagePreview, ...rest }) => rest);

    return (
        <div className="space-y-8">
            <input type="hidden" name="questions_json" value={JSON.stringify(questionsForJson)} form={formId} />

            <div className="clay-card p-6 space-y-4 border-l-4 border-primary">
                <h2 className="text-xl font-bold">Quiz Settings</h2>
                <div>
                    <label className="block text-sm font-bold text-on-surface-variant mb-1">Quiz</label>
                    <input type="text" name="title" required value={quiz.title} onChange={(e) => setField('title', e.target.value)} form={formId} className="w-full px-4 py-3 border rounded-xl" placeholder="e.g. Midterm quiz" />
                </div>
                <div>
                    <label className="block text-sm font-bold text-on-surface-variant mb-1">Description (Optional)</label>
                    <textarea name="description" value={quiz.description} onChange={(e) => setField('description', e.target.value)} form={formId} rows={2} className="w-full px-4 py-3 border rounded-xl" />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-bold text-on-surface-variant mb-1">Score to unlock badge (%)</label>
                        <input type="number" name="passing_score" required min={1} max={100} value={quiz.passing_score} onChange={(e) => setField('passing_score', e.target.value)} form={formId} className="w-full px-4 py-3 border rounded-xl" />
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-on-surface-variant mb-1">Total XP Reward</label>
                        <input type="number" name="xp_reward" required min={0} value={quiz.xp_reward} onChange={(e) => setField('xp_reward', e.target.value)} form={formId} className="w-full px-4 py-3 border rounded-xl" />
                    </div>
                </div>
            </div>

            <div className="space-y-6">
                {quiz.questions.map((q, qIndex) => (
                    <div key={q.id} className="clay-card p-6 space-y-4 border-l-4 border-tertiary relative bg-white">
                        <div className="flex justify-between items-start gap-4">
                            <div className="flex-1 space-y-4">
                                <div className="flex items-center gap-4">
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
                                        <option value="mcq">Multiple Choice</option>
                                        <option value="short_answer">Short Answer</option>
                                        <option value="draw">Draw Answer</option>
                                        <option value="image_upload">Upload Image Answer</option>
                                    </select>
                                </div>
                                <div className="flex gap-2">
                                    <input type="text" required value={q.question} onChange={(e) => updateQuestion(qIndex, { question: e.target.value })} className="flex-1 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="Type your question here..." />
                                    <input type="number" required min={0} value={q.xp} onChange={(e) => updateQuestion(qIndex, { xp: e.target.value })} className="w-24 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="XP" />
                                </div>
                                <div>
                                    <label className="flex items-center gap-2 text-sm font-bold text-primary cursor-pointer w-fit">
                                        <span className="material-symbols-outlined text-sm">image</span> Add Image
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
                            <button type="button" onClick={() => removeQuestion(qIndex)} className="p-2 text-on-surface-variant hover:text-error bg-surface-container-low rounded-xl" title="Delete Question">
                                <span className="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                        <hr className="border-outline-variant/30" />
                        <div className="pl-4 border-l-2 border-outline-variant/30 space-y-4">
                            {q.type === 'mcq' ? (
                                <div className="space-y-3">
                                    {q.options.map((opt, oIndex) => (
                                        <div key={oIndex} className="flex items-center gap-3">
                                            <input type="radio" name={`q_${q.id}_correct`} checked={Number(q.correctOption) === oIndex} onChange={() => updateQuestion(qIndex, { correctOption: oIndex })} className="w-5 h-5" title="Mark as correct answer" />
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
                                                        if (correctOption === oIndex) correctOption = 0;
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
                                    <button type="button" onClick={() => updateQuestion(qIndex, { options: [...q.options, { text: '' }] })} className="text-sm font-bold text-tertiary flex items-center gap-1 mt-2 hover:underline">
                                        <span className="material-symbols-outlined text-sm">add</span> Add Option
                                    </button>
                                </div>
                            ) : null}
                            {q.type === 'short_answer' ? (
                                <div>
                                    <label className="block text-sm font-bold text-on-surface-variant mb-1">Acceptable Correct Answer (Exact match)</label>
                                    <input type="text" value={q.correctText} onChange={(e) => updateQuestion(qIndex, { correctText: e.target.value })} className="w-full px-4 py-3 border border-success/40 bg-success/5 rounded-xl font-mono text-sm" placeholder="e.g. Washington" />
                                </div>
                            ) : null}
                            {q.type === 'draw' ? (
                                <p className="text-sm text-on-surface-variant">Students draw their answer. This question is not marked right or wrong automatically.</p>
                            ) : null}
                            {q.type === 'image_upload' ? (
                                <p className="text-sm text-on-surface-variant">Students upload a photo. This question is not marked right or wrong automatically.</p>
                            ) : null}
                        </div>
                    </div>
                ))}
            </div>

            <div className="sticky bottom-6 flex justify-center z-40 pointer-events-none mt-8">
                <div className="pointer-events-auto bg-white p-2 rounded-full shadow-2xl border border-outline-variant flex items-center gap-2">
                    <button type="button" onClick={addQuestion} className="flex items-center gap-2 px-6 py-3 bg-tertiary text-white font-bold rounded-full shadow-md">
                        <span className="material-symbols-outlined">add</span> Add Question
                    </button>
                    <div className="w-px h-8 bg-outline-variant mx-1" />
                    <button type="button" onClick={submitQuiz} className="flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-full shadow-md">
                        <span className="material-symbols-outlined">save</span> Save Quiz
                    </button>
                </div>
            </div>
        </div>
    );
}
