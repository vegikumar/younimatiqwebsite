@extends('layouts.dashboard')

@section('title', 'Growth & Re-engagement Hub')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Growth & Re-engagement Nudges</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Automatically follow up with families who showed interest or abandoned checkout for your classes.</p>
    </div>

    

    <div class="clay-card bg-white p-6 md:p-8 space-y-6">
        <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">groups</span> Interested Families & Cart Abandonment Follow-ups
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/20 text-xs">
                        <th class="py-3 px-4 font-bold">Family / Learner</th>
                        <th class="py-3 px-4 font-bold">Class Explored</th>
                        <th class="py-3 px-4 font-bold">Interest Date</th>
                        <th class="py-3 px-4 font-bold">Status</th>
                        <th class="py-3 px-4 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr class="border-b border-outline-variant/10 text-xs hover:bg-surface-container-low">
                            <td class="py-3 px-4 font-bold">{{ $lead->user->name ?? 'Interested Parent' }}</td>
                            <td class="py-3 px-4 font-medium">{{ $lead->course->title ?? 'Class Listing' }}</td>
                            <td class="py-3 px-4 text-on-surface-variant"><x-local-time :date="$lead->created_at" format="date" /></td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $lead->status === 'nudged' ? 'bg-secondary-container text-on-secondary-container' : 'bg-warning-container text-warning' }}">
                                    {{ $lead->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($lead->status !== 'enrolled')
                                    <form method="POST" action="{{ route('tutor.growth.nudge', $lead) }}">
                                        @csrf
                                        <button type="submit" class="btn-enroll py-1.5 px-3 text-[11px] font-bold">
                                            Send Re-engagement Nudge
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-on-tertiary-container font-bold">Enrolled!</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-on-surface-variant text-xs">No interested leads or cart abandonments tracked yet. The system automatically tracks families who explore your classes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
