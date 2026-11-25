@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-xl clay-card">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-white text-3xl">lock_reset</span>
            </div>
            <h2 class="font-headline-lg font-bold text-on-surface">Forgot Password?</h2>
            <p class="text-on-surface-variant mt-2">Enter your email and we'll send you a reset link.</p>
        </div>

        

        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6" autocomplete="on">
            @csrf
            <div>
                <label for="email" class="block text-sm font-bold text-on-surface mb-2">Email Address</label>
                <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" value="{{ old('email') }}" class="w-full px-4 py-3 rounded-xl bg-surface-container-low border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary" placeholder="you@example.com">
            </div>
            <button type="submit" class="btn-primary btn-block">Send Reset Link</button>
        </form>

        <p class="mt-6 text-center text-sm text-on-surface-variant">
            Remember your password? <a href="{{ route('login.student') }}" class="text-primary font-bold hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
