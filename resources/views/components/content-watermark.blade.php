@props(['absolute' => false])
@php
    $wmUser = auth()->user() ?? auth('tutor')->user() ?? auth('admin')->user();
@endphp
@if($wmUser)
    @php
        $wmText = trim(($wmUser->name ?? 'User') . ' · ' . ($wmUser->email ?? ''));
        $wmSvg = "<svg xmlns='http://www.w3.org/2000/svg' width='480' height='280'><g transform='rotate(-30 240 140)'><text x='240' y='140' text-anchor='middle' font-family='system-ui, sans-serif' font-size='11' fill='#0f172a' fill-opacity='0.05'>" . e($wmText) . "</text></g></svg>";
        $wmUrl = 'data:image/svg+xml;charset=utf-8,' . rawurlencode($wmSvg);
    @endphp
    <div class="younimatiq-watermark {{ $absolute ? 'is-absolute' : '' }}"
         aria-hidden="true"
         data-clarity-mask="true"
         style="background-image: url('{{ $wmUrl }}');"></div>
@endif
