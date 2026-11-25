@extends('layouts.dashboard')

@section('title', 'Submit Class Feedback')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 border-t-8 border-primary bg-white max-w-3xl mx-auto">
        
        <div class="mb-8">
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">
                {{ $type === 'demo' ? 'Demo Class Feedback' : 'Class Session Feedback' }}
            </h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Submit your feedback for <strong>{{ $studentName }}</strong>.
            </p>
        </div>

        <form action="{{ route('tutor.feedback.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">

            @if($type === 'demo')
                <!-- Demo Class Form -->
                <div>
                    <label class="block font-bold mb-2 text-on-surface">Did the student enjoy the demo class? *</label>
                    <select name="enjoyed_class" required class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                        <option value="">Select an option</option>
                        <option value="Very much">Very much</option>
                        <option value="Somewhat">Somewhat</option>
                        <option value="Not really">Not really</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold mb-2 text-on-surface">What is the student's current skill level? *</label>
                    <select name="skill_level" required class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                        <option value="">Select an option</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold mb-2 text-on-surface">Recommended Follow-up (Customer Type) *</label>
                    <select name="customer_type_recommendation" required class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                        <option value="">Select an option</option>
                        <option value="Hot Lead">Hot Lead - Very likely to enroll</option>
                        <option value="Warm Lead">Warm Lead - Might enroll with some follow-up</option>
                        <option value="Follow Up Required">Follow Up Required - Has specific questions/concerns</option>
                        <option value="Not Interested">Not Interested - Unlikely to enroll</option>
                    </select>
                    <p class="text-xs text-on-surface-variant mt-1">This helps admins determine how to properly follow up with this parent.</p>
                </div>
            @else
                <!-- Regular Class Form -->
                <div>
                    <label class="block font-bold mb-2 text-on-surface">Student's progress today? *</label>
                    <select name="progress" required class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                        <option value="">Select an option</option>
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Average">Average</option>
                        <option value="Needs Work">Needs Work</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold mb-2 text-on-surface">Areas of improvement? *</label>
                    <textarea name="improvement_areas" required rows="3" class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors"></textarea>
                </div>

                <div>
                    <label class="block font-bold mb-2 text-on-surface">General behavior *</label>
                    <select name="behavior" required class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors">
                        <option value="">Select an option</option>
                        <option value="Attentive">Attentive and Engaged</option>
                        <option value="Distracted">Somewhat Distracted</option>
                        <option value="Disruptive">Disruptive</option>
                    </select>
                </div>
            @endif

            <div>
                <label class="block font-bold mb-2 text-on-surface">Additional Notes (Optional)</label>
                <textarea name="notes" rows="4" class="w-full bg-surface-container-low border-2 border-outline-variant p-3 rounded-xl focus:outline-none focus:border-primary focus:bg-white transition-colors" placeholder="Any other observations..."></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-8 border-t border-outline-variant pt-6">
                <a href="{{ route('tutor.classes') }}" class="px-6 py-3 bg-surface-container-low hover:bg-surface-container text-on-surface font-bold rounded-xl transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary-container text-white font-bold rounded-xl transition-colors shadow-sm">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>
@endsection
