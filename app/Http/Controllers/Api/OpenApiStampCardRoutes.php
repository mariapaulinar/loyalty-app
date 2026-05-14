<?php

namespace App\Http\Controllers\Api;

/**
 * OpenAPI Endpoint Definitions for Member Stamp Card Routes
 *
 * This file contains OpenAPI annotations for member stamp card endpoints
 * that are implemented in other controllers but documented here to keep
 * all API documentation centralized in the Api directory.
 *
 * @OA\Get(
 *     path="/{locale}/v1/member/stamp-cards",
 *     operationId="getMemberStampCards",
 *     tags={"Member"},
 *     summary="Retrieve member's stamp cards",
 *     description="Retrieve all stamp cards the authenticated member is enrolled in, including current progress, stamps collected, and rewards earned. Also returns aggregate statistics.",
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
 *         description="Stamp cards retrieved successfully",
 *
 *         @OA\JsonContent(ref="#/components/schemas/StampCardsResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *
 *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
 *     ),
 *
 *     security={
 *         {"member_auth_token": {}}
 *     }
 * )
 *
 * @OA\Get(
 *     path="/{locale}/v1/member/stamp-cards/{id}/history",
 *     operationId="getMemberStampCardHistory",
 *     tags={"Member"},
 *     summary="Retrieve stamp card transaction history",
 *     description="Retrieve the transaction history for a specific stamp card, including stamps earned, redeemed, and expired. History viewing must be enabled for the stamp card by the partner.",
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
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Stamp card ID",
 *         required=true,
 *
 *         @OA\Schema(type="integer", example=81559402807296)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Transaction history retrieved successfully",
 *
 *         @OA\JsonContent(ref="#/components/schemas/StampHistoryResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *
 *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=403,
 *         description="History viewing not enabled for this stamp card",
 *
 *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Stamp card not found",
 *
 *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
 *     ),
 *
 *     security={
 *         {"member_auth_token": {}}
 *     }
 * )
 */
class OpenApiStampCardRoutes {}
