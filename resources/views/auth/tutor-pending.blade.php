@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-20">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 max-w-lg w-full text-center">
        <span class="material-symbols-outlined text-secondary text-6xl mb-6">hourglass_top</span>
        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-4">Application Under Review</h1>
        <p class="text-on-surface-variant mb-8">
            Thank you for applying to teach at Younimatiq. Our team is reviewing your application and will notify you by email once your account is approved.
        </p>
        <form method="POST" action="{{ route('logout.tutor') }}">
            @csrf
            <button type="submit" class="text-on-surface-variant hover:text-primary text-sm font-medium">Log out</button>
        </form>
    </div>
</div>
@endsection
