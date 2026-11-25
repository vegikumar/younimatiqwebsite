@extends('layouts.dashboard')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface">Class Feedback Reviews</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">Review feedback submitted by tutors and determine customer types.</p>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('admin.feedback.index') }}" class="clay-button inline-flex items-center px-4 py-2 {{ !request('type') ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                All
            </a>
            <a href="{{ route('admin.feedback.index', ['type' => 'demo']) }}" class="clay-button inline-flex items-center px-4 py-2 {{ request('type') === 'demo' ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                Demo Classes
            </a>
            <a href="{{ route('admin.feedback.index', ['type' => 'regular']) }}" class="clay-button inline-flex items-center px-4 py-2 {{ request('type') === 'regular' ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                Regular Classes
            </a>
        </div>
    </div>

    <div class="clay-card p-6 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/30 text-on-surface-variant">
                        <th class="py-4 px-4 font-bold">Class Type</th>
                        <th class="py-4 px-4 font-bold">Student</th>
                        <th class="py-4 px-4 font-bold">Tutor</th>
                        <th class="py-4 px-4 font-bold">Submitted</th>
                        <th class="py-4 px-4 font-bold">Customer Type</th>
                        <th class="py-4 px-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedbacks as $fb)
                    <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                        <td class="py-4 px-4">
                            @if($fb->session_type === 'demo')
                                <span class="px-2 py-1 bg-warning-container text-warning text-xs rounded-full font-bold uppercase">Demo</span>
                            @else
                                <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold uppercase">Regular</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 font-medium text-on-surface">
                            {{ $fb->student->name ?? 'Student' }}
                        </td>
                        <td class="py-4 px-4 text-on-surface">
                            {{ $fb->tutor->name ?? 'Deleted Tutor' }}
                        </td>
                        <td class="py-4 px-4 text-on-surface text-sm">
                            <x-local-time :date="$fb->created_at" format="date" />
                        </td>
                        <td class="py-4 px-4">
                            @if($fb->customer_type)
                                <span class="px-3 py-1 bg-tertiary-fixed text-tertiary rounded-lg text-sm font-bold border border-tertiary-fixed-dim">
                                    {{ $fb->customer_type }}
                                </span>
                            @else
                                <span class="text-sm text-outline italic">Pending Decision</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <a href="{{ route('admin.feedback.show', $fb) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm">
                                Review & Act
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-on-surface-variant">No feedback records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $feedbacks->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
