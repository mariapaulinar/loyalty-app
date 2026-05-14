{{--
╔═══════════════════════════════════════════════════════════════════════════════╗
║                    MEMBER IDENTITY BADGE — "Your Digital ID"                    ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║  Premium identity component for the member header.                             ║
║                                                                                ║
║  STATES:                                                                        ║
║  • Anonymous Member: Shows device code pill → tap for QR modal                  ║
║  • Registered Member: Shows avatar with name → dropdown menu                    ║
║                                                                                ║
║  DESIGN:                                                                        ║
║  • Revolut-inspired identity pill with QR code icon                            ║
║  • Modal with Universal QR + device sync functionality                          ║
║  • Micro-animations for premium feel (Linear/Stripe quality)                    ║
║                                                                                ║
║  Copyright (c) 2026 NowSquare. All rights reserved.                            ║
╚═══════════════════════════════════════════════════════════════════════════════╝
--}}

@props([
    'member' => null,
])

@php
    $member = $member ?? auth('member')->user();
    $isAnonymous = $member && $member->isAnonymous();
    $deviceCode = $member?->device_code ?? null;
    $displayName = $member?->getDisplayNameFormatted() ?? trans('common.guest');
@endphp

{{-- Only render if member exists (anonymous or registered) --}}
@if($member)
    <div 
        x-data="memberIdentity({
            memberId: '{{ $member->id }}',
            deviceCode: '{{ $deviceCode }}',
            isAnonymous: {{ $isAnonymous ? 'true' : 'false' }},
            displayName: '{{ addslashes($displayName) }}',
            uniqueIdentifier: '{{ $member->unique_identifier }}',
        })"
        {{ $attributes->merge(['class' => 'relative']) }}
    >
        {{-- ════════════════════════════════════════════════════════════════════
             IDENTITY PILL — Tap to show Universal QR + Settings
             ════════════════════════════════════════════════════════════════════ --}}
        <button 
            @click="showQrModal = true"
            class="flex items-center gap-2.5 px-3 py-2 rounded-2xl cursor-pointer
                   bg-white/60 dark:bg-white/5
                   border border-secondary-200/60 dark:border-white/10
                   hover:bg-white hover:border-secondary-300 dark:hover:bg-white/10 dark:hover:border-white/15
                   hover:shadow-lg hover:shadow-secondary-900/5 dark:hover:shadow-black/20
                   active:scale-[0.97]
                   transition-all duration-200 ease-out"
        >
            {{-- QR Icon --}}
            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-sm shadow-primary-500/30">
                <x-ui.icon icon="qr-code" class="w-4 h-4 text-white" />
            </div>
            
            {{-- Device Code / Name --}}
            @if($isAnonymous)
                <span class="font-mono text-sm font-semibold tracking-wider text-secondary-700 dark:text-white/80">
                    {{ $deviceCode }}
                </span>
            @else
                <span class="text-sm font-medium text-secondary-700 dark:text-white/80 max-w-[100px] truncate">
                    {{ $member->name ?? $member->email }}
                </span>
            @endif
            
            {{-- Expand indicator --}}
            <x-ui.icon icon="chevron-down" class="w-3.5 h-3.5 text-secondary-400 dark:text-white/40" />
        </button>

        {{-- ════════════════════════════════════════════════════════════════════
             UNIVERSAL QR MODAL — "Your Digital Pass"
             ════════════════════════════════════════════════════════════════════ --}}
        <div 
            x-show="showQrModal" 
            x-cloak
            @keydown.escape.window="showQrModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        >
            {{-- Backdrop with blur --}}
            <div 
                class="absolute inset-0 bg-black/60 backdrop-blur-md"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showQrModal = false"
            ></div>
            
            {{-- Modal Card --}}
            <div 
                class="relative w-full max-w-sm rounded-3xl overflow-hidden
                       bg-white dark:bg-secondary-900
                       border border-secondary-200/50 dark:border-white/10
                       shadow-2xl"
                x-transition:enter="transition ease-out duration-300 delay-50"
                x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            >
                {{-- Close Button --}}
                <button 
                    @click="showQrModal = false"
                    class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full flex items-center justify-center cursor-pointer
                           bg-secondary-100 dark:bg-white/10 
                           hover:bg-secondary-200 dark:hover:bg-white/15
                           active:scale-95
                           transition-all duration-150"
                >
                    <x-ui.icon icon="x" class="w-5 h-5 text-secondary-600 dark:text-white/60" />
                </button>

                {{-- Gradient Background Glow --}}
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] rounded-full bg-primary-500/10 dark:bg-primary-500/5 blur-3xl"></div>
                </div>

                {{-- Content --}}
                <div class="relative z-10 p-8 text-center">
                    {{-- Title --}}
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 mb-4 shadow-xl shadow-primary-500/30">
                            <x-ui.icon icon="wallet" class="w-7 h-7 text-white" />
                        </div>
                        <h2 class="text-xl font-bold text-secondary-900 dark:text-white">
                            {{ trans('common.your_digital_pass') }}
                        </h2>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                            {{ trans('common.scan_to_earn') }}
                        </p>
                    </div>

                    {{-- QR Code Container --}}
                    <div class="mx-auto w-56 h-56 bg-white rounded-2xl p-3 mb-6 shadow-xl border border-secondary-100">
                        <img 
                            x-ref="qrcode"
                            src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                            class="w-full h-full object-contain rounded-xl"
                            data-qr-url=""
                            data-qr-color-dark="#0a0a0a"
                            data-qr-color-light="#ffffff"
                            alt="QR Code"
                        />
                    </div>

                    {{-- Device Code Display --}}
                    <div class="mb-6">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500 mb-2 font-medium">
                            {{ trans('common.your_code') }}
                        </p>
                        <div class="inline-flex items-center gap-3 px-5 py-3 rounded-2xl bg-secondary-100/80 dark:bg-white/5 border border-secondary-200/50 dark:border-white/10">
                            <span 
                                class="font-mono text-3xl font-bold tracking-[0.25em] text-secondary-900 dark:text-white"
                                x-text="deviceCode || '----'"
                            ></span>
                            <button 
                                @click="copyCode()"
                                class="p-2 rounded-lg bg-white dark:bg-white/10 hover:bg-secondary-50 dark:hover:bg-white/15 transition-colors cursor-pointer"
                                :class="{ 'bg-emerald-100 dark:bg-emerald-500/20': copied }"
                            >
                                <x-ui.icon 
                                    x-show="!copied"
                                    icon="copy" 
                                    class="w-4 h-4 text-secondary-500 dark:text-white/50" 
                                />
                                <x-ui.icon 
                                    x-show="copied"
                                    x-cloak
                                    icon="check" 
                                    class="w-4 h-4 text-emerald-600 dark:text-emerald-400" 
                                />
                            </button>
                        </div>
                    </div>

                    @if($isAnonymous)
                        {{-- Anonymous: Divider and Device Sync Option --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-secondary-200 dark:border-white/10"></div>
                            </div>
                            <div class="relative flex justify-center text-xs">
                                <span class="px-3 bg-white dark:bg-secondary-900 text-secondary-400 dark:text-secondary-500">
                                    {{ trans('common.or') }}
                                </span>
                            </div>
                        </div>

                        {{-- Device Sync / Switch Account --}}
                        <div 
                            x-data="{ 
                                syncMode: false, 
                                syncCode: '', 
                                syncing: false, 
                                error: '',
                                confirming: false,
                            }"
                        >
                            {{-- Toggle Sync Mode --}}
                            <button
                                x-show="!syncMode"
                                @click="syncMode = true"
                                class="w-full py-3 px-4 rounded-xl font-medium cursor-pointer
                                       text-primary-600 dark:text-primary-400
                                       bg-primary-50 dark:bg-primary-500/10
                                       hover:bg-primary-100 dark:hover:bg-primary-500/15
                                       active:scale-[0.98]
                                       transition-all duration-150"
                            >
                                <span class="flex items-center justify-center gap-2">
                                    <x-ui.icon icon="smartphone" class="w-4 h-4" />
                                    {{ trans('common.sync_another_device') }}
                                </span>
                            </button>

                            {{-- Sync Form --}}
                            <div x-show="syncMode" x-cloak class="space-y-4">
                                {{-- Info Banner --}}
                                <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200/50 dark:border-blue-500/20 text-left">
                                    <div class="flex gap-2">
                                        <x-ui.icon icon="info" class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-blue-700 dark:text-blue-300/90 leading-relaxed">
                                            {{ trans('common.sync_info') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Code Input --}}
                                <div class="relative">
                                    <input 
                                        type="text"
                                        x-model="syncCode"
                                        @input="syncCode = syncCode.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, {{ config('default.anonymous_member_code_length', 4) }}); confirming = false; error = '';"
                                        class="w-full px-4 py-4 rounded-xl text-center font-mono text-2xl font-bold tracking-[0.2em] uppercase
                                               bg-secondary-50 dark:bg-black/30
                                               border-2 text-secondary-900 dark:text-white
                                               placeholder-secondary-300 dark:placeholder-white/20
                                               outline-none transition-all"
                                        :class="error 
                                            ? 'border-red-400 dark:border-red-500 focus:border-red-500 focus:ring-red-500/20' 
                                            : 'border-secondary-200 dark:border-white/10 focus:border-primary-500 dark:focus:border-primary-400 focus:ring-primary-500/20 dark:focus:ring-primary-400/20'"
                                        placeholder="{{ str_repeat('X', config('default.anonymous_member_code_length', 4)) }}"
                                        maxlength="{{ config('default.anonymous_member_code_length', 4) }}"
                                    >
                                </div>

                                {{-- Error Message --}}
                                <div x-show="error" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     class="p-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200/50 dark:border-red-500/20 text-left">
                                    <div class="flex gap-2">
                                        <x-ui.icon icon="x-circle" class="w-4 h-4 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-red-600 dark:text-red-300/90" x-text="error"></p>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex gap-3">
                                    <button
                                        @click="syncMode = false; syncCode = ''; error = ''; confirming = false;"
                                        class="flex-1 py-3 px-4 rounded-xl font-medium cursor-pointer
                                               text-secondary-600 dark:text-secondary-400
                                               bg-secondary-100 dark:bg-white/5
                                               hover:bg-secondary-200 dark:hover:bg-white/10
                                               active:scale-[0.98]
                                               transition-all duration-150"
                                    >
                                        {{ trans('common.cancel') }}
                                    </button>
                                    <button
                                        @click="
                                            if (!syncCode || syncCode.length < {{ config('default.anonymous_member_code_length', 4) }}) { 
                                                error = '{{ trans('member.code_required') }}'; 
                                                return; 
                                            }
                                            if (syncCode === deviceCode) { 
                                                error = '{{ trans('member.same_code') }}'; 
                                                return; 
                                            }
                                            if (!confirming) { 
                                                confirming = true; 
                                                return; 
                                            }
                                            syncing = true;
                                            error = '';
                                            
                                            const deviceUuid = localStorage.getItem('member_device_uuid') || '';
                                            
                                            fetch('{{ url('/api/' . app()->getLocale() . '/v1/member/session/switch') }}', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                                body: JSON.stringify({ code: syncCode.toUpperCase(), device_uuid: deviceUuid })
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data.success && data.device_uuid) {
                                                    localStorage.setItem('member_device_uuid', data.device_uuid);
                                                    localStorage.setItem('member_code', syncCode.toUpperCase());
                                                    localStorage.setItem('member_id', data.member?.id || '');
                                                    localStorage.setItem('member_last_sync', Date.now().toString());
                                                    document.cookie = 'member_device_uuid=' + data.device_uuid + '; path=/; max-age=31536000; SameSite=Lax';
                                                    window.location.reload();
                                                } else {
                                                    error = data.message || '{{ trans('member.code_not_found') }}';
                                                    syncing = false;
                                                    confirming = false;
                                                }
                                            })
                                            .catch(err => {
                                                console.error('Switch failed:', err);
                                                error = '{{ trans('member.code_not_found') }}';
                                                syncing = false;
                                                confirming = false;
                                            });
                                        "
                                        :disabled="syncing"
                                        class="flex-1 py-3 px-4 rounded-xl font-medium cursor-pointer
                                               text-white transition-all duration-150
                                               disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="confirming 
                                            ? 'bg-amber-500 hover:bg-amber-600 active:scale-[0.98]' 
                                            : 'bg-primary-600 hover:bg-primary-700 active:scale-[0.98]'"
                                    >
                                        <span x-show="!syncing && !confirming">{{ trans('common.switch_device') }}</span>
                                        <span x-show="confirming && !syncing">{{ trans('common.confirm_switch') }}</span>
                                        <span x-show="syncing" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ trans('common.switching') }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Link Email CTA (subtle) --}}
                        <div class="mt-6 pt-6 border-t border-secondary-100 dark:border-white/5">
                            <a href="{{ route('member.register') }}" 
                               class="inline-flex items-center gap-2 text-sm text-secondary-500 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                <x-ui.icon icon="mail" class="w-4 h-4" />
                                {{ trans('common.link_email_for_backup') }}
                                <x-ui.icon icon="arrow-right" class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@pushOnce('scripts')
