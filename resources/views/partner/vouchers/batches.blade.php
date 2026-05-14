{{--
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.

 Batch Management Dashboard - Jony Ive Edition

 Purpose: Central command center for managing voucher batches.
 Philosophy: QR code is HERO. Metrics are whispers. Actions are intuitive.
 Design: The batch card Apple would create if they did voucher systems.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.voucher_batches') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="layers"
        :title="trans('common.voucher_batches')"
        :description="trans('common.voucher_batches_description')"
    >
        <x-slot name="actions">
            @if($hasVouchers)
                {{-- Partner has vouchers: Show import & generate batch buttons --}}
                {{-- 
                <a href="{{ route('partner.vouchers.import') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 
                           text-sm font-medium text-secondary-700 dark:text-secondary-300 
                           bg-white dark:bg-secondary-800 
                           border border-secondary-200 dark:border-secondary-700 
                           rounded-xl shadow-sm
                           hover:bg-secondary-50 dark:hover:bg-secondary-700 
                           hover:border-secondary-300 dark:hover:border-secondary-600
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                           transition-colors duration-200">
                    <x-ui.icon icon="upload" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ trans('common.import_codes') }}</span>
                </a>
                 --}}
                <a href="{{ route('partner.vouchers.batch') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 
                           text-sm font-medium text-white 
                           bg-primary-600 hover:bg-primary-500
                           rounded-xl shadow-sm hover:shadow-md
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                           transition-all duration-200 active:scale-[0.98]">
                    <x-ui.icon icon="sparkles" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ trans('common.generate_batch') }}</span>
                </a>
            @else
                {{-- No vouchers yet: Direct to create first voucher --}}
                <a href="{{ route('partner.data.list', ['name' => 'vouchers']) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 
                           text-sm font-medium text-white 
                           bg-primary-600 hover:bg-primary-500
                           rounded-xl shadow-sm hover:shadow-md
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                           transition-all duration-200 active:scale-[0.98]">
                    <x-ui.icon icon="plus" class="w-4 h-4" />
                    <span>{{ trans('common.create_first_voucher') }}</span>
                </a>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- Stats Overview — Premium card design matching Shopify integration --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Total Batches --}}
        <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-100 dark:border-secondary-800
            shadow-sm transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="layers" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.batches') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['total_batches'] }}</p>
                </div>
            </div>
        </div>

        {{-- Total Codes --}}
        <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-100 dark:border-secondary-800
            shadow-sm transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="ticket" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.codes') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['total_codes'] }}</p>
                </div>
            </div>
        </div>

        {{-- Redeemed --}}
        <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-100 dark:border-secondary-800
            shadow-sm transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="check-circle" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.redeemed') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $stats['codes_used'] }}</p>
                </div>
            </div>
        </div>

        {{-- Usage Rate --}}
        <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
            border border-secondary-100 dark:border-secondary-800
            shadow-sm transition-colors duration-200">
            <div class="flex items-start gap-4">
                <x-ui.icon icon="trending-up" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.usage_rate') }}</p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $stats['usage_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Grid — iOS Premium Edition --}}
    @if($batches->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($batches as $batch)
                @php
                    $used = $batch->vouchers()->where('times_used', '>', 0)->count();
                    $usagePercent = $batch->vouchers_created > 0 ? round(($used / $batch->vouchers_created) * 100) : 0;
                @endphp
                <div class="group bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 
                    shadow-sm transition-colors duration-200 overflow-hidden">
                    
                    {{-- Card Header --}}
                    <div class="p-5 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            {{-- QR Button — Compact, elegant --}}
                            <button 
                                type="button"
                                @click="$dispatch('open-qr-modal-data', {
                                    title: '{{ $batch->name }}',
                                    subtitle: '{{ trans('common.scan_to_claim_voucher') }}',
                                    url: '{{ $batch->claim_url }}'
                                })"
                                class="w-14 h-14 flex-shrink-0 rounded-xl bg-secondary-50 dark:bg-secondary-800 
                                    flex items-center justify-center
                                    hover:bg-primary-50 dark:hover:bg-primary-900/30
                                    transition-colors duration-200"
                            >
                                <x-ui.icon icon="qr-code" class="w-7 h-7 text-secondary-400 dark:text-secondary-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-300" />
                            </button>

                            {{-- Title + Meta --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-semibold text-secondary-900 dark:text-white truncate mb-0.5">
                                    {{ $batch->name }}
                                </h3>
                                <p class="text-xs text-secondary-400 dark:text-secondary-500 format-date" data-date="{{ $batch->created_at }}">
                                    {{ $batch->created_at->format('M d, Y') }}
                                </p>
                            </div>

                            {{-- Status Badge --}}
                            @php
                                $statusConfig = match($batch->status) {
                                    'active' => ['bg' => 'bg-emerald-500', 'ring' => 'ring-emerald-500/20', 'animate' => true],
                                    'paused' => ['bg' => 'bg-amber-500', 'ring' => 'ring-amber-500/20', 'animate' => false],
                                    default => ['bg' => 'bg-secondary-400', 'ring' => 'ring-secondary-400/20', 'animate' => false],
                                };
                            @endphp
                            <div class="flex-shrink-0 relative">
                                <span class="block w-2.5 h-2.5 rounded-full {{ $statusConfig['bg'] }} ring-4 {{ $statusConfig['ring'] }} {{ $statusConfig['animate'] ? 'animate-pulse' : '' }}"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Metrics Row — Clean typography, no boxes --}}
                    <div class="px-5 pb-4">
                        <div class="flex items-baseline justify-between">
                            <div class="text-center">
                                <span class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $batch->vouchers_created }}</span>
                                <span class="block text-[10px] font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mt-0.5">{{ trans('common.created') }}</span>
                            </div>
                            <div class="text-center">
                                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums format-number">{{ $batch->claimed_count }}</span>
                                <span class="block text-[10px] font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mt-0.5">{{ trans('common.claimed') }}</span>
                            </div>
                            <div class="text-center">
                                <span class="text-2xl font-bold text-teal-600 dark:text-teal-400 tabular-nums format-number">{{ $used }}</span>
                                <span class="block text-[10px] font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mt-0.5">{{ trans('common.used') }}</span>
                            </div>
                            <div class="text-center">
                                <span class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $usagePercent }}%</span>
                                <span class="block text-[10px] font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mt-0.5">{{ trans('common.rate') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar — Subtle --}}
                    <div class="px-5 pb-5">
                        <div class="h-1 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-700 ease-out"
                                style="width: {{ $usagePercent }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Actions — Border-top separation --}}
                    <div class="px-5 py-4 border-t border-secondary-100 dark:border-secondary-800 flex items-center gap-2">
                        <a href="{{ route('partner.data.list', ['name' => 'batch-vouchers', 'batch_id' => $batch->id]) }}"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium 
                                text-secondary-700 dark:text-secondary-300
                                bg-secondary-100 dark:bg-secondary-800 
                                hover:bg-secondary-200 dark:hover:bg-secondary-700
                                rounded-xl transition-all duration-200 active:scale-[0.98]">
                            <x-ui.icon icon="tickets" class="w-4 h-4" />
                            <span>{{ trans('common.view_codes') }}</span>
                        </a>

                        {{-- QR Button --}}
                        <button 
                            type="button"
                            @click="$dispatch('open-qr-modal-data', {
                                title: '{{ $batch->name }}',
                                subtitle: '{{ trans('common.scan_to_claim_voucher') }}',
                                url: '{{ $batch->claim_url }}'
                            })"
                            class="inline-flex items-center justify-center w-10 h-10
                                text-secondary-500 dark:text-secondary-400 
                                hover:text-primary-600 dark:hover:text-primary-400
                                hover:bg-primary-50 dark:hover:bg-primary-900/30
                                rounded-xl transition-all duration-200 active:scale-[0.98]">
                            <x-ui.icon icon="qr-code" class="w-5 h-5" />
                        </button>

                        {{-- More Actions --}}
                        <div x-data="{ open: false, toggling: false }" @click.away="open = false" class="relative">
                            <button 
                                @click="open = !open" 
                                class="inline-flex items-center justify-center w-10 h-10
                                    text-secondary-400 dark:text-secondary-500 
                                    hover:text-secondary-600 dark:hover:text-secondary-300
                                    hover:bg-secondary-100 dark:hover:bg-secondary-800
                                    rounded-xl transition-all duration-200 active:scale-[0.98]">
                                <x-ui.icon icon="ellipsis" class="w-5 h-5" />
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 bottom-full mb-2 w-48 
                                    bg-white dark:bg-secondary-800 
                                    rounded-xl shadow-xl shadow-secondary-900/10 dark:shadow-black/30
                                    border border-secondary-200 dark:border-secondary-700 
                                    py-1.5 z-50 overflow-hidden"
                                 style="display: none;">
                                
                                {{-- Pause/Resume --}}
                                <form id="toggle-batch-form-{{ $batch->id }}" 
                                      action="{{ route('partner.vouchers.batch.toggle', $batch->id) }}" 
                                      method="POST"
                                      @submit="toggling = true">
                                    @csrf
                                    <button type="submit" 
                                            :disabled="toggling"
                                            class="w-full text-left px-3.5 py-2 text-sm text-secondary-700 dark:text-secondary-300 
                                                hover:bg-secondary-50 dark:hover:bg-secondary-700/50 
                                                flex items-center gap-2.5 transition-colors 
                                                disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-ui.icon icon="{{ $batch->status === 'active' ? 'pause' : 'play' }}" class="w-4 h-4 text-secondary-400" />
                                        <span x-show="!toggling">{{ $batch->status === 'active' ? trans('common.pause_batch') : trans('common.resume_batch') }}</span>
                                        <span x-show="toggling" class="flex items-center gap-2">
                                            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ trans('common.processing') }}
                                        </span>
                                    </button>
                                </form>

                                <div class="my-1.5 mx-3 border-t border-secondary-100 dark:border-secondary-700"></div>

                                {{-- Delete --}}
                                <form id="delete-batch-form-{{ $batch->id }}" action="{{ route('partner.vouchers.batch.delete', $batch->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                        onclick="deleteBatch('{{ $batch->id }}', '{{ addslashes($batch->name) }}', {{ $batch->vouchers_created }})"
                                        class="w-full text-left px-3.5 py-2 text-sm text-red-600 dark:text-red-400 
                                            hover:bg-red-50 dark:hover:bg-red-900/20 
                                            flex items-center gap-2.5 transition-colors">
                                        <x-ui.icon icon="trash-2" class="w-4 h-4" />
                                        {{ trans('common.delete_batch') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $batches->links() }}
        </div>
    @else
        {{-- Empty State — Premium card design --}}
        <div class="bg-white dark:bg-secondary-900 rounded-3xl border border-secondary-100 dark:border-secondary-800 shadow-sm overflow-hidden">
            <div class="flex flex-col items-center justify-center py-20 px-8">
                @if($hasVouchers)
                    {{-- Has vouchers, no batches yet --}}
                    <x-ui.icon icon="layers" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-6" />
                    <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-3">
                        {{ trans('common.no_batches_yet') }}
                    </h3>
                    <p class="text-secondary-500 dark:text-secondary-400 mb-8 text-center max-w-md">
                        {{ trans('common.create_first_batch_description') }}
                    </p>
                    <a href="{{ route('partner.vouchers.batch') }}"
                        class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl font-medium text-sm
                            bg-primary-600 text-white 
                            hover:bg-primary-500
                            transition-colors duration-200">
                        <x-ui.icon icon="sparkles" class="w-4 h-4" />
                        {{ trans('common.generate_batch') }}
                    </a>
                @else
                    {{-- No vouchers at all --}}
                    <x-ui.icon icon="ticket" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-6" />
                    <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-3">
                        {{ trans('common.create_voucher_first') }}
                    </h3>
                    <p class="text-secondary-500 dark:text-secondary-400 mb-8 text-center max-w-md">
                        {{ trans('common.create_voucher_first_description') }}
                    </p>
                    <a href="{{ route('partner.data.list', ['name' => 'vouchers']) }}"
                        class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl font-medium text-sm
                            bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 
                            hover:bg-secondary-800 dark:hover:bg-secondary-100
                            transition-colors duration-200">
                        <x-ui.icon icon="plus" class="w-4 h-4" />
                        {{ trans('common.create_first_voucher') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- QR Modal Component (Event-Based) --}}
