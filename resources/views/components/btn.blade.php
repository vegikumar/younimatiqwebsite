@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
])

@php
    $base = match ($variant) {
        'enroll' => 'btn-enroll',
        'whatsapp' => 'btn-whatsapp-light',
        'outline' => 'btn-outline-primary',
        'outline-light' => 'btn-outline-light',
        default => 'btn-primary',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
