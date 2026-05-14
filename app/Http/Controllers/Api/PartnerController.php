<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Partner\PartnerService;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Update the authenticated partner's details.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/",
     *     operationId="updatePartner",
     *     tags={"Partner"},
     *     summary="Update the authenticated partner's details",
     *     description="Update the profile details of the currently authenticated partner. Only include fields you wish to modify.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\RequestBody(
     *         description="Provide the partner data you wish to update. Omit any key/value pairs that should remain unchanged.",
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", description="Partner's name", example="Partner Name"),
     *             @OA\Property(property="email", type="string", description="Partner's email", example="partner@example.com"),
     *             @OA\Property(property="locale", type="string", description="Locale setting", example="en-us"),
     *             @OA\Property(property="currency", type="string", description="Currency setting", example="USD"),
     *             @OA\Property(property="time_zone", type="string", description="Time zone setting", example="America/Los_Angeles")
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="The locale (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Partner updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Partner")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     *
     * @param  string  $locale  The locale setting (e.g., 'en-us')
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(string $locale, Request $request, PartnerService $partnerService)
    {
        $partner = $request->user('partner_api');

        $data = $request->only(['name', 'email', 'locale', 'currency', 'time_zone']);

        $updatedPartner = $partnerService->update($partner, $data);

        return response()->json(['partner' => $updatedPartner], 200);
    }
}
