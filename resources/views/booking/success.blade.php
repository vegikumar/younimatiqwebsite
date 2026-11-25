@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-surface">
    <div class="max-w-md w-full space-y-8 bg-white p-5 md:p-10 rounded-[2rem] shadow-xl animate-in fade-in zoom-in duration-500 border border-outline-variant/20 text-center">
        
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-secondary-container text-on-secondary-container mb-6 shadow-inner">
            <span class="material-symbols-outlined text-[48px]">check_circle</span>
        </div>
        
        @php
            $isLegacyConsultation = session('consultation_success') || \Illuminate\Support\Str::startsWith($booking->camp_name, 'Consultation');
            $courseLabel = str_replace(['Free Trial - ', 'Consultation - ', 'Demo Class - '], '', $booking->camp_name);
        @endphp

        @if($isLegacyConsultation)
            <h2 class="text-3xl font-extrabold text-on-surface">Thank You!</h2>
            <p class="mt-3 text-lg font-bold text-on-surface">Your Free Consultation is Confirmed!</p>
            <p class="mt-4 text-on-surface-variant font-body-lg">
                Your consultation has been scheduled for your selected date and time. Click the consultation link below to join at the scheduled time.
            </p>
            @php
                $consultLink = $booking->zoom_join_url ?: url('/student/dashboard');
                $consultTz = $booking->parent_timezone ?: 'IST';
                $consultTimeStr = $booking->scheduled_at ? \Carbon\Carbon::parse($booking->scheduled_at)->timezone($consultTz)->format('l, F j, Y 	 g:i A T') : null;
            @endphp
            @if($consultTimeStr)
                <p class="mt-3 text-on-surface-variant"><strong>Date & Time:</strong> {{ $consultTimeStr }}</p>
            @endif
            <div class="mt-6">
                <a href="{{ $consultLink }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-white"
                   style="background:#2D8CFF;">
                    Join Zoom Meeting
                </a>
                <p class="mt-3 text-xs text-on-surface-variant break-all">{{ $consultLink }}</p>
            </div>
            <p class="mt-4 text-sm text-on-surface-variant">
                A confirmation email with your booking details and consultation link has also been sent to your registered email address.
            </p>
            <p class="mt-2 text-on-surface-variant">We look forward to meeting you!</p>

            @if(session('temp_password'))
            <div class="mt-6 bg-surface-container-low p-4 rounded-xl border border-outline-variant/30 text-left">
                <h3 class="font-bold text-on-surface mb-2">Student Account Details</h3>
                <p class="text-sm text-on-surface-variant mb-1"><strong>Login URL:</strong> <a href="{{ route('login.student') }}" class="text-primary hover:underline">{{ route('login.student') }}</a></p>
                <p class="text-sm text-on-surface-variant mb-1"><strong>Email:</strong> {{ $booking->email }}</p>
                <p class="text-sm text-on-surface-variant"><strong>Temporary Password:</strong> {{ session('temp_password') }}</p>
            </div>
            @endif
            
            <div class="mt-8">
                <a href="{{ route('home') }}" class="btn-primary w-full inline-flex justify-center items-center gap-2">
                    Return to Home <span class="material-symbols-outlined">home</span>
                </a>
            </div>
        @else
            @if($booking->amount > 0)
                <h2 class="text-3xl font-extrabold text-on-surface">Payment Successful!</h2>
            @else
                <h2 class="text-3xl font-extrabold text-on-surface">Class Booked!</h2>
            @endif
            <p class="mt-2 text-on-surface-variant font-body-lg">
                Your free trial class for <strong>{{ $courseLabel }}</strong> has been confirmed.
            </p>

            @if($booking->zoom_join_url)
            <div class="mt-6">
                <a href="{{ $booking->zoom_join_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-white"
                   style="background:#2D8CFF;">
                    Join Zoom Meeting
                </a>
            </div>
            @endif

            <div class="bg-surface-container-low p-6 rounded-2xl text-left space-y-4 my-8">
                <h3 class="font-bold text-on-surface border-b border-outline-variant/20 pb-2">Booking Details</h3>
                
                <div class="flex justify-between text-sm">
                    <span class="text-on-surface-variant">Student Name</span>
                    <span class="font-bold text-on-surface">{{ $booking->child_name ?? $booking->parent_name }}</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-on-surface-variant">Course</span>
                    <span class="font-bold text-on-surface text-right">{{ $courseLabel }}</span>
                </div>
                
                @if($booking->amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-on-surface-variant">Payment Method</span>
                    <span class="font-bold text-on-surface">{{ ucfirst($booking->payment_method ?? 'Credit Card') }}</span>
                </div>
                @endif

                <div class="flex justify-between text-sm">
                    <span class="text-on-surface-variant">Booked On</span>
                    <span class="font-bold text-on-surface text-right"><x-local-time :date="$booking->created_at" format="datetime" /></span>
                </div>

                @if($booking->scheduled_at)
                <div class="flex justify-between text-sm mt-3">
                    <span class="text-on-surface-variant">Class Timing</span>
                    <span class="font-bold text-on-surface text-right" id="scheduled-timing">
                        <x-local-time :date="$booking->scheduled_at" format="datetime" />
                    </span>
                </div>
                @endif

                @if($booking->amount > 0)
                <div class="flex justify-between text-lg pt-4 border-t border-outline-variant/20">
                    <span class="font-bold text-on-surface-variant">Amount Paid</span>
                    <span class="font-extrabold text-primary">{{ \App\Services\CurrencyService::formatLocal($booking->amount, $booking->currency) }}</span>
                </div>
                @else
                <div class="flex justify-between text-lg pt-4 border-t border-outline-variant/20">
                    <span class="font-bold text-on-surface-variant">Amount Paid</span>
                    <span class="font-extrabold text-on-tertiary-container">Free</span>
                </div>
                @endif
            </div>

            <p class="text-sm text-on-surface-variant mb-6">
                We've sent a confirmation email to <strong>{{ $booking->email }}</strong> with all the details.
            </p>

            @if(session('temp_password'))
            <div class="mt-2 mb-8 bg-surface-container-low p-4 rounded-xl border border-outline-variant/30 text-left">
                <h3 class="font-bold text-on-surface mb-2">Student Account Details</h3>
                <p class="text-sm text-on-surface-variant mb-1"><strong>Login URL:</strong> <a href="{{ route('login.student') }}" class="text-primary hover:underline">{{ route('login.student') }}</a></p>
                <p class="text-sm text-on-surface-variant mb-1"><strong>Email:</strong> {{ $booking->email }}</p>
                <p class="text-sm text-on-surface-variant mb-3"><strong>Temporary Password:</strong> <span class="font-mono font-bold text-on-surface">{{ session('temp_password') }}</span></p>
                <p class="text-xs text-on-surface-variant">After your class, trial dashboard access stays open for 9 days.</p>
            </div>
            @else
            <a href="{{ route('register.student', ['email' => $booking->email, 'name' => $booking->child_name ?? $booking->parent_name]) }}" class="btn-primary w-full inline-flex justify-center items-center gap-2 mb-4">
                Create Student Account <span class="material-symbols-outlined">arrow_forward</span>
            </a>
            @endif

            <div class="mt-4 flex flex-col gap-3">
                <a href="{{ route('login.student') }}" class="btn-primary w-full inline-flex justify-center items-center gap-2">
                    Go to Student Login <span class="material-symbols-outlined">login</span>
                </a>
                <a href="{{ route('home') }}" class="text-sm text-on-surface-variant hover:text-primary">
                    Return to Home
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
