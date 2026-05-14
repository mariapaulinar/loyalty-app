{{--
Privacy & Data Management Tab
Premium GDPR-compliant data management interface.

Sections:
1. Partner Relationships - View and manage business connections (adapts to single/multi partner)
2. Download Your Data - Export all personal data (GDPR Article 20)
3. Delete Account - Permanently remove account (GDPR Article 17)

Design: Stripe/Revolut inspired with OTP verification for sensitive actions.
Uses reusable components: x-ui.modal, x-ui.otp-verify
--}}

@php
    use Illuminate\Support\Facades\DB;
    
    // Get member's partner relationships with aggregated data
    $memberId = $member->id;
    
    // Get partners from card_member (loyalty cards) - count cards only, points come from transactions
    $cardPartners = DB::table('card_member')
        ->join('cards', 'card_member.card_id', '=', 'cards.id')
        ->join('partners', 'cards.created_by', '=', 'partners.id')
        ->where('card_member.member_id', $memberId)
        ->select(
            'partners.id as partner_id',
            'partners.name as partner_name',
            DB::raw('COUNT(DISTINCT cards.id) as card_count')
        )
        ->groupBy('partners.id', 'partners.name')
        ->get()
        ->keyBy('partner_id');
    
    // Get points per partner from transactions table
    $pointsByPartner = DB::table('transactions')
        ->join('cards', 'transactions.card_id', '=', 'cards.id')
        ->join('partners', 'cards.created_by', '=', 'partners.id')
        ->where('transactions.member_id', $memberId)
        ->whereNull('transactions.deleted_at')
        ->select(
            'partners.id as partner_id',
            DB::raw('COALESCE(SUM(transactions.points), 0) as total_points')
        )
        ->groupBy('partners.id')
        ->get()
        ->keyBy('partner_id');
    
    // Get partners from stamp card memberships
    $stampPartners = DB::table('stamp_card_member')
        ->join('stamp_cards', 'stamp_card_member.stamp_card_id', '=', 'stamp_cards.id')
        ->join('partners', 'stamp_cards.created_by', '=', 'partners.id')
        ->where('stamp_card_member.member_id', $memberId)
        ->select(
            'partners.id as partner_id',
            'partners.name as partner_name',
            DB::raw('COUNT(DISTINCT stamp_cards.id) as stamp_card_count')
        )
        ->groupBy('partners.id', 'partners.name')
        ->get()
        ->keyBy('partner_id');
    
    // Get partners from member vouchers
    $voucherPartners = DB::table('member_voucher')
        ->join('vouchers', 'member_voucher.voucher_id', '=', 'vouchers.id')
        ->join('partners', 'vouchers.created_by', '=', 'partners.id')
        ->where('member_voucher.member_id', $memberId)
        ->select(
            'partners.id as partner_id',
            'partners.name as partner_name',
            DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count')
        )
        ->groupBy('partners.id', 'partners.name')
        ->get()
        ->keyBy('partner_id');
    
    // Merge all partner relationships
    $allPartnerIds = collect()
        ->merge($cardPartners->keys())
        ->merge($stampPartners->keys())
        ->merge($voucherPartners->keys())
        ->unique();
    
    // Get partner details with avatars
    $partners = \App\Models\Partner::whereIn('id', $allPartnerIds)->get()->keyBy('id');
    
    // Build consolidated partner data
    $partnerRelationships = $allPartnerIds->map(function ($partnerId) use ($partners, $cardPartners, $stampPartners, $voucherPartners, $pointsByPartner) {
        $partner = $partners->get($partnerId);
        if (!$partner) return null;
        
        $cardData = $cardPartners->get($partnerId);
        $stampData = $stampPartners->get($partnerId);
        $voucherData = $voucherPartners->get($partnerId);
        $pointsData = $pointsByPartner->get($partnerId);
        
        return [
            'id' => $partnerId,
            'name' => $partner->name,
            'avatar' => $partner->avatar ?? null,
            'avatar_small' => $partner->{'avatar-small'} ?? null,
            'cards' => $cardData->card_count ?? 0,
            'points' => $pointsData->total_points ?? 0,
            'stamp_cards' => $stampData->stamp_card_count ?? 0,
            'vouchers' => $voucherData->voucher_count ?? 0,
        ];
    })->filter()->values();
    
    // Determine if single or multiple partners
    $partnerCount = $partnerRelationships->count();
    $isSinglePartner = $partnerCount === 1;
    $hasPartners = $partnerCount > 0;
    $singlePartner = $isSinglePartner ? $partnerRelationships->first() : null;
