<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Http\Controllers\Integration\Shopify\ShopifyOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', '\App\Http\Controllers\I18n\LocaleController@redirectToLocale')->name('redir.locale');

/*
|--------------------------------------------------------------------------
| Shopify OAuth Callback (PUBLIC)
|--------------------------------------------------------------------------
|
| Shopify redirects the user's browser to this endpoint after authorization.
| It MUST be public (no session auth / Sanctum) and relies on:
| - HMAC validation
| - Encrypted state (club_id, shop, ts, nonce)
| - Single-use nonce (cached for 10 minutes)
|
| @see App\Services\Integration\Shopify\OAuthService
|
*/
Route::get('/shopify/callback', [ShopifyOAuthController::class, 'callback'])->name('shopify.oauth.callback');

/*
|--------------------------------------------------------------------------
| Referral Short Link (PUBLIC, LOCALE-AGNOSTIC)
|--------------------------------------------------------------------------
|
| Short, shareable referral URLs without locale: /r/CODE
| Detects user's preferred language and redirects to localized landing page.
| This keeps share URLs clean while ensuring proper i18n support.
|
| Flow: /r/ABC123 → (detect locale) → /en-us/r/ABC123 → (show landing page)
|
*/
Route::get('/r/{code}', [\App\Http\Controllers\ReferralLandingController::class, 'redirect'])->name('referral.redirect');

