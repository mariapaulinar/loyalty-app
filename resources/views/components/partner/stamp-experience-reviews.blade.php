{{--
    Partner Experience Reviews Panel

    Shows aggregate ratings and recent member feedback from stamp transactions.
--}}
@props([
    'summary',
    'recentReviews' => collect(),
    'compact' => false,
])

@php
    $average = $summary['average_rating'] ?? null;
    $totalReviews = $summary['total_reviews'] ?? 0;
    $totalEligible = $summary['total_eligible'] ?? 0;
    $responseRate = $summary['response_rate'] ?? 0;
    $distribution = $summary['distribution'] ?? array_fill(1, 5, 0);
    $maxCount = max(1, max($distribution));
@endphp

<div class="{{ $compact ? 'space-y-4' : 'space-y-6' }}">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-secondary-500 dark:text-secondary-400">
                {{ trans('common.stamp_review_average') }}
            </p>
            <div class="mt-2 flex items-end gap-2">
                <span class="text-3xl font-bold text-secondary-900 dark:text-white">
                    {{ $average !== null ? number_format($average, 1) : '—' }}
                </span>
                @if($average !== null)
                    <span class="text-sm text-amber-500 mb-1">/ 5</span>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-secondary-500 dark:text-secondary-400">
                {{ trans('common.stamp_review_total') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-secondary-900 dark:text-white">
                <span class="format-number">{{ $totalReviews }}</span>
            </p>
        </div>

        <div class="rounded-xl border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-secondary-500 dark:text-secondary-400">
                {{ trans('common.stamp_review_response_rate') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-secondary-900 dark:text-white">
                {{ number_format($responseRate, 1) }}%
            </p>
            <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">
                {{ trans('common.stamp_review_eligible_count', ['count' => $totalEligible]) }}
            </p>
        </div>
    </div>

    @if($totalReviews > 0)
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 p-5">
            <h4 class="text-sm font-semibold text-secondary-900 dark:text-white mb-4">
                {{ trans('common.stamp_review_distribution') }}
            </h4>
            <div class="space-y-2">
                @for($star = 5; $star >= 1; $star--)
                    @php $count = $distribution[$star] ?? 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-8 text-xs font-medium text-secondary-500 dark:text-secondary-400 text-right">
                            {{ $star }}★
                        </span>
                        <div class="flex-1 h-2 rounded-full bg-secondary-100 dark:bg-secondary-800 overflow-hidden">
                            <div class="h-full rounded-full bg-amber-400 transition-all duration-500"
                                style="width: {{ round(($count / $maxCount) * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-xs text-secondary-500 dark:text-secondary-400 text-right">
                            {{ $count }}
                        </span>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    @if(!$compact && $recentReviews->isNotEmpty())
        <div class="rounded-xl border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-secondary-100 dark:border-secondary-800">
                <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.stamp_review_recent') }}
                </h4>
            </div>
            <ul class="divide-y divide-secondary-100 dark:divide-secondary-800">
                @foreach($recentReviews as $review)
                    @php
                        $rating = (int) ($review->review['rating'] ?? 0);
                        $submittedAt = $review->review['submitted_at'] ?? null;
                    @endphp
                    <li class="px-5 py-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                {{ $review->member?->name ?? trans('common.member') }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate mt-0.5">
                                {{ $review->stampCard?->name ?? trans('common.stamp_card') }}
                                @if($review->staff?->name)
                                    · {{ $review->staff->name }}
                                @endif
                            </p>
                            @if($submittedAt)
                                <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">
                                    {{ \Carbon\Carbon::parse($submittedAt)->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                        <div class="shrink-0 inline-flex items-center gap-0.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-sm font-semibold">
                            {{ $rating }}/5
                            <x-ui.icon icon="star" class="w-3.5 h-3.5 fill-current" />
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($totalReviews === 0)
        <div class="rounded-xl border border-dashed border-secondary-200 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-900/50 p-6 text-center">
            <x-ui.icon icon="star" class="w-8 h-8 text-secondary-300 dark:text-secondary-600 mx-auto mb-3" />
            <p class="text-sm text-secondary-500 dark:text-secondary-400">
                {{ trans('common.stamp_review_no_data') }}
            </p>
        </div>
    @endif
</div>
