<?php

namespace Trogers1884\ExtendedMigration\Database\Migrations;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\Migrator;
use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface;

class SchemaMigrationRunner implements SchemaMigrationRunnerInterface
{
    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * The schema manager instance.
     *
     * @var \Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface
     */
    protected $schemaManager;

    /**
     * The schema migration repository instance.
     *
     * @var \Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Whether to use transactions for migrations.
     *
     * @var bool
     */
    protected $useTransactions = true;

    /**
     * Create a new migration runner instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @param  \Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface  $schemaManager
     * @param  \Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface  $repository
     * @return void
     */
    public function __construct(
        Migrator $migrator,
        SchemaManagerInterface $schemaManager,
        SchemaMigrationRepositoryInterface $repository
    ) {
        $this->migrator = $migrator;
        $this->schemaManager = $schemaManager;
        $this->repository = $repository;
    }

    /**
     * Run migrations for a specific schema.
     *
     * @param  string  $schema
     * @param  array|string[]  $paths
     * @param  bool  $pretend
     * @return array
     */
    public function runSchemaMigrations(string $schema, array $paths, bool $pretend = false): array
    {
        if (!$this->schemaManager->hasSchema($schema)) {
            throw new \InvalidArgumentException("Schema '{$schema}' does not exist.");
        }

        // Ensure schema dependencies are satisfied
        if (!$this->checkDependencies($schema)) {
            throw new \RuntimeException("Schema '{$schema}' has unsatisfied dependencies.");
        }

        $migrations = $this->resolveMigrationOrder($schema, $paths);
        $ran = [];

        if ($this->useTransactions) {
            $this->migrator->usingConnection(null, function () use ($migrations, $schema, $pretend, &$ran) {
                $ran = $this->runMigrationBatch($schema, $migrations, $pretend);
            });
        } else {
            $ran = $this->runMigrationBatch($schema, $migrations, $pretend);
        }

        return $ran;
    }

    /**
     * Run a batch of migrations within a schema.
     *
     * @param  string  $schema
     * @param  array  $migrations
     * @param  bool  $pretend
     * @return array
     */
    protected function runMigrationBatch(string $schema, array $migrations, bool $pretend): array
    {
        $ran = [];
        $batch = $this->repository->getNextBatchNumber($schema);

        foreach ($migrations as $migration) {
            $fullPath = $this->schemaManager->getPath($schema) . '/' . $migration;

            // Run the migration
            $this->migrator->run([$fullPath], ['pretend' => $pretend]);

            if (!$pretend) {
                $this->repository->log($schema, $migration, $batch);
            }

            $ran[] = $migration;
        }

        return $ran;
    }

    /**
     * Rollback migrations for a specific schema.
     *
     * @param  string  $schema
     * @param  array|null  $migrations
     * @param  bool  $pretend
     * @return array
     */
    public function rollbackSchemaMigrations(string $schema, ?array $migrations = null, bool $pretend = false): array
    {
        if (!$this->schemaManager->hasSchema($schema)) {
            throw new \InvalidArgumentException("Schema '{$schema}' does not exist.");
        }

        // If no specific migrations provided, get the last batch
        if ($migrations === null) {
            $migrations = $this->repository->getLast($schema);
        }

        $rolledBack = [];

        if ($this->useTransactions) {
            $this->migrator->usingConnection(null, function () use ($migrations, $schema, $pretend, &$rolledBack) {
                $rolledBack = $this->rollbackMigrationBatch($schema, $migrations, $pretend);
            });
        } else {
            $rolledBack = $this->rollbackMigrationBatch($schema, $migrations, $pretend);
        }

        return $rolledBack;
    }

    /**
     * Rollback a batch of migrations within a schema.
     *
     * @param  string  $schema
     * @param  array  $migrations
     * @param  bool  $pretend
     * @return array
     */
    protected function rollbackMigrationBatch(string $schema, array $migrations, bool $pretend): array
    {
        $rolledBack = [];

        foreach ($migrations as $migration) {
            $fullPath = $this->schemaManager->getPath($schema) . '/' . $migration;

            // Rollback the migration
            $this->migrator->rollback([$fullPath], ['pretend' => $pretend]);

            if (!$pretend) {
                $this->repository->delete($schema, $migration);
            }

            $rolledBack[] = $migration;
        }

        return $rolledBack;
    }

