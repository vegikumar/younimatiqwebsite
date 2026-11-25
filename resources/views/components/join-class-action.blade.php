@props([
    'url' => null,
    'provider' => null,
    'roomId' => null,
    'passcode' => null,
    'buttonClass' => 'btn-primary btn-sm !bg-success hover:!bg-success mt-1',
    'icon' => false,
])
@php
    $joinUrl = $url;
    $hostPresent = true;
    if (($provider ?? null) === 'meet' && $roomId) {
        $status = app(\App\Services\MeetService::class)->roomHostPresent($roomId);
        if ($status === false) {
            $hostPresent = false;
        }
    }
@endphp
@if($joinUrl)
    <div {{ $attributes->class(['flex flex-col gap-1']) }}>
        @if($hostPresent)
            <a href="{{ $joinUrl }}" target="_blank" class="{{ $buttonClass }}">
                @if($icon)
                    <span class="material-symbols-outlined text-sm">language</span>
                @endif
                Join Class
            </a>
        @else
            <span class="text-xs text-warning font-bold bg-warning-container border border-warning-container px-2 py-1 rounded-lg">Waiting for teacher…</span>
        @endif
        @if($passcode)
            <div class="text-[10px] text-on-surface-variant font-bold bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant mt-1">Passcode: {{ $passcode }}</div>
        @endif
    </div>
@endif
