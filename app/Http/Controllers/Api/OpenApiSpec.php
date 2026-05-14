<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    title: 'Reward Loyalty API',
    description: 'The Reward Loyalty API provides programmatic access to the Reward Loyalty platform, enabling integration with external systems, mobile apps, and custom workflows. This RESTful API supports authentication, loyalty card management, member operations, partner operations, and transaction processing.

## Authentication

All authenticated endpoints use Bearer token authentication via Laravel Sanctum. Include your token in the Authorization header.

Tokens are obtained by calling the login endpoints for each user type (Admin, Partner, or Member).

## Rate Limiting

API requests are rate-limited to ensure fair usage. Standard limits apply per authenticated user.

## Response Format

All responses are returned in JSON format. Successful responses include the requested data, while errors include a message field describing the issue.',
    contact: new OA\Contact(name: 'Reward Loyalty Website', url: 'https://rewardloyalty.co'),
    license: new OA\License(name: 'Proprietary')
)]
#[OA\Server(url: '/api', description: 'Current Server')]
#[OA\Tag(name: 'Admin', description: 'Endpoints for platform administrators and managers. Admins can manage partners, view system-wide data, and configure platform settings.')]
#[OA\Tag(name: 'Partner', description: 'Endpoints for business partners (merchants). Partners can manage their clubs, loyalty cards, staff members, and process customer transactions.')]
#[OA\Tag(name: 'Member', description: 'Endpoints for loyalty program members (customers). Members can view their cards, check balances, and track their stamp card progress.')]
#[OA\Tag(name: 'Staff', description: 'Endpoints for staff members operating point-of-sale systems. Staff can look up members, add purchases, award points, add stamps, and redeem rewards and vouchers.')]
class OpenApiSpec {}