<x-ui.qr-modal-data />

@push('styles')
<style>
@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.animate-bounce-subtle {
    animation: bounce-subtle 2s ease-in-out infinite;
}
</style>
@endpush

@push('scripts')
<script>
// Translation strings for modal
const modalTranslations = {
    deleteTitle: @json(trans('common.delete_batch')),
    confirmMessage: @json(trans('common.confirm_delete_batch')),
    vouchers: @json(trans('common.vouchers')),
    willBeDeleted: @json(trans('common.will_be_deleted')),
    cancel: @json(trans('common.cancel'))
};

function deleteBatch(batchId, batchName, voucherCount) {
    const alertIcon = `<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>`;
    
    appConfirm(
        modalTranslations.deleteTitle,
        `<div class="space-y-3">
            <p class="text-secondary-900 dark:text-white font-medium">${batchName}</p>
            <p>${modalTranslations.confirmMessage}</p>
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                    ${alertIcon}
                    ${voucherCount} ${modalTranslations.vouchers} ${modalTranslations.willBeDeleted}
                </p>
            </div>
        </div>`,
        {
            btnConfirm: {
                text: modalTranslations.deleteTitle,
                click: () => {
                    document.getElementById('delete-batch-form-' + batchId).submit();
                }
            },
            btnCancel: {
                text: modalTranslations.cancel
            }
        }
    );
}
</script>
@endpush
@endsection
