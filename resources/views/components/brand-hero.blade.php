@props([
    'eyebrow' => null,
    'centered' => true,
    'compact' => false,
])

<section {{ $attributes->merge(['class' => 'px-4 md:px-8 w-[98%] max-w-[1600px] mx-auto']) }}>
    <div @class([
        'brand-panel',
        'text-center' => $centered,
        'brand-panel--compact' => $compact,
        'p-5 md:p-10 md:p-6 md:p-16' => ! $compact,
        'p-5 md:p-8 lg:p-6 md:p-12' => $compact,
    ])>
        @if($eyebrow)
            <p class="brand-eyebrow">{{ $eyebrow }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
