<?php

namespace App\Http\Controllers\Api;

/**
 * OpenAPI Schema Definitions
 *
 * This file contains all reusable schema definitions for the Reward Loyalty API.
 * Schemas are organized by category: Security, Errors, Users, Entities, and Requests.
 *
 * @OA\SecurityScheme(
 *     securityScheme="admin_auth_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="sanctum",
 *     description="Administrator authentication token obtained from the login endpoint. Include in the Authorization header as `Bearer <token>`."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="partner_auth_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="sanctum",
 *     description="Partner authentication token obtained from the login endpoint. Include in the Authorization header as `Bearer <token>`."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="member_auth_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="sanctum",
 *     description="Member authentication token obtained from the login endpoint. Include in the Authorization header as `Bearer <token>`."
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     description="Standard error response for failed operations",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", description="Human-readable error message", example="The provided credentials are incorrect."),
 *     @OA\Property(property="code", type="integer", description="Application-specific error code", example=400)
 * )
 *
 * @OA\Schema(
 *     schema="UnauthenticatedResponse",
 *     description="Response when authentication is required but not provided or invalid",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", description="Authentication error message", example="Unauthenticated.")
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     description="Response when the requested resource does not exist",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", description="Not found error message", example="Resource not found.")
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenResponse",
 *     description="Response when the user lacks permission to access the resource",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", description="Permission error message", example="You do not have permission to access this resource.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     description="Response when request validation fails",
 *     @OA\Property(property="message", type="string", description="General validation error message", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object", description="Field-specific validation errors")
 * )
 *
 * @OA\Schema(
 *     schema="AdminLoginSuccess",
 *     description="Successful admin authentication response",
 *     required={"token"},
 *     @OA\Property(property="token", type="string", description="Bearer token for authenticating subsequent requests", example="1|laravel_sanctum_j3ffUNZdoP0JZJn0y3GzgcAONlDzekQrUWI2sqk3c473f47b")
 * )
 *
 * @OA\Schema(
 *     schema="PartnerLoginSuccess",
 *     description="Successful partner authentication response",
 *     required={"token"},
 *     @OA\Property(property="token", type="string", description="Bearer token for authenticating subsequent requests", example="2|laravel_sanctum_abc123xyz789partnerToken")
 * )
 *
 * @OA\Schema(
 *     schema="MemberLoginSuccess",
 *     description="Successful member authentication response",
 *     required={"token"},
 *     @OA\Property(property="token", type="string", description="Bearer token for authenticating subsequent requests", example="3|laravel_sanctum_def456uvw321memberToken")
 * )
 *
 * @OA\Schema(
 *     schema="LogoutSuccess",
 *     description="Successful logout response",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", description="Logout confirmation message", example="Successfully logged out")
 * )
 *
 * @OA\Schema(
 *     schema="Admin",
 *     description="Administrator or manager user account",
 *     required={"id", "role", "name", "email", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier (Snowflake ID)", example="019b8d48-97df-7177-8aa0-17f2107a8eb0"),
 *     @OA\Property(property="role", type="integer", description="User role: 1 = Administrator, 2 = Manager", example=1),
 *     @OA\Property(property="name", type="string", maxLength=64, description="Display name", example="John Admin"),
 *     @OA\Property(property="email", type="string", format="email", description="Email address", example="admin@example.com"),
 *     @OA\Property(property="locale", type="string", nullable=true, description="Preferred locale", example="en_US"),
 *     @OA\Property(property="currency", type="string", nullable=true, description="Preferred currency code", example="USD"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="IANA time zone identifier", example="America/New_York"),
 *     @OA\Property(property="number_of_times_logged_in", type="integer", description="Total login count", example=42),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Timestamp of last login"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Account creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp"),
 *     @OA\Property(property="avatar", type="string", format="uri", nullable=true, description="URL to avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="AdminPartner",
 *     description="Partner data as viewed by an administrator",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-27f2107a8eb1"),
 *     @OA\Property(property="network_id", type="string", format="uuid", nullable=true, description="Associated network identifier"),
 *     @OA\Property(property="name", type="string", maxLength=64, description="Business name", example="Coffee Corner"),
 *     @OA\Property(property="email", type="string", format="email", description="Contact email", example="partner@coffecorner.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="Email verification timestamp"),
 *     @OA\Property(property="locale", type="string", nullable=true, description="Preferred locale", example="en_US"),
 *     @OA\Property(property="currency", type="string", nullable=true, description="Default currency", example="USD"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="Time zone", example="America/New_York"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether the partner account is active", example=true),
 *     @OA\Property(property="number_of_times_logged_in", type="integer", description="Total login count", example=15),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Last login timestamp"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Account creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp"),
 *     @OA\Property(property="avatar", type="string", format="uri", nullable=true, description="URL to avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="Partner",
 *     description="Partner (merchant) account information",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-37f2107a8eb2"),
 *     @OA\Property(property="network_id", type="string", format="uuid", nullable=true, description="Associated network identifier"),
 *     @OA\Property(property="name", type="string", maxLength=64, description="Business name", example="Coffee Corner"),
 *     @OA\Property(property="email", type="string", format="email", description="Contact email", example="partner@coffecorner.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="Email verification timestamp"),
 *     @OA\Property(property="locale", type="string", nullable=true, description="Preferred locale", example="en_US"),
 *     @OA\Property(property="currency", type="string", nullable=true, description="Default currency", example="USD"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="Time zone", example="America/Los_Angeles"),
 *     @OA\Property(property="number_of_times_logged_in", type="integer", description="Total login count", example=28),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Last login timestamp"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Account creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp"),
 *     @OA\Property(property="avatar", type="string", format="uri", nullable=true, description="URL to avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="Member",
 *     description="Loyalty program member (customer) account",
 *     required={"id", "unique_identifier", "name", "email"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
 *     @OA\Property(property="unique_identifier", type="string", description="Human-readable unique ID", example="700-857-223-945"),
 *     @OA\Property(property="name", type="string", maxLength=64, description="Member name", example="Jane Customer"),
 *     @OA\Property(property="email", type="string", format="email", description="Email address", example="jane@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="Email verification timestamp"),
 *     @OA\Property(property="locale", type="string", nullable=true, description="Preferred locale", example="en_US"),
 *     @OA\Property(property="currency", type="string", nullable=true, description="Preferred currency", example="USD"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="Time zone", example="America/New_York"),
 *     @OA\Property(property="accepts_emails", type="boolean", description="Whether member accepts marketing emails", example=true),
 *     @OA\Property(property="number_of_times_logged_in", type="integer", description="Total login count", example=12),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Last login timestamp"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Account creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp"),
 *     @OA\Property(property="avatar", type="string", format="uri", nullable=true, description="URL to avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="MemberRegistration",
 *     description="Response after successful member registration",
 *     required={"email", "name"},
 *     @OA\Property(property="email", type="string", format="email", description="Registered email", example="newmember@example.com"),
 *     @OA\Property(property="name", type="string", maxLength=64, description="Member name", example="New Member"),
 *     @OA\Property(property="password", type="string", description="Generated password (only returned if auto-generated)", example="123456"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="Time zone", example="America/New_York"),
 *     @OA\Property(property="locale", type="string", nullable=true, description="Locale", example="en_US"),
 *     @OA\Property(property="currency", type="string", nullable=true, description="Currency", example="USD"),
 *     @OA\Property(property="accepts_emails", type="integer", description="Email preference", example=0),
 *     @OA\Property(property="send_mail", type="integer", description="Whether welcome email was sent", example=0)
 * )
 *
 * @OA\Schema(
 *     schema="StaffMember",
 *     description="Staff member who can process transactions",
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-57f2107a8eb4"),
 *     @OA\Property(property="club_id", type="string", format="uuid", description="Associated club identifier"),
 *     @OA\Property(property="name", type="string", description="Staff member name", example="Alex Staff"),
 *     @OA\Property(property="email", type="string", format="email", description="Email address", example="alex@coffeecorner.com"),
 *     @OA\Property(property="time_zone", type="string", nullable=true, description="Time zone"),
 *     @OA\Property(property="number_of_times_logged_in", type="integer", description="Total login count"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Last login timestamp"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp"),
 *     @OA\Property(property="avatar", type="string", format="uri", nullable=true, description="URL to avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="Club",
 *     description="A loyalty club containing cards and members",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-67f2107a8eb5"),
 *     @OA\Property(property="name", type="string", maxLength=120, description="Club name", example="Coffee Lovers Club"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether the club is active", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last modification timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="Card",
 *     description="A points-based loyalty card",
 *     required={"id", "club_id", "name", "unique_identifier", "currency"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier", example="019b8d48-97df-7177-8aa0-77f2107a8eb6"),
 *     @OA\Property(property="club_id", type="string", format="uuid", description="Parent club identifier"),
 *     @OA\Property(property="name", type="string", description="Card name", example="Coffee Rewards Card"),
 *     @OA\Property(property="head", type="object", description="Multilingual header text"),
 *     @OA\Property(property="title", type="object", description="Multilingual card title"),
 *     @OA\Property(property="description", type="object", description="Multilingual card description"),
 *     @OA\Property(property="unique_identifier", type="string", description="Unique identifier for QR codes"),
 *     @OA\Property(property="issue_date", type="string", format="date-time", nullable=true, description="When the card became active"),
 *     @OA\Property(property="expiration_date", type="string", format="date-time", nullable=true, description="When the card expires"),
 *     @OA\Property(property="bg_color", type="string", description="Background color (hex)", example="#8B4513"),
 *     @OA\Property(property="bg_color_opacity", type="integer", description="Background opacity percentage", example=90),
 *     @OA\Property(property="text_color", type="string", description="Text color (hex)", example="#FFFFFF"),
 *     @OA\Property(property="currency", type="string", description="Currency code (ISO 4217)", example="USD"),
 *     @OA\Property(property="initial_bonus_points", type="integer", description="Bonus points on first use", example=10),
 *     @OA\Property(property="points_expiration_months", type="integer", nullable=true, description="Months until points expire"),
 *     @OA\Property(property="currency_unit_amount", type="integer", description="Amount per currency unit"),
 *     @OA\Property(property="points_per_currency", type="integer", description="Points per currency unit spent"),
 *     @OA\Property(property="min_points_per_purchase", type="integer", description="Minimum points per transaction"),
 *     @OA\Property(property="max_points_per_purchase", type="integer", description="Maximum points per transaction"),
 *     @OA\Property(property="is_visible_by_default", type="boolean", description="Whether publicly discoverable"),
 *     @OA\Property(property="is_visible_when_logged_in", type="boolean", description="Visible to logged-in members"),
 *     @OA\Property(property="total_amount_purchased", type="integer", description="Total purchase amount (minor units)"),
 *     @OA\Property(property="number_of_points_issued", type="integer", description="Total points issued"),
 *     @OA\Property(property="last_points_issued_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="number_of_points_redeemed", type="integer", description="Total points redeemed"),
 *     @OA\Property(property="number_of_rewards_redeemed", type="integer", description="Total rewards redeemed"),
 *     @OA\Property(property="last_reward_redeemed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="views", type="integer", description="Total card page views"),
 *     @OA\Property(property="last_view", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="meta", type="object", nullable=true, description="Additional metadata"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="balance", type="integer", description="Member's current point balance (-1 if not applicable)", example=250)
 * )
 *
 * @OA\Schema(
 *     schema="StampCard",
 *     description="A stamp-based loyalty card",
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier"),
 *     @OA\Property(property="title", type="string", description="Card title"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Card description"),
 *     @OA\Property(property="reward_title", type="string", description="Reward title"),
 *     @OA\Property(property="reward_description", type="string", nullable=true, description="Reward description"),
 *     @OA\Property(property="current_stamps", type="integer", description="Member's current stamp count"),
 *     @OA\Property(property="stamps_required", type="integer", description="Stamps needed to complete"),
 *     @OA\Property(property="progress_percentage", type="number", format="float", description="Completion progress (0-100)"),
 *     @OA\Property(property="pending_rewards", type="integer", description="Unclaimed completed rewards"),
 *     @OA\Property(property="completed_count", type="integer", description="Total times completed"),
 *     @OA\Property(property="redeemed_count", type="integer", description="Total rewards redeemed"),
 *     @OA\Property(property="enrolled_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="last_stamp_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="last_completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="next_stamp_expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="stamp_icon", type="string", nullable=true, description="Icon identifier"),
 *     @OA\Property(property="colors", type="object", description="Card color scheme"),
 *     @OA\Property(property="media", type="object", description="Card media assets"),
 *     @OA\Property(property="settings", type="object", description="Card behavior settings"),
 *     @OA\Property(property="reward_value", type="number", format="float", nullable=true),
 *     @OA\Property(property="reward_points", type="integer", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StampCardsResponse",
 *     description="Response containing member's stamp cards and statistics",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="stamp_cards", type="array", @OA\Items(ref="#/components/schemas/StampCard")),
 *         @OA\Property(property="stats", type="object")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StampTransaction",
 *     description="A stamp card transaction record",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="event", type="string", description="Transaction type", example="earned"),
 *     @OA\Property(property="stamps", type="integer", description="Number of stamps"),
 *     @OA\Property(property="purchase_amount", type="number", format="float", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="staff_name", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StampHistoryResponse",
 *     description="Response containing stamp card transaction history",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/StampTransaction"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PurchaseRequest",
 *     description="Request body for adding a purchase transaction",
 *     required={"purchase_amount"},
 *     @OA\Property(property="purchase_amount", type="number", format="float", description="Purchase amount", example=25.50),
 *     @OA\Property(property="note", type="string", nullable=true, description="Optional note"),
 *     @OA\Property(property="image", type="string", nullable=true, description="Optional image"),
 *     @OA\Property(property="staffId", type="string", nullable=true, description="Staff member ID")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     description="A completed loyalty card transaction",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="staff_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="member_id", type="string", format="uuid"),
 *     @OA\Property(property="card_id", type="string", format="uuid"),
 *     @OA\Property(property="partner_name", type="string"),
 *     @OA\Property(property="partner_email", type="string"),
 *     @OA\Property(property="purchase_amount", type="number", format="float", nullable=true),
 *     @OA\Property(property="note", type="string", nullable=true),
 *     @OA\Property(property="points_issued", type="integer"),
 *     @OA\Property(property="reward_redeemed", type="boolean"),
 *     @OA\Property(property="reward_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="transaction_date", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="BalanceResponse",
 *     description="Response containing a member's point balance",
 *     required={"balance"},
 *     @OA\Property(property="balance", type="integer", description="Current point balance", example=250)
 * )
 *
 * @OA\Schema(
 *     schema="AdminPartnerFull",
 *     description="Partner data with permissions as viewed by an administrator",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/AdminPartner"),
 *         @OA\Schema(
 *             @OA\Property(property="permissions", ref="#/components/schemas/PartnerPermissions")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="PartnerPermissions",
 *     description="Partner feature permissions and limits for SaaS billing management",
 *     @OA\Property(property="loyalty_cards_permission", type="boolean", description="Can create loyalty cards", example=true),
 *     @OA\Property(property="loyalty_cards_limit", type="integer", description="Max loyalty cards (-1 = unlimited)", example=-1),
 *     @OA\Property(property="stamp_cards_permission", type="boolean", description="Can create stamp cards", example=true),
 *     @OA\Property(property="stamp_cards_limit", type="integer", description="Max stamp cards (-1 = unlimited)", example=-1),
 *     @OA\Property(property="vouchers_permission", type="boolean", description="Can create vouchers", example=true),
 *     @OA\Property(property="voucher_batches_permission", type="boolean", description="Can create voucher batches", example=true),
 *     @OA\Property(property="vouchers_limit", type="integer", description="Max vouchers (-1 = unlimited)", example=-1),
 *     @OA\Property(property="rewards_limit", type="integer", description="Max rewards (-1 = unlimited)", example=-1),
 *     @OA\Property(property="staff_members_limit", type="integer", description="Max staff members (-1 = unlimited)", example=-1),
 *     @OA\Property(property="email_campaigns_permission", type="boolean", description="Can create email campaigns", example=true),
 *     @OA\Property(property="activity_permission", type="boolean", description="Can view activity logs", example=true),
 *     @OA\Property(property="cards_on_homepage", type="boolean", description="Can display cards on homepage", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="PartnerUsage",
 *     description="Partner usage statistics compared to limits",
 *     @OA\Property(
 *         property="loyalty_cards",
 *         type="object",
 *         @OA\Property(property="used", type="integer", description="Current count", example=5),
 *         @OA\Property(property="limit", type="integer", description="Maximum allowed (-1 = unlimited)", example=-1),
 *         @OA\Property(property="allowed", type="boolean", description="Feature enabled", example=true)
 *     ),
 *     @OA\Property(
 *         property="stamp_cards",
 *         type="object",
 *         @OA\Property(property="used", type="integer", example=3),
 *         @OA\Property(property="limit", type="integer", example=-1),
 *         @OA\Property(property="allowed", type="boolean", example=true)
 *     ),
 *     @OA\Property(
 *         property="vouchers",
 *         type="object",
 *         @OA\Property(property="used", type="integer", example=10),
 *         @OA\Property(property="limit", type="integer", example=100),
 *         @OA\Property(property="allowed", type="boolean", example=true)
 *     ),
 *     @OA\Property(
 *         property="rewards",
 *         type="object",
 *         @OA\Property(property="used", type="integer", example=8),
 *         @OA\Property(property="limit", type="integer", example=-1),
 *         @OA\Property(property="allowed", type="boolean", example=true)
 *     ),
 *     @OA\Property(
 *         property="staff_members",
 *         type="object",
 *         @OA\Property(property="used", type="integer", example=2),
 *         @OA\Property(property="limit", type="integer", example=5),
 *         @OA\Property(property="allowed", type="boolean", example=true)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Voucher",
 *     description="A promotional voucher or discount code",
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier"),
 *     @OA\Property(property="club_id", type="string", format="uuid", description="Associated club ID"),
 *     @OA\Property(property="title", type="string", description="Voucher title", example="Summer Sale 20% Off"),
 *     @OA\Property(property="code", type="string", description="Voucher code", example="SUMMER20"),
 *     @OA\Property(property="type", type="string", description="Type: percentage, fixed_amount, bonus_points", example="percentage"),
 *     @OA\Property(property="discount_amount", type="integer", description="Discount amount (cents or percentage)", example=2000),
 *     @OA\Property(property="min_purchase_amount", type="integer", description="Minimum purchase in cents", example=5000),
 *     @OA\Property(property="max_uses", type="integer", description="Maximum uses (-1 = unlimited)", example=100),
 *     @OA\Property(property="max_uses_per_member", type="integer", description="Max uses per member", example=1),
 *     @OA\Property(property="uses_count", type="integer", description="Current use count", example=42),
 *     @OA\Property(property="valid_from", type="string", format="date-time", nullable=true, description="Validity start date"),
 *     @OA\Property(property="valid_until", type="string", format="date-time", nullable=true, description="Validity end date"),
 *     @OA\Property(property="is_active", type="boolean", description="Active status", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Reward",
 *     description="A loyalty reward that can be redeemed",
 *     @OA\Property(property="id", type="string", format="uuid", description="Unique identifier"),
 *     @OA\Property(property="card_id", type="string", format="uuid", description="Associated card ID"),
 *     @OA\Property(property="title", type="string", description="Reward title", example="Free Coffee"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Reward description"),
 *     @OA\Property(property="points_required", type="integer", description="Points needed to redeem", example=100),
 *     @OA\Property(property="is_active", type="boolean", description="Active status", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class OpenApiSchemas {}


