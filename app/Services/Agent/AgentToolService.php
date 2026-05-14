<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Converts the Agent OpenAPI spec into machine-readable tool definitions
 * for AI agent frameworks (OpenAI, Anthropic/Claude, MCP, Generic).
 *
 * Single source of truth: tool definitions are ALWAYS derived from the
 * OpenAPI spec — never hand-maintained. Both the Artisan export command
 * and the GET /tools endpoint delegate to this service.
 *
 * Design:
 * - extractTools() reads agent-api.json and filters by role + scopes
 *   + partner feature permissions (x-agent-permission)
 * - formatTools() converts to framework-specific JSON
 * - resolveSchema() recursively flattens $ref and allOf into
 *   self-contained JSON Schema, preserving oneOf, additionalProperties,
 *   nested objects, arrays, nullable, and every other schema keyword
 * - Body schemas are preserved as full JSON Schema — never flattened
 *   to scalar metadata. This ensures translatable fields (oneOf),
 *   nested objects, arrays, and complex types survive the export.
 *
 * @see RewardLoyalty-102-api-agents.md §7
 */

namespace App\Services\Agent;

use Illuminate\Support\Facades\File;

class AgentToolService
{
    /**
     * Server base URL prefix used to build full HTTP paths in tool descriptions.
     * Spec paths are relative (e.g., /partner/cards); agents need full paths
     * (e.g., /api/agent/v1/partner/cards) to route tool calls to HTTP requests.
     */
    private const BASE_PATH = '/api/agent/v1';

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Load tool definitions from the spec, filtered by role, scopes,
     * and owner feature permissions.
     *
     * @param  string       $role    Key role (partner, admin, member)
     * @param  array|null   $scopes  Scopes to filter by (null = all)
     * @param  bool         $hasAdminScope  Whether the key has the super 'admin' scope
     * @param  array<string>  $disabledPermissions  Feature permissions that are disabled
     *                        for this owner (e.g., ['loyalty_cards_permission']). Endpoints
     *                        annotated with `x-agent-permission` matching a disabled
     *                        permission will be excluded from the tool list.
     * @return array<int, array>  Intermediate tool definitions
     *
     * @throws \RuntimeException  If spec file is missing or invalid
     */
    public function extractTools(string $role, ?array $scopes, bool $hasAdminScope = false, array $disabledPermissions = []): array
    {
        $spec = $this->loadSpec();

        return $this->extractFromSpec($spec, $role, $scopes, $hasAdminScope, $disabledPermissions);
    }

    /**
     * Format extracted tools for a specific framework.
     *
     * @param  array<int, array>  $tools   Intermediate tool definitions
     * @param  string             $format  One of: openai, anthropic, mcp, generic
     * @return array  Framework-specific tool definitions
     *
     * @throws \InvalidArgumentException  If format is unsupported
     */
    public function formatTools(array $tools, string $format): array
    {
        return match ($format) {
            'openai'    => $this->toOpenAIFormat($tools),
            'anthropic' => $this->toAnthropicFormat($tools),
            'mcp'       => $this->toMCPFormat($tools),
            'generic'   => $this->toGenericFormat($tools),
            default     => throw new \InvalidArgumentException(
                "Unsupported format: {$format}. Use: openai, anthropic, mcp, or generic."
            ),
        };
    }

    /**
     * Check if the OpenAPI spec file exists.
     */
    public function specExists(): bool
    {
        return File::exists($this->specPath());
    }

    /**
     * Get a deterministic version identifier for the current spec file.
     *
     * Used as part of the cache key so that tool definitions are automatically
     * invalidated when the spec is regenerated — without requiring manual
     * cache:clear or event listeners.
     *
     * Returns the file's last-modified timestamp, which changes on every
     * `php artisan l5-swagger:generate agent`. Falls back to '0' if the
     * file doesn't exist (the caller should check specExists() first).
     */
    public function specVersion(): string
    {
        $path = $this->specPath();

        if (! File::exists($path)) {
            return '0';
        }

        return (string) File::lastModified($path);
    }

    /**
     * Supported output formats.
     *
     * @return array<string>
     */
    public static function supportedFormats(): array
    {
        return ['openai', 'anthropic', 'mcp', 'generic'];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SPEC LOADING
    // ═══════════════════════════════════════════════════════════════════════

    private function loadSpec(): array
    {
        $path = $this->specPath();

        if (! File::exists($path)) {
            throw new \RuntimeException(
                "Agent API spec not found at: {$path}. "
                . 'Run: php artisan l5-swagger:generate agent'
            );
        }

        $spec = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Invalid JSON in agent-api.json: ' . json_last_error_msg()
            );
        }

