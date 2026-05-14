<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Export Agent API endpoints as tool definitions for AI agent frameworks.
 *
 * Usage:
 *   php artisan agent:export-tools                         → generic format, partner role
 *   php artisan agent:export-tools openai                  → OpenAI function calling
 *   php artisan agent:export-tools anthropic --role=admin  → Claude tools, admin endpoints
 *   php artisan agent:export-tools generic --scopes=read   → Read-only partner tools
 *
 * All extraction logic lives in AgentToolService — this command
 * is a thin CLI adapter.
 *
 * @see App\Services\Agent\AgentToolService
 * @see RewardLoyalty-102-api-agents.md §7
 */

namespace App\Console\Commands;

use App\Services\Agent\AgentToolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportAgentTools extends Command
{
    protected $signature = 'agent:export-tools
                            {format=generic : Tool format (openai, anthropic, mcp, generic)}
                            {--role=partner : Key role to scope tools to (partner, admin, member)}
                            {--scopes= : Comma-separated scopes to filter tools by}';

    protected $description = 'Export Agent API endpoints as tool definitions for AI agent frameworks';

    public function handle(AgentToolService $service): int
    {
        $format = $this->argument('format');
        $role = $this->option('role');
        $scopes = $this->option('scopes')
            ? explode(',', $this->option('scopes'))
            : null;

        // Validate format
        if (! in_array($format, AgentToolService::supportedFormats(), true)) {
            $this->error("Unknown format: {$format}. Supported: " . implode(', ', AgentToolService::supportedFormats()));
            return 1;
        }

        // Validate role
        if (! in_array($role, ['partner', 'admin', 'member'], true)) {
            $this->error("Unknown role: {$role}. Supported: partner, admin, member");
            return 1;
        }

        // Check spec exists
        if (! $service->specExists()) {
            $this->error('Agent API spec not found.');
            $this->error('Run: php artisan l5-swagger:generate agent');
            return 1;
        }

        try {
            // When --scopes not specified, pass null (= show all for role).
            // The 'admin' scope logic: if scopes includes 'admin', hasAdminScope is true.
            $hasAdminScope = $scopes !== null && in_array('admin', $scopes, true);
            $tools = $service->extractTools($role, $scopes, $hasAdminScope);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        if (empty($tools)) {
            $this->warn("No tools found for role '{$role}'"
                . ($scopes ? ' with scopes: ' . implode(', ', $scopes) : ''));
            return 0;
        }

        $output = $service->formatTools($tools, $format);

        $outputPath = storage_path("api-docs/agent-tools-{$format}.json");
        File::put($outputPath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Exported " . count($tools) . " tools in '{$format}' format to {$outputPath}");
        return 0;
    }
}
