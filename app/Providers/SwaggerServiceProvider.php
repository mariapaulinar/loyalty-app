<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SwaggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure the analyser for l5-swagger at runtime
        // We can't put object instances in config files (they must be serializable)
        // Skip this when config:cache is running to avoid serialization errors
        if ($this->app->runningInConsole()) {
            $command = $_SERVER['argv'][1] ?? '';
            $isConfigCaching = str_contains($command, 'config:cache') || str_contains($command, 'optimize');
            
            if (!$isConfigCaching && class_exists(\OpenApi\Analysers\ReflectionAnalyser::class)) {
                config([
                    'l5-swagger.defaults.scanOptions.analyser' => new \OpenApi\Analysers\ReflectionAnalyser([
                        new \OpenApi\Analysers\AttributeAnnotationFactory(),
                        new \OpenApi\Analysers\DocBlockAnnotationFactory(),
                    ]),
                ]);
            }
            
            // Suppress swagger-php warnings about PathItem
            $oldHandler = set_error_handler(function ($severity, $message, $file, $line) use (&$oldHandler) {
                if (str_contains($file, 'swagger-php') && str_contains($message, 'PathItem')) {
                    return true;
                }
                
                if ($oldHandler) {
                    return $oldHandler($severity, $message, $file, $line);
                }
                
                return false;
            });
        }
    }
}
