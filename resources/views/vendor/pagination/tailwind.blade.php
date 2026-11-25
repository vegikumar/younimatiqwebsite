@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex gap-2 items-center justify-between sm:hidden">

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

        </div>

        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">

            <div>
                <p class="text-sm text-on-surface leading-5 dark:text-on-surface">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse shadow-sm rounded-md">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-on-surface-variant bg-white border border-outline cursor-not-allowed rounded-l-md leading-5 dark:bg-outline dark:border-outline dark:text-outline" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2 py-2 text-sm font-medium text-on-surface-variant bg-white border border-outline rounded-l-md leading-5 hover:text-outline focus:outline-none focus:ring ring-outline focus:border-secondary-container active:bg-surface-container-low active:text-on-surface-variant transition ease-in-out duration-150 dark:bg-on-surface-variant dark:border-outline dark:active:bg-outline dark:focus:border-secondary dark:text-outline dark:hover:bg-on-surface-variant dark:hover:text-outline" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-on-surface bg-white border border-outline cursor-default leading-5 dark:bg-on-surface-variant dark:border-outline dark:text-outline">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-on-surface bg-surface-container border border-outline cursor-default leading-5 dark:bg-outline dark:border-outline dark:text-outline">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-on-surface bg-white border border-outline leading-5 hover:text-on-surface focus:outline-none focus:ring ring-outline focus:border-secondary-container active:bg-surface-container-low active:text-on-surface transition ease-in-out duration-150 dark:bg-on-surface-variant dark:border-outline dark:text-outline dark:hover:text-outline dark:active:bg-outline dark:focus:border-secondary hover:bg-surface-container-low dark:hover:bg-on-surface-variant" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-on-surface-variant bg-white border border-outline rounded-r-md leading-5 hover:text-outline focus:outline-none focus:ring ring-outline focus:border-secondary-container active:bg-surface-container-low active:text-on-surface-variant transition ease-in-out duration-150 dark:bg-on-surface-variant dark:border-outline dark:active:bg-outline dark:focus:border-secondary dark:text-outline dark:hover:bg-on-surface-variant dark:hover:text-outline" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-on-surface-variant bg-white border border-outline cursor-not-allowed rounded-r-md leading-5 dark:bg-outline dark:border-outline dark:text-outline" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
