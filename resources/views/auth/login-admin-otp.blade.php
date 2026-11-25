@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-md w-full bg-white rounded-[2rem] border border-surface-container-high shadow-xl relative z-10 clay-card overflow-hidden p-5 md:p-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-primary mx-auto rounded-2xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-white text-3xl">mark_email_read</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface">Verify Admin Login</h2>
            <p class="text-on-surface-variant font-body-md mt-2">
                Enter the 6-digit code sent to
                <span class="font-bold text-on-surface">{{ $email }}</span>
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-primary/10 text-primary p-4 rounded-xl text-sm font-bold">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="space-y-6" action="{{ route('login.admin.otp.verify') }}" method="POST" autocomplete="one-time-code">
            @csrf
            <div>
                <label for="otp" class="block text-sm font-bold text-on-surface mb-2">Verification Code</label>
                <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
                    class="appearance-none rounded-xl relative block w-full px-4 py-3 bg-surface-container-low border border-surface-container-high text-on-surface text-center text-2xl tracking-[0.4em] font-bold focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="••••••" value="{{ old('otp') }}">
            </div>

            <button type="submit" class="btn-primary btn-block">Verify & Continue</button>
        </form>

        <form action="{{ route('login.admin.otp.resend') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="w-full text-sm font-bold text-primary hover:underline">Resend code</button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login.admin') }}" class="text-sm font-medium text-on-surface-variant hover:text-primary">Back to login</a>
        </div>
    </div>
</div>
@endsection