        return $spec;
    }

    private function specPath(): string
    {
        return storage_path('api-docs/agent-api.json');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EXTRACTION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Walk every path/method in the spec and build intermediate tool defs.
     *
     * Each tool carries:
     * - name, description, method, path (spec-relative), full_path (absolute)
     * - path_params: array of {name, schema} for path parameters
     * - query_params: array of {name, schema, required} for query parameters
     * - body_schema: full JSON Schema for the request body (preserved as-is)
     * - required_scopes: scopes from x-agent-scopes
     *
     * Filtering layers (in order):
     * 1. Role — only endpoints matching the key's role prefix
     * 2. /tools self-exclusion — meta endpoint, not a user-facing tool
     * 3. Scopes — only endpoints the key's scopes grant access to
     * 4. Permissions — only endpoints the owner's feature flags enable
     *
     * The x-agent-permission extension on an operation declares which
     * partner feature flag gates that endpoint at runtime (e.g.,
     * 'loyalty_cards_permission'). If that permission is in the
     * disabledPermissions list, the endpoint is excluded from the tool
     * list — because the agent would get a 403 FEATURE_DISABLED anyway.
     */
    private function extractFromSpec(array $spec, string $role, ?array $scopes, bool $hasAdminScope, array $disabledPermissions = []): array
    {
        $tools = [];
        // Spec paths are relative to the server base URL (/api/agent/v1),
        // so partner endpoints are /partner/*, admin are /admin/*, etc.
        $rolePrefix = "/{$role}/";

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            // Role filtering: include own-role paths + health (shared)
            if (! str_starts_with($path, $rolePrefix) && $path !== '/health') {
                continue;
            }

            // Exclude /tools from exported tool lists — meta endpoint, not a user-facing tool
            if ($path === '/tools') {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                // Scope filtering (admin super-scope bypasses)
                $endpointScopes = $operation['x-agent-scopes'] ?? [];
                if (! $hasAdminScope && $scopes !== null && ! empty($endpointScopes)) {
                    if (empty(array_intersect($scopes, $endpointScopes))) {
                        continue;
                    }
                }

                // Permission filtering — exclude endpoints gated by a disabled feature.
                // x-agent-permission declares the partner feature flag checked at runtime.
                // If the partner doesn't have that permission, the endpoint would return
                // 403 FEATURE_DISABLED, so don't advertise it as an available tool.
                $requiredPermission = $operation['x-agent-permission'] ?? null;
                if ($requiredPermission && in_array($requiredPermission, $disabledPermissions, true)) {
                    continue;
                }

                $tools[] = [
                    'name'            => $operation['operationId'] ?? $this->deriveToolName($method, $path),
                    'description'     => trim(($operation['summary'] ?? '') . "\n" . ($operation['description'] ?? '')),
                    'method'          => strtoupper($method),
                    'path'            => $path,
                    'full_path'       => self::BASE_PATH . $path,
                    'path_params'     => $this->extractPathParams($operation),
                    'query_params'    => $this->extractQueryParams($operation),
                    'body_schema'     => $this->extractBodySchema($operation, $spec),
                    'required_scopes' => $endpointScopes,
                ];
            }
        }

        return $tools;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PARAMETER EXTRACTION — preserves full JSON Schema
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Extract path parameters (e.g., {id}) as simple schema objects.
     *
     * @return array<int, array{name: string, schema: array}>
     */
    private function extractPathParams(array $operation): array
    {
        $params = [];

        foreach ($operation['parameters'] ?? [] as $param) {
            if (($param['in'] ?? '') !== 'path') {
                continue;
            }

            $schema = $param['schema'] ?? ['type' => 'string'];
            if (isset($param['description'])) {
                $schema['description'] = $param['description'];
            }

            $params[] = [
                'name'   => $param['name'],
                'schema' => $schema,
            ];
        }

        return $params;
    }

    /**
     * Extract query parameters with their full schemas.
     *
     * @return array<int, array{name: string, required: bool, schema: array}>
     */
    private function extractQueryParams(array $operation): array
    {
        $params = [];

        foreach ($operation['parameters'] ?? [] as $param) {
            if (($param['in'] ?? '') !== 'query') {
                continue;
            }

            $schema = $param['schema'] ?? ['type' => 'string'];
            if (isset($param['description'])) {
                $schema['description'] = $param['description'];
            }

            $params[] = [
                'name'     => $param['name'],
                'required' => $param['required'] ?? false,
                'schema'   => $schema,
            ];
        }

        return $params;
    }

    /**
     * Extract the request body as a fully-resolved JSON Schema.
     *
     * This is the critical difference from the old approach: instead of
     * flattening properties to scalar metadata (type, format, min, max),
     * we preserve the full schema including oneOf, additionalProperties,
     * nested objects, arrays, nullable, pattern, and every other keyword.
     *
     * $ref and allOf are resolved so the schema is self-contained.
     *
     * @return array|null  Full JSON Schema for the body, or null if no body
     */
    private function extractBodySchema(array $operation, array $spec): ?array
    {
        $bodySchema = $operation['requestBody']['content']['application/json']['schema'] ?? null;

        if ($bodySchema === null) {
            return null;
        }

        return $this->resolveSchema($bodySchema, $spec);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCHEMA RESOLUTION ($ref + allOf)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Recursively resolve $ref and allOf, producing a self-contained schema.
     *
     * Preserves every JSON Schema keyword: oneOf, anyOf, additionalProperties,
     * nullable, pattern, minLength, maxLength, items, enum, etc. Only $ref
     * pointers and allOf compositions are expanded — everything else passes
     * through untouched.
     *
     * This is critical for:
     * - PartnerDetail (allOf: [PartnerSummary, {permissions, usage}])
     * - AdminMemberDetail (allOf: [MemberSummary, {card_balances}])
     * - Translatable fields (oneOf: [string, {additionalProperties: string}])
     */
    private function resolveSchema(array $schema, array $spec): array
    {
        // Resolve $ref first
        if (isset($schema['$ref'])) {
            $schema = $this->resolveRef($schema['$ref'], $spec);
        }

        // Resolve allOf by merging all sub-schemas
        if (isset($schema['allOf'])) {
            $merged = ['type' => 'object', 'properties' => [], 'required' => []];

            foreach ($schema['allOf'] as $subSchema) {
                $resolved = $this->resolveSchema($subSchema, $spec);

                // Merge properties
                if (isset($resolved['properties'])) {
                    foreach ($resolved['properties'] as $propName => $propDef) {
                        $merged['properties'][$propName] = $this->resolveSchema($propDef, $spec);
                    }
                }

                // Merge required arrays
                if (isset($resolved['required'])) {
                    $merged['required'] = array_unique(array_merge($merged['required'], $resolved['required']));
                }

                // Carry over description if set
                if (isset($resolved['description']) && ! isset($merged['description'])) {
                    $merged['description'] = $resolved['description'];
                }
            }

            if (empty($merged['required'])) {
                unset($merged['required']);
            }

            return $merged;
        }

        // Resolve nested properties recursively
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                $schema['properties'][$propName] = $this->resolveSchema($propDef, $spec);
            }
        }

        // Resolve items (for array types)
        if (isset($schema['items'])) {
            $schema['items'] = $this->resolveSchema($schema['items'], $spec);
        }

        // Resolve oneOf sub-schemas
        if (isset($schema['oneOf'])) {
            $schema['oneOf'] = array_map(
                fn ($sub) => $this->resolveSchema($sub, $spec),
                $schema['oneOf']
            );
        }

        // Resolve anyOf sub-schemas
        if (isset($schema['anyOf'])) {
            $schema['anyOf'] = array_map(
                fn ($sub) => $this->resolveSchema($sub, $spec),
                $schema['anyOf']
            );
        }

        // Resolve additionalProperties if it's a schema (not just true/false)
        if (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
            $schema['additionalProperties'] = $this->resolveSchema($schema['additionalProperties'], $spec);
        }

        return $schema;
    }

    /**
     * Follow a $ref pointer like "#/components/schemas/LoyaltyCard"
     * and return the referenced schema.
     */
    private function resolveRef(string $ref, array $spec): array
    {
        $refPath = ltrim($ref, '#/');
        $parts = explode('/', $refPath);
        $resolved = $spec;

        foreach ($parts as $part) {
            $resolved = $resolved[$part] ?? [];
        }

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * Derive a tool name from HTTP method + path when operationId is missing.
     * This is a fallback — every endpoint should have an explicit operationId.
     */
    private function deriveToolName(string $method, string $path): string
    {
        $path = ltrim($path, '/');
        $rolePrefix = '';

        if (str_starts_with($path, 'admin/')) {
            $rolePrefix = 'admin_';
            $path = substr($path, 6);
        } elseif (str_starts_with($path, 'member/')) {
            $rolePrefix = 'member_';
            $path = substr($path, 7);
        } elseif (str_starts_with($path, 'partner/')) {
            $path = substr($path, 8);
        }

        $hasPathParam = str_contains($path, '{');
        $path = preg_replace('#/\{[^}]+\}#', '', $path);
        $resource = str_replace(['/', '-'], '_', trim($path, '/'));

        $action = match (strtolower($method)) {
            'get'              => $hasPathParam ? 'get' : 'list',
            'post'             => 'create',
            'put', 'patch'     => 'update',
            'delete'           => 'delete',
            default            => strtolower($method),
        };

        return $rolePrefix . $action . '_' . $resource;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // JSON SCHEMA ASSEMBLY — builds proper tool parameter schemas
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Build a complete JSON Schema object for a tool's parameters.
     *
     * Merges path params, query params, and body properties into a single
     * JSON Schema object. Body properties are included verbatim (preserving
     * oneOf, additionalProperties, nested objects, etc.).
     */
    private function buildParameterSchema(array $tool): array
    {
        $properties = [];
        $required = [];

        // 1. Path parameters — always required
        foreach ($tool['path_params'] as $param) {
            $schema = $param['schema'];
            $schema['description'] = trim(($schema['description'] ?? '') . ' (path parameter)');
            $properties[$param['name']] = $schema;
            $required[] = $param['name'];
        }

        // 2. Query parameters
        foreach ($tool['query_params'] as $param) {
            $properties[$param['name']] = $param['schema'];
            if ($param['required']) {
                $required[] = $param['name'];
            }
        }

        // 3. Body properties — preserved as full JSON Schema
        if ($tool['body_schema'] !== null) {
            $bodyProps = $tool['body_schema']['properties'] ?? [];
            $bodyRequired = $tool['body_schema']['required'] ?? [];

            foreach ($bodyProps as $name => $schema) {
                $properties[$name] = $schema;
            }
            $required = array_merge($required, $bodyRequired);
        }

        $result = [
            'type'       => 'object',
            'properties' => (object) $properties, // {} not [] when empty
            'required'   => array_values(array_unique($required)),
        ];

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FORMAT CONVERTERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * OpenAI function-calling format.
     * Uses full_path for HTTP routing in the description.
     *
     * @see https://platform.openai.com/docs/guides/function-calling
     */
    private function toOpenAIFormat(array $tools): array
    {
        return array_values(array_map(fn (array $tool) => [
            'type' => 'function',
            'function' => [
                'name' => $tool['name'],
                'description' => $tool['description']
                    . "\n\nHTTP: {$tool['method']} {$tool['full_path']}",
                'parameters' => $this->buildParameterSchema($tool),
            ],
        ], $tools));
    }

    /**
     * Anthropic/Claude tool-use format.
     * Uses full_path for HTTP routing in the description.
     *
     * @see https://docs.anthropic.com/en/docs/build-with-claude/tool-use
     */
    private function toAnthropicFormat(array $tools): array
    {
        return array_values(array_map(fn (array $tool) => [
            'name' => $tool['name'],
            'description' => $tool['description']
                . "\n\nHTTP: {$tool['method']} {$tool['full_path']}",
            'input_schema' => $this->buildParameterSchema($tool),
        ], $tools));
    }

    /**
     * Model Context Protocol tools format.
     * Uses full_path for HTTP routing in the description.
     *
     * @see https://modelcontextprotocol.io/docs/concepts/tools
     */
    private function toMCPFormat(array $tools): array
    {
        return [
            'tools' => array_values(array_map(fn (array $tool) => [
                'name' => $tool['name'],
                'description' => $tool['description']
                    . "\n\nHTTP: {$tool['method']} {$tool['full_path']}",
                'inputSchema' => $this->buildParameterSchema($tool),
            ], $tools)),
        ];
    }

    /**
     * Generic format — includes method, path, scopes as first-class fields.
     * Uses full_path so integrators can route HTTP calls without guessing.
     * Suitable for custom integrations and automation platforms.
     */
    private function toGenericFormat(array $tools): array
    {
        return [
            'api_name' => 'Reward Loyalty Agent API',
            'api_version' => '1.0.0',
            'base_url' => self::BASE_PATH,
            'auth' => [
                'type' => 'api_key',
                'header' => 'X-Agent-Key',
                'description' => 'Agent API key (prefix: rl_admin_*, rl_agent_*, rl_member_*)',
            ],
            'tools' => array_values(array_map(fn (array $tool) => [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'method' => $tool['method'],
                'path' => $tool['full_path'],
                'required_scopes' => $tool['required_scopes'],
                'parameters' => $this->buildParameterSchema($tool),
            ], $tools)),
        ];
    }
}
