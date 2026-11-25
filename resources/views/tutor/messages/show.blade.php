@extends('layouts.dashboard')

@section('title', 'Conversation with ' . ($conversation->student->name ?? 'Student'))

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-6 md:p-8 mb-8 border-t-8 border-primary relative overflow-hidden flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary text-white font-bold flex items-center justify-center text-lg">
                {{ substr($conversation->student->name ?? 'S', 0, 1) }}
            </div>
            <div>
                <h1 class="font-headline-sm text-xl font-bold text-on-surface">1:1 Conversation with {{ $conversation->student->name ?? 'Student' }}</h1>
                <p class="text-xs text-on-surface-variant">Private Direct Message Thread</p>
            </div>
        </div>
        <a href="{{ route('tutor.messages.index') }}" class="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Inbox
        </a>
    </div>

    
    

    <div class="clay-card p-6 bg-white space-y-6">
        
        <!-- Messages Stream -->
        <div class="space-y-4 max-h-[500px] overflow-y-auto p-4 bg-surface-container-low/30 rounded-2xl">
            @forelse($messages as $msg)
                @php
                    $isTutor = $msg->sender_id === Auth::id();
                @endphp
                <div class="flex flex-col {{ $isTutor ? 'items-end' : 'items-start' }}">
                    <div class="max-w-[75%] p-4 rounded-2xl text-sm shadow-sm {{ $isTutor ? 'bg-primary text-on-primary rounded-br-none' : 'bg-white border border-outline-variant/20 text-on-surface rounded-bl-none' }}">
                        <p class="text-[10px] opacity-75 mb-1 font-bold">{{ $msg->sender->name ?? 'User' }} • <x-local-time :date="$msg->created_at" format="time" /></p>
                        @if($msg->message)
                            <p class="whitespace-pre-line leading-relaxed">{{ $msg->message }}</p>
                        @endif
                        @if($msg->attachment_url)
                            <div class="mt-2 pt-2 border-t border-white/20">
                                <a href="{{ $msg->attachment_url }}" target="_blank" class="flex items-center gap-2 font-bold hover:underline text-xs">
                                    <span class="material-symbols-outlined text-sm">{{ $msg->attachment_type === 'video' ? 'movie' : 'attach_file' }}</span>
                                    {{ $msg->attachment_name }} ({{ number_format(($msg->file_size_bytes ?? 0) / 1024 / 1024, 2) }} MB)
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-xs text-on-surface-variant">No private messages exchanged yet. Send a message below to start chatting!</p>
            @endforelse
        </div>

        <!-- Send Message Form -->
        <form method="POST" action="{{ route('tutor.messages.store', $conversation) }}" enctype="multipart/form-data" class="space-y-3 pt-2">
            @csrf
            <div>
                <textarea name="message" rows="3" placeholder="Type a private message..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-2xl outline-none text-sm resize-none focus:border-primary"></textarea>
            </div>
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Attachment (Video up to 1GB, docs up to 10MB)</label>
                    <input type="file" name="attachment" class="text-xs text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container">
                </div>
                <button type="submit" class="btn-enroll py-2.5 px-6 text-xs font-bold whitespace-nowrap">
                    <span class="material-symbols-outlined text-sm">send</span> Send Private Message
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
