@extends('layouts.app')

@section('title', 'Trial Expired - Younimatiq')

@section('content')
<div class="min-h-screen bg-surface-container-lowest flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden text-center border border-surface-container">
        <div class="bg-primary py-8 text-white">
            <span class="material-symbols-outlined text-6xl mb-2">hourglass_empty</span>
            <h1 class="text-3xl font-headline-lg font-bold">Trial Expired</h1>
        </div>
        <div class="p-5 md:p-8">
            <p class="text-on-surface-variant text-lg mb-6">
                Your 9-day free trial period has ended. We hope you and your child enjoyed the free trial class!
            </p>
            <p class="text-on-surface text-md font-medium mb-8">
                To unlock full access to the student dashboard, interactive learning paths, and more live classes, please purchase a course.
            </p>
            
            <div class="flex flex-col gap-3">
                <a href="{{ route('courses.index') }}" class="btn-primary w-full text-center text-lg py-3 rounded-xl font-bold">
                    Browse Courses
                </a>
                <a href="{{ route('home') }}" class="btn-secondary w-full text-center text-lg py-3 rounded-xl font-bold">
                    Back to Home
                </a>
                <form action="{{ route('logout.student') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="w-full text-center text-lg py-3 rounded-xl font-bold border border-outline text-on-surface hover:bg-surface-container transition-colors">
                        Log Out
                    </button>
                </form>
            </div>
            
            <p class="text-sm text-on-surface-variant mt-8">
                If you believe this is a mistake, please <a href="mailto:support@younimatiq.com" class="text-primary hover:underline">contact support</a>.
            </p>
        </div>
    </div>
</div>
@endsection
