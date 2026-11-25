@props(["quiz" => null])
<div x-data="quizBuilder()" class="space-y-8">
    
    <!-- Quiz Settings -->
    <div class="clay-card p-6 space-y-4 border-l-4 border-primary">
        <h2 class="text-xl font-bold">Quiz Settings</h2>
        
        <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-1">Quiz Title</label>
            <input type="text" name="title" x-model="quiz.title" required class="w-full px-4 py-3 border rounded-xl" placeholder="e.g. Midterm Examination">
        </div>
        
        <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-1">Description (Optional)</label>
            <textarea name="description" x-model="quiz.description" rows="2" class="w-full px-4 py-3 border rounded-xl" placeholder="Provide instructions for this quiz"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-on-surface-variant mb-1">Score to unlock badge (%)</label>
                <input type="number" name="passing_score" x-model="quiz.passing_score" required min="1" max="100" class="w-full px-4 py-3 border rounded-xl">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface-variant mb-1">Total XP Reward</label>
                <input type="number" name="xp_reward" x-model="quiz.xp_reward" required min="0" class="w-full px-4 py-3 border rounded-xl" placeholder="e.g. 500">
            </div>
        </div>
    </div>

    <!-- Questions Area -->
    <div class="space-y-6">
        <template x-for="(q, qIndex) in quiz.questions" :key="q.id">
            <div class="clay-card p-6 space-y-4 border-l-4 border-tertiary relative bg-white">
                
                <!-- Question Header -->
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 space-y-4">
                        <div class="flex items-center gap-4">
                            <span class="font-bold text-lg">Question <span x-text="qIndex + 1"></span></span>
                            <select x-model="q.type" @change="resetQuestion(q)" class="px-3 py-1.5 border rounded-lg text-sm bg-surface-container-low font-bold">
                                <option value="mcq">Multiple Choice</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-2">
                            <input type="text" x-model="q.question" required class="flex-1 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="Type your question here...">
                            <input type="number" x-model="q.xp" required min="0" class="w-24 px-4 py-3 border rounded-xl text-lg font-bold" placeholder="XP" title="XP reward for this question">
                        </div>
                        
                        <!-- Question Media -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-primary cursor-pointer w-fit">
                                <span class="material-symbols-outlined text-sm">image</span> Add Image
                                <input type="file" :name="`questions[${qIndex}][image_file]`" accept="image/*" class="hidden" @change="handleImagePreview($event, q)">
                            </label>
                            
                            <!-- Existing Image -->
                            <template x-if="q.image_url && !q.imagePreview && !q.removeImage">
                                <div class="mt-2 relative inline-block">
                                    <img :src="q.image_url" class="h-32 rounded-lg border object-contain">
                                    <button type="button" @click="q.removeImage = true" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 shadow-sm" title="Remove Image">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>
                            </template>

                            <!-- New Image Preview -->
                            <template x-if="q.imagePreview">
                                <div class="mt-2 relative inline-block">
                                    <img :src="q.imagePreview" class="h-32 rounded-lg border object-contain border-primary">
                                    <button type="button" @click="q.imagePreview = null; $event.target.closest('div').previousElementSibling.querySelector('input').value=''" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>
                            </template>
                            
                            <!-- Hidden input to track removal of existing image -->
                            <input type="hidden" :name="`questions[${qIndex}][remove_image]`" :value="q.removeImage ? '1' : '0'">
                        </div>
                    </div>
                    
                    <button type="button" @click="removeQuestion(qIndex)" class="p-2 text-on-surface-variant hover:text-error transition-colors bg-surface-container-low rounded-xl" title="Delete Question">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>

                <hr class="border-outline-variant/30">

                <!-- Answers Configuration -->
                <div class="pl-4 border-l-2 border-outline-variant/30 space-y-4">
                    
                    <!-- MCQ Options -->
                    <template x-if="q.type === 'mcq'">
                        <div class="space-y-3">
                            <template x-for="(opt, oIndex) in q.options" :key="oIndex">
                                <div class="flex items-center gap-3">
                                    <input type="radio" :name="`q_${q.id}_correct`" :value="oIndex" x-model="q.correctOption" class="w-5 h-5 text-tertiary focus:ring-tertiary border-outline cursor-pointer" title="Mark as correct answer">
                                    <input type="text" x-model="q.options[oIndex].text" class="flex-1 px-3 py-2 border rounded-lg text-sm" placeholder="Option text...">
                                    
                                    <button type="button" @click="removeOption(q, oIndex)" x-show="q.options.length > 2" class="p-1.5 text-on-surface-variant hover:text-error">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </template>
                            
                            <button type="button" @click="addOption(q)" class="text-sm font-bold text-tertiary flex items-center gap-1 mt-2 hover:underline">
                                <span class="material-symbols-outlined text-sm">add</span> Add Option
                            </button>
                        </div>
                    </template>

                    <!-- Short Answer -->
                    <template x-if="q.type === 'short_answer'">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1">Acceptable Correct Answer (Exact match)</label>
                            <input type="text" x-model="q.correctText" class="w-full px-4 py-3 border border-success/40 bg-success/5 rounded-xl font-mono text-sm" placeholder="e.g. Washington">
                            <p class="text-xs text-on-surface-variant mt-1">Automated grading will check if the student's answer matches this text exactly (case-insensitive).</p>
                        </div>
                    </template>

                </div>

            </div>
        </template>
    </div>

    <!-- Floating Add Button -->
    <div class="sticky bottom-6 flex justify-center z-40 pointer-events-none mt-8">
        <div class="pointer-events-auto bg-white p-2 rounded-full shadow-2xl border border-outline-variant flex items-center gap-2">
            <button type="button" @click="addQuestion()" class="flex items-center gap-2 px-6 py-3 bg-tertiary text-white font-bold rounded-full hover:scale-105 transition-transform shadow-md">
                <span class="material-symbols-outlined">add</span> Add Question
            </button>
            <div class="w-px h-8 bg-outline-variant mx-1"></div>
            <button type="button" @click="submitQuiz()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-full hover:scale-105 transition-transform shadow-md">
                <span class="material-symbols-outlined">save</span> Save Quiz
            </button>
        </div>
    </div>

    <!-- Hidden Form for actual submission -->
    <input type="hidden" name="questions_json" :value="JSON.stringify(quiz.questions)">
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quizBuilder', () => ({
        quiz: {
            title: @json($quiz->title ?? ''),
            description: @json($quiz->description ?? ''),
            passing_score: @json($quiz->passing_score ?? 70),
            xp_reward: @json($quiz->xp_reward ?? 100),
            questions: @json($quiz->questions ?? [])
        },
        
        init() {
            if (this.quiz.questions.length === 0) {
                this.addQuestion();
            } else {
                // Ensure legacy data is structured properly for UI
                this.quiz.questions.forEach(q => {
                    if (!q.id) q.id = this.generateId();
                    if (!q.type) q.type = 'mcq';
                    if (!q.xp) q.xp = 10;
                    if (!q.options) q.options = [{text: ''}, {text: ''}];
                    q.imagePreview = null;
                    q.removeImage = false;
                });
            }
        },

        generateId() {
            return Math.random().toString(36).substring(2, 9);
        },

        addQuestion() {
            this.quiz.questions.push({
                id: this.generateId(),
                type: 'mcq', // mcq, short_answer
                question: '',
                xp: 10,
                options: [
                    { text: '' },
                    { text: '' }
                ],
                correctOption: 0, 
                correctText: '', 
                imagePreview: null,
                image_url: null,
                removeImage: false
            });
            
            // Scroll to bottom
            setTimeout(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }), 100);
        },

        removeQuestion(index) {
            if(confirm('Delete this question?')) {
                this.quiz.questions.splice(index, 1);
            }
        },

        addOption(q) {
            q.options.push({ text: '' });
        },

        removeOption(q, index) {
            q.options.splice(index, 1);
            // Re-adjust correct option index
            if (q.correctOption === index) q.correctOption = 0;
            if (q.correctOption > index) q.correctOption--;
        },

        resetQuestion(q) {
            if (q.type === 'mcq' && (!q.options || q.options.length === 0)) {
                q.options = [{text: ''}, {text: ''}];
                q.correctOption = 0;
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

        submitQuiz() {
            // Basic validation
            if (!this.quiz.title) {
                alert('Please enter a quiz title.');
                return;
            }
            
            for (let i = 0; i < this.quiz.questions.length; i++) {
                const q = this.quiz.questions[i];
                if (!q.question.trim()) {
                    alert(`Question ${i + 1} is missing text.`);
                    return;
                }
                if (q.type === 'mcq') {
                    for(let o of q.options) {
                        if(!o.text.trim()) {
                            alert(`Question ${i + 1} has empty options.`);
                            return;
                        }
                    }
                    if (q.correctOption === null || q.correctOption === undefined || q.correctOption === '') {
                        alert(`Question ${i + 1} does not have a correct option selected.`);
                        return;
                    }
                } else if (q.type === 'short_answer') {
                    if (!q.correctText || !q.correctText.trim()) {
                        alert(`Question ${i + 1} requires an acceptable correct answer.`);
                        return;
                    }
                }
                
                // Clear out large base64 image preview string before JSON stringify 
                // so it doesn't get sent to backend in questions_json.
                // The actual file is uploaded via multipart/form-data input type="file"
                const originalPreview = q.imagePreview;
                q.imagePreview = null; 
            }
            
            // Submit the parent form
            event.target.closest('form').submit();
        }
    }));
});
</script>
