@extends('layouts.app')

@section('title', 'Tutor Login')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-secondary/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Login Container -->
    <div class="max-w-4xl w-full flex flex-col md:flex-row bg-white rounded-[2rem] border border-surface-container-high shadow-xl relative z-10 clay-card overflow-hidden">
        
        @include('auth.partials.character-stage')

        <!-- Right Side: Form Section -->
        <div class="w-full md:w-1/2 p-5 md:p-8 lg:p-6 md:p-12 flex flex-col justify-center relative">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-secondary mx-auto rounded-2xl flex items-center justify-center mb-4 transform -rotate-6 hover:rotate-0 transition-transform">
                    <span class="material-symbols-outlined text-white text-3xl">co_present</span>
                </div>
                <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface">Welcome Back, Tutor</h2>
                <p class="text-on-surface-variant font-body-md mt-2">Sign in to manage your courses and students.</p>
            </div>

            <form class="space-y-6" action="{{ url('/login/tutor') }}" method="POST" autocomplete="on">
                @csrf
                
                @if($errors->any())
                    <div class="bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-bold text-on-surface mb-2">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">mail</span>
                            </span>
                            <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" class="appearance-none rounded-xl relative block w-full px-12 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all sm:text-sm font-medium" placeholder="you@example.com" value="{{ old('email') }}">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-on-surface mb-2">Password</label>
                        <div class="relative" x-data="{ show: false }">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">lock</span>
                            </span>
                            <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password" class="appearance-none rounded-xl relative block w-full px-12 py-3 bg-surface-container-low border border-surface-container-high placeholder-on-surface-variant text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all sm:text-sm font-medium" placeholder="••••••••">
                            <button type="button" @click="show = !show; if(window.updateCharState) setTimeout(window.updateCharState, 10);" class="absolute inset-y-0 right-0 flex items-center pr-4 text-on-surface-variant hover:text-primary transition-colors focus:outline-none" aria-label="Toggle password visibility">
                                <span class="material-symbols-outlined text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember" type="checkbox" class="h-4 w-4 text-secondary focus:ring-secondary border-surface-container-high rounded cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-sm text-on-surface font-medium cursor-pointer">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="{{ route('password.request') }}" class="font-bold text-secondary hover:underline transition-colors">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-enroll btn-block">Sign In</button>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-sm text-on-surface-variant font-medium">
                        New Tutor?
                        <a href="{{ route('register.tutor') }}" class="font-bold text-secondary hover:underline transition-colors">
                            Apply Here
                        </a>
                    </p>
                    <p class="text-sm text-on-surface-variant font-medium mt-4 pt-4 border-t border-surface-container-high">
                        Are you a Student? 
                        <a href="{{ route('login.student') }}" class="font-bold text-primary hover:underline transition-colors">
                            Sign In Here
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
