@props(['paginator'])

@if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <nav class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="Course pagination">
        @if($paginator->onFirstPage())
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-surface-container-high text-on-surface-variant/50 cursor-not-allowed" aria-disabled="true">Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-4 py-2 rounded-full text-sm font-bold bg-white border border-outline-variant text-on-surface hover:bg-primary hover:text-white hover:border-primary transition-colors">Prev</a>
        @endif

        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="min-w-10 h-10 inline-flex items-center justify-center rounded-full text-sm font-bold bg-primary text-white" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="min-w-10 h-10 inline-flex items-center justify-center rounded-full text-sm font-bold bg-white border border-outline-variant text-on-surface hover:bg-primary hover:text-white hover:border-primary transition-colors">{{ $page }}</a>
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-4 py-2 rounded-full text-sm font-bold bg-white border border-outline-variant text-on-surface hover:bg-primary hover:text-white hover:border-primary transition-colors">Next</a>
        @else
            <span class="px-4 py-2 rounded-full text-sm font-bold bg-surface-container-high text-on-surface-variant/50 cursor-not-allowed" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
