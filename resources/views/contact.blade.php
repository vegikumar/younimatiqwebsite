@extends('layouts.app')

@section('seo_title', 'Contact Younimatiq')
@section('seo_description', 'Contact Younimatiq to learn more about online classes and courses for kids. Get in touch with our team for course details, enrollment, and support.')

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Get in Touch">
    <h1 class="brand-title">Contact <span class="text-brand-accent">Us</span></h1>
    <p class="brand-description">Questions about courses, demos, or enrollment? Send us a message and our team will help.</p>
</x-brand-hero>

<div class="pb-20 px-4 md:px-6 max-w-6xl mx-auto">
    @if(session('success'))
        <div class="mb-8 rounded-2xl border border-tertiary-fixed-dim bg-tertiary-fixed text-tertiary px-5 py-4 font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="mb-8 rounded-2xl border border-error-container bg-error-container text-error px-5 py-4">
            <p class="font-bold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-10">
        {{-- Contact details --}}
        <aside class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-outline-variant shadow-sm p-6 md:p-8 space-y-5">
                <h2 class="text-xl font-bold text-on-surface">Talk to our team</h2>
                <p class="text-on-surface text-sm leading-relaxed">
                    Prefer to reach out directly? Use the options below — we are happy to help families worldwide.
                </p>

                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <svg class="text-primary mt-0.5" fill="currentColor" height="24" viewbox="0 0 24 24" width="24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">USA</p>
                            <a href="https://wa.me/12027944843" target="_blank" class="font-semibold text-on-surface hover:text-primary">+1 (202) 794 4843</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="text-primary mt-0.5" fill="currentColor" height="24" viewbox="0 0 24 24" width="24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Australia</p>
                            <a href="https://wa.me/61470214835" target="_blank" class="font-semibold text-on-surface hover:text-primary">+61 470 214 835</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary mt-0.5">call</span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">India</p>
                            <a href="tel:+918981918383" class="font-semibold text-on-surface hover:text-primary">+91 89819 18383</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary mt-0.5">mail</span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Email</p>
                            <a href="mailto:support@younimatiq.com" class="font-semibold text-on-surface hover:text-primary">support@younimatiq.com</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary mt-0.5">near_me</span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Location</p>
                            <p class="font-semibold text-on-surface">Texas, USA</p>
                        </div>
                    </li>
                </ul>

                <div class="pt-2 flex flex-col gap-3">
                    <a href="https://wa.me/12027944843" target="_blank" rel="noopener" class="btn-primary w-full inline-flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-base">chat</span>
                        Chat on WhatsApp
                    </a>
                    <a href="{{ route('book-free-session') }}" class="w-full inline-flex justify-center items-center gap-2 rounded-xl border border-outline-variant px-4 py-3 font-bold text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-base">event</span>
                        Book a Free Class
                    </a>
                </div>
            </div>
        </aside>

        {{-- Contact form --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-3xl border border-outline-variant shadow-sm p-6 md:p-8">
                <h2 class="text-xl font-bold text-on-surface mb-1">Send a message</h2>
                <p class="text-on-surface text-sm mb-6">Fill out the form and we will get back to you soon.</p>

                <form action="{{ route('contact.store') }}" method="POST" class="space-y-5" autocomplete="on">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-bold text-on-surface mb-2">Full name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                maxlength="255"
                                autocomplete="name"
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="Your name"
                            >
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-bold text-on-surface mb-2">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                maxlength="255"
                                autocomplete="email"
                                inputmode="email"
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="you@email.com"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="phone" class="block text-sm font-bold text-on-surface mb-2">Phone / WhatsApp</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                autocomplete="tel"
                                inputmode="tel"
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="Phone number"
                            >
                            <p id="phone-error" class="hidden mt-2 text-sm text-error font-medium">Please enter a valid phone number with country code.</p>
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-bold text-on-surface mb-2">Subject</label>
                            <select
                                id="subject"
                                name="subject"
                                required
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                            >
                                <option value="">Select a topic</option>
                                <option value="General Inquiry" @selected(old('subject') === 'General Inquiry')>General Inquiry</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}" @selected(old('subject') === $category->name)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-bold text-on-surface mb-2">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            required
                            maxlength="5000"
                            rows="6"
                            class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 resize-y"
                            placeholder="How can we help you and your child?"
                        >{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3">
                        <span class="material-symbols-outlined text-base">send</span>
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@php
    $detectedCountry = strtolower((string) (session('country_code') ?: 'us'));
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<style>
    .iti { width: 100%; }
    .iti__flag-container { z-index: 5; }
    .iti__country-list { color: #1f2937; text-align: left; z-index: 40; }
    #phone.iti-invalid {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.15);
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script>
(function () {
    const phoneInput = document.getElementById('phone');
    const form = phoneInput ? phoneInput.closest('form') : null;
    const phoneError = document.getElementById('phone-error');
    if (!phoneInput || !window.intlTelInput) return;

    const sessionCountry = @json($detectedCountry);
    const iti = window.intlTelInput(phoneInput, {
        initialCountry: 'auto',
        preferredCountries: ['us', 'in', 'au', 'gb', 'ae', 'sg'],
        nationalMode: false,
        formatOnDisplay: true,
        autoPlaceholder: 'aggressive',
        geoIpLookup: function (callback) {
            // Prefer already-detected session country from GeoLocaleService
            if (sessionCountry && sessionCountry.length === 2) {
                callback(sessionCountry);
                return;
            }
            fetch('https://ipapi.co/json')
                .then(res => res.json())
                .then(data => callback((data.country_code || 'us').toLowerCase()))
                .catch(() => callback('us'));
        },
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
    });

    function showPhoneError(show) {
        if (show) {
            phoneInput.classList.add('iti-invalid');
            if (phoneError) phoneError.classList.remove('hidden');
        } else {
            phoneInput.classList.remove('iti-invalid');
            if (phoneError) phoneError.classList.add('hidden');
        }
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!phoneInput.value.trim()) {
                showPhoneError(false);
                return;
            }

            // Wait briefly for utils if needed, then validate / normalize
            const number = iti.getNumber();
            if (window.intlTelInputUtils && !iti.isValidNumber()) {
                e.preventDefault();
                showPhoneError(true);
                phoneInput.focus();
                return;
            }

            showPhoneError(false);
            // Store full E.164 number including country code
            phoneInput.value = number || phoneInput.value;
        });
    }

    phoneInput.addEventListener('input', function () {
        showPhoneError(false);
    });
    phoneInput.addEventListener('countrychange', function () {
        showPhoneError(false);
    });
})();
</script>
@endsection
