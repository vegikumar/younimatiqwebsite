@props(['date', 'format' => 'datetime', 'class' => ''])

@if($date)
    @php 
        $carbonDate = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        $utcIso = $carbonDate->utc()->toIso8601String();
        
        $fallback = '';
        if ($format === 'date') {
            $fallback = $carbonDate->format('M d, Y');
        } elseif ($format === 'time') {
            $fallback = $carbonDate->format('g:i A');
        } elseif ($format === 'datetime-short') {
            $fallback = $carbonDate->format('M d, g:i A');
        } else {
            $fallback = $carbonDate->format('M d, Y g:i A');
        }
    @endphp
    <span class="dynamic-local-time {{ $class }}" data-utc="{{ $utcIso }}" data-format="{{ $format }}">
        {{ $fallback }}
    </span>
@endif
