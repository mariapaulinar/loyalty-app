<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileBrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public mobile branding bootstrap — additive; does not affect PWA Blade rendering.
 */
class MobileBrandingController extends Controller
{
    public function __construct(
        private readonly MobileBrandingService $branding
    ) {}

    /**
     * GET /{locale}/v1/mobile/branding
     *
     * Supports If-None-Match → 304. Cache-Control mirrors admin settings TTL (~1h).
     */
    public function show(string $locale, Request $request): JsonResponse|Response
    {
        $result = $this->branding->getBranding();
        $etag = $result['etag'];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->noContent(304)->withHeaders([
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age='.MobileBrandingService::CACHE_TTL_SECONDS,
            ]);
        }

        return response()->json($result['payload'])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age='.MobileBrandingService::CACHE_TTL_SECONDS);
    }
}
