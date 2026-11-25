@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-20">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 max-w-lg w-full text-center">
        <span class="material-symbols-outlined text-primary text-6xl mb-6">mark_email_unread</span>
        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-4">Verify Your Email</h1>
        <p class="text-on-surface-variant mb-8">
            Thanks for signing up! Before you can access your dashboard, please verify your email address by clicking the link we sent you.
        </p>

        

        <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
            @csrf
            <button type="submit" class="btn-primary btn-block">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout.student') }}">
            @csrf
            <button type="submit" class="text-on-surface-variant hover:text-primary text-sm font-medium">Log out</button>
        </form>
    </div>
</div>
@endsection
