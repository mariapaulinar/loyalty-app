{{--
    Stamp Experience Review Modal

    Shown via WebSocket when staff awards a stamp to the member.
    Rating is stored in stamp_transactions.review as JSON.
--}}
@auth('member')
@if(Route::has('member.stamp.review'))
<div
    x-data="stampReviewModal({
        memberId: @js(auth('member')->id()),
        submitUrlTemplate: @js(route('member.stamp.review', ['transactionId' => '__TRANSACTION_ID__'])),
        reverbConfig: @js(config('broadcasting.connections.reverb.client'))
    })"
    x-cloak>

    <div x-show="show" style="display: none;"
        class="fixed inset-0 z-[70] flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="stamp-review-title">

        <div class="theme-surface-lock bg-white dark:bg-secondary-900 w-full max-w-sm rounded-3xl p-8 shadow-2xl relative transform transition-all"
            @click.away="closeModal()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-90 translate-y-4">

            <button @click="closeModal()"
                type="button"
                class="absolute top-4 right-4 p-2 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
                :aria-label="@js(trans('common.close'))">
                <x-ui.icon icon="x" class="w-6 h-6 text-secondary-500" />
            </button>

            <div class="text-center space-y-6">
                <div>
                    <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
                        <x-ui.icon icon="star" class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 id="stamp-review-title" class="text-2xl font-bold text-secondary-900 dark:text-white">
                        {{ trans('common.stamp_review_title') }}
                    </h3>
                    <p class="text-secondary-500 dark:text-secondary-400 mt-2">
                        {{ trans('common.stamp_review_subtitle') }}
                    </p>
                    <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mt-3" x-text="stampCardTitle"></p>
                    <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1"
                        x-show="stampsRequired > 0"
                        x-text="`${currentStamps} / ${stampsRequired} {{ trans('common.stamps') }}`">
                    </p>
                </div>

                <div class="flex items-center justify-center gap-2" role="radiogroup" aria-label="{{ trans('common.stamp_review_rating_label') }}">
                    <template x-for="star in 5" :key="star">
                        <button
                            type="button"
                            @click="setRating(star)"
                            @mouseenter="hoverRating = star"
                            @mouseleave="hoverRating = 0"
                            class="p-1 rounded-lg transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            :aria-label="`${star} {{ trans('common.stamp_review_stars') }}`"
                            :aria-pressed="rating === star ? 'true' : 'false'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                class="w-10 h-10 transition-colors"
                                :class="(hoverRating >= star || rating >= star)
                                    ? 'text-amber-400 fill-amber-400'
                                    : 'text-secondary-300 dark:text-secondary-600 fill-transparent'"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        </button>
                    </template>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        type="button"
                        @click="submitReview()"
                        :disabled="!rating || isSubmitting"
                        class="w-full py-3.5 px-6 rounded-2xl font-semibold text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!isSubmitting">{{ trans('common.stamp_review_submit') }}</span>
                        <span x-show="isSubmitting">{{ trans('common.stamp_review_submitting') }}</span>
                    </button>
                    <button
                        type="button"
                        @click="closeModal()"
                        class="w-full py-2 text-sm text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300 transition-colors">
                        {{ trans('common.stamp_review_skip') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endauth
