<?php

namespace Trogers1884\ExtendedMigration;

use Illuminate\Support\ServiceProvider;
use Trogers1884\ExtendedMigration\Support\SchemaManager;
use Trogers1884\ExtendedMigration\Console\Commands\ListSchemaCommand;
use Trogers1884\ExtendedMigration\Console\Commands\CreateSchemaCommand;
use Trogers1884\ExtendedMigration\Console\Commands\ValidateSchemaCommand;
use Trogers1884\ExtendedMigration\Console\Commands\SchemaMigrateCommand;
use Trogers1884\ExtendedMigration\Console\Commands\SchemaRollbackCommand;
use Trogers1884\ExtendedMigration\Console\Commands\SchemaMigrationStatusCommand;
use Trogers1884\ExtendedMigration\Console\Commands\SchemaResetCommand;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface;
use Trogers1884\ExtendedMigration\Database\Migrations\SchemaMigrationRepository;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface;
use Trogers1884\ExtendedMigration\Database\Migrations\SchemaMigrationRunner;

class ExtendedMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Schema Manager singleton
        $this->app->singleton('schema-manager', function ($app) {
            return new SchemaManager();
        });

        // Register Schema Migration Repository
        $this->app->singleton(SchemaMigrationRepositoryInterface::class, function ($app) {
            return new SchemaMigrationRepository(
                $app['db'],
                config('database.migrations', 'migrations')
            );
        });

        // Register Schema Migration Runner
        $this->app->singleton(SchemaMigrationRunnerInterface::class, function ($app) {
            return new SchemaMigrationRunner(
                $app['migrator'],
                $app['schema-manager'],
                $app->make(SchemaMigrationRepositoryInterface::class)
            );
        });

        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/extended-migration.php',
            'extended-migration'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListSchemaCommand::class,
                CreateSchemaCommand::class,
                ValidateSchemaCommand::class,
                SchemaMigrateCommand::class,
                SchemaRollbackCommand::class,
                SchemaMigrationStatusCommand::class,
                SchemaResetCommand::class,
            ]);
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/extended-migration.php' => config_path('extended-migration.php'),
        ], 'extended-migration-config');

        // Register schemas from config
        $this->registerSchemasFromConfig();
    }

    /**
     * Register schemas defined in the configuration.
     */
    private function registerSchemasFromConfig(): void
    {
        $config = $this->app['config']['extended-migration.schemas'];
        $schemaManager = $this->app['schema-manager'];

        if (isset($config['paths']) && is_array($config['paths'])) {
            foreach ($config['paths'] as $name => $path) {
                $dependencies = $config['dependencies'][$name] ?? [];

                try {
                    $schemaManager->registerSchema($name, $path, $dependencies);
                } catch (\InvalidArgumentException $e) {
                    // Log error but don't prevent application boot
                    $this->app['log']->warning(
                        "Failed to register schema '{$name}' from config: " . $e->getMessage()
                    );
                }
            }
        }
    }
}
