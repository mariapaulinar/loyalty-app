<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PrivacyController
 *
 * Handles GDPR-compliant privacy operations for members:
 * - Data portability (Article 20): Download all personal data
 * - Right to erasure (Article 17): Delete account
 * - Partner relationship management: Remove specific business relationships
 *
 * All operations are scoped to the authenticated member and include
 * comprehensive audit logging for compliance purposes.
 */
class PrivacyController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}
    /**
     * Download all member data as JSON (GDPR Article 20 - Data Portability).
     *
     * Exports all personal data including:
     * - Profile information
     * - Loyalty card memberships and points
     * - Stamp card enrollments
     * - Voucher history
     * - Transaction history
     * - Referral data
     */
    public function downloadData(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = Auth::guard('member')->user();

        try {
            // Compile all member data
            $exportData = [
                'export_info' => [
                    'generated_at' => now()->toIso8601String(),
                    'format_version' => '1.0',
                    'member_id' => $member->id,
                ],
                'profile' => [
                    'name' => $member->name,
                    'email' => $member->email,
                    'locale' => $member->locale,
                    'time_zone' => $member->time_zone,
                    'accepts_emails' => $member->accepts_emails,
                    'created_at' => $member->created_at?->toIso8601String(),
                    'updated_at' => $member->updated_at?->toIso8601String(),
                ],
                'loyalty_cards' => $this->getCardData($member),
                'stamp_cards' => $this->getStampCardData($member),
                'vouchers' => $this->getVoucherData($member),
                'transactions' => $this->getTransactionData($member),
                'referrals' => $this->getReferralData($member),
            ];

            // Log the export for audit purposes
            $this->activityLog->log(
                'Member exported personal data',
                $member,
                'data_export',
                ['ip_address' => $request->ip()],
                'privacy'
            );

            // Return as downloadable JSON
            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="my-data-export-'.now()->format('Y-m-d').'.json"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Remove relationship with a specific partner.
     *
     * Deletes all data associated with the member-partner relationship:
     * - Card memberships
     * - Stamp card enrollments
     * - Voucher assignments
     * - Transaction history with that partner
     */
    public function removeRelationship(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|string|exists:partners,id',
            'otp_token' => 'required|string',
        ]);

        /** @var Member $member */
        $member = Auth::guard('member')->user();
        $partnerId = $request->input('partner_id');
        
        // Validate OTP token
        $sessionKey = 'otp_verified_member_profile_update';
        $sessionToken = session($sessionKey);
        $otpToken = $request->input('otp_token');
        
        if (!$otpToken || !$sessionToken || !hash_equals($sessionToken, $otpToken)) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.profile_otp_required'),
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Get partner name for logging
            $partner = DB::table('partners')->where('id', $partnerId)->first();
            $partnerName = $partner->name ?? 'Unknown';

            // Get all card IDs belonging to this partner
            $cardIds = DB::table('cards')->where('created_by', $partnerId)->pluck('id');
            
            // Hard delete card memberships
            DB::table('card_member')
                ->where('member_id', $member->id)
                ->whereIn('card_id', $cardIds)
                ->delete();

            // Hard delete point transactions for this partner
            DB::table('transactions')
                ->where('member_id', $member->id)
                ->where('created_by', $partnerId)
                ->delete();

            // Get all stamp card IDs belonging to this partner
            $stampCardIds = DB::table('stamp_cards')->where('created_by', $partnerId)->pluck('id');
            
            // Hard delete stamp card memberships
            DB::table('stamp_card_member')
                ->where('member_id', $member->id)
                ->whereIn('stamp_card_id', $stampCardIds)
                ->delete();
            
            // Hard delete stamp transactions for this partner's stamp cards
            DB::table('stamp_transactions')
                ->where('member_id', $member->id)
                ->whereIn('stamp_card_id', $stampCardIds)
                ->delete();

            // Get all voucher IDs belonging to this partner
            $voucherIds = DB::table('vouchers')->where('created_by', $partnerId)->pluck('id');
            
            // Hard delete voucher assignments
            DB::table('member_voucher')
                ->where('member_id', $member->id)
                ->whereIn('voucher_id', $voucherIds)
                ->delete();
            
            // Hard delete voucher redemptions
            DB::table('voucher_redemptions')
                ->where('member_id', $member->id)
                ->whereIn('voucher_id', $voucherIds)
                ->delete();

            DB::commit();

            // Log for audit
            $this->activityLog->log(
                'Member removed partner relationship',
                $member,
                'partner_data_deleted',
                [
                    'partner_id' => $partnerId,
                    'partner_name' => $partnerName,
                    'ip_address' => $request->ip(),
                ],
                'privacy'
            );

            return response()->json([
                'success' => true,
                'message' => trans('common.relationship_removed', ['partner' => $partnerName]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => trans('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Delete member account (GDPR Article 17 - Right to Erasure).
     *
     * Permanently removes the member account and all associated data.
     * This action is irreversible.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'otp_token' => 'required|string',
        ]);

        /** @var Member $member */
        $member = Auth::guard('member')->user();
        
        // Validate OTP token
        $sessionKey = 'otp_verified_member_profile_update';
        $sessionToken = session($sessionKey);
        $otpToken = $request->input('otp_token');
        
        if (!$otpToken || !$sessionToken || !hash_equals($sessionToken, $otpToken)) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.profile_otp_required'),
            ], 403);
        }

        try {
            DB::beginTransaction();

            // The Member model should have cascading deletes configured,
            // but we'll explicitly handle critical relationships

            // Remove card memberships
            DB::table('card_member')->where('member_id', $member->id)->delete();

            // Remove stamp card memberships
            DB::table('stamp_card_member')->where('member_id', $member->id)->delete();

            // Remove voucher assignments
            DB::table('member_voucher')->where('member_id', $member->id)->delete();
            
            // Remove voucher redemptions
            DB::table('voucher_redemptions')->where('member_id', $member->id)->delete();

            // Hard delete transactions (complete data erasure)
            DB::table('transactions')->where('member_id', $member->id)->delete();
            
            // Hard delete stamp transactions
            DB::table('stamp_transactions')->where('member_id', $member->id)->delete();

            // Delete referral codes owned by this member
            DB::table('member_referral_codes')->where('member_id', $member->id)->delete();

            // Store member email before anonymization for audit
            $originalEmail = $member->email;

            // Anonymize the member instead of hard delete to preserve referral chain integrity
            // Other members who were referred by this member will still have valid referral records
            $anonymizedEmail = 'deleted_' . $member->id . '@anonymous.invalid';
            $member->update([
                'name' => '[Deleted]',
                'display_name' => null,
                'email' => $anonymizedEmail,
                'password' => null,
                'phone' => null,
                'phone_e164' => null,
                'phone_prefix' => null,
                'phone_country' => null,
                'birthday' => null,
                'meta' => null,
                'remember_token' => null,
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'is_active' => false,
                'accepts_emails' => false,
                'accepts_text_messages' => false,
                'deleted_at' => now(),
            ]);
            
            // Delete avatar media
            $member->clearMediaCollection('avatar');

            // Log account deletion for GDPR audit trail (before commit to ensure logging)
            $this->activityLog->log(
                'Member account deleted',
                $member,
                'account_deleted',
                [
                    'original_email_hash' => hash('sha256', $originalEmail),
                    'ip_address' => $request->ip(),
                ],
                'privacy'
            );

            DB::commit();

            // Log out the member
            Auth::guard('member')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Your account has been deleted.',
                'redirect' => '/',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => trans('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Get loyalty card data for export.
     */
    private function getCardData(Member $member): array
    {
        return DB::table('card_member')
            ->join('cards', 'card_member.card_id', '=', 'cards.id')
            ->join('partners', 'cards.created_by', '=', 'partners.id')
            ->where('card_member.member_id', $member->id)
            ->select([
                'cards.name as card_name',
                'partners.name as partner_name',
                'card_member.created_at as joined_at',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get stamp card data for export.
     */
    private function getStampCardData(Member $member): array
    {
        return DB::table('stamp_card_member')
            ->join('stamp_cards', 'stamp_card_member.stamp_card_id', '=', 'stamp_cards.id')
            ->join('partners', 'stamp_cards.created_by', '=', 'partners.id')
            ->where('stamp_card_member.member_id', $member->id)
            ->select([
                'stamp_cards.name as card_name',
                'partners.name as partner_name',
                'stamp_card_member.current_stamps',
                'stamp_card_member.lifetime_stamps',
                'stamp_card_member.enrolled_at as joined_at',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get voucher data for export.
     */
    private function getVoucherData(Member $member): array
    {
        return DB::table('member_voucher')
            ->join('vouchers', 'member_voucher.voucher_id', '=', 'vouchers.id')
            ->join('partners', 'vouchers.created_by', '=', 'partners.id')
            ->where('member_voucher.member_id', $member->id)
            ->select([
                'vouchers.title as voucher_title',
                'partners.name as partner_name',
                'member_voucher.created_at as collected_at',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get transaction data for export.
     */
    private function getTransactionData(Member $member): array
    {
        // Only export member-facing data, exclude internal fields (notes, meta, staff details)
        return DB::table('transactions')
            ->where('transactions.member_id', $member->id)
            ->whereNull('transactions.deleted_at')
            ->select([
                'transactions.points',
                'transactions.points_used',
                'transactions.event',
                'transactions.purchase_amount',
                'transactions.currency',
                'transactions.card_title',
                'transactions.reward_title',
                'transactions.partner_name',
                'transactions.created_at',
            ])
            ->orderBy('transactions.created_at', 'desc')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get referral data for export.
     */
    private function getReferralData(Member $member): array
    {
        // Only export simple referral info - role, status, and date
        $asReferrer = DB::table('referrals')
            ->where('referrer_id', $member->id)
            ->select([
                DB::raw("'referrer' as role"),
                'status',
                'created_at',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        $asReferee = DB::table('referrals')
            ->where('referee_id', $member->id)
            ->select([
                DB::raw("'referee' as role"),
                'status',
                'created_at',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        return [
            'as_referrer' => $asReferrer,
            'as_referee' => $asReferee,
        ];
    }
}