    /**
     * Get the ordered list of migrations to run, respecting dependencies.
     *
     * @param  string  $schema
     * @param  array  $migrations
     * @return array
     */
    public function resolveMigrationOrder(string $schema, array $migrations): array
    {
        // Get all dependencies for the schema
        $dependencies = $this->schemaManager->getDependencies($schema);

        // Ensure all dependent schemas are migrated first
        foreach ($dependencies as $dependency) {
            $pendingDependencyMigrations = $this->repository->getPendingMigrations($dependency);
            if (!empty($pendingDependencyMigrations)) {
                throw new \RuntimeException(
                    "Schema '{$schema}' depends on '{$dependency}' which has pending migrations."
                );
            }
        }

        // Return migrations in their natural order
        sort($migrations);
        return $migrations;
    }

    /**
     * Check if all dependencies for a schema are satisfied.
     *
     * @param  string  $schema
     * @return bool
     */
    protected function checkDependencies(string $schema): bool
    {
        $dependencies = $this->schemaManager->getDependencies($schema);

        foreach ($dependencies as $dependency) {
            if ($this->repository->getPendingMigrations($dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reset migrations for a specific schema.
     *
     * @param  string  $schema
     * @param  bool  $pretend
     * @return array
     */
    public function resetSchema(string $schema, bool $pretend = false): array
    {
        $migrations = array_reverse($this->repository->getMigrationHistory($schema));
        return $this->rollbackSchemaMigrations($schema, $migrations, $pretend);
    }

    /**
     * Run all pending migrations for all schemas in dependency order.
     *
     * @param  bool  $pretend
     * @return array
     */
    public function runAllSchemaMigrations(bool $pretend = false): array
    {
        $results = [];
        $schemas = $this->resolveSchemasInDependencyOrder();

        foreach ($schemas as $schema) {
            $pending = $this->repository->getPendingMigrations($schema);
            if (!empty($pending)) {
                $results[$schema] = $this->runSchemaMigrations($schema, $pending, $pretend);
            }
        }

        return $results;
    }

    /**
     * Rollback the last batch of migrations for all schemas in reverse dependency order.
     *
     * @param  bool  $pretend
     * @return array
     */
    public function rollbackAllSchemas(bool $pretend = false): array
    {
        $results = [];
        $schemas = array_reverse($this->resolveSchemasInDependencyOrder());

        foreach ($schemas as $schema) {
            $last = $this->repository->getLast($schema);
            if (!empty($last)) {
                $results[$schema] = $this->rollbackSchemaMigrations($schema, $last, $pretend);
            }
        }

        return $results;
    }

    /**
     * Get schemas ordered by their dependencies.
     *
     * @return array
     */
    protected function resolveSchemasInDependencyOrder(): array
    {
        $schemas = $this->schemaManager->getSchemas();
        $ordered = [];
        $visited = [];

        $visit = function ($schema) use (&$visit, &$ordered, &$visited, $schemas) {
            if (!isset($visited[$schema['name']])) {
                $visited[$schema['name']] = true;

                foreach ($schema['dependencies'] as $dependency) {
                    if (isset($schemas[$dependency])) {
                        $visit($schemas[$dependency]);
                    }
                }

                $ordered[] = $schema['name'];
            }
        };

        foreach ($schemas as $schema) {
            $visit($schema);
        }

        return $ordered;
    }

    /**
     * Get the status of pending migrations across all schemas.
     *
     * @return array
     */
    public function getPendingMigrationStatus(): array
    {
        $status = [];
        $schemas = $this->schemaManager->getSchemas();

        foreach ($schemas as $schema) {
            $pending = $this->repository->getPendingMigrations($schema['name']);
            if (!empty($pending)) {
                $status[$schema['name']] = [
                    'pending' => $pending,
                    'dependencies' => $schema['dependencies'],
                    'can_run' => $this->checkDependencies($schema['name']),
                ];
            }
        }

        return $status;
    }

    /**
     * Set whether to use transactions for migrations.
     *
     * @param  bool  $useTransactions
     * @return void
     */
    public function useTransactions(bool $useTransactions): void
    {
        $this->useTransactions = $useTransactions;
    }

    /**
     * Get whether the runner is using transactions.
     *
     * @return bool
     */
    public function isUsingTransactions(): bool
    {
        return $this->useTransactions;
    }
}