Route::prefix('{locale}')->where(['locale' => '[a-z]{2}-[a-z]{2}'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Email Unsubscribe (PUBLIC, SIGNED)
    |--------------------------------------------------------------------------
    |
    | One-click unsubscribe from email campaigns.
    | Uses signed URLs for security (no auth required).
    | Sets member.accepts_emails = false.
    | Located inside locale group so URLs include locale segment.
    |
    */
    Route::get('/email/unsubscribe/{member}', [\App\Http\Controllers\EmailUnsubscribeController::class, 'unsubscribe'])
        ->middleware('signed')
        ->name('email.unsubscribe');
    Route::get('scripts/language.js', '\App\Http\Controllers\Javascript\IncludeController@language')->name('javascript.include.language');

    // Set cookie consent
    Route::post('/set-cookie/{value?}', '\App\Http\Controllers\Cookie\CookieController@setConsentCookie')->name('set.consent.cookie.post');

    // PWA routes (outside installed middleware so they work during setup)
    Route::get('manifest.json', '\App\Http\Controllers\Pwa\ManifestController@show')->name('pwa.manifest');
    Route::get('splash/{width?}/{height?}', '\App\Http\Controllers\Pwa\SplashController@show')
        ->where(['width' => '[0-9]+', 'height' => '[0-9]+'])
        ->name('pwa.splash');
    Route::get('offline', function () {
        return view('pwa.offline');
    })->name('pwa.offline');

    Route::group(['middleware' => 'installed', 'namespace' => '\App\Http\Controllers'], function () {

        // Authenticated member routes (auto-creates anonymous members)
        Route::group(['middleware' => ['member.auth.auto', 'member.role:1,2,3']], function () {
            // My Cards (Consolidated Dashboard + Wallet)
            Route::get('my-cards', 'Member\PageController@dashboard')->name('member.cards');

            // Claim reward
            Route::get('card/{card_id}/{reward_id}/claim', 'Member\CardController@showClaimReward')->name('member.card.reward.claim');

            // Enter Code
            Route::get('enter-code', [\App\Http\Controllers\Member\CodeController::class, 'showRedeemCode'])->name('member.code.enter');
            Route::post('enter-code', [\App\Http\Controllers\Member\CodeController::class, 'postRedeemCode'])->name('member.code.enter.post');

            // Referrals
            Route::get('referrals', 'Member\ReferralController@index')->name('member.referrals');

            // Generation of the request link by a member:
            Route::get('request-points/generate/{card_identifier?}', [\App\Http\Controllers\Member\PointRequestController::class, 'showGenerateRequest'])->name('member.request.points.generate');
            Route::post('request-points/generate', [\App\Http\Controllers\Member\PointRequestController::class, 'postGenerateRequest'])->name('member.request.points.generate.post');


            // Data Definition
            Route::get('manage/{name}', 'Data\ListController@showList')->name('member.data.list');
            Route::get('manage/{name}/suggest', 'Data\ListController@suggest')->name('member.data.suggest');
            Route::get('manage/export/{name}', 'Data\ExportController@exportList')->name('member.data.export');
            Route::post('manage/{name}/delete/{id?}', 'Data\DeleteController@postDelete')->name('member.data.delete.post');
            Route::get('manage/{name}/view/{id}', 'Data\ViewController@showViewItem')->name('member.data.view');
            Route::get('manage/{name}/insert', 'Data\InsertController@showInsertItem')->name('member.data.insert');
            Route::post('manage/{name}/insert', 'Data\InsertController@postInsertItem')->name('member.data.insert.post');
            Route::get('manage/{name}/edit/{id}', 'Data\EditController@showEditItem')->name('member.data.edit');
            Route::post('manage/{name}/edit/{id}', 'Data\EditController@postEditItem')->name('member.data.edit.post');
            Route::get('manage/{name}/impersonate/{guard}/{id}', 'Data\AuthController@impersonate')->name('member.data.impersonate');

            // Profile OTP Verification
            Route::post('profile/otp/send', 'Data\ProfileOtpController@send')->name('member.profile.otp.send');
            Route::post('profile/otp/verify', 'Data\ProfileOtpController@verify')->name('member.profile.otp.verify');

            // Privacy & Data Management (GDPR Compliance)
            Route::post('privacy/download', 'Member\PrivacyController@downloadData')->name('member.privacy.download');
            Route::post('privacy/remove-relationship', 'Member\PrivacyController@removeRelationship')->name('member.privacy.remove-relationship');
            Route::post('privacy/delete-account', 'Member\PrivacyController@deleteAccount')->name('member.privacy.delete-account');

            // Account Switching (for device linking)
            Route::post('account/switch', 'Member\AccountController@switch')->name('member.account.switch');
            Route::post('account/login-email/send', 'Member\AccountController@sendLoginOtp')->name('member.account.login-email.send');
            Route::post('account/login-email/verify', 'Member\AccountController@verifyLoginOtp')->name('member.account.login-email.verify');
        });

        /*
        |--------------------------------------------------------------------------
        | Referral Landing Page (LOCALIZED)
        |--------------------------------------------------------------------------
        |
        | The actual referral landing page with full i18n support.
        | Users are redirected here from the short /r/CODE URL.
        | Displays campaign details, referrer info, and registration CTA.
        |
        */
        Route::get('r/{code}', [\App\Http\Controllers\ReferralLandingController::class, 'show'])->name('referral.landing');

        // ─────────────────────────────────────────────────────────────────────────
        // PUBLIC MEMBER ROUTES (with auto-authentication)
        // ─────────────────────────────────────────────────────────────────────────
        // These routes are publicly accessible but auto-create anonymous members
        // when anonymous mode is enabled. If disabled, visitors see guest content.
        Route::middleware(['member.auth.auto'])->group(function () {
            // Homepage / Member Index
            Route::get('/', 'Member\PageController@index')->name('member.index');

            // Loyalty Cards
            Route::get('card/{card_id}', 'Member\CardController@showCard')->name('member.card')->where(['card_id' => '[a-zA-Z0-9\-]+']);
            Route::get('card/{card_id}/{reward_id}', 'Member\CardController@showReward')->name('member.card.reward')->where(['card_id' => '[a-zA-Z0-9\-]+', 'reward_id' => '[a-zA-Z0-9\-]+']);
            Route::get('follow/{card_id}', 'Member\CardController@follow')->name('member.card.follow')->where(['card_id' => '[a-zA-Z0-9\-]+']);
            Route::get('unfollow/{card_id}', 'Member\CardController@unfollow')->name('member.card.unfollow')->where(['card_id' => '[a-zA-Z0-9\-]+']);

            // Stamp Cards
            Route::get('stamp-card/{stamp_card_id}', 'Member\StampCardController@show')->name('member.stamp-card')->where(['stamp_card_id' => '[a-zA-Z0-9\-]+']);
            Route::get('stamp-card/{stamp_card_id}/enroll', 'Member\StampCardController@enroll')->name('member.stamp-card.enroll')->where(['stamp_card_id' => '[a-zA-Z0-9\-]+']);
            Route::get('stamp-card/{stamp_card_id}/unenroll', 'Member\StampCardController@unenroll')->name('member.stamp-card.unenroll')->where(['stamp_card_id' => '[a-zA-Z0-9\-]+']);

            // Vouchers
            Route::get('voucher/{voucher_id}', 'Member\VoucherController@show')->name('member.voucher')->where(['voucher_id' => '[a-zA-Z0-9\-]+']);
            Route::get('voucher/{voucher_id}/save', 'Member\VoucherController@save')->name('member.voucher.save')->where(['voucher_id' => '[a-zA-Z0-9\-]+']);
            Route::get('voucher/{voucher_id}/unsave', 'Member\VoucherController@unsave')->name('member.voucher.unsave')->where(['voucher_id' => '[a-zA-Z0-9\-]+']);

            // Batch Voucher Claiming (QR Code)
            Route::get('claim-voucher/{batchId}/{token}', [\App\Http\Controllers\Member\VoucherClaimController::class, 'show'])->name('member.vouchers.claim');
            Route::post('claim-voucher/{batchId}/{token}', [\App\Http\Controllers\Member\VoucherClaimController::class, 'claim'])->name('member.vouchers.claim.process');

            // ─────────────────────────────────────────────────────────────
            // Point Request — Shareable Link (public access)
            // ─────────────────────────────────────────────────────────────
            // These routes MUST be in the public group because request links
            // are shareable URLs. Unauthenticated visitors are redirected to
            // login by the controller, not blocked with a 403.
            // The generate routes remain in the role-restricted group above.
            Route::get('request-points/{request_identifier}', [\App\Http\Controllers\Member\PointRequestController::class, 'showSendPoints'])->name('member.request.points.send');
            Route::post('request-points/{request_identifier}', [\App\Http\Controllers\Member\PointRequestController::class, 'postSendPoints'])->name('member.request.points.send.post');
        });

        // Static pages (no auth required, truly public)
        Route::get('about', 'Member\PageController@about')->name('member.about');
        Route::get('contact', 'Member\PageController@contact')->name('member.contact');
        Route::get('faq', 'Member\PageController@faq')->name('member.faq');
        Route::get('terms', 'Member\PageController@terms')->name('member.terms');
        Route::get('privacy', 'Member\PageController@privacy')->name('member.privacy');

        Route::middleware(['guest:member'])->group(function () {
            Route::get('login', 'Member\AuthController@login')->name('member.login');
            Route::post('login', 'Member\AuthController@postLogin')->name('member.login.post');

            // OTP Authentication Routes
            Route::post('login/check', 'Member\OtpController@checkEmail')->name('member.login.check');
            Route::post('login/otp/send', 'Member\OtpController@sendOtp')->name('member.login.otp.send');
            Route::get('login/otp/verify', 'Member\OtpController@showOtpVerify')->name('member.login.otp.verify');
            Route::post('login/otp/verify', 'Member\OtpController@verifyOtp')->name('member.login.otp.verify.post');
            Route::post('login/otp/resend', 'Member\OtpController@resendOtp')->name('member.login.otp.resend');

            Route::get('register', 'Member\AuthController@register')->name('member.register');
            // OTP Registration Routes (replaces password email)
            Route::post('register', 'Member\OtpController@postRegister')->name('member.register.post');
            Route::get('register/verify', 'Member\OtpController@showRegisterVerify')->name('member.register.otp.verify');
            Route::post('register/verify', 'Member\OtpController@verifyRegisterOtp')->name('member.register.otp.verify.post');
            Route::post('register/resend', 'Member\OtpController@resendRegisterOtp')->name('member.register.otp.resend');
            Route::get('password', 'Member\AuthController@forgotPassword')->name('member.forgot_password');
            Route::post('password', 'Member\AuthController@postForgotPassword')->name('member.forgot_password.post');
            Route::get('reset-password', 'Member\AuthController@resetPassword')->name('member.reset_password')->middleware('signed');
            Route::post('reset-password', 'Member\AuthController@postResetPassword')->name('member.reset_password.post')->middleware('signed');
            Route::get('login-link', 'Member\AuthController@loginLink')->name('member.login.link')->middleware('signed');
        });
        Route::get('logout', 'Member\AuthController@logout')->name('member.logout');

        // Authenticated staff routes
        Route::group(['prefix' => 'staff', 'middleware' => ['staff.auth', 'staff.role:1,2,3']], function () {
            Route::get('/', 'Staff\PageController@index')->name('staff.index');

            // Scan QR code
            Route::get('scan', 'Staff\PageController@showQrScanner')->name('staff.qr.scanner');

            // API - Member Search
            Route::get('api/search-members', 'Staff\PageController@searchMembers')->name('staff.api.search-members');

            // Stamp Cards
            // ⚠️ stamps/member/{identifier} MUST be before stamps/{param1}/{param2}
            // otherwise the wildcard route greedily captures "member" as {member_identifier}
            Route::get('stamps/member/{identifier}', [\App\Http\Controllers\Staff\StampController::class, 'getMemberStatus'])->name('staff.stamps.member');
            Route::get('stamps/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Staff\StampController::class, 'showStampTransactions'])->name('staff.stamp.transactions');
            Route::get('stamps/add/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Staff\StampController::class, 'showAddStamps'])->name('staff.stamps.add.show');
            Route::post('stamps/add', [\App\Http\Controllers\Staff\StampController::class, 'addStamps'])->name('staff.stamps.add');
            Route::get('stamps/claim/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Staff\StampController::class, 'showClaimReward'])->name('staff.stamps.claim.show');
            Route::post('stamps/claim', [\App\Http\Controllers\Staff\StampController::class, 'claimReward'])->name('staff.stamps.claim');


            // Vouchers
            Route::get('vouchers/history/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Staff\VoucherController::class, 'showMemberTransactions'])->name('staff.voucher.transactions');
            Route::get('vouchers/redeem/{member_identifier?}', [\App\Http\Controllers\Staff\VoucherController::class, 'showRedeemForm'])->name('staff.vouchers.redeem');
            Route::get('vouchers/redeem/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Staff\VoucherController::class, 'showRedeemWithVoucher'])->name('staff.vouchers.redeem.show');
            Route::post('vouchers/validate', [\App\Http\Controllers\Staff\VoucherController::class, 'validateVoucher'])->name('staff.vouchers.validate');
            Route::post('vouchers/redeem', [\App\Http\Controllers\Staff\VoucherController::class, 'redeem'])->name('staff.vouchers.redeem.post');

            // Generate a code
            Route::get('generate-code/{card_identifier}', [\App\Http\Controllers\Staff\CodeController::class, 'showGenerateCode'])->name('staff.code.generate');
            Route::post('generate-code/{card_identifier}', [\App\Http\Controllers\Staff\CodeController::class, 'postGenerateCode'])->name('staff.code.generate.post');

            // Earn
            Route::get('earn/{member_identifier}/{card_identifier}', 'Staff\EarnController@showEarnPoints')->name('staff.earn.points');
            Route::post('earn/{member_identifier}/{card_identifier}', 'Staff\EarnController@postEarnPoints')->name('staff.earn.points.post');

            // Claim
            Route::get('claim/{member_identifier}/{card_id}/{reward_id}', 'Staff\RewardController@showClaimReward')->name('staff.claim.reward')->middleware('signed'); // signed:consume to make the link accessible only once
            Route::post('claim/{member_identifier}/{card_id}/{reward_id}', 'Staff\RewardController@postClaimReward')->name('staff.claim.reward.post');

            // History (Loyalty Card Transactions)
            Route::get('history/{member_identifier?}/{card_identifier?}', 'Staff\TransactionController@showTransactions')->name('staff.transactions');

            // Data Definition
            Route::get('manage/{name}', 'Data\ListController@showList')->name('staff.data.list');
            Route::get('manage/{name}/suggest', 'Data\ListController@suggest')->name('staff.data.suggest');
            Route::get('manage/export/{name}', 'Data\ExportController@exportList')->name('staff.data.export');
            Route::post('manage/{name}/delete/{id?}', 'Data\DeleteController@postDelete')->name('staff.data.delete.post');
            Route::get('manage/{name}/view/{id}', 'Data\ViewController@showViewItem')->name('staff.data.view')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/insert', 'Data\InsertController@showInsertItem')->name('staff.data.insert');
            Route::post('manage/{name}/insert', 'Data\InsertController@postInsertItem')->name('staff.data.insert.post');
            Route::get('manage/{name}/edit/{id}', 'Data\EditController@showEditItem')->name('staff.data.edit')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::post('manage/{name}/edit/{id}', 'Data\EditController@postEditItem')->name('staff.data.edit.post')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/impersonate/{guard}/{id}', 'Data\AuthController@impersonate')->name('staff.data.impersonate');

            // Profile OTP Verification
            Route::post('profile/otp/send', 'Data\ProfileOtpController@send')->name('staff.profile.otp.send');
            Route::post('profile/otp/verify', 'Data\ProfileOtpController@verify')->name('staff.profile.otp.verify');
        });

        // Non-authenticated staff routes
        Route::prefix('staff')->group(function () {
            Route::middleware(['guest:staff'])->group(function () {
                Route::get('login', 'Staff\AuthController@login')->name('staff.login');
                Route::post('login', 'Staff\AuthController@postLogin')->name('staff.login.post');

                // OTP Authentication Routes
                Route::post('login/check', 'Staff\OtpController@checkEmail')->name('staff.login.check');
                Route::post('login/otp/send', 'Staff\OtpController@sendOtp')->name('staff.login.otp.send');
                Route::get('login/otp/verify', 'Staff\OtpController@showOtpVerify')->name('staff.login.otp.verify');
                Route::post('login/otp/verify', 'Staff\OtpController@verifyOtp')->name('staff.login.otp.verify.post');
                Route::post('login/otp/resend', 'Staff\OtpController@resendOtp')->name('staff.login.otp.resend');

                Route::get('password', 'Staff\AuthController@forgotPassword')->name('staff.forgot_password');
                Route::post('password', 'Staff\AuthController@postForgotPassword')->name('staff.forgot_password.post');
                Route::get('reset-password', 'Staff\AuthController@resetPassword')->name('staff.reset_password')->middleware('signed');
                Route::post('reset-password', 'Staff\AuthController@postResetPassword')->name('staff.reset_password.post')->middleware('signed');
            });
            Route::get('logout', 'Staff\AuthController@logout')->name('staff.logout');
        });

        // Authenticated partner routes
        Route::group(['prefix' => 'partner',  'middleware' => ['partner.auth', 'partner.role:1,2,3']], function () {
            Route::get('/', 'Partner\PageController@index')->name('partner.index');

            // Shopify integration install (initiated from partner dashboard).
            Route::get('integrations/shopify/install', [ShopifyOAuthController::class, 'install'])->name('partner.shopify.install');

            // Transactions
            Route::get('transactions/{member_identifier?}/{card_identifier?}', 'Partner\TransactionController@showTransactions')->name('partner.transactions');
            Route::get('transactions/delete-last/{member_identifier?}/{card_identifier?}', 'Partner\TransactionController@deleteLastTransaction')->name('partner.delete.last.transaction');

            // Stamp Transactions
            Route::get('stamp-transactions/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Partner\StampController::class, 'showStampTransactions'])->name('partner.stamp.transactions');
            Route::get('delete-last-stamp/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Partner\StampController::class, 'deleteLastStamp'])->name('partner.delete.last.stamp');

            // Voucher Transactions
            Route::get('voucher-transactions/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Partner\VoucherController::class, 'showVoucherTransactions'])->name('partner.voucher.transactions');
            Route::get('delete-last-voucher-redemption/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Partner\VoucherController::class, 'deleteLastRedemption'])->name('partner.delete.last.voucher.redemption');

            // Analytics
            Route::get('loyalty-card-analytics', 'Partner\AnalyticsController@showAnalytics')->name('partner.analytics');
            Route::get('loyalty-card-analytics/card/{card_id}', 'Partner\AnalyticsController@showCardAnalytics')->name('partner.analytics.card');

            Route::get('stamp-card-analytics', 'Partner\StampCardAnalyticsController@index')->name('partner.stamp-card-analytics');
            Route::get('stamp-card-analytics/card/{stamp_card_id}', 'Partner\StampCardAnalyticsController@show')->name('partner.stamp-card-analytics.card');

            // Voucher Analytics
            Route::get('voucher-analytics', [\App\Http\Controllers\Partner\VoucherAnalyticsController::class, 'index'])->name('partner.voucher-analytics');
            Route::get('voucher-analytics/voucher/{voucher_id}', [\App\Http\Controllers\Partner\VoucherAnalyticsController::class, 'show'])->name('partner.voucher-analytics.voucher');

            // Voucher Management
            // Voucher Batch Management (New Wizard System)
            Route::get('vouchers/batch', [\App\Http\Controllers\Partner\VoucherController::class, 'showBatchWizard'])->name('partner.vouchers.batch');
            Route::post('vouchers/batch/generate', [\App\Http\Controllers\Partner\VoucherController::class, 'generateBatchWizard'])->name('partner.vouchers.batch.generate');
            Route::get('vouchers/batches', [\App\Http\Controllers\Partner\VoucherController::class, 'showBatches'])->name('partner.vouchers.batches');
            Route::get('vouchers/batch/{batch}/export', [\App\Http\Controllers\Partner\VoucherController::class, 'exportBatch'])->name('partner.vouchers.batch.export');
            Route::post('vouchers/batch/{batch}/toggle', [\App\Http\Controllers\Partner\VoucherController::class, 'toggleBatch'])->name('partner.vouchers.batch.toggle');
            Route::delete('vouchers/batch/{batch}', [\App\Http\Controllers\Partner\VoucherController::class, 'deleteBatch'])->name('partner.vouchers.batch.delete');
            Route::post('vouchers/batch/{batch}/extend', [\App\Http\Controllers\Partner\VoucherController::class, 'extendBatch'])->name('partner.vouchers.batch.extend');
            Route::delete('vouchers/batch/{batch}/delete-unused', [\App\Http\Controllers\Partner\VoucherController::class, 'deleteUnusedVouchers'])->name('partner.vouchers.batch.delete-unused');
            Route::get('vouchers/batch/{batch}/analytics', [\App\Http\Controllers\Partner\VoucherController::class, 'showBatchAnalytics'])->name('partner.vouchers.batch.analytics');

            // CSV Import
            Route::get('vouchers/import', [\App\Http\Controllers\Partner\VoucherController::class, 'showImport'])->name('partner.vouchers.import');
            Route::post('vouchers/import/process', [\App\Http\Controllers\Partner\VoucherController::class, 'processImport'])->name('partner.vouchers.import.process');

            // Legacy routes (keeping for backward compatibility)
            Route::get('vouchers/batch-old', [\App\Http\Controllers\Partner\VoucherController::class, 'showBatchForm'])->name('partner.vouchers.batch.old');
            Route::post('vouchers/batch-old', [\App\Http\Controllers\Partner\VoucherController::class, 'generateBatch'])->name('partner.vouchers.batch.post.old');
            Route::get('vouchers/transactions', [\App\Http\Controllers\Partner\VoucherController::class, 'transactions'])->name('partner.vouchers.transactions');
            Route::post('vouchers/transactions/{transaction_id}/void', [\App\Http\Controllers\Partner\VoucherController::class, 'voidTransaction'])->name('partner.vouchers.transactions.void');

            // Activity Log Analytics
            Route::get('activity-logs/analytics', 'Partner\ActivityLogAnalyticsController@index')->name('partner.activity-logs.analytics');

            // Billing
            Route::get('billing', [\App\Http\Controllers\Partner\BillingController::class, 'index'])->name('partner.billing');

            // Ai
            Route::post('api/ai-response', 'Partner\AiController@getResponse')->name('partner.api.ai-response');

            // Data Definition
            Route::get('manage/{name}', 'Data\ListController@showList')->name('partner.data.list');
            Route::get('manage/{name}/suggest', 'Data\ListController@suggest')->name('partner.data.suggest');
            Route::get('manage/export/{name}', 'Data\ExportController@exportList')->name('partner.data.export');
            Route::post('manage/{name}/delete/{id?}', 'Data\DeleteController@postDelete')->name('partner.data.delete.post');
            Route::get('manage/{name}/view/{id}', 'Data\ViewController@showViewItem')->name('partner.data.view')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/insert', 'Data\InsertController@showInsertItem')->name('partner.data.insert');
            Route::post('manage/{name}/insert', 'Data\InsertController@postInsertItem')->name('partner.data.insert.post');
            Route::get('manage/{name}/edit/{id}', 'Data\EditController@showEditItem')->name('partner.data.edit')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::post('manage/{name}/edit/{id}', 'Data\EditController@postEditItem')->name('partner.data.edit.post')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/impersonate/{guard}/{id}', 'Data\AuthController@impersonate')->name('partner.data.impersonate');

            // Profile OTP Verification
            Route::post('profile/otp/send', 'Data\ProfileOtpController@send')->name('partner.profile.otp.send');
            Route::post('profile/otp/verify', 'Data\ProfileOtpController@verify')->name('partner.profile.otp.verify');

            /*
            |--------------------------------------------------------------------------
            | Shopify Integration Dashboard (Partner)
            |--------------------------------------------------------------------------
            |
            | Partner-facing dashboard for managing Shopify integrations:
            | - Widget embed snippet generation
            | - Connection controls (pause/resume/disconnect)
            | - Settings management
            | - Reward mapping configuration
            |
            */
            Route::prefix('integrations/shopify')->group(function () {
                Route::get('/', [\App\Http\Controllers\Partner\ShopifyDashboardController::class, 'index'])->name('partner.integrations.shopify');
                Route::post('/settings', [\App\Http\Controllers\Partner\ShopifyDashboardController::class, 'updateSettings'])->name('partner.integrations.shopify.settings');
                Route::post('/{integrationId}/pause', [\App\Http\Controllers\Partner\ShopifyDashboardController::class, 'pause'])->name('partner.integrations.shopify.pause');
                Route::post('/{integrationId}/resume', [\App\Http\Controllers\Partner\ShopifyDashboardController::class, 'resume'])->name('partner.integrations.shopify.resume');
                Route::post('/{integrationId}/disconnect', [\App\Http\Controllers\Partner\ShopifyDashboardController::class, 'disconnect'])->name('partner.integrations.shopify.disconnect');
                Route::get('/install', [ShopifyOAuthController::class, 'install'])->name('partner.integrations.shopify.install');
            });

            /*
            |--------------------------------------------------------------------------
            | Email Campaigns
            |--------------------------------------------------------------------------
            |
            | Partner-to-member email marketing with smart segmentation.
            | Sequential sending for PHP timeout compatibility.
            |
            */
            Route::prefix('email-campaigns')->group(function () {
                Route::get('/', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'index'])->name('partner.email-campaigns.index');
                Route::get('/compose', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'compose'])->name('partner.email-campaigns.compose');
                Route::post('/preview', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'preview'])->name('partner.email-campaigns.preview');
                Route::post('/', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'send'])->name('partner.email-campaigns.send');
                Route::get('/{campaign}', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'show'])->name('partner.email-campaigns.show');
                Route::post('/{campaign}/activate', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'activate'])->name('partner.email-campaigns.activate');
                Route::post('/{campaign}/send-next', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'sendNext'])->name('partner.email-campaigns.send-next');
                Route::delete('/{campaign}', [\App\Http\Controllers\Partner\EmailCampaignController::class, 'destroy'])->name('partner.email-campaigns.destroy');
            });
        });

        // Non-authenticated partner routes
        Route::prefix('partner')->group(function () {
            Route::middleware(['guest:partner'])->group(function () {
                Route::get('login', 'Partner\AuthController@login')->name('partner.login');
                Route::post('login', 'Partner\AuthController@postLogin')->name('partner.login.post');

                // OTP Authentication Routes
                Route::post('login/check', 'Partner\OtpController@checkEmail')->name('partner.login.check');
                Route::post('login/otp/send', 'Partner\OtpController@sendOtp')->name('partner.login.otp.send');
                Route::get('login/otp/verify', 'Partner\OtpController@showOtpVerify')->name('partner.login.otp.verify');
                Route::post('login/otp/verify', 'Partner\OtpController@verifyOtp')->name('partner.login.otp.verify.post');
                Route::post('login/otp/resend', 'Partner\OtpController@resendOtp')->name('partner.login.otp.resend');

                // Registration Routes (when config('default.partners_can_register') is enabled)
                Route::get('register', 'Partner\AuthController@register')->name('partner.register');
                Route::post('register', 'Partner\AuthController@postRegister')->middleware('throttle:partner-registration')->name('partner.register.post');
                Route::get('register/verify', 'Partner\OtpController@showRegisterVerify')->name('partner.register.otp.verify');
                Route::post('register/verify', 'Partner\OtpController@verifyRegisterOtp')->name('partner.register.otp.verify.post');
                Route::post('register/resend', 'Partner\OtpController@resendRegisterOtp')->name('partner.register.otp.resend');

                Route::get('password', 'Partner\AuthController@forgotPassword')->name('partner.forgot_password');
                Route::post('password', 'Partner\AuthController@postForgotPassword')->name('partner.forgot_password.post');
                Route::get('reset-password', 'Partner\AuthController@resetPassword')->name('partner.reset_password')->middleware('signed');
                Route::post('reset-password', 'Partner\AuthController@postResetPassword')->name('partner.reset_password.post')->middleware('signed');
            });
            Route::get('logout', 'Partner\AuthController@logout')->name('partner.logout');
        });

        // Authenticated admin routes (Super Admin only - role:1)
        Route::group(['prefix' => 'admin',  'middleware' => ['admin.auth', 'admin.role:1']], function () {
            Route::get('migrate', 'Admin\PageController@runMigrations')->name('admin.migrate');

            // Transactions
            Route::get('transactions/{member_identifier?}/{card_identifier?}', 'Admin\TransactionController@showTransactions')->name('admin.transactions');
            Route::get('transactions/delete-last/{member_identifier?}/{card_identifier?}', 'Admin\TransactionController@deleteLastTransaction')->name('admin.delete.last.transaction');

            // Stamp Transactions
            Route::get('stamp-transactions/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Admin\StampController::class, 'showStampTransactions'])->name('admin.stamp.transactions');
            Route::get('delete-last-stamp/{member_identifier}/{stamp_card_id}', [\App\Http\Controllers\Admin\StampController::class, 'deleteLastStamp'])->name('admin.delete.last.stamp');

            // Voucher Transactions
            Route::get('voucher-transactions/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Admin\VoucherController::class, 'showVoucherTransactions'])->name('admin.voucher.transactions');
            Route::get('delete-last-voucher-redemption/{member_identifier}/{voucher_id}', [\App\Http\Controllers\Admin\VoucherController::class, 'deleteLastRedemption'])->name('admin.delete.last.voucher.redemption');

            // License & Updates
            Route::get('license', [\App\Http\Controllers\Admin\LicenseController::class, 'index'])->name('admin.license.index');
            Route::post('license/activate', [\App\Http\Controllers\Admin\LicenseController::class, 'activate'])->name('admin.license.activate');
            Route::post('license/refresh', [\App\Http\Controllers\Admin\LicenseController::class, 'refresh'])->name('admin.license.refresh');
            Route::post('license/deactivate', [\App\Http\Controllers\Admin\LicenseController::class, 'deactivate'])->name('admin.license.deactivate');
            Route::post('license/check-updates', [\App\Http\Controllers\Admin\LicenseController::class, 'checkUpdates'])->name('admin.license.check-updates');
            Route::post('license/install-update', [\App\Http\Controllers\Admin\LicenseController::class, 'installUpdate'])->name('admin.license.install-update');
            Route::get('license/check-status', [\App\Http\Controllers\Admin\LicenseController::class, 'checkStatus'])->name('admin.license.check-status');
            Route::post('license/restore-backup', [\App\Http\Controllers\Admin\LicenseController::class, 'restoreBackup'])->name('admin.license.restore-backup');
            Route::get('license/check-restore-status', [\App\Http\Controllers\Admin\LicenseController::class, 'checkRestoreStatus'])->name('admin.license.check-restore-status');
            Route::delete('license/delete-backup', [\App\Http\Controllers\Admin\LicenseController::class, 'deleteBackup'])->name('admin.license.delete-backup');
            Route::post('license/install-manual-update', [\App\Http\Controllers\Admin\LicenseController::class, 'installManualUpdate'])->name('admin.license.install-manual-update');

            // System Settings
            Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
            Route::post('settings/reset', [\App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('admin.settings.reset');

            // Health Center
            Route::get('health', [\App\Http\Controllers\Admin\HealthController::class, 'index'])->name('admin.health.index');
            Route::get('health/json', [\App\Http\Controllers\Admin\HealthController::class, 'json'])->name('admin.health.json');

            // SaaS Dashboard
            Route::get('saas', [\App\Http\Controllers\Admin\SaasDashboardController::class, 'index'])->name('admin.saas.index');
        });

        // Authenticated admin and manager routes
        Route::group(['prefix' => 'admin',  'middleware' => ['admin.auth', 'admin.role:1,2']], function () {
            Route::get('/', 'Admin\PageController@index')->name('admin.index');

            // Activity Log Analytics
            Route::get('activity-logs/analytics', 'Admin\ActivityLogAnalyticsController@index')->name('admin.activity-logs.analytics');
            Route::get('activity-logs/purge', 'Admin\ActivityLogController@showPurge')->name('admin.activity-logs.purge');
            Route::post('activity-logs/purge', 'Admin\ActivityLogController@purge')->name('admin.activity-logs.purge.post');

            // Member Purge (ghost/anonymous cleanup)
            Route::get('members/purge', 'Admin\MemberPurgeController@show')->name('admin.members.purge');
            Route::post('members/purge', 'Admin\MemberPurgeController@purge')->name('admin.members.purge.post');

            // Data Definition
            Route::get('manage/{name}', 'Data\ListController@showList')->name('admin.data.list');
            Route::get('manage/{name}/suggest', 'Data\ListController@suggest')->name('admin.data.suggest');
            Route::get('manage/export/{name}', 'Data\ExportController@exportList')->name('admin.data.export');
            Route::post('manage/{name}/delete/{id?}', 'Data\DeleteController@postDelete')->name('admin.data.delete.post');
            Route::get('manage/{name}/view/{id}', 'Data\ViewController@showViewItem')->name('admin.data.view')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/insert', 'Data\InsertController@showInsertItem')->name('admin.data.insert');
            Route::post('manage/{name}/insert', 'Data\InsertController@postInsertItem')->name('admin.data.insert.post');
            Route::get('manage/{name}/edit/{id}', 'Data\EditController@showEditItem')->name('admin.data.edit')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::post('manage/{name}/edit/{id}', 'Data\EditController@postEditItem')->name('admin.data.edit.post')->where(['id' => '[a-zA-Z0-9\-]+']);
            Route::get('manage/{name}/impersonate/{guard}/{id}', 'Data\AuthController@impersonate')->name('admin.data.impersonate');

            // Profile OTP Verification
            Route::post('profile/otp/send', 'Data\ProfileOtpController@send')->name('admin.profile.otp.send');
            Route::post('profile/otp/verify', 'Data\ProfileOtpController@verify')->name('admin.profile.otp.verify');
        });

        // Non-authenticated admin routes
        Route::prefix('admin')->group(function () {
            Route::middleware(['guest:admin'])->group(function () {
                Route::get('login', 'Admin\AuthController@login')->name('admin.login');
                Route::post('login', 'Admin\AuthController@postLogin')->name('admin.login.post');

                // OTP Authentication Routes
                Route::post('login/check', 'Admin\OtpController@checkEmail')->name('admin.login.check');
                Route::post('login/otp/send', 'Admin\OtpController@sendOtp')->name('admin.login.otp.send');
                Route::get('login/otp/verify', 'Admin\OtpController@showOtpVerify')->name('admin.login.otp.verify');
                Route::post('login/otp/verify', 'Admin\OtpController@verifyOtp')->name('admin.login.otp.verify.post');
                Route::post('login/otp/resend', 'Admin\OtpController@resendOtp')->name('admin.login.otp.resend');

                Route::get('password', 'Admin\AuthController@forgotPassword')->name('admin.forgot_password');
                Route::post('password', 'Admin\AuthController@postForgotPassword')->name('admin.forgot_password.post');
                Route::get('reset-password', 'Admin\AuthController@resetPassword')->name('admin.reset_password')->middleware('signed');
                Route::post('reset-password', 'Admin\AuthController@postResetPassword')->name('admin.reset_password.post')->middleware('signed');
            });
            Route::get('logout', 'Admin\AuthController@logout')->name('admin.logout');
        });
    });

    Route::group(['namespace' => '\App\Http\Controllers', 'prefix' => 'install', 'middleware' => 'not.installed'], function () {
        Route::get('/', 'Installation\PageController@index')->name('installation.index');
        Route::get('log', 'Installation\PageController@downloadLog')->name('installation.log');
        Route::post('/', 'Installation\PageController@postInstall')->name('installation.install');
        Route::post('test-email', 'Installation\EmailTestController@test')->name('installation.test-email');
    });
});

// Fallback Route
// This route will catch any requests that don't match any of the defined routes.
// It redirects to the 'redir.locale' route, which handles the root URL and is responsible for locale redirection.
// It should be placed at the very bottom of this file to ensure it only runs for undefined routes.
/*
Route::fallback(function () {
    return redirect()->route('redir.locale');
});
*/
