{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Campaign List — Command Center

Purpose: Overview of all email campaigns with stats and status.
Philosophy: Information density without clutter. Scannable at a glance.
Design: Dashboard aesthetics that make Stripe engineers curious.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.email_campaigns') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">

    {{-- Page Header --}}
    <x-ui.page-header
        icon="mails"
        :title="trans('common.email_campaigns')"
        :description="trans('common.email_campaigns_description')"
    >
        <x-slot name="actions">
            <a href="{{ route('partner.email-campaigns.compose') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 
                       text-sm font-medium text-white 
                       bg-primary-600 hover:bg-primary-500
                       rounded-xl shadow-sm hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-all duration-200 active:scale-[0.98]">
                <x-ui.icon icon="mail-plus" class="w-4 h-4" />
                <span class="hidden sm:inline">{{ trans('common.email_campaign.compose') }}</span>
            </a>
        </x-slot>
    </x-ui.page-header>

    {{-- Stats Overview — Premium card design matching voucher batches --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Total Campaigns --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-200 dark:border-secondary-800
            hover:border-secondary-300 dark:hover:border-secondary-700
            transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="mails" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.total_campaigns') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-200 dark:border-secondary-800
            hover:border-secondary-300 dark:hover:border-secondary-700
            transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="check-circle" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.completed') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['sent'] }}</p>
                </div>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-200 dark:border-secondary-800
            hover:border-secondary-300 dark:hover:border-secondary-700
            transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="loader" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.in_progress') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['sending'] }}</p>
                </div>
            </div>
        </div>

        {{-- Total Delivered --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-200 dark:border-secondary-800
            hover:border-secondary-300 dark:hover:border-secondary-700
            transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="send" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.total_delivered') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['total_delivered'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Campaigns List --}}
    @if($campaigns->isEmpty())
        {{-- Empty State — Premium card design matching voucher batches --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
            <div class="flex flex-col items-center justify-center py-20 px-8">
                <x-ui.icon icon="mails" class="w-10 h-10 text-secondary-300 dark:text-secondary-600 mb-6" />
                <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-3">
                    {{ trans('common.email_campaign.no_campaigns_title') }}
                </h3>
                <p class="text-secondary-500 dark:text-secondary-400 mb-8 text-center max-w-md">
                    {{ trans('common.email_campaign.no_campaigns_description') }}
                </p>
                <a href="{{ route('partner.email-campaigns.compose') }}"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-medium text-sm
                        bg-primary-600 text-white 
                        hover:bg-primary-500
                        active:scale-[0.98]
                        transition-colors duration-200">
                    <x-ui.icon icon="mail-plus" class="w-4 h-4" />
                    {{ trans('common.email_campaign.create_first') }}
                </a>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden">
            {{-- Table Header --}}
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-secondary-50 dark:bg-secondary-800/50 border-b border-secondary-200 dark:border-secondary-700">
                <div class="col-span-4 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                    {{ trans('common.email_campaign.campaign') }}
                </div>
                <div class="col-span-2 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                    {{ trans('common.status') }}
                </div>
                <div class="col-span-2 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider text-right">
                    {{ trans('common.email_campaign.recipients') }}
                </div>
                <div class="col-span-2 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider text-right">
                    {{ trans('common.email_campaign.delivered') }}
                </div>
                <div class="col-span-2 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider text-right">
                    {{ trans('common.created') }}
                </div>
            </div>

            {{-- Campaign Rows --}}
            <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                @foreach($campaigns as $campaign)
                    <a 
                        href="{{ route('partner.email-campaigns.show', $campaign) }}"
                        class="block md:grid grid-cols-12 gap-4 px-6 py-4 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-150"
                    >
                        {{-- Campaign Info --}}
                        <div class="col-span-4 flex items-center gap-3 mb-2 md:mb-0">
                            @php
                                $bgClass = match(true) {
                                    $campaign->isSent() => 'bg-emerald-500/10',
                                    $campaign->isSending() => 'bg-amber-500/10',
                                    $campaign->isFailed() => 'bg-red-500/10',
                                    $campaign->isDraft() => 'bg-violet-500/10',
                                    default => 'bg-secondary-500/10',
                                };
                                $iconName = match(true) {
                                    $campaign->isSent() => 'check-circle',
                                    $campaign->isSending() => 'loader',
                                    $campaign->isFailed() => 'x-circle',
                                    $campaign->isDraft() => 'file-edit',
                                    default => 'clock',
                                };
                                $iconClass = match(true) {
                                    $campaign->isSent() => 'text-emerald-600 dark:text-emerald-400',
                                    $campaign->isSending() => 'text-amber-600 dark:text-amber-400 animate-spin',
                                    $campaign->isFailed() => 'text-red-600 dark:text-red-400',
                                    $campaign->isDraft() => 'text-violet-600 dark:text-violet-400',
                                    default => 'text-secondary-600 dark:text-secondary-400',
                                };
                            @endphp
                            <x-ui.icon :icon="$iconName" class="w-5 h-5 {{ $iconClass }} flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                    {{ $campaign->getSubjectForLocale(config('app.locale')) ?: trans('common.email_campaign.no_subject') }}
                                </p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                    {{ $campaign->getSegmentLabel() }}
                                </p>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="col-span-2 flex items-center md:justify-start mb-2 md:mb-0">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
                                    'pending' => 'bg-secondary-100 text-secondary-700 dark:bg-secondary-800 dark:text-secondary-300',
                                    'sending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                    'sent' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $statusColors[$campaign->status] ?? $statusColors['pending'] }}">
                                {{ trans('common.email_campaign.status.' . $campaign->status) }}
                            </span>
                        </div>

                        {{-- Recipients --}}
                        <div class="col-span-2 flex items-center justify-end">
                            <span class="text-sm tabular-nums text-secondary-900 dark:text-white format-number">
                                {{ $campaign->recipient_count }}
                            </span>
                        </div>

                        {{-- Delivered --}}
                        <div class="col-span-2 flex items-center justify-end">
                            <span class="text-sm tabular-nums text-secondary-900 dark:text-white">
                                <span class="text-emerald-600 dark:text-emerald-400 format-number">{{ $campaign->sent_count }}</span>
                                @if($campaign->failed_count > 0)
                                    <span class="text-secondary-400 dark:text-secondary-500">/</span>
                                    <span class="text-red-600 dark:text-red-400 format-number">{{ $campaign->failed_count }}</span>
                                @endif
                            </span>
                        </div>

                        {{-- Created --}}
                        <div class="col-span-2 flex items-center justify-end">
                            <span class="text-sm text-secondary-500 dark:text-secondary-400 format-date-time" data-date="{{ $campaign->created_at->toIso8601String() }}">
                                {{ $campaign->created_at->format('M j, Y H:i') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
            <div class="mt-8">
                {{ $campaigns->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
