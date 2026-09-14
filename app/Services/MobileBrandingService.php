<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ColorHelper;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the mobile branding payload (admin white-label theme).
 * Mirrors what <x-ui.brand-styles /> and PWA manifest expose to the web.
 */
class MobileBrandingService
{
    public const CACHE_KEY = 'mobile.branding.payload.v1';

    public const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly SettingsService $settings
    ) {}

    /**
     * @return array{payload: array<string, mixed>, etag: string}
     */
    public function getBranding(): array
    {
        $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->buildPayload();
        });

        $etag = '"'.sha1(json_encode($payload)).'"';

        return [
            'payload' => $payload,
            'etag' => $etag,
        ];
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        $brandColor = (string) $this->settings->get(
            'brand_color',
            config('default.brand_color', '#FCD34D')
        );

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $brandColor)) {
            $brandColor = '#FCD34D';
        }

        $pwaTheme = (string) $this->settings->get(
            'pwa_theme_color',
            config('default.pwa_theme_color', '#FCD34D')
        );
        $pwaBackground = (string) $this->settings->get(
            'pwa_background_color',
            config('default.pwa_background_color', '#ffffff')
        );

        $appName = (string) $this->settings->get(
            'app_name',
            config('default.app_name', config('app.name', 'Loyalty'))
        );

        $palette = ColorHelper::generatePalette($brandColor);
        $foreground = ColorHelper::getContrastColor($brandColor);

        $brandingSetting = Setting::where('key', 'brand_color')->first();
        $logoUrl = $brandingSetting?->getFirstMediaUrl('app_logo') ?: (string) config('default.app_logo', '');
        $logoDarkUrl = $brandingSetting?->getFirstMediaUrl('app_logo_dark') ?: (string) config('default.app_logo_dark', '');

        return [
            'version' => sha1($brandColor.'|'.$pwaTheme.'|'.$pwaBackground.'|'.$appName.'|'.$logoUrl.'|'.$logoDarkUrl),
            'app_name' => $appName,
            'brand_color' => $brandColor,
            'pwa_theme_color' => $pwaTheme,
            'pwa_background_color' => $pwaBackground,
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
            'logo_dark_url' => $logoDarkUrl !== '' ? $logoDarkUrl : null,
            'primary' => $this->paletteToStringKeys($palette),
            'foreground_on_brand' => $foreground,
            'semantic' => [
                'brand' => $brandColor,
                'cta_hint' => $pwaTheme,
                'background' => $pwaBackground,
            ],
            'notes' => [
                'primary_overrides_css' => 'Same as <x-ui.brand-styles /> injecting --color-primary-*',
                'partner_card_colors' => 'Per-partner brand_color comes on card payloads, not this endpoint',
            ],
        ];
    }

    /**
     * @param  array<int|string, string>  $palette
     * @return array<string, string>
     */
    private function paletteToStringKeys(array $palette): array
    {
        $out = [];
        foreach ($palette as $shade => $hex) {
            $out[(string) $shade] = $hex;
        }

        return $out;
    }
}
