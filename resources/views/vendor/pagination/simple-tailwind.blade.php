@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-white border border-outline cursor-not-allowed leading-5 rounded-md dark:text-outline dark:bg-outline dark:border-outline">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-white border border-outline leading-5 rounded-md hover:text-on-surface focus:outline-none focus:ring ring-outline focus:border-secondary-container active:bg-surface-container-low active:text-on-surface transition ease-in-out duration-150 dark:bg-on-surface-variant dark:border-outline dark:text-outline-variant dark:focus:border-secondary dark:active:bg-outline dark:active:text-outline hover:bg-surface-container-low dark:hover:bg-on-surface-variant dark:hover:text-outline-variant">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-white border border-outline leading-5 rounded-md hover:text-on-surface focus:outline-none focus:ring ring-outline focus:border-secondary-container active:bg-surface-container-low active:text-on-surface transition ease-in-out duration-150 dark:bg-on-surface-variant dark:border-outline dark:text-outline-variant dark:focus:border-secondary dark:active:bg-outline dark:active:text-outline hover:bg-surface-container-low dark:hover:bg-on-surface-variant dark:hover:text-outline-variant">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-white border border-outline cursor-not-allowed leading-5 rounded-md dark:text-outline dark:bg-outline dark:border-outline">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
