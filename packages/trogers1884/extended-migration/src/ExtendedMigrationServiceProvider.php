<?php

namespace Trogers1884\ExtendedMigration;

use Illuminate\Support\ServiceProvider;
use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;
use Trogers1884\ExtendedMigration\Support\SchemaManager;

class ExtendedMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the configuration file
        $this->mergeConfigFrom(
            __DIR__.'/../config/extended-migration.php', 'extended-migration'
        );

        // Register the Schema Manager as a singleton
        $this->app->singleton(SchemaManagerInterface::class, function ($app) {
            $manager = new SchemaManager();

            // Register schemas from configuration
            $schemas = config('extended-migration.schemas.paths', []);
            $dependencies = config('extended-migration.schemas.dependencies', []);

            foreach ($schemas as $name => $path) {
                $schemaDependencies = $dependencies[$name] ?? [];
                $manager->registerSchema($name, $path, $schemaDependencies);
            }

            return $manager;
        });

        // Register the facade accessor
        $this->app->bind('schema-manager', function ($app) {
            return $app->make(SchemaManagerInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/extended-migration.php' => config_path('extended-migration.php'),
        ], 'extended-migration-config');
    }
}
