<?php

namespace Trogers1884\ExtendedMigration;

use Illuminate\Support\ServiceProvider;

class ExtendedMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/extended-migration.php',
            'extended-migration'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/extended-migration.php' => config_path('extended-migration.php'),
            ], 'config');

            // Register commands
            $this->commands([
                // Commands will be added here
            ]);
        }
    }
}
