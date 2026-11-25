@extends('layouts.dashboard')

@section('title', 'Mandatory Policy Training')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="px-3 py-1 bg-warning-container text-warning rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Mandatory Educator Onboarding
                </span>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Educator Policy & Safety Training</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Before teaching your first class, watch these two short policy training video modules covering content standards, learner safety, and privacy.</p>
            </div>
            @if($tutor->hasCompletedPolicyTraining())
                <span class="px-4 py-2 bg-tertiary-fixed text-tertiary rounded-xl font-bold text-xs flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">verified</span> Policy Training Complete!
                </span>
            @endif
        </div>
    </div>

    
    

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:p-8">
        <!-- Module 1: Class Content Standards -->
        <div class="clay-card bg-white p-6 md:p-8 space-y-4 border border-outline-variant/20">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">menu_book</span> Module 1: Content Standards
                </h3>
                @if(in_array('content_standards', $completedModules))
                    <span class="px-2.5 py-0.5 bg-tertiary-fixed text-tertiary rounded-full text-[10px] font-bold">COMPLETED</span>
                @endif
            </div>

            <div class="aspect-video bg-black rounded-2xl flex items-center justify-center text-white relative overflow-hidden">
                <span class="material-symbols-outlined text-6xl text-white/80">play_circle</span>
            </div>

            <p class="text-xs text-on-surface-variant leading-relaxed">
                Learn class content guidelines, age-appropriate curriculum structuring, and Younimatiq quality standards.
            </p>

            @if(!in_array('content_standards', $completedModules))
                <form method="POST" action="{{ route('tutor.policy-training.complete') }}">
                    @csrf
                    <input type="hidden" name="module_slug" value="content_standards">
                    <button type="submit" class="btn-enroll w-full py-2.5 text-xs font-bold">
                        Mark Module 1 Completed
                    </button>
                </form>
            @endif
        </div>

        <!-- Module 2: Safety & Privacy -->
        <div class="clay-card bg-white p-6 md:p-8 space-y-4 border border-outline-variant/20">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">security</span> Module 2: Safety & Privacy
                </h3>
                @if(in_array('safety_privacy', $completedModules))
                    <span class="px-2.5 py-0.5 bg-tertiary-fixed text-tertiary rounded-full text-[10px] font-bold">COMPLETED</span>
                @endif
            </div>

            <div class="aspect-video bg-black rounded-2xl flex items-center justify-center text-white relative overflow-hidden">
                <span class="material-symbols-outlined text-6xl text-white/80">play_circle</span>
            </div>

            <p class="text-xs text-on-surface-variant leading-relaxed">
                Understand learner privacy protection, anti-bypass communication policies, and child safety rules.
            </p>

            @if(!in_array('safety_privacy', $completedModules))
                <form method="POST" action="{{ route('tutor.policy-training.complete') }}">
                    @csrf
                    <input type="hidden" name="module_slug" value="safety_privacy">
                    <button type="submit" class="btn-enroll w-full py-2.5 text-xs font-bold">
                        Mark Module 2 Completed
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
