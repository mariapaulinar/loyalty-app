{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Improved CRUD Pagination Component
  - Uses select dropdown for page navigation (better for large datasets)
  - Shows results count and per-page selector
  - Prev/Next buttons for quick navigation
--}}

@if ($paginator->hasPages())
    <nav aria-label="Page navigation" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        {{-- Results Info --}}
        <div class="text-sm text-secondary-500 dark:text-secondary-400">
            {{ trans('common.showing_results', [
                'n' => $paginator->firstItem() ? $paginator->firstItem() . ' ' . trans('common._to') . ' ' . $paginator->lastItem() : $paginator->count(),
                'total' => $paginator->total()
            ]) }}
        </div>

        {{-- Pagination Controls --}}
        <div class="flex items-center gap-2">
            {{-- Previous Button --}}
            @if ($paginator->onFirstPage())
                <button disabled
                    class="inline-flex items-center justify-center w-9 h-9 text-secondary-400 bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="inline-flex items-center justify-center w-9 h-9 text-secondary-600 dark:text-secondary-300 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 transition-all shadow-sm">
                    <span class="sr-only">{{ trans('common.previous') }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Page Select Dropdown --}}
            <div class="relative">
                <select onchange="window.location.href = this.value"
                    class="appearance-none bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm rounded-lg pl-3 pr-8 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-pointer shadow-sm transition-all hover:border-secondary-300 dark:hover:border-secondary-600">
                    @foreach (range(1, $paginator->lastPage()) as $page)
                        <option value="{{ $paginator->url($page) }}" {{ $page == $paginator->currentPage() ? 'selected' : '' }}>
                            {{ trans('common.page') }} {{ $page }} {{ trans('common.of') }} {{ $paginator->lastPage() }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            {{-- Next Button --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="inline-flex items-center justify-center w-9 h-9 text-secondary-600 dark:text-secondary-300 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 transition-all shadow-sm">
                    <span class="sr-only">{{ trans('common.next') }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <button disabled
                    class="inline-flex items-center justify-center w-9 h-9 text-secondary-400 bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @endif

            {{-- First/Last Page Quick Jump (shown only if many pages) --}}
            @if ($paginator->lastPage() > 10)
                <div class="hidden sm:flex items-center gap-1 ml-2 pl-2 border-l border-secondary-200 dark:border-secondary-700">
                    @if ($paginator->currentPage() > 2)
                        <a href="{{ $paginator->url(1) }}"
                            class="inline-flex items-center justify-center px-2 h-9 text-xs text-secondary-500 dark:text-secondary-400 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-all shadow-sm"
                            title="{{ trans('common.first_page') }}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                            1
                        </a>
                    @endif
                    @if ($paginator->currentPage() < $paginator->lastPage() - 1)
                        <a href="{{ $paginator->url($paginator->lastPage()) }}"
                            class="inline-flex items-center justify-center px-2 h-9 text-xs text-secondary-500 dark:text-secondary-400 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-all shadow-sm"
                            title="{{ trans('common.last_page') }}">
                            {{ $paginator->lastPage() }}
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </nav>
@endif
