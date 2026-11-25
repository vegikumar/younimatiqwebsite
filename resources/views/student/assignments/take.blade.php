@extends('layouts.dashboard')
@section('title', $assignment->title)
@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12 space-y-8">
    <div class="text-center space-y-2">
        <h1 class="font-headline-lg text-3xl font-bold">{{ $assignment->title }}</h1>
        @if($assignment->description)
            <p class="text-on-surface-variant text-lg">{{ $assignment->description }}</p>
        @endif
        @if($assignment->due_at)
            <p class="text-sm font-bold text-error">Due: <x-local-time :date="$assignment->due_at" format="datetime" /></p>
        @endif
        @if($assignment->file_path)
            <a href="{{ route('secure.asset', ['path' => $assignment->file_path]) }}" target="_blank" class="inline-flex items-center gap-1 text-primary text-sm font-bold hover:underline">
                <span class="material-symbols-outlined text-[16px]">visibility</span> View assignment attachment
            </a>
        @endif
        <div class="flex justify-center gap-4 text-sm font-bold text-on-surface-variant">
            <span>{{ count($assignment->questions ?? []) }} Questions</span>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-error-container text-on-error-container rounded-xl border border-error/20 font-medium">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="space-y-12" id="assignment-form">
        @csrf

        @foreach($assignment->questions ?? [] as $index => $q)
            @php $qId = $q['id'] ?? uniqid(); @endphp
            <div class="clay-card p-6 md:p-8 space-y-6 bg-white relative overflow-hidden">
                <div class="absolute top-0 left-0 bg-primary text-white font-bold px-3 py-1 text-sm rounded-br-xl">
                    Q{{ $index + 1 }}
                </div>

                <div class="pt-4">
                    <div class="flex justify-between items-start gap-4">
                        <h3 class="text-xl font-bold leading-relaxed">{{ $q['question'] }}</h3>
                        @if(!empty($q['xp']) && $q['xp'] > 0)
                            <span class="bg-tertiary-fixed text-tertiary font-bold px-2 py-1 rounded text-sm shrink-0 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">star</span> {{ $q['xp'] }} XP</span>
                        @endif
                    </div>
                    @if(!empty($q['image_url']))
                        <div class="mt-4">
                            <img src="{{ $q['image_url'] }}" alt="Question image" class="max-h-64 rounded-xl border border-outline-variant shadow-sm object-contain">
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    @if(($q['type'] ?? 'mcq') === 'mcq')
                        <div class="space-y-3">
                            @foreach($q['options'] ?? [] as $optIndex => $opt)
                                <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:bg-primary/5 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                    <input type="radio" name="answers[{{ $qId }}]" value="{{ $optIndex }}" required class="w-5 h-5 text-primary focus:ring-primary border-outline">
                                    <span class="text-base">{{ $opt['text'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif(($q['type'] ?? '') === 'short_answer')
                        <input type="text" name="answers[{{ $qId }}]" required placeholder="Type your answer here..." class="w-full px-4 py-4 border-2 border-outline-variant rounded-xl focus:border-primary focus:ring-0 text-lg transition-colors">
                    @elseif(($q['type'] ?? '') === 'draw')
                        <div class="space-y-3" x-data="drawingPad('{{ $qId }}')">
                            <p class="text-sm text-on-surface-variant font-medium">Use your mouse or finger to draw your answer below.</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setTool('pen')" :class="tool === 'pen' ? 'bg-primary text-white' : 'bg-surface-container-low'" class="px-3 py-1.5 rounded-lg text-sm font-bold border">Pen</button>
                                <button type="button" @click="setTool('eraser')" :class="tool === 'eraser' ? 'bg-primary text-white' : 'bg-surface-container-low'" class="px-3 py-1.5 rounded-lg text-sm font-bold border">Eraser</button>
                                <input type="color" x-model="color" class="w-10 h-9 rounded cursor-pointer border" title="Pen color">
                                <button type="button" @click="clearCanvas()" class="px-3 py-1.5 rounded-lg text-sm font-bold border bg-error-container text-on-error-container">Clear</button>
                            </div>
                            <div class="border-2 border-dashed border-outline-variant rounded-xl overflow-hidden bg-white touch-none">
                                <canvas x-ref="canvas" data-drawing="{{ $qId }}" width="800" height="400" class="w-full max-w-full cursor-crosshair" style="height: auto; aspect-ratio: 2/1;"
                                    @mousedown="startDraw($event)" @mousemove="draw($event)" @mouseup="endDraw()" @mouseleave="endDraw()"
                                    @touchstart.prevent="startDraw($event)" @touchmove.prevent="draw($event)" @touchend.prevent="endDraw()"></canvas>
                            </div>
                            <input type="hidden" name="answer_drawings[{{ $qId }}]" x-ref="hiddenInput" required>
                        </div>
                    @elseif(($q['type'] ?? '') === 'image_upload')
                        <div class="space-y-3">
                            <p class="text-sm text-on-surface-variant font-medium">Upload an image file as your answer (JPG, PNG, or WEBP).</p>
                            <input type="file" name="answer_files[{{ $qId }}]" accept="image/jpeg,image/jpg,image/png,image/webp" required
                                class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary"
                                onchange="previewUploadImage(this, 'preview-{{ $qId }}')">
                            <div id="preview-{{ $qId }}" class="hidden">
                                <img src="" alt="Preview" class="max-h-48 rounded-xl border object-contain">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="text-center pt-8">
            <button type="submit" class="btn-primary py-4 px-12 text-lg rounded-full font-bold shadow-lg hover:scale-105 transition-transform">
                Submit Assignment
            </button>
        </div>
    </form>
</div>

<script>
function previewUploadImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        preview.classList.remove('hidden');
        preview.querySelector('img').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
}

document.getElementById('assignment-form').addEventListener('submit', function(e) {
    document.querySelectorAll('canvas[data-drawing]').forEach(canvas => {
        const qId = canvas.dataset.drawing;
        const hidden = document.querySelector(`input[name="answer_drawings[${qId}]"]`);
        if (hidden) {
            hidden.value = canvas.toDataURL('image/png');
        }
    });

    if (!confirm('Submit your assignment answers? You can update your submission until it is graded.')) {
        e.preventDefault();
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('drawingPad', (questionId) => ({
        tool: 'pen',
        color: '#111827',
        drawing: false,
        ctx: null,

        init() {
            const canvas = this.$refs.canvas;
            this.ctx = canvas.getContext('2d');
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, canvas.width, canvas.height);
            this.syncHidden();
        },

        setTool(t) { this.tool = t; },

        getPos(e) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY,
            };
        },

        startDraw(e) {
            this.drawing = true;
            const pos = this.getPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        },

        draw(e) {
            if (!this.drawing) return;
            const pos = this.getPos(e);
            this.ctx.lineWidth = this.tool === 'eraser' ? 24 : 3;
            this.ctx.strokeStyle = this.tool === 'eraser' ? '#ffffff' : this.color;
            this.ctx.globalCompositeOperation = this.tool === 'eraser' ? 'destination-out' : 'source-over';
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        },

        endDraw() {
            if (this.drawing) {
                this.drawing = false;
                this.syncHidden();
            }
        },

        clearCanvas() {
            const canvas = this.$refs.canvas;
            this.ctx.globalCompositeOperation = 'source-over';
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, canvas.width, canvas.height);
            this.syncHidden();
        },

        syncHidden() {
            const canvas = this.$refs.canvas;
            this.$refs.hiddenInput.value = canvas.toDataURL('image/png');
        },
    }));
});
</script>
@endsection
