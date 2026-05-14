<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Set app locale for agent API requests.
 *
 * Unlike the web SetLocale middleware (which reads locale from URL segment),
 * this reads from the standard Accept-Language header. If not provided,
 * locale stays at app default — and controllers return all translations.
 *
 * This is critical because many models use Spatie's HasTranslations trait
 * (Card, Reward, Tier, Voucher, StampCard, etc.). Without locale detection,
 * agent API responses would always return the server default locale.
 *
 * Dual-mode behavior:
 * - No Accept-Language → controllers return all translations (management mode)
 * - Accept-Language: nl → controllers return single locale (display mode)
 *
 * Sets a request attribute 'agent_locale' that BaseAgentController reads
 * to decide between single-locale or all-translations serialization.
 *
 * @see App\Http\Middleware\SetLocale (web equivalent)
 * @see App\Http\Controllers\Api\Agent\BaseAgentController::serializeForAgent()
 * @see RewardLoyalty-100-agent.md §2b
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class SetAgentLocale
{
    /**
     * Cached available locales — computed once per request lifecycle.
     *
     * @var array<string>|null
     */
    private ?array $availableLocales = null;

    public function handle(Request $request, Closure $next): Response
    {
        $acceptLanguage = $request->header('Accept-Language');

        if ($acceptLanguage) {
            $available = $this->getAvailableLocales();

            if (! empty($available)) {
                // Parse Accept-Language (e.g., "nl", "en-US", "de-DE,de;q=0.9")
                // Symfony's getPreferredLanguage handles quality values and matching
                $preferred = $request->getPreferredLanguage($available);

                if ($preferred) {
                    app()->setLocale($preferred);
                    \Carbon\Carbon::setLocale($preferred);
                    $request->attributes->set('agent_locale', $preferred);
                }
            }
        }

        // If no Accept-Language sent, agent_locale stays null
        // → controllers know to return all translations (management mode)
        return $next($request);
    }

    /**
     * Get available locales from the lang directory.
     *
     * Reuses the same locale discovery approach as the web layer.
     * Results are cached for the request lifecycle.
     *
     * @return array<string>
     */
    private function getAvailableLocales(): array
    {
        if ($this->availableLocales !== null) {
            return $this->availableLocales;
        }

        $langPath = lang_path();

        if (! File::isDirectory($langPath)) {
            $this->availableLocales = [];

            return [];
        }

        $dirs = File::directories($langPath);
        $this->availableLocales = array_map('basename', $dirs);

        return $this->availableLocales;
    }
}
