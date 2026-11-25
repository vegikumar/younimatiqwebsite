@props(['assignment' => null])

<div x-data="assignmentQuestionBuilder()" class="space-y-6">
    <div class="space-y-6">
        <template x-for="(q, qIndex) in questions" :key="q.id">
            <div class="clay-card p-6 space-y-4 border-l-4 border-tertiary relative bg-white">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 space-y-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="font-bold text-lg">Question <span x-text="qIndex + 1"></span></span>
                            <select x-model="q.type" @change="resetQuestion(q)" class="px-3 py-1.5 border rounded-lg text-sm bg-surface-container-low font-bold">
                                <option value="mcq">Multiple Choice</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="draw">Draw Answer</option>
                                <option value="image_upload">Upload Image Answer</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" x-model="q.question" required class="flex-1 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="Type your question here...">
                            <input type="number" x-model="q.xp" required min="0" class="w-24 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="XP" title="XP reward for this question">
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-primary cursor-pointer w-fit">
                                <span class="material-symbols-outlined text-sm">image</span> Add prompt image
                                <input type="file" :name="`questions[${qIndex}][image_file]`" accept="image/*" class="hidden" @change="handleImagePreview($event, q)">
                            </label>

                            <template x-if="q.image_url && !q.imagePreview && !q.removeImage">
                                <div class="mt-2 relative inline-block">
                                    <img :src="q.image_url" class="h-32 rounded-lg border object-contain">
                                    <button type="button" @click="q.removeImage = true" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="q.imagePreview">
                                <div class="mt-2 relative inline-block">
                                    <img :src="q.imagePreview" class="h-32 rounded-lg border object-contain border-primary">
                                </div>
                            </template>

                            <input type="hidden" :name="`questions[${qIndex}][remove_image]`" :value="q.removeImage ? '1' : '0'">
                        </div>
                    </div>

                    <button type="button" @click="removeQuestion(qIndex)" class="p-2 text-on-surface-variant hover:text-error transition-colors bg-surface-container-low rounded-xl" title="Delete Question">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>

                <hr class="border-outline-variant/30">

                <div class="pl-4 border-l-2 border-outline-variant/30 space-y-4">
                    <template x-if="q.type === 'mcq'">
                        <div class="space-y-3">
                            <p class="text-xs text-on-surface-variant font-medium">Optional: mark the correct option to enable auto-check on submission.</p>
                            <template x-for="(opt, oIndex) in q.options" :key="oIndex">
                                <div class="flex items-center gap-3">
                                    <input type="radio" :name="`q_${q.id}_correct`" :value="oIndex" x-model="q.correctOption" class="w-5 h-5 text-tertiary" title="Mark as correct (optional)">
                                    <input type="text" x-model="q.options[oIndex].text" class="flex-1 px-3 py-2 border rounded-lg text-sm" placeholder="Option text...">
                                    <button type="button" @click="removeOption(q, oIndex)" x-show="q.options.length > 2" class="p-1.5 text-on-surface-variant hover:text-error">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="clearCorrectOption(q)" x-show="q.correctOption !== null && q.correctOption !== ''" class="text-xs font-bold text-on-surface-variant hover:underline">Clear correct answer</button>
                            <button type="button" @click="addOption(q)" class="text-sm font-bold text-tertiary flex items-center gap-1 hover:underline">
                                <span class="material-symbols-outlined text-sm">add</span> Add Option
                            </button>
                        </div>
                    </template>

                    <template x-if="q.type === 'short_answer'">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1">Correct answer <span class="font-normal">(optional)</span></label>
                            <input type="text" x-model="q.correctText" class="w-full px-4 py-3 border border-outline-variant rounded-xl font-mono text-sm" placeholder="e.g. Washington">
                            <p class="text-xs text-on-surface-variant mt-1">If set, answers are auto-checked with case-insensitive exact match.</p>
                        </div>
                    </template>

                    <template x-if="q.type === 'draw'">
                        <div class="p-4 bg-warning-container border border-warning-container rounded-xl text-sm text-warning">
                            <span class="font-bold">Draw answer:</span> Students will get a drawing canvas. Grade manually after submission.
                        </div>
                    </template>

                    <template x-if="q.type === 'image_upload'">
                        <div class="p-4 bg-secondary-container border border-secondary-container rounded-xl text-sm text-on-secondary-container">
                            <span class="font-bold">Image upload:</span> Students will upload an image. Grade manually after submission.
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="addQuestion()" class="flex items-center gap-2 px-5 py-3 bg-tertiary text-white font-bold rounded-full hover:scale-105 transition-transform shadow-md">
            <span class="material-symbols-outlined">add</span> Add Question
        </button>
        <p class="text-sm text-on-surface-variant" x-show="questions.length === 0">Add at least one question to this assignment.</p>
    </div>

    <input type="hidden" name="questions_json" :value="JSON.stringify(questions)">
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assignmentQuestionBuilder', () => ({
        questions: @json($assignment->questions ?? []),

        init() {
            if (this.questions.length === 0) {
                this.addQuestion();
            } else {
                this.questions.forEach(q => {
                    if (!q.id) q.id = this.generateId();
                    if (!q.type) q.type = 'mcq';
                    if (!q.xp) q.xp = 10;
                    if (!q.options) q.options = [{text: ''}, {text: ''}];
                    if (!Object.prototype.hasOwnProperty.call(q, 'correctOption')) q.correctOption = null;
                    if (!Object.prototype.hasOwnProperty.call(q, 'correctText')) q.correctText = '';
                    q.imagePreview = null;
                    q.removeImage = false;
                });
            }
        },

        generateId() {
            return Math.random().toString(36).substring(2, 9);
        },

        addQuestion() {
            this.questions.push({
                id: this.generateId(),
                type: 'mcq',
                question: '',
                xp: 10,
                options: [{ text: '' }, { text: '' }],
                correctOption: null,
                correctText: '',
                imagePreview: null,
                image_url: null,
                removeImage: false,
            });
        },

        removeQuestion(index) {
            if (confirm('Delete this question?')) {
                this.questions.splice(index, 1);
            }
        },

        addOption(q) {
            q.options.push({ text: '' });
        },

        removeOption(q, index) {
            q.options.splice(index, 1);
            if (q.correctOption === index) q.correctOption = null;
            if (q.correctOption > index) q.correctOption--;
        },

        clearCorrectOption(q) {
            q.correctOption = null;
        },

        resetQuestion(q) {
            if (q.type === 'mcq' && (!q.options || q.options.length === 0)) {
                q.options = [{text: ''}, {text: ''}];
            }
        },

        handleImagePreview(event, q) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    q.imagePreview = e.target.result;
                    q.removeImage = false;
                };
                reader.readAsDataURL(file);
            }
        },
    }));
});
</script>
