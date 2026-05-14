<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerAgentApiEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $agentKey = $request->attributes->get('agent_key');

        if (! $agentKey || $agentKey->owner_type !== Partner::class) {
            return $next($request);
        }

        $partner = $agentKey->getPartner();

        if (! $partner || ! $partner->agent_api_permission) {
            return response()->json([
                'error' => true,
                'code' => 'FEATURE_DISABLED',
                'message' => 'Agent API access has been revoked for this partner.',
                'retry_strategy' => 'contact_support',
                'details' => [
                    'permission' => 'agent_api_permission',
                ],
            ], 403);
        }

        return $next($request);
    }
}
