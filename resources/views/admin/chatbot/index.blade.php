@extends('layouts.dashboard')

@section('title', 'Chatbot Training Data')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-10">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Chatbot Training Data</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Train your automated assistant with Q&A pairs.</p>
        </div>
        <a href="{{ route('admin.chatbot.create') }}" class="btn-primary btn-sm flex items-center gap-2">
            <span class="material-symbols-outlined">add</span> Add New QA Pair
        </a>
    </div>

    

    <div class="clay-card p-5 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold text-on-surface">Question / Keywords</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Answer Snippet</th>
                        <th class="py-4 px-4 font-bold text-on-surface text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trainings as $training)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4">
                                <div class="font-bold text-on-surface mb-1">{{ $training->question }}</div>
                                @if($training->tags)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(explode(',', $training->tags) as $tag)
                                            <span class="text-xs bg-primary-container text-on-primary-container px-2 py-0.5 rounded-md">{{ trim($tag) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-on-surface-variant">
                                {{ Str::limit($training->answer, 60) }}
                            </td>
                            <td class="py-4 px-4 text-right">
                                <a href="{{ route('admin.chatbot.edit', $training->id) }}" class="p-2 text-primary hover:bg-primary-container rounded-lg transition-colors inline-block">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.chatbot.destroy', $training->id) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this training data?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-error hover:bg-error-container rounded-lg transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2">smart_toy</span>
                                <p>Your chatbot has no training data yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
