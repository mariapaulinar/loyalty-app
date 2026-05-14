{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Campaign Detail View — Mission Control

Purpose: Campaign sending progress and results dashboard.
Philosophy: Transparency during sending. Confidence after completion.
Design: Progress that feels professional, not anxious.

Critical UX:
- Sending modal is non-dismissable (shows warning on close attempt)
- Progress updates in real-time with smooth animations
- Browser close warning during active send
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.email_campaign.campaign_details') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div 
    class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8"
    x-data="campaignSender(@js($progress))"
    x-init="init()"
>

    {{-- Page Header --}}
    <x-ui.page-header
        icon="mail"
        :title="$campaign->getSubjectForLocale(config('app.locale')) ?: trans('common.email_campaign.no_subject')"
    >
        <x-slot name="description">
            <div class="flex items-center gap-3 flex-wrap">
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
                <span class="text-secondary-400">•</span>
                <span class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ $campaign->getSegmentLabel() }}
                </span>
                <span class="text-secondary-400">•</span>
<span class="text-sm text-secondary-500 dark:text-secondary-400 format-date-time" data-date-time="{{ $campaign->created_at->toIso8601String() }}">
                                    {{ $campaign->created_at->format('M j, Y H:i') }}
                                </span>
            </div>
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('partner.email-campaigns.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 
                       text-sm font-medium text-secondary-700 dark:text-secondary-300 
                       bg-white dark:bg-secondary-800 
                       border border-secondary-200 dark:border-secondary-700 
                       rounded-xl shadow-sm
                       hover:bg-secondary-50 dark:hover:bg-secondary-700 
                       transition-colors duration-200">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                {{ trans('common.back_to_campaigns') }}
            </a>
        </x-slot>
    </x-ui.page-header>

    {{-- Progress Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 mb-8">
        {{-- Total Recipients --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-5 
            border border-secondary-100 dark:border-secondary-800 shadow-sm">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="users" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.total_recipients') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums" x-text="total.toLocaleString()">{{ $campaign->recipient_count }}</p>
                </div>
            </div>
        </div>

        {{-- Delivered --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-5 
            border border-secondary-100 dark:border-secondary-800 shadow-sm">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="check-circle" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.delivered') }}</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums" x-text="sent.toLocaleString()">{{ $campaign->sent_count }}</p>
                </div>
            </div>
        </div>

        {{-- Failed --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-5 
            border border-secondary-100 dark:border-secondary-800 shadow-sm">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="x-circle" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.failed') }}</p>
                    <p class="text-2xl font-bold tabular-nums" :class="failed > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'" x-text="failed.toLocaleString()">{{ $campaign->failed_count }}</p>
                </div>
            </div>
        </div>

        {{-- Progress --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl p-5 
            border border-secondary-100 dark:border-secondary-800 shadow-sm">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="percent" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.email_campaign.progress') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums" x-text="progress.toFixed(1) + '%'">{{ $progress['progress'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Draft Campaign Notice --}}
    @if($campaign->isDraft())
        <div class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-2xl border border-violet-200 dark:border-violet-800/50 p-6 mb-8">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="file-edit" class="w-6 h-6 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-violet-900 dark:text-violet-100">
                        {{ trans('common.email_campaign.status.draft') }}
                    </h3>
                    <p class="mt-1 text-violet-700 dark:text-violet-300">
                        {{ trans('common.email_campaign.send_from_list') }}
                    </p>
                    <div class="mt-4">
                        <form action="{{ route('partner.email-campaigns.activate', $campaign) }}" method="POST">
                            @csrf
                            <button 
                                type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 
                                       text-sm font-semibold text-white 
                                       bg-primary-600 hover:bg-primary-500
                                       rounded-xl transition-colors duration-200"
                            >
                                <x-ui.icon icon="send" class="w-5 h-5" />
                                {{ trans('common.email_campaign.start_sending') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Progress Bar (when sending) --}}
    <template x-if="!done && status !== 'failed' && status !== 'draft'">
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-secondary-900 dark:text-white">
                    <span x-text="isSending ? '{{ trans('common.email_campaign.sending_emails') }}' : '{{ trans('common.email_campaign.ready_to_send') }}'"></span>
                </span>
                <span class="text-sm text-secondary-500 dark:text-secondary-400">
                    <span x-text="(sent + failed).toLocaleString()"></span> / <span x-text="total.toLocaleString()"></span>
                </span>
            </div>
            
            <div class="w-full h-3 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
                <div 
                    class="h-full bg-gradient-to-r from-primary-500 to-primary-400 rounded-full transition-all duration-300 ease-out"
                    :style="`width: ${progress}%`"
                ></div>
            </div>

            {{-- Start/Resume Button --}}
            <div class="mt-4 flex justify-center">
                <button 
                    type="button"
                    @click="startSending"
                    :disabled="isSending"
                    class="inline-flex items-center gap-2 px-6 py-3 
                           text-sm font-semibold text-white 
                           bg-primary-600 hover:bg-primary-500
                           disabled:bg-secondary-400 disabled:cursor-not-allowed
                           rounded-xl transition-colors duration-200"
                >
                    <template x-if="!isSending">
                        <span class="flex items-center gap-2">
                            <x-ui.icon icon="send" class="w-5 h-5" />
                            <span x-text="sent + failed > 0 ? '{{ trans('common.email_campaign.resume_sending') }}' : '{{ trans('common.email_campaign.start_sending') }}'"></span>
                        </span>
                    </template>
                    <template x-if="isSending">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ trans('common.email_campaign.sending') }}
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </template>

    {{-- Completion Summary --}}
    <template x-if="done && status === 'sent'">
        <div 
            x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
            class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl border border-emerald-200 dark:border-emerald-800/50 p-6 mb-8"
        >
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                    <x-ui.icon icon="circle-check" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">
                        {{ trans('common.email_campaign.completed_title') }}
                    </h3>
                    <p class="mt-1 text-emerald-700 dark:text-emerald-300">
                        {{ trans('common.email_campaign.completed_message') }}
                    </p>
                    <div class="mt-3 flex gap-4 text-sm">
                        <span class="text-emerald-600 dark:text-emerald-400">
                            <span x-text="sent.toLocaleString()"></span> {{ trans('common.email_campaign.emails_sent') }}
                        </span>
                        <template x-if="failed > 0">
                            <span class="text-red-600 dark:text-red-400">
                                <span x-text="failed.toLocaleString()"></span> {{ trans('common.email_campaign.emails_failed') }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Recent Recipients Table --}}
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                {{ trans('common.email_campaign.recent_recipients') }}
            </h3>
            <span class="text-sm text-secondary-500 dark:text-secondary-400">
                {{ trans('common.showing_latest', ['count' => min(50, $campaign->recipient_count)]) }}
            </span>
        </div>

        @if($recipients->isEmpty())
            <div class="px-6 py-12 text-center">
                <x-ui.icon icon="inbox" class="w-12 h-12 mx-auto text-secondary-300 dark:text-secondary-600 mb-4" />
                <p class="text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.email_campaign.no_recipients_yet') }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50 dark:bg-secondary-800/50 border-b border-secondary-100 dark:border-secondary-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                                {{ trans('common.member') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                                {{ trans('common.email_address') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                                {{ trans('common.status') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">
                                {{ trans('common.sent_at') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        @foreach($recipients as $recipient)
                            <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                        {{ $recipient->member?->name ?? trans('common.deleted_member') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-secondary-500 dark:text-secondary-400">
                                        {{ $recipient->email }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $recipientStatusColors = [
                                            'pending' => 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-300',
                                            'sent' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $recipientStatusColors[$recipient->status] ?? $recipientStatusColors['pending'] }}">
                                        @if($recipient->isSent())
                                            <x-ui.icon icon="check" class="w-3 h-3 mr-1" />
                                        @elseif($recipient->isFailed())
                                            <x-ui.icon icon="x" class="w-3 h-3 mr-1" />
                                        @endif
                                        {{ trans('common.email_campaign.recipient_status.' . $recipient->status) }}
                                    </span>
                                    @if($recipient->error_message)
                                        <span class="block mt-1 text-xs text-red-500 dark:text-red-400 truncate max-w-xs" title="{{ $recipient->error_message }}">
                                            {{ Str::limit($recipient->error_message, 50) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if($recipient->sent_at)
                                        <span class="text-sm text-secondary-500 dark:text-secondary-400 format-date-time" data-date-time="{{ $recipient->sent_at->toIso8601String() }}">
                                            {{ $recipient->sent_at->format('H:i:s') }}
                                        </span>
                                    @else
                                        <span class="text-sm text-secondary-400 dark:text-secondary-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Delete Button (only for non-sending campaigns) --}}
    @if(!$campaign->isSending())
        <div class="mt-8 pt-8 border-t border-secondary-200 dark:border-secondary-800" x-data="{ confirmDelete: false }">
            <form 
                x-ref="deleteForm"
                action="{{ route('partner.email-campaigns.destroy', $campaign) }}" 
                method="POST"
            >
                @csrf
                @method('DELETE')
                
                <button 
                    type="button"
                    @click="confirmDelete = true"
                    class="inline-flex items-center gap-2 text-sm text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                >
                    <x-ui.icon icon="trash-2" class="w-4 h-4" />
                    {{ trans('common.email_campaign.delete_campaign') }}
                </button>
            </form>

            {{-- Delete Confirmation Modal --}}
            <template x-teleport="body">
                <div 
                    x-show="confirmDelete"
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @keydown.escape.window="confirmDelete = false"
                >
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="confirmDelete = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div 
                            class="relative w-full max-w-md bg-white dark:bg-secondary-800 rounded-2xl shadow-2xl p-6"
                            @click.stop
                        >
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <x-ui.icon icon="trash-2" class="w-6 h-6 text-red-600 dark:text-red-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                                    {{ trans('common.email_campaign.delete_confirm_title') }}
                                </h3>
                                <p class="text-secondary-600 dark:text-secondary-400 mb-6">
                                    {{ trans('common.email_campaign.delete_confirm_message') }}
                                </p>
                                <div class="flex gap-3">
                                    <button 
                                        type="button"
                                        @click="confirmDelete = false"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-secondary-700 dark:text-secondary-300 bg-secondary-100 dark:bg-secondary-700 rounded-xl hover:bg-secondary-200 dark:hover:bg-secondary-600 transition-colors"
                                    >
                                        {{ trans('common.cancel') }}
                                    </button>
                                    <button 
                                        type="button"
                                        @click="$refs.deleteForm.submit()"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-500 rounded-xl transition-colors"
                                    >
                                        {{ trans('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif

    {{-- Sending Modal (Non-Dismissable) --}}
    <template x-teleport="body">
        <div 
            x-show="showSendingModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-secondary-800 rounded-2xl shadow-2xl overflow-hidden">
                    {{-- Header --}}
                    <div class="px-6 py-4 bg-gradient-to-r from-primary-500 to-violet-500">
                        <h3 class="text-lg font-semibold text-white">
                            {{ trans('common.email_campaign.sending_in_progress') }}
                        </h3>
                        <p class="text-sm text-white/80">
                            {{ trans('common.email_campaign.do_not_close') }}
                        </p>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        {{-- Animated Mail Icon --}}
                        <div class="flex justify-center mb-6">
                            <div class="relative">
                                <div class="w-20 h-20 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                    <x-ui.icon icon="send" class="w-10 h-10 text-primary-600 dark:text-primary-400 animate-pulse" />
                                </div>
                                <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold animate-bounce">
                                    <span x-text="sent"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Progress --}}
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                    {{ trans('common.email_campaign.progress') }}
                                </span>
                                <span class="text-sm text-secondary-500 dark:text-secondary-400">
                                    <span x-text="(sent + failed).toLocaleString()"></span> / <span x-text="total.toLocaleString()"></span>
                                </span>
                            </div>
                            <div class="w-full h-4 bg-secondary-100 dark:bg-secondary-700 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-to-r from-primary-500 to-violet-500 rounded-full transition-all duration-300 ease-out relative overflow-hidden"
                                    :style="`width: ${progress}%`"
                                >
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-3 bg-secondary-50 dark:bg-secondary-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums" x-text="total.toLocaleString()"></p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.email_campaign.total') }}</p>
                            </div>
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums" x-text="sent.toLocaleString()"></p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ trans('common.email_campaign.sent') }}</p>
                            </div>
                            <div class="p-3 rounded-xl" :class="failed > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-secondary-50 dark:bg-secondary-700/50'">
                                <p class="text-2xl font-bold tabular-nums" :class="failed > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-400'" x-text="failed.toLocaleString()"></p>
                                <p class="text-xs" :class="failed > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-500 dark:text-secondary-400'">{{ trans('common.email_campaign.failed_short') }}</p>
                            </div>
                        </div>

                        {{-- Pause Button --}}
                        <div class="mt-6 flex justify-center">
                            <button 
                                type="button"
                                @click="pauseSending"
                                class="inline-flex items-center gap-2 px-5 py-2.5 
                                       text-sm font-medium text-amber-700 dark:text-amber-300 
                                       bg-amber-100 dark:bg-amber-900/30 
                                       border border-amber-200 dark:border-amber-800/50
                                       rounded-xl hover:bg-amber-200 dark:hover:bg-amber-900/50
                                       transition-colors duration-200"
                            >
                                <x-ui.icon icon="pause" class="w-4 h-4" />
                                {{ trans('common.email_campaign.pause_sending') }}
                            </button>
                        </div>

                        {{-- Warning --}}
                        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800/50">
                            <p class="text-xs text-amber-700 dark:text-amber-300 flex items-center gap-2">
                                <x-ui.icon icon="alert-triangle" class="w-4 h-4 flex-shrink-0" />
                                {{ trans('common.email_campaign.close_warning') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('campaignSender', (initialProgress) => ({
        // State from server
        sent: initialProgress.sent || 0,
        failed: initialProgress.failed || 0,
        total: initialProgress.total || 0,
        status: initialProgress.status || 'pending',
        progress: initialProgress.progress || 0,
        done: initialProgress.done || false,
        
        // UI state
        isSending: false,
        isPaused: false,
        showSendingModal: false,
        
        init() {
            // Setup beforeunload warning when sending
            window.addEventListener('beforeunload', (e) => {
                if (this.isSending) {
                    e.preventDefault();
                    e.returnValue = '{{ trans('common.email_campaign.leave_warning') }}';
                    return e.returnValue;
                }
            });
            
            // Auto-start if campaign needs sending and was previously sending
            if (!this.done && this.status === 'sending') {
                this.startSending();
            }
        },
        
        async startSending() {
            if (this.isSending || this.done) return;
            
            this.isSending = true;
            this.isPaused = false;
            this.showSendingModal = true;
            
            while (!this.done && !this.isPaused) {
                try {
                    const response = await fetch('{{ route('partner.email-campaigns.send-next', $campaign) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    });
                    
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }
                    
                    const data = await response.json();
                    
                    // Update state
                    this.sent = data.sent;
                    this.failed = data.failed;
                    this.total = data.total;
                    this.status = data.status;
                    this.progress = data.progress;
                    this.done = data.done;
                    
                    if (data.error) {
                        console.error('Send error:', data.error);
                    }
                } catch (error) {
                    console.error('Network error:', error);
                    // Wait a bit before retrying
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }
            
            this.isSending = false;
            this.showSendingModal = false;
            
            // Celebrate completion and reload page to show updated status
            if (this.done && !this.isPaused) {
                if (typeof confetti === 'function') {
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
                // Reload page after a short delay to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        },
        
        pauseSending() {
            this.isPaused = true;
        },
    }));
});
</script>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>
@endpush
@endsection

