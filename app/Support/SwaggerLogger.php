<?php

namespace App\Support;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Custom logger for swagger-php that ignores warnings.
 * 
 * swagger-php 5.7+ triggers "Required @OA\PathItem() not found" warnings
 * even when paths ARE found. Laravel converts these warnings to exceptions,
 * causing deployment failures. This logger suppresses those non-fatal warnings.
 */
class SwaggerLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        // Only log actual errors, not warnings or notices
        // The "Required @OA\PathItem() not found" message is a warning that
        // appears even when paths are successfully generated, so we suppress it.
        if (in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ALERT])) {
            // Log to Laravel's logger instead of triggering PHP error
            \Log::error("[Swagger] $message", $context);
        }
        
        // Debug and info are fine to pass through
        if (in_array($level, [LogLevel::DEBUG, LogLevel::INFO])) {
            \Log::info("[Swagger] $message", $context);
        }
        
        // Warnings and notices are silently ignored
    }
}
