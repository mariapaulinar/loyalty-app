{{--
    Partner Billing Page — Plan Overview & Subscription Management
    Adapts to manual (plan info + limits) and Stripe (+ status + portal) modes.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.billing_my_plan') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
@php
    $entitlements = app(\App\Services\EntitlementService::class);
    $currentPlanKey = $summary['plan']['key'];
    $status = $summary['status'];
    $isRestricted = $summary['is_restricted'];

    // Status display config
    $statusConfig = match($status) {
        'active' => ['label' => trans('common.billing_status_active'), 'color' => 'emerald', 'icon' => 'check-circle'],
        'trialing' => ['label' => trans('common.billing_status_trialing'), 'color' => 'blue', 'icon' => 'clock'],
        'past_due' => ['label' => trans('common.billing_status_past_due'), 'color' => 'amber', 'icon' => 'alert-triangle'],
        'cancelled' => ['label' => trans('common.billing_status_cancelled'), 'color' => 'red', 'icon' => 'x-circle'],
        'incomplete' => ['label' => trans('common.billing_status_incomplete'), 'color' => 'amber', 'icon' => 'alert-circle'],
        'suspended' => ['label' => trans('common.billing_status_suspended'), 'color' => 'red', 'icon' => 'ban'],
        'legacy' => ['label' => trans('common.billing_status_legacy'), 'color' => 'secondary', 'icon' => 'shield'],
        default => ['label' => trans('common.billing_status_manual'), 'color' => 'secondary', 'icon' => 'wallet'],
    };
@endphp

<div class="min-h-screen relative">
    <div class="w-full max-w-5xl mx-auto px-6 py-8 md:px-10 md:py-14 lg:py-16">

        {{-- Header --}}
        <header class="mb-10 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white tracking-tight">
                        {{ trans('common.billing_my_plan') }}
                    </h1>
                    <p class="text-secondary-400 dark:text-secondary-500 mt-1">
                        {{ trans('common.billing_page_subtitle') }}
                    </p>
                </div>
                @if($billingEnabled && $portalUrl)
                    <a href="{{ $portalUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-medium text-sm
                            bg-secondary-900 dark:bg-white text-white dark:text-secondary-900
                            hover:bg-secondary-800 dark:hover:bg-secondary-100
                            transition-colors duration-200 self-start">
                        <x-ui.icon icon="credit-card" class="w-4 h-4" />
                        {{ trans('common.billing_manage_billing') }}
                        <x-ui.icon icon="external-link" class="w-3.5 h-3.5 opacity-50" />
                    </a>
                @endif
            </div>
        </header>

        {{-- Status Banner (restricted states only) --}}
        @if($isRestricted)
        <div class="mb-8 animate-fade-in" style="animation-delay: 40ms;">
            @php
                $bannerColor = in_array($status, ['past_due', 'incomplete']) ? 'amber' : 'red';
                $bannerMessage = match($status) {
                    'past_due' => trans('common.billing_past_due_notice'),
                    'cancelled' => trans('common.billing_cancelled_notice'),
                    'incomplete' => trans('common.billing_incomplete_notice'),
                    'suspended' => trans('common.billing_suspended_notice'),
                    default => '',
                };
            @endphp
            <div class="flex items-start gap-4 p-5 rounded-xl border
                {{ $bannerColor === 'amber' ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20' : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20' }}">
                <x-ui.icon :icon="$statusConfig['icon']"
                    class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $bannerColor === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400' }}" />
                <div class="flex-1">
                    <p class="text-sm font-semibold {{ $bannerColor === 'amber' ? 'text-amber-900 dark:text-amber-200' : 'text-red-900 dark:text-red-200' }}">
                        {{ $statusConfig['label'] }}
                    </p>
                    <p class="text-sm mt-1 {{ $bannerColor === 'amber' ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ $bannerMessage }}
                    </p>
                    @if($portalUrl)
                        <a href="{{ $portalUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 mt-3 text-sm font-medium
                            {{ $bannerColor === 'amber' ? 'text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100' : 'text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100' }}
                            transition-colors">
                            {{ trans('common.billing_update_payment') }}
                            <x-ui.icon icon="external-link" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Trial Banner --}}
        @if($status === 'trialing')
        <div class="mb-8 animate-fade-in" style="animation-delay: 40ms;">
            <div class="flex items-start gap-4 p-5 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
                <x-ui.icon icon="clock" class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" />
                <div>
                    <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ trans('common.billing_status_trialing') }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">{{ trans('common.billing_trial_notice') }}</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Plan Card --}}
        <section class="mb-10 animate-fade-in" style="animation-delay: 80ms;">
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="p-8">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h2 class="text-2xl font-bold text-secondary-900 dark:text-white">
                                    {{ $summary['plan']['name'] }}
                                </h2>
                                @if($billingEnabled)
                                <span @class([
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold',
                                    'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' => $statusConfig['color'] === 'emerald',
                                    'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400' => $statusConfig['color'] === 'blue',
                                    'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400' => $statusConfig['color'] === 'amber',
                                    'bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-400' => $statusConfig['color'] === 'red',
                                    'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400' => $statusConfig['color'] === 'secondary',
                                ])>
                                    <x-ui.icon :icon="$statusConfig['icon']" class="w-3 h-3" />
                                    {{ $statusConfig['label'] }}
                                </span>
                                @endif
                            </div>
                            @if($summary['plan']['description'])
                                <p class="text-secondary-500 dark:text-secondary-400">{{ $summary['plan']['description'] }}</p>
                            @endif
                        </div>
                        @if(!$billingEnabled)
                            <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 text-sm text-secondary-500 dark:text-secondary-400">
                                <x-ui.icon icon="wallet" class="w-4 h-4" />
                                {{ trans('common.billing_manual_mode') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="mb-10 animate-fade-in" style="animation-delay: 160ms;">
            <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-5">{{ trans('common.billing_plan_features') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($summary['features'] as $featureKey => $enabled)
                    @php
                        $featureNames = [
                            'loyalty_cards' => trans('common.loyalty_cards'),
                            'stamp_cards' => trans('common.stamp_cards'),
                            'vouchers' => trans('common.vouchers'),
                            'voucher_batches' => trans('common.batches'),
                            'email_campaigns' => trans('common.email_campaigns'),
                            'activity_log' => trans('common.activity_logs'),
                            'agent_api' => trans('agent.agent_keys'),
                        ];
                        $featureIcons = [
                            'loyalty_cards' => 'credit-card',
                            'stamp_cards' => 'stamp',
                            'vouchers' => 'ticket',
                            'voucher_batches' => 'layers',
                            'email_campaigns' => 'mail',
                            'activity_log' => 'activity',
                            'agent_api' => 'bot',
                        ];
                    @endphp
                    <div class="flex items-center gap-3 p-4 bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800">
                        <div @class([
                            'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                            'bg-emerald-100 dark:bg-emerald-500/15' => $enabled,
                            'bg-secondary-100 dark:bg-secondary-800' => !$enabled,
                        ])>
                            <x-ui.icon :icon="$featureIcons[$featureKey] ?? 'circle'" @class([
                                'w-4 h-4',
                                'text-emerald-600 dark:text-emerald-400' => $enabled,
                                'text-secondary-400 dark:text-secondary-600' => !$enabled,
                            ]) />
                        </div>
                        <span @class([
                            'text-sm font-medium',
                            'text-secondary-900 dark:text-white' => $enabled,
                            'text-secondary-400 dark:text-secondary-600 line-through' => !$enabled,
                        ])>
                            {{ $featureNames[$featureKey] ?? $featureKey }}
                        </span>
                        @if($enabled)
                            <x-ui.icon icon="check" class="w-4 h-4 text-emerald-500 ml-auto flex-shrink-0" />
                        @else
                            <x-ui.icon icon="x" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 ml-auto flex-shrink-0" />
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Resource Usage --}}
        @php
            $activeLimits = collect($summary['limits'])->filter(fn($data) => !$data['unlimited']);
            $limitLabels = [
                'cards' => trans('common.loyalty_cards'),
                'stamp_cards' => trans('common.stamp_cards'),
                'vouchers' => trans('common.vouchers'),
                'staff' => trans('common.staff_members'),
                'rewards' => trans('common.rewards'),
                'clubs' => trans('common.clubs'),
                'agent_keys' => trans('agent.agent_keys'),
            ];
            $limitIcons = [
                'cards' => 'credit-card', 'stamp_cards' => 'stamp', 'vouchers' => 'ticket',
                'staff' => 'briefcase', 'rewards' => 'gift', 'clubs' => 'layers', 'agent_keys' => 'bot',
            ];
            $limitColors = [
                'cards' => 'primary', 'stamp_cards' => 'emerald', 'vouchers' => 'purple',
                'staff' => 'amber', 'rewards' => 'pink', 'clubs' => 'blue', 'agent_keys' => 'violet',
            ];
        @endphp

        @if($activeLimits->isNotEmpty())
        <section class="mb-10 animate-fade-in" style="animation-delay: 240ms;">
            <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-5">{{ trans('common.billing_resource_usage') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($activeLimits as $key => $data)
                    @php
                        $percentage = $data['limit'] > 0 ? min(100, round(($data['usage'] / $data['limit']) * 100)) : 0;
                        $isAtLimit = $data['exceeded'];
                        $colorKey = $limitColors[$key] ?? 'primary';

                        $barColor = $isAtLimit ? 'bg-red-500' : match($colorKey) {
                            'emerald' => 'bg-emerald-500', 'purple' => 'bg-purple-500',
                            'pink' => 'bg-pink-500', 'amber' => 'bg-amber-500',
                            'blue' => 'bg-blue-500', 'violet' => 'bg-violet-500',
                            default => 'bg-primary-500',
                        };
                    @endphp
                    <div class="bg-white dark:bg-secondary-900 rounded-xl p-5 border border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <x-ui.icon :icon="$limitIcons[$key] ?? 'circle'" class="w-4 h-4 text-secondary-400 dark:text-secondary-500" />
                                <span class="text-sm font-semibold text-secondary-900 dark:text-white">{{ $limitLabels[$key] ?? $key }}</span>
                            </div>
                            <span class="text-xs font-semibold tabular-nums {{ $isAtLimit ? 'text-red-600 dark:text-red-400' : 'text-secondary-500' }}">
                                {{ $data['usage'] }} / {{ $data['limit'] }}
                            </span>
                        </div>
                        <div class="w-full bg-secondary-100 dark:bg-secondary-800 rounded-full h-2 overflow-hidden">
                            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-1000 ease-out" style="width: {{ $percentage }}%"></div>
                        </div>
                        @if($isAtLimit)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-2">{{ trans('common.billing_limit_reached') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Unlimited Resources --}}
        @php $unlimitedLimits = collect($summary['limits'])->filter(fn($data) => $data['unlimited']); @endphp
        @if($unlimitedLimits->isNotEmpty())
        <section class="mb-10 animate-fade-in" style="animation-delay: 280ms;">
            <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-5">{{ trans('common.billing_unlimited') }}</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($unlimitedLimits as $key => $data)
                    <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800">
                        <x-ui.icon :icon="$limitIcons[$key] ?? 'circle'" class="w-4 h-4 text-emerald-500" />
                        <span class="text-sm font-medium text-secondary-900 dark:text-white">{{ $limitLabels[$key] ?? $key }}</span>
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">∞</span>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Available Plans (Stripe mode only, when partner can upgrade) --}}
        @if($billingEnabled && $billingConfigured && $plans->count() > 1)
        <section class="mb-10 animate-fade-in" style="animation-delay: 320ms;">
            <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-5">{{ trans('common.billing_available_plans') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($plans->count(), 4) }} gap-4">
                @foreach($plans as $planKey => $plan)
                    @php
                        $isCurrent = $planKey === $currentPlanKey;
                        $planName = isset($plan['name_key']) ? trans($plan['name_key']) : ($plan['name'] ?? $planKey);
                        $planDesc = isset($plan['description_key']) ? trans($plan['description_key']) : ($plan['description'] ?? '');
                        $isFree = ($plan['price_monthly'] ?? 0) === 0;
                    @endphp
                    <div @class([
                        'rounded-xl border p-5 transition-colors duration-200',
                        'bg-primary-50 dark:bg-primary-500/10 border-primary-300 dark:border-primary-500/30' => $isCurrent,
                        'bg-white dark:bg-secondary-900 border-secondary-200 dark:border-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-700' => !$isCurrent,
                    ])>
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-bold text-secondary-900 dark:text-white">{{ $planName }}</h3>
                            @if($isCurrent)
                                <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 bg-primary-100 dark:bg-primary-500/20 px-2 py-0.5 rounded-full">
                                    {{ trans('common.billing_current') }}
                                </span>
                            @endif
                            @if($plan['is_popular'] ?? false)
                                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-500/20 px-2 py-0.5 rounded-full">
                                    {{ trans('common.popular') }}
                                </span>
                            @endif
                        </div>
                        @if($planDesc)
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">{{ $planDesc }}</p>
                        @endif
                        @if(!$isFree && ($plan['price_monthly'] ?? 0) > 0)
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                                {{ moneyFormatStyled($plan['price_monthly'], $plan['currency'] ?? 'USD') }}<span class="text-xs font-normal text-secondary-400">/{{ trans('common.month') }}</span>
                            </p>
                        @elseif($isFree)
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ trans('common.free') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            @if(!$canUpgrade)
                <p class="text-sm text-secondary-400 dark:text-secondary-500 mt-4">
                    <x-ui.icon icon="info" class="w-4 h-4 inline -mt-0.5" />
                    {{ trans('common.billing_contact_admin') }}
                </p>
            @endif
        </section>
        @endif

        {{-- Manual Mode Footer --}}
        @if(!$billingEnabled)
        <section class="animate-fade-in" style="animation-delay: 320ms;">
            <div class="bg-white dark:bg-secondary-900 rounded-xl p-6 border border-secondary-200 dark:border-secondary-800
                border-l-2 border-l-secondary-300 dark:border-l-secondary-600">
                <div class="flex items-start gap-4">
                    <x-ui.icon icon="info" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ trans('common.billing_manual_mode') }}</p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">{{ trans('common.billing_manual_description') }}</p>
                    </div>
                </div>
            </div>
        </section>
        @endif

    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}
</style>
@stop