@endphp

<div class="space-y-8" 
     x-data="privacyDataManager({
         csrfToken: '{{ csrf_token() }}',
         downloadUrl: '{{ route('member.privacy.download') }}',
         deleteAccountUrl: '{{ route('member.privacy.delete-account') }}',
         removeRelationshipUrl: '{{ route('member.privacy.remove-relationship') }}',
         memberEmail: '{{ $member->email }}',
     })">
    
    {{-- Section 1: Your Loyalty Program Data --}}
    <section class="space-y-4">
        {{-- Section Header --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <x-ui.icon icon="building-2" class="w-5 h-5 text-white" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.privacy_settings.your_loyalty_data') }}
                </h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    @if($hasPartners)
                        @if($isSinglePartner)
                            {{ trans('common.privacy_settings.single_partner_description', ['partner' => $singlePartner['name']]) }}
                        @else
                            {{ trans('common.privacy_settings.multi_partner_description') }}
                        @endif
                    @else
                        {{ trans('common.privacy_settings.no_data_description') }}
                    @endif
                </p>
            </div>
        </div>
        
        @if($hasPartners)
            @if($isSinglePartner)
                {{-- Single Partner View: Clean summary card --}}
                <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-5 border border-secondary-200 dark:border-secondary-700/50">
                    <div class="flex items-start gap-4">
                        {{-- Partner Avatar --}}
                        <div class="flex-shrink-0">
                            @if($singlePartner['avatar_small'])
                                <img src="{{ $singlePartner['avatar_small'] }}" 
                                     alt="{{ $singlePartner['name'] }}"
                                     class="w-14 h-14 rounded-xl object-cover ring-2 ring-white dark:ring-secondary-700 shadow-sm" />
                            @else
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 
                                            flex items-center justify-center text-white font-semibold text-xl
                                            ring-2 ring-white dark:ring-secondary-700 shadow-sm">
                                    {{ strtoupper(substr($singlePartner['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        {{-- Partner Info --}}
                        <div class="flex-1">
                            <h4 class="font-semibold text-secondary-900 dark:text-white text-lg">
                                {{ $singlePartner['name'] }}
                            </h4>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                                {{ trans('common.privacy_settings.your_data_with_partner') }}
                            </p>
                            
                            {{-- Stats Grid --}}
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                @if($singlePartner['cards'] > 0)
                                    <div class="bg-white dark:bg-secondary-700/50 rounded-lg p-3 border border-secondary-200 dark:border-secondary-600/50">
                                        <div class="flex items-center gap-2 text-secondary-500 dark:text-secondary-400">
                                            <x-ui.icon icon="credit-card" class="w-4 h-4" />
                                            <span class="text-xs font-medium uppercase tracking-wide">{{ trans('common.privacy_settings.loyalty_cards') }}</span>
                                        </div>
                                        <p class="text-xl font-bold text-secondary-900 dark:text-white mt-1">{{ $singlePartner['cards'] }}</p>
                                    </div>
                                @endif
                                @if($singlePartner['points'] != 0)
                                    <div class="bg-white dark:bg-secondary-700/50 rounded-lg p-3 border border-secondary-200 dark:border-secondary-600/50">
                                        <div class="flex items-center gap-2 text-secondary-500 dark:text-secondary-400">
                                            <x-ui.icon icon="coins" class="w-4 h-4" />
                                            <span class="text-xs font-medium uppercase tracking-wide">{{ trans('common.privacy_settings.points') }}</span>
                                        </div>
                                        <p class="text-xl font-bold text-secondary-900 dark:text-white mt-1">{{ number_format($singlePartner['points']) }}</p>
                                    </div>
                                @endif
                                @if($singlePartner['stamp_cards'] > 0)
                                    <div class="bg-white dark:bg-secondary-700/50 rounded-lg p-3 border border-secondary-200 dark:border-secondary-600/50">
                                        <div class="flex items-center gap-2 text-secondary-500 dark:text-secondary-400">
                                            <x-ui.icon icon="stamp" class="w-4 h-4" />
                                            <span class="text-xs font-medium uppercase tracking-wide">{{ trans('common.privacy_settings.stamp_cards') }}</span>
                                        </div>
                                        <p class="text-xl font-bold text-secondary-900 dark:text-white mt-1">{{ $singlePartner['stamp_cards'] }}</p>
                                    </div>
                                @endif
                                @if($singlePartner['vouchers'] > 0)
                                    <div class="bg-white dark:bg-secondary-700/50 rounded-lg p-3 border border-secondary-200 dark:border-secondary-600/50">
                                        <div class="flex items-center gap-2 text-secondary-500 dark:text-secondary-400">
                                            <x-ui.icon icon="ticket" class="w-4 h-4" />
                                            <span class="text-xs font-medium uppercase tracking-wide">{{ trans('common.privacy_settings.vouchers') }}</span>
                                        </div>
                                        <p class="text-xl font-bold text-secondary-900 dark:text-white mt-1">{{ $singlePartner['vouchers'] }}</p>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Remove Data Button --}}
                            <div class="mt-4 pt-4 border-t border-secondary-200 dark:border-secondary-600/50">
                                <button type="button"
                                        @click="selectPartnerForRemoval('{{ $singlePartner['id'] }}', '{{ addslashes($singlePartner['name']) }}')"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium 
                                               text-secondary-500 dark:text-secondary-400
                                               bg-white dark:bg-secondary-700 
                                               border border-secondary-200 dark:border-secondary-600
                                               rounded-lg shadow-sm
                                               hover:text-red-600 dark:hover:text-red-400
                                               hover:border-red-200 dark:hover:border-red-500/30
                                               hover:bg-red-50 dark:hover:bg-red-500/10
                                               transition-all duration-200">
                                    <x-ui.icon icon="trash-2" class="w-3.5 h-3.5" />
                                    {{ trans('common.privacy_settings.delete_partner_data') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Multiple Partners View: List with remove options --}}
                <div class="grid gap-3">
                    @foreach($partnerRelationships as $relationship)
                        <div class="group relative bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4 
                                    border border-secondary-200 dark:border-secondary-700/50
                                    hover:border-secondary-300 dark:hover:border-secondary-600
                                    transition-all duration-200">
                            <div class="flex items-center gap-4">
                                {{-- Partner Avatar --}}
                                <div class="flex-shrink-0">
                                    @if($relationship['avatar_small'])
                                        <img src="{{ $relationship['avatar_small'] }}" 
                                             alt="{{ $relationship['name'] }}"
                                             class="w-12 h-12 rounded-xl object-cover ring-2 ring-white dark:ring-secondary-700 shadow-sm" />
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 
                                                    flex items-center justify-center text-white font-semibold text-lg
                                                    ring-2 ring-white dark:ring-secondary-700 shadow-sm">
                                            {{ strtoupper(substr($relationship['name'], 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Partner Info --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-secondary-900 dark:text-white truncate">
                                        {{ $relationship['name'] }}
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-sm text-secondary-500 dark:text-secondary-400">
                                        @if($relationship['cards'] > 0)
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon icon="credit-card" class="w-3.5 h-3.5" />
                                                {{ trans_choice('common.cards_count', $relationship['cards'], ['count' => $relationship['cards']]) }}
                                            </span>
                                        @endif
                                        @if($relationship['points'] != 0)
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon icon="coins" class="w-3.5 h-3.5" />
                                                {{ number_format($relationship['points']) }} {{ trans('common.privacy_settings.pts') }}
                                            </span>
                                        @endif
                                        @if($relationship['stamp_cards'] > 0)
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon icon="stamp" class="w-3.5 h-3.5" />
                                                {{ $relationship['stamp_cards'] }} stamp {{ Str::plural('card', $relationship['stamp_cards']) }}
                                            </span>
                                        @endif
                                        @if($relationship['vouchers'] > 0)
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon icon="ticket" class="w-3.5 h-3.5" />
                                                {{ $relationship['vouchers'] }} {{ Str::plural('voucher', $relationship['vouchers']) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Remove Button --}}
                                <div class="flex-shrink-0">
                                    <button type="button"
                                            @click="selectPartnerForRemoval('{{ $relationship['id'] }}', '{{ addslashes($relationship['name']) }}')"
                                            class="px-3 py-1.5 text-xs font-medium text-secondary-500 dark:text-secondary-400
                                                   bg-white dark:bg-secondary-700 
                                                   border border-secondary-200 dark:border-secondary-600
                                                   rounded-lg shadow-sm
                                                   hover:text-red-600 dark:hover:text-red-400
                                                   hover:border-red-200 dark:hover:border-red-500/30
                                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                                   opacity-0 group-hover:opacity-100
                                                   transition-all duration-200">
                                        {{ trans('common.privacy_settings.remove') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <x-ui.empty-state
                icon="building-2"
                :title="trans('common.privacy_settings.no_loyalty_data')"
            />
        @endif
    </section>
    
    {{-- Divider --}}
    <div class="border-t border-secondary-200 dark:border-secondary-700/50"></div>
    
    {{-- Section 2: Download Your Data --}}
    <section class="space-y-4">
        {{-- Section Header --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <x-ui.icon icon="download" class="w-5 h-5 text-white" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.privacy_settings.download_data_title') }}
                </h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.privacy_settings.download_data_description') }}
                </p>
            </div>
        </div>
        
        {{-- Download Card --}}
        <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4 border border-secondary-200 dark:border-secondary-700/50">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 
                                flex items-center justify-center shadow-sm">
                        <x-ui.icon icon="file-json" class="w-5 h-5 text-secondary-500 dark:text-secondary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ trans('common.privacy_settings.personal_data_export') }}</p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.privacy_settings.json_format') }}</p>
                    </div>
                </div>
                <button type="button"
                        @click="downloadData()"
                        :disabled="downloading"
                        class="inline-flex items-center gap-2 px-4 py-2
                               text-sm font-medium text-white
                               bg-gradient-to-r from-emerald-600 to-teal-600
                               hover:from-emerald-500 hover:to-teal-500
                               rounded-lg shadow-sm shadow-emerald-500/20
                               disabled:opacity-50 disabled:cursor-not-allowed
                               transition-all duration-200 active:scale-[0.98]">
                    <template x-if="!downloading">
                        <x-ui.icon icon="download" class="w-4 h-4" />
                    </template>
                    <template x-if="downloading">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="downloading ? '{{ trans('common.privacy_settings.preparing') }}' : '{{ trans('common.privacy_settings.download') }}'"></span>
                </button>
            </div>
        </div>
    </section>
    
    {{-- Divider --}}
    <div class="border-t border-secondary-200 dark:border-secondary-700/50"></div>
    
    {{-- Section 3: Delete Account (Danger Zone) --}}
    <section class="space-y-4">
        {{-- Section Header --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-500/20">
                <x-ui.icon icon="trash-2" class="w-5 h-5 text-white" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.privacy_settings.delete_account_title') }}
                </h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.privacy_settings.delete_account_description') }}
                </p>
            </div>
        </div>
        
        {{-- Delete Account Card --}}
        <div class="bg-red-50 dark:bg-red-500/5 rounded-xl p-4 border border-red-200 dark:border-red-500/20">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white dark:bg-secondary-800 border border-red-200 dark:border-red-500/30 
                                flex items-center justify-center shadow-sm">
                        <x-ui.icon icon="user-x" class="w-5 h-5 text-red-500 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-red-900 dark:text-red-300">{{ trans('common.privacy_settings.delete_warning') }}</p>
                    </div>
                </div>
                <button type="button"
                        @click="showDeleteModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2
                               text-sm font-medium text-red-700 dark:text-red-300
                               bg-white dark:bg-red-500/10
                               border border-red-300 dark:border-red-500/30
                               hover:bg-red-50 dark:hover:bg-red-500/20
                               hover:border-red-400 dark:hover:border-red-500/50
                               rounded-lg shadow-sm
                               transition-all duration-200">
                    <x-ui.icon icon="trash-2" class="w-4 h-4" />
                    {{ trans('common.privacy_settings.delete_account_button') }}
                </button>
            </div>
        </div>
    </section>
    
    {{-- Remove Partner Data Modal with OTP Verification --}}
    <x-ui.modal show="showRemovePartnerModal" max-width="max-w-md" :closeable="false">
        <div x-show="!partnerOtpVerified">
            {{-- OTP Verification Step --}}
            <div class="text-center mb-6">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-500/20 
                            flex items-center justify-center">
                    <x-ui.icon icon="shield-alert" class="w-7 h-7 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.privacy_settings.verify_identity_title') }}
                </h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.privacy_settings.otp_required_for_partner_removal') }}
                </p>
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mt-2" x-text="selectedPartnerName"></p>
            </div>
            
            {{-- Embedded OTP Component --}}
            <div @otp-verified="partnerOtpToken = $event.detail.token; partnerOtpVerified = true">
                <x-ui.otp-verify 
                    guard="member" 
                    :email="$member->email"
                    :show-title="false"
                    :compact="true"
                />
            </div>
            
            <div class="mt-6 text-center">
                <button type="button"
                        @click="closePartnerRemovalModal()"
                        class="text-sm text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300">
                    {{ trans('common.cancel') }}
                </button>
            </div>
        </div>
        
        <div x-show="partnerOtpVerified" x-cloak>
            {{-- Final Confirmation Step --}}
            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-500/20 
                            flex items-center justify-center">
                    <x-ui.icon icon="alert-triangle" class="w-7 h-7 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.privacy_settings.remove_relationship_title') }}
                </h3>
                <p class="text-sm text-secondary-600 dark:text-secondary-300 mb-6">
                    {{ trans('common.privacy_settings.remove_data_warning_generic') }}
                    <span class="font-semibold" x-text="selectedPartnerName"></span>.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button type="button"
                            @click="closePartnerRemovalModal()"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300
                                   bg-white dark:bg-secondary-700 
                                   border border-secondary-300 dark:border-secondary-600
                                   rounded-lg shadow-sm
                                   hover:bg-secondary-50 dark:hover:bg-secondary-600
                                   transition-colors duration-150">
                        {{ trans('common.cancel') }}
                    </button>
                    <button type="button"
                            @click="removeRelationship()"
                            :disabled="loading"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2
                                   text-sm font-medium text-white
                                   bg-gradient-to-r from-red-600 to-rose-600
                                   hover:from-red-500 hover:to-rose-500
                                   rounded-lg shadow-sm shadow-red-500/20
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   transition-all duration-200 active:scale-[0.98]">
                        <x-ui.icon x-show="!loading" icon="trash-2" class="w-4 h-4" />
                        <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? '{{ trans('common.removing') }}' : '{{ trans('common.privacy_settings.remove_confirm') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.modal>
    
    {{-- Delete Account Modal with OTP Verification --}}
    <x-ui.modal show="showDeleteModal" max-width="max-w-md" :closeable="false">
        <div x-show="!otpVerified">
            {{-- OTP Verification Step --}}
            <div class="text-center mb-6">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-500/20 
                            flex items-center justify-center">
                    <x-ui.icon icon="shield-alert" class="w-7 h-7 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.privacy_settings.verify_identity_title') }}
                </h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.privacy_settings.otp_required_for_deletion') }}
                </p>
            </div>
            
            {{-- Embedded OTP Component --}}
            <div @otp-verified="otpToken = $event.detail.token; otpVerified = true">
                <x-ui.otp-verify 
                    guard="member" 
                    :email="$member->email"
                    :show-title="false"
                    :compact="true"
                />
            </div>
            
            <div class="mt-6 text-center">
                <button type="button"
                        @click="closeDeleteModal()"
                        class="text-sm text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300">
                    {{ trans('common.cancel') }}
                </button>
            </div>
        </div>
        
        <div x-show="otpVerified" x-cloak>
            {{-- Final Confirmation Step --}}
            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-500/20 
                            flex items-center justify-center">
                    <x-ui.icon icon="alert-octagon" class="w-7 h-7 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-300 mb-2">
                    {{ trans('common.privacy_settings.final_confirmation_title') }}
                </h3>
                <p class="text-sm text-red-700 dark:text-red-400 mb-6">
                    {{ trans('common.privacy_settings.final_confirmation_description') }}
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button type="button"
                            @click="closeDeleteModal()"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300
                                   bg-white dark:bg-secondary-700 
                                   border border-secondary-300 dark:border-secondary-600
                                   rounded-lg shadow-sm
                                   hover:bg-secondary-50 dark:hover:bg-secondary-600
                                   transition-colors duration-150">
                        {{ trans('common.cancel') }}
                    </button>
                    <button type="button"
                            @click="deleteAccount()"
                            :disabled="deletingAccount"
                            class="inline-flex items-center gap-2 px-4 py-2
                                   text-sm font-medium text-white
                                   bg-gradient-to-r from-red-600 to-rose-600
                                   hover:from-red-500 hover:to-rose-500
                                   rounded-lg shadow-sm shadow-red-500/20
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   transition-all duration-200 active:scale-[0.98]">
                        <template x-if="!deletingAccount">
                            <x-ui.icon icon="trash-2" class="w-4 h-4" />
                        </template>
                        <template x-if="deletingAccount">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="deletingAccount ? '{{ trans('common.deleting') }}' : '{{ trans('common.privacy_settings.delete_confirm') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.modal>
</div>

{{-- Alpine.js Component --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('privacyDataManager', (config) => ({
        csrfToken: config.csrfToken,
        downloadUrl: config.downloadUrl,
        deleteAccountUrl: config.deleteAccountUrl,
        removeRelationshipUrl: config.removeRelationshipUrl,
        memberEmail: config.memberEmail,
        loading: false,
        downloading: false,
        deletingAccount: false,
        showDeleteModal: false,
        showRemovePartnerModal: false,
        // Partner removal state
        selectedPartnerId: null,
        selectedPartnerName: '',
        partnerOtpVerified: false,
        partnerOtpToken: null,
        // Account deletion state
        otpVerified: false,
        otpToken: null,
        
        selectPartnerForRemoval(partnerId, partnerName) {
            this.selectedPartnerId = partnerId;
            this.selectedPartnerName = partnerName;
            // DON'T reset OTP state - if user already verified, let them proceed
            this.showRemovePartnerModal = true;
        },
        
        closePartnerRemovalModal() {
            // Just close the modal, preserve OTP verification state
            this.showRemovePartnerModal = false;
        },
        
        resetPartnerRemoval() {
            // Full reset - called after successful deletion
            this.selectedPartnerId = null;
            this.selectedPartnerName = '';
            this.partnerOtpVerified = false;
            this.partnerOtpToken = null;
            this.showRemovePartnerModal = false;
        },
        
        resetOtp() {
            this.otpVerified = false;
            this.otpToken = null;
        },
        
        closeDeleteModal() {
            // Just close the modal, preserve OTP verification state
            this.showDeleteModal = false;
        },
        
        async removeRelationship() {
            if (!this.partnerOtpToken || !this.selectedPartnerId) {
                console.error('OTP token or partner ID missing');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(this.removeRelationshipUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        partner_id: this.selectedPartnerId,
                        otp_token: this.partnerOtpToken
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Reset state before reload to prevent cleanup errors
                    this.showRemovePartnerModal = false;
                    this.loading = false;
                    if (window.appSuccess) {
                        window.appSuccess(data.message || '{{ trans('common.privacy_settings.relationship_removed') }}');
                    }
                    // Use replace to avoid history issues
                    setTimeout(() => window.location.href = window.location.href, 1200);
                } else {
                    if (window.appAlert) {
                        window.appAlert('Error', data.message || '{{ trans('common.error_occurred') }}');
                    }
                }
            } catch (e) {
                console.error('Remove relationship error:', e);
                if (window.appAlert) {
                    window.appAlert('Error', '{{ trans('common.error_occurred') }}');
                }
            } finally {
                this.loading = false;
            }
        },
        
        async downloadData() {
            this.downloading = true;
            try {
                const response = await fetch(this.downloadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'my-data-export.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    
                    if (window.appSuccess) {
                        window.appSuccess('{{ trans('common.privacy_settings.download_ready') }}');
                    }
                } else {
                    const data = await response.json();
                    if (window.appAlert) {
                        window.appAlert('Error', data.message || '{{ trans('common.error_occurred') }}');
                    }
                }
            } catch (e) {
                console.error('Download error:', e);
                if (window.appAlert) {
                    window.appAlert('Error', '{{ trans('common.error_occurred') }}');
                }
            } finally {
                this.downloading = false;
            }
        },
        
        async deleteAccount() {
            this.deletingAccount = true;
            try {
                const response = await fetch(this.deleteAccountUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ otp_token: this.otpToken })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect || '/';
                } else {
                    if (window.appAlert) {
                        window.appAlert('Error', data.message || '{{ trans('common.error_occurred') }}');
                    }
                }
            } catch (e) {
                console.error('Delete account error:', e);
                if (window.appAlert) {
                    window.appAlert('Error', '{{ trans('common.error_occurred') }}');
                }
            } finally {
                this.deletingAccount = false;
            }
        }
    }));
});
</script>