<script>
/**
 * Member Identity Component — Alpine.js
 * 
 * Manages the identity badge state, QR code generation,
 * and device code copy functionality.
 */
function memberIdentity(config) {
    return {
        memberId: config.memberId,
        deviceCode: config.deviceCode,
        isAnonymous: config.isAnonymous,
        displayName: config.displayName,
        uniqueIdentifier: config.uniqueIdentifier,
        
        showQrModal: false,
        copied: false,
        
        init() {
            // Generate QR code when modal opens
            this.$watch('showQrModal', (open) => {
                if (open) {
                    this.$nextTick(() => this.generateQrCode());
                }
            });
        },
        
        generateQrCode() {
            const qrEl = this.$refs.qrcode;
            if (!qrEl) return;
            
            // Build URL for QR code - staff scan endpoint
            const baseUrl = window.location.origin;
            const locale = document.documentElement.lang || 'en-us';
            const identifier = this.uniqueIdentifier || this.deviceCode;
            
            // QR leads to member lookup (staff can scan to identify customer)
            const qrUrl = `${baseUrl}/${locale}/member/${identifier}`;
            
            // Use QR code API or library
            const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrUrl)}&qzone=2&margin=0`;
            qrEl.src = qrApiUrl;
        },
        
        async copyCode() {
            if (!this.deviceCode) return;
            
            try {
                await navigator.clipboard.writeText(this.deviceCode);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (err) {
                console.warn('Copy failed', err);
            }
        }
    };
}
</script>
@endPushOnce
