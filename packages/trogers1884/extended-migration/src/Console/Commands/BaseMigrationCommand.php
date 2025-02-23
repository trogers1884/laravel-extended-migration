<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

use Illuminate\Console\Command;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface;
use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;

abstract class BaseMigrationCommand extends BaseSchemaCommand
{
    /**
     * The migration runner instance.
     *
     * @var \Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface
     */
    protected $runner;

    /**
     * Create a new command instance.
     *
     * @param  \Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface  $schemaManager
     * @param  \Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface  $runner
     * @return void
     */
    public function __construct(SchemaManagerInterface $schemaManager, SchemaMigrationRunnerInterface $runner)
    {
        parent::__construct($schemaManager);
        $this->runner = $runner;
    }

    /**
     * Format migration status output.
     *
     * @param  array  $migrations
     * @return array
     */
    protected function formatMigrationStatus(array $migrations): array
    {
        return collect($migrations)->map(function ($info, $name) {
            return [
                'Schema' => $name,
                'Pending' => count($info['pending']),
                'Dependencies' => implode(', ', $info['dependencies']),
                'Ready' => $info['can_run'] ? 'Yes' : 'No',
            ];
        })->values()->toArray();
    }

    /**
     * Format migration results for display.
     *
     * @param  array  $results
     * @return array
     */
    protected function formatMigrationResults(array $results): array
    {
        $formatted = [];
        foreach ($results as $schema => $migrations) {
            foreach ($migrations as $migration) {
                $formatted[] = [
                    'Schema' => $schema,
                    'Migration' => $migration,
                ];
            }
        }
        return $formatted;
    }

    /**
     * Confirm dangerous operation when multiple schemas are affected.
     *
     * @param  string  $operation
     * @param  array  $schemas
     * @return bool
     */
    protected function confirmSchemaOperation(string $operation, array $schemas): bool
    {
        if (empty($schemas)) {
            return true;
        }

        $schemaList = implode(', ', $schemas);
        return $this->confirm(
            "Are you sure you want to {$operation} the following schemas: {$schemaList}?",
            false
        );
    }

    /**
     * Display migration operation results.
     *
     * @param  array  $results
     * @param  string  $operation
     * @return void
     */
    protected function displayResults(array $results, string $operation): void
    {
        if (empty($results)) {
            $this->info('Nothing to ' . $operation);
            return;
        }

        $this->table(
            ['Schema', 'Migration'],
            $this->formatMigrationResults($results)
        );
    }
}
