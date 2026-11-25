@extends('layouts.dashboard')
@section('title', $quiz->title)
@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12 space-y-8">
    <div class="text-center space-y-2">
        <h1 class="font-headline-lg text-3xl font-bold">{{ $quiz->title }}</h1>
        @if($quiz->description)
            <p class="text-on-surface-variant text-lg">{{ $quiz->description }}</p>
        @endif
        <div class="flex justify-center gap-4 text-sm font-bold text-on-surface-variant">
            <span>{{ count($quiz->questions ?? []) }} Questions</span>
            @if($quiz->xp_reward > 0)
                <span>&bull;</span>
                <span class="text-tertiary flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">star</span> {{ $quiz->xp_reward }} XP</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-12">
        @csrf
        
        @foreach($quiz->questions ?? [] as $index => $q)
            @php $qId = $q['id'] ?? uniqid(); @endphp
            <div class="clay-card p-6 md:p-8 space-y-6 bg-white relative overflow-hidden group">
                <!-- Question Number Badge -->
                <div class="absolute top-0 left-0 bg-primary text-white font-bold px-3 py-1 text-sm rounded-br-xl">
                    Q{{ $index + 1 }}
                </div>

                <!-- Question Text & Media -->
                <div class="pt-4">
                    <div class="flex justify-between items-start gap-4">
                        <h3 class="text-xl font-bold leading-relaxed">{{ $q['question'] }}</h3>
                        @if(!empty($q['xp']) && $q['xp'] > 0)
                            <span class="bg-tertiary-fixed text-tertiary font-bold px-2 py-1 rounded text-sm shrink-0 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">star</span> {{ $q['xp'] }} XP</span>
                        @endif
                    </div>
                    @if(!empty($q['image_url']))
                        <div class="mt-4">
                            <img src="{{ $q['image_url'] }}" alt="Question Image" class="max-h-64 rounded-xl border border-outline-variant shadow-sm object-contain">
                        </div>
                    @endif
                </div>

                <!-- Answers Input -->
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
                        <div>
                            <input type="text" name="answers[{{ $qId }}]" required placeholder="Type your answer here..." class="w-full px-4 py-4 border-2 border-outline-variant rounded-xl focus:border-primary focus:ring-0 text-lg transition-colors">
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="text-center pt-8">
            <button type="submit" class="btn-primary py-4 px-12 text-lg rounded-full font-bold shadow-lg hover:scale-105 transition-transform" onclick="return confirm('Are you sure you want to submit your answers?')">
                Submit Quiz
            </button>
        </div>
    </form>
</div>
@endsection
