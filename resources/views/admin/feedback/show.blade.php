@extends('layouts.dashboard')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    
    <div class="mb-8 flex items-center gap-4">
        <a href="{{ route('admin.feedback.index') }}" class="text-primary hover:underline font-bold text-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Feedback
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        
        <!-- Left Column: Feedback Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="clay-card p-5 md:p-8 bg-white border-t-8 {{ $feedback->session_type === 'demo' ? 'border-warning' : 'border-primary' }}">
                <h2 class="font-headline-md text-2xl text-on-surface mb-6 border-b border-outline-variant/30 pb-4">
                    {{ $feedback->session_type === 'demo' ? 'Demo Class Feedback' : 'Regular Class Feedback' }}
                </h2>

                <div class="space-y-6">
                    @foreach($feedback->feedback_data as $question => $answer)
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20">
                            <h4 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                {{ str_replace('_', ' ', $question) }}
                            </h4>
                            <p class="text-on-surface text-lg">
                                {{ $answer ?: 'N/A' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Class & User Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="clay-card p-6">
                    <h3 class="font-bold text-lg mb-4 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">person</span> Student Info
                    </h3>
                    <p class="text-sm text-on-surface-variant mb-1">Name: <span class="font-bold text-on-surface">{{ $feedback->student->name ?? 'Student' }}</span></p>
                    <p class="text-sm text-on-surface-variant mb-1">Email: <span class="font-bold text-on-surface">{{ $feedback->student->email ?? 'N/A' }}</span></p>
                    <div class="mt-4">
                        <a href="{{ route('admin.users.show', $feedback->student_id ?? 0) }}" class="text-primary text-xs font-bold hover:underline">View Profile &rarr;</a>
                    </div>
                </div>

                <div class="clay-card p-6">
                    <h3 class="font-bold text-lg mb-4 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">school</span> Tutor Info
                    </h3>
                    <p class="text-sm text-on-surface-variant mb-1">Name: <span class="font-bold text-on-surface">{{ $feedback->tutor->name ?? 'Tutor' }}</span></p>
                    <p class="text-sm text-on-surface-variant mb-1">Email: <span class="font-bold text-on-surface">{{ $feedback->tutor->email ?? 'N/A' }}</span></p>
                </div>
            </div>
        </div>

        <!-- Right Column: Admin Action -->
        <div class="lg:col-span-1">
            <div class="clay-card p-6 bg-primary-container text-on-primary-container">
                <h3 class="font-headline-md text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">gavel</span> Admin Decision
                </h3>
                <p class="text-sm mb-6 opacity-90">Determine the customer type and leave internal notes for follow-up actions.</p>

                <form action="{{ route('admin.feedback.update', $feedback) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-bold mb-2 text-sm">Customer Type / Lead Status</label>
                        <select name="customer_type" class="w-full bg-white text-on-surface border-2 border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary transition-colors">
                            <option value="">-- Select Status --</option>
                            <option value="Hot Lead" {{ $feedback->customer_type === 'Hot Lead' ? 'selected' : '' }}>🔥 Hot Lead</option>
                            <option value="Warm Lead" {{ $feedback->customer_type === 'Warm Lead' ? 'selected' : '' }}>☀️ Warm Lead</option>
                            <option value="Follow Up Required" {{ $feedback->customer_type === 'Follow Up Required' ? 'selected' : '' }}>📅 Follow Up Required</option>
                            <option value="Not Interested" {{ $feedback->customer_type === 'Not Interested' ? 'selected' : '' }}>🧊 Not Interested</option>
                            <option value="Enrolled" {{ $feedback->customer_type === 'Enrolled' ? 'selected' : '' }}>✅ Enrolled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-2 text-sm">Internal Admin Notes</label>
                        <textarea name="admin_notes" rows="4" class="w-full bg-white text-on-surface border-2 border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Notes for sales team or follow-up plan...">{{ $feedback->admin_notes }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                        Save Decision
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
