<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Base controller for all Agent API endpoints.
 *
 * Provides the shared foundation that every agent controller inherits:
 * - Standardized response envelopes via ReturnsAgentErrors trait
 * - Translation-aware serialization (dual-mode: all translations vs single locale)
 * - Collection serialization with pagination envelope
 * - Member resolution by flexible identifier (UUID, email, number, unique_id)
 *
 * Every agent controller MUST extend this class. No exceptions.
 * If you find yourself writing response()->json() directly, stop and
 * use the trait methods instead.
 *
 * @see App\Http\Controllers\Api\Agent\Concerns\ReturnsAgentErrors
 * @see RewardLoyalty-100-agent.md §8
 */

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\Agent\Concerns\ReturnsAgentErrors;
use App\Http\Controllers\Controller;
use App\Models\Member;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BaseAgentController extends Controller
{
    use ReturnsAgentErrors;

    // ═════════════════════════════════════════════════════════════════════════
    // SERIALIZATION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Serialize a model for agent API response.
     *
     * If the agent sent Accept-Language, Spatie's HasTranslations
     * automatically returns the active locale (single string).
     * If no Accept-Language, we override translatable fields with
     * all translations (JSON object of locale → value).
     *
     * This is the ONLY way to serialize models in agent responses.
     *
     * @param  Model  $model  The model to serialize
     * @return array<string, mixed>
     */
    protected function serializeForAgent(Model $model): array
    {
        $data = $model->toArray();

        // If no specific locale requested → return all translations (management mode)
        if (! request()->attributes->get('agent_locale') && method_exists($model, 'getTranslatableAttributes')) {
            foreach ($model->getTranslatableAttributes() as $attribute) {
                $data[$attribute] = $model->getTranslations($attribute);
            }
        }

        return $data;
    }

    /**
     * Serialize a paginated collection for agent API response.
     *
     * Returns the standard envelope with data + pagination metadata.
     */
    protected function jsonPaginated(LengthAwarePaginator $paginator, int $status = 200): JsonResponse
    {
        $items = $paginator->getCollection()->map(
            fn (Model $model) => $this->serializeForAgent($model)
        );

        return $this->jsonSuccess([
            'data' => $items,
            'pagination' => $this->paginationMeta($paginator),
        ], $status);
    }

    /**
     * Serialize a single resource for agent API response.
     */
    protected function jsonResource(Model $model, int $status = 200): JsonResponse
    {
        return $this->jsonSuccess([
            'data' => $this->serializeForAgent($model),
        ], $status);
    }

    /**
     * Serialize a plain collection (non-paginated) for agent API response.
     */
    protected function jsonCollection(Collection $collection, int $status = 200): JsonResponse
    {
        $items = $collection->map(
            fn (Model $model) => $this->serializeForAgent($model)
        );

        return $this->jsonSuccess([
            'data' => $items,
        ], $status);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER RESOLUTION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Resolve a member by flexible identifier.
     *
     * Supports UUID, email, member_number, and unique_identifier.
     * Used by transaction endpoints where agents may identify members
     * in various ways depending on their integration.
     *
     * Priority order:
     * 1. UUID (exact match by primary key)
     * 2. Email (case-insensitive)
     * 3. Member number (string match)
     * 4. Unique identifier (string match)
     *
     * @param  string  $identifier  The flexible member identifier
     * @return Member|null  The resolved member or null
     */
    protected function resolveMember(string $identifier): ?Member
    {
        // 1. Try UUID (primary key lookup — fastest path)
        if (Str::isUuid($identifier)) {
            return Member::find($identifier);
        }

        // 2. Try email (case-insensitive)
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return Member::where('email', strtolower($identifier))->first();
        }

        // 3. Try member_number or unique_identifier
        return Member::where('member_number', $identifier)
            ->orWhere('unique_identifier', $identifier)
            ->first();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // REQUEST HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the sanitized per_page value from the request.
     * Clamps between 1 and 100, defaults to 25.
     */
    protected function getPerPage(): int
    {
        $perPage = (int) request()->input('per_page', 25);

        return max(1, min(100, $perPage));
    }

    /**
     * Reject deprecated request fields so the public contract stays canonical.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $deprecatedToCanonical
     */
    protected function rejectDeprecatedFields(array $payload, array $deprecatedToCanonical): ?JsonResponse
    {
        $errors = [];

        foreach ($deprecatedToCanonical as $deprecatedField => $canonicalField) {
            if (! array_key_exists($deprecatedField, $payload)) {
                continue;
            }

            $errors[$deprecatedField] = [
                "The {$deprecatedField} field is not supported. Use {$canonicalField} instead.",
            ];
        }

        if ($errors === []) {
            return null;
        }

        return $this->jsonValidationError($errors);
    }

    /**
     * Standard pagination payload for agent list endpoints.
     *
     * @return array<string, int>
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Normalize date/time values to ISO-8601 strings for API responses.
     */
    protected function serializeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return (string) $value;
    }
}
