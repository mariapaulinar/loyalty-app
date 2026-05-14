{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Send Points — Member View

  Purpose:
  Two-state page:
  1. Owner view: Shows the shareable link for the member who created the request.
  2. Sender view: Form for another member to send points to the requester.

  Design principles:
  - Member Action Page pattern: no ambient blobs, focused layout
  - Card container: rounded-2xl, border-only, no shadow (§6.3)
  - Submit button: primary brand solid, standard CTA
  - Alerts: bare icons, neutral containers, no icon backgrounds
  - All strings translated
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.send_points') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">

    {{-- Page Header --}}
    @php
        if ($memberOwnsRequestLink) {
            $breadcrumbs = [
                ['url' => route('member.cards'), 'icon' => 'home', 'title' => trans('common.home')],
                ['url' => route('member.data.list', ['name' => 'request-links']), 'text' => trans('common.request_links')],
                ['text' => trans('common.share_link')]
            ];
            $pageIcon = 'share-2';
            $pageTitle = trans('common.share_link');
        } else {
            if ($pointRequest->card) {
                $breadcrumbs = [
                    ['url' => route('member.cards'), 'icon' => 'home', 'title' => trans('common.home')],
                    ['url' => route('member.cards'), 'text' => trans('common.wallet')],
                    ['url' => route('member.card', ['card_id' => $pointRequest->card->id]), 'text' => $pointRequest->card->head],
                    ['text' => trans('common.send_points')]
                ];
            } else {
                $breadcrumbs = [
                    ['url' => route('member.cards'), 'icon' => 'home', 'title' => trans('common.home')],
                    ['url' => route('member.cards'), 'text' => trans('common.wallet')],
                    ['text' => trans('common.send_points')]
                ];
            }
            $pageIcon = 'send';
            $pageTitle = trans('common.send_points');
        }
    @endphp

    <x-ui.page-header
        :icon="$pageIcon"
        :title="$pageTitle"
        :breadcrumbs="$breadcrumbs"
        compact
    />

    {{-- Card Preview --}}
    @if($pointRequest->card)
        <div class="mt-8">
            <x-member.premium-card
                :card="$pointRequest->card"
                :member="null"
                :flippable="false"
                :links="false"
                :show-qr="false"
            />
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         OWNER VIEW — Share link with others
         ═══════════════════════════════════════════════════════════════ --}}
    @if($memberOwnsRequestLink)

        {{-- Context notice --}}
        <div class="mt-8 bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <x-ui.icon icon="handshake" class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                </div>
                <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                    {{ trans('common.member_owns_link_info') }}
                </p>
            </div>
        </div>

        {{-- Copy Link Card --}}
        <div class="mt-4 bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                <div class="flex items-center gap-3">
                    <x-ui.icon icon="link" class="w-5 h-5 text-secondary-400" />
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.share_link') }}
                    </h3>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-6">
                <div class="grid grid-cols-8 gap-3 w-full">
                    <label for="request_link" class="sr-only">{{ trans('common.link') }}</label>
                    <input
                        id="request_link"
                        type="text"
                        class="col-span-6 bg-secondary-50 dark:bg-secondary-800
                               border border-secondary-200 dark:border-secondary-700
                               text-secondary-600 dark:text-secondary-400
                               text-sm rounded-xl
                               focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                               block w-full p-3 transition-all duration-200"
                        value="{{ $requestLink }}"
                        disabled
                        readonly
                    >
                    <button
                        type="button"
                        data-copy-target="request_link"
                        class="col-span-2 flex items-center justify-center gap-1.5
                               text-sm font-semibold text-white
                               bg-primary-600 hover:bg-primary-500
                               rounded-xl shadow-sm hover:shadow-md
                               transition-all duration-200 active:scale-[0.98]">
                        <span id="default-message">{{ trans('common.copy') }}</span>
                        <span id="success-message" class="hidden items-center gap-1">
                            <x-ui.icon icon="check" class="w-3.5 h-3.5" />
                            {{ trans('common.copied') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Copy Script --}}
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const targetInput = document.getElementById('request_link');
            const copyButton = document.querySelector('[data-copy-target="request_link"]');
            const defaultMessage = document.getElementById('default-message');
            const successMessage = document.getElementById('success-message');

            if (!copyButton || !targetInput) return;

            copyButton.addEventListener('click', function () {
                try {
                    const isDisabled = targetInput.disabled;
                    if (isDisabled) targetInput.disabled = false;

                    targetInput.select();
                    targetInput.setSelectionRange(0, 99999);

                    const successful = document.execCommand('copy');

                    window.getSelection().removeAllRanges();
                    targetInput.setSelectionRange(0, 0);
                    targetInput.blur();

                    if (isDisabled) targetInput.disabled = true;

                    if (successful) {
                        defaultMessage.classList.add('hidden');
                        successMessage.classList.remove('hidden');
                        successMessage.classList.add('inline-flex');

                        setTimeout(() => {
                            defaultMessage.classList.remove('hidden');
                            successMessage.classList.add('hidden');
                            successMessage.classList.remove('inline-flex');
                        }, 2000);
                    }
                } catch (err) {
                    console.error('Failed to copy text: ', err);
                }
            });
        });
        </script>

    {{-- ═══════════════════════════════════════════════════════════════
         SENDER VIEW — Send points to requester
         ═══════════════════════════════════════════════════════════════ --}}
    @else

        {{-- Context notice --}}
        <div class="mt-8 bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <x-ui.icon icon="handshake" class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                </div>
                <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                    {{ trans('common.send_points_request_info', ['memberName' => $pointRequest->member->name]) }}
                </p>
            </div>
        </div>

        {{-- Flash Messages --}}
        <div class="mt-4">
            <x-forms.messages />
        </div>

        @if(!$memberHasPoints)
            {{-- No Points Warning --}}
            <div class="mt-4 bg-white dark:bg-secondary-900 rounded-xl border border-amber-200 dark:border-amber-800/50 p-4">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <x-ui.icon icon="triangle-alert" class="w-4 h-4 text-amber-500 dark:text-amber-400" />
                    </div>
                    <p class="text-sm text-amber-700 dark:text-amber-300 leading-relaxed">
                        {{ trans('common.member_has_no_points_to_send_msg') }}
                    </p>
                </div>
            </div>
        @else
            {{-- Points Balance Script --}}
            <script>
            const cardBalances = @json($cardBalances);

            window.addEventListener('load', function () {
                const pointsInput = document.getElementById('points');
                const cardSelect = document.querySelector('select[name="card_id"]');
                const hiddenCardInput = document.querySelector('input[type="hidden"][name="card_id"]');

                function updatePointsInput(cardId) {
                    const maxPoints = cardBalances[cardId];
                    if (maxPoints !== undefined) {
                        pointsInput.setAttribute('max', maxPoints);
                        pointsInput.placeholder = "{{ trans('common.send_points_placeholder') }} (1 - " + window.appFormatNumber(maxPoints) + ")";
                        if (parseInt(pointsInput.value) > maxPoints) {
                            pointsInput.value = maxPoints;
                        }
                    }
                }

                if (cardSelect) {
                    cardSelect.addEventListener('change', function () {
                        updatePointsInput(this.value);
                    });
                    cardSelect.dispatchEvent(new Event('change'));
                } else if (hiddenCardInput) {
                    updatePointsInput(hiddenCardInput.value);
                }
            });
            </script>

            {{-- Send Points Form --}}
            <div class="mt-4 bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">

                {{-- Form Header --}}
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-400" />
                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.send_points') }}
                        </h3>
                    </div>
                </div>

                {{-- Form Content --}}
                <div class="p-6">
                    <x-forms.form-open
                        action="{{ route('member.request.points.send.post', ['request_identifier' => $pointRequest->unique_identifier]) }}"
                        method="POST"
                    />
                    @csrf

                    <div class="space-y-5 mb-6">
                        @if(count($memberCardsOptions) > 1)
                            <x-forms.select
                                name="card_id"
                                :label="trans('common.select_card')"
                                :options="$memberCardsOptions"
                                required="true"
                            />
                        @else
                            <input type="hidden" name="card_id" value="{{ key($memberCardsOptions) }}">
                        @endif

                        <x-forms.input
                            name="points"
                            :label="trans('common.points')"
                            type="number"
                            inputmode="numeric"
                            icon="coins"
                            min="1"
                            step="1"
                            placeholder="{{ trans('common.send_points_placeholder') }}"
                            required
                        />

                        <div class="flex items-start p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl border border-secondary-100 dark:border-secondary-700/50">
                            <x-forms.checkbox
                                name="confirm"
                                :checked="false"
                                :label="trans('common.confirm_send_points')"
                            />
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" disabled id="submitButton"
                        class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                               text-sm font-semibold text-white
                               bg-primary-600 hover:bg-primary-500
                               rounded-xl shadow-sm hover:shadow-md
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               transition-all duration-200 active:scale-[0.98]
                               disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                        <x-ui.icon icon="send" class="w-4.5 h-4.5" />
                        {{ trans('common.send_points') }}
                    </button>

                    {{-- Submit Validation --}}
                    <script>
                    window.addEventListener('load', function () {
                        const submitButton = document.getElementById('submitButton');
                        const pointsInput = document.getElementById('points');
                        const confirmCheckbox = document.querySelector('input[type="checkbox"][id^="confirm"]')
                            || document.querySelector('input[name="confirm"][type="checkbox"]');

                        function updateSubmitState() {
                            if (!submitButton || !pointsInput || !confirmCheckbox) return;

                            const pointsValue = parseInt(pointsInput.value, 10);
                            const maxPoints = parseInt(pointsInput.getAttribute('max'), 10) || Infinity;
                            const isConfirmed = confirmCheckbox.checked;
                            const hasValidPoints = !isNaN(pointsValue) && pointsValue >= 1 && pointsValue <= maxPoints;

                            submitButton.disabled = !(isConfirmed && hasValidPoints);
                        }

                        if (confirmCheckbox) {
                            confirmCheckbox.addEventListener('change', updateSubmitState);
                        }
                        if (pointsInput) {
                            pointsInput.addEventListener('input', updateSubmitState);
                        }

                        updateSubmitState();
                    });
                    </script>

                    <x-forms.form-close />
                </div>
            </div>
        @endif
    @endif
</div>
@stop