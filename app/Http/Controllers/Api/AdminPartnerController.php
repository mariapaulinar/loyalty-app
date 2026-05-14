<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Partner;
use App\Services\Partner\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPartnerController extends Controller
{
    protected $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
    }

    /**
     * Check if admin has permission to access a partner.
     */
    private function adminCanAccessPartner($admin, Partner $partner): bool
    {
        if ($admin->role == 1) {
            return true;
        }

        $networks = $admin->networks;
        foreach ($networks as $network) {
            if ($network->partners->contains($partner)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve all accessible partners for the authenticated administrator.
     *
     * @OA\Get(
     *     path="/{locale}/v1/admin/partners",
     *     operationId="getAdminPartners",
     *     tags={"Admin"},
     *     summary="List all accessible partners",
     *     description="Retrieve a list of all partners the authenticated administrator has access to. Administrators (role=1) can view all partners; Managers see only partners within their assigned networks.",
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale code (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of partners",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AdminPartner"))
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function getPartners(string $locale, Request $request): JsonResponse
    {
        $admin = $request->user('admin_api');

        if ($admin->role == 1) {
            $partners = Partner::all();
        } else {
            $networks = $admin->networks;
            $partners = collect();
            foreach ($networks as $network) {
                $partners = $partners->concat($network->partners);
            }
        }

        $partners->each(fn ($partner) => $partner->hideForPublic());

        return response()->json($partners);
    }

    /**
     * Retrieve a specific partner's details.
     *
     * @OA\Get(
     *     path="/{locale}/v1/admin/partner/{partnerId}",
     *     operationId="getAdminPartner",
     *     tags={"Admin"},
     *     summary="Get partner details",
     *     description="Retrieve detailed information about a specific partner including their permissions and limits.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Partner details", @OA\JsonContent(ref="#/components/schemas/AdminPartnerFull")),
     *     @OA\Response(response=403, description="Permission denied", @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")),
     *     @OA\Response(response=404, description="Partner not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function getPartner(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $partner->hideForPublic();

        // Include permissions from meta
        $partnerData = $partner->toArray();
        $partnerData['permissions'] = $this->extractPermissions($partner);

        return response()->json($partnerData);
    }

    /**
     * Create a new partner.
     *
     * @OA\Post(
     *     path="/{locale}/v1/admin/partners",
     *     operationId="createAdminPartner",
     *     tags={"Admin"},
     *     summary="Create a new partner",
     *     description="Create a new partner account with optional permissions and limits. A default club is automatically created for the partner.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Partner data",
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "network_id"},
     *
     *             @OA\Property(property="name", type="string", description="Partner name", example="Coffee Corner"),
     *             @OA\Property(property="email", type="string", format="email", description="Partner email", example="partner@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Password (6-48 chars)", example="securepass123"),
     *             @OA\Property(property="network_id", type="integer", description="Network ID to assign partner to", example=1),
     *             @OA\Property(property="locale", type="string", description="Locale", example="en_US"),
     *             @OA\Property(property="currency", type="string", description="Currency code", example="USD"),
     *             @OA\Property(property="time_zone", type="string", description="Time zone", example="America/New_York"),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true),
     *             @OA\Property(property="permissions", type="object", ref="#/components/schemas/PartnerPermissions")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Partner created", @OA\JsonContent(ref="#/components/schemas/AdminPartnerFull")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function createPartner(string $locale, Request $request): JsonResponse
    {
        $admin = $request->user('admin_api');

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:partners,email',
            'password' => 'required|string|min:6|max:48',
            'network_id' => 'required|exists:networks,id',
            'locale' => 'nullable|string|size:5',
            'currency' => 'nullable|string|size:3',
            'time_zone' => 'nullable|string|max:48',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.loyalty_cards_permission' => 'nullable|boolean',
            'permissions.loyalty_cards_limit' => 'nullable|integer|min:-1',
            'permissions.stamp_cards_permission' => 'nullable|boolean',
            'permissions.stamp_cards_limit' => 'nullable|integer|min:-1',
            'permissions.vouchers_permission' => 'nullable|boolean',
            'permissions.voucher_batches_permission' => 'nullable|boolean',
            'permissions.vouchers_limit' => 'nullable|integer|min:-1',
            'permissions.rewards_limit' => 'nullable|integer|min:-1',
            'permissions.staff_members_limit' => 'nullable|integer|min:-1',
            'permissions.email_campaigns_permission' => 'nullable|boolean',
            'permissions.activity_permission' => 'nullable|boolean',
            'permissions.agent_api_permission' => 'nullable|boolean',
            'permissions.agent_keys_limit' => 'nullable|integer|min:-1',
            'permissions.cards_on_homepage' => 'nullable|boolean',
        ]);

        // Check manager permission on network
        if ($admin->role != 1) {
            $networkIds = $admin->networks->pluck('id')->toArray();
            if (! in_array($validated['network_id'], $networkIds)) {
                return response()->json(['message' => 'Permission denied for this network'], 403);
            }
        }

        // Build meta with permissions
        $meta = [];
        if (isset($validated['permissions'])) {
            foreach ($validated['permissions'] as $key => $value) {
                $meta[$key] = $value;
            }
        }

        // Create partner
        $partner = Partner::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'network_id' => $validated['network_id'],
            'locale' => $validated['locale'] ?? config('app.locale'),
            'currency' => $validated['currency'] ?? 'USD',
            'time_zone' => $validated['time_zone'] ?? config('app.timezone'),
            'is_active' => $validated['is_active'] ?? true,
            'meta' => $meta,
            'created_by' => $admin->id,
        ]);

        // Create default club
        $partner->clubs()->create([
            'name' => trans('common.general', [], $partner->locale),
            'is_active' => true,
        ]);

        $partner->hideForPublic();
        $partnerData = $partner->toArray();
        $partnerData['permissions'] = $this->extractPermissions($partner);

        return response()->json($partnerData, 201);
    }

    /**
     * Update partner details.
     *
     * @OA\Put(
     *     path="/{locale}/v1/admin/partner/{partnerId}",
     *     operationId="updateAdminPartner",
     *     tags={"Admin"},
     *     summary="Update partner details",
     *     description="Update a partner's profile information. Does not include permissions - use the permissions endpoint for that.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Partner data to update",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Partner Name"),
     *             @OA\Property(property="email", type="string", example="partner@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="New password (optional)"),
     *             @OA\Property(property="locale", type="string", example="en_US"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="time_zone", type="string", example="America/Los_Angeles"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Updated partner", @OA\JsonContent(ref="#/components/schemas/AdminPartner")),
     *     @OA\Response(response=403, description="Permission denied", @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")),
     *     @OA\Response(response=404, description="Partner not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function updatePartner(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120|unique:partners,email,' . $partner->id,
            'password' => 'nullable|string|min:6|max:48',
            'locale' => 'nullable|string|size:5',
            'currency' => 'nullable|string|size:3',
            'time_zone' => 'nullable|string|max:48',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle password separately
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['updated_by'] = $admin->id;

        $this->partnerService->update($partner, array_filter($validated, fn ($v) => $v !== null));

        $partner->hideForPublic();

        return response()->json($partner);
    }

    /**
     * Delete a partner.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/admin/partner/{partnerId}",
     *     operationId="deleteAdminPartner",
     *     tags={"Admin"},
     *     summary="Delete a partner",
     *     description="Permanently delete a partner and all associated data. This action cannot be undone.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Partner deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Partner deleted successfully"))),
     *     @OA\Response(response=403, description="Permission denied", @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")),
     *     @OA\Response(response=404, description="Partner not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function deletePartner(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        // Delete partner (cascades to related data via model events)
        $partner->delete();

        return response()->json(['message' => 'Partner deleted successfully']);
    }

    /**
     * Get partner permissions.
     *
     * @OA\Get(
     *     path="/{locale}/v1/admin/partner/{partnerId}/permissions",
     *     operationId="getAdminPartnerPermissions",
     *     tags={"Admin"},
     *     summary="Get partner permissions and limits",
     *     description="Retrieve the feature permissions and usage limits for a partner. Used for SaaS billing management.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Partner permissions", @OA\JsonContent(ref="#/components/schemas/PartnerPermissions")),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="Partner not found"),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function getPartnerPermissions(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        return response()->json($this->extractPermissions($partner));
    }

    /**
     * Update partner permissions.
     *
     * @OA\Patch(
     *     path="/{locale}/v1/admin/partner/{partnerId}/permissions",
     *     operationId="updateAdminPartnerPermissions",
     *     tags={"Admin"},
     *     summary="Update partner permissions and limits",
     *     description="Update the feature permissions and usage limits for a partner. Used for SaaS billing tier management. Use -1 for unlimited.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permissions to update",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PartnerPermissions")
     *     ),
     *
     *     @OA\Response(response=200, description="Updated permissions", @OA\JsonContent(ref="#/components/schemas/PartnerPermissions")),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="Partner not found"),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function updatePartnerPermissions(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'loyalty_cards_permission' => 'nullable|boolean',
            'loyalty_cards_limit' => 'nullable|integer|min:-1',
            'stamp_cards_permission' => 'nullable|boolean',
            'stamp_cards_limit' => 'nullable|integer|min:-1',
            'vouchers_permission' => 'nullable|boolean',
            'voucher_batches_permission' => 'nullable|boolean',
            'vouchers_limit' => 'nullable|integer|min:-1',
            'rewards_limit' => 'nullable|integer|min:-1',
            'staff_members_limit' => 'nullable|integer|min:-1',
            'email_campaigns_permission' => 'nullable|boolean',
            'activity_permission' => 'nullable|boolean',
            'agent_api_permission' => 'nullable|boolean',
            'agent_keys_limit' => 'nullable|integer|min:-1',
            'cards_on_homepage' => 'nullable|boolean',
        ]);

        // Merge with existing meta
        $meta = $partner->meta ?? [];
        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $meta[$key] = $value;
            }
        }

        $partner->meta = $meta;
        $partner->updated_by = $admin->id;
        $partner->save();

        return response()->json($this->extractPermissions($partner));
    }

    /**
     * Get partner usage statistics.
     *
     * @OA\Get(
     *     path="/{locale}/v1/admin/partner/{partnerId}/usage",
     *     operationId="getAdminPartnerUsage",
     *     tags={"Admin"},
     *     summary="Get partner usage vs limits",
     *     description="Retrieve current usage counts compared to limits for a partner. Useful for billing dashboards and limit enforcement.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="partnerId", in="path", required=true, description="Partner ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Partner usage statistics",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PartnerUsage")
     *     ),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="Partner not found"),
     *
     *     security={{"admin_auth_token": {}}}
     * )
     */
    public function getPartnerUsage(string $locale, Request $request, string $partnerId): JsonResponse
    {
        $admin = $request->user('admin_api');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if (! $this->adminCanAccessPartner($admin, $partner)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $permissions = $this->extractPermissions($partner);

        // Count actual usage
        $usage = [
            'loyalty_cards' => [
                'used' => $partner->cards()->count(),
                'limit' => $permissions['loyalty_cards_limit'],
                'allowed' => $permissions['loyalty_cards_permission'],
            ],
            'stamp_cards' => [
                'used' => $partner->stampCards()->count(),
                'limit' => $permissions['stamp_cards_limit'],
                'allowed' => $permissions['stamp_cards_permission'],
            ],
            'vouchers' => [
                'used' => $partner->vouchers()->count(),
                'limit' => $permissions['vouchers_limit'],
                'allowed' => $permissions['vouchers_permission'],
            ],
            'rewards' => [
                'used' => $partner->rewards()->count(),
                'limit' => $permissions['rewards_limit'],
                'allowed' => true,
            ],
            'staff_members' => [
                'used' => $partner->staff()->count(),
                'limit' => $permissions['staff_members_limit'],
                'allowed' => true,
            ],
        ];

        return response()->json($usage);
    }

    /**
     * Extract permissions from partner meta.
     */
    private function extractPermissions(Partner $partner): array
    {
        $meta = $partner->meta ?? [];

        return [
            'loyalty_cards_permission' => $meta['loyalty_cards_permission'] ?? true,
            'loyalty_cards_limit' => $meta['loyalty_cards_limit'] ?? -1,
            'stamp_cards_permission' => $meta['stamp_cards_permission'] ?? true,
            'stamp_cards_limit' => $meta['stamp_cards_limit'] ?? -1,
            'vouchers_permission' => $meta['vouchers_permission'] ?? true,
            'voucher_batches_permission' => $meta['voucher_batches_permission'] ?? true,
            'vouchers_limit' => $meta['vouchers_limit'] ?? -1,
            'rewards_limit' => $meta['rewards_limit'] ?? -1,
            'staff_members_limit' => $meta['staff_members_limit'] ?? -1,
            'email_campaigns_permission' => $meta['email_campaigns_permission'] ?? true,
            'activity_permission' => $meta['activity_permission'] ?? true,
            'agent_api_permission' => $meta['agent_api_permission'] ?? false,
            'agent_keys_limit' => $meta['agent_keys_limit'] ?? 5,
            'cards_on_homepage' => $meta['cards_on_homepage'] ?? false,
        ];
    }
}

