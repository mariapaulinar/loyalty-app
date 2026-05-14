<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EmailUnsubscribeController
 *
 * Purpose:
 * Handles email unsubscribe requests from campaign emails.
 * Uses signed URLs for security - no authentication required.
 *
 * Architecture:
 * - Public route (no auth middleware)
 * - URL signed via Laravel's URL::signedRoute()
 * - One-click unsubscribe for compliance (CAN-SPAM, GDPR)
 *
 * Security:
 * - Signed URL prevents unauthorized unsubscribes
 * - Invalid/tampered signatures return 403
 * - Member ID cannot be guessed or enumerated
 */

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailUnsubscribeController extends Controller
{
    /**
     * Process unsubscribe request from signed email link.
     *
     * Sets member.accepts_emails = false and displays confirmation.
     *
     * Note: The 'signed' middleware validates the URL signature before
     * this method is called. Invalid signatures never reach here.
     *
     * @param  string  $locale  The locale slug from URL (e.g. 'en-us')
     * @param  Member  $member  The member to unsubscribe (route model binding)
     * @return View Confirmation page
     */
    public function unsubscribe(Request $request, string $locale, Member $member): View
    {
        // Set app locale from URL for proper translations
        $appLocale = str_replace('-', '_', $locale);
        if (is_dir(lang_path($appLocale))) {
            app()->setLocale($appLocale);
        }

        // Signed URL middleware already validated the signature
        // Just update the member's email preference
        $member->update(['accepts_emails' => false]);

        return view('emails.unsubscribed', [
            'member' => $member,
            'appName' => config('default.app_name', config('app.name')),
        ]);
    }
}
