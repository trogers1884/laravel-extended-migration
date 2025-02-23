<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class SchemaMigrateCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:migrate
                          {schema? : The schema to migrate}
                          {--pretend : Simulate the migration operations}
                          {--all : Run migrations for all schemas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for specific schema or all schemas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $schema = $this->argument('schema');
            $pretend = $this->option('pretend');
            $all = $this->option('all');

            if ($all) {
                return $this->migrateAll($pretend);
            }

            if ($schema === null) {
                $this->error('Please specify a schema or use --all option');
                return 1;
            }

            return $this->migrateSingle($schema, $pretend);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Migrate a single schema.
     *
     * @param  string  $schema
     * @param  bool  $pretend
     * @return int
     */
    protected function migrateSingle(string $schema, bool $pretend): int
    {
        $this->validateSchemaExists($schema);

        if ($pretend) {
            $this->info("Simulating migrations for schema: {$schema}");
        } else {
            $this->info("Running migrations for schema: {$schema}");
        }

        $status = $this->runner->getPendingMigrationStatus();

        if (!isset($status[$schema])) {
            $this->info('Nothing to migrate.');
            return 0;
        }

        if (!$status[$schema]['can_run']) {
            $this->error('Cannot run migrations - dependencies not satisfied');
            $this->table(
                ['Schema', 'Pending', 'Dependencies', 'Ready'],
                $this->formatMigrationStatus([$schema => $status[$schema]])
            );
            return 1;
        }

        $results = $this->runner->runSchemaMigrations(
            $schema,
            $status[$schema]['pending'],
            $pretend
        );

        $this->displayResults([$schema => $results], 'migrate');
        return 0;
    }

    /**
     * Migrate all schemas.
     *
     * @param  bool  $pretend
     * @return int
     */
    protected function migrateAll(bool $pretend): int
    {
        $status = $this->runner->getPendingMigrationStatus();

        if (empty($status)) {
            $this->info('Nothing to migrate.');
            return 0;
        }

        $this->table(
            ['Schema', 'Pending', 'Dependencies', 'Ready'],
            $this->formatMigrationStatus($status)
        );

        if (!$this->confirmSchemaOperation(
            'migrate',
            array_keys($status)
        )) {
            return 1;
        }

        if ($pretend) {
            $this->info('Simulating migrations for all schemas');
        } else {
            $this->info('Running migrations for all schemas');
        }

        $results = $this->runner->runAllSchemaMigrations($pretend);
        $this->displayResults($results, 'migrate');

        return 0;
    }
}
